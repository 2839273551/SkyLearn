<?php
function getWk($type, $noun, $school, $user, $pass, $name = false){//查课代码
	global $DB;
	global $wk;
	$a = $DB->get_row("select * from qingka_wangke_huoyuan where hid='{$type}' ");
	$type = $a["pt"];
	$cookie = $a["cookie"];
	$token = $a["token"];
	
//27查课
	 if ($type == "27") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "school" => $school, "user" => $user, "pass" => $pass, "platform" => $noun);
    $eq_rl = $a["url"];
    $er_url = "$eq_rl/api.php?act=get";
    $result = get_url($er_url, $data);
    $result = json_decode($result, true);
    return $result;
  }
  // 爱学习查课接口
  else if ($type == "2xx") {
    $data = array("token" => $a["pass"], "school" => $school, "user" => $user, "pass" => $pass, "platform" => $noun);
    $ixx_url = $a["url"] . "/api/get";
    $result = httpRequest('POST', $ixx_url, $data, [], true);
    $result = json_decode($result, true);
    return $result;
  }
	  //29系统查课接口
  else if ($type == "29") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "school" => $school, "user" => $user, "pass" => $pass, "platform" => $noun);
    $dx_rl = $a["url"];
    $dx_url = "$dx_rl/api.php?act=get";
    $result = get_url($dx_url, $data);
    $result = json_decode($result, true);
    return $result;
  }
  // SkyLearn
  else if ($type == "xm") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "school" => $school, "user" => $user, "pass" => $pass, "platform" => $noun);
    $xm_rl = $a["url"];
    $xm_url = "$xm_rl/api.php?act=get";
    $result = get_url($xm_url, $data);
    $result = json_decode($result, true);
    return $result;
  }
   //流年查课接口
  else if ($type == "liunian") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "school" => $school, "user" => $user, "pass" => $pass, "platform" => $noun);
    $dx_rl = $a["url"];
    $dx_url = "$dx_rl/api.php?act=get";
    $result = get_url($dx_url, $data);
    $result = json_decode($result, true);
    return $result;
  }
    //benz查课
  else if ($type == "benz") {
    $data = array("token" => $token, "school" => $school, "user" => $user, "pass" => $pass, "ptid" => $noun);
    $benz_rl = $a["url"];
    $benz_url = "$benz_rl/api/query";
    $result = get_url($benz_url, $data);
    $result = json_decode($result, true);
    if ($result["code"] == -1) {
      $b = ["code" => -1, 'msg' => $result["msg"]];
    } else {
      $courseList = $result["data"];
      foreach ($courseList as $key => $value) {
        $json_data[] = [
          'id' => $value['id'],
          'name' => $value['name'],

        ];
      }
      $b = [
        'code' => 0,
        'msg' => '查询成功',
        'data' => $json_data,
        'userName' => $result['userName'],
      ];
    }
    return $b;
  }
   // longlong查课接口
  else if ($type == "longlong") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "school" => $school, "user" => $user, "pass" => $pass, "platform" => $noun);
    $dx_rl = $a["url"];
    $dx_url = "$dx_rl/api.php?act=get";
    $result = get_url($dx_url, $data);
    $result = json_decode($result, true);
    return $result;
  }
   // hzw查课接口
  else if ($type == "hzw") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "school" => $school, "user" => $user, "pass" => $pass, "platform" => $noun);
    $eq_rl = $a["url"];
    $er_url = "$eq_rl/api.php?act=get";
    $result = get_url($er_url, $data);
    $result = json_decode($result, true);
    return $result;
  }
  
 else {
    $b = array("code" => -1, "msg" => "未匹配到查课接口");
    return $b;
  }
}