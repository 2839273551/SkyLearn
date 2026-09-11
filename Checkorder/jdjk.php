
<?php
function processCx($oid)//进度代码
{
	global $DB;
	$d = $DB->get_row("select * from qingka_wangke_order where oid='{$oid}' ");
	$b = $DB->get_row("select hid,user,pass from qingka_wangke_order where oid='{$oid}' ");
	$a = $DB->get_row("select * from qingka_wangke_huoyuan where hid='{$b["hid"]}' ");
	$type = $a["pt"];
	$cookie = $a["cookie"];
	$token = $a["token"];
	$ip = $a["ip"];
	$user = $b["user"];
	$pass = $b["pass"];
	$kcname = $d["kcname"];
	$school = $d["school"];
	$pt = $d["noun"];
	$kcid = $d["kcid"];
	
	//27同步状态接口
  
    //27同步状态接口
  if ($type == "27") {
    $data = array("username" => $user);
    $eq_rl = $a["url"];
    $eq_url = "$eq_rl/api.php?act=chadan";
    $result = get_url($eq_url, $data);
    $result = json_decode($result, true);
    if ($result["code"] == "1") {
      foreach ($result["data"] as $res) {
        $yid = $res["id"];
        $kcname = $res["kcname"];
        $status = $res["status"];
        $process = $res["process"];
        $remarks = $res["remarks"];
        $kcks = $res["courseStartTime"];
        $kcjs = $res["courseEndTime"];
        $ksks = $res["examStartTime"];
        $ksjs = $res["examEndTime"];
        $b[] = array("code" => 1, "msg" => "查询成功", "yid" => $yid, "kcname" => $kcname, "user" => $user, "pass" => $pass, "ksks" => $ksks, "ksjs" => $ksjs, "status_text" => $status, "process" => $process, "remarks" => $remarks);
      }
    } else {
      $b[] = array("code" => -1, "msg" => "查询失败,请联系管理员");
    }
    return $b;
  }
  // 爱学习同步状态接口
  else if ($type == "2xx") {
    $data = array("id" => $d['yid'], "username" => $user);
    $ixx_url = $a["url"] . "/api/refresh";
    $result = httpRequest('POST', $ixx_url, $data, [], true);
    $result = json_decode($result, true);
    if ($result["code"] == "1") {
      foreach ($result["data"] as $res) {
        $yid = $res["id"];
        $kcname = $res["kcname"];
        $status = $res["status"];
        $process = $res["process"];
        $remarks = $res["remarks"];
        $xxtremarks = $res["xxtremarks"];
        $kcks = $res["courseStartTime"];
        $kcjs = $res["courseEndTime"];
        $ksks = $res["examStartTime"];
        $ksjs = $res["examEndTime"];

        $b[] = array("code" => 1, "msg" => "查询成功", "yid" => $yid, "kcname" => $kcname, "user" => $user, "pass" => $pass, "ksks" => $ksks, "ksjs" => $ksjs, "status_text" => $status, "process" => $process, "remarks" => $remarks, "xxtremarks" => $xxtremarks);
      }
    } else {
      $b[] = array("code" => -1, "msg" => "查询失败,请联系管理员");
    }
    return $b;
  }
  //benz同步状态接口
  else if ($type == "benz") {
    $data = array("token" => $token, "user" => $user);
    $benz_rl = $a["url"];
    $benz_url = "$benz_rl/api/order";
    $result = get_url($benz_url, $data, $cookie);
    $result = json_decode($result, true);
    if ($result["code"] == "1") {
      foreach ($result["data"] as $res) {
        $yid = $res["id"];
        $kcname = $res["kcname"];
        $status = $res["status"];
        $process = $res["process"];
        $remarks = $res["remarks"];
        $kcks = $res["courseStartTime"];
        $kcjs = $res["courseEndTime"];
        $ksks = $res["examStartTime"];
        $ksjs = $res["examEndTime"];
        $b[] = array("code" => 1, "msg" => "查询成功", "yid" => $yid, "kcname" => $kcname, "user" => $user, "pass" => $pass, "kcks" => $kcks, "kcjs" => $kcjs, "ksks" => $ksks, "ksjs" => $ksjs, "status_text" => $status, "process" => $process, "remarks" => $remarks);
      }
    } else {
      $b[] = array("code" => -1, "msg" => "查询失败,请重试");
    }
    return $b;
  }
   //29系统进度
  else if ($type == "29") {
    $data = array("username" => $user, "uid" => $a["user"], "key" => $a["pass"]);
    $dx_rl = $a["url"];
    $dx_url = "$dx_rl/api.php?act=chadan";
    $result = get_url($dx_url, $data);
    $result = json_decode($result, true);
    if ($result["code"] == "1") {
      foreach ($result["data"] as $res) {
        $yid = $res["id"];
        $kcname = $res["kcname"];
        $status = $res["status"];
        if ($status == '补刷中') {
          $status = '重刷中';
        }
        if ($status == '') {
          $status = '待上号';
        }
        if ($status == '队列中') {
          $status = '待上号';
        }
        $process = $res["process"];
        // 判断 $process 中是否包含 / 符号
        if (strpos($process, '/') !== false) {
          // 通过 / 符号分割字符串并转换为百分比
          $parts = explode('/', $process);
          $process = round(($parts[0] / $parts[1]) * 100, 2) . '%';
        }
        $remarks = $res["remarks"];
        $kcks = $res["courseStartTime"];
        $kcjs = $res["courseEndTime"];
        $ksks = $res["examStartTime"];
        $ksjs = $res["examEndTime"];
        $b[] = array("code" => 1, "msg" => "查询成功", "yid" => $yid, "kcname" => $kcname, "user" => $user, "pass" => $pass, "ksks" => $ksks, "ksjs" => $ksjs, "status_text" => $status, "process" => $process, "remarks" => $remarks);
      }
    } else {
      $b[] = array("code" => -1, "msg" => $result["msg"]);
    }
    return $b;
  }
  
    // hzw进度接口
  else if ($type == "hzw") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "username" => $user, "id" => $d['yid']);
    $eq_rl = $a["url"];
    $eq_url = "$eq_rl/api.php?act=chadan";
    $result = get_url($eq_url, $data);
    $result = json_decode($result, true);
    $b = [];
    if ($result["code"] == "1") {
      foreach ($result["data"] as $res) {
        $yid = $res["id"];
        $cid = $pt;
        $kcname = $res["kcname"];
        $status = $res["status"];
        $progress = $res["progress"] . '%';
        $remarks = $res["remarks"];
        $process = $res["process"];
        if (!!$process) {
          $remarks = $process . '|' . $remarks;
        }
        $kcks = $res["courseStartTime"];
        $kcjs = $res["courseEndTime"];
        $ksks = $res["examStartTime"];
        $ksjs = $res["examEndTime"];
        $b[] = array("code" => 1, "msg" => "查询成功", "yid" => $yid, "cid" => $cid, "kcname" => $kcname, "user" => $user, "pass" => $pass, "ksks" => $ksks, "ksjs" => $ksjs, "status_text" => $status, "process" => $progress, "remarks" => $remarks);
      }
    } else {
      $b[] = array("code" => -1, "msg" => $result["msg"]);
    }
    return $b;
  }
  // SkyLearn
  else if ($type == "xm") {
    $xm_rl = $a["url"];
    $kcname_encoded = urlencode($kcname);
    $user_encoded = urlencode($user);
    $xm_url = "$xm_rl/api/search?uid=" . $a["user"] . "&key=" . $a["pass"] . "&kcname=" . $kcname_encoded . "&username=" . $user_encoded . "&cid=" . $d["noun"];
    $result = get_url($xm_url);
    $result = json_decode($result, true);
    if ($result["code"] == "1") {
      foreach ($result["data"] as $res) {
        $yid = $res["id"];
        $kcname = $res["kcname"];
        $status = $res["status"];
        $process = $res["process"];
        $remarks = $res["remarks"];
        if ($res["zhgx"] !== "无" && $res["zhgx"] !== "" && $res["zhgx"] !== null) {
          $remarks .= "|御弟哥哥|最近学习:" . $res["zhgx"];
        }
        $kcks = $res["courseStartTime"];
        $kcjs = $res["courseEndTime"];
        $ksks = $res["examStartTime"];
        $ksjs = $res["examEndTime"];
        $zhgx = $res["zhgx"];
        $b[] = array("code" => 1, "msg" => "查询成功", "yid" => $yid, "kcname" => $kcname, "user" => $user, "pass" => $pass, "ksks" => $ksks, "ksjs" => $ksjs, "status_text" => $status, "process" => $process, "remarks" => $remarks, "zhgx" =>  $zhgx);
      }
    } else {
      $b[] = array("code" => -1, "msg" => $result["msg"]);
    }
    return $b;
  }
    // longlong进度
  else if ($type == "longlong") {
    $b = array();
    $data = array("username" => $user, "uid" => $a["user"], "key" => $a["pass"]);
    $dx_rl = $a["url"];
    $dx_url = "$dx_rl/api.php?act=chadan";
    $result = get_url($dx_url, $data);
    $result = json_decode($result, true);
    if ($result["code"] == "1") {
      foreach ($result["data"] as $res) {
        $yid = $res["id"];
        $kcname = $res["kcname"];
        $status = $res["status"];
        $process = $res["process"];
        $more = $res['order']['score'];
        $order = $res['order'];
        $dlz = $order["status"];
        $more = json_decode($more, true);
        if ($d["hid"] == "你的对接接口id") {//longlong货源ID
          $remarks = $order["result"];
          $remarks .= "。当前总分:" . $more["score"] . " 习惯分:" . $more["xiguan"] . " 视频:" . $more["jindu"] . " 作业:" . $more["zuoye"] . " 见面课:" . $more["jianmian"] . " 互动:" . $more["hudong"] . " 考试:" . $more["exam"] . " 考试时间:" . $more["examStartTime"];
        } else {
          $remarks = $res["remarks"];
        }
        $kcks = $res["courseStartTime"];
        $kcjs = $res["courseEndTime"];
        $ksks = $res["examStartTime"];
        $ksjs = $res["examEndTime"];

        $msg = merge_spaces(trim($process));
        $msg1 = merge_spaces(trim($remarks));

        $jindu = explode("%", $msg);
        $jindu1 = explode("%", $msg1);
        if (is_numeric($jindu[0])) {
          $jindu = $jindu[0] . '%';
        } elseif (is_numeric($jindu1[0])) {
          $jindu = $jindu1[0] . '%';
        } else {
          $jindu = explode("/", $msg);
          if (is_numeric($jindu[0])) {
            $jindu = number_format($jindu[0] / $jindu[1] * 100, 2);
            $jindu = $jindu . '%';
          } else {
            $jindu = '处理中..';
          }
        }

        if (strpos($msg, ",当前进度:") != false) {
          $jindu = explode("当前进度:", $msg);
          $jindu = $jindu[1];
        }

        if ($jindu == '处理中..') {
          if ($status == '已完成') $jindu = '100%';
          if ($status == '待处理') $jindu = '1%';
        }

        if ($jindu == '0%') $jindu = '1%';
        if ($jindu == 'nan%') $jindu = '0%';

        if ($process == $remarks) $process = '';

        $b[] = array("code" => 1, "msg" => "查询成功", "yid" => $yid, "kcname" => $kcname, "user" => $user, "pass" => $pass, "ksks" => $ksks, "ksjs" => $ksjs, "process" => $jindu, "status_text" => $status, "remarks" => $remarks);
      }
    } else {
      $b[] = array("code" => -1, "msg" => "查询失败,请联系管理员");
    }
    return $b;
  }
  //流年进度新接口
  else if ($type == "liunian") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "kcname" => $kcname, "username" => $user, "cid" => $d["noun"], "yid" => $d["yid"]);
    $dx_rl = $a["url"];
    $dx_url = "$dx_rl/api/chadan1";
    $result = get_url($dx_url, $data);
    $result = json_decode($result, true);
    if ($result["code"] == "1") {
      foreach ($result["data"] as $res) {
        $yid = $res["id"];
        $kcname = $res["kcname"];
        $status = $res["status"];
        $process = $res["process"];
        $remarks = $res["remarks"];
        $kcks = $res["courseStartTime"];
        $kcjs = $res["courseEndTime"];
        $ksks = $res["examStartTime"];
        $ksjs = $res["examEndTime"];
        $b[] = array("code" => 1, "msg" => "查询成功", "yid" => $yid, "kcname" => $kcname, "user" => $user, "pass" => $pass, "ksks" => $ksks, "ksjs" => $ksjs, "status_text" => $status, "process" => $process, "remarks" => $remarks);
      }
    } else {
      $b[] = array("code" => -1, "msg" => $result["msg"]);
    }
    return $b;
  }
  
  
  
   else {
    $b = array("code" => -1, "msg" => "该课程不支持进度同步");
    return $b;
  }
}
