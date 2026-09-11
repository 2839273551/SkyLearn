<?php
function msWk($oid){//秒刷代码
	global $DB;
	global $wk;
	$d = $DB->get_row("select * from qingka_wangke_order where oid='{$oid}' ");
	$b = $DB->get_row("select hid,yid,user from qingka_wangke_order where oid='{$oid}' ");
	$hid = $b["hid"];
	$yid = $b["yid"];
	$user = $b["user"];
	$a = $DB->get_row("select * from qingka_wangke_huoyuan where hid='{$hid}' ");
	$type = $a["pt"];
	$cookie = $a["cookie"];
	$token = $a["token"];
	$ip = $a["ip"];
	$cid = $d["cid"];
	$school = $d["school"];
	$user = $d["user"];
	$pass = $d["pass"];
	$kcid = $d["kcid"];
	$kcname = $d["kcname"];
	$noun = $d["noun"];
	$miaoshua = $d["miaoshua"];

	if ($type == "xm") {
$data = array("uid" => $a["user"], "key" => $a["pass"], "id" => $yid);
$xm_rl = $a["url"];
$xm_url = "$xm_rl/api.php?act=ms_order";
$result = get_url($xm_url, $data);
$result = json_decode($result, true);
return $result;}
	else {
				$b = array("code" => -1, "msg" => "接口异常，请联系管理员");
		}
}
