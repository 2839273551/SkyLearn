<?php
include('../confing/common.php');

// 初始化 Redis 连接
$redis = new Redis();
$redis->connect("127.0.0.1", "6379");
$redis->select(11);

// 检查 Redis 连接是否成功
if (!$redis->ping()) {
    die("无法连接到 Redis");
}

// 入队操作
$lenth = $redis->LLEN('csoid');

if ($lenth == 0) {
    $i = 0;
    $orders = $DB->query("SELECT * FROM qingka_wangke_order WHERE status='待重刷' AND dockstatus=1 ORDER BY oid ASC");

    foreach ($orders as $order) {
        $redis->lPush("csoid", $order['oid']);
        $i++;
    }

    echo "入队成功！本次入队订单共计：{$i} 条\r\n";
} else {
    echo "入队失败！队列池还有：{$lenth} 条订单正在执行\r\n";
}
?>
