<?php
function xgmm($oid,$xgmm){//改密代码
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
	
	  // 爱学习
  if ($type == "2xx") {
    $data = array("id" => $yid, "username" => $d['user'], "pass" => $xgmm);
    $ixx_url = $a["url"] . "/api/update";
    $result = httpRequest('POST', $ixx_url, $data, [], true);
    $result = json_decode($result, true);
    if ($result["code"] == "1") {
      $b = array("code" => 1, "msg" => "修改成功");
    } else {
      $b = array("code" => -1, "msg" => $result["msg"] ?: "修改失败");
    }
    return $b;
  }
// 流年改密接口
  else if ($type == "liunian") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "id" => $yid, "xgmm" => $xgmm);
    $xm_rl = $a["url"];
    $xm_url = "$xm_rl/api.php?act=xgmm";
    $result = get_url($xm_url, $data);
    $result = json_decode($result, true);
    return $result;
  }
  // hzw
  else if ($type == "hzw") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "id" => $yid, "newPwd" => $xgmm);
    $uu_rl = $a["url"];
    $uu_url = "$uu_rl/api.php?act=gaimi";
    $result = get_url($uu_url, $data);
    $result = json_decode($result, true);
    return $result;
  }
  // SkyLearn
  if ($type == "xm") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "id" => $yid, "xgmm" => $xgmm);
    $xm_rl = $a["url"];
    $xm_url = "$xm_rl/api.php?act=xgmm";
    $result = get_url($xm_url, $data);
    $result = json_decode($result, true);
    return $result;
  } else {
    $b = array("code" => -1, "msg" => "当前项目暂不支持修改信息");
    return $b;
  }	

}
