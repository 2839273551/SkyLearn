<?php
include('../confing/common.php');
header('Content-Type: application/json; charset=UTF-8');

$uid = isset($_POST['uid']) ? daddslashes($_POST['uid']) : '';
$key = isset($_POST['key']) ? daddslashes($_POST['key']) : '';

if (empty($uid) || empty($key)) {
    exit(json_encode(array("code" => 0, "msg" => "所有项目不能为空")));
}

$row = $DB->get_row("SELECT * FROM qingka_wangke_user WHERE uid='$uid' LIMIT 1");

if (!$row || $row['key'] == '0') {
    exit(json_encode(array("code" => -1, "msg" => "你还没有开通接口哦")));
}

if ($row['key'] != $key) {
    exit(json_encode(array("code" => -2, "msg" => "密匙错误")));
} else {
    $data = array();
    $result = $DB->query("SELECT * FROM qingka_wangke_fenlei WHERE status=1");
    
    while ($row = $DB->fetch($result)) {
        $data[] = array(
            'id' => $row['id'],
            'name' => $row['name'],
            'fenlei' => $row['id']
        );
    }

    $response = array(
        'code' => 1,
        'msg' => "成功获取分类信息",
        'data' => $data
    );

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>
