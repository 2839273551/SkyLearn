<?php
/**
 * 宝塔计划任务全自动调度入口脚本 (Shell CLI & HTTP 访问双模式自适应)
 * 
 * 宝塔使用指南：
 * 1. 在宝塔面板进入「计划任务」；
 * 2. 任务类型选择：Shell 脚本
 * 3. 任务名称：网课全自动调度引擎
 * 4. 执行周期：N分钟 -> 1 分钟
 * 5. 脚本内容填写：
 *    /www/server/php/74/bin/php /www/wwwroot/sk.yunxnet.cn/admin-api/v1/cron.php
 * 
 * 效果：
 * - 只要宝塔这个任务开着，每分钟自动巡检所有已开启的调度任务，按各自周期自动出单、自动同步进度；
 * - 只要在宝塔中点击「停用」或「删除」该任务，调度立刻完全停止，零后台驻留。
 */

@ignore_user_abort(true);
@set_time_limit(300);

require_once __DIR__ . '/../../confing/common.php';
require_once __DIR__ . '/scheduler_worker.php';

$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    header('Content-Type: text/plain; charset=UTF-8');
}

$now = time();
$timeStr = date('Y-m-d H:i:s');
$isForce = false;

if ($isCli) {
    global $argv;
    if (isset($argv) && in_array('--force', $argv, true)) {
        $isForce = true;
    }
} else {
    if (isset($_GET['force']) && $_GET['force'] == '1') {
        $isForce = true;
    }
}

echo "====================================================================\n";
echo "           网课平台自动化任务调度引擎 (宝塔专用调度)\n";
echo "           执行时间: $timeStr\n";
echo "====================================================================\n\n";

$res = $DB->query("SELECT * FROM `qingka_wangke_cron_task` WHERE enabled=1 ORDER BY CASE id 
    WHEN 'order_dispatch' THEN 1 
    WHEN 'progress_active' THEN 2 
    WHEN 'progress_exam' THEN 3 
    ELSE 9 END ASC");

$tasks = array();
while ($r = $DB->fetch($res)) {
    $tasks[] = $r;
}

$totalEnabled = count($tasks);
if ($totalEnabled === 0) {
    echo "[!] 提示: 后台所有调度任务均处于停用状态，无需执行任何调度。\n";
    echo "[✔] 巡检完成，安全退出。\n";
    exit;
}

echo "[*] 扫描到已启用调度任务: {$totalEnabled} 个\n\n";

$ranCount = 0;
$totalSuccessOrders = 0;

foreach ($tasks as $task) {
    $taskId = $task['id'];
    $taskName = $task['name'];
    $intervalMins = max(1, intval($task['interval_mins']));
    $intervalSec = $intervalMins * 60;
    $lastTime = !empty($task['last_run_time']) ? strtotime($task['last_run_time']) : 0;
    $elapsed = $now - $lastTime;

    $shouldRun = $isForce || ($elapsed >= ($intervalSec - 5));

    if (!$shouldRun) {
        $remainingSec = max(0, $intervalSec - $elapsed);
        echo "[-] 任务 [{$taskName}] ($taskId): 周期 {$intervalMins}分钟, 上次执行: " . ($lastTime ? date('H:i:s', $lastTime) : '从未') . ", 距下次运行还需 {$remainingSec}秒 (跳过)\n";
        continue;
    }

    echo "[+] >>> 启动任务: [{$taskName}] ($taskId) [周期: {$intervalMins}分钟] ...\n";
    $report = scheduler_execute_task($taskId);
    $cost = isset($report['cost_ms']) ? $report['cost_ms'] : 0;
    $summary = isset($report['summary']) ? $report['summary'] : '';
    $success = isset($report['success_count']) ? intval($report['success_count']) : 0;
    $totalSuccessOrders += $success;

    echo "    ↳ 调度结果: {$summary} (耗时: {$cost}ms)\n\n";
    $ranCount++;
}

echo "--------------------------------------------------------------------\n";
echo "[✔] 本轮调度巡检完成：共触发 {$ranCount} / {$totalEnabled} 个到期任务，共成功处理订单 {$totalSuccessOrders} 笔。\n";
echo "====================================================================\n";
