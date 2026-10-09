<?php
include('confing/common.php');
include('ayconfig.php');

header('Content-Type: application/json; charset=UTF-8');
if (empty($islogin) || empty($userrow['uid'])) {
    http_response_code(401);
    exit(json_encode(['code' => -1, 'msg' => '请先登录']));
}
// 在gd.php文件开头添加
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// 检查直接访问
$php_Self = substr($_SERVER['PHP_SELF'], strripos($_SERVER['PHP_SELF'], "/") + 1);
if ($php_Self != "gd.php") {
    exit(json_encode(['code' => -1, 'msg' => '文件错误']));
}

// 获取管理员推送Token
$supertoken = $DB->get_row("SELECT `pushPlusToken` FROM `qingka_wangke_user` WHERE `uid`='1'");
$current_time = date('Y-m-d H:i:s');

/**
 * 推送通知函数
 * @param string $token 推送Token
 * @param string $title 通知标题
 * @param string $content 通知内容
 * @return bool 推送是否成功
 */
function sendPushNotification($token, $title, $content) {
    if (empty($token)) {
        file_put_contents('push_error.log', date('Y-m-d H:i:s')." | 错误: Token为空\n", FILE_APPEND);
        return false;
    }
    
    // 准备推送数据
    $title = mb_substr($title, 0, 100);
    $content = mb_substr($content, 0, 500);
    $content = str_replace(["\r", "\n"], ' ', $content);
    
    $apiUrl = 'https://push.showdoc.com.cn/server/api/push/' . urlencode($token) . 
              '?title=' . urlencode($title) . 
              '&content=' . urlencode($content);
    
    // 使用cURL发送请求
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FAILONERROR => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // 记录日志
    $logData = [
        'time' => date('Y-m-d H:i:s'),
        'token' => $token,
        'title' => $title,
        'response' => $response,
        'http_code' => $httpCode
    ];
    file_put_contents('push_complete.log', json_encode($logData)."\n", FILE_APPEND);
    
    return $response !== false;
}
// 主逻辑
switch ($_GET['act']) {
    // 添加工单
    
    case 'feedback':
        
//         error_reporting(E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
    $oid = intval($_GET['oid']);
    $feedback = trim($_REQUEST['feedback']); 
    
    // 验证输入
    if (empty($oid) || empty($feedback)) {
        exit(json_encode(['code' => 0, 'msg' => '订单ID或反馈内容不能为空']));
    }
    
    if (strlen($feedback) > 50) {
        exit(json_encode(['code' => 0, 'msg' => '反馈内容不能超过50个字']));
    }
    
    // 获取订单信息
    $order = $DB->get_row("SELECT * FROM `qingka_wangke_order` WHERE `oid` = '$oid'");
    if (!$order) {
        exit(json_encode(['code' => 0, 'msg' => '订单不存在']));
    }
    if ((string)$userrow['uid'] !== '1' && (string)$order['uid'] !== (string)$userrow['uid']) {
        exit(json_encode(['code' => 0, 'msg' => '无权操作此订单']));
    }
    
    $date = date('Y-m-d H:i:s');
    
    // 构建工单标题和内容
    $title = $order['ptname'] . "\n" . $order['school'] . "\n" . 
             $order['kcname'] . "\n状态: " . $order['status'] . " 备注: " . $order['remarks'] . 
             "\n下单时间: " . $order['addtime'];
    
    $content = $date . " 用户反馈: " . $feedback;
    
    // 检查是否已存在相同工单
    $exists = $DB->get_row("SELECT gid FROM `qingka_wangke_gongdan` WHERE `title` = " . $DB->escape($title));
    if ($exists) {
        exit(json_encode(['code' => 0, 'msg' => '该订单问题已提交过工单']));
    }
    
    // 插入新工单
    $insertResult = $DB->query("INSERT INTO `qingka_wangke_gongdan` 
                              (`title`, `region`, `content`, `uid`, `state`, `addtime`) 
                              VALUES 
                              (" . $DB->escape($title) . ", '$oid', " . $DB->escape($content) . ", 
                              '{$userrow['uid']}', '待回复', '$date')");
    
    if (!$insertResult) {
        exit(json_encode(['code' => 0, 'msg' => '反馈失败，请重试']));
    }
    
    // 获取管理员推送Token
    $supertoken = $DB->get_row("SELECT `pushPlusToken` FROM `qingka_wangke_user` WHERE `uid`='1'");
    
    // 推送通知给管理员
    $message = "用户 {$userrow['uid']} 反馈了订单 #$oid\n"
             . "时间: $date\n"
             . "内容: $feedback";
    
    if (!empty($supertoken['pushPlusToken'])) {
        sendPushNotification($supertoken['pushPlusToken'], '新订单反馈', $message);
    }
    
    // 检查用户是否绑定推送Token
    $usertoken = $DB->get_row("SELECT `pushPlusToken` FROM `qingka_wangke_user` WHERE `uid`='{$order['uid']}'");
    
    $response = [
        'code' => 1,
        'msg' => '反馈成功'
    ];
    
    if (empty($usertoken['pushPlusToken'])) {
        $response['msg'] = '反馈成功，请绑定推送token以接收回复通知';
    }
    
    exit(json_encode($response));
    break;
    
    
    
    
    
    
    
    
    case 'addTicket':
        $content = trim(strip_tags(daddslashes($_GET['content'])));
        if (empty($content)) {
            exit(json_encode(['code' => -1, 'msg' => '问题内容不能为空']));
        }
        if (strlen($content) > 100) {
            exit(json_encode(['code' => -1, 'msg' => '问题不能超过100个字']));
        }
        
        $title = "无";
        $region = "其他问题";
        $content = $current_time . " 用户提问: " . $content;
        
        $insertResult = $DB->query("INSERT INTO `qingka_wangke_gongdan` 
                                  (`title`, `region`, `content`, `uid`, `state`, `addtime`) 
                                  VALUES 
                                  ('$title', '$region', '$content', '{$userrow['uid']}', '待回复', '$current_time')");
        
        if ($insertResult) {
            // 推送通知给管理员
            $message = "用户{$userrow['uid']}提交了新工单\n类型: {$region}\n时间: {$current_time}";
            sendPushNotification($supertoken['pushPlusToken'], '新工单通知', $message);
            
            exit(json_encode(['code' => 1, 'msg' => '工单新增成功']));
        } else {
            exit(json_encode(['code' => -1, 'msg' => '工单新增失败']));
        }
        break;
        
    // 获取工单列表
    case 'gdlist':
        $searchQuery = isset($_POST['searchQuery']) ? trim(strip_tags($_POST['searchQuery'])) : '';
        $statusFilter = isset($_POST['statusFilter']) ? trim(strip_tags($_POST['statusFilter'])) : '';
        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $limit = isset($_POST['limit']) ? max(1, intval($_POST['limit'])) : 10;
        $offset = ($page - 1) * $limit;
        
        // 基础查询条件
        if ($userrow['uid'] != '1') {
            $where = "WHERE uid='{$userrow['uid']}'";
        } else {
            $where = "WHERE 1=1";
        }
        
        // 添加搜索条件
        if (!empty($searchQuery)) {
            $where .= " AND (title LIKE '%$searchQuery%' OR region LIKE '%$searchQuery%' OR content LIKE '%$searchQuery%')";
        }
        
        // 添加状态筛选
        if (!empty($statusFilter)) {
            $where .= " AND state='$statusFilter'";
        }
        
        // 获取总数
        $totalQuery = $DB->query("SELECT COUNT(*) as total FROM qingka_wangke_gongdan $where");
        $totalResult = $DB->fetch($totalQuery);
        $total = $totalResult['total'];
        
        // 获取分页数据
        $dataQuery = $DB->query("SELECT * FROM qingka_wangke_gongdan $where ORDER BY gid DESC LIMIT $limit OFFSET $offset");
        $data = [];
        while ($row = $DB->fetch($dataQuery)) {
            $data[] = $row;
        }
        
        exit(json_encode(['code' => 1, 'data' => $data, 'total' => $total]));
        break;
        
    // 保存推送Token
    case 'savePushToken':
        $token = trim(strip_tags(daddslashes($_GET['token'])));
        $updateResult = $DB->query("UPDATE `qingka_wangke_user` SET `pushPlusToken`='$token' WHERE `uid`='{$userrow['uid']}'");
        
        if ($updateResult) {
            exit(json_encode(['code' => 1, 'msg' => '推送Token保存成功']));
        } else {
            exit(json_encode(['code' => -1, 'msg' => '推送Token保存失败']));
        }
        break;
        
    // 获取推送Token
    case 'getPushToken':
        $token = $DB->get_row("SELECT `pushPlusToken` FROM `qingka_wangke_user` WHERE `uid`='{$userrow['uid']}'");
        if ($token) {
            exit(json_encode(['code' => 1, 'token' => $token['pushPlusToken']]));
        } else {
            exit(json_encode(['code' => -1, 'msg' => '暂未设置推送token']));
        }
        break;
        
    // 删除工单
    case 'shan':
        $gid = trim(strip_tags(daddslashes($_POST['gid'])));
        $ticket = $DB->get_row("SELECT * FROM qingka_wangke_gongdan WHERE gid='{$gid}'");
        
        if (!$ticket) {
            exit(json_encode(['code' => -1, 'msg' => '工单不存在']));
        }
        if ((string)$userrow['uid'] !== '1' && (string)$ticket['uid'] !== (string)$userrow['uid']) {
            exit(json_encode(['code' => -1, 'msg' => '无权操作此工单']));
        }
        
        // 权限检查
        if ($userrow['uid'] != $ticket['uid'] && $userrow['uid'] != '1') {
            exit(json_encode(['code' => -1, 'msg' => '无权删除此工单']));
        }
        
        $deleteResult = $DB->query("DELETE FROM qingka_wangke_gongdan WHERE gid='{$gid}'");
        
        if ($deleteResult) {
            exit(json_encode(['code' => 1, 'msg' => '删除成功']));
        } else {
            exit(json_encode(['code' => -1, 'msg' => '删除失败']));
        }
        break;
        
    // 回复工单
    case 'answer':
        $gid = trim(strip_tags(daddslashes($_POST['gid'])));
        $answer = trim(strip_tags(daddslashes($_POST['answer'])));
        
        // 权限检查
        if ($userrow['uid'] != '1') {
            exit(json_encode(['code' => -1, 'msg' => '无权限操作']));
        }
        
        $ticket = $DB->get_row("SELECT * FROM qingka_wangke_gongdan WHERE gid='{$gid}'");
        if (!$ticket) {
            exit(json_encode(['code' => -1, 'msg' => '工单不存在']));
        }
        if ((string)$userrow['uid'] !== '1' && (string)$ticket['uid'] !== (string)$userrow['uid']) {
            exit(json_encode(['code' => -1, 'msg' => '无权操作此工单']));
        }
        
        $newContent = $ticket['content'] . "\n\n" . $current_time . " 管理员回复: " . $answer;
        $updateResult = $DB->query("UPDATE qingka_wangke_gongdan SET `content`='$newContent', `state`='已回复' WHERE gid='$gid'");
        
        if ($updateResult) {
            // 推送通知给用户
            $userToken = $DB->get_row("SELECT `pushPlusToken` FROM `qingka_wangke_user` WHERE `uid`='{$ticket['uid']}'");
            $message = "您反馈的工单有新回复\n时间: {$current_time}\n内容: {$answer}";
            sendPushNotification($userToken['pushPlusToken'], '工单回复通知', $message);
            
            exit(json_encode(['code' => 1, 'msg' => '回复成功']));
        } else {
            exit(json_encode(['code' => -1, 'msg' => '回复失败']));
        }
        break;
        
    // 完成工单
    case 'bohui':
        $gid = trim(strip_tags(daddslashes($_POST['gid'])));
        $answer = trim(strip_tags(daddslashes($_POST['answer'])));
        
        // 权限检查
        if ($userrow['uid'] != '1') {
            exit(json_encode(['code' => -1, 'msg' => '无权限操作']));
        }
        
        $ticket = $DB->get_row("SELECT * FROM qingka_wangke_gongdan WHERE gid='{$gid}'");
        if (!$ticket) {
            exit(json_encode(['code' => -1, 'msg' => '工单不存在']));
        }
        if ((string)$userrow['uid'] !== '1' && (string)$ticket['uid'] !== (string)$userrow['uid']) {
            exit(json_encode(['code' => -1, 'msg' => '无权操作此工单']));
        }
        
        $newContent = $ticket['content'] . "\n\n" . $current_time . " 管理员完成工单，备注: " . $answer;
        $updateResult = $DB->query("UPDATE qingka_wangke_gongdan SET `content`='$newContent', `state`='已完成' WHERE gid='$gid'");
        
        if ($updateResult) {
            // 推送通知给用户
            $userToken = $DB->get_row("SELECT `pushPlusToken` FROM `qingka_wangke_user` WHERE `uid`='{$ticket['uid']}'");
            $message = "您的工单已完成\n时间: {$current_time}\n处理结果: {$answer}";
            sendPushNotification($userToken['pushPlusToken'], '工单完成通知', $message);
            
            exit(json_encode(['code' => 1, 'msg' => '工单已完成']));
        } else {
            exit(json_encode(['code' => -1, 'msg' => '操作失败']));
        }
        break;
        
    // 用户追加提问
    case 'toanswer':
        $gid = trim(strip_tags(daddslashes($_POST['gid'])));
        $toanswer = trim(strip_tags(daddslashes($_POST['toanswer'])));
        
        $ticket = $DB->get_row("SELECT * FROM qingka_wangke_gongdan WHERE gid='{$gid}'");
        if (!$ticket) {
            exit(json_encode(['code' => -1, 'msg' => '工单不存在']));
        }
        if ((string)$userrow['uid'] !== '1' && (string)$ticket['uid'] !== (string)$userrow['uid']) {
            exit(json_encode(['code' => -1, 'msg' => '无权操作此工单']));
        }
        
        if (strlen($toanswer) > 100) {
            exit(json_encode(['code' => -1, 'msg' => '问题不能超过100个字']));
        }
        
        if ($ticket['state'] == '已完成') {
            exit(json_encode(['code' => -1, 'msg' => '工单已完成，无法追加提问']));
        }
        
        $newContent = $ticket['content'] . "\n\n" . $current_time . " 用户追问: " . $toanswer;
        $updateResult = $DB->query("UPDATE qingka_wangke_gongdan SET `content`='$newContent', `state`='待回复' WHERE gid='$gid'");
        
        if ($updateResult) {
            // 推送通知给管理员
            $message = "用户{$ticket['uid']}追加了工单提问\n工单ID: {$gid}\n时间: {$current_time}\n内容: {$toanswer}";
            sendPushNotification($supertoken['pushPlusToken'], '工单追问通知', $message);
            
            exit(json_encode(['code' => 1, 'msg' => '追问已提交']));
        } else {
            exit(json_encode(['code' => -1, 'msg' => '提交失败']));
        }
        break;
    
    // 测试推送
    case 'testPushToken':
        $token = trim(strip_tags(daddslashes($_GET['token'])));
        if (empty($token)) {
            exit(json_encode(['code' => -1, 'msg' => 'Token不能为空']));
        }
        
        $message = "这是一条测试推送消息\n时间: {$current_time}\n如果收到此消息，说明推送配置正确";
        $result = sendPushNotification($token, '推送测试', $message);
        
        if ($result) {
            exit(json_encode(['code' => 1, 'msg' => '测试推送已发送']));
        } else {
            exit(json_encode(['code' => -1, 'msg' => '测试推送失败']));
        }
        break;
        
    default:
        exit(json_encode(['code' => -1, 'msg' => '无效操作']));
}
?>
