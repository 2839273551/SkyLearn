<?php
include('../confing/common.php');

// 接口ID
$interfaceId = intval($_GET['hid']);
// 价格倍数
$price_multiple = $_GET['price'] ?: 5;

$hy = $DB->get_row("select hid, url, user, pass, name from qingka_wangke_huoyuan where hid='{$interfaceId}' limit 1 ");

$data = array("uid" => $hy['user'], "key" => $hy['pass']);
$ixx_url = "{$hy['url']}/api.php?act=getclass";
$result = httpRequest('POST', $ixx_url, $data, [], false);
$result = json_decode($result, true);

if ($result["code"] == 1) {
  $data = $result["data"];
  // 数据总条数
  $total = count($data);

  for ($i = 0; $i < $total; $i++) {
    $cid = daddslashes($data[$i]['cid']);
    $price = round(daddslashes($data[$i]['price']) * $price_multiple, 2);
    $content = daddslashes($data[$i]['content']);

    $DB->query("update qingka_wangke_class set `price`='{$price}',`content`='{$content}' where `noun`='{$cid}' and `docking`='{$hy['hid']}' limit 1");
  }

  exit("{$hy['name']} 商品价格更新已全部完成，总计 " . $total . " 条数据 \r\n");
} else {
  exit("发生错误：" . $result["msg"] . "\r\n");
}
