<?php

function logWk($oid)
{
  global $DB;
  $d = $DB->get_row("select * from qingka_wangke_order where oid='{$oid}' limit 1");
  $hid = $d["hid"];
  $yid = $d["yid"];
  $user = $d["user"];
  $pass = $d["pass"];
  $kcname = $d["kcname"];
  $kcid = $d["kcid"];
  $a = $DB->get_row("select * from qingka_wangke_huoyuan where hid='{$hid}' limit 1");
  $type = $a["pt"];



   if ($type == "2xx") {
    $data = array("uid" => $a["user"], "key" => $a["pass"], "id" => $yid);
    $eq_rl = $a["url"];
    $eq_url = "$eq_rl/api.php?act=xq";
    $result = get_url($eq_url, $data, $cookie);
    if (!$result) {
        return array("code" => -1, "msg" => "接口请求失败");
    }
    $result = json_decode($result, true);
    if (!isset($result["code"])) {
        return array("code" => -1, "msg" => "接口返回格式错误");
    }
    if ($result["code"] == 1) {
        if (!isset($result["data"]) || !is_array($result["data"])) {
            return array("code" => -1, "msg" => "数据解析失败");
        }
        $logs = [];
        foreach ($result["data"] as $log) {
            $logs[] = [
                "time"    => $log["time"] ?? "未知",
                "course"  => $log["course"] ?? "未知",
                "status"  => $log["status"] ?? "未知",
                "process" => $log["process"] ?? "未知",
                "remarks" => $log["remarks"] ?? "无备注",
                // "detail"  => $log["detail"] ?? "橘子海"
            ];
        }
        wlog($row['uid'], "API详情", "订单{$oid}已获取日志", 0);
        return array("code" => 1, "msg" => "查询成功", "data" => $logs);
    } else {
        return array("code" => -1, "msg" => $result["msg"]);
    }
}

     
      
        else {
    $b = array("code" => -1, "msg" => "当前项目暂不支持查看日志操作");
    return $b;
  }
}