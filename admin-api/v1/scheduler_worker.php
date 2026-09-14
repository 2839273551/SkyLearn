<?php

if (!defined('IN_CRONLITE')) {
    exit();
}

/**
 * 统计指定任务当前积压的待处理订单数
 * 核心优化：凡是标记【已完成】、已取消、已退款，或进度已达 100% 且未申请补单的订单，自动归档不再统计和参与后续轮询
 */
function scheduler_count_pending($taskId) {
    global $DB;
    $completedFilter = "status NOT IN ('已完成','已取消','已退款') AND (process NOT LIKE '100%' OR status IN ('待重刷','补刷中'))";

    switch ($taskId) {
        case 'add':
            return intval($DB->count("SELECT COUNT(*) FROM `qingka_wangke_order` WHERE dockstatus='0' AND status NOT IN ('已取消','已退款')"));
        case 'cc':
            return intval($DB->count("SELECT COUNT(*) FROM `qingka_wangke_order` WHERE (status='进行中' OR status='补刷中') AND dockstatus=1 AND $completedFilter"));
        case 'plsx':
            return intval($DB->count("SELECT COUNT(*) FROM `qingka_wangke_order` WHERE status='待刷新' AND dockstatus=1 AND status NOT IN ('已完成','已取消','已退款')"));
        case 'plbs':
            return intval($DB->count("SELECT COUNT(*) FROM `qingka_wangke_order` WHERE status='待重刷' AND dockstatus=1"));
        case 'aa':
            return intval($DB->count("SELECT COUNT(*) FROM `qingka_wangke_order` WHERE dockstatus=1 AND status IN ('待处理','上号中','重刷中','正在开药','等待治疗') AND $completedFilter"));
        case 'bb':
            // 活跃订单高频巡检：严格排除已完成、已退款/取消及进度满格订单
            return intval($DB->count("SELECT COUNT(*) FROM `qingka_wangke_order` WHERE dockstatus=1 AND $completedFilter"));
        case 'dd':
            return intval($DB->count("SELECT COUNT(*) FROM `qingka_wangke_order` WHERE status IN ('待考试','平时分','平时分中','已暂停') AND dockstatus=1 AND status NOT IN ('已完成','已取消','已退款')"));
        case 'ee':
            return intval($DB->count("SELECT COUNT(*) FROM `qingka_wangke_order` WHERE status NOT IN ('已完成','进行中','待考试','治疗完成','平时分中','待处理','已取消','已退款') AND dockstatus=1 AND (process NOT LIKE '100%' OR status IN ('待重刷','补刷中'))"));
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
    $logs[] = "[$timeStr] >>> 开始启动任务调度: [$taskId] ...";

    $successCount = 0;
    $failedCount = 0;
    $limit = 20;

    if ($taskId === 'add') {
        $res = $DB->query("SELECT * FROM `qingka_wangke_order` WHERE dockstatus='0' AND status!='已取消' ORDER BY oid ASC LIMIT $limit");
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
                $nowTime = date('Y-m-d H:i:s');

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
                $logs[] = "  [x] 货源适配函数 addWk 未加载";
                $failedCount++;
            }
        }
    } elseif ($taskId === 'plbs') {
        $res = $DB->query("SELECT * FROM `qingka_wangke_order` WHERE status='待重刷' AND dockstatus=1 ORDER BY oid ASC LIMIT $limit");
        $orders = array();
        while ($r = $DB->fetch($res)) { $orders[] = $r; }
        $logs[] = "[$timeStr] 扫描到待补单重跑订单: " . count($orders) . " 笔";

        foreach ($orders as $b) {
            $oid = $b['oid'];
            if (function_exists('budanWk')) {
                $result = budanWk($oid);
                if (isset($result['code']) && $result['code'] == 1) {
                    $DB->query("UPDATE `qingka_wangke_order` SET status='重刷中' WHERE oid='$oid'");
                    $logs[] = "  [+] 订单 #$oid 补单请求已推向上游，转为[重刷中]";
                    $successCount++;
                } else {
                    $msg = isset($result['msg']) ? $result['msg'] : '补单失败';
                    $logs[] = "  [x] 订单 #$oid 补单失败: $msg";
                    $failedCount++;
                }
            }
        }
    } else {
        // 属于各种状态的进度同步巡检任务 (cc, plsx, aa, bb, dd, ee)
        // 核心：严格排除已完成、已取消、已退款及无补单需求的100%订单
        $completedFilter = "status NOT IN ('已完成','已取消','已退款') AND (process NOT LIKE '100%' OR status IN ('待重刷','补刷中'))";
        $where = "dockstatus=1 AND $completedFilter";

        if ($taskId === 'cc') {
            $where = "(status='进行中' OR status='补刷中') AND dockstatus=1 AND $completedFilter";
        } elseif ($taskId === 'plsx') {
            $where = "status='待刷新' AND dockstatus=1 AND status NOT IN ('已完成','已取消','已退款')";
        } elseif ($taskId === 'aa') {
            $where = "dockstatus=1 AND status IN ('待处理','上号中','重刷中','正在开药','等待治疗') AND $completedFilter";
        } elseif ($taskId === 'bb') {
            $where = "dockstatus=1 AND $completedFilter";
            $limit = 20;
        } elseif ($taskId === 'dd') {
            $where = "status IN ('待考试','平时分','平时分中','已暂停') AND dockstatus=1 AND status NOT IN ('已完成','已取消','已退款')";
        } elseif ($taskId === 'ee') {
            $where = "status NOT IN ('已完成','进行中','待考试','治疗完成','平时分中','待处理','已取消','已退款') AND dockstatus=1 AND (process NOT LIKE '100%' OR status IN ('待重刷','补刷中'))";
        }

        $res = $DB->query("SELECT * FROM `qingka_wangke_order` WHERE $where ORDER BY oid ASC LIMIT $limit");
        $orders = array();
        while ($r = $DB->fetch($res)) { $orders[] = $r; }
        $logs[] = "[$timeStr] 扫描到待同步进度活跃订单: " . count($orders) . " 笔 (已排除全部已完成/归档订单)";

        if (count($orders) === 0) {
            $logs[] = "[$timeStr] 当前该队列下无未完成的待巡检订单，任务跳过。";
        }

        foreach ($orders as $a) {
            $oid = $a['oid'];
            if (function_exists('processCx')) {
                $results = processCx($oid);
                if (is_array($results)) {
                    $updated = false;
                    foreach ($results as $item) {
                        if (isset($item['kcname']) && $item['kcname'] === $a['kcname']) {
                            $newProcess = isset($item['process']) ? daddslashes($item['process']) : $a['process'];
                            $newStatus = isset($item['status_text']) ? daddslashes($item['status_text']) : $a['status'];
                            $newRemarks = isset($item['remarks']) ? daddslashes($item['remarks']) : $a['remarks'];
                            $newKcks = isset($item['kcks']) ? daddslashes($item['kcks']) : $a['courseStartTime'];
                            $newKcjs = isset($item['kcjs']) ? daddslashes($item['kcjs']) : $a['courseEndTime'];
                            
                            // 智能归档：若进度达到 100% 或上游回传已完成/已结课，标记为[已完成]并自动移出下一轮队列
                            $numVal = floatval(preg_replace('/[^\d.]/', '', (string)$newProcess));
                            $isNowFinished = false;
                            if ($newStatus === '已完成' || $newStatus === '已结课' || $newStatus === '已学完' || ($numVal >= 100 && $newStatus !== '异常' && $newStatus !== '待重刷' && $newStatus !== '补刷中')) {
                                $newStatus = '已完成';
                                $isNowFinished = true;
                            }

                            $DB->query("UPDATE `qingka_wangke_order` SET 
                                `status`='$newStatus', 
                                `process`='$newProcess', 
                                `remarks`='$newRemarks',
                                `courseStartTime`='$newKcks',
                                `courseEndTime`='$newKcjs',
                                `finalupdate`=NOW()
                                WHERE oid='$oid'");

                            if ($isNowFinished) {
                                $logs[] = "  [✔] 订单 #$oid [{$a['kcname']}] 进度已达到 100% 并标记[已完成]，已归档退出后续轮询！";
                            } else {
                                $logs[] = "  [~] 订单 #$oid [{$a['kcname']}] 进度: {$a['process']}% -> {$newProcess}%, 状态: {$newStatus}";
                            }
                            $updated = true;
                            $successCount++;
                            break;
                        }
                    }
                    if (!$updated) {
                        $logs[] = "  [?] 订单 #$oid 上游未返回对应课程进度";
                    }
                } else {
                    $logs[] = "  [-] 订单 #$oid 进度查询上游无有效数据响应";
                    $failedCount++;
                }
            } else {
                $logs[] = "  [x] 查询函数 processCx 未定义";
                $failedCount++;
            }
        }
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
 * 一键执行所有已开启的任务
 */
function scheduler_run_all_enabled() {
    global $DB;
    $res = $DB->query("SELECT id FROM `qingka_wangke_cron_task` WHERE enabled=1 ORDER BY id ASC");
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
        WHEN 'add' THEN 1 
        WHEN 'cc' THEN 2 
        WHEN 'plsx' THEN 3 
        WHEN 'plbs' THEN 4 
        WHEN 'aa' THEN 5 
        WHEN 'bb' THEN 6 
        WHEN 'dd' THEN 7 
        WHEN 'ee' THEN 8 
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

    api_respond(0, 'ok', array(
        'tasks' => $tasks,
        'summary' => array(
            'total_tasks' => count($tasks),
            'enabled_tasks' => count(array_filter($tasks, function($t) { return $t['enabled']; })),
            'total_runs_all' => $totalRunsAll,
            'total_success_all' => $totalSuccessAll
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

    $res = $DB->query("SELECT * FROM `qingka_wangke_cron_task` WHERE enabled=1 ORDER BY id ASC");
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
