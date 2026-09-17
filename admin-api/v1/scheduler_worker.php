<?php

if (!defined('IN_CRONLITE')) {
    exit();
}

/**
 * 统计指定任务当前积压的待处理订单数
 * 核心优化：
 * 1. 凡是标记【已完成】、已取消、已退款，或进度已达 100% 的订单，永久自动隔离归档，绝不再统计和参与后续轮询！
 * 2. 补刷属于人工操作单次触发，不进行高频无意义死循环提交。
 */
function scheduler_count_pending($taskId) {
    global $DB;
    $completedFilter = "status NOT IN ('已完成','已取消','已退款') AND process NOT LIKE '100%'";

    switch ($taskId) {
        case 'order_dispatch':
        case 'add':
            // 引擎 1：纯粹扫描待出单新订单 (dockstatus=0)
            return intval($DB->count("SELECT COUNT(*) FROM `qingka_wangke_order` WHERE dockstatus='0' AND status NOT IN ('已取消','已退款')"));

        case 'progress_active':
        case 'cc':
        case 'bb':
        case 'aa':
        case 'plsx':
            // 引擎 2：正在刷课活跃订单 (包含进行中、待处理、待上号、上号中、重刷中、待刷新等所有未归档订单)
            return intval($DB->count("SELECT COUNT(*) FROM `qingka_wangke_order` WHERE dockstatus=1 AND status NOT IN ('已完成','已取消','已退款','待考试','平时分','平时分中') AND $completedFilter"));

        case 'progress_exam':
        case 'dd':
        case 'ee':
            // 引擎 3：待考试与平时分慢速收尾订单 (待考试、平时分、平时分中、已暂停)
            return intval($DB->count("SELECT COUNT(*) FROM `qingka_wangke_order` WHERE dockstatus=1 AND status IN ('待考试','平时分','平时分中','已暂停') AND status NOT IN ('已完成','已取消','已退款')"));

        default:
            return 0;
    }
}

/**
 * 执行指定代号的单个调度任务
 */
function scheduler_execute_task($taskId) {
    global $DB, $date;
    $st = microtime(true);
    $timeStr = date('H:i:s');
    $logs = array();
    $logs[] = "[$timeStr] >>> 调度引擎启动: [$taskId] ...";

    $successCount = 0;
    $failedCount = 0;
    $limit = 30;

    // 自动加载货源驱动
    if (!function_exists('addWk') && file_exists(ROOT . '../Checkorder/xdjk.php')) {
        require_once ROOT . '../Checkorder/xdjk.php';
    }
    if (!function_exists('processCx') && file_exists(ROOT . '../Checkorder/jdjk.php')) {
        require_once ROOT . '../Checkorder/jdjk.php';
    }

    // ==========================================
    // 引擎 1：新订单自动出单 (order_dispatch / add)
    // ==========================================
    if ($taskId === 'order_dispatch' || $taskId === 'add') {
        $res = $DB->query("SELECT * FROM `qingka_wangke_order` WHERE dockstatus='0' AND status NOT IN ('已取消','已退款') ORDER BY oid ASC LIMIT $limit");
        $orders = array();
        while ($r = $DB->fetch($res)) { $orders[] = $r; }

        $pendingNum = count($orders);
        $logs[] = "[$timeStr] 扫描到待出单新订单: $pendingNum 笔";

        if ($pendingNum === 0) {
            $logs[] = "[$timeStr] 当前无待提交的新订单，任务空闲退出。";
        }

        foreach ($orders as $b) {
            $oid = $b['oid'];
            if (empty($b['school'])) {
                $DB->query("UPDATE `qingka_wangke_order` SET status='请检查学校名字', dockstatus=2 WHERE oid='$oid'");
                $logs[] = "  [-] 订单 #$oid 缺少学校名称，已标记异常拦截";
                $failedCount++;
                continue;
            }
            if (empty($b['user'])) {
                $DB->query("UPDATE `qingka_wangke_order` SET status='请检查账号', dockstatus=2 WHERE oid='$oid'");
                $logs[] = "  [-] 订单 #$oid 缺少登录账号，已标记异常拦截";
                $failedCount++;
                continue;
            }

            if (function_exists('addWk')) {
                $result = addWk($oid);
                $d = $DB->get_row("SELECT * FROM `qingka_wangke_class` WHERE cid='{$b['cid']}' LIMIT 1");

                if (isset($result['code']) && $result['code'] == 1) {
                    $yid = isset($result['yid']) ? daddslashes($result['yid']) : '';
                    $hid = isset($d['docking']) ? daddslashes($d['docking']) : '0';
                    $DB->query("UPDATE `qingka_wangke_order` SET hid='$hid', status='进行中', dockstatus=1, yid='$yid' WHERE oid='$oid'");
                    $logs[] = "  [+] 订单 #$oid [{$b['kcname']}] 提交货源成功，上游订单ID: " . ($yid ?: 'OK');
                    $successCount++;
                } else {
                    $msg = isset($result['msg']) ? $result['msg'] : '货源返回异常';
                    $DB->query("UPDATE `qingka_wangke_order` SET dockstatus=2, status='提交失败' WHERE oid='$oid'");
                    $logs[] = "  [x] 订单 #$oid [{$b['kcname']}] 提交失败: $msg";
                    $failedCount++;
                }
            } else {
                $logs[] = "  [x] 货源出单驱动 addWk 未加载";
                $failedCount++;
            }
        }
    }

    // =========================================================================
    // 引擎 2：活跃看课·高频同步引擎 (progress_active / cc / bb / aa / plsx)
    // =========================================================================
    elseif ($taskId === 'progress_active' || in_array($taskId, array('cc', 'bb', 'aa', 'plsx'), true)) {
        $completedFilter = "status NOT IN ('已完成','已取消','已退款') AND process NOT LIKE '100%'";
        // 覆盖所有已对接上游、但尚未结课归档的订单（包括待处理、待上号、进行中、上号中、重刷中、待刷新等）
        $where = "dockstatus=1 AND status NOT IN ('已完成','已取消','已退款','待考试','平时分','平时分中') AND $completedFilter";

        $res = $DB->query("SELECT * FROM `qingka_wangke_order` WHERE $where ORDER BY oid ASC LIMIT $limit");
        $orders = array();
        while ($r = $DB->fetch($res)) { $orders[] = $r; }
        $logs[] = "[$timeStr] 扫描到看课中活跃订单: " . count($orders) . " 笔";

        if (count($orders) === 0) {
            $logs[] = "[$timeStr] 当前无刷课中的活跃订单，任务跳过。";
        }

        foreach ($orders as $a) {
            $oid = $a['oid'];
            if (function_exists('processCx')) {
                $results = processCx($oid);
                if (is_array($results) && !empty($results)) {
                    $updated = false;
                    $cleanOrderKc = trim(preg_replace('/[【\(（]课程进度.*?[】\)）]/u', '', $a['kcname']));
                    
                    // 1. 优先按课程名精准匹配
                    foreach ($results as $item) {
                        if (!is_array($item) || !isset($item['kcname'])) continue;
                        $cleanItemKc = trim(preg_replace('/[【\(（]课程进度.*?[】\)）]/u', '', $item['kcname']));
                        if ($item['kcname'] === $a['kcname'] || $cleanItemKc === $cleanOrderKc) {
                            $newProcess = isset($item['process']) ? daddslashes($item['process']) : $a['process'];
                            $newStatus = isset($item['status_text']) ? daddslashes($item['status_text']) : $a['status'];
                            $newRemarks = isset($item['remarks']) ? daddslashes($item['remarks']) : $a['remarks'];
                            $newKcks = isset($item['kcks']) ? daddslashes($item['kcks']) : $a['courseStartTime'];
                            $newKcjs = isset($item['kcjs']) ? daddslashes($item['kcjs']) : $a['courseEndTime'];
                            $uYid = isset($item['yid']) ? daddslashes(strval($item['yid'])) : '';

                            $numVal = floatval(preg_replace('/[^\d.]/', '', (string)$newProcess));
                            $isFinished = ($newStatus === '已完成' || $newStatus === '已结课' || $newStatus === '已学完' || ($numVal >= 100 && $newStatus !== '异常' && $newStatus !== '待重刷' && $newStatus !== '补刷中'));
                            $isExamStage = in_array($newStatus, array('待考试', '平时分', '平时分中', '已暂停'), true);

                            if ($isFinished) {
                                $newStatus = '已完成';
                            }

                            $setYidSql = (!empty($uYid) && $uYid !== '0') ? ", `yid`='$uYid'" : '';
                            $DB->query("UPDATE `qingka_wangke_order` SET 
                                `status`='$newStatus', 
                                `process`='$newProcess', 
                                `remarks`='$newRemarks',
                                `courseStartTime`='$newKcks',
                                `courseEndTime`='$newKcjs',
                                `finalupdate`=NOW() 
                                $setYidSql 
                                WHERE oid='$oid'");

                            if ($isFinished) {
                                $logs[] = "  [✔] 订单 #$oid [{$a['kcname']}] 进度达 100% 并结课，已自动标记【已完成】归档！";
                            } elseif ($isExamStage) {
                                $logs[] = "  [🐢] 订单 #$oid [{$a['kcname']}] 看课完毕转入【{$newStatus}】，已自动移交慢速巡检池！";
                            } else {
                                $logs[] = "  [~] 订单 #$oid [{$a['kcname']}] 状态: {$newStatus}, 进度: {$a['process']} -> {$newProcess}";
                            }
                            $updated = true;
                            $successCount++;
                            break;
                        }
                    }

                    // 2. 若仅有1门课程且未匹配到名称时进行兜底匹配
                    if (!$updated && count($results) === 1 && isset($results[0]['status_text'])) {
                        $item = $results[0];
                        $newProcess = isset($item['process']) ? daddslashes($item['process']) : $a['process'];
                        $newStatus = isset($item['status_text']) ? daddslashes($item['status_text']) : $a['status'];
                        $newRemarks = isset($item['remarks']) ? daddslashes($item['remarks']) : $a['remarks'];
                        $newKcks = isset($item['kcks']) ? daddslashes($item['kcks']) : $a['courseStartTime'];
                        $newKcjs = isset($item['kcjs']) ? daddslashes($item['kcjs']) : $a['courseEndTime'];
                        $uYid = isset($item['yid']) ? daddslashes(strval($item['yid'])) : '';

                        $numVal = floatval(preg_replace('/[^\d.]/', '', (string)$newProcess));
                        $isFinished = ($newStatus === '已完成' || $newStatus === '已结课' || $newStatus === '已学完' || ($numVal >= 100 && $newStatus !== '异常' && $newStatus !== '待重刷' && $newStatus !== '补刷中'));
                        $isExamStage = in_array($newStatus, array('待考试', '平时分', '平时分中', '已暂停'), true);

                        if ($isFinished) {
                            $newStatus = '已完成';
                        }

                        $setYidSql = (!empty($uYid) && $uYid !== '0') ? ", `yid`='$uYid'" : '';
                        $DB->query("UPDATE `qingka_wangke_order` SET 
                            `status`='$newStatus', 
                            `process`='$newProcess', 
                            `remarks`='$newRemarks',
                            `courseStartTime`='$newKcks',
                            `courseEndTime`='$newKcjs',
                            `finalupdate`=NOW() 
                            $setYidSql 
                            WHERE oid='$oid'");

                        if ($isFinished) {
                            $logs[] = "  [✔] 订单 #$oid [{$a['kcname']}] (单课兜底) 进度达 100% 并结课，已自动标记【已完成】归档！";
                        } elseif ($isExamStage) {
                            $logs[] = "  [🐢] 订单 #$oid [{$a['kcname']}] (单课兜底) 看课完毕转入【{$newStatus}】，已自动移交慢速巡检池！";
                        } else {
                            $logs[] = "  [~] 订单 #$oid [{$a['kcname']}] (单课兜底) 状态: {$newStatus}, 进度: {$a['process']} -> {$newProcess}";
                        }
                        $updated = true;
                        $successCount++;
                    }

                    if (!$updated) {
                        $logs[] = "  [?] 订单 #$oid 上游未返回对应课程进度";
                    }
                } else {
                    $logs[] = "  [-] 订单 #$oid 上游无进度响应数据";
                    $failedCount++;
                }
            } else {
                $logs[] = "  [x] 进度驱动函数 processCx 未加载";
                $failedCount++;
            }
        }
    }

    // =========================================================================
    // 引擎 3：待考试与平时分·慢速巡检引擎 (progress_exam / dd / ee)
    // =========================================================================
    elseif ($taskId === 'progress_exam' || in_array($taskId, array('dd', 'ee'), true)) {
        $where = "dockstatus=1 AND status IN ('待考试','平时分','平时分中','已暂停') AND status NOT IN ('已完成','已取消','已退款') AND process NOT LIKE '100%'";

        $res = $DB->query("SELECT * FROM `qingka_wangke_order` WHERE $where ORDER BY oid ASC LIMIT $limit");
        $orders = array();
        while ($r = $DB->fetch($res)) { $orders[] = $r; }
        $logs[] = "[$timeStr] 扫描到待考试/平时分收尾慢速订单: " . count($orders) . " 笔";

        if (count($orders) === 0) {
            $logs[] = "[$timeStr] 当前无待考试或录平时分的收尾订单，任务跳过。";
        }

        foreach ($orders as $a) {
            $oid = $a['oid'];
            if (function_exists('processCx')) {
                $results = processCx($oid);
                if (is_array($results) && !empty($results)) {
                    $updated = false;
                    $cleanOrderKc = trim(preg_replace('/[【\(（]课程进度.*?[】\)）]/u', '', $a['kcname']));

                    foreach ($results as $item) {
                        if (!is_array($item) || !isset($item['kcname'])) continue;
                        $cleanItemKc = trim(preg_replace('/[【\(（]课程进度.*?[】\)）]/u', '', $item['kcname']));
                        if ($item['kcname'] === $a['kcname'] || $cleanItemKc === $cleanOrderKc) {
                            $newProcess = isset($item['process']) ? daddslashes($item['process']) : $a['process'];
                            $newStatus = isset($item['status_text']) ? daddslashes($item['status_text']) : $a['status'];
                            $newRemarks = isset($item['remarks']) ? daddslashes($item['remarks']) : $a['remarks'];
                            $newKcks = isset($item['kcks']) ? daddslashes($item['kcks']) : $a['courseStartTime'];
                            $newKcjs = isset($item['kcjs']) ? daddslashes($item['kcjs']) : $a['courseEndTime'];
                            $uYid = isset($item['yid']) ? daddslashes(strval($item['yid'])) : '';

                            $numVal = floatval(preg_replace('/[^\d.]/', '', (string)$newProcess));
                            $isFinished = ($newStatus === '已完成' || $newStatus === '已结课' || $newStatus === '已学完' || ($numVal >= 100 && $newStatus !== '异常' && $newStatus !== '待重刷' && $newStatus !== '补刷中'));

                            if ($isFinished) {
                                $newStatus = '已完成';
                            }

                            $setYidSql = (!empty($uYid) && $uYid !== '0') ? ", `yid`='$uYid'" : '';
                            $DB->query("UPDATE `qingka_wangke_order` SET 
                                `status`='$newStatus', 
                                `process`='$newProcess', 
                                `remarks`='$newRemarks',
                                `courseStartTime`='$newKcks',
                                `courseEndTime`='$newKcjs',
                                `finalupdate`=NOW() 
                                $setYidSql 
                                WHERE oid='$oid'");

                            if ($isFinished) {
                                $logs[] = "  [✔] 订单 #$oid [{$a['kcname']}] 考试/平时分已完结，已成功归档！";
                            } else {
                                $logs[] = "  [~] 订单 #$oid [{$a['kcname']}] 慢速巡检状态保持: 【{$newStatus}】, 进度: {$newProcess}%";
                            }
                            $updated = true;
                            $successCount++;
                            break;
                        }
                    }

                    if (!$updated && count($results) === 1 && isset($results[0]['status_text'])) {
                        $item = $results[0];
                        $newProcess = isset($item['process']) ? daddslashes($item['process']) : $a['process'];
                        $newStatus = isset($item['status_text']) ? daddslashes($item['status_text']) : $a['status'];
                        $newRemarks = isset($item['remarks']) ? daddslashes($item['remarks']) : $a['remarks'];
                        $newKcks = isset($item['kcks']) ? daddslashes($item['kcks']) : $a['courseStartTime'];
                        $newKcjs = isset($item['kcjs']) ? daddslashes($item['kcjs']) : $a['courseEndTime'];
                        $uYid = isset($item['yid']) ? daddslashes(strval($item['yid'])) : '';

                        $numVal = floatval(preg_replace('/[^\d.]/', '', (string)$newProcess));
                        $isFinished = ($newStatus === '已完成' || $newStatus === '已结课' || $newStatus === '已学完' || ($numVal >= 100 && $newStatus !== '异常' && $newStatus !== '待重刷' && $newStatus !== '补刷中'));

                        if ($isFinished) {
                            $newStatus = '已完成';
                        }

                        $setYidSql = (!empty($uYid) && $uYid !== '0') ? ", `yid`='$uYid'" : '';
                        $DB->query("UPDATE `qingka_wangke_order` SET 
                            `status`='$newStatus', 
                            `process`='$newProcess', 
                            `remarks`='$newRemarks',
                            `courseStartTime`='$newKcks',
                            `courseEndTime`='$newKcjs',
                            `finalupdate`=NOW() 
                            $setYidSql 
                            WHERE oid='$oid'");

                        if ($isFinished) {
                            $logs[] = "  [✔] 订单 #$oid [{$a['kcname']}] (单课兜底) 考试/平时分已完结，已成功归档！";
                        } else {
                            $logs[] = "  [~] 订单 #$oid [{$a['kcname']}] (单课兜底) 慢速巡检状态保持: 【{$newStatus}】, 进度: {$newProcess}%";
                        }
                        $updated = true;
                        $successCount++;
                    }
                    if (!$updated) {
                        $logs[] = "  [?] 订单 #$oid 上游未返回对应课程进度";
                    }
                } else {
                    $logs[] = "  [-] 订单 #$oid 上游无进度响应数据";
                    $failedCount++;
                }
            } else {
                $logs[] = "  [x] 进度驱动函数 processCx 未加载";
                $failedCount++;
            }
        }
    }

    // 容错处理旧的单独 plbs 任务 (如果还有遗留)
    elseif ($taskId === 'plbs') {
        $logs[] = "[$timeStr] 提示：补刷机制已升级为人工单次触发受控模式，不再进行重复后台轮询。";
        $successCount = 0;
    }

    $costMs = round((microtime(true) - $st) * 1000);
    $endTimeStr = date('H:i:s');
    $summary = "[$endTimeStr] 调度完成，耗时 {$costMs}ms | 成功处理: {$successCount} 笔，异常/跳过: {$failedCount} 笔";
    $logs[] = $summary;

    $content = implode("\n", $logs);
    $status = $failedCount > 0 && $successCount === 0 ? 0 : 1;
    $nowDateTime = date('Y-m-d H:i:s');

    // 记录到日志表
    $contentSafe = daddslashes($content);
    $DB->query("INSERT INTO `qingka_wangke_cron_log` (`task_id`, `content`, `status`, `cost_ms`, `processed_count`, `created_at`) VALUES ('$taskId', '$contentSafe', '$status', '$costMs', '$successCount', '$nowDateTime')");

    // 触发历史调度日志自动瘦身与清理 (保持数据库轻盈)
    scheduler_auto_prune_logs($taskId);

    // 更新任务配置表指标
    $summarySafe = daddslashes($summary);
    $DB->query("UPDATE `qingka_wangke_cron_task` SET 
        `last_run_time`='$nowDateTime',
        `last_cost_ms`='$costMs',
        `last_status`='$status',
        `last_result`='$summarySafe',
        `total_runs`=`total_runs`+1,
        `total_success`=`total_success`+'$successCount',
        `total_failed`=`total_failed`+'$failedCount'
        WHERE id='$taskId'");

    return array(
        'task_id' => $taskId,
        'cost_ms' => $costMs,
        'success_count' => $successCount,
        'failed_count' => $failedCount,
        'summary' => $summary,
        'logs' => $content
    );
}

/**
 * 自动滚动清理历史调度日志 (全自动瘦身)
 * 1. 自动删除超过 3 天的历史过期日志
 * 2. 自动清理旧版废弃任务残留的孤立日志
 * 3. 单个任务严格保持最多保留最近 200 条记录，超出部分自动截断
 */
function scheduler_auto_prune_logs($taskId = '') {
    global $DB;
    // 1. 删除 3 天前的过期历史日志
    $DB->query("DELETE FROM `qingka_wangke_cron_log` WHERE `created_at` < DATE_SUB(NOW(), INTERVAL 3 DAY)");

    // 2. 清除废弃旧任务残留日志
    $DB->query("DELETE FROM `qingka_wangke_cron_log` WHERE `task_id` NOT IN ('order_dispatch', 'progress_active', 'progress_exam')");

    // 3. 单任务超过 200 条时自动截断多余记录
    if (!empty($taskId)) {
        $taskIdSafe = daddslashes($taskId);
        $count = intval($DB->count("SELECT COUNT(*) FROM `qingka_wangke_cron_log` WHERE `task_id`='$taskIdSafe'"));
        if ($count > 200) {
            $cutoff = $DB->get_row("SELECT id FROM `qingka_wangke_cron_log` WHERE `task_id`='$taskIdSafe' ORDER BY id DESC LIMIT 200, 1");
            if ($cutoff && !empty($cutoff['id'])) {
                $cutoffId = intval($cutoff['id']);
                $DB->query("DELETE FROM `qingka_wangke_cron_log` WHERE `task_id`='$taskIdSafe' AND id <= '$cutoffId'");
            }
        }
    }
}

/**
 * 一键执行所有已开启的任务
 */
function scheduler_run_all_enabled() {
    global $DB;
    $res = $DB->query("SELECT id FROM `qingka_wangke_cron_task` WHERE enabled=1 ORDER BY CASE id 
        WHEN 'order_dispatch' THEN 1 
        WHEN 'progress_active' THEN 2 
        WHEN 'progress_exam' THEN 3 
        ELSE 9 END ASC");
    $reports = array();
    while ($r = $DB->fetch($res)) {
        $reports[] = scheduler_execute_task($r['id']);
    }
    return $reports;
}

// ==========================================
// 调度器 API 控制接口分发
// ==========================================

if ($action === 'scheduler-tasks-list') {
    api_require_login(isset($islogin) ? $islogin : 0);
    $currentUid = intval($userrow['uid']);
    if ($currentUid !== 1) {
        api_respond(403, '仅超级管理员可管理调度任务');
    }

    $res = $DB->query("SELECT * FROM `qingka_wangke_cron_task` ORDER BY CASE id 
        WHEN 'order_dispatch' THEN 1 
        WHEN 'progress_active' THEN 2 
        WHEN 'progress_exam' THEN 3 
        ELSE 9 END ASC");

    $tasks = array();
    $totalRunsAll = 0;
    $totalSuccessAll = 0;

    while ($r = $DB->fetch($res)) {
        $pending = scheduler_count_pending($r['id']);
        
        // 获取最新一条日志
        $lastLog = $DB->get_row("SELECT * FROM `qingka_wangke_cron_log` WHERE task_id='{$r['id']}' ORDER BY id DESC LIMIT 1");

        $tasks[] = array(
            'id' => (string)$r['id'],
            'name' => (string)$r['name'],
            'description' => (string)$r['description'],
            'enabled' => intval($r['enabled']) === 1,
            'interval_mins' => intval($r['interval_mins']),
            'last_run_time' => (string)$r['last_run_time'],
            'last_cost_ms' => intval($r['last_cost_ms']),
            'last_status' => intval($r['last_status']),
            'last_result' => (string)$r['last_result'],
            'total_runs' => intval($r['total_runs']),
            'total_success' => intval($r['total_success']),
            'total_failed' => intval($r['total_failed']),
            'pending_count' => $pending,
            'latest_log' => $lastLog ? (string)$lastLog['content'] : '暂无历史运行日志'
        );

        $totalRunsAll += intval($r['total_runs']);
        $totalSuccessAll += intval($r['total_success']);
    }

    $totalLogs = intval($DB->count("SELECT COUNT(*) FROM `qingka_wangke_cron_log`"));

    // 查询宝塔计划任务实时心跳
    $rowHeart = $DB->get_row("SELECT `v` FROM `qingka_wangke_config` WHERE `k`='bt_cron_heartbeat' LIMIT 1");
    $lastHeartbeat = ($rowHeart && !empty($rowHeart['v'])) ? intval($rowHeart['v']) : 0;
    $now = time();
    $elapsed = $lastHeartbeat > 0 ? ($now - $lastHeartbeat) : 999999;
    $isActive = ($elapsed <= 120); // 120秒内有心跳则视为活跃开启中

    api_respond(0, 'ok', array(
        'tasks' => $tasks,
        'summary' => array(
            'total_tasks' => count($tasks),
            'enabled_tasks' => count(array_filter($tasks, function($t) { return $t['enabled']; })),
            'total_runs_all' => $totalRunsAll,
            'total_success_all' => $totalSuccessAll,
            'total_logs' => $totalLogs
        ),
        'bt_cron' => array(
            'is_active' => $isActive,
            'last_heartbeat_time' => $lastHeartbeat > 0 ? date('Y-m-d H:i:s', $lastHeartbeat) : '',
            'elapsed_seconds' => $elapsed
        )
    ));
}

if ($action === 'scheduler-task-update') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();
    if (intval($userrow['uid']) !== 1) {
        api_respond(403, '无权限');
    }

    $input = api_read_input();
    $taskId = isset($input['task_id']) ? trim(strip_tags($input['task_id'])) : '';
    if (empty($taskId)) {
        api_respond(422, '任务ID不能为空');
    }

    $updates = array();
    if (isset($input['enabled'])) {
        $enabled = $input['enabled'] ? 1 : 0;
        $updates[] = "`enabled`='$enabled'";
    }
    if (isset($input['interval_mins'])) {
        $mins = max(1, min(1440, intval($input['interval_mins'])));
        $updates[] = "`interval_mins`='$mins'";
    }

    if (!empty($updates)) {
        $taskIdSafe = daddslashes($taskId);
        $setStr = implode(', ', $updates);
        $DB->query("UPDATE `qingka_wangke_cron_task` SET $setStr WHERE id='$taskIdSafe' LIMIT 1");
    }

    api_respond(0, '任务配置更新成功');
}

if ($action === 'scheduler-task-run') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();
    if (intval($userrow['uid']) !== 1) {
        api_respond(403, '无权限');
    }

    $input = api_read_input();
    $taskId = isset($input['task_id']) ? trim(strip_tags($input['task_id'])) : '';
    if (empty($taskId)) {
        api_respond(422, '任务ID不能为空');
    }

    $res = scheduler_execute_task($taskId);
    api_respond(0, '任务执行完毕', $res);
}

if ($action === 'scheduler-run-all') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();
    if (intval($userrow['uid']) !== 1) {
        api_respond(403, '无权限');
    }

    $reports = scheduler_run_all_enabled();
    api_respond(0, '全部已启用任务执行完成', array('reports' => $reports));
}

if ($action === 'scheduler-task-logs') {
    api_require_login(isset($islogin) ? $islogin : 0);
    if (intval($userrow['uid']) !== 1) {
        api_respond(403, '无权限');
    }

    $taskId = isset($_GET['task_id']) ? trim(strip_tags($_GET['task_id'])) : '';
    $taskIdSafe = daddslashes($taskId);
    $res = $DB->query("SELECT * FROM `qingka_wangke_cron_log` WHERE task_id='$taskIdSafe' ORDER BY id DESC LIMIT 50");
    $list = array();
    while ($r = $DB->fetch($res)) {
        $list[] = array(
            'id' => (string)$r['id'],
            'task_id' => (string)$r['task_id'],
            'content' => (string)$r['content'],
            'status' => intval($r['status']),
            'cost_ms' => intval($r['cost_ms']),
            'processed_count' => intval($r['processed_count']),
            'created_at' => (string)$r['created_at']
        );
    }
    api_respond(0, 'ok', array('logs' => $list));
}

if ($action === 'scheduler-task-clear-logs') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();
    if (intval($userrow['uid']) !== 1) {
        api_respond(403, '无权限');
    }

    $input = api_read_input();
    $taskId = isset($input['task_id']) ? trim(strip_tags($input['task_id'])) : '';

    if (!empty($taskId)) {
        $taskIdSafe = daddslashes($taskId);
        $DB->query("DELETE FROM `qingka_wangke_cron_log` WHERE task_id='$taskIdSafe'");
    } else {
        $DB->query("TRUNCATE TABLE `qingka_wangke_cron_log`");
    }

    api_respond(0, '调度日志已清理');
}

if ($action === 'scheduler-cron') {
    $cronKey = isset($_GET['key']) ? trim($_GET['key']) : '';
    $clientIp = isset($clientip) ? $clientip : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1');
    $isLocal = in_array($clientIp, array('127.0.0.1', '::1', 'localhost'), true) || php_sapi_name() === 'cli';
    $validKey = 'cron_scheduler_sk_2026';

    if (!$isLocal && $cronKey !== $validKey) {
        api_respond(403, 'Cron 调度密钥鉴权失败');
    }

    $res = $DB->query("SELECT * FROM `qingka_wangke_cron_task` WHERE enabled=1 ORDER BY CASE id 
        WHEN 'order_dispatch' THEN 1 
        WHEN 'progress_active' THEN 2 
        WHEN 'progress_exam' THEN 3 
        ELSE 9 END ASC");
    $now = time();
    $ranReports = array();

    while ($task = $DB->fetch($res)) {
        $intervalSec = max(60, intval($task['interval_mins']) * 60);
        $lastTime = !empty($task['last_run_time']) ? strtotime($task['last_run_time']) : 0;
        
        if (($now - $lastTime) >= ($intervalSec - 5)) {
            $ranReports[] = scheduler_execute_task($task['id']);
        }
    }

    api_respond(0, '自动周期调度巡检完成', array('dispatched' => $ranReports));
}
