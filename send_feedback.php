<?php
include('confing/common.php');

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => '无效的请求方法']));
}

// 获取POST数据
$feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';
$contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
$token = isset($_POST['token']) ? trim($_POST['token']) : '';
$user = isset($_POST['user']) ? trim($_POST['user']) : '匿名用户';

if (empty($feedback)) {
    die(json_encode(['success' => false, 'message' => '反馈内容不能为空']));
}

// 构建消息内容
$message = "【价格监控功能反馈】\n";
$message .= "用户: $user\n";
$message .= "时间: " . date('Y-m-d H:i:s') . "\n";
$message .= "反馈内容:\n$feedback\n";
if (!empty($contact)) {
    $message .= "\n联系方式: $contact";
}

// 发送到Chanify
$url = "https://api.chanify.net/v1/sender/$token";
$data = ['text' => $message];
$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
    ],
];
$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

header('Content-Type: application/json');
if ($result !== false) {
    echo json_encode(['success' => true, 'message' => '反馈已发送']);
} else {
    echo json_encode(['success' => false, 'message' => '发送失败，请检查Chanify配置']);
}
?>