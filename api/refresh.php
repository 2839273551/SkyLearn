<?php
require_once('../confing/common.php');
require_once('comm.php');
$oid = intval(trim(strip_tags(daddslashes($RECEIVE_DATA['id']))));
$username = trim(strip_tags(daddslashes($RECEIVE_DATA['username'])));
$IS_NOW = 1; // 1为实时进度，2为本平台进度

if (!$oid || !$username) {
    ApiFail("订单号或用户账号不能为空");
}

// 修改后的查询语句，只使用实际存在的字段
$order = $DB->get_row("SELECT `oid`, `uid`, `cid`, `ptname`, `school`, `name`, `user`, `pass`, 
                              `kcid`, `kcname`, `courseStartTime`, `courseEndTime`, `examStartTime`, 
                              `examEndTime`, `fees`, `addtime`, `dockstatus`, `status`, `process`, 
                              `remarks`, `ip`, `shichang` AS `uTime`, `score` AS `uScore`, `bsnum`,
                              `finalupdate`, `region`, `fenlei`
                       FROM qingka_wangke_order 
                       WHERE oid='{$oid}' 
                       LIMIT 1");

if (!$order || $order['user'] != $username) {
    ApiFail("该订单不存在");
}

if ($order['dockstatus'] == '99' || $IS_NOW == 2) {
    $data[] = array(
        'id' => $order['oid'],
        'cid' => $order['cid'],
        'ptname' => $order['ptname'],
        'school' => $order['school'],
        'name' => $order['name'],
        'user' => $order['user'],
        'pass' => $order['pass'],
        'kcname' => $order['kcname'],
        'addtime' => $order['addtime'],
        'courseStartTime' => $order['courseStartTime'],
        'courseEndTime' => $order['courseEndTime'],
        'examStartTime' => $order['examStartTime'],
        'examEndTime' => $order['examEndTime'],
        'time' => $order['uTime'],       // 使用shichang字段
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
    ApiSuccess('同步成功', $data);
} else {
    $result = processCx($oid);
    if (!$result) {
        ApiFail("同步失败");
    }
    
    for ($i = 0; $i < count($result); $i++) {
        $name = daddslashes($result[$i]['name']);
        $yid = daddslashes($result[$i]['yid']);
        $status = daddslashes($result[$i]['status_text']);
        $courseStartTime = daddslashes($result[$i]['kcks']);
        $courseEndTime = daddslashes($result[$i]['kcjs']);
        $examStartTime = daddslashes($result[$i]['ksks']);
        $examEndTime = daddslashes($result[$i]['ksjs']);
        $process = daddslashes($result[$i]['process']);
        $remarks = daddslashes($result[$i]['remarks']);
        $user = daddslashes($result[$i]['user']);
        $kcname = daddslashes($result[$i]['kcname']);

        $DB->query("UPDATE qingka_wangke_order SET 
                   `name`='{$name}',
                   `yid`='{$yid}',
                   `status`='{$status}',
                   `courseStartTime`='{$courseStartTime}',
                   `courseEndTime`='{$courseEndTime}',
                   `examStartTime`='{$examStartTime}',
                   `examEndTime`='{$examEndTime}',
                   `process`='{$process}',
                   `remarks`='{$remarks}'
                   WHERE `user`='{$user}' AND `kcname`='{$kcname}' AND `oid`='{$oid}' 
                   LIMIT 1");
    }
    
    // 修改后的查询语句，只使用实际存在的字段
    $new_order = $DB->get_row("SELECT `oid`, `uid`, `cid`, `ptname`, `school`, `name`, `user`, `pass`, 
                                     `kcid`, `kcname`, `courseStartTime`, `courseEndTime`, `examStartTime`, 
                                     `examEndTime`, `fees`, `addtime`, `dockstatus`, `status`, `process`, 
                                     `remarks`, `ip`, `shichang` AS `uTime`, `score` AS `uScore`, `bsnum`,
                                     `finalupdate`, `region`, `fenlei`
                              FROM qingka_wangke_order 
                              WHERE oid='{$oid}' 
                              LIMIT 1");
    
    $data[] = array(
        'id' => $oid,
        'cid' => $new_order['cid'],
        'ptname' => $new_order['ptname'],
        'school' => $new_order['school'],
        'name' => $new_order['name'],
        'user' => $new_order['user'],
        'pass' => $new_order['pass'],
        'kcname' => $new_order['kcname'],
        'addtime' => $new_order['addtime'],
        'courseStartTime' => $new_order['courseStartTime'],
        'courseEndTime' => $new_order['courseEndTime'],
        'examStartTime' => $new_order['examStartTime'],
        'examEndTime' => $new_order['examEndTime'],
        'time' => $new_order['uTime'],       // 使用shichang字段
        'score' => $new_order['uScore'],     // 使用score字段
        'status' => $new_order['status'],
        'process' => $new_order['process'],
        'remarks' => $new_order['remarks'],
        'fees' => $new_order['fees'],
        'ip' => $new_order['ip'],
        'dockstatus' => $new_order['dockstatus'],
        'bsnum' => $new_order['bsnum'],
        'finalupdate' => $new_order['finalupdate'],
        'region' => $new_order['region'],
        'fenlei' => $new_order['fenlei']
    );
    ApiSuccess('同步成功', $data);
}