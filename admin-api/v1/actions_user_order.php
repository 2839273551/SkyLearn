<?php

if (!defined('IN_CRONLITE')) {
    exit();
}

if ($action === 'order-nocheck-options') {
    api_require_login(isset($islogin) ? $islogin : 0);
    $userRate = floatval($userrow['addprice']);

    $res = $DB->query("SELECT c.cid, c.name, c.price, c.yunsuan, c.content, c.fenlei, f.name AS fenlei_name FROM qingka_wangke_class c LEFT JOIN qingka_wangke_fenlei f ON c.fenlei=f.id WHERE c.status=1 ORDER BY CAST(c.sort AS UNSIGNED) ASC, c.cid DESC");
    $list = array();
    while ($row = $DB->fetch($res)) {
        $basePrice = floatval($row['price']);
        if ($row['yunsuan'] === '+') {
            $calcPrice = round($basePrice + $userRate, 2);
        } else {
            $calcPrice = round($basePrice * $userRate, 2);
        }

        $list[] = array(
            'cid' => (string)$row['cid'],
            'name' => (string)$row['name'],
            'price' => $calcPrice,
            'base_price' => $basePrice,
            'fenlei_id' => (string)$row['fenlei'],
            'fenlei_name' => (string)($row['fenlei_name'] ? $row['fenlei_name'] : '默认分类'),
            'content' => (string)$row['content']
        );
    }

    api_respond(0, 'ok', array(
        'classes' => $list,
        'user_money' => floatval($userrow['money']),
        'user_rate' => $userRate
    ));
}

if ($action === 'order-submit-nocheck') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();

    $input = api_read_input();
    $cid = isset($input['cid']) ? intval($input['cid']) : 0;
    $rawText = isset($input['content']) ? trim((string)$input['content']) : '';

    if ($cid <= 0) {
        api_respond(422, '请选择目标网课平台');
    }
    if ($rawText === '') {
        api_respond(422, '请填写提交信息');
    }

    $rs = $DB->get_row("SELECT * FROM qingka_wangke_class WHERE cid='$cid' AND status=1 LIMIT 1");
    if (!$rs) {
        api_respond(404, '该网课平台不存在或已下架');
    }

    $userRate = floatval($userrow['addprice']);
    if ($rs['yunsuan'] === '+') {
        $unitPrice = round(floatval($rs['price']) + $userRate, 2);
    } else {
        $unitPrice = round(floatval($rs['price']) * $userRate, 2);
    }

    $lines = preg_split('/[\r\n]+/', $rawText);
    $ordersToCreate = array();

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;

        $line = preg_replace('/\s+/', ' ', $line);
        $parts = explode(' ', $line);
        if (count($parts) < 2) continue;

        $school = '自动识别';
        $user = '';
        $pass = '';
        $kcnames = array();

        if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $parts[0])) {
            $school = $parts[0];
            $user = isset($parts[1]) ? $parts[1] : '';
            $pass = isset($parts[2]) ? $parts[2] : '';
            for ($k = 3; $k < count($parts); $k++) {
                if ($parts[$k] !== '') $kcnames[] = $parts[$k];
            }
        } else {
            $user = $parts[0];
            $pass = isset($parts[1]) ? $parts[1] : '';
            for ($k = 2; $k < count($parts); $k++) {
                if ($parts[$k] !== '') $kcnames[] = $parts[$k];
            }
        }

        if (empty($user) || empty($pass)) continue;
        if (empty($kcnames)) {
            $kcnames[] = '全部课程';
        }

        foreach ($kcnames as $kc) {
            $ordersToCreate[] = array(
                'school' => $school,
                'user' => $user,
                'pass' => $pass,
                'kcname' => $kc
            );
        }
    }

    $orderCount = count($ordersToCreate);
    if ($orderCount === 0) {
        api_respond(422, '未解析出有效账号信息，请检查输入格式（例如：账号 密码 课程名）');
    }

    $totalMoney = round($orderCount * $unitPrice, 2);
    $userMoney = floatval($userrow['money']);
    if ($userMoney < $totalMoney) {
        api_respond(400, "余额不足！本次交单需 {$totalMoney} 元，当前余额 {$userMoney} 元");
    }

    $successCount = 0;
    $nowTime = date('Y-m-d H:i:s');
    $clientIp = isset($clientip) ? $clientip : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1');
    $dockstatus = intval($rs['docking']) === 0 ? '99' : '0';

    foreach ($ordersToCreate as $item) {
        $schoolSafe = daddslashes($item['school']);
        $userSafe = daddslashes($item['user']);
        $passSafe = daddslashes($item['pass']);
        $kcnameSafe = daddslashes($item['kcname']);
        $ptnameSafe = daddslashes($rs['name']);
        $kcidSafe = daddslashes($rs['kcid']);
        $nounSafe = daddslashes($rs['noun']);
        $hidSafe = daddslashes($rs['docking']);
        $currentUid = intval($userrow['uid']);
        $currentUserName = daddslashes($userrow['name'] ? $userrow['name'] : $userrow['user']);

        $exist = $DB->get_row("SELECT oid FROM qingka_wangke_order WHERE ptname='$ptnameSafe' AND user='$userSafe' AND pass='$passSafe' AND kcname='$kcnameSafe' LIMIT 1");
        $statusToSet = $exist ? '3' : $dockstatus;

        $insertSql = "INSERT INTO `qingka_wangke_order` 
            (`uid`, `cid`, `hid`, `yid`, `ptname`, `school`, `name`, `user`, `pass`, `phone`, `kcid`, `kcname`, `courseStartTime`, `courseEndTime`, `examStartTime`, `examEndTime`, `chapterCount`, `unfinishedChapterCount`, `cookie`, `fees`, `noun`, `miaoshua`, `addtime`, `ip`, `dockstatus`, `loginstatus`, `status`, `process`, `bsnum`, `remarks`, `dakatime`, `leixing`, `detailed`, `dlip`, `docknum`, `finalupdate`, `region`) 
            VALUES 
            ('$currentUid', '{$rs['cid']}', '$hidSafe', '0', '$ptnameSafe', '$schoolSafe', '$currentUserName', '$userSafe', '$passSafe', '', '$kcidSafe', '$kcnameSafe', '', '', '', '', '0', '0', '', '$unitPrice', '$nounSafe', '0', '$nowTime', '$clientIp', '$statusToSet', '', '待处理', '待处理', '0', '', '', '0', '', '', 0, '$nowTime', '')";

        if ($DB->query($insertSql)) {
            $successCount++;
            $DB->query("UPDATE `qingka_wangke_user` SET `money`=`money`-'$unitPrice' WHERE `uid`='$currentUid' LIMIT 1");
            if (function_exists('wlog')) {
                wlog($currentUid, "批量提交", "无查交单: {$rs['name']} [{$item['user']}] 课程: {$item['kcname']}", -$unitPrice);
            }
        }
    }

    $actualDeducted = round($successCount * $unitPrice, 2);
    api_respond(0, "成功提交 {$successCount} 门课程，共计扣费 {$actualDeducted} 元", array(
        'success_count' => $successCount,
        'total_count' => $orderCount,
        'deducted_money' => $actualDeducted,
        'remain_money' => round($userMoney - $actualDeducted, 2)
    ));
}

if ($action === 'user-grade-options') {
    api_require_login(isset($islogin) ? $islogin : 0);
    $currentUid = intval($userrow['uid']);
    $userRate = floatval($userrow['addprice']);

    $grades = array();
    $res = $DB->query("SELECT * FROM `qingka_wangke_dengji` ORDER BY CAST(`rate` AS DECIMAL(10,2)) ASC");
    while ($r = $DB->fetch($res)) {
        $rate = floatval($r['rate']);
        $disabled = ($currentUid !== 1 && $rate < $userRate);
        $grades[] = array(
            'id' => (string)$r['id'],
            'name' => (string)$r['name'],
            'rate' => $rate,
            'money' => floatval($r['money']),
            'addkf' => intval($r['addkf']),
            'disabled' => $disabled
        );
    }

    $openReg = isset($conf['user_htkh']) ? strval($conf['user_htkh']) : '1';
    $ktMoney = isset($conf['user_ktmoney']) ? floatval($conf['user_ktmoney']) : 0.0;

    api_respond(0, 'ok', array(
        'grades' => $grades,
        'user_htkh' => $openReg,
        'user_ktmoney' => ($currentUid === 1) ? 0.0 : $ktMoney,
        'current_user_rate' => $userRate,
        'is_admin' => $currentUid === 1
    ));
}

if ($action === 'user-create') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();

    $currentUid = intval($userrow['uid']);
    $openReg = isset($conf['user_htkh']) ? strval($conf['user_htkh']) : '1';
    if ($openReg === '0' && $currentUid !== 1) {
        api_respond(403, '当前系统已暂停后台开户，请联系管理员');
    }

    $input = api_read_input();
    $user = isset($input['user']) ? trim(strip_tags($input['user'])) : '';
    $pass = isset($input['pass']) ? trim($input['pass']) : '';
    $name = isset($input['name']) ? trim(strip_tags($input['name'])) : '';
    $gradeId = isset($input['grade_id']) ? intval($input['grade_id']) : 0;

    if ($user === '' || $pass === '' || $name === '' || $gradeId <= 0) {
        api_respond(422, '所有表单项目均不能为空');
    }

    if (!preg_match('/^[1-9]\d{4,14}$/', $user)) {
        api_respond(422, '代理账号必须为有效的 QQ 号码（5~15位数字）');
    }

    $userSafe = daddslashes($user);
    $nameSafe = daddslashes($name);
    if ($DB->get_row("SELECT uid FROM `qingka_wangke_user` WHERE `user`='$userSafe' LIMIT 1")) {
        api_respond(400, '该账号已被注册使用，请更换');
    }
    if ($DB->get_row("SELECT uid FROM `qingka_wangke_user` WHERE `name`='$nameSafe' LIMIT 1")) {
        api_respond(400, '该代理昵称已被占用，请更换');
    }

    $gradeRow = $DB->get_row("SELECT * FROM `qingka_wangke_dengji` WHERE `id`='$gradeId' LIMIT 1");
    if (!$gradeRow) {
        api_respond(404, '所选代理等级不存在');
    }

    $targetRate = floatval($gradeRow['rate']);
    $userRate = floatval($userrow['addprice']);
    if ($currentUid !== 1 && $targetRate < $userRate) {
        api_respond(400, "下级成本费率不能低于您自身的费率 ({$userRate}×)");
    }

    $ktFee = ($currentUid === 1) ? 0.0 : (isset($conf['user_ktmoney']) ? floatval($conf['user_ktmoney']) : 0.0);
    $firstRecharge = 0.0;
    if (intval($gradeRow['addkf']) === 1) {
        $firstRecharge = floatval($gradeRow['money']);
    }

    $deductRecharge = 0.0;
    if ($firstRecharge > 0 && $targetRate > 0) {
        if ($currentUid === 1) {
            $deductRecharge = 0.0;
        } else {
            $deductRecharge = round($firstRecharge * ($userRate / $targetRate), 2);
        }
    }
    $totalNeed = round($ktFee + $deductRecharge, 2);

    $currentMoney = floatval($userrow['money']);
    if ($currentUid !== 1 && $currentMoney < $totalNeed) {
        api_respond(400, "可用余额不足！开户需手续费 {$ktFee} 元" . ($deductRecharge > 0 ? " 及下级首充扣款 {$deductRecharge} 元" : '') . "，当前余额 {$currentMoney} 元");
    }

    $passSafe = daddslashes($pass);
    $now = date('Y-m-d H:i:s');
    $yqm = substr(md5($user . time() . mt_rand(100, 999)), 0, 8);
    $gradeName = isset($gradeRow['name']) ? trim($gradeRow['name']) : '';
    $gradeNameSafe = daddslashes($gradeName);
    $clientIp = function_exists('real_ip') ? real_ip() : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1');
    $clientIpSafe = daddslashes($clientIp);

    // 严密适配 qingka_wangke_user 表结构：移除不存在的 addpriceid 字段，补齐严格模式下所有 NOT NULL 字段的安全默认值
    $sql = "INSERT INTO `qingka_wangke_user` 
        (`uuid`, `user`, `pass`, `name`, `qq_openid`, `nickname`, `faceimg`, `money`, `zcz`, `addprice`, `key`, `yqm`, `yqprice`, `notice`, `addtime`, `endtime`, `ip`, `grade`, `active`, `vip`, `last_sign_in_date`, `used_batches`, `daily_invites`, `freeadd`) 
        VALUES 
        ('$currentUid', '$userSafe', '$passSafe', '$nameSafe', '', '$nameSafe', '', '$firstRecharge', '$firstRecharge', '$targetRate', '0', '$yqm', '0.2', '', '$now', '', '$clientIpSafe', '$gradeNameSafe', '1', 0, '', '', 0, 0)";

    if (!$DB->query($sql)) {
        $dbErr = method_exists($DB, 'error') ? $DB->error() : '';
        api_respond(500, '创建代理失败' . ($dbErr ? ": {$dbErr}" : '，请稍后重试'));
    }

    $newUid = 0;
    if (method_exists($DB, 'insert_id')) {
        $newUid = intval($DB->insert_id());
    }
    if ($newUid <= 0 && isset($DB->link)) {
        $newUid = intval(mysqli_insert_id($DB->link));
    }
    if ($newUid <= 0) {
        $createdUser = $DB->get_row("SELECT `uid` FROM `qingka_wangke_user` WHERE `user`='$userSafe' ORDER BY `uid` DESC LIMIT 1");
        $newUid = $createdUser ? intval($createdUser['uid']) : 0;
    }

    if ($totalNeed > 0) {
        $DB->query("UPDATE `qingka_wangke_user` SET `money`=`money`-'$totalNeed' WHERE `uid`='$currentUid' LIMIT 1");
    }

    if (function_exists('wlog')) {
        if ($ktFee > 0) {
            wlog($currentUid, "添加商户", "开通下级代理 {$name} (UID: {$newUid})，扣除开户费 {$ktFee} 元", -$ktFee);
        }
        if ($firstRecharge > 0) {
            if ($deductRecharge > 0) {
                wlog($currentUid, "代理充值", "为新下级 {$name} (UID: {$newUid}) 首充 {$firstRecharge} 元，折算扣除 {$deductRecharge} 元", -$deductRecharge);
            }
            if ($newUid > 0) {
                wlog($newUid, "上级充值", "上级开户赠送初始余额 {$firstRecharge} 元", +$firstRecharge);
            }
        }
    }

    api_respond(0, "代理账号开通成功！UID: {$newUid}", array(
        'uid' => $newUid,
        'user' => $user,
        'name' => $name,
        'rate' => $targetRate,
        'first_recharge' => $firstRecharge,
        'deducted' => $totalNeed
    ));
}

if ($action === 'user-migrate') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();

    $currentUid = intval($userrow['uid']);
    $openMigrate = isset($conf['sjqykg']) ? strval($conf['sjqykg']) : '0';
    if ($openMigrate !== '1') {
        api_respond(403, '当前系统未开启上级迁移功能');
    }

    $input = api_read_input();
    $targetUid = isset($input['target_uid']) ? intval($input['target_uid']) : 0;
    $yqm = isset($input['yqm']) ? trim(strip_tags($input['yqm'])) : '';

    if ($targetUid <= 0 || empty($yqm)) {
        api_respond(422, '目标上级 UID 与邀请码不能为空');
    }
    if ($targetUid === $currentUid) {
        api_respond(422, '不能将上级迁移为自己');
    }

    $targetUser = $DB->get_row("SELECT uid, user, name, yqm, addprice FROM `qingka_wangke_user` WHERE uid='$targetUid' LIMIT 1");
    if (!$targetUser) {
        api_respond(404, '目标上级用户不存在');
    }
    if ($targetUser['yqm'] !== $yqm) {
        api_respond(400, '目标上级专属邀请码校验不匹配');
    }

    // 检查自己当前是否已经是该上级
    if (intval($userrow['uuid']) === $targetUid) {
        api_respond(400, '您当前已经在该上级团队名下，无需重复迁移');
    }

    // 执行迁移
    $DB->query("UPDATE `qingka_wangke_user` SET uuid='$targetUid' WHERE uid='$currentUid' LIMIT 1");
    if (function_exists('wlog')) {
        wlog($currentUid, "上级迁移", "成功将团队上级迁移至 UID: {$targetUid} ({$targetUser['user']})", 0);
    }

    api_respond(0, "团队上级已成功迁移至: [UID {$targetUid}] {$targetUser['name']}");
}

if ($action === 'user-batch-rate') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();

    $currentUid = intval($userrow['uid']);
    $input = api_read_input();
    $targetUids = isset($input['uids']) && is_array($input['uids']) ? array_map('intval', $input['uids']) : array();
    $rate = isset($input['rate']) ? floatval($input['rate']) : 0.0;

    if ($rate < 0.1 || $rate > 5.0) {
        api_respond(422, '费率系数必须在 0.10 ~ 5.00 之间');
    }

    $myRate = floatval($userrow['addprice']);
    if ($currentUid !== 1 && $rate < $myRate) {
        api_respond(422, "下级费率不能低于您自身的费率 ({$myRate}×)");
    }

    $uidScope = $currentUid === 1 ? '' : " AND uuid='$currentUid'";
    $updated = 0;

    if (!empty($targetUids)) {
        $uidsStr = implode(',', $targetUids);
        $res = $DB->query("UPDATE `qingka_wangke_user` SET addprice='$rate' WHERE uid IN ($uidsStr) $uidScope");
        $updated = count($targetUids);
    } else {
        // 全量下级改价
        if ($currentUid !== 1) {
            $res = $DB->query("UPDATE `qingka_wangke_user` SET addprice='$rate' WHERE uuid='$currentUid'");
        } else {
            $res = $DB->query("UPDATE `qingka_wangke_user` SET addprice='$rate' WHERE uid > 1");
        }
    }

    api_respond(0, "批量费率调整已生效！新费率: {$rate}×");
}

if ($action === 'my-referrals') {
    api_require_login(isset($islogin) ? $islogin : 0);
    $currentUid = intval($userrow['uid']);

    $res = $DB->query("SELECT uid, user, name, addprice, money, addtime, active FROM `qingka_wangke_user` WHERE uuid='$currentUid' ORDER BY uid DESC LIMIT 100");
    $list = array();
    while ($r = $DB->fetch($res)) {
        $orderCount = $DB->count("SELECT COUNT(*) FROM `qingka_wangke_order` WHERE uid='{$r['uid']}'");
        $list[] = array(
            'uid' => (string)$r['uid'],
            'user' => (string)$r['user'],
            'name' => (string)$r['name'],
            'rate' => floatval($r['addprice']),
            'money' => floatval($r['money']),
            'addtime' => (string)$r['addtime'],
            'order_count' => intval($orderCount)
        );
    }

    $myInfo = $DB->get_row("SELECT yqm, addprice FROM `qingka_wangke_user` WHERE uid='$currentUid' LIMIT 1");
    api_respond(0, 'ok', array(
        'list' => $list,
        'my_yqm' => isset($myInfo['yqm']) ? (string)$myInfo['yqm'] : '',
        'total_referrals' => count($list),
        'site_url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'sk.yunxnet.cn')
    ));
}
