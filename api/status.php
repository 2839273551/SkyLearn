<?php
require_once('../confing/common.php');
require_once('comm.php');

// 接收参数
$username = trim(strip_tags(daddslashes($RECEIVE_DATA['username'])));

if (!$username) {
    ApiFail("用户名不能为空");
}

// 获取用户最新订单的推送状态
$order = $DB->get_row("SELECT 
    pushUid, pushStatus,
    pushEmail, pushEmailStatus,
    showdoc_push_url, pushShowdocStatus
    FROM qingka_wangke_order 
    WHERE user='{$username}' 
    ORDER BY oid DESC 
    LIMIT 1");

if (!$order) {
    ApiFail("该用户没有订单");
}

// 组织返回数据
$result = [
    'wxpusher' => [
        'bind' => !empty($order['pushUid']) && $order['pushStatus'] == '1',
        'value' => $order['pushUid'],
        'status' => $order['pushStatus']
    ],
    'email' => [
        'bind' => !empty($order['pushEmail']) && $order['pushEmailStatus'] == '1',
        'value' => $order['pushEmail'],
        'status' => $order['pushEmailStatus']
    ],
    'showdoc' => [
        'bind' => !empty($order['showdoc_push_url']) && $order['pushShowdocStatus'] == '1',
        'value' => $order['showdoc_push_url'],
        'status' => $order['pushShowdocStatus']
    ]
];

ApiSuccess("查询成功", $result);
