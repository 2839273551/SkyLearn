<?php
// 允许所有域的跨域请求
header('Access-Control-Allow-Origin: *');
// 允许的请求方法
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
// 允许的请求头
header('Access-Control-Allow-Headers: Content-Type, Authorization');
// 允许发送身份验证信息（如 cookies）
header('Access-Control-Allow-Credentials: true');
// 响应类型
header('Content-Type: application/json');
// 处理预检请求（OPTIONS 请求）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
  header('Access-Control-Allow-Headers: Content-Type, Authorization');
  header('Access-Control-Allow-Credentials: true');
  header('HTTP/1.1 200 OK');
  exit();
}

if ($conf['ckkg'] == 0) {
  ApiFail("暂未开放API");
}

// 获取 JSON 数据
$JSON_DATA = file_get_contents('php://input');
$RECEIVE_DATA = json_decode($JSON_DATA, true);

// 身份校验函数
function VerifyAuth()
{
  global $DB, $RECEIVE_DATA;

  $token = trim(strip_tags(daddslashes($RECEIVE_DATA['token']))) ?: trim(strip_tags(daddslashes($RECEIVE_DATA['key'])));
  if (!$token) {
    ApiFail("密钥不能为空");
  }
  $userInfo = $DB->get_row("select `uid`, `user`, `name`, `ck`, `xdlv`, `dd`, `money`, `zcz`, `addprice`, `key`, `yqprice`, `active`, `dockip`, `vip_status`, `faceimg`, `yqm` from love_learn_user where `key`='{$token}' limit 1");
  if (!$userInfo) {
    ApiFail("密匙错误");
  }

  return $userInfo;
}
