<?php
require_once('../confing/common.php');
require_once('comm.php');
$oid = intval(daddslashes($RECEIVE_DATA['id']));
$username = trim(strip_tags(daddslashes($RECEIVE_DATA['username'])));
if (!$username || !$oid) {
  ApiFail("账号或订单号不能为空");
}
$order = $DB->get_row("select `oid`, `uid`, `cid`, `ptname`, `school`, `name`, `user`, `pass`, `kcid`, `kcname`, `courseStartTime`, `courseEndTime`, `examStartTime`, `examEndTime`, `fees`, `addtime`, `dockstatus`, `status`, `process`, `remarks`, `ip` from qingka_wangke_order where oid='{$oid}' limit 1");
if (!$order) {
  ApiFail("该订单不存在");
}
if ($order['bsnum'] > 20) {
  ApiFail("该订单补刷已超过20次");
}
if ($order['user'] != $username) {
  ApiFail("您暂无权限操作此订单");
}
if ($order['dockstatus'] == '99') {
  $DB->query("update qingka_wangke_order set status='补刷中',`bsnum`=bsnum+1 where oid='{$oid}' limit 1");
  ApiSuccess("成功加入线程，排队补刷中");
} else {
  $c = budanWk($oid);
  if ($c['code'] == 1) {
    $DB->query("update qingka_wangke_order set status='补刷中',`bsnum`=bsnum+1 where oid='{$oid}' limit 1");
    ApiSuccess($c['msg']);
  } else {
    ApiFail($c['msg']);
  }
}
