<?php
include('../confing/common.php');
// 返回近 5 天的订单
$recently_day = 5;

// 接收参数
$token = trim(strip_tags(daddslashes($_POST['token']))) ?: trim(strip_tags(daddslashes($_POST['key'])));
$offset = intval($_REQUEST['offset']) ?: 0;
$limit = intval($_REQUEST['limit']) ?: 20;
$timestamp = $_REQUEST['timestamp'] ?: time();
if (!$token) {
  exit('{"code":-1,"msg":"密钥不能为空"}');
}
$user = $DB->get_row("select `uid`, `key` from qingka_wangke_user where `key`='{$token}' limit 1");
if (!$user || $user['key'] != $token) {
  $result = array("code" => -2, "msg" => "密匙错误");
  exit(json_encode($result));
}

// 判断时间是否小于 8 点
$today = date('Y-m-d');
$eightClockToday = strtotime($today . ' 8:00');
if ($timestamp < $eightClockToday) {
  // 返回所有订单
  $a = $DB->query("SELECT `oid` AS `id`, `cid`, `ptname`, `school`, `name`, `user`, `pass`, `kcid`, `kcname`, `courseStartTime`, `courseEndTime`, `examStartTime`, `examEndTime`, `addtime`, `status`, `process`, `remarks` FROM qingka_wangke_order WHERE `uid` = '{$user['uid']}' ORDER BY `id` DESC LIMIT {$offset}, {$limit}");
} else {
  // 返回近 5 天的订单
  $recently_day_date = date('Y-m-d', strtotime("-{$recently_day} days"));
  $a = $DB->query("SELECT `oid` AS `id`, `cid`, `ptname`, `school`, `name`, `user`, `pass`, `kcid`, `kcname`, `courseStartTime`, `courseEndTime`, `examStartTime`, `examEndTime`, `addtime`, `status`, `process`, `remarks` FROM qingka_wangke_order WHERE `uid` = '{$user['uid']}' AND `addtime`> '{$recently_day_date}' ORDER BY `id` DESC LIMIT {$offset}, {$limit}");
}

while ($row = $DB->fetch($a)) {
  $data[] = $row;
}
if ($data != null) {
  $data = array('code' => 1, 'msg' => '查询成功', 'data' => $data);
} else {
  $data = array('code' => -1, 'msg' => '已经没有更多数据了');
}
exit(json_encode($data));
