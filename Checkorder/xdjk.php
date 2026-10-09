<?php


function wkname()//对接代码对应标识
{
	$data = array(
	 
    "27" => "27系统",
    "29" => "29系统",
    "2xx" => "爱学习",
    "xm" => "SkyLearn",
    "hzw" => "hzw",
     "benz" => "benz",
    "longlong" => "龙龙平台",
    "liunian" => "流年平台",
	     
	    );
	return $data;
}

function checkAddWkResult($b) {
  if (isset($b['code']) && strval($b['code']) === '-1' && !empty($b['msg'])) {
    $checkMsg = (string)$b['msg'];
    if (strpos($checkMsg, "已存在") !== false || 
        strpos($checkMsg, "重复下单") !== false || 
        strpos($checkMsg, "已有订单") !== false || 
        strpos($checkMsg, "已有此课程") !== false || 
        strpos($checkMsg, "重复") !== false || 
        strpos($checkMsg, "存在相同") !== false || 
        strpos($checkMsg, "已下单") !== false) {
      return array("code" => 1, "msg" => "上游已存在该订单(自动纳管)", "already_exists" => true);
    }
  }
  return checkAddWkResult($b);
}

function addWk($oid){//下单接口代码
	global $DB;
	global $wk;
	$d = $DB->get_row("select * from qingka_wangke_order where oid='{$oid}' ");
	$cid = $d["cid"];
	$school = $d["school"];
	$user = $d["user"];
	$pass = $d["pass"];
	$kcid = $d["kcid"];
	$kcname = $d["kcname"];
	$noun = $d["noun"];
	$miaoshua = $d["miaoshua"];
	$b = $DB->get_row("select * from qingka_wangke_class where cid='{$cid}' ");
	$hid = $b["docking"];
	$a = $DB->get_row("select * from qingka_wangke_huoyuan where hid='{$hid}' ");
	$type = $a["pt"];
	$cookie = $a["cookie"];
	$token = $a["token"];
	$ip = $a["ip"];

	/*****
	 自己可以根据规则增加下单接口    
	 
	//XXXX下单接口
	else if ($type == "XXXX") {
	$data = array("optoken" => $token,"type" => $noun);  请求体参数自己加
	$XXXX_ul = $a["url"];      变量XXXX自己命名    获取顶级域名
	$XXXX_dingdan = "http://$XXXX_ul/api/CourseQuery/api/";    请求接口   XXXX自己命名
	$result = get_url($XXXX_dingdan, $data, $cookie); 
	$result = json_decode($result, true);
	
	if ($result["code"] == "0") {
		$b = array("code" => 1, "msg" => $result["msg"]);
	} else {
		$b = array("code" => -1, "msg" => $result["msg"]);
	}
	return checkAddWkResult($b);
    }
	
	
	$token  传的token
	$school  传的学校
	$user    传的账号
	$pass    传的密码
	$noun    传的平台里面的接口编号 
	$kcid    传的课程id
	****/ 
	
 
	
 //27下单接口
	 
	 if ($type == "27") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "platform" => $noun, "school" => $school, "user" => $user, "pass" => $pass, "kcname" => $kcname);
    $eq_rl = $a["url"];
    $eq_url = "$eq_rl/api.php?act=add";
    $result = get_url($eq_url, $data, $cookie);
    $result = json_decode($result, true);
    if ($result["code"] == "0") {
      $b = array("code" => 1, "msg" => "下单成功");
    } else {
      $b = array("code" => -1, "msg" => $result["msg"]);
    }
    return checkAddWkResult($b);
  }
	 
	 //29系统下单接口
  else if ($type == "29") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "platform" => $noun, "school" => $school, "user" => $user, "pass" => $pass, "kcname" => $kcname, "kcid" => $kcid);
    $dx_rl = $a["url"];
    $dx_url = "$dx_rl/api.php?act=add";
    $result = get_url($dx_url, $data);
    $result = json_decode($result, true);
    if ($result["code"] == "0") {
      $b = array("code" => 1, "msg" => "下单成功");
    } else {
      $b = array("code" => -1, "msg" => $result["msg"]);
    }
    return checkAddWkResult($b);
  }
  //爱学习
	 else if ($type == "2xx") {
    $data = array("token" => $a["pass"], "platform" => $noun, "school" => $school, "user" => $user, "pass" => $pass, "kcname" => $kcname, "kcid" => $kcid, "time" => $uTime, "score" => $uScore, "speed" => $d["study_speed"], "exam_submit" => $d["is_submit_exam"], "exam_time" => $d["exam_time"]);
    $ixx_url = $a["url"] . "/api/add";
    $result = httpRequest('POST', $ixx_url, $data, [], true);
    $result = json_decode($result, true);
    if ($result["code"] == "1") {
      $b = array("code" => 1, "msg" => "下单成功", "yid" => $result["id"]);
    } else {
      $b = array("code" => -1, "msg" => $result["msg"]);
    }
    return checkAddWkResult($b);
  }
	 //benz下单接口
  else if ($type == "benz") {
    $data = array("token" => $token, "ptid" => $noun, "school" => $school, "user" => $user, "pass" => $pass, "kcname" => $kcname, "kcid" => $kcid, "shichang" => $uTime);
    $benz_rl = $a["url"];
    $benz_url = "$benz_rl/api/add";
    $result = get_url($benz_url, $data, $cookie);
    $result = json_decode($result, true);
    if ($result["code"] == "0") {
      $b = array("code" => 1, "msg" => "下单成功");
    } else {
      $b = array("code" => -1, "msg" => $result["msg"]);
    }
    return checkAddWkResult($b);
  }
	 // hzw下单接口
  else if ($type == "hzw") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "platform" => $noun, "school" => $school, "user" => $user, "pass" => $pass, "kcid" => $kcid, "kcname" => $kcname);
    $eq_rl = $a["url"];
    $eq_url = "$eq_rl/api.php?act=add";
    $result = get_url($eq_url, $data);
    $result = json_decode($result, true);
    if ($result["code"] == "1") {
      $b = array("code" => 1, "msg" => "下单成功", "yid" => $result["id"]);
    } else {
      $b = array("code" => -1, "msg" => $result["msg"]);
    }
    return checkAddWkResult($b);
  }
	 
	 // SkyLearn
  else if ($type == "xm") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "platform" => $noun, "school" => $school, "user" => $user, "pass" => $pass, "kcname" => $kcname, "kcid" => $kcid);
    $eq_rl = $a["url"];
    $eq_url = "$eq_rl/api.php?act=add";
    $result = get_url($eq_url, $data, $cookie);
    $result = json_decode($result, true);
    if ($result["code"] == "0") {
      $b = array("code" => 1, "msg" => "下单成功", "yid" => $result["id"]);
    } else {
      $b = array("code" => -1, "msg" => $result["msg"]);
    }
    return checkAddWkResult($b);
  }
	   // longlong下单接口
  else if ($type == "longlong") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "platform" => $noun, "school" => $school, "user" => $user, "pass" => $pass, "kcname" => $kcname, "kcid" => $kcid);
    $dx_rl = $a["url"];
    $dx_url = "$dx_rl/api.php?act=add";
    $result = get_url($dx_url, $data);
    $result = json_decode($result, true);
    if ($result["code"] == "0") {
      $b = array("code" => 1, "msg" => "下单成功");
    } else {
      $b = array("code" => -1, "msg" => $result["msg"]);
    }
    return checkAddWkResult($b);
  }
  else if ($type == "liunian") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "platform" => $noun, "school" => $school, "user" => $user, "pass" => $pass, "kcname" => $kcname, "kcid" => $kcid);
    $dx_rl = $a["url"];
    $dx_url = "$dx_rl/api.php?act=add";
    $result = get_url($dx_url, $data);
    $result = json_decode($result, true);
    if ($result["code"] == "0") {
      $b = array("code" => 1, "msg" => "下单成功", "yid" => $result['id']);
    } else {
      $b = array("code" => -1, "msg" => $result["msg"]);
    }
    return checkAddWkResult($b);
  }
  
  else {
    $b = array("code" => -1, "msg" => "未匹配到下单接口");
  }

  // 智能识别“上游已存在/重复下单”场景：上游已接单且在跑，视为交单成功并自动纳管
  if (isset($b['code']) && $b['code'] == -1 && !empty($b['msg'])) {
    $checkMsg = (string)$b['msg'];
    if (strpos($checkMsg, '已存在') !== false || 
        strpos($checkMsg, '重复下单') !== false || 
        strpos($checkMsg, '已有订单') !== false || 
        strpos($checkMsg, '已有此课程') !== false || 
        strpos($checkMsg, '重复') !== false || 
        strpos($checkMsg, '存在相同') !== false ||
        strpos($checkMsg, '已下单') !== false) {
      $b = array('code' => 1, 'msg' => '上游已存在该订单(自动纳管)', 'already_exists' => true);
    }
  }

  return checkAddWkResult($b);
}