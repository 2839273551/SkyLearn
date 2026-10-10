
<?php
function processCx($oid)//进度代码
{
	global $DB;
	$d = $DB->get_row("select * from qingka_wangke_order where oid='{$oid}' ");
	$orderInfo = $DB->get_row("select hid,user,pass from qingka_wangke_order where oid='{$oid}' ");
	$a = $DB->get_row("select * from qingka_wangke_huoyuan where hid='{$orderInfo["hid"]}' ");
	$type = $a["pt"];
	$cookie = $a["cookie"];
	$token = $a["token"];
	$ip = $a["ip"];
	$user = $orderInfo["user"];
	$pass = $orderInfo["pass"];
	$kcname = $d["kcname"];
	$school = $d["school"];
	$pt = $d["noun"];
	$kcid = $d["kcid"];
	$b = array();
	
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

/**
 * 判定订单详细进度备注是否处于中间过程态（未完成最终结算汇报）
 *
 * @param string $remarks 备注信息
 * @return bool true=过程态（需要等待/拉取上游终态汇总）, false=终态（已汇总封板）
 */
function isOrderRemarksIntermediate($remarks) {
    if (empty($remarks)) return true;
    $rem = trim($remarks);
    return (bool)preg_match('/(当前执行:|正在阅读|正在播放|正在做|正在处理|正在答题|正在学习|正在观看|正在考试|正在提交|正在|\[处理中\]|【进行中】|status:【进行中】|【待上号】|待上号|排队中|队列中|上号中)/u', $rem);
}

/**
 * 规范化课程名称用于高容错匹配（消除【课程进度:...】以及平台附加的 ---课程id---省份---城市 干扰）
 */
function normalizeCourseNameForMatch($name) {
    if (empty($name)) return '';
    $s = trim($name);
    // 1. 去除 【课程进度:...】 或 (课程进度:...)
    $s = preg_replace('/[【\(\（]课程进度.*?[】\)\）]/u', '', $s);
    // 2. 去除流年/平台附加的 ---课程id---省份---城市 后缀，例如 ---266911586---湖北省---武汉市
    $s = preg_replace('/---[\s\S]*$/u', '', $s);
    // 3. 去除首尾空白与特殊标点
    return trim($s);
}

/**
 * 智能匹配上游进度条目与本地订单（解决同账号同课程下单课件与单答题进度混淆、yid覆盖等系统性缺陷）
 *
 * @param array $order 本地订单信息数组（包含 oid, yid, kcname, ptname, user 等）
 * @param array $results 上游 processCx 返回的条目数组
 * @return array|null 最佳匹配的上游条目
 */
function matchOrderProgressItem($order, $results) {
    if (empty($results) || !is_array($results)) {
        return null;
    }

    $orderYid = isset($order['yid']) ? trim(strval($order['yid'])) : '';
    $orderOid = isset($order['oid']) ? intval($order['oid']) : 0;
    $orderKc = isset($order['kcname']) ? $order['kcname'] : '';
    $cleanOrderKc = trim(preg_replace('/[【\(（]课程进度.*?[】\)）]/u', '', $orderKc));
    $normOrderKc = normalizeCourseNameForMatch($orderKc);

    // 1. 最高优先级：若订单已有明确的上游 yid，且课程名能对得上，优先精准按 yid 匹配
    if ($orderYid !== '' && $orderYid !== '0') {
        foreach ($results as $item) {
            if (!is_array($item)) continue;
            $itemYid = isset($item['yid']) ? trim(strval($item['yid'])) : (isset($item['id']) ? trim(strval($item['id'])) : '');
            if ($itemYid === $orderYid) {
                $itemKc = isset($item['kcname']) ? $item['kcname'] : '';
                $cleanItemKc = trim(preg_replace('/[【\(（]课程进度.*?[】\)）]/u', '', $itemKc));
                $normItemKc = normalizeCourseNameForMatch($itemKc);
                // 安全校验：确认上游该 yid 的课程与本地订单一致（防止历史串单误绑）
                if (empty($normOrderKc) || empty($normItemKc) || $normOrderKc === $normItemKc || 
                    $cleanItemKc === $cleanOrderKc ||
                    mb_strpos($normOrderKc, $normItemKc) !== false || mb_strpos($normItemKc, $normOrderKc) !== false) {
                    return $item;
                }
            }
        }
    }

    // 2. 筛选出所有与当前课程名匹配的候选集
    $candidates = array();
    foreach ($results as $item) {
        if (!is_array($item)) continue;
        $itemKc = isset($item['kcname']) ? $item['kcname'] : '';
        $cleanItemKc = trim(preg_replace('/[【\(（]课程进度.*?[】\)）]/u', '', $itemKc));
        $normItemKc = normalizeCourseNameForMatch($itemKc);

        $isMatched = false;
        if ($itemKc !== '' && $itemKc === $orderKc) {
            $isMatched = true;
        } elseif ($cleanItemKc !== '' && $cleanItemKc === $cleanOrderKc) {
            $isMatched = true;
        } elseif ($normItemKc !== '' && $normItemKc === $normOrderKc) {
            $isMatched = true;
        } elseif ($normItemKc !== '' && $normOrderKc !== '' && 
                 (mb_strpos($normOrderKc, $normItemKc) !== false || mb_strpos($normItemKc, $normOrderKc) !== false)) {
            $isMatched = true;
        }

        if ($isMatched) {
            $candidates[] = $item;
        }
    }

    // 兜底校验：严禁在课程名完全不相符时，仅仅因为上游返回 1 条其他课程记录就指鹿为马！
    if (empty($candidates)) {
        if (count($results) === 1 && isset($results[0]['status_text'])) {
            $singleKc = isset($results[0]['kcname']) ? $results[0]['kcname'] : '';
            $singleNorm = normalizeCourseNameForMatch($singleKc);
            if (empty($normOrderKc) || empty($singleNorm) || $singleNorm === $normOrderKc || 
                mb_strpos($normOrderKc, $singleNorm) !== false || mb_strpos($singleNorm, $normOrderKc) !== false) {
                return $results[0];
            }
        }
        return null;
    }

    // 若匹配课程只有1条候选，直接返回
    if (count($candidates) === 1) {
        return $candidates[0];
    }

    // 3. 多条候选时的精细化智能裁决（单课件 vs 单答题/单考试、排他性占用等）
    $ptname = isset($order['ptname']) ? $order['ptname'] : '';
    $isExamOrder = preg_match('/(考试|答题|测试|测验|作业)/u', $ptname . ' ' . $orderKc);

    // 查询该账号下其他同名课程订单已占用的 yid，避免重复抢占同一个上游订单
    global $DB;
    $occupiedYids = array();
    if (isset($DB) && is_object($DB) && !empty($order['user'])) {
        $userEsc = daddslashes($order['user']);
        $occRes = $DB->query("SELECT yid FROM qingka_wangke_order WHERE `user`='$userEsc' AND oid != '$orderOid' AND yid != '' AND yid != '0'");
        if ($occRes) {
            while ($row = $DB->fetch($occRes)) {
                if (!empty($row['yid'])) {
                    $occupiedYids[strval($row['yid'])] = true;
                }
            }
        }
    }

    $bestItem = null;
    $bestScore = -999;

    foreach ($candidates as $item) {
        $itemYid = isset($item['yid']) ? trim(strval($item['yid'])) : (isset($item['id']) ? trim(strval($item['id'])) : '');
        $remarks = isset($item['remarks']) ? $item['remarks'] : '';
        $statusText = isset($item['status_text']) ? $item['status_text'] : '';
        $isExamItem = preg_match('/(考试|答题|测试|测验|作业|分\))/u', $remarks . ' ' . $statusText);

        $score = 0;

        // 订单属性与上游条目特征匹配
        if ($isExamOrder) {
            if ($isExamItem) {
                $score += 50; // 强匹配考试
            } else {
                $score -= 30; // 课件记录扣分
            }
        } else {
            if (!$isExamItem) {
                $score += 50; // 强匹配课件
            } else {
                $score -= 30; // 考试记录扣分
            }
        }

        // 避免匹配已经被其他本地订单占用的 yid
        if (!empty($itemYid) && isset($occupiedYids[$itemYid])) {
            $score -= 100;
        }

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestItem = $item;
        }
    }

    return $bestItem ? $bestItem : $candidates[0];
}
