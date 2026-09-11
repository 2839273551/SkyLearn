<?php
include('../confing/common.php');
@header('Content-Type: application/json; charset=UTF-8');
$uid=daddslashes($_REQUEST['uid']);
$key=daddslashes($_REQUEST['key']);
$username=trim(strip_tags(daddslashes($_REQUEST['username'])));
$kcname=daddslashes($_REQUEST['kcname']);
$cid=daddslashes($_REQUEST['cid']);
if($uid==''){
    exit('{"code":0,"msg":"uid参数不能为空（你得uid）"}');
}
if($key==''){
    exit('{"code":0,"msg":"key参数不能为空（你得key）"}');
}
if($username==''){
    exit('{"code":0,"msg":"username参数不能为空（查询账号）"}');
}
if($kcname==''){
    exit('{"code":0,"msg":"kcname参数不能为空（查询课程）"}');
}
if($cid==''){
    exit('{"code":0,"msg":"cid参数不能为空（对接平台id）"}');
}
$a = $DB->query("select * from qingka_wangke_order where user='{$username}' and kcname='{$kcname}' and cid='{$cid}' and dockstatus!='4' and uid='{$uid}' and dockstatus!='已取消' order by oid desc limit 1");
if ($a) {
   	while ($row = $DB->fetch($a)) {
		$data[] = array(
			'id' => $row['oid'],
			'ptname' => $row['ptname'],
			'school' => $row['school'],
			'name' => $row['name'],
			'user' => $row['user'],
			'kcname' => $row['kcname'],
			'addtime' => $row['addtime'],
			'courseStartTime' => $row['courseStartTime'],
			'courseEndTime' => $row['courseEndTime'],
			'examStartTime' => $row['examStartTime'],
			'examEndTime' => $row['examEndTime'],
			'status' => $row['status'],
			'process' => $row['process'],
			'remarks' => $row['remarks'],
			'zhgx' => $row['finalupdate']
		);
	}
	$data = array('code' => 1, 'data' => $data);
	exit(json_encode($data));
}else {
	$data = array('code' => -1, 'msg' => "未查到该账号的下单信息");
	exit(json_encode($data));
}
