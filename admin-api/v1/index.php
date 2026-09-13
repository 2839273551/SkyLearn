<?php

$isHttpsRequest = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_secure', $isHttpsRequest ? '1' : '0');

require_once __DIR__ . '/../../confing/common.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');

function api_respond($code, $msg, $data = null, $status = 200)
{
    http_response_code($status);
    echo json_encode(array(
        'code' => $code,
        'msg' => $msg,
        'data' => $data
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_read_input()
{
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? strtolower($_SERVER['CONTENT_TYPE']) : '';
    if (strpos($contentType, 'application/json') !== false) {
        $decoded = json_decode(file_get_contents('php://input'), true);
        return is_array($decoded) ? $decoded : array();
    }

    return $_POST;
}

function api_request_host()
{
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    $parsed = parse_url('http://' . $host);
    return isset($parsed['host']) ? strtolower($parsed['host']) : '';
}

function api_is_same_origin()
{
    $source = '';
    if (!empty($_SERVER['HTTP_ORIGIN'])) {
        $source = $_SERVER['HTTP_ORIGIN'];
    } elseif (!empty($_SERVER['HTTP_REFERER'])) {
        $source = $_SERVER['HTTP_REFERER'];
    }

    if ($source === '') {
        return true;
    }

    $parsed = parse_url($source);
    $sourceHost = isset($parsed['host']) ? strtolower($parsed['host']) : '';
    return $sourceHost !== '' && hash_equals(api_request_host(), $sourceHost);
}

function api_require_post()
{
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        api_respond(405, '请求方法不受支持', null, 405);
    }

    if (!api_is_same_origin()) {
        api_respond(403, '请求来源校验失败', null, 403);
    }
}

function api_set_auth_cookie($token, $expires)
{
    $secure = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off';

    if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
        setcookie('admin_token', $token, array(
            'expires' => $expires,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ));
        return;
    }

    setcookie('admin_token', $token, $expires, '/; SameSite=Lax', '', $secure, true);
}

function api_csrf_token()
{
    if (empty($_SESSION['admin_api_csrf'])) {
        $_SESSION['admin_api_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['admin_api_csrf'];
}

function api_random_string($length = 12, $numeric = false)
{
    if ($numeric) {
        $chars = '0123456789';
    } else {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    }
    $max = strlen($chars) - 1;
    $res = '';
    for ($i = 0; $i < $length; $i++) {
        $res .= $chars[mt_rand(0, $max)];
    }
    return $res;
}

function api_user_avatar($user, $conf = array())
{
    $digits = preg_replace('/\D/', '', (string) $user);
    if (strlen($digits) >= 5 && strlen($digits) <= 11) {
        return 'https://q1.qlogo.cn/g?b=qq&nk=' . $digits . '&s=640';
    }

    if (!empty($conf['zzqq'])) {
        $zzqqDigits = preg_replace('/\D/', '', (string) $conf['zzqq']);
        if (strlen($zzqqDigits) >= 5 && strlen($zzqqDigits) <= 11) {
            return 'https://q1.qlogo.cn/g?b=qq&nk=' . $zzqqDigits . '&s=640';
        }
    }

    return 'https://q1.qlogo.cn/g?b=qq&nk=10001&s=640';
}

function api_send_push($token, $title, $content)
{
    if (empty($token)) return false;
    $apiUrl = 'https://push.showdoc.com.cn/server/api/push/' . urlencode($token)
            . '?title=' . urlencode(mb_substr($title, 0, 100, 'UTF-8'))
            . '&content=' . urlencode(mb_substr($content, 0, 500, 'UTF-8'));
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true
    ));
    $res = curl_exec($ch);
    curl_close($ch);
    return $res !== false;
}

function api_user_data($userrow, $conf)
{
    $isSuper = intval($userrow['uid']) === 1;
    $capabilities = array('dashboard.read', 'orders.read', 'orders.create', 'profile.read');

    if ($isSuper) {
        $capabilities = array_merge($capabilities, array(
            'courses.manage',
            'users.manage',
            'supplies.manage',
            'finance.manage',
            'system.manage'
        ));
    }

    $today = date('Y-m-d');
    $hasSignedIn = isset($userrow['last_sign_in_date']) && $userrow['last_sign_in_date'] === $today;

    return array(
        'userId' => (string) $userrow['uid'],
        'userName' => (string) $userrow['user'],
        'displayName' => isset($userrow['name']) ? (string) $userrow['name'] : (string) $userrow['user'],
        'avatar' => api_user_avatar($userrow['user'], $conf),
        'siteName' => isset($conf['sitename']) ? (string) $conf['sitename'] : '网课管理中心',
        'balance' => isset($userrow['money']) ? number_format((float) $userrow['money'], 2, '.', '') : '0.00',
        'freeAdd' => isset($userrow['freeadd']) ? intval($userrow['freeadd']) : 0,
        'canMigrateSuperior' => isset($conf['sjqykg']) && intval($conf['sjqykg']) === 1,
        'sykg' => isset($conf['sykg']) && intval($conf['sykg']) === 1,
        'ddggkg' => isset($conf['ddggkg']) && intval($conf['ddggkg']) === 1,
        'ddgg' => isset($conf['ddgg']) ? (string) $conf['ddgg'] : '',
        'czph' => isset($conf['czph']) && intval($conf['czph']) === 1,
        'qdkg' => isset($conf['qdkg']) && intval($conf['qdkg']) === 1,
        'hasSignedIn' => $hasSignedIn,
        'csrfToken' => api_csrf_token(),
        'roles' => array($isSuper ? 'R_SUPER' : 'R_AGENT'),
        'buttons' => array(),
        'capabilities' => $capabilities
    );
}

function api_require_login($islogin)
{
    if (intval($islogin) !== 1) {
        api_respond(401, '登录状态已失效，请重新登录', null, 401);
    }
}

function api_require_super($userrow, $islogin)
{
    api_require_login(isset($islogin) ? $islogin : 0);
    if (intval($userrow['uid']) !== 1) {
        api_respond(403, '权限不足，仅管理员可访问此接口', null, 403);
    }
}

function api_require_csrf()
{
    $provided = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : '';
    $expected = isset($_SESSION['admin_api_csrf']) ? $_SESSION['admin_api_csrf'] : '';

    if ($provided === '' || $expected === '' || !hash_equals($expected, $provided)) {
        api_respond(419, '页面验证已失效，请刷新后重试', null, 419);
    }
}

function order_free_cids($conf)
{
    $value = isset($conf['mfxd']) ? trim($conf['mfxd']) : '';
    if ($value === '') {
        return array();
    }

    return array_filter(array_map('trim', explode(',', $value)), 'strlen');
}

function order_catalog_price($product, $userrow, $DB, $conf, &$displayName)
{
    $displayName = (string) $product['name'];
    $validCids = order_free_cids($conf);

    $freeOrderEnabled = isset($conf['mfxdkg']) && intval($conf['mfxdkg']) === 1;
    if ($freeOrderEnabled && intval($userrow['freeadd']) > 0 && in_array((string) $product['cid'], $validCids, true)) {
        $displayName = '免费*' . $displayName;
        return 0.0;
    }

    $rate = (float) $userrow['addprice'];
    if (intval($userrow['vip']) === 1) {
        if ($product['vipyunsuan'] === '+') {
            $price = round((float) $product['vipprice'] + $rate, 2);
        } else {
            $minimum = $product['vipyunsuan'] === '*' ? 0.15 : 0.1;
            $price = round((float) $product['vipprice'] * max($rate, $minimum), 2);
        }
    } else {
        if ($product['yunsuan'] === '+') {
            $price = round((float) $product['price'] + $rate, 2);
        } else {
            $price = round((float) $product['price'] * max($rate, 0.15), 2);
        }
    }

    $originalPrice = $price;
    $special = $DB->get_row(
        "SELECT mode,price FROM qingka_wangke_mijia WHERE uid='" . intval($userrow['uid'])
        . "' AND cid='" . intval($product['cid']) . "' LIMIT 1"
    );

    if ($special) {
        if (intval($special['mode']) === 0) {
            $price = round($price - (float) $special['price'], 2);
        } elseif (intval($special['mode']) === 1) {
            $basePrice = intval($userrow['vip']) === 1 ? (float) $product['vipprice'] : (float) $product['price'];
            $price = round(($basePrice - (float) $special['price']) * $rate, 2);
        } elseif (intval($special['mode']) === 2) {
            $price = (float) $special['price'];
        }

        $displayName = ($price < $originalPrice ? '密*' : '内*') . $displayName;
        if ($price >= $originalPrice) {
            $price = $originalPrice;
        }
    } elseif (intval($userrow['vip']) === 1) {
        $displayName = '内*' . $displayName;
    }

    return max(round($price, 2), 0);
}

function order_submit_price($product, $userrow, $DB)
{
    $rate = (float) $userrow['addprice'];
    if (intval($userrow['vip']) === 1) {
        $price = round((float) $product['vipprice'] * max($rate, 0.15), 2);
    } elseif ($product['yunsuan'] === '+') {
        $price = round((float) $product['price'] + $rate, 2);
    } else {
        $price = round((float) $product['price'] * $rate, 2);
    }

    $special = $DB->get_row(
        "SELECT mode,price FROM qingka_wangke_mijia WHERE uid='" . intval($userrow['uid'])
        . "' AND cid='" . intval($product['cid']) . "' LIMIT 1"
    );

    if ($special) {
        if (intval($special['mode']) === 0) {
            $price = max(round($price - (float) $special['price'], 2), 0);
        } elseif (intval($special['mode']) === 1) {
            $price = max(round(((float) $product['price'] - (float) $special['price']) * $rate, 2), 0);
        } elseif (intval($special['mode']) === 2) {
            $price = max((float) $special['price'], 0);
        }
    }

    return round($price, 2);
}

function order_parse_userinfo($userinfo)
{
    $normalized = merge_spaces(trim($userinfo));
    $parts = explode(' ', $normalized);
    $parts = array_values(array_filter($parts, 'strlen'));

    if (count($parts) > 2) {
        return array(
            'school' => $parts[0],
            'user' => $parts[1],
            'pass' => $parts[2],
            'normalized' => $parts[0] . ' ' . $parts[1] . ' ' . $parts[2]
        );
    }

    if (count($parts) === 2) {
        return array(
            'school' => '自动识别',
            'user' => $parts[0],
            'pass' => $parts[1],
            'normalized' => $parts[0] . ' ' . $parts[1]
        );
    }

    return null;
}

function order_course_data($course)
{
    return array(
        'id' => isset($course['id']) ? (string) $course['id'] : (isset($course['kcid']) ? (string) $course['kcid'] : ''),
        'name' => isset($course['name']) ? (string) $course['name'] : (isset($course['kcname']) ? (string) $course['kcname'] : ''),
        'teacher' => isset($course['teacher']) ? (string) $course['teacher'] : '',
        'state' => isset($course['state']) ? (string) $course['state'] : '',
        'kcjs' => isset($course['kcjs']) ? (string) $course['kcjs'] : (isset($course['courseEndTime']) ? (string) $course['courseEndTime'] : '')
    );
}

function system_setting_keys()
{
    return array(
        'sitename', 'keywords', 'description', 'logo', 'sykg', 'ddggkg', 'czph', 'qdkg',
        'notice', 'ddgg', 'tcgonggao', 'zsgonggao', 'sjqykg', 'user_yqzc', 'user_htkh',
        'user_ktmoney', 'zxczkg', 'zdpay', 'is_qqpay', 'is_wxpay', 'is_alipay', 'epay_api',
        'epay_pid', 'epay_key', 'yqjl', 'yqsq', 'yqsx', 'mfxdkg', 'mfxd', 'flkg', 'fllx', 'zddy',
        'zdxd', 'ckkg', 'xdkg', 'zzqq', 'zzvx'
    );
}

function system_settings_data($conf)
{
    $settings = array();
    $defaults = array('mfxdkg' => '0');
    foreach (system_setting_keys() as $key) {
        if ($key === 'epay_key') {
            $settings[$key] = '';
            continue;
        }
        $settings[$key] = isset($conf[$key]) ? (string) $conf[$key] : (isset($defaults[$key]) ? $defaults[$key] : '');
    }

    return array(
        'settings' => $settings,
        'hasEpayKey' => !empty($conf['epay_key'])
    );
}

$action = isset($_GET['action']) ? preg_replace('/[^a-z-]/', '', strtolower($_GET['action'])) : '';

if ($action === 'login') {
    api_require_post();
    $input = api_read_input();
    $username = isset($input['userName']) ? trim(strip_tags($input['userName'])) : '';
    $password = isset($input['password']) ? trim($input['password']) : '';
    $adminVerification = isset($input['verification']) ? trim($input['verification']) : '';

    if ($username === '' || $password === '') {
        api_respond(422, '账号和密码不能为空');
    }

    $safeUsername = daddslashes($username);
    $row = $DB->get_row("SELECT uid,user,pass,active FROM qingka_wangke_user WHERE user='" . $safeUsername . "' LIMIT 1");

    if (!$row || !hash_equals((string) $row['pass'], (string) $password)) {
        api_respond(422, '账号或密码不正确');
    }

    if (isset($row['active']) && intval($row['active']) === 0) {
        api_respond(403, '账号已被禁用');
    }

    if (intval($row['uid']) === 1) {
        if ($adminVerification === '') {
            api_respond(1002, '检测到管理员账号登录，请输入二次验证码', array('needVerification' => true));
        }
        if (!hash_equals((string) $verification, (string) $adminVerification)) {
            api_respond(1001, '管理员二次验证码不正确');
        }
    }

    session_regenerate_id(true);
    $session = md5($row['user'] . $row['pass'] . $password_hash);
    $token = authcode($row['user'] . "\t" . $session, 'ENCODE', SYS_KEY);
    api_set_auth_cookie($token, time() + 216000);
    wlog($row['uid'], '登录', '登录新版管理后台', '0');

    api_respond(0, '登录成功', array(
        'token' => 'cookie-session',
        'refreshToken' => ''
    ));
}

if ($action === 'logout') {
    api_require_post();
    api_set_auth_cookie('', time() - 3600);
    unset($_SESSION['admin_api_csrf']);
    api_respond(0, '已退出登录', null);
}

if ($action === 'site-info') {
    api_respond(0, 'ok', array(
        'siteName' => isset($conf['sitename']) && trim($conf['sitename']) !== '' ? (string) $conf['sitename'] : '网课管理中心',
        'logo' => isset($conf['logo']) ? (string) $conf['logo'] : '',
        'keywords' => isset($conf['keywords']) ? (string) $conf['keywords'] : '',
        'description' => isset($conf['description']) ? (string) $conf['description'] : '',
        'sykg' => isset($conf['sykg']) && intval($conf['sykg']) === 1
    ));
}

if ($action === 'session') {
    api_require_login(isset($islogin) ? $islogin : 0);
    api_respond(0, 'ok', api_user_data($userrow, $conf));
}

if ($action === 'system-settings') {
    api_require_super($userrow, $islogin);
    api_respond(0, 'ok', system_settings_data($conf));
}

if ($action === 'system-settings-save') {
    api_require_post();
    api_require_super($userrow, $islogin);
    api_require_csrf();

    $input = api_read_input();
    $incoming = isset($input['settings']) && is_array($input['settings']) ? $input['settings'] : array();
    $allowed = array_flip(system_setting_keys());
    $switchKeys = array_flip(array(
        'sykg', 'ddggkg', 'czph', 'qdkg', 'sjqykg', 'user_yqzc', 'user_htkh', 'zxczkg',
        'is_qqpay', 'is_wxpay', 'is_alipay', 'mfxdkg', 'flkg', 'ckkg', 'xdkg'
    ));
    $numericKeys = array_flip(array('user_ktmoney', 'zdpay', 'yqjl', 'yqsq', 'yqsx', 'zddy', 'zdxd'));
    $longTextKeys = array_flip(array('notice', 'ddgg', 'tcgonggao', 'zsgonggao'));
    $changes = array();

    foreach ($incoming as $key => $value) {
        if (!isset($allowed[$key]) || !is_scalar($value)) {
            api_respond(422, '包含不允许修改的配置项');
        }

        $value = trim((string) $value);
        if ($key === 'epay_key' && $value === '') {
            continue;
        }
        if (isset($switchKeys[$key]) && $value !== '0' && $value !== '1') {
            api_respond(422, $key . ' 的开关值不正确');
        }
        if ($key === 'fllx' && !in_array($value, array('1', '2'), true)) {
            api_respond(422, '分类类型不正确');
        }
        if (isset($numericKeys[$key]) && $value !== '' && !preg_match('/^\d+(?:\.\d+)?$/', $value)) {
            api_respond(422, $key . ' 必须为非负数字');
        }
        if ($key === 'mfxd' && $value !== '' && !preg_match('/^\d+(?:,\d+)*$/', $value)) {
            api_respond(422, '免费课程 CID 请使用英文逗号分隔');
        }
        if ($key === 'epay_api' && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
            api_respond(422, '易支付 API 地址格式不正确');
        }

        $limit = isset($longTextKeys[$key]) ? 20000 : 1000;
        if (strlen($value) > $limit) {
            api_respond(422, $key . ' 内容过长');
        }
        $changes[$key] = $value;
    }

    if (count($changes) === 0) {
        api_respond(422, '没有需要保存的配置');
    }

    $DB->query('START TRANSACTION');
    foreach ($changes as $key => $value) {
        $safeValue = daddslashes($value);
        $saved = $DB->query(
            "INSERT INTO qingka_wangke_config (v,k) VALUES ('" . $key . "','" . $safeValue . "') "
            . "ON DUPLICATE KEY UPDATE k=VALUES(k)"
        );
        if (!$saved) {
            $DB->query('ROLLBACK');
            api_respond(500, '配置保存失败，请重试');
        }
        $conf[$key] = $value;
    }
    wlog($userrow['uid'], '系统设置', '新版系统设置修改：' . implode(',', array_keys($changes)), 0);
    $DB->query('COMMIT');

    api_respond(0, '修改成功', system_settings_data($conf));
}

if ($action === 'order-catalog') {
    api_require_login(isset($islogin) ? $islogin : 0);

    $categoryId = isset($_GET['categoryId']) ? max(0, intval($_GET['categoryId'])) : 0;
    $categories = array();
    $categoryRows = $DB->query(
        "SELECT id,name,sort FROM qingka_wangke_fenlei WHERE status=1 ORDER BY sort ASC,id ASC"
    );
    while ($category = $DB->fetch($categoryRows)) {
        $categories[] = array(
            'id' => (string) $category['id'],
            'name' => (string) $category['name'],
            'sort' => intval($category['sort'])
        );
    }

    $scope = $categoryId > 0 ? "fenlei='" . $categoryId . "'" : 'fenlei<>0';
    $productRows = $DB->query(
        'SELECT cid,sort,name,getnoun,noun,price,queryplat,docking,yunsuan,content,status,fenlei,ckkf,'
        . 'vipprice,nocheck,vipyunsuan FROM qingka_wangke_class WHERE status=1 AND ' . $scope
        . ' ORDER BY sort DESC,cid DESC'
    );
    $products = array();
    while ($product = $DB->fetch($productRows)) {
        $displayName = '';
        $price = order_catalog_price($product, $userrow, $DB, $conf, $displayName);
        $products[] = array(
            'id' => (string) $product['cid'],
            'categoryId' => (string) $product['fenlei'],
            'name' => $displayName,
            'price' => number_format($price, 2, '.', ''),
            'queryFee' => number_format(round((float) $product['ckkf'] * (float) $userrow['addprice'], 2), 2, '.', ''),
            'content' => trim(strip_tags((string) $product['content'])),
            'noun' => (string) $product['noun'],
            'sort' => intval($product['sort']),
            'noCheck' => intval($product['nocheck']) === 1
        );
    }

    usort($products, function ($left, $right) {
        if ($left['sort'] === $right['sort']) {
            return intval($right['id']) - intval($left['id']);
        }
        return $left['sort'] - $right['sort'];
    });

    api_respond(0, 'ok', array(
        'categories' => $categories,
        'products' => $products,
        'balance' => number_format((float) $userrow['money'], 2, '.', ''),
        'freeAdd' => intval($userrow['freeadd']),
        'freeOrderEnabled' => isset($conf['mfxdkg']) && intval($conf['mfxdkg']) === 1,
        'queryEnabled' => isset($conf['ckkg']) && intval($conf['ckkg']) === 1,
        'orderEnabled' => isset($conf['xdkg']) && intval($conf['xdkg']) === 1,
        'notice' => isset($conf['ddgg']) ? trim(strip_tags($conf['ddgg'])) : ''
    ));
}

if ($action === 'course-query') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();

    if (!isset($conf['ckkg']) || intval($conf['ckkg']) !== 1) {
        api_respond(422, '管理员已关闭查课功能，使用请联系管理员！');
    }

    $input = api_read_input();
    $productId = isset($input['productId']) ? intval($input['productId']) : 0;
    $accounts = isset($input['accounts']) && is_array($input['accounts']) ? $input['accounts'] : array();
    $accounts = array_slice($accounts, 0, 50);

    if ($productId <= 0 || count($accounts) === 0) {
        api_respond(422, '所有项目不能为空');
    }

    $product = $DB->get_row(
        "SELECT * FROM qingka_wangke_class WHERE cid='" . $productId . "' AND status=1 LIMIT 1"
    );
    if (!$product) {
        api_respond(404, '所选项目不存在或已下架');
    }

    $queryFee = round((float) $product['ckkf'] * (float) $userrow['addprice'], 2);
    $balance = (float) $userrow['money'];
    $results = array();

    foreach ($accounts as $rawAccount) {
        $parsed = order_parse_userinfo((string) $rawAccount);
        if (!$parsed) {
            $results[] = array(
                'code' => -1,
                'msg' => '信息格式错误，请按“学校 账号 密码”或“账号 密码”填写',
                'userinfo' => trim((string) $rawAccount),
                'userName' => '',
                'courses' => array()
            );
            continue;
        }

        if ($balance < $queryFee && intval($userrow['uid']) !== 1064) {
            $results[] = array(
                'code' => -1,
                'msg' => '余额不足以查课',
                'userinfo' => $parsed['normalized'],
                'userName' => $parsed['user'],
                'courses' => array()
            );
            continue;
        }

        $queryResult = getWk(
            $product['queryplat'],
            $product['getnoun'],
            $parsed['school'],
            $parsed['user'],
            $parsed['pass'],
            $product['name']
        );
        if (!is_array($queryResult)) {
            $queryResult = array('code' => -1, 'msg' => '查课接口返回异常', 'data' => array());
        }

        $courses = array();
        if (isset($queryResult['data']) && is_array($queryResult['data'])) {
            foreach ($queryResult['data'] as $course) {
                if (is_array($course)) {
                    $courses[] = order_course_data($course);
                }
            }
        }

        if (intval($userrow['uid']) !== 1064 && $queryFee > 0) {
            $DB->query(
                "UPDATE qingka_wangke_user SET money=money-" . $queryFee
                . " WHERE uid='" . intval($userrow['uid']) . "' LIMIT 1"
            );
            $balance -= $queryFee;
            wlog($userrow['uid'], '查课', $product['name'] . '-新版查课', -$queryFee);
        }

        $results[] = array(
            'code' => isset($queryResult['code']) ? intval($queryResult['code']) : (count($courses) > 0 ? 1 : -1),
            'msg' => isset($queryResult['msg']) ? (string) $queryResult['msg'] : (count($courses) > 0 ? '查询成功' : '未查询到课程'),
            'userinfo' => $parsed['normalized'],
            'userName' => isset($queryResult['userName']) ? (string) $queryResult['userName'] : $parsed['user'],
            'courses' => $courses
        );
    }

    api_respond(0, '查询完成', array(
        'results' => $results,
        'balance' => number_format(max($balance, 0), 2, '.', ''),
        'queryFee' => number_format($queryFee, 2, '.', '')
    ));
}

if ($action === 'order-submit') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();

    if (!isset($conf['xdkg']) || intval($conf['xdkg']) !== 1) {
        api_respond(422, '管理员已关闭下单功能，使用请联系管理员！');
    }

    $input = api_read_input();
    $productId = isset($input['productId']) ? intval($input['productId']) : 0;
    $selections = isset($input['selections']) && is_array($input['selections']) ? $input['selections'] : array();
    $selections = array_slice($selections, 0, 200);

    if ($productId <= 0 || count($selections) === 0) {
        api_respond(422, '请先选择课程');
    }

    $product = $DB->get_row(
        "SELECT * FROM qingka_wangke_class WHERE cid='" . $productId . "' AND status=1 LIMIT 1"
    );
    if (!$product) {
        api_respond(404, '所选项目不存在或已下架');
    }

    $prepared = array();
    foreach ($selections as $selection) {
        if (!is_array($selection) || !isset($selection['userinfo']) || !isset($selection['course'])) {
            api_respond(422, '课程数据不完整，请重新查课');
        }

        $parsed = order_parse_userinfo((string) $selection['userinfo']);
        $course = is_array($selection['course']) ? order_course_data($selection['course']) : null;
        if (!$parsed || !$course || $course['name'] === '') {
            api_respond(422, '课程数据不完整，请重新查课');
        }

        $safeSchool = daddslashes($parsed['school']);
        $safeUser = daddslashes($parsed['user']);
        $safePass = daddslashes($parsed['pass']);
        $safeCourseId = daddslashes($course['id']);
        $safeCourseName = daddslashes($course['name']);
        $safePlatform = daddslashes($product['name']);
        $lastOrder = $DB->get_row(
            "SELECT dockstatus FROM qingka_wangke_order WHERE ptname='" . $safePlatform
            . "' AND school='" . $safeSchool . "' AND user='" . $safeUser . "' AND pass='" . $safePass
            . "' AND kcid='" . $safeCourseId . "' AND kcname='" . $safeCourseName
            . "' ORDER BY addtime DESC LIMIT 1"
        );
        if ($lastOrder && (string) $lastOrder['dockstatus'] !== '4') {
            api_respond(422, '重复下单，请取消订单再试！');
        }

        $prepared[] = array(
            'account' => $parsed,
            'userName' => isset($selection['userName']) ? trim(strip_tags((string) $selection['userName'])) : $parsed['user'],
            'course' => $course
        );
    }

    $totalCourses = count($prepared);
    $validCids = order_free_cids($conf);
    $isFreeOrder = isset($conf['mfxdkg']) && intval($conf['mfxdkg']) === 1
        && intval($userrow['freeadd']) >= $totalCourses
        && in_array((string) $product['cid'], $validCids, true);
    $unitPrice = $isFreeOrder ? 0 : order_submit_price($product, $userrow, $DB);

    if ($unitPrice < 0 || (float) $userrow['addprice'] < 0.1) {
        api_respond(422, '当前账号费率异常，请联系管理员');
    }

    $totalPrice = round($totalCourses * $unitPrice, 2);
    if ((float) $userrow['money'] < $totalPrice) {
        api_respond(422, '余额不足');
    }

    $clientip = real_ip();
    $dockstatus = intval($product['docking']) === 0 ? '99' : '0';
    $DB->query('START TRANSACTION');

    if ($isFreeOrder) {
        $updated = $DB->query(
            "UPDATE qingka_wangke_user SET freeadd=freeadd-" . $totalCourses
            . " WHERE uid='" . intval($userrow['uid']) . "' AND freeadd>=" . $totalCourses . " LIMIT 1"
        );
        if (!$updated) {
            $DB->query('ROLLBACK');
            api_respond(500, '免费次数扣减失败，请重试');
        }
    } elseif ($totalPrice > 0) {
        $updated = $DB->query(
            "UPDATE qingka_wangke_user SET money=money-" . $totalPrice
            . " WHERE uid='" . intval($userrow['uid']) . "' AND money>=" . $totalPrice . " LIMIT 1"
        );
        if (!$updated) {
            $DB->query('ROLLBACK');
            api_respond(500, '余额扣减失败，请重试');
        }
    }

    foreach ($prepared as $item) {
        $account = $item['account'];
        $course = $item['course'];
        $safeName = daddslashes($item['userName']);
        $safeSchool = daddslashes($account['school']);
        $safeUser = daddslashes($account['user']);
        $safePass = daddslashes($account['pass']);
        $safeCourseId = daddslashes($course['id']);
        $safeCourseName = daddslashes($course['name']);
        $safeCourseEnd = daddslashes($course['kcjs']);
        $safePlatform = daddslashes($product['name']);
        $safeNoun = daddslashes($product['noun']);
        $safeClientIp = daddslashes($clientip);
        $inserted = $DB->query(
            "INSERT INTO qingka_wangke_order "
            . "(uid,cid,hid,yid,ptname,school,name,user,pass,phone,kcid,kcname,courseStartTime,courseEndTime,examStartTime,examEndTime,chapterCount,unfinishedChapterCount,cookie,fees,noun,miaoshua,addtime,ip,dockstatus,loginstatus,status,process,bsnum,remarks,dakatime,leixing,detailed,dlip,docknum,finalupdate,region) VALUES ("
            . "'" . intval($userrow['uid']) . "','" . intval($product['cid']) . "','" . intval($product['docking']) . "','0','" . $safePlatform
            . "','" . $safeSchool . "','" . $safeName . "','" . $safeUser . "','" . $safePass . "','','" . $safeCourseId
            . "','" . $safeCourseName . "','','" . $safeCourseEnd . "','','','0','0','','" . $unitPrice . "','" . $safeNoun
            . "','0','" . $date . "','" . $safeClientIp . "','" . $dockstatus . "','','待处理','待处理','0','','','0','','',0,'" . $date . "','')"
        );

        if (!$inserted) {
            $dbErr = method_exists($DB, 'error') ? $DB->error() : '';
            $DB->query('ROLLBACK');
            api_respond(500, '提交失败' . ($dbErr ? ": {$dbErr}" : '，请重试'));
        }

        wlog(
            $userrow['uid'],
            $isFreeOrder ? '免费下单' : '添加任务',
            $product['name'] . '-' . $course['name'] . '-新版提交',
            $isFreeOrder ? 0 : -$unitPrice
        );
    }

    $DB->query('COMMIT');
    $freshUser = $DB->get_row(
        "SELECT money,freeadd FROM qingka_wangke_user WHERE uid='" . intval($userrow['uid']) . "' LIMIT 1"
    );

    api_respond(0, '提交成功' . $totalCourses . '门课程', array(
        'submitted' => $totalCourses,
        'charged' => number_format($totalPrice, 2, '.', ''),
        'balance' => number_format((float) $freshUser['money'], 2, '.', ''),
        'freeAdd' => intval($freshUser['freeadd'])
    ));
}

if ($action === 'dashboard') {
    api_require_login(isset($islogin) ? $islogin : 0);
    $uid = intval($userrow['uid']);
    $scope = $uid === 1 ? '' : " WHERE uid='" . $uid . "'";
    $todayScope = $uid === 1
        ? " WHERE addtime>'" . $jtdate . "'"
        : " WHERE uid='" . $uid . "' AND addtime>'" . $jtdate . "'";
    $runningScope = $uid === 1
        ? " WHERE status='进行中'"
        : " WHERE uid='" . $uid . "' AND status='进行中'";
    $completedScope = $uid === 1
        ? " WHERE status='已完成'"
        : " WHERE uid='" . $uid . "' AND status='已完成'";

    $orderTotal = $DB->count('SELECT COUNT(*) FROM qingka_wangke_order' . $scope);
    $todayOrders = $DB->count('SELECT COUNT(*) FROM qingka_wangke_order' . $todayScope);
    $runningOrders = $DB->count('SELECT COUNT(*) FROM qingka_wangke_order' . $runningScope);
    $completedOrders = $DB->count('SELECT COUNT(*) FROM qingka_wangke_order' . $completedScope);
    $userTotal = $uid === 1
        ? $DB->count('SELECT COUNT(*) FROM qingka_wangke_user')
        : $DB->count("SELECT COUNT(*) FROM qingka_wangke_user WHERE uuid='" . $uid . "'");

    // 真实近7日订单趋势
    $trendDates = array();
    $trendCounts = array();
    for ($i = 6; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i day"));
        $dayStart = $day . ' 00:00:00';
        $dayEnd = $day . ' 23:59:59';
        $dayWhere = $uid === 1 
            ? " WHERE addtime >= '$dayStart' AND addtime <= '$dayEnd'" 
            : " WHERE uid='$uid' AND addtime >= '$dayStart' AND addtime <= '$dayEnd'";
        $c = $DB->count("SELECT COUNT(*) FROM qingka_wangke_order" . $dayWhere);
        $trendDates[] = date('m/d', strtotime($day));
        $trendCounts[] = intval($c);
    }

    // 真实订单状态分布
    $statusMap = array(
        '进行中' => intval($runningOrders),
        '已完成' => intval($completedOrders),
        '待处理' => intval($DB->count("SELECT COUNT(*) FROM qingka_wangke_order" . ($uid === 1 ? " WHERE status='待处理' OR dockstatus=0" : " WHERE uid='$uid' AND (status='待处理' OR dockstatus=0)"))),
        '异常/其他' => intval(max(0, $orderTotal - $runningOrders - $completedOrders))
    );
    $distribution = array();
    foreach ($statusMap as $k => $v) {
        $distribution[] = array('name' => $k, 'value' => $v);
    }

    // 真实最新系统公告列表（前5条）
    $notices = array();
    $gQuery = $DB->query("SELECT * FROM qingka_wangke_gonggao ORDER BY id DESC LIMIT 5");
    while ($gr = $DB->fetch($gQuery)) {
        $notices[] = array(
            'id' => intval($gr['id']),
            'title' => (string)$gr['title'],
            'content' => trim(strip_tags((string)$gr['content'])),
            'time' => !empty($gr['time']) ? (string)$gr['time'] : (string)$gr['addtime']
        );
    }

    api_respond(0, 'ok', array(
        'orderTotal' => intval($orderTotal),
        'todayOrders' => intval($todayOrders),
        'runningOrders' => intval($runningOrders),
        'completedOrders' => intval($completedOrders),
        'userTotal' => intval($userTotal),
        'balance' => isset($userrow['money']) ? number_format((float) $userrow['money'], 2, '.', '') : '0.00',
        'announcement' => isset($conf['zsgonggao']) ? trim(strip_tags($conf['zsgonggao'])) : '',
        'trend' => array(
            'dates' => $trendDates,
            'counts' => $trendCounts
        ),
        'distribution' => $distribution,
        'notices' => $notices
    ));
}

if ($action === 'orders') {
    api_require_login(isset($islogin) ? $islogin : 0);

    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $pageSize = isset($_GET['pageSize']) ? intval($_GET['pageSize']) : 20;
    $pageSize = max(10, min(100, $pageSize));
    $offset = ($page - 1) * $pageSize;
    $keyword = isset($_GET['keyword']) ? trim(strip_tags($_GET['keyword'])) : '';
    $status = isset($_GET['status']) ? trim(strip_tags($_GET['status'])) : '';
    $uid = intval($userrow['uid']);

    $conditions = array($uid === 1 ? '1=1' : "uid='" . $uid . "'");

    if ($keyword !== '') {
        $safeKeyword = daddslashes($keyword);
        $conditions[] = "(oid LIKE '%" . $safeKeyword . "%'"
            . " OR user LIKE '%" . $safeKeyword . "%'"
            . " OR ptname LIKE '%" . $safeKeyword . "%'"
            . " OR kcname LIKE '%" . $safeKeyword . "%'"
            . " OR school LIKE '%" . $safeKeyword . "%'"
            . " OR remarks LIKE '%" . $safeKeyword . "%')";
    }

    if ($status !== '') {
        $conditions[] = "status='" . daddslashes($status) . "'";
    }

    $where = ' WHERE ' . implode(' AND ', $conditions);
    $result = $DB->query(
        'SELECT oid,uid,cid,user,ptname,kcname,school,process,remarks,status,dockstatus,addtime '
        . 'FROM qingka_wangke_order' . $where . ' ORDER BY oid DESC LIMIT ' . $offset . ',' . $pageSize
    );
    $total = $DB->count('SELECT COUNT(*) FROM qingka_wangke_order' . $where);
    $records = array();

    while ($row = $DB->fetch($result)) {
        $records[] = array(
            'orderId' => (string) $row['oid'],
            'ownerId' => (string) $row['uid'],
            'courseId' => (string) $row['cid'],
            'account' => (string) $row['user'],
            'platform' => isset($row['ptname']) ? (string) $row['ptname'] : '',
            'courseName' => isset($row['kcname']) ? (string) $row['kcname'] : '',
            'school' => isset($row['school']) ? (string) $row['school'] : '',
            'progress' => isset($row['process']) ? (string) $row['process'] : '',
            'remarks' => isset($row['remarks']) ? (string) $row['remarks'] : '',
            'status' => isset($row['status']) ? (string) $row['status'] : '',
            'dockStatus' => isset($row['dockstatus']) ? (string) $row['dockstatus'] : '',
            'createdAt' => isset($row['addtime']) ? (string) $row['addtime'] : ''
        );
    }

    api_respond(0, 'ok', array(
        'records' => $records,
        'current' => $page,
        'size' => $pageSize,
        'total' => intval($total)
    ));
}

// ==========================================
// 核心商品与货源接口: 分类、接口、网课、一键对接
// ==========================================

function db_get_or_create_fenlei($DB, $name, $now)
{
    $safeName = daddslashes(trim($name));
    $ex = $DB->get_row("SELECT id FROM qingka_wangke_fenlei WHERE name='$safeName' LIMIT 1");
    if ($ex) {
        return array('id' => (string) $ex['id'], 'created' => false);
    }
    $maxSort = $DB->get_row("SELECT MAX(CAST(sort AS UNSIGNED)) AS msort FROM qingka_wangke_fenlei");
    $newSort = isset($maxSort['msort']) ? intval($maxSort['msort']) + 1 : 1;
    $DB->query("INSERT INTO qingka_wangke_fenlei (sort, name, status, time) VALUES ('$newSort', '$safeName', '1', '$now')");
    $newId = (string) ($DB->insert_id ? $DB->insert_id : $DB->get_row("SELECT id FROM qingka_wangke_fenlei WHERE name='$safeName' ORDER BY id DESC LIMIT 1")['id']);
    return array('id' => $newId, 'created' => true);
}

function db_upsert_docking_class($DB, $hid, $course, $fenleiId, $now)
{
    $safeName = daddslashes(trim($course['name']));
    $safeCid = daddslashes(trim($course['cid']));
    $safePrice = daddslashes(trim($course['price']));
    $safeContent = isset($course['content']) ? daddslashes(trim($course['content'])) : '';

    $exClass = $DB->get_row("SELECT cid FROM qingka_wangke_class WHERE (noun='$safeCid' OR getnoun='$safeCid') AND docking='$hid' LIMIT 1");
    if ($exClass) {
        $DB->query(
            "UPDATE qingka_wangke_class SET name='$safeName', price='$safePrice', fenlei='$fenleiId', "
            . "status='1', queryplat='$hid', docking='$hid', content='$safeContent' WHERE cid='{$exClass['cid']}'"
        );
        return false;
    }

    $DB->query(
        "INSERT INTO qingka_wangke_class (sort, name, getnoun, noun, price, vipprice, ckkf, queryplat, docking, yunsuan, content, addtime, status, fenlei, kcid) "
        . "VALUES ('10', '$safeName', '$safeCid', '$safeCid', '$safePrice', '$safePrice', '0', '$hid', '$hid', '*', '$safeContent', '$now', '1', '$fenleiId', '0')"
    );
    return true;
}

if (!function_exists('wkname')) {
    if (file_exists(__DIR__ . '/../../Checkorder/xdjk.php')) {
        require_once __DIR__ . '/../../Checkorder/xdjk.php';
    } else {
        function wkname()
        {
            return array(
                '27' => '27系统',
                '29' => '29系统',
                '2xx' => '爱学习',
                'xm' => 'SkyLearn',
                'hzw' => 'hzw',
                'benz' => 'benz',
                'longlong' => '龙龙平台',
                'liunian' => '流年平台'
            );
        }
    }
}

function sprint1_call_huoyuan($huoyuan, $act = 'getclass')
{
    $baseUrl = isset($huoyuan['url']) ? trim($huoyuan['url']) : '';
    if (!preg_match('/^https?:\/\//i', $baseUrl)) {
        $baseUrl = 'http://' . $baseUrl;
    }

    $apiUrl = rtrim($baseUrl, '/') . '/api.php?act=' . $act;
    $postData = array(
        'uid' => isset($huoyuan['user']) ? $huoyuan['user'] : '',
        'key' => isset($huoyuan['pass']) ? $huoyuan['pass'] : ''
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AdminAPI');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode === 200 && !$error && $response) {
        $decoded = json_decode($response, true);
        if (is_array($decoded)) {
            return array('success' => true, 'data' => $decoded);
        }
    }

    return array('success' => false, 'error' => $error ? $error : ('上游返回异常 (HTTP ' . $httpCode . ')'));
}

// ------------------------------------------
// 1. 分类设置 (fenlei)
// ------------------------------------------

if ($action === 'fenlei-list') {
    api_require_super($userrow, $islogin);

    $sql = 'SELECT f.*, (SELECT COUNT(*) FROM qingka_wangke_class c WHERE c.fenlei = f.id) AS course_count '
        . 'FROM qingka_wangke_fenlei f ORDER BY CAST(f.sort AS UNSIGNED) ASC, f.id ASC';
    $result = $DB->query($sql);
    $list = array();

    while ($row = $DB->fetch($result)) {
        $list[] = array(
            'id' => (string) $row['id'],
            'sort' => intval($row['sort']),
            'name' => (string) $row['name'],
            'status' => intval($row['status']),
            'time' => isset($row['time']) ? (string) $row['time'] : '',
            'courseCount' => intval($row['course_count'])
        );
    }

    api_respond(0, 'ok', array('list' => $list));
}

if ($action === 'fenlei-save') {
    api_require_post();
    api_require_super($userrow, $islogin);

    $input = api_read_input();
    $id = isset($input['id']) ? intval($input['id']) : 0;
    $name = isset($input['name']) ? trim(strip_tags($input['name'])) : '';
    $sort = isset($input['sort']) ? intval($input['sort']) : 0;
    $status = isset($input['status']) && intval($input['status']) === 1 ? 1 : 0;

    if ($name === '') {
        api_respond(422, '分类名称不能为空');
    }

    $safeName = daddslashes($name);
    $now = date('Y-m-d H:i:s');

    if ($id > 0) {
        $DB->query("UPDATE qingka_wangke_fenlei SET sort='$sort', name='$safeName', status='$status' WHERE id='$id'");
        api_respond(0, '修改分类成功');
    } else {
        $DB->query("INSERT INTO qingka_wangke_fenlei (sort, name, status, time) VALUES ('$sort', '$safeName', '$status', '$now')");
        api_respond(0, '添加分类成功');
    }
}

if ($action === 'fenlei-delete') {
    api_require_post();
    api_require_super($userrow, $islogin);

    $input = api_read_input();
    $id = isset($input['id']) ? intval($input['id']) : 0;

    if ($id <= 0) {
        api_respond(422, '请选择要删除的分类');
    }

    $hasCourse = $DB->count("SELECT COUNT(*) FROM qingka_wangke_class WHERE fenlei='$id'");
    if (intval($hasCourse) > 0) {
        api_respond(400, '该分类下仍有 ' . $hasCourse . ' 门网课，请先迁移或删除网课后再删除分类');
    }

    $DB->query("DELETE FROM qingka_wangke_fenlei WHERE id='$id'");
    api_respond(0, '分类删除成功');
}

// ------------------------------------------
// 2. 接口配置 (huoyuan)
// ------------------------------------------

if ($action === 'huoyuan-list') {
    api_require_super($userrow, $islogin);

    $wkPlatforms = wkname();
    $sql = 'SELECT hid, pt, name, url, user, ip, cookie, status, addtime, endtime, '
        . 'CASE WHEN pass IS NOT NULL AND pass != "" THEN 1 ELSE 0 END AS has_pass, '
        . 'CASE WHEN token IS NOT NULL AND token != "" THEN 1 ELSE 0 END AS has_token '
        . 'FROM qingka_wangke_huoyuan ORDER BY hid ASC';
    $result = $DB->query($sql);
    $list = array();

    while ($row = $DB->fetch($result)) {
        $ptKey = (string) $row['pt'];
        $list[] = array(
            'hid' => (string) $row['hid'],
            'pt' => $ptKey,
            'ptName' => isset($wkPlatforms[$ptKey]) ? $wkPlatforms[$ptKey] : $ptKey,
            'name' => (string) $row['name'],
            'url' => (string) $row['url'],
            'user' => (string) $row['user'],
            'ip' => isset($row['ip']) ? (string) $row['ip'] : '',
            'cookie' => isset($row['cookie']) ? (string) $row['cookie'] : '',
            'status' => intval($row['status']),
            'hasPass' => intval($row['has_pass']) === 1,
            'hasToken' => intval($row['has_token']) === 1,
            'addtime' => isset($row['addtime']) ? (string) $row['addtime'] : '',
            'endtime' => isset($row['endtime']) ? (string) $row['endtime'] : ''
        );
    }

    $platformOptions = array();
    foreach ($wkPlatforms as $k => $v) {
        $platformOptions[] = array('value' => (string) $k, 'label' => (string) $v);
    }

    api_respond(0, 'ok', array('list' => $list, 'platformOptions' => $platformOptions));
}

if ($action === 'huoyuan-save') {
    api_require_post();
    api_require_super($userrow, $islogin);

    $input = api_read_input();
    $hid = isset($input['hid']) ? intval($input['hid']) : 0;
    $name = isset($input['name']) ? trim(strip_tags($input['name'])) : '';
    $pt = isset($input['pt']) ? trim(strip_tags($input['pt'])) : '';
    $url = isset($input['url']) ? trim(strip_tags($input['url'])) : '';
    $user = isset($input['user']) ? trim(strip_tags($input['user'])) : '';
    $pass = isset($input['pass']) ? trim((string) $input['pass']) : '';
    $token = isset($input['token']) ? trim((string) $input['token']) : '';
    $ip = isset($input['ip']) ? trim(strip_tags($input['ip'])) : '';
    $cookie = isset($input['cookie']) ? trim((string) $input['cookie']) : '';
    $status = isset($input['status']) && intval($input['status']) === 0 ? 0 : 1;

    if ($name === '') {
        api_respond(422, '平台名称不能为空');
    }
    if ($pt === '') {
        api_respond(422, '请选择平台类型');
    }

    $now = date('Y-m-d H:i:s');
    $safeName = daddslashes($name);
    $safePt = daddslashes($pt);
    $safeUrl = daddslashes($url);
    $safeUser = daddslashes($user);
    $safeIp = daddslashes($ip);
    $safeCookie = daddslashes($cookie);

    if ($hid > 0) {
        $existing = $DB->get_row("SELECT * FROM qingka_wangke_huoyuan WHERE hid='$hid' LIMIT 1");
        if (!$existing) {
            api_respond(404, '接口不存在');
        }

        $safePass = $pass !== '' ? daddslashes($pass) : daddslashes($existing['pass']);
        $safeToken = $token !== '' ? daddslashes($token) : daddslashes($existing['token']);

        $DB->query(
            "UPDATE qingka_wangke_huoyuan SET pt='$safePt', name='$safeName', url='$safeUrl', "
            . "user='$safeUser', pass='$safePass', token='$safeToken', ip='$safeIp', cookie='$safeCookie', "
            . "status='$status', endtime='$now' WHERE hid='$hid'"
        );
        api_respond(0, '修改接口配置成功');
    } else {
        $safePass = daddslashes($pass);
        $safeToken = daddslashes($token);

        $DB->query(
            "INSERT INTO qingka_wangke_huoyuan (pt, name, url, user, pass, token, ip, cookie, status, addtime, endtime) "
            . "VALUES ('$safePt', '$safeName', '$safeUrl', '$safeUser', '$safePass', '$safeToken', '$safeIp', '$safeCookie', '$status', '$now', '$now')"
        );
        api_respond(0, '添加接口配置成功');
    }
}

if ($action === 'huoyuan-delete') {
    api_require_post();
    api_require_super($userrow, $islogin);

    $input = api_read_input();
    $hid = isset($input['hid']) ? intval($input['hid']) : 0;

    if ($hid <= 0) {
        api_respond(422, '请选择要删除的接口');
    }

    $linkedCount = $DB->count("SELECT COUNT(*) FROM qingka_wangke_class WHERE docking='$hid' OR queryplat='$hid'");
    if (intval($linkedCount) > 0) {
        api_respond(400, '仍有 ' . $linkedCount . ' 门网课正在使用此接口对接或查课，请先更换网课接口设置后再删除');
    }

    $DB->query("DELETE FROM qingka_wangke_huoyuan WHERE hid='$hid'");
    api_respond(0, '接口删除成功');
}

if ($action === 'huoyuan-balance') {
    api_require_post();
    api_require_super($userrow, $islogin);

    $input = api_read_input();
    $hid = isset($input['hid']) ? intval($input['hid']) : 0;
    if ($hid <= 0) {
        api_respond(422, '货源参数错误');
    }

    $row = $DB->get_row("SELECT * FROM qingka_wangke_huoyuan WHERE hid='$hid' LIMIT 1");
    if (!$row) {
        api_respond(404, '货源不存在');
    }

    $callRes = sprint1_call_huoyuan($row, 'getmoney');
    if (!$callRes['success']) {
        api_respond(400, '上游连接失败: ' . $callRes['error']);
    }

    $data = $callRes['data'];
    $balance = isset($data['money']) ? (string) $data['money'] : (isset($data['data']['money']) ? (string) $data['data']['money'] : null);

    if ($balance !== null) {
        api_respond(0, 'ok', array(
            'hid' => (string) $hid,
            'name' => (string) $row['name'],
            'balance' => $balance,
            'raw' => $data
        ));
    } else {
        $msg = isset($data['msg']) ? $data['msg'] : '未获取到有效余额字段';
        api_respond(400, '查询失败: ' . $msg, $data);
    }
}

// ------------------------------------------
// 3. 网课设置 (class)
// ------------------------------------------

if ($action === 'class-options') {
    api_require_super($userrow, $islogin);

    $fenleiRes = $DB->query('SELECT id, name, sort FROM qingka_wangke_fenlei WHERE status=1 ORDER BY CAST(sort AS UNSIGNED) ASC, id ASC');
    $fenleiList = array();
    while ($f = $DB->fetch($fenleiRes)) {
        $fenleiList[] = array('value' => (string) $f['id'], 'label' => (string) $f['name']);
    }
    $fenleiList[] = array('value' => 'wck', 'label' => '无查课');

    $huoyuanRes = $DB->query('SELECT hid, name, pt FROM qingka_wangke_huoyuan WHERE status=1 ORDER BY hid ASC');
    $huoyuanList = array(
        array('value' => '0', 'label' => '自营 (0)')
    );
    while ($h = $DB->fetch($huoyuanRes)) {
        $huoyuanList[] = array('value' => (string) $h['hid'], 'label' => $h['name'] . ' [' . $h['pt'] . ']');
    }

    $wkPlatforms = wkname();
    $platformOptions = array();
    foreach ($wkPlatforms as $k => $v) {
        $platformOptions[] = array('value' => (string) $k, 'label' => (string) $v);
    }

    api_respond(0, 'ok', array(
        'fenleiList' => $fenleiList,
        'huoyuanList' => $huoyuanList,
        'platformOptions' => $platformOptions
    ));
}

if ($action === 'class-list') {
    api_require_super($userrow, $islogin);

    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $pageSize = isset($_GET['pageSize']) ? max(10, min(200, intval($_GET['pageSize']))) : 50;
    $offset = ($page - 1) * $pageSize;
    $keyword = isset($_GET['keyword']) ? trim(strip_tags($_GET['keyword'])) : '';
    $fenlei = isset($_GET['fenlei']) ? trim(strip_tags($_GET['fenlei'])) : '';
    $status = isset($_GET['status']) && $_GET['status'] !== '' ? intval($_GET['status']) : null;

    $conditions = array('1=1');
    if ($keyword !== '') {
        $safeKw = daddslashes($keyword);
        $conditions[] = "(name LIKE '%$safeKw%' OR noun LIKE '%$safeKw%' OR getnoun LIKE '%$safeKw%' OR cid='$safeKw')";
    }
    if ($fenlei !== '') {
        $conditions[] = "fenlei='" . daddslashes($fenlei) . "'";
    }
    if ($status !== null) {
        $conditions[] = "status='$status'";
    }

    $where = ' WHERE ' . implode(' AND ', $conditions);
    $total = $DB->count("SELECT COUNT(*) FROM qingka_wangke_class $where");

    $sql = "SELECT c.*, "
        . "f.name AS fenlei_name, "
        . "h1.name AS cx_name, "
        . "h2.name AS add_name "
        . "FROM qingka_wangke_class c "
        . "LEFT JOIN qingka_wangke_fenlei f ON c.fenlei = f.id "
        . "LEFT JOIN qingka_wangke_huoyuan h1 ON c.queryplat = h1.hid "
        . "LEFT JOIN qingka_wangke_huoyuan h2 ON c.docking = h2.hid "
        . "$where ORDER BY CAST(c.sort AS UNSIGNED) ASC, c.cid DESC LIMIT $offset, $pageSize";

    $result = $DB->query($sql);
    $records = array();

    while ($row = $DB->fetch($result)) {
        $fenleiName = $row['fenlei_name'];
        if ($row['fenlei'] === 'wck') {
            $fenleiName = '无查课';
        } elseif (!$fenleiName) {
            $fenleiName = '未分类';
        }

        $cxName = $row['queryplat'] === '0' ? '自营' : ($row['cx_name'] ? $row['cx_name'] : 'ID:' . $row['queryplat']);
        $addName = $row['docking'] === '0' ? '自营' : ($row['add_name'] ? $row['add_name'] : 'ID:' . $row['docking']);

        $records[] = array(
            'cid' => (string) $row['cid'],
            'sort' => intval($row['sort']),
            'name' => (string) $row['name'],
            'getnoun' => (string) $row['getnoun'],
            'noun' => (string) $row['noun'],
            'price' => (string) $row['price'],
            'vipprice' => isset($row['vipprice']) ? (string) $row['vipprice'] : '',
            'ckkf' => isset($row['ckkf']) ? (string) $row['ckkf'] : '0',
            'queryplat' => (string) $row['queryplat'],
            'docking' => (string) $row['docking'],
            'yunsuan' => isset($row['yunsuan']) ? (string) $row['yunsuan'] : '*',
            'content' => isset($row['content']) ? (string) $row['content'] : '',
            'status' => intval($row['status']),
            'fenlei' => (string) $row['fenlei'],
            'kcid' => isset($row['kcid']) ? (string) $row['kcid'] : '0',
            'addtime' => isset($row['addtime']) ? (string) $row['addtime'] : '',
            'fenleiName' => $fenleiName,
            'cxName' => $cxName,
            'addName' => $addName
        );
    }

    api_respond(0, 'ok', array(
        'records' => $records,
        'current' => $page,
        'size' => $pageSize,
        'total' => intval($total)
    ));
}

if ($action === 'class-save') {
    api_require_post();
    api_require_super($userrow, $islogin);

    $input = api_read_input();
    $cid = isset($input['cid']) ? intval($input['cid']) : 0;
    $name = isset($input['name']) ? trim(strip_tags($input['name'])) : '';
    $sort = isset($input['sort']) ? intval($input['sort']) : 10;
    $getnoun = isset($input['getnoun']) ? trim($input['getnoun']) : '';
    $noun = isset($input['noun']) ? trim($input['noun']) : '';
    $price = isset($input['price']) ? trim($input['price']) : '0';
    $vipprice = isset($input['vipprice']) ? trim($input['vipprice']) : '0';
    $ckkf = isset($input['ckkf']) ? trim($input['ckkf']) : '0';
    $queryplat = isset($input['queryplat']) ? trim($input['queryplat']) : '0';
    $docking = isset($input['docking']) ? trim($input['docking']) : '0';
    $yunsuan = isset($input['yunsuan']) && $input['yunsuan'] === '+' ? '+' : '*';
    $content = isset($input['content']) ? trim($input['content']) : '';
    $status = isset($input['status']) && intval($input['status']) === 0 ? 0 : 1;
    $fenlei = isset($input['fenlei']) ? trim($input['fenlei']) : '1';
    $kcid = isset($input['kcid']) ? trim($input['kcid']) : '0';

    if ($name === '') {
        api_respond(422, '课程名称不能为空');
    }

    $safeName = daddslashes($name);
    $safeGetnoun = daddslashes($getnoun);
    $safeNoun = daddslashes($noun);
    $safePrice = daddslashes($price);
    $safeVipprice = daddslashes($vipprice);
    $safeCkkf = daddslashes($ckkf);
    $safeQueryplat = daddslashes($queryplat);
    $safeDocking = daddslashes($docking);
    $safeContent = daddslashes($content);
    $safeFenlei = daddslashes($fenlei);
    $safeKcid = daddslashes($kcid);
    $now = date('Y-m-d H:i:s');

    if ($cid > 0) {
        $DB->query(
            "UPDATE qingka_wangke_class SET sort='$sort', name='$safeName', getnoun='$safeGetnoun', noun='$safeNoun', "
            . "price='$safePrice', vipprice='$safeVipprice', ckkf='$safeCkkf', queryplat='$safeQueryplat', docking='$safeDocking', "
            . "yunsuan='$yunsuan', content='$safeContent', status='$status', fenlei='$safeFenlei', kcid='$safeKcid' WHERE cid='$cid'"
        );
        api_respond(0, '修改网课成功');
    } else {
        $DB->query(
            "INSERT INTO qingka_wangke_class (sort, name, getnoun, noun, price, vipprice, ckkf, queryplat, docking, yunsuan, content, addtime, status, fenlei, kcid) "
            . "VALUES ('$sort', '$safeName', '$safeGetnoun', '$safeNoun', '$safePrice', '$safeVipprice', '$safeCkkf', '$safeQueryplat', '$safeDocking', '$yunsuan', '$safeContent', '$now', '$status', '$safeFenlei', '$safeKcid')"
        );
        api_respond(0, '添加网课成功');
    }
}

if ($action === 'class-delete') {
    api_require_post();
    api_require_super($userrow, $islogin);

    $input = api_read_input();
    $cids = array();
    if (isset($input['cids']) && is_array($input['cids'])) {
        foreach ($input['cids'] as $v) {
            $id = intval($v);
            if ($id > 0) $cids[] = $id;
        }
    } elseif (isset($input['cid'])) {
        $id = intval($input['cid']);
        if ($id > 0) $cids[] = $id;
    }

    if (empty($cids)) {
        api_respond(422, '请选择要删除的网课');
    }

    $idList = implode(',', $cids);
    $DB->query("DELETE FROM qingka_wangke_class WHERE cid IN ($idList)");
    api_respond(0, '删除成功，共删除 ' . count($cids) . ' 条记录');
}

if ($action === 'class-batch-status') {
    api_require_post();
    api_require_super($userrow, $islogin);

    $input = api_read_input();
    $status = isset($input['status']) && intval($input['status']) === 1 ? 1 : 0;
    $cids = array();
    if (isset($input['cids']) && is_array($input['cids'])) {
        foreach ($input['cids'] as $v) {
            $id = intval($v);
            if ($id > 0) $cids[] = $id;
        }
    }

    if (empty($cids)) {
        api_respond(422, '请选择要操作的网课');
    }

    $idList = implode(',', $cids);
    $DB->query("UPDATE qingka_wangke_class SET status='$status' WHERE cid IN ($idList)");
    api_respond(0, '批量更新状态成功');
}

if ($action === 'class-batch-price-sort') {
    api_require_post();
    api_require_super($userrow, $islogin);

    $input = api_read_input();
    $updates = isset($input['updates']) && is_array($input['updates']) ? $input['updates'] : array();

    if (empty($updates)) {
        api_respond(422, '无修改数据');
    }

    $updatedCount = 0;
    foreach ($updates as $item) {
        $cid = isset($item['cid']) ? intval($item['cid']) : 0;
        if ($cid <= 0) continue;

        $setParts = array();
        if (isset($item['price'])) {
            $setParts[] = "price='" . daddslashes(trim($item['price'])) . "'";
        }
        if (isset($item['sort'])) {
            $setParts[] = "sort='" . intval($item['sort']) . "'";
        }

        if (!empty($setParts)) {
            $DB->query("UPDATE qingka_wangke_class SET " . implode(', ', $setParts) . " WHERE cid='$cid'");
            $updatedCount++;
        }
    }

    api_respond(0, '成功更新 ' . $updatedCount . ' 门网课的配置');
}

// ------------------------------------------
// 4. 一键对接 (yjdj)
// ------------------------------------------

if ($action === 'yjdj-remote-classes') {
    api_require_post();
    api_require_super($userrow, $islogin);

    $input = api_read_input();
    $hid = isset($input['hid']) ? intval($input['hid']) : 0;
    if ($hid <= 0) {
        api_respond(422, '请选择货源接口');
    }

    $huoyuan = $DB->get_row("SELECT * FROM qingka_wangke_huoyuan WHERE hid='$hid' AND status=1 LIMIT 1");
    if (!$huoyuan) {
        api_respond(404, '货源不存在或已停用');
    }

    $callRes = sprint1_call_huoyuan($huoyuan, 'getclass');
    if (!$callRes['success']) {
        api_respond(400, '调用上游接口失败: ' . $callRes['error']);
    }

    $remoteData = $callRes['data'];
    $rawList = isset($remoteData['data']) && is_array($remoteData['data']) ? $remoteData['data'] : array();

    // 查本地已上架商品比对
    $existingRes = $DB->query("SELECT cid, noun, getnoun FROM qingka_wangke_class WHERE docking='$hid'");
    $existingMap = array();
    while ($ex = $DB->fetch($existingRes)) {
        if ($ex['noun']) $existingMap[(string) $ex['noun']] = true;
        if ($ex['getnoun']) $existingMap[(string) $ex['getnoun']] = true;
    }

    $classList = array();
    $categories = array();

    foreach ($rawList as $item) {
        $remoteCid = isset($item['cid']) ? (string) $item['cid'] : '';
        $fenleiName = isset($item['fenleiname']) && trim($item['fenleiname']) !== '' ? trim($item['fenleiname']) : '未分类';

        if (!in_array($fenleiName, $categories)) {
            $categories[] = $fenleiName;
        }

        $isOnline = isset($existingMap[$remoteCid]);

        $classList[] = array(
            'cid' => $remoteCid,
            'name' => isset($item['name']) ? (string) $item['name'] : '',
            'price' => isset($item['price']) ? (string) $item['price'] : '0.00',
            'fenleiname' => $fenleiName,
            'content' => isset($item['content']) ? (string) $item['content'] : '',
            'isOnline' => $isOnline
        );
    }

    api_respond(0, 'ok', array(
        'hid' => (string) $hid,
        'categories' => $categories,
        'classes' => $classList,
        'total' => count($classList)
    ));
}

if ($action === 'yjdj-copy-fenlei') {
    api_require_post();
    api_require_super($userrow, $islogin);

    $input = api_read_input();
    $hid = isset($input['hid']) ? intval($input['hid']) : 0;
    $copyMode = isset($input['copyMode']) && $input['copyMode'] === 'fenlei_and_class' ? 'fenlei_and_class' : 'fenlei_only';

    if ($hid <= 0) api_respond(422, '请选择货源接口');

    $huoyuan = $DB->get_row("SELECT * FROM qingka_wangke_huoyuan WHERE hid='$hid' AND status=1 LIMIT 1");
    if (!$huoyuan) api_respond(404, '货源不存在或已停用');

    $callRes = sprint1_call_huoyuan($huoyuan, 'getclass');
    if (!$callRes['success']) api_respond(400, '调用上游接口失败: ' . $callRes['error']);

    $rawList = isset($callRes['data']['data']) && is_array($callRes['data']['data']) ? $callRes['data']['data'] : array();
    $fenleiNames = array_unique(array_filter(array_map(function ($i) { return isset($i['fenleiname']) ? trim($i['fenleiname']) : ''; }, $rawList)));
    if (empty($fenleiNames)) api_respond(400, '上游货源未返回有效分类');

    $createdFenlei = 0;
    $skippedFenlei = 0;
    $fenleiMap = array();
    $now = date('Y-m-d H:i:s');

    foreach ($fenleiNames as $fn) {
        $res = db_get_or_create_fenlei($DB, $fn, $now);
        $fenleiMap[$fn] = $res['id'];
        $res['created'] ? $createdFenlei++ : $skippedFenlei++;
    }

    $createdClass = 0;
    $updatedClass = 0;

    if ($copyMode === 'fenlei_and_class') {
        foreach ($rawList as $item) {
            if (empty($item['name']) || empty($item['cid'])) continue;
            $fn = !empty($item['fenleiname']) && isset($fenleiMap[trim($item['fenleiname'])]) ? $fenleiMap[trim($item['fenleiname'])] : '1';
            $isNew = db_upsert_docking_class($DB, $hid, $item, $fn, $now);
            $isNew ? $createdClass++ : $updatedClass++;
        }
    }

    $msg = "分类克隆完成：新建 {$createdFenlei} 个，已存在 {$skippedFenlei} 个。";
    if ($copyMode === 'fenlei_and_class') {
        $msg .= " 课程导入完成：新建 {$createdClass} 门，更新 {$updatedClass} 门。";
    }

    api_respond(0, $msg, array(
        'createdFenlei' => $createdFenlei,
        'skippedFenlei' => $skippedFenlei,
        'createdClass' => $createdClass,
        'updatedClass' => $updatedClass
    ));
}

if ($action === 'yjdj-batch-online') {
    api_require_post();
    api_require_super($userrow, $islogin);

    $input = api_read_input();
    $hid = isset($input['hid']) ? intval($input['hid']) : 0;
    $courses = isset($input['courses']) && is_array($input['courses']) ? $input['courses'] : array();
    $categoryMode = isset($input['categoryMode']) ? $input['categoryMode'] : 'default';

    if ($hid <= 0) api_respond(422, '请选择货源');
    if (empty($courses)) api_respond(422, '请勾选需要上架的课程');

    $huoyuan = $DB->get_row("SELECT * FROM qingka_wangke_huoyuan WHERE hid='$hid' AND status=1 LIMIT 1");
    if (!$huoyuan) api_respond(404, '货源不存在或已停用');

    $now = date('Y-m-d H:i:s');
    $finalCategoryId = null;

    if ($categoryMode === 'custom') {
        $customCategoryName = isset($input['customCategoryName']) ? trim(strip_tags((string) $input['customCategoryName'])) : '';
        if ($customCategoryName === '') api_respond(422, '请输入新分类名称');
        $finalCategoryId = db_get_or_create_fenlei($DB, $customCategoryName, $now)['id'];
    } elseif ($categoryMode === 'specified') {
        $targetCategoryId = isset($input['categoryId']) ? trim((string) $input['categoryId']) : '';
        if ($targetCategoryId === '') api_respond(422, '请选择指定分类');
        $finalCategoryId = $targetCategoryId;
    }

    $createdCount = 0;
    $updatedCount = 0;

    foreach ($courses as $course) {
        if (empty($course['name']) || empty($course['cid'])) continue;
        $fn = $finalCategoryId !== null ? $finalCategoryId : (!empty($course['fenleiname']) ? db_get_or_create_fenlei($DB, $course['fenleiname'], $now)['id'] : '1');
        $isNew = db_upsert_docking_class($DB, $hid, $course, $fn, $now);
        $isNew ? $createdCount++ : $updatedCount++;
    }

    api_respond(0, "批量上架完成：新增 {$createdCount} 门，更新 {$updatedCount} 门", array(
        'createdCount' => $createdCount,
        'updatedCount' => $updatedCount
    ));
}

// ------------------------------------------
// 5. 等级设置 (dengji)
// ------------------------------------------

if ($action === 'dengji-list') {
    api_require_super($userrow, $islogin);
    $res = $DB->query('SELECT * FROM qingka_wangke_dengji ORDER BY CAST(sort AS UNSIGNED) ASC, rate ASC');
    $list = array();
    while ($r = $DB->fetch($res)) {
        $list[] = array(
            'id' => (string) $r['id'],
            'sort' => intval($r['sort']),
            'name' => (string) $r['name'],
            'rate' => (string) $r['rate'],
            'money' => (string) $r['money'],
            'addkf' => intval($r['addkf']),
            'gjkf' => intval($r['gjkf']),
            'status' => intval($r['status']),
            'time' => (string) $r['time']
        );
    }
    api_respond(0, 'ok', array('list' => $list));
}

if ($action === 'dengji-save') {
    api_require_post();
    api_require_super($userrow, $islogin);
    $input = api_read_input();
    $id = isset($input['id']) ? intval($input['id']) : 0;
    $name = isset($input['name']) ? trim(strip_tags($input['name'])) : '';
    $sort = isset($input['sort']) ? intval($input['sort']) : 10;
    $rate = isset($input['rate']) ? trim($input['rate']) : '1.00';
    $money = isset($input['money']) ? trim($input['money']) : '0.00';
    $addkf = isset($input['addkf']) && intval($input['addkf']) === 1 ? 1 : 0;
    $gjkf = isset($input['gjkf']) && intval($input['gjkf']) === 1 ? 1 : 0;
    $status = isset($input['status']) && intval($input['status']) === 0 ? 0 : 1;

    if ($name === '') api_respond(422, '等级名称不能为空');

    $safeName = daddslashes($name);
    $safeRate = daddslashes($rate);
    $safeMoney = daddslashes($money);
    $now = date('Y-m-d H:i:s');

    if ($id > 0) {
        $DB->query("UPDATE qingka_wangke_dengji SET name='$safeName', sort='$sort', rate='$safeRate', money='$safeMoney', addkf='$addkf', gjkf='$gjkf', status='$status' WHERE id='$id'");
        api_respond(0, '等级修改成功');
    } else {
        $DB->query("INSERT INTO qingka_wangke_dengji (sort, name, rate, money, addkf, gjkf, status, time) VALUES ('$sort', '$safeName', '$safeRate', '$safeMoney', '$addkf', '$gjkf', '$status', '$now')");
        api_respond(0, '等级添加成功');
    }
}

if ($action === 'dengji-delete') {
    api_require_post();
    api_require_super($userrow, $islogin);
    $id = isset(api_read_input()['id']) ? intval(api_read_input()['id']) : 0;
    if ($id <= 0) api_respond(422, '请选择要删除的等级');
    $DB->query("DELETE FROM qingka_wangke_dengji WHERE id='$id'");
    api_respond(0, '等级删除成功');
}

// ------------------------------------------
// 6. 密价设置 (mijia)
// ------------------------------------------

if ($action === 'mijia-list') {
    api_require_super($userrow, $islogin);
    $uid = isset($_GET['uid']) ? trim(strip_tags($_GET['uid'])) : '';
    $cid = isset($_GET['cid']) ? trim(strip_tags($_GET['cid'])) : '';
    $where = array('1=1');
    if ($uid !== '') $where[] = "m.uid='" . daddslashes($uid) . "'";
    if ($cid !== '') $where[] = "m.cid='" . daddslashes($cid) . "'";
    $sql = "SELECT m.*, c.name AS class_name, u.user AS user_name, u.name AS display_name "
        . "FROM qingka_wangke_mijia m "
        . "LEFT JOIN qingka_wangke_class c ON m.cid = c.cid "
        . "LEFT JOIN qingka_wangke_user u ON m.uid = u.uid "
        . "WHERE " . implode(' AND ', $where) . " ORDER BY m.mid DESC";
    $res = $DB->query($sql);
    $list = array();
    while ($r = $DB->fetch($res)) {
        $list[] = array(
            'mid' => (string) $r['mid'],
            'uid' => (string) $r['uid'],
            'userName' => (string) ($r['display_name'] ? $r['display_name'] : $r['user_name']),
            'cid' => (string) $r['cid'],
            'className' => (string) ($r['class_name'] ? $r['class_name'] : 'ID:' . $r['cid']),
            'mode' => intval($r['mode']),
            'price' => (string) $r['price'],
            'addtime' => (string) $r['addtime']
        );
    }
    api_respond(0, 'ok', array('list' => $list));
}

if ($action === 'mijia-save') {
    api_require_post();
    api_require_super($userrow, $islogin);
    $input = api_read_input();
    $mid = isset($input['mid']) ? intval($input['mid']) : 0;
    $uid = isset($input['uid']) ? intval($input['uid']) : 0;
    $cid = isset($input['cid']) ? intval($input['cid']) : 0;
    $mode = isset($input['mode']) ? intval($input['mode']) : 0;
    $price = isset($input['price']) ? trim($input['price']) : '0';

    if ($uid <= 0) api_respond(422, '请输入有效的用户UID');
    if ($cid <= 0) api_respond(422, '请选择商品课程');

    $safePrice = daddslashes($price);
    $now = date('Y-m-d H:i:s');

    if ($mid > 0) {
        $DB->query("UPDATE qingka_wangke_mijia SET uid='$uid', cid='$cid', mode='$mode', price='$safePrice' WHERE mid='$mid'");
        api_respond(0, '密价修改成功');
    } else {
        $DB->query("INSERT INTO qingka_wangke_mijia (uid, cid, mode, price, addtime) VALUES ('$uid', '$cid', '$mode', '$safePrice', '$now')");
        api_respond(0, '密价添加成功');
    }
}

if ($action === 'mijia-delete') {
    api_require_post();
    api_require_super($userrow, $islogin);
    $mid = isset(api_read_input()['mid']) ? intval(api_read_input()['mid']) : 0;
    if ($mid <= 0) api_respond(422, '请选择要删除的密价记录');
    $DB->query("DELETE FROM qingka_wangke_mijia WHERE mid='$mid'");
    api_respond(0, '密价删除成功');
}

// ------------------------------------------
// 7. 支付订单 (paylist)
// ------------------------------------------

if ($action === 'paylist-list') {
    api_require_super($userrow, $islogin);
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $pageSize = isset($_GET['pageSize']) ? max(10, min(200, intval($_GET['pageSize']))) : 20;
    $offset = ($page - 1) * $pageSize;
    $keyword = isset($_GET['keyword']) ? trim(strip_tags($_GET['keyword'])) : '';
    $status = isset($_GET['status']) && $_GET['status'] !== '' ? intval($_GET['status']) : null;
    $type = isset($_GET['type']) ? trim(strip_tags($_GET['type'])) : '';

    $where = array('1=1');
    if ($keyword !== '') {
        $kw = daddslashes($keyword);
        $where[] = "(out_trade_no LIKE '%$kw%' OR trade_no LIKE '%$kw%' OR name LIKE '%$kw%' OR uid='$kw')";
    }
    if ($status !== null) {
        $where[] = "status='$status'";
    }
    if ($type !== '') {
        $where[] = "type='" . daddslashes($type) . "'";
    }

    $whereStr = ' WHERE ' . implode(' AND ', $where);
    $total = $DB->count("SELECT COUNT(*) FROM qingka_wangke_pay $whereStr");
    $res = $DB->query("SELECT * FROM qingka_wangke_pay $whereStr ORDER BY oid DESC LIMIT $offset, $pageSize");
    $records = array();
    while ($r = $DB->fetch($res)) {
        $records[] = array(
            'oid' => (string) $r['oid'],
            'outTradeNo' => (string) $r['out_trade_no'],
            'tradeNo' => (string) $r['trade_no'],
            'type' => (string) $r['type'],
            'uid' => (string) $r['uid'],
            'name' => (string) $r['name'],
            'money' => (string) $r['money'],
            'status' => intval($r['status']),
            'addtime' => (string) $r['addtime'],
            'endtime' => (string) $r['endtime']
        );
    }
    api_respond(0, 'ok', array(
        'records' => $records,
        'current' => $page,
        'size' => $pageSize,
        'total' => intval($total)
    ));
}

// ------------------------------------------
// 8. 充值卡密 (guanx)
// ------------------------------------------

if ($action === 'guanx-list') {
    api_require_super($userrow, $islogin);
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $pageSize = isset($_GET['pageSize']) ? max(10, min(500, intval($_GET['pageSize']))) : 50;
    $offset = ($page - 1) * $pageSize;
    $status = isset($_GET['status']) && $_GET['status'] !== '' ? intval($_GET['status']) : null;
    $batchId = isset($_GET['batchId']) && $_GET['batchId'] !== '' ? intval($_GET['batchId']) : null;
    $keyword = isset($_GET['keyword']) ? trim(strip_tags($_GET['keyword'])) : '';

    $where = array('1=1');
    if ($status !== null) $where[] = "status='$status'";
    if ($batchId !== null) $where[] = "batch_id='$batchId'";
    if ($keyword !== '') {
        $kw = daddslashes($keyword);
        $where[] = "(content LIKE '%$kw%' OR uid='$kw')";
    }

    $whereStr = ' WHERE ' . implode(' AND ', $where);
    $total = $DB->count("SELECT COUNT(*) FROM qingka_wangke_km $whereStr");
    $res = $DB->query("SELECT * FROM qingka_wangke_km $whereStr ORDER BY id DESC LIMIT $offset, $pageSize");
    $records = array();
    while ($r = $DB->fetch($res)) {
        $records[] = array(
            'id' => (string) $r['id'],
            'content' => (string) $r['content'],
            'money' => intval($r['money']),
            'status' => intval($r['status']),
            'uid' => (string) $r['uid'],
            'batchId' => intval($r['batch_id']),
            'addtime' => (string) $r['addtime'],
            'usedtime' => (string) $r['usedtime']
        );
    }
    api_respond(0, 'ok', array(
        'records' => $records,
        'current' => $page,
        'size' => $pageSize,
        'total' => intval($total)
    ));
}

if ($action === 'guanx-generate') {
    api_require_post();
    api_require_super($userrow, $islogin);
    $input = api_read_input();
    $num = isset($input['num']) ? max(1, min(500, intval($input['num']))) : 1;
    $money = isset($input['money']) ? max(1, intval($input['money'])) : 10;
    $batchId = isset($input['batchId']) && intval($input['batchId']) > 0 ? intval($input['batchId']) : intval(date('mdHi'));

    $prefix = date('md');
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $charsLen = strlen($chars) - 1;
    $now = date('Y-m-d H:i:s');

    $generated = array();
    for ($i = 0; $i < $num; $i++) {
        $randStr = '';
        for ($j = 0; $j < 14; $j++) {
            $randStr .= $chars[mt_rand(0, $charsLen)];
        }
        $code = $prefix . $randStr . '_' . $money;
        $DB->query("INSERT INTO qingka_wangke_km (content, money, status, addtime, batch_id) VALUES ('$code', '$money', 0, '$now', '$batchId')");
        $generated[] = $code;
    }

    api_respond(0, "成功生成 {$num} 张面值 ¥{$money} 的卡密", array(
        'count' => $num,
        'batchId' => $batchId,
        'cards' => $generated
    ));
}

if ($action === 'guanx-delete') {
    api_require_post();
    api_require_super($userrow, $islogin);
    $input = api_read_input();
    $ids = array();
    if (isset($input['ids']) && is_array($input['ids'])) {
        foreach ($input['ids'] as $v) if (intval($v) > 0) $ids[] = intval($v);
    } elseif (isset($input['id']) && intval($input['id']) > 0) {
        $ids[] = intval($input['id']);
    }
    if (empty($ids)) api_respond(422, '请选择要删除的卡密');
    $idList = implode(',', $ids);
    $DB->query("DELETE FROM qingka_wangke_km WHERE id IN ($idList)");
    api_respond(0, '删除成功，共删除 ' . count($ids) . ' 条记录');
}

// ------------------------------------------
// 9. 公告列表 (gglist)
// ------------------------------------------

if ($action === 'gglist-list') {
    api_require_login(isset($islogin) ? $islogin : 0);
    $currentUid = intval($userrow['uid']);
    $where = ($currentUid === 1) ? '' : 'WHERE status=1';
    $res = $DB->query("SELECT * FROM qingka_wangke_gonggao $where ORDER BY CAST(zhiding AS UNSIGNED) DESC, id DESC");
    $list = array();
    while ($r = $DB->fetch($res)) {
        $list[] = array(
            'id' => (string) $r['id'],
            'title' => (string) $r['title'],
            'content' => (string) $r['content'],
            'time' => (string) $r['time'],
            'uid' => (string) $r['uid'],
            'status' => intval($r['status']),
            'zhiding' => intval($r['zhiding'])
        );
    }
    api_respond(0, 'ok', array('list' => $list));
}

if ($action === 'gglist-save') {
    api_require_post();
    api_require_super($userrow, $islogin);
    $input = api_read_input();
    $id = isset($input['id']) ? intval($input['id']) : 0;
    $title = isset($input['title']) ? trim(strip_tags($input['title'])) : '';
    $content = isset($input['content']) ? trim($input['content']) : '';
    $status = isset($input['status']) && intval($input['status']) === 0 ? 0 : 1;
    $zhiding = isset($input['zhiding']) && intval($input['zhiding']) === 1 ? 1 : 0;

    if ($title === '') api_respond(422, '公告标题不能为空');
    if ($content === '') api_respond(422, '公告内容不能为空');

    $safeTitle = daddslashes($title);
    $safeContent = daddslashes($content);
    $now = date('Y-m-d H:i:s');
    $uid = intval($userrow['uid']);

    if ($id > 0) {
        $DB->query("UPDATE qingka_wangke_gonggao SET title='$safeTitle', content='$safeContent', status='$status', zhiding='$zhiding' WHERE id='$id'");
        api_respond(0, '公告修改成功');
    } else {
        $DB->query("INSERT INTO qingka_wangke_gonggao (title, content, status, zhiding, uid, time) VALUES ('$safeTitle', '$safeContent', '$status', '$zhiding', '$uid', '$now')");
        api_respond(0, '公告发布成功');
    }
}

if ($action === 'gglist-delete') {
    api_require_post();
    api_require_super($userrow, $islogin);
    $id = isset(api_read_input()['id']) ? intval(api_read_input()['id']) : 0;
    if ($id <= 0) api_respond(422, '请选择要删除的公告');
    $DB->query("DELETE FROM qingka_wangke_gonggao WHERE id='$id'");
    api_respond(0, '公告删除成功');
}

// ------------------------------------------
// 10. 今日数据 (data)
// ------------------------------------------

if ($action === 'data-stats') {
    api_require_super($userrow, $islogin);
    $todayStart = date('Y-m-d 00:00:00');
    $todayEnd = date('Y-m-d 23:59:59');
    $yesterdayStart = date('Y-m-d 00:00:00', strtotime('yesterday'));
    $yesterdayEnd = date('Y-m-d 23:59:59', strtotime('yesterday'));
    $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));

    $totalUsers = $DB->count('SELECT count(*) FROM qingka_wangke_user');
    $todayUsers = $DB->count("SELECT count(*) FROM qingka_wangke_user WHERE addtime >= '$todayStart' AND addtime <= '$todayEnd'");
    $totalOrders = $DB->count('SELECT count(*) FROM qingka_wangke_order');
    $todayOrders = $DB->count("SELECT count(*) FROM qingka_wangke_order WHERE addtime >= '$todayStart' AND addtime <= '$todayEnd'");
    $yesterdayOrders = $DB->count("SELECT count(*) FROM qingka_wangke_order WHERE addtime >= '$yesterdayStart' AND addtime <= '$yesterdayEnd'");
    $sevenDaysOrders = $DB->count("SELECT count(*) FROM qingka_wangke_order WHERE date(addtime) >= '$sevenDaysAgo'");

    $todaySalesRow = $DB->get_row("SELECT COALESCE(SUM(fees), 0) AS total FROM qingka_wangke_order WHERE addtime >= '$todayStart' AND addtime <= '$todayEnd'");
    $todaySales = number_format(floatval($todaySalesRow['total']), 2, '.', '');

    $yesterdaySalesRow = $DB->get_row("SELECT COALESCE(SUM(fees), 0) AS total FROM qingka_wangke_order WHERE addtime >= '$yesterdayStart' AND addtime <= '$yesterdayEnd'");
    $yesterdaySales = number_format(floatval($yesterdaySalesRow['total']), 2, '.', '');

    $todayPayRow = $DB->get_row("SELECT COALESCE(SUM(money), 0) AS total FROM qingka_wangke_pay WHERE status=1 AND addtime >= '$todayStart'");
    $todayPay = number_format(floatval($todayPayRow['total']), 2, '.', '');

    api_respond(0, 'ok', array(
        'totalUsers' => intval($totalUsers),
        'todayUsers' => intval($todayUsers),
        'totalOrders' => intval($totalOrders),
        'todayOrders' => intval($todayOrders),
        'yesterdayOrders' => intval($yesterdayOrders),
        'sevenDaysOrders' => intval($sevenDaysOrders),
        'todaySales' => $todaySales,
        'yesterdaySales' => $yesterdaySales,
        'todayRecharge' => $todayPay
    ));
}

// ------------------------------------------
// 11. 货源统计 (ddtj)
// ------------------------------------------

if ($action === 'ddtj-stats') {
    api_require_super($userrow, $islogin);
    $todayStart = date('Y-m-d 00:00:00');
    $yesterdayStart = date('Y-m-d 00:00:00', strtotime('yesterday'));
    $yesterdayEnd = date('Y-m-d 23:59:59', strtotime('yesterday'));
    $weekStart = date('Y-m-d', strtotime('monday this week'));

    $hwSql = "SELECT hw.name, "
        . "(SELECT COUNT(*) FROM qingka_wangke_order WHERE hid = hw.hid AND addtime >= '$todayStart') AS today_count, "
        . "(SELECT COUNT(*) FROM qingka_wangke_order WHERE hid = hw.hid AND addtime >= '$yesterdayStart' AND addtime <= '$yesterdayEnd') AS yesterday_count, "
        . "(SELECT COUNT(*) FROM qingka_wangke_order WHERE hid = hw.hid AND addtime >= '$weekStart') AS week_count, "
        . "(SELECT COUNT(*) FROM qingka_wangke_order WHERE hid = hw.hid AND MONTH(addtime) = MONTH(CURDATE()) AND YEAR(addtime) = YEAR(CURDATE())) AS month_count, "
        . "COUNT(o.oid) AS total_count, "
        . "MAX(o.addtime) AS latest_order_time "
        . "FROM qingka_wangke_huoyuan hw "
        . "LEFT JOIN qingka_wangke_order o ON hw.hid = o.hid "
        . "WHERE hw.status=1 GROUP BY hw.hid ORDER BY total_count DESC, latest_order_time DESC";

    $hwRes = $DB->query($hwSql);
    $huoyuanRank = array();
    while ($r = $DB->fetch($hwRes)) {
        $huoyuanRank[] = array(
            'name' => (string) $r['name'],
            'today' => intval($r['today_count']),
            'yesterday' => intval($r['yesterday_count']),
            'week' => intval($r['week_count']),
            'month' => intval($r['month_count']),
            'total' => intval($r['total_count']),
            'latest' => isset($r['latest_order_time']) ? (string) $r['latest_order_time'] : '-'
        );
    }

    $ptSql = "SELECT ptname, "
        . "SUM(CASE WHEN addtime >= '$todayStart' THEN 1 ELSE 0 END) AS today_count, "
        . "SUM(CASE WHEN addtime >= '$yesterdayStart' AND addtime <= '$yesterdayEnd' THEN 1 ELSE 0 END) AS yesterday_count, "
        . "SUM(CASE WHEN addtime >= '$weekStart' THEN 1 ELSE 0 END) AS week_count, "
        . "SUM(CASE WHEN MONTH(addtime) = MONTH(CURDATE()) AND YEAR(addtime) = YEAR(CURDATE())) AS month_count, "
        . "COUNT(*) AS total_count, "
        . "MAX(addtime) AS latest_order_time "
        . "FROM qingka_wangke_order WHERE ptname IS NOT NULL AND ptname != '' "
        . "GROUP BY ptname ORDER BY total_count DESC";

    $ptRes = $DB->query($ptSql);
    $platformRank = array();
    while ($r = $DB->fetch($ptRes)) {
        $platformRank[] = array(
            'name' => (string) $r['ptname'],
            'today' => intval($r['today_count']),
            'yesterday' => intval($r['yesterday_count']),
            'week' => intval($r['week_count']),
            'month' => intval($r['month_count']),
            'total' => intval($r['total_count']),
            'latest' => isset($r['latest_order_time']) ? (string) $r['latest_order_time'] : '-'
        );
    }

    api_respond(0, 'ok', array(
        'huoyuanRank' => $huoyuanRank,
        'platformRank' => $platformRank
    ));
}

// ------------------------------------------
// 12. 站长帮助 (zzbz)
// ------------------------------------------

if ($action === 'zzbz-info') {
    api_require_super($userrow, $islogin);
    $scheme = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'sk.yunxnet.cn';
    $baseUrl = $scheme . '://' . $host;
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '/www/wwwroot/sk.yunxnet.cn';

    $crons = array(
        array('title' => '玉帝实时进度', 'cycle' => '1-5 分钟 1 次', 'url' => $baseUrl . '/cron/YD.php'),
        array('title' => '邀请次数上限', 'cycle' => '每天 00:10 执行 1 次', 'url' => $baseUrl . '/cron/yqm.php'),
        array('title' => '提交入队', 'cycle' => '1 分钟 1 次', 'url' => $baseUrl . '/redis/addru.php'),
        array('title' => '实时入队', 'cycle' => '1 分钟 1 次', 'url' => $baseUrl . '/redis/ccru.php'),
        array('title' => '批量补刷入队', 'cycle' => '1 分钟 1 次', 'url' => $baseUrl . '/redis/plbsru.php'),
        array('title' => '批量刷新入队', 'cycle' => '1 分钟 1 次', 'url' => $baseUrl . '/redis/plsxru.php'),
        array('title' => '补刷入队', 'cycle' => '1 分钟 1 次', 'url' => $baseUrl . '/redis/bsru.php'),
        array('title' => '价格同步', 'cycle' => '建议每日定时执行', 'url' => $baseUrl . '/cron/updateprice.php')
    );

    $daemons = array(
        array('name' => '提交出队 (add)', 'count' => 1, 'cmd' => 'nohup php addchu.php &', 'dir' => $docRoot . '/redis'),
        array('name' => '批量补刷 (plbs)', 'count' => 5, 'cmd' => 'nohup php plbschu.php &', 'dir' => $docRoot . '/redis'),
        array('name' => '批量刷新 (plsx)', 'count' => 5, 'cmd' => 'nohup php plsxchu.php &', 'dir' => $docRoot . '/redis'),
        array('name' => '补刷出队 (bs)', 'count' => 5, 'cmd' => 'nohup php bschu.php &', 'dir' => $docRoot . '/redis'),
        array('name' => '实时出队 (cc)', 'count' => 10, 'cmd' => 'nohup php ccchu.php &', 'dir' => $docRoot . '/redis')
    );

    api_respond(0, 'ok', array('crons' => $crons, 'daemons' => $daemons));
}

// ------------------------------------------
// 13. 系统信息 (webmsg)
// ------------------------------------------

if ($action === 'webmsg-info') {
    api_require_super($userrow, $islogin);
    $serverIp = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : (isset($_SERVER['LOCAL_ADDR']) ? $_SERVER['LOCAL_ADDR'] : '127.0.0.1');
    $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

    $systemInfo = array(
        'appName' => isset($conf['sitename']) && $conf['sitename'] ? (string) $conf['sitename'] : '网课管理中心',
        'author' => 'SkyLearn',
        'version' => '7.0.9',
        'domain' => $domain,
        'serverIp' => $serverIp,
        'phpVersion' => PHP_VERSION,
        'os' => PHP_OS
    );

    $timeline = array(
        array('version' => 'v7.0.9', 'time' => '2026-09-09', 'desc' => '全站设置中心现代化微前端重构上线；重构分级菜单导航；完善分类、网课、接口配置及一键对接链路。'),
        array('version' => 'v7.0.8', 'time' => '2025-10-18', 'desc' => '优化查课交单接口稳定性与并发处理能力，完善密价及代理折扣算法。'),
        array('version' => 'v7.0.0', 'time' => '2024-06-09', 'desc' => '全新 7.0 架构升级，引入 Redis 出入队异步任务体系，强化接口防重复下单机制。')
    );

    api_respond(0, 'ok', array('systemInfo' => $systemInfo, 'timeline' => $timeline));
}

// ==========================================
// 我的信息与代理管理接口
// ==========================================

if ($action === 'userlist-list') {
    api_require_login(isset($islogin) ? $islogin : 0);
    $currentUid = intval($userrow['uid']);
    $isSuper = ($currentUid === 1);

    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $pageSize = isset($_GET['pageSize']) ? max(10, min(500, intval($_GET['pageSize']))) : 20;
    $offset = ($page - 1) * $pageSize;
    $keyword = isset($_GET['keyword']) ? trim(strip_tags($_GET['keyword'])) : '';
    $status = isset($_GET['status']) && $_GET['status'] !== '' ? intval($_GET['status']) : null;

    // 严密数据隔离：超级管理员看全网商户，普通代理仅看自己名下的下级代理！
    $where = array($isSuper ? '1=1' : "uuid='$currentUid'");
    if ($keyword !== '') {
        $kw = daddslashes($keyword);
        $where[] = "(user LIKE '%$kw%' OR name LIKE '%$kw%' OR uid='$kw' OR yqm LIKE '%$kw%')";
    }
    if ($status !== null) {
        $where[] = "active='$status'";
    }

    $whereStr = ' WHERE ' . implode(' AND ', $where);
    $total = $DB->count("SELECT COUNT(*) FROM qingka_wangke_user $whereStr");
    $res = $DB->query("SELECT * FROM qingka_wangke_user $whereStr ORDER BY uid DESC LIMIT $offset, $pageSize");
    $records = array();
    while ($r = $DB->fetch($res)) {
        $records[] = array(
            'uid' => (string) $r['uid'],
            'uuid' => (string) $r['uuid'],
            'user' => (string) $r['user'],
            'name' => (string) $r['name'],
            'addprice' => (string) $r['addprice'],
            'money' => (string) $r['money'],
            'zcz' => (string) $r['zcz'],
            'yqm' => (string) $r['yqm'],
            'active' => intval($r['active']),
            'key' => (string) $r['key'],
            'addtime' => (string) $r['addtime'],
            'endtime' => (string) $r['endtime']
        );
    }
    api_respond(0, 'ok', array(
        'records' => $records,
        'current' => $page,
        'size' => $pageSize,
        'total' => intval($total),
        'is_admin' => $isSuper
    ));
}

if ($action === 'userlist-status') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();

    $currentUid = intval($userrow['uid']);
    $isSuper = ($currentUid === 1);
    $input = api_read_input();
    $uid = isset($input['uid']) ? intval($input['uid']) : 0;
    $active = isset($input['active']) && intval($input['active']) === 1 ? 1 : 0;
    if ($uid <= 0) api_respond(422, '用户参数错误');

    if (!$isSuper) {
        $target = $DB->get_row("SELECT uuid FROM qingka_wangke_user WHERE uid='$uid' LIMIT 1");
        if (!$target || intval($target['uuid']) !== $currentUid) {
            api_respond(403, '只能管理属于您名下的直属下级代理');
        }
    }

    $DB->query("UPDATE qingka_wangke_user SET active='$active' WHERE uid='$uid'");
    api_respond(0, $active === 1 ? '已解封该代理账号' : '已封禁该代理账号');
}

if ($action === 'userlist-recharge') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();

    $currentUid = intval($userrow['uid']);
    $isSuper = ($currentUid === 1);
    $input = api_read_input();
    $uid = isset($input['uid']) ? intval($input['uid']) : 0;
    $amount = isset($input['amount']) ? floatval($input['amount']) : 0;
    if ($uid <= 0) api_respond(422, '用户参数错误');
    if ($amount == 0) api_respond(422, '充值金额不能为0');

    $target = $DB->get_row("SELECT uid, uuid, user, name, money FROM qingka_wangke_user WHERE uid='$uid' LIMIT 1");
    if (!$target) api_respond(404, '目标代理不存在');

    if ($isSuper) {
        // 超管自由调账运维
        $newMoney = round($target['money'] + $amount, 2);
        if ($newMoney < 0) api_respond(400, '扣款后余额不能为负数');

        $setSql = "money='$newMoney'";
        if ($amount > 0) $setSql .= ", zcz=zcz+'$amount'";
        $DB->query("UPDATE qingka_wangke_user SET $setSql WHERE uid='$uid'");

        $sign = $amount > 0 ? "+$amount" : "$amount";
        if (function_exists('wlog')) {
            wlog($uid, "管理员调账", "管理员调整余额: {$sign} 元，当前余额 {$newMoney} 元", $sign);
        }
        api_respond(0, "调整成功，目标代理当前余额: ¥ {$newMoney}");
    } else {
        // 普通代理为下级充值转账
        if (intval($target['uuid']) !== $currentUid) {
            api_respond(403, '只能为属于您名下的直属下级代理充值');
        }
        if ($amount <= 0) {
            api_respond(422, '充值金额必须大于 0');
        }
        $myMoney = floatval($userrow['money']);
        if ($myMoney < $amount) {
            api_respond(400, "您的可用余额不足！当前余额: ¥ {$myMoney}，充值需要: ¥ {$amount}");
        }

        // 扣除当前代理自身余额，充入下级账户
        $DB->query("UPDATE qingka_wangke_user SET money=money-'$amount' WHERE uid='$currentUid' LIMIT 1");
        $DB->query("UPDATE qingka_wangke_user SET money=money+'$amount', zcz=zcz+'$amount' WHERE uid='$uid' LIMIT 1");

        $newTargetMoney = round(floatval($target['money']) + $amount, 2);
        if (function_exists('wlog')) {
            wlog($currentUid, "代理充值", "为直属下级 {$target['name']}({$target['user']}) 充值 {$amount} 元", -$amount);
            wlog($uid, "上级充值", "上级 {$userrow['name']}({$userrow['user']}) 为您充值 {$amount} 元", +$amount);
        }
        api_respond(0, "充值成功！已从您的账户划扣 ¥ {$amount}，目标下级当前余额: ¥ {$newTargetMoney}");
    }
}

if ($action === 'userlist-rate') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();

    $currentUid = intval($userrow['uid']);
    $isSuper = ($currentUid === 1);
    $input = api_read_input();
    $uid = isset($input['uid']) ? intval($input['uid']) : 0;
    $rate = isset($input['rate']) ? trim($input['rate']) : '';
    if ($uid <= 0 || $rate === '') api_respond(422, '参数错误');
    $rateNum = floatval($rate);
    if ($rateNum < 0.01 || $rateNum > 10.0) api_respond(422, '费率系数不合理');

    $target = $DB->get_row("SELECT uid, uuid, user, name, addprice FROM qingka_wangke_user WHERE uid='$uid' LIMIT 1");
    if (!$target) api_respond(404, '目标代理不存在');

    if (!$isSuper) {
        if (intval($target['uuid']) !== $currentUid) {
            api_respond(403, '只能修改属于您名下的直属下级代理费率');
        }
        $myRate = floatval($userrow['addprice']);
        if ($rateNum < $myRate) {
            api_respond(400, "下级成本费率不能低于您自身的费率 ({$myRate}×)");
        }
    }

    $safeRate = daddslashes($rate);
    $DB->query("UPDATE qingka_wangke_user SET addprice='$safeRate' WHERE uid='$uid'");
    api_respond(0, '费率修改成功');
}

if ($action === 'class-latest') {
    api_require_login(isset($islogin) ? $islogin : 0);
    $res = $DB->query("SELECT c.*, f.name AS fenlei_name FROM qingka_wangke_class c LEFT JOIN qingka_wangke_fenlei f ON c.fenlei=f.id WHERE c.status=1 AND c.addtime >= DATE_SUB(NOW(), INTERVAL 8 DAY) ORDER BY c.addtime DESC, c.cid DESC");
    $list = array();
    while ($r = $DB->fetch($res)) {
        $list[] = array(
            'cid' => (string) $r['cid'],
            'name' => (string) $r['name'],
            'price' => (string) $r['price'],
            'fenlei' => (string) ($r['fenlei_name'] ? $r['fenlei_name'] : '未分类'),
            'addtime' => (string) $r['addtime']
        );
    }
    api_respond(0, 'ok', array('list' => $list));
}

if ($action === 'class-offline') {
    api_require_login(isset($islogin) ? $islogin : 0);
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $pageSize = 20;
    $offset = ($page - 1) * $pageSize;
    $total = $DB->count("SELECT COUNT(*) FROM qingka_wangke_class WHERE status=0");
    $res = $DB->query("SELECT c.*, f.name AS fenlei_name FROM qingka_wangke_class c LEFT JOIN qingka_wangke_fenlei f ON c.fenlei=f.id WHERE c.status=0 ORDER BY c.cid DESC LIMIT $offset, $pageSize");
    $records = array();
    while ($r = $DB->fetch($res)) {
        $records[] = array(
            'cid' => (string) $r['cid'],
            'courseName' => (string) $r['name'],
            'categoryId' => (string) $r['fenlei'],
            'categoryName' => (string) ($r['fenlei_name'] ? $r['fenlei_name'] : '未分类'),
            'content' => (string) $r['content']
        );
    }
    api_respond(0, 'ok', array('records' => $records, 'current' => $page, 'size' => $pageSize, 'total' => intval($total)));
}

if ($action === 'rank-stats') {
    api_require_login(isset($islogin) ? $islogin : 0);
    // 玩家排行 (最近90天)
    $userRankRes = $DB->query("SELECT u.name, u.user, COUNT(o.oid) AS order_count FROM qingka_wangke_order o JOIN qingka_wangke_user u ON o.uid=u.uid WHERE o.addtime >= DATE_SUB(NOW(), INTERVAL 90 DAY) GROUP BY o.uid ORDER BY order_count DESC LIMIT 20");
    $userRank = array();
    while ($r = $DB->fetch($userRankRes)) {
        $raw = $r['name'] ? $r['name'] : $r['user'];
        $len = mb_strlen($raw);
        $masked = $len > 4 ? mb_substr($raw, 0, 2) . '***' . mb_substr($raw, -2) : $raw;
        $userRank[] = array('name' => $masked, 'orderCount' => intval($r['order_count']));
    }

    // 热门课程 (最近30天)
    $courseRankRes = $DB->query("SELECT ptname, kcname, COUNT(*) AS order_count FROM qingka_wangke_order WHERE addtime >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND kcname IS NOT NULL AND kcname != '' GROUP BY kcname ORDER BY order_count DESC LIMIT 20");
    $courseRank = array();
    while ($r = $DB->fetch($courseRankRes)) {
        $courseRank[] = array(
            'platform' => (string) $r['ptname'],
            'courseName' => (string) $r['kcname'],
            'orderCount' => intval($r['order_count'])
        );
    }

    // 本周充值排行榜 (受 czph 控制)
    $czRank = array();
    $czEnabled = isset($conf['czph']) && intval($conf['czph']) === 1;
    if ($czEnabled) {
        $czRes = $DB->query("SELECT u.name, u.user, SUM(l.money) AS total_money FROM qingka_wangke_log l JOIN qingka_wangke_user u ON l.uid = u.uid WHERE YEARWEEK(l.addtime, 1) = YEARWEEK(CURDATE(), 1) AND l.money > 0 GROUP BY l.uid ORDER BY total_money DESC LIMIT 15");
        while ($r = $DB->fetch($czRes)) {
            $raw = $r['name'] ? $r['name'] : $r['user'];
            $len = mb_strlen($raw);
            $masked = $len > 4 ? mb_substr($raw, 0, 2) . '***' . mb_substr($raw, -2) : $raw;
            $czRank[] = array(
                'name' => $masked,
                'money' => number_format(floatval($r['total_money']), 2, '.', '')
            );
        }
    }

    api_respond(0, 'ok', array(
        'userRank' => $userRank,
        'courseRank' => $courseRank,
        'rechargeRank' => $czRank,
        'czEnabled' => $czEnabled
    ));
}

if ($action === 'kcid-compare') {
    api_require_login(isset($islogin) ? $islogin : 0);
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $pageSize = 20;
    $offset = ($page - 1) * $pageSize;
    $keyword = isset($_GET['keyword']) ? trim(strip_tags($_GET['keyword'])) : '';
    $uid = intval($userrow['uid']);

    $where = array($uid === 1 ? '1=1' : "uid='$uid'");
    if ($keyword !== '') {
        $kw = daddslashes($keyword);
        $where[] = "(oid LIKE '%$kw%' OR user LIKE '%$kw%' OR kcname LIKE '%$kw%' OR kcid LIKE '%$kw%')";
    }
    $whereStr = ' WHERE ' . implode(' AND ', $where);
    $total = $DB->count("SELECT COUNT(*) FROM qingka_wangke_order $whereStr");
    $res = $DB->query("SELECT oid, user, ptname, kcname, kcid, process, status, addtime FROM qingka_wangke_order $whereStr ORDER BY oid DESC LIMIT $offset, $pageSize");
    $records = array();
    while ($r = $DB->fetch($res)) {
        $records[] = array(
            'oid' => (string) $r['oid'],
            'user' => (string) $r['user'],
            'platform' => (string) $r['ptname'],
            'courseName' => (string) $r['kcname'],
            'kcid' => (string) $r['kcid'],
            'progress' => (string) $r['process'],
            'status' => (string) $r['status'],
            'addtime' => (string) $r['addtime']
        );
    }
    api_respond(0, 'ok', array('records' => $records, 'current' => $page, 'size' => $pageSize, 'total' => intval($total)));
}

if ($action === 'order-available') {
    api_require_login(isset($islogin) ? $islogin : 0);
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $pageSize = 20;
    $offset = ($page - 1) * $pageSize;
    $keyword = isset($_GET['keyword']) ? trim(strip_tags($_GET['keyword'])) : '';

    $where = array('1=1');
    if ($keyword !== '') {
        $kw = daddslashes($keyword);
        $where[] = "(ptname LIKE '%$kw%' OR kcname LIKE '%$kw%' OR remarks LIKE '%$kw%')";
    }
    $whereStr = ' WHERE ' . implode(' AND ', $where);
    $total = $DB->count("SELECT COUNT(*) FROM qingka_wangke_order $whereStr");
    $res = $DB->query("SELECT oid, ptname, kcname, status, process, remarks, addtime FROM qingka_wangke_order $whereStr ORDER BY oid DESC LIMIT $offset, $pageSize");
    $records = array();
    while ($r = $DB->fetch($res)) {
        $records[] = array(
            'oid' => (string) $r['oid'],
            'platform' => (string) $r['ptname'],
            'courseName' => (string) $r['kcname'],
            'status' => (string) $r['status'],
            'progress' => (string) $r['process'],
            'remarks' => (string) $r['remarks'],
            'addtime' => (string) $r['addtime']
        );
    }
    api_respond(0, 'ok', array('records' => $records, 'current' => $page, 'size' => $pageSize, 'total' => intval($total)));
}

if ($action === 'log-list') {
    api_require_login(isset($islogin) ? $islogin : 0);
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $pageSize = 20;
    $offset = ($page - 1) * $pageSize;
    $type = isset($_GET['type']) ? trim(strip_tags($_GET['type'])) : '';
    $keyword = isset($_GET['keyword']) ? trim(strip_tags($_GET['keyword'])) : '';
    $uid = intval($userrow['uid']);

    $where = array($uid === 1 ? '1=1' : "uid='$uid'");
    if ($type !== '') $where[] = "type='" . daddslashes($type) . "'";
    if ($keyword !== '') {
        $kw = daddslashes($keyword);
        $where[] = "(text LIKE '%$kw%' OR uid='$kw')";
    }
    $whereStr = ' WHERE ' . implode(' AND ', $where);
    $total = $DB->count("SELECT COUNT(*) FROM qingka_wangke_log $whereStr");
    $res = $DB->query("SELECT * FROM qingka_wangke_log $whereStr ORDER BY id DESC LIMIT $offset, $pageSize");
    $records = array();
    while ($r = $DB->fetch($res)) {
        $records[] = array(
            'id' => (string) $r['id'],
            'uid' => (string) $r['uid'],
            'type' => (string) $r['type'],
            'text' => (string) $r['text'],
            'money' => (string) $r['money'],
            'smoney' => isset($r['smoney']) ? (string) $r['smoney'] : '',
            'ip' => (string) $r['ip'],
            'addtime' => (string) $r['addtime']
        );
    }
    api_respond(0, 'ok', array('records' => $records, 'current' => $page, 'size' => $pageSize, 'total' => intval($total)));
}

if ($action === 'help-list') {
    api_require_login(isset($islogin) ? $islogin : 0);
    $res = $DB->query("SELECT c.cid, c.name, c.content, c.fenlei, f.name AS fenlei_name FROM qingka_wangke_class c LEFT JOIN qingka_wangke_fenlei f ON c.fenlei=f.id WHERE c.status=1 ORDER BY CAST(c.sort AS UNSIGNED) ASC, c.cid DESC");
    $list = array();
    while ($r = $DB->fetch($res)) {
        $list[] = array(
            'cid' => (string) $r['cid'],
            'name' => (string) $r['name'],
            'content' => (string) $r['content'],
            'fenleiName' => (string) ($r['fenlei_name'] ? $r['fenlei_name'] : '未分类')
        );
    }
    api_respond(0, 'ok', array('list' => $list));
}

if ($action === 'myprice-list') {
    api_require_login(isset($islogin) ? $islogin : 0);
    $userRate = floatval($userrow['addprice'] ? $userrow['addprice'] : 1.0);
    $uid = intval($userrow['uid']);

    $totalProducts = $DB->count("SELECT COUNT(*) FROM qingka_wangke_class WHERE status=1");
    $totalCategories = $DB->count("SELECT COUNT(*) FROM qingka_wangke_fenlei WHERE status=1");

    $res = $DB->query("SELECT c.*, f.name AS fenlei_name, m.mode AS mijia_mode, m.price AS mijia_price FROM qingka_wangke_class c LEFT JOIN qingka_wangke_fenlei f ON c.fenlei=f.id LEFT JOIN qingka_wangke_mijia m ON c.cid=m.cid AND m.uid='$uid' WHERE c.status=1 ORDER BY CAST(c.sort AS UNSIGNED) ASC, c.cid DESC");
    $list = array();
    while ($r = $DB->fetch($res)) {
        $basePrice = floatval($r['price']);
        $calcPrice = $r['yunsuan'] === '+' ? $basePrice + $userRate : $basePrice * $userRate;

        // 若有针对该用户的密价则应用
        if (isset($r['mijia_mode'])) {
            $mMode = intval($r['mijia_mode']);
            $mPrice = floatval($r['mijia_price']);
            if ($mMode === 0) $calcPrice -= $mPrice;
            elseif ($mMode === 1) $calcPrice *= $mPrice;
            elseif ($mMode === 2) $calcPrice = $mPrice;
        }

        if ($calcPrice < 0) $calcPrice = 0;

        $list[] = array(
            'cid' => (string) $r['cid'],
            'name' => (string) $r['name'],
            'basePrice' => (string) $r['price'],
            'userPrice' => number_format($calcPrice, 2, '.', ''),
            'ckkf' => (string) $r['ckkf'],
            'fenleiName' => (string) ($r['fenlei_name'] ? $r['fenlei_name'] : '未分类'),
            'content' => (string) $r['content']
        );
    }

    api_respond(0, 'ok', array(
        'userRate' => number_format($userRate, 2, '.', ''),
        'totalProducts' => intval($totalProducts),
        'totalCategories' => intval($totalCategories),
        'list' => $list
    ));
}

if ($action === 'docking-info') {
    api_require_login(isset($islogin) ? $islogin : 0);
    $scheme = !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off' ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'sk.yunxnet.cn';
    $baseUrl = $scheme . '://' . $host;

    api_respond(0, 'ok', array(
        'uid' => (string) $userrow['uid'],
        'key' => isset($userrow['key']) ? (string) $userrow['key'] : '',
        'apiBaseUrl' => $baseUrl . '/api.php',
        'apiBalanceUrl' => $baseUrl . '/api.php?act=getmoney',
        'apiGoodsUrl' => $baseUrl . '/api.php?act=getclass',
        'apiQueryUrl' => $baseUrl . '/api.php?act=get',
        'apiAddUrl' => $baseUrl . '/api.php?act=add',
        'apiAutoAddUrl' => $baseUrl . '/api.php?act=getadd',
        'apiStatusUrl' => $baseUrl . '/api.php?act=chadan',
        'apiBudanUrl' => $baseUrl . '/api.php?act=budan'
    ));
}

if ($action === 'pchange-list') {
    api_require_login(isset($islogin) ? $islogin : 0);
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $pageSize = 20;
    $offset = ($page - 1) * $pageSize;
    $cid = isset($_GET['cid']) && intval($_GET['cid']) > 0 ? intval($_GET['cid']) : 0;

    $where = array('1=1');
    if ($cid > 0) $where[] = "cid='$cid'";
    $whereStr = ' WHERE ' . implode(' AND ', $where);

    $total = $DB->count("SELECT COUNT(*) FROM qingka_wangke_pchange $whereStr");
    $res = $DB->query("SELECT * FROM qingka_wangke_pchange $whereStr ORDER BY updatetime DESC LIMIT $offset, $pageSize");
    $records = array();
    while ($r = $DB->fetch($res)) {
        $records[] = array(
            'cid' => (string) $r['cid'],
            'kcname' => (string) $r['kcname'],
            'oldprice' => (string) $r['oldprice'],
            'newprice' => (string) $r['newprice'],
            'updatetime' => (string) $r['updatetime']
        );
    }

    $totalAll = $DB->count("SELECT COUNT(*) FROM qingka_wangke_pchange");
    $todayAll = $DB->count("SELECT COUNT(*) FROM qingka_wangke_pchange WHERE DATE(updatetime)=CURDATE()");

    api_respond(0, 'ok', array(
        'records' => $records,
        'current' => $page,
        'size' => $pageSize,
        'total' => intval($total),
        'stats' => array('total' => intval($totalAll), 'today' => intval($todayAll))
    ));
}

if ($action === 'pay-card') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    $input = api_read_input();
    $content = isset($input['content']) ? trim(strip_tags($input['content'])) : '';
    if ($content === '') api_respond(422, '请输入充值卡密');

    $safeContent = daddslashes($content);
    $km = $DB->get_row("SELECT * FROM qingka_wangke_km WHERE content='$safeContent' LIMIT 1");
    if (!$km) api_respond(400, '卡密不存在或已被使用');
    if (intval($km['status']) !== 0) api_respond(400, '该卡密已被使用过');

    $kmMoney = floatval($km['money']);
    $uid = intval($userrow['uid']);
    $now = date('Y-m-d H:i:s');

    $DB->query("UPDATE qingka_wangke_km SET status=1, uid='$uid', usedtime='$now' WHERE id='{$km['id']}'");
    $DB->query("UPDATE qingka_wangke_user SET money=money+'$kmMoney', zcz=zcz+'$kmMoney' WHERE uid='$uid'");

    if (function_exists('wlog')) {
        wlog($uid, "卡密充值", "使用卡密充值成功，面值 {$kmMoney} 元", "+$kmMoney");
    }

    $newUser = $DB->get_row("SELECT money FROM qingka_wangke_user WHERE uid='$uid' LIMIT 1");
    api_respond(0, "充值成功！充值金额: ¥ {$kmMoney}，当前余额: ¥ {$newUser['money']}", array('newBalance' => (string) $newUser['money']));
}

if ($action === 'charge-info') {
    api_require_login(isset($islogin) ? $islogin : 0);
    api_respond(0, 'ok', array(
        'user' => (string) $userrow['user'],
        'balance' => (string) $userrow['money'],
        'isSuper' => intval($userrow['uid']) === 1,
        'isDirect' => intval($userrow['uuid']) === 1,
        'onlineRechargeEnabled' => isset($conf['zxczkg']) && intval($conf['zxczkg']) === 1,
        'minAmount' => isset($conf['zdpay']) && $conf['zdpay'] ? (string) $conf['zdpay'] : '10',
        'isAlipay' => isset($conf['is_alipay']) && intval($conf['is_alipay']) === 1,
        'isWxpay' => isset($conf['is_wxpay']) && intval($conf['is_wxpay']) === 1,
        'isQqpay' => isset($conf['is_qqpay']) && intval($conf['is_qqpay']) === 1
    ));
}

if ($action === 'user-signin') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    if (!isset($conf['qdkg']) || intval($conf['qdkg']) !== 1) {
        api_respond(400, '系统暂未开启每日签到功能');
    }

    $uid = intval($userrow['uid']);
    $userSign = $DB->get_row("SELECT uid, money, zcz, addprice, freeadd, last_sign_in_date FROM qingka_wangke_user WHERE uid='$uid' LIMIT 1");
    if (!$userSign) api_respond(404, '用户数据异常');

    $today = date('Y-m-d');
    if ($userSign['last_sign_in_date'] === $today) {
        api_respond(400, '今日已经完成过签到啦，明天再来吧！');
    }

    $userLevel = floatval($userSign['addprice']);
    $rewardMsg = '';

    if ($userLevel > 0.2 || empty($conf['mfxdkg']) || intval($conf['mfxdkg']) !== 1) {
        $randMoney = (mt_rand(1, 100) <= 80) ? mt_rand(1, 2) / 100 : mt_rand(3, 5) / 100;
        $DB->query("UPDATE qingka_wangke_user SET money=money+'$randMoney', zcz=zcz+'$randMoney', last_sign_in_date='$today' WHERE uid='$uid'");
        $rewardMsg = "恭喜签到成功！账户余额增加 ¥ {$randMoney} 元";
        if (function_exists('wlog')) wlog($uid, "签到成功", $rewardMsg, "+$randMoney");
    } else {
        $freeAdds = (mt_rand(1, 100) <= 70) ? 1 : mt_rand(2, 3);
        $DB->query("UPDATE qingka_wangke_user SET freeadd=freeadd+'$freeAdds', last_sign_in_date='$today' WHERE uid='$uid'");
        $rewardMsg = "恭喜签到成功！获得 {$freeAdds} 次免费下单机会";
        if (function_exists('wlog')) wlog($uid, "签到成功", $rewardMsg, "0");
    }

    $freshUser = $DB->get_row("SELECT money, freeadd FROM qingka_wangke_user WHERE uid='$uid' LIMIT 1");
    api_respond(0, $rewardMsg, array(
        'balance' => number_format(floatval($freshUser['money']), 2, '.', ''),
        'freeAdd' => intval($freshUser['freeadd']),
        'hasSignedIn' => true
    ));
}

if ($action === 'user-profile') {
    api_require_login(isset($islogin) ? $islogin : 0);
    $uid = intval($userrow['uid']);
    $currentUser = $DB->get_row("SELECT * FROM qingka_wangke_user WHERE uid='$uid' LIMIT 1");
    if (!$currentUser) {
        api_respond(404, '用户不存在');
    }

    $superiorUser = '无';
    $superiorNotice = '';
    $uuid = intval($currentUser['uuid']);
    if ($uuid > 0 && $uuid !== $uid) {
        $superior = $DB->get_row("SELECT uid, user, notice FROM qingka_wangke_user WHERE uid='$uuid' LIMIT 1");
        if ($superior) {
            $superiorUser = (string) $superior['user'];
            $superiorNotice = isset($superior['notice']) ? (string) $superior['notice'] : '';
        }
    }

    $today = date('Y-m-d');
    $totalOrders = $DB->count("SELECT count(oid) FROM qingka_wangke_order WHERE uid='$uid'");
    $agentTotal = $DB->count("SELECT count(uid) FROM qingka_wangke_user WHERE uuid='$uid'");
    $agentRegToday = $DB->count("SELECT count(uid) FROM qingka_wangke_user WHERE uuid='$uid' AND addtime>='$today'");
    $agentLoginToday = $DB->count("SELECT count(uid) FROM qingka_wangke_user WHERE uuid='$uid' AND endtime>='$today'");
    $orderToday = $DB->count("SELECT count(oid) FROM qingka_wangke_order WHERE uid='$uid' AND addtime>='$today'");

    $rawKey = isset($currentUser['key']) ? trim((string) $currentUser['key']) : '';
    $hasKey = ($rawKey !== '' && $rawKey !== '0' && $rawKey !== '-1');
    $key = $hasKey ? $rawKey : '';

    $yqm = isset($currentUser['yqm']) ? trim((string) $currentUser['yqm']) : '';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    $protocol = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ? 'https://' : 'http://';
    $inviteUrl = ($yqm !== '' && $host !== '') ? "${protocol}${host}/index/login?yqm=${yqm}" : '';

    api_respond(0, 'ok', array(
        'uid' => (string) $currentUser['uid'],
        'user' => (string) $currentUser['user'],
        'name' => isset($currentUser['name']) && trim($currentUser['name']) !== '' ? (string) $currentUser['name'] : (string) $currentUser['user'],
        'avatar' => api_user_avatar($currentUser['user'], $conf),
        'money' => number_format(floatval($currentUser['money']), 2, '.', ''),
        'zcz' => isset($currentUser['zcz']) ? (string) $currentUser['zcz'] : '0',
        'addprice' => isset($currentUser['addprice']) ? (string) $currentUser['addprice'] : '1.00',
        'vip' => intval($currentUser['vip']),
        'freeAdd' => isset($currentUser['freeadd']) ? intval($currentUser['freeadd']) : 0,
        'yqm' => $yqm,
        'yqprice' => isset($currentUser['yqprice']) && trim($currentUser['yqprice']) !== '' ? (string) $currentUser['yqprice'] : '',
        'inviteUrl' => $inviteUrl,
        'superiorUser' => $superiorUser,
        'key' => $key,
        'hasKey' => $hasKey,
        'pushPlusToken' => isset($currentUser['pushPlusToken']) && trim($currentUser['pushPlusToken']) !== '' ? (string) $currentUser['pushPlusToken'] : '',
        'totalOrders' => intval($totalOrders),
        'stats' => array(
            'agentTotal' => intval($agentTotal),
            'agentRegToday' => intval($agentRegToday),
            'agentLoginToday' => intval($agentLoginToday),
            'orderToday' => intval($orderToday)
        ),
        'siteNotice' => isset($conf['notice']) ? (string) $conf['notice'] : '',
        'superiorNotice' => $superiorNotice,
        'siteName' => isset($conf['sitename']) && trim($conf['sitename']) !== '' ? (string) $conf['sitename'] : '网课管理中心'
    ));
}

if ($action === 'user-profile-save') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();

    $input = api_read_input();
    $name = isset($input['name']) ? trim(strip_tags($input['name'])) : '';
    if ($name === '') {
        api_respond(422, '用户昵称不能为空');
    }
    if (mb_strlen($name, 'UTF-8') > 30) {
        api_respond(422, '用户昵称长度不能超过 30 个字符');
    }

    $uid = intval($userrow['uid']);
    $safeName = daddslashes($name);
    $DB->query("UPDATE qingka_wangke_user SET name='$safeName' WHERE uid='$uid'");

    if (function_exists('wlog')) {
        wlog($uid, '修改资料', "修改用户昵称为: ${name}", '0');
    }

    api_respond(0, '个人资料更新成功', array('name' => $name));
}

if ($action === 'user-password-save') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();

    $input = api_read_input();
    $oldPassword = isset($input['oldPassword']) ? trim($input['oldPassword']) : '';
    $newPassword = isset($input['newPassword']) ? trim($input['newPassword']) : '';
    $confirmPassword = isset($input['confirmPassword']) ? trim($input['confirmPassword']) : '';

    if ($oldPassword === '') {
        api_respond(422, '原密码不能为空');
    }
    if ($newPassword === '') {
        api_respond(422, '新密码不能为空');
    }
    if (strlen($newPassword) < 6) {
        api_respond(422, '新密码长度至少需要 6 位');
    }
    if ($newPassword !== $confirmPassword) {
        api_respond(422, '两次输入的新密码不一致');
    }

    $uid = intval($userrow['uid']);
    $currentUser = $DB->get_row("SELECT user, pass FROM qingka_wangke_user WHERE uid='$uid' LIMIT 1");
    if (!$currentUser || !hash_equals((string) $currentUser['pass'], (string) $oldPassword)) {
        api_respond(422, '原密码不正确');
    }

    $safeNewPass = daddslashes($newPassword);
    $DB->query("UPDATE qingka_wangke_user SET pass='$safeNewPass' WHERE uid='$uid'");

    $session = md5($currentUser['user'] . $newPassword . $password_hash);
    $token = authcode($currentUser['user'] . "\t" . $session, 'ENCODE', SYS_KEY);
    api_set_auth_cookie($token, time() + 216000);

    if (function_exists('wlog')) {
        wlog($uid, '修改密码', '用户成功修改登录密码', '0');
    }

    api_respond(0, '密码修改成功，请牢记新密码');
}

if ($action === 'user-yqprice-save') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();

    $input = api_read_input();
    $yqprice = isset($input['yqprice']) ? trim(strip_tags($input['yqprice'])) : '';
    if (!is_numeric($yqprice)) {
        api_respond(422, '请正确输入费率，必须为数字');
    }

    $floatYqprice = round(floatval($yqprice), 2);
    $myAddprice = floatval($userrow['addprice']);
    if ($floatYqprice < $myAddprice) {
        api_respond(422, "下级默认费率不能低于您自身的成本费率 (${myAddprice})");
    }
    if ($floatYqprice < 0.20) {
        api_respond(422, '邀请费率最低不能低于 0.20');
    }

    $uid = intval($userrow['uid']);
    $currentUser = $DB->get_row("SELECT yqm FROM qingka_wangke_user WHERE uid='$uid' LIMIT 1");
    $yqm = isset($currentUser['yqm']) ? trim($currentUser['yqm']) : '';

    if ($yqm === '') {
        $yqm = api_random_string(5, true);
        if ($DB->get_row("SELECT uid FROM qingka_wangke_user WHERE yqm='$yqm' LIMIT 1")) {
            $yqm = api_random_string(6, true);
        }
        $sql = "yqm='$yqm', yqprice='$floatYqprice'";
    } else {
        $sql = "yqprice='$floatYqprice'";
    }

    $DB->query("UPDATE qingka_wangke_user SET ${sql} WHERE uid='$uid'");

    if (function_exists('wlog')) {
        wlog($uid, '修改费率', "设置下级默认邀请费率为: ${floatYqprice}", '0');
    }

    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    $protocol = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ? 'https://' : 'http://';
    $inviteUrl = ($yqm !== '' && $host !== '') ? "${protocol}${host}/index/login?yqm=${yqm}" : '';

    api_respond(0, '下级默认费率设置成功', array(
        'yqprice' => (string) $floatYqprice,
        'yqm' => $yqm,
        'inviteUrl' => $inviteUrl
    ));
}

if ($action === 'user-api-key-create') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();

    $uid = intval($userrow['uid']);
    $currentUser = $DB->get_row("SELECT money, `key` FROM qingka_wangke_user WHERE uid='$uid' LIMIT 1");
    if (!$currentUser) {
        api_respond(404, '用户不存在');
    }

    $rawKey = isset($currentUser['key']) ? trim((string) $currentUser['key']) : '';
    if ($rawKey !== '' && $rawKey !== '0' && $rawKey !== '-1') {
        api_respond(400, '您已开通 API 接口，无需重复开通');
    }

    $key = api_random_string(12);
    $money = floatval($currentUser['money']);

    if ($money < 50.00) {
        if ($money >= 5.00) {
            $DB->query("UPDATE qingka_wangke_user SET `key`='$key', money=money-5 WHERE uid='$uid'");
            if (function_exists('wlog')) {
                wlog($uid, '开通接口', '花费 5 元开通 API 接口成功', '-5');
            }
            $freshUser = $DB->get_row("SELECT money FROM qingka_wangke_user WHERE uid='$uid' LIMIT 1");
            api_respond(0, '已扣除 5 元手续费，API 接口开通成功！', array(
                'key' => $key,
                'balance' => number_format(floatval($freshUser['money']), 2, '.', '')
            ));
        } else {
            api_respond(400, '余额不足 5 元（满 50 元可免手续费开通），请先充值');
        }
    } else {
        $DB->query("UPDATE qingka_wangke_user SET `key`='$key' WHERE uid='$uid'");
        if (function_exists('wlog')) {
            wlog($uid, '开通接口', '满足余额条件，免费开通 API 接口成功', '0');
        }
        api_respond(0, '满足免费开通条件，API 接口开通成功！', array(
            'key' => $key,
            'balance' => number_format($money, 2, '.', '')
        ));
    }
}

if ($action === 'user-api-key-refresh') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();

    $uid = intval($userrow['uid']);
    $currentUser = $DB->get_row("SELECT `key` FROM qingka_wangke_user WHERE uid='$uid' LIMIT 1");
    if (!$currentUser) {
        api_respond(404, '用户不存在');
    }

    $rawKey = isset($currentUser['key']) ? trim((string) $currentUser['key']) : '';
    if ($rawKey === '' || $rawKey === '0' || $rawKey === '-1') {
        api_respond(400, '尚未开通 API 接口，请先点击开通');
    }

    $newKey = api_random_string(12);
    $DB->query("UPDATE qingka_wangke_user SET `key`='$newKey' WHERE uid='$uid'");

    if (function_exists('wlog')) {
        wlog($uid, '更换接口', '更换 API 接口密钥成功', '0');
    }

    api_respond(0, 'API 密钥更换成功，旧密钥已即时失效', array('key' => $newKey));
}

if ($action === 'user-push-token-save') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();

    $input = api_read_input();
    $token = isset($input['pushPlusToken']) ? trim(strip_tags($input['pushPlusToken'])) : '';

    $uid = intval($userrow['uid']);
    $safeToken = daddslashes($token);

    $DB->query("UPDATE qingka_wangke_user SET pushPlusToken='$safeToken' WHERE uid='$uid'");

    if (function_exists('wlog')) {
        $msg = $token !== '' ? "更新微信推送 Token 为: ${token}" : '清空/解绑微信推送 Token';
        wlog($uid, '更新推送Token', $msg, '0');
    }

    $respMsg = $token !== '' ? '微信推送 Token 设置成功' : '微信推送 Token 已解绑清空';
    api_respond(0, $respMsg, array('pushPlusToken' => $token));
}

if ($action === 'workorder-list') {
    api_require_login(isset($islogin) ? $islogin : 0);
    $uid = intval($userrow['uid']);
    $isSuper = ($uid === 1);

    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $pageSize = isset($_GET['pageSize']) ? max(1, min(100, intval($_GET['pageSize']))) : 15;
    $offset = ($page - 1) * $pageSize;

    $keyword = isset($_GET['keyword']) ? trim(strip_tags($_GET['keyword'])) : '';
    $status = isset($_GET['status']) ? trim(strip_tags($_GET['status'])) : '';

    $whereClauses = array();
    if (!$isSuper) {
        $whereClauses[] = "g.uid='$uid'";
    }
    if ($keyword !== '') {
        $safeKw = daddslashes($keyword);
        $whereClauses[] = "(g.title LIKE '%$safeKw%' OR g.content LIKE '%$safeKw%' OR g.region LIKE '%$safeKw%')";
    }
    if ($status !== '') {
        $safeStatus = daddslashes($status);
        $whereClauses[] = "g.state='$safeStatus'";
    }

    $where = count($whereClauses) > 0 ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

    $total = $DB->count("SELECT count(g.gid) FROM qingka_wangke_gongdan g $where");
    $rows = $DB->query("SELECT g.*, u.user as username, u.name as nickname FROM qingka_wangke_gongdan g LEFT JOIN qingka_wangke_user u ON g.uid=u.uid $where ORDER BY g.gid DESC LIMIT $pageSize OFFSET $offset");

    $records = array();
    while ($row = $DB->fetch($rows)) {
        $records[] = array(
            'gid' => (string) $row['gid'],
            'uid' => (string) $row['uid'],
            'userName' => isset($row['username']) ? (string) $row['username'] : '',
            'displayName' => isset($row['nickname']) && $row['nickname'] !== '' ? (string) $row['nickname'] : (isset($row['username']) ? (string) $row['username'] : ''),
            'region' => (string) $row['region'],
            'title' => (string) $row['title'],
            'content' => (string) $row['content'],
            'state' => (string) $row['state'],
            'addtime' => (string) $row['addtime']
        );
    }

    $pendingCount = $DB->count("SELECT count(gid) FROM qingka_wangke_gongdan WHERE " . ($isSuper ? "1=1" : "uid='$uid'") . " AND state='待回复'");
    $answeredCount = $DB->count("SELECT count(gid) FROM qingka_wangke_gongdan WHERE " . ($isSuper ? "1=1" : "uid='$uid'") . " AND state='已回复'");
    $finishedCount = $DB->count("SELECT count(gid) FROM qingka_wangke_gongdan WHERE " . ($isSuper ? "1=1" : "uid='$uid'") . " AND state='已完成'");

    api_respond(0, 'ok', array(
        'records' => $records,
        'total' => intval($total),
        'page' => $page,
        'pageSize' => $pageSize,
        'stats' => array(
            'pending' => intval($pendingCount),
            'answered' => intval($answeredCount),
            'finished' => intval($finishedCount)
        ),
        'isSuper' => $isSuper
    ));
}

if ($action === 'workorder-create') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();

    $input = api_read_input();
    $type = isset($input['type']) ? trim($input['type']) : 'order';
    $oid = isset($input['oid']) ? intval($input['oid']) : 0;
    $content = isset($input['content']) ? trim(strip_tags($input['content'])) : '';

    if ($content === '') {
        api_respond(422, '问题描述内容不能为空');
    }
    if (mb_strlen($content, 'UTF-8') > 500) {
        api_respond(422, '内容长度不能超过 500 个字符');
    }

    $uid = intval($userrow['uid']);
    $date = date('Y-m-d H:i:s');
    $region = '其它问题';
    $title = '其它问题咨询';

    if ($type === 'order' && $oid > 0) {
        $order = $DB->get_row("SELECT * FROM qingka_wangke_order WHERE oid='$oid' LIMIT 1");
        if (!$order) {
            api_respond(404, '关联的订单不存在，请核实订单号');
        }
        if ($uid !== 1 && intval($order['uid']) !== $uid) {
            api_respond(403, '无权操作此订单');
        }
        $region = (string) $oid;
        $title = (string)$order['ptname'] . " | " . (string)$order['school'] . " | " . (string)$order['kcname']
               . " (状态: " . (string)$order['status'] . ", 下单时间: " . (string)$order['addtime'] . ")";
    }

    $safeTitle = daddslashes($title);
    $safeRegion = daddslashes($region);
    $initialLog = "【" . $date . " 用户提交工单】\n" . $content;
    $safeLog = daddslashes($initialLog);

    $oidVal = isset($oid) ? intval($oid) : 0;
    $insertOk = $DB->query("INSERT INTO qingka_wangke_gongdan (oid, title, region, content, answer, uid, state, addtime, last_responder_uid) VALUES ('$oidVal', '$safeTitle', '$safeRegion', '$safeLog', '', '$uid', '待回复', '$date', '$uid')");
    if (!$insertOk) {
        $dbErr = method_exists($DB, 'error') ? $DB->error() : '';
        api_respond(500, '工单提交失败' . ($dbErr ? ": {$dbErr}" : '，请稍后重试'));
    }

    $gid = 0;
    if (method_exists($DB, 'insert_id')) {
        $gid = intval($DB->insert_id());
    }
    if ($gid <= 0 && isset($DB->link)) {
        $gid = intval(mysqli_insert_id($DB->link));
    }
    if ($gid <= 0) {
        $gRow = $DB->get_row("SELECT gid FROM qingka_wangke_gongdan WHERE uid='$uid' ORDER BY gid DESC LIMIT 1");
        $gid = $gRow ? intval($gRow['gid']) : 0;
    }

    $super = $DB->get_row("SELECT pushPlusToken FROM qingka_wangke_user WHERE uid='1' LIMIT 1");
    if (!empty($super['pushPlusToken'])) {
        $msg = "用户 {$userrow['user']}(UID:{$uid}) 提交了新工单 #{$gid}\n类型: {$region}\n内容: {$content}";
        api_send_push($super['pushPlusToken'], '新工单提醒', $msg);
    }

    api_respond(0, '工单提交成功，站长将尽快为您处理！', array('gid' => (string) $gid));
}

if ($action === 'workorder-reply') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();

    $input = api_read_input();
    $gid = isset($input['gid']) ? intval($input['gid']) : 0;
    $reply = isset($input['reply']) ? trim(strip_tags($input['reply'])) : '';

    if ($gid <= 0 || $reply === '') {
        api_respond(422, '回复内容不能为空');
    }

    $ticket = $DB->get_row("SELECT * FROM qingka_wangke_gongdan WHERE gid='$gid' LIMIT 1");
    if (!$ticket) {
        api_respond(404, '工单不存在');
    }

    $uid = intval($userrow['uid']);
    $isSuper = ($uid === 1);
    if (!$isSuper && intval($ticket['uid']) !== $uid) {
        api_respond(403, '无权操作此工单');
    }

    $date = date('Y-m-d H:i:s');
    if ($isSuper) {
        $actor = "管理员回复";
        $newState = '已回复';
    } else {
        $actor = "用户追加反馈";
        $newState = '待回复';
    }

    $append = "\n\n【" . $date . " " . $actor . "】\n" . $reply;
    $newContent = daddslashes($ticket['content'] . $append);

    $DB->query("UPDATE qingka_wangke_gongdan SET content='$newContent', state='$newState' WHERE gid='$gid'");

    if ($isSuper) {
        $userToken = $DB->get_row("SELECT pushPlusToken FROM qingka_wangke_user WHERE uid='{$ticket['uid']}' LIMIT 1");
        if (!empty($userToken['pushPlusToken'])) {
            api_send_push($userToken['pushPlusToken'], '工单回复通知', "您的工单 #{$gid} 有新回复：\n{$reply}");
        }
    } else {
        $superToken = $DB->get_row("SELECT pushPlusToken FROM qingka_wangke_user WHERE uid='1' LIMIT 1");
        if (!empty($superToken['pushPlusToken'])) {
            api_send_push($superToken['pushPlusToken'], '工单追问提醒', "工单 #{$gid} 用户追加提问：\n{$reply}");
        }
    }

    api_respond(0, $isSuper ? '工单回复成功' : '追加提问成功');
}

if ($action === 'workorder-finish') {
    api_require_post();
    api_require_super($userrow, $islogin);
    api_require_csrf();

    $input = api_read_input();
    $gid = isset($input['gid']) ? intval($input['gid']) : 0;
    $remark = isset($input['remark']) && trim($input['remark']) !== '' ? trim(strip_tags($input['remark'])) : '处理完成，结单';

    $ticket = $DB->get_row("SELECT * FROM qingka_wangke_gongdan WHERE gid='$gid' LIMIT 1");
    if (!$ticket) {
        api_respond(404, '工单不存在');
    }

    $date = date('Y-m-d H:i:s');
    $append = "\n\n【" . $date . " 管理员结单】\n" . $remark;
    $newContent = daddslashes($ticket['content'] . $append);

    $DB->query("UPDATE qingka_wangke_gongdan SET content='$newContent', state='已完成' WHERE gid='$gid'");

    $userToken = $DB->get_row("SELECT pushPlusToken FROM qingka_wangke_user WHERE uid='{$ticket['uid']}' LIMIT 1");
    if (!empty($userToken['pushPlusToken'])) {
        api_send_push($userToken['pushPlusToken'], '工单结单通知', "您的工单 #{$gid} 已结单：\n{$remark}");
    }

    api_respond(0, '工单已结单完成');
}

if ($action === 'workorder-delete') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();

    $input = api_read_input();
    $gid = isset($input['gid']) ? intval($input['gid']) : 0;

    $ticket = $DB->get_row("SELECT * FROM qingka_wangke_gongdan WHERE gid='$gid' LIMIT 1");
    if (!$ticket) {
        api_respond(404, '工单不存在');
    }

    $uid = intval($userrow['uid']);
    if ($uid !== 1 && intval($ticket['uid']) !== $uid) {
        api_respond(403, '无权删除此工单');
    }

    $DB->query("DELETE FROM qingka_wangke_gongdan WHERE gid='$gid'");
    api_respond(0, '工单已删除');
}

function api_format_bytes($bytes) {
    $bytes = max(0, floatval($bytes));
    if ($bytes < 1024) {
        return round($bytes) . ' B';
    } elseif ($bytes < 1048576) {
        return round($bytes / 1024, 2) . ' KB';
    } elseif ($bytes < 1073741824) {
        return round($bytes / 1048576, 2) . ' MB';
    } else {
        return round($bytes / 1073741824, 2) . ' GB';
    }
}

if ($action === 'docking-log-list') {
    api_require_login(isset($islogin) ? $islogin : 0);
    $currentUid = intval($userrow['uid']);
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $pageSize = isset($_GET['pageSize']) ? min(100, max(5, intval($_GET['pageSize']))) : 20;
    $offset = ($page - 1) * $pageSize;

    $direction = isset($_GET['direction']) ? trim(strip_tags($_GET['direction'])) : '';
    $actionParam = isset($_GET['action_filter']) ? trim(strip_tags($_GET['action_filter'])) : '';
    $keyword = isset($_GET['keyword']) ? trim(strip_tags($_GET['keyword'])) : '';
    $status = isset($_GET['status']) && $_GET['status'] !== '' ? intval($_GET['status']) : null;
    $startTime = isset($_GET['start_time']) ? trim(strip_tags($_GET['start_time'])) : '';
    $endTime = isset($_GET['end_time']) ? trim(strip_tags($_GET['end_time'])) : '';

    $where = array();
    if ($currentUid !== 1) {
        $where[] = "direction='in'";
        $where[] = "uid='$currentUid'";
    } else {
        if ($direction === 'in' || $direction === 'out') {
            $where[] = "direction='$direction'";
        }
    }

    if ($actionParam !== '') {
        $where[] = "action LIKE '%" . daddslashes($actionParam) . "%'";
    }
    if ($status !== null) {
        $where[] = "status='$status'";
    }
    if ($startTime !== '') {
        $where[] = "created_at >= '" . daddslashes($startTime) . "'";
    }
    if ($endTime !== '') {
        $where[] = "created_at <= '" . daddslashes($endTime) . "'";
    }
    if ($keyword !== '') {
        $kw = daddslashes($keyword);
        $where[] = "(caller LIKE '%$kw%' OR ip LIKE '%$kw%' OR target LIKE '%$kw%' OR action LIKE '%$kw%' OR params LIKE '%$kw%')";
    }

    $whereSql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

    $total = $DB->count("SELECT COUNT(*) FROM `qingka_wangke_docking_log` $whereSql");
    $listRes = $DB->query("SELECT * FROM `qingka_wangke_docking_log` $whereSql ORDER BY id DESC LIMIT $offset, $pageSize");

    $records = array();
    while ($row = $DB->fetch($listRes)) {
        $bytesIn = intval($row['bytes_in']);
        $bytesOut = intval($row['bytes_out']);
        $trafficTotal = intval($row['traffic_total']);
        if ($trafficTotal === 0 && ($bytesIn > 0 || $bytesOut > 0)) {
            $trafficTotal = $bytesIn + $bytesOut;
        }

        $records[] = array(
            'id' => (string)$row['id'],
            'direction' => (string)$row['direction'],
            'action' => (string)$row['action'],
            'caller' => (string)$row['caller'],
            'uid' => intval($row['uid']),
            'target' => (string)$row['target'],
            'method' => (string)$row['method'],
            'ip' => (string)$row['ip'],
            'params' => (string)$row['params'],
            'response' => (string)$row['response'],
            'status' => intval($row['status']),
            'cost_ms' => intval($row['cost_ms']),
            'bytes_in' => $bytesIn,
            'bytes_out' => $bytesOut,
            'traffic_total' => $trafficTotal,
            'traffic_text' => api_format_bytes($trafficTotal),
            'traffic_detail' => '入: ' . api_format_bytes($bytesIn) . ' | 出: ' . api_format_bytes($bytesOut),
            'created_at' => (string)$row['created_at']
        );
    }

    $today = date('Y-m-d');
    $statWhere = $currentUid !== 1 ? "WHERE direction='in' AND uid='$currentUid' AND created_at >= '{$today} 00:00:00'" : "WHERE created_at >= '{$today} 00:00:00'";
    $todayTotal = $DB->count("SELECT COUNT(*) FROM `qingka_wangke_docking_log` $statWhere");
    $todayIn = $DB->count("SELECT COUNT(*) FROM `qingka_wangke_docking_log` $statWhere AND direction='in'");
    $todayOut = $DB->count("SELECT COUNT(*) FROM `qingka_wangke_docking_log` $statWhere AND direction='out'");
    $todaySuccess = $DB->count("SELECT COUNT(*) FROM `qingka_wangke_docking_log` $statWhere AND status=1");
    
    $trafficRow = $DB->get_row("SELECT SUM(traffic_total) as total_traffic, AVG(cost_ms) as avg_cost FROM `qingka_wangke_docking_log` $statWhere");
    $todayTrafficBytes = isset($trafficRow['total_traffic']) ? floatval($trafficRow['total_traffic']) : 0;
    $avgCostMs = isset($trafficRow['avg_cost']) ? round(floatval($trafficRow['avg_cost']), 1) : 0;
    $successRate = $todayTotal > 0 ? (round(($todaySuccess / $todayTotal) * 100, 1) . '%') : '100%';

    api_respond(0, 'ok', array(
        'records' => $records,
        'current' => $page,
        'size' => $pageSize,
        'total' => intval($total),
        'metrics' => array(
            'today_total' => intval($todayTotal),
            'today_in' => intval($todayIn),
            'today_out' => intval($todayOut),
            'today_traffic' => api_format_bytes($todayTrafficBytes),
            'today_traffic_bytes' => $todayTrafficBytes,
            'avg_cost_ms' => $avgCostMs,
            'success_rate' => $successRate,
            'is_admin' => $currentUid === 1
        )
    ));
}

if ($action === 'docking-log-clear') {
    api_require_post();
    api_require_login(isset($islogin) ? $islogin : 0);
    api_require_csrf();
    
    $currentUid = intval($userrow['uid']);
    if ($currentUid !== 1) {
        api_respond(403, '仅管理员可清理对接日志');
    }

    $input = api_read_input();
    $range = isset($input['range']) ? trim(strip_tags($input['range'])) : '7days';

    if ($range === 'all') {
        $DB->query("TRUNCATE TABLE `qingka_wangke_docking_log`");
    } elseif ($range === '30days') {
        $limitDate = date('Y-m-d H:i:s', strtotime('-30 days'));
        $DB->query("DELETE FROM `qingka_wangke_docking_log` WHERE created_at < '$limitDate'");
    } else {
        $limitDate = date('Y-m-d H:i:s', strtotime('-7 days'));
        $DB->query("DELETE FROM `qingka_wangke_docking_log` WHERE created_at < '$limitDate'");
    }

    api_respond(0, '对接日志清理成功');
}

require_once __DIR__ . '/actions_user_order.php';
require_once __DIR__ . '/scheduler_worker.php';

api_respond(404, '接口不存在', null, 404);
