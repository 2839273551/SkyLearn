<?php
include('../confing/common.php');

$redis = new Redis();
$redis->connect("127.0.0.1", "6379");
$redis->select(11);

while (true) {
    // 从 Redis 列表 "csoid" 中取出订单ID
    $oid = $redis->lpop('csoid');

    if ($oid != '') {
        // 获取订单信息
        $orderInfo = $DB->get_row("select * from qingka_wangke_order where oid='$oid'");

        if ($orderInfo) {
            // 执行补刷逻辑
            $result = budanWk($oid);

            // 更新订单状态和相关信息
            for ($i = 0; $i < count($result); $i++) {
                $today_day = date("Y-m-d H:i:s");
                echo ("订单ID：" . $oid . "|重刷成功！$today_day\r\n");
                $DB->query("update qingka_wangke_order set `status`='补刷中',`bsnum`=bsnum+1 where `oid`='$oid'");
            }
        }
    }

    // 休眠3秒，避免过于频繁的操作
    sleep(3);
}
?>
