<?php
function budanWk($oid){//补刷代码
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
//27补刷
  if ($type == "27") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "id" => $yid);
    $eq_rl = $a["url"];
    $eq_url = "$eq_rl/api.php?act=budan";
    $result = get_url($eq_url, $data);
    $result = json_decode($result, true);
    return $result;
  }
  // 爱学习补刷
  else if ($type == "2xx") {
    $data = array("id" => $yid, "username" => $d['user']);
    $ixx_url = $a["url"] . "/api/reset";
    $result = httpRequest('POST', $ixx_url, $data, [], true);
    $result = json_decode($result, true);
    return $result;
  }
  //benz补刷
  else if ($type == "benz") {
    $data = array("token" => $token, "id" => $yid);
    $benz_rl = $a["url"];
    $benz_url = "$benz_rl/api/reset";
    $result = get_url($benz_url, $data, $cookie);
    $result = json_decode($result, true);
    if ($result["code"] == 1) {
      $b = array("code" => 1, "msg" => "操作成功");
    } else {
      $b = array("code" => -1, "msg" => "操作失败，请重试");
    }
    return $b;
  }
  
  //29系统补刷
  else if ($type == "29") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "id" => $yid);
    $txt_rl = $a["url"];
    $txt_url = "$txt_rl/api.php?act=budan";
    $result = get_url($txt_url, $data);
    $result = json_decode($result, true);
    return $result;
  }
  
  // hzw补刷接口
  else if ($type == "hzw") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "id" => $yid);
    $eq_rl = $a["url"];
    $eq_url = "$eq_rl/api.php?act=budan";
    $result = get_url($eq_url, $data);
    $result = json_decode($result, true);
    return $result;
  }
   // SkyLearn
  else if ($type == "xm") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "id" => $yid);
    $xm_rl = $a["url"];
    $xm_url = "$xm_rl/api.php?act=budan";
    $result = get_url($xm_url, $data);
    $result = json_decode($result, true);
    return $result;
  }
  // longlong补刷
  else if ($type == "longlong") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "id" => $yid);
    $txt_rl = $a["url"];
    $txt_url = "$txt_rl/api.php?act=budan";
    $result = get_url($txt_url, $data);
    $result = json_decode($result, true);
    return $result;
  }
  //流年补刷接口
  else if ($type == "liunian") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "id" => $yid);
    $dx_rl = $a["url"];
    $dx_url = "$dx_rl/api.php?act=budan";
    $result = get_url($dx_url, $data);
    $result = json_decode($result, true);
    return $result;
  }
  
  else {
    $b = array("code" => -1, "msg" => "当前项目暂不支持补刷");
    return $b;
  }

}
