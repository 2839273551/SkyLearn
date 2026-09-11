<?php
// 获取 CPU 使用率
$cpu_usage = sys_getloadavg()[0] * 100; // 1分钟平均负载即为 CPU 使用率

// 获取负载状态
$loadavg = sys_getloadavg();

// 将 CPU 使用率和负载状态的百分比值作为 JSON 返回
echo json_encode([
    'cpu' => round($cpu_usage, 2)/4 . '%',
    'loadavg' => '1分钟:' . round($loadavg[0], 2) . '%, 5分钟:' . round($loadavg[1], 2) . '%, 15分钟:' . round($loadavg[2], 2) . '%'
]);
?>
