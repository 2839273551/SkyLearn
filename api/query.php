<?php
require_once('../confing/common.php');
require_once('comm.php');

$username = trim(strip_tags(daddslashes($RECEIVE_DATA['username'])));
if (!$username) {
  ApiFail("账号不能为空");
}

// 修改后的查询语句，只使用表中存在的字段
$order_data = $DB->query("SELECT `oid`, `uid`, `cid`, `ptname`, `school`, `name`, `user`, `pass`, 
                                 `kcid`, `kcname`, `courseStartTime`, `courseEndTime`, `examStartTime`, 
                                 `examEndTime`, `fees`, `addtime`, `dockstatus`, `status`, `process`, 
                                 `remarks`, `ip`, `shichang` AS `time`, `score` AS `uScore`, `bsnum`,
                                 `finalupdate`, `region`, `fenlei`
                          FROM qingka_wangke_order 
                          WHERE user='{$username}' 
                          ORDER BY oid DESC");

$data = array();
while ($order = $DB->fetch($order_data)) {
  $data[] = array(
    'id' => $order['oid'],
    'cid' => $order['cid'],
    'ptname' => $order['ptname'],
    'school' => $order['school'],
    'name' => $order['name'],
    'user' => $order['user'],
    // 'pass' => $order['pass'],
    'kcname' => $order['kcname'],
    'addtime' => $order['addtime'],
    'courseStartTime' => $order['courseStartTime'],
    'courseEndTime' => $order['courseEndTime'],
    'examStartTime' => $order['examStartTime'],
    'examEndTime' => $order['examEndTime'],
    'time' => $order['time'],        // 使用shichang字段
    'score' => $order['uScore'],     // 使用score字段
    'status' => $order['status'],
    'process' => $order['process'],
    'remarks' => $order['remarks'],
    'fees' => $order['fees'],
    'ip' => $order['ip'],
    'dockstatus' => $order['dockstatus'],
    'bsnum' => $order['bsnum'],
    'finalupdate' => $order['finalupdate'],
    'region' => $order['region'],
    'fenlei' => $order['fenlei']
  );
}

if (empty($data)) {
  ApiFail("未查询到该账号的下单信息");
}
ApiSuccess('查询成功', $data);