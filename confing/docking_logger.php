<?php

if (!defined('IN_CRONLITE')) {
    exit();
}

/**
 * 对敏感参数做脱敏掩码处理（密码、密钥、Token等）
 */
function docking_mask_data($data) {
    if (is_array($data)) {
        $clean = array();
        foreach ($data as $k => $v) {
            $lowerKey = strtolower((string)$k);
            if (in_array($lowerKey, array('pass', 'password', 'key', 'token', 'secret', 'cookie', 'pay_pwd', 'apikey'), true)) {
                if (is_string($v) && strlen($v) > 4) {
                    $clean[$k] = substr($v, 0, 2) . '******' . substr($v, -2);
                } else {
                    $clean[$k] = '******';
                }
            } elseif (is_array($v)) {
                $clean[$k] = docking_mask_data($v);
            } else {
                $clean[$k] = $v;
            }
        }
        return $clean;
    }
    return $data;
}

/**
 * 记录对接流向流水（入站/出站）
 */
function record_docking_log(array $info) {
    global $DB, $clientip;
    if (!isset($DB) || !is_object($DB)) {
        return false;
    }

    try {
        $direction = isset($info['direction']) && $info['direction'] === 'out' ? 'out' : 'in';
        $action = isset($info['action']) ? trim(strip_tags((string)$info['action'])) : '未知接口';
        $caller = isset($info['caller']) ? trim(strip_tags((string)$info['caller'])) : '';
        $uid = isset($info['uid']) ? intval($info['uid']) : 0;
        $target = isset($info['target']) ? trim(strip_tags((string)$info['target'])) : '';
        $method = isset($info['method']) ? strtoupper(trim(strip_tags((string)$info['method']))) : 'POST';
        
        $ip = isset($info['ip']) && !empty($info['ip']) ? $info['ip'] : (isset($clientip) ? $clientip : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'));
        if (empty($caller)) {
            $caller = $uid > 0 ? "UID: {$uid}" : "匿名客户端";
        }

        // 脱敏处理
        $maskedParams = docking_mask_data($info['params'] ?? array());
        $paramsStr = is_string($maskedParams) ? $maskedParams : json_encode($maskedParams, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (mb_strlen($paramsStr, 'UTF-8') > 8000) {
            $paramsStr = mb_substr($paramsStr, 0, 8000, 'UTF-8') . '...[截断]';
        }

        $maskedResp = docking_mask_data($info['response'] ?? array());
        $respStr = is_string($maskedResp) ? $maskedResp : json_encode($maskedResp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (mb_strlen($respStr, 'UTF-8') > 8000) {
            $respStr = mb_substr($respStr, 0, 8000, 'UTF-8') . '...[截断]';
        }

        $status = isset($info['status']) && $info['status'] ? 1 : 0;
        $costMs = isset($info['cost_ms']) ? max(0, intval($info['cost_ms'])) : 0;
        $bytesIn = isset($info['bytes_in']) ? max(0, intval($info['bytes_in'])) : 0;
        $bytesOut = isset($info['bytes_out']) ? max(0, intval($info['bytes_out'])) : 0;
        $trafficTotal = $bytesIn + $bytesOut;
        $now = date('Y-m-d H:i:s');

        $directionSafe = daddslashes($direction);
        $actionSafe = daddslashes($action);
        $callerSafe = daddslashes($caller);
        $targetSafe = daddslashes($target);
        $methodSafe = daddslashes($method);
        $ipSafe = daddslashes($ip);
        $paramsSafe = daddslashes($paramsStr);
        $respSafe = daddslashes($respStr);

        $sql = "INSERT INTO `qingka_wangke_docking_log` 
                (`direction`, `action`, `caller`, `uid`, `target`, `method`, `ip`, `params`, `response`, `status`, `cost_ms`, `bytes_in`, `bytes_out`, `traffic_total`, `created_at`) 
                VALUES 
                ('$directionSafe', '$actionSafe', '$callerSafe', '$uid', '$targetSafe', '$methodSafe', '$ipSafe', '$paramsSafe', '$respSafe', '$status', '$costMs', '$bytesIn', '$bytesOut', '$trafficTotal', '$now')";

        return (bool)$DB->query($sql);
    } catch (Exception $e) {
        return false;
    }
}
