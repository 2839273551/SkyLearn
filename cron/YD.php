
<?php
require_once('../confing/common.php');

// 配置项开始
$token = ""; // 您的key
$api_url = "https://xxt.one/"; // 对接地址，例：http://www.xxxxxx.com
$limit = 500; // 一次性获取订单条数，建议 300 - 1000 之间
$day = 3; // 默认在服务器非空闲时间同步近3天的订单（在空闲时间会同步所有订单，默认服务器空闲时间为：00:00 - 08:00）
// 配置项结束

// 下次数据偏移量（请勿修改该参数）
$offset = 0;
// 总计数据条数（请勿修改该参数）
$updateTotal = 0;
while (true) {
  $data = array("token" => $token, "offset" => $offset, "limit" => $limit, "day" => $day);
  $ixx_url = "$api_url/api/getorder";
  $result = httpRequest('POST', $ixx_url, $data, [], true);
  $result = json_decode($result, true);

  if ($result["code"] == 1) {
    $data = $result["data"];
    // 数据总条数
    $total = count($data);
    // 数据总条数累加
    $updateTotal += $total;
    // 下次数据偏移量
    $offset += $limit;

    for ($i = 0; $i < $total; $i++) {
      $DB->query("update qingka_wangke_order set `yid`='{$data[$i]['id']}',`status`='{$data[$i]['status']}',`remarks`='{$data[$i]['remarks']}',`process`='{$data[$i]['process']}',`courseStartTime`='{$data[$i]['kcks']}',`courseEndTime`='{$data[$i]['kcjs']}',`examStartTime`='{$data[$i]['ksks']}',`examEndTime`='{$data[$i]['ksjs']}' where `user`='{$data[$i]['user']}' and `pass`='{$data[$i]['pass']}' and `kcname`='{$data[$i]['kcname']}' and `noun`='{$data[$i]['cid']}' ");
      // 输出每条数据的详细信息
      echo "订单ID: " . $data[$i]['id'] . ", ";
      echo "账号: " . $data[$i]['user'] . ", ";
      echo "状态: " . $data[$i]['status'] . ", ";
      echo "备注: " . $data[$i]['remarks'] . ", ";
      echo "进度: " . $data[$i]['process'] . ", ";
      echo "\r\n";

      // 服务器承受不住，请解除以下注释开启延时
      // sleep(1);
    }

    // 订单总数已经小于单次获取条数，说明没有更多数据了，跳出循环
    if ($total < $limit) {
      echo "本次进度更新已全部完成，总计 " . $updateTotal . " 条数据 \r\n";
      break;
    }
  } else {
    echo "发生错误：" . $result["msg"] . "\r\n";
    break;
  }
}







