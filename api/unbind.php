<?php
require_once('../confing/common.php');
require_once('comm.php');

// 接收参数
$username = trim(strip_tags(daddslashes($RECEIVE_DATA['username'])));
$type = trim(strip_tags(daddslashes($RECEIVE_DATA['type'])));
$password = trim(strip_tags(daddslashes($RECEIVE_DATA['password'])));

if (!$username || !$type || !$password) {
    ApiFail("参数不完整");
}

// 验证推送类型
$validTypes = ['wxpusher', 'email', 'showdoc'];
if (!in_array($type, $validTypes)) {
    ApiFail("无效的推送类型");
}

// 验证密码是否正确
$orderInfo = $DB->get_row("SELECT * FROM qingka_wangke_order WHERE user='{$username}' LIMIT 1");
if (!$orderInfo) {
    ApiFail("订单不存在");
}

if ($orderInfo['pass'] !== $password) {
    ApiFail("密码验证失败");
}

// 更新所有订单的推送信息
$updateField = '';
$statusField = '';
switch ($type) {
    case 'wxpusher':
        $updateField = 'pushUid';
        $statusField = 'pushStatus';
        break;
    case 'email':
        $updateField = 'pushEmail';
        $statusField = 'pushEmailStatus';
        break;
    case 'showdoc':
        $updateField = 'showdoc_push_url';
        $statusField = 'pushShowdocStatus';
        break;
}

$result = $DB->query("UPDATE qingka_wangke_order SET 
    {$updateField} = '',
    {$statusField} = '0'
    WHERE user='{$username}'");

if ($result) {
    ApiSuccess("解绑成功", [
        'type' => $type,
        'status' => '1'
    ]);
} else {
    ApiFail("解绑失败");
}