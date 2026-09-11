<?php
require_once('../confing/common.php');
require_once('comm.php');

// 接收参数
$username = trim(strip_tags(daddslashes($RECEIVE_DATA['username'])));
$type = trim(strip_tags(daddslashes($RECEIVE_DATA['type'])));
$value = trim(strip_tags(daddslashes($RECEIVE_DATA['value'])));
$password = trim(strip_tags(daddslashes($RECEIVE_DATA['password']))); // 新增密码参数

if (!$username || !$type || !$value || !$password) {
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

// 验证绑定值
switch ($type) {
    case 'wxpusher':
        if (empty($value)) ApiFail("WxPusher UID不能为空");
        break;
    case 'email':
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) ApiFail("邮箱格式不正确");
        break;
    case 'showdoc':
        if (!filter_var($value, FILTER_VALIDATE_URL)) ApiFail("ShowDoc URL格式不正确");
        break;
}

// 更新所有订单的推送信息
$updateField = '';
$statusField = '';
switch ($type) {
    case 'wxpusher':
        $updateField = 'pushUid'; // 字段名
        $statusField = 'pushStatus';
        break;
    case 'email':
        $updateField = 'pushEmail'; // 字段名
        $statusField = 'pushEmailStatus';
        break;
    case 'showdoc':
        $updateField = 'showdoc_push_url'; // 字段名
        $statusField = 'pushShowdocStatus';
        break;
}


$result = $DB->query("UPDATE qingka_wangke_order SET 
    {$updateField} = '{$value}',
    {$statusField} = '1'
    WHERE user='{$username}'");

if ($result) {
    ApiSuccess("绑定成功", [
        'type' => $type,
        'value' => $value,
        'status' => '0'
    ]);
} else {
    ApiFail("绑定失败");
}