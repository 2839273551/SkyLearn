<?php
// CLI integration checks against connection-local MySQL temporary tables.
// SKYLEARN_TEST_CONFIG points to an existing config.php; no real rows are changed.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
if (!getenv('SKYLEARN_TEST_CONFIG')) {
    fwrite(STDERR, "Set SKYLEARN_TEST_CONFIG to a database config.php.\n");
    exit(1);
}

if (!isset($argv[1])) {
    $cases = array(
        array('name' => 'admin deletes agent', 'actor' => 1),
        array('name' => 'parent deletes direct child'),
        array('name' => 'reject unrelated agent', 'actor' => 21, 'code' => 403),
        array('name' => 'reject administrator', 'uid' => 1, 'code' => 403),
        array('name' => 'reject self', 'uid' => 10, 'code' => 403),
        array('name' => 'reject missing uid', 'uid' => 9999, 'code' => 404),
        array('name' => 'reject malformed uid', 'uid' => '20junk', 'code' => 422),
        array('name' => 'reject fractional uid', 'uid' => 20.5, 'code' => 422),
        array('name' => 'reject non-scalar uid', 'uid' => array(20), 'code' => 422),
        array('name' => 'reject positive balance', 'balance' => '0.01', 'code' => 409),
        array('name' => 'reject negative balance', 'balance' => '-0.01', 'code' => 409),
        array('name' => 'reject active child', 'child' => true, 'code' => 409),
        array('name' => 'reject pending payment', 'payment' => 0, 'code' => 409),
        array('name' => 'preserve completed payment', 'payment' => 1),
        array('name' => 'ignore deleted child', 'deletedChild' => true),
        array('name' => 'reject repeated deletion', 'uid' => 30, 'code' => 404),
        array('name' => 'reject GET', 'method' => 'GET', 'code' => 405),
        array('name' => 'reject missing login', 'loggedIn' => false, 'code' => 401),
        array('name' => 'reject invalid csrf', 'csrf' => 'bad', 'code' => 419),
        array('name' => 'reject foreign origin', 'origin' => 'https://foreign.test', 'code' => 403),
        array('name' => 'rollback deletion if audit fails', 'failAudit' => true, 'code' => 500),
        array('name' => 'exclude deleted agents from list', 'action' => 'userlist-list'),
        array('name' => 'exclude deleted agents from referrals', 'action' => 'my-referrals'),
        array('name' => 'batch rate leaves deleted users unchanged', 'action' => 'user-batch-rate', 'input' => array('rate' => 0.5)),
        array('name' => 'deleted user cannot log in', 'action' => 'login', 'input' => array('userName' => 'fixture30', 'password' => 'fixture-password'), 'code' => 403),
        array('name' => 'credit direct child atomically', 'action' => 'userlist-recharge', 'input' => array('amount' => 5), 'balances' => array(95, 5)),
        array('name' => 'deduct direct child atomically', 'action' => 'userlist-recharge', 'balance' => 8, 'input' => array('amount' => 5, 'type' => 'deduct'), 'balances' => array(105, 3)),
        array('name' => 'admin credit', 'actor' => 1, 'action' => 'userlist-recharge', 'input' => array('amount' => 5), 'balances' => array(100, 5)),
        array('name' => 'admin deduct', 'actor' => 1, 'action' => 'userlist-recharge', 'balance' => 8, 'input' => array('amount' => 5, 'type' => 'deduct'), 'balances' => array(100, 3)),
        array('name' => 'reject insufficient funds', 'action' => 'userlist-recharge', 'input' => array('amount' => 101), 'code' => 400),
        array('name' => 'rollback transfer if audit fails', 'action' => 'userlist-recharge', 'input' => array('amount' => 5), 'failAudit' => true, 'code' => 500),
        array('name' => 'create under active parent', 'action' => 'user-create', 'input' => array('user' => '55555', 'pass' => 'fixture-password', 'name' => 'new fixture', 'grade_id' => 1)),
        array('name' => 'deleted actor cannot create child', 'actor' => 30, 'action' => 'user-create', 'input' => array('user' => '55555', 'pass' => 'fixture-password', 'name' => 'new fixture', 'grade_id' => 1), 'code' => 403),
        array('name' => 'migrate to active parent', 'action' => 'user-migrate', 'input' => array('target_uid' => 21, 'yqm' => 'fixture-invite')),
        array('name' => 'cannot migrate to deleted parent', 'action' => 'user-migrate', 'input' => array('target_uid' => 30, 'yqm' => 'fixture-invite'), 'code' => 404),
        array('name' => 'deleted actor cannot migrate', 'actor' => 30, 'action' => 'user-migrate', 'input' => array('target_uid' => 21, 'yqm' => 'fixture-invite'), 'code' => 403)
    );
    foreach (array('status', 'recharge', 'toggle-key', 'login-as', 'rate', 'reset-password') as $operation) {
        $cases[] = array(
            'name' => 'reject deleted target: ' . $operation,
            'action' => 'userlist-' . $operation,
            'uid' => 30, 'code' => 404,
            'input' => array('active' => 1, 'amount' => 5, 'rate' => 0.5, 'password' => 'fixture-new-password')
        );
    }
    foreach ($cases as $case) {
        $process = proc_open(array(PHP_BINARY, __FILE__, json_encode($case)), array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);
        echo $output;
        if ($status !== 0) {
            fwrite(STDERR, $errors);
            exit(1);
        }
    }
    echo 'PASS: ' . count($cases) . " MySQL integration checks\n";
    exit;
}

$case = json_decode($argv[1], true);
define('IN_CRONLITE', true);
require getenv('SKYLEARN_TEST_CONFIG');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

class DB
{
    public $link;
    public function __construct($host, $user, $password, $database, $port)
    {
        $this->link = new mysqli($host, $user, $password, $database, $port);
        $this->link->set_charset('utf8');
    }
    public function query($sql)
    {
        global $case;
        if (!empty($case['failAudit']) && strpos($sql, 'INSERT INTO qingka_wangke_log') === 0) return false;
        return $this->link->query($sql);
    }
    public function fetch($result) { return $result->fetch_assoc(); }
    public function get_row($sql) { return $this->fetch($this->query($sql)); }
    public function count($sql) { return $this->query($sql)->fetch_row()[0]; }
    public function affected() { return $this->link->affected_rows; }
    public function error() { return $this->link->error ?: 'injected audit failure'; }
}
$DB = new DB($host, $user, $pwd, $dbname, isset($port) ? (int) $port : 3306);
$DB->query("SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION'");
foreach (array('user', 'pay', 'log', 'order', 'dengji') as $suffix) {
    $schema = $DB->query("SHOW CREATE TABLE qingka_wangke_$suffix")->fetch_row()[1];
    $DB->query(preg_replace('/^CREATE TABLE/', 'CREATE TEMPORARY TABLE', $schema, 1));
}
if (!$DB->get_row("SHOW COLUMNS FROM qingka_wangke_user LIKE 'deleted_at'")) {
    $DB->query(file_get_contents(__DIR__ . '/../migrations/20261113_user_deleted_at.sql'));
}

function daddslashes($value) { global $DB; return $DB->link->real_escape_string((string) $value); }
function real_ip() { return '127.0.0.1'; }
function fixture_insert($suffix, $values)
{
    global $DB;
    $columns = $DB->query("SHOW COLUMNS FROM qingka_wangke_$suffix");
    while ($column = $columns->fetch_assoc()) {
        $name = $column['Field'];
        if (array_key_exists($name, $values) || $column['Null'] === 'YES' || $column['Default'] !== null || $column['Extra'] === 'auto_increment') continue;
        $values[$name] = preg_match('/int|decimal|float|double/', $column['Type']) ? 0 : '';
    }
    $keys = '`' . implode('`,`', array_keys($values)) . '`';
    $encoded = array_map(function ($value) { return $value === null ? 'NULL' : "'" . daddslashes($value) . "'"; }, array_values($values));
    $DB->query("INSERT INTO qingka_wangke_$suffix ($keys) VALUES (" . implode(',', $encoded) . ')');
}
foreach (array(1 => 1, 10 => 1, 20 => 10, 21 => 1, 30 => 10) as $id => $parent) {
    fixture_insert('user', array(
        'uid' => $id, 'uuid' => $parent, 'user' => "fixture$id", 'pass' => 'fixture-password', 'name' => "fixture$id",
        'money' => $id === 10 ? 100 : ($id === 20 ? ($case['balance'] ?? 0) : 0),
        'addprice' => 0.3, 'active' => $id === 30 ? 0 : 1,
        'key' => $id === 30 ? '' : 'fixture-key', 'yqm' => $id === 30 ? '' : 'fixture-invite',
        'deleted_at' => $id === 30 ? '2026-11-13 00:00:00' : null
    ));
}
if (!empty($case['child']) || !empty($case['deletedChild'])) {
    fixture_insert('user', array('uid' => 22, 'uuid' => 20, 'user' => 'fixture22', 'deleted_at' => !empty($case['deletedChild']) ? '2026-11-13 00:00:00' : null));
}
if (isset($case['payment'])) fixture_insert('pay', array('oid' => 100, 'uid' => 20, 'status' => $case['payment']));
fixture_insert('order', array('oid' => 100, 'uid' => 20));
fixture_insert('log', array('id' => 100, 'uid' => 20, 'type' => 'fixture history'));
fixture_insert('dengji', array('id' => 1, 'name' => 'test', 'rate' => '0.30', 'money' => '0', 'addkf' => 0));
$actor = $case['actor'] ?? 10;
$uid = $case['uid'] ?? 20;
$action = $case['action'] ?? 'userlist-delete';
$userrow = $DB->get_row("SELECT * FROM qingka_wangke_user WHERE uid='$actor'");
$islogin = ($case['loggedIn'] ?? true) ? 1 : 0;
$conf = array('sjqykg' => '1');
$date = date('Y-m-d H:i:s');
$jtdate = date('Y-m-d');
$_SESSION = array('admin_api_csrf' => str_repeat('a', 64));
$_GET = array('action' => $action);
$_POST = array_merge(array('uid' => $uid), $case['input'] ?? array());
$_SERVER = array_merge($_SERVER, array(
    'REQUEST_METHOD' => $case['method'] ?? 'POST', 'HTTP_HOST' => 'fixture.test',
    'HTTP_ORIGIN' => $case['origin'] ?? 'https://fixture.test',
    'HTTP_X_CSRF_TOKEN' => $case['csrf'] ?? str_repeat('a', 64),
    'CONTENT_TYPE' => 'application/x-www-form-urlencoded', 'REMOTE_ADDR' => '127.0.0.1'
));
$before = array();
foreach (array('user', 'pay', 'order', 'log') as $table) {
    $before[$table] = $DB->query("SELECT * FROM qingka_wangke_$table")->fetch_all(MYSQLI_ASSOC);
}

ob_start();
register_shutdown_function(function () use ($case, $DB, $action, $actor, $before) {
    $raw = ob_get_clean();
    $response = json_decode($raw, true);
    try {
        $check = function ($condition, $message) { if (!$condition) throw new RuntimeException($message); };
        $check(is_array($response), 'invalid API response: ' . $raw);
        $expected = $case['code'] ?? 0;
        $check($response['code'] === $expected, 'unexpected response: ' . $raw);
        $after = array();
        foreach (array('user', 'pay', 'order', 'log') as $table) {
            $after[$table] = $DB->query("SELECT * FROM qingka_wangke_$table")->fetch_all(MYSQLI_ASSOC);
        }
        $check($before['pay'] === $after['pay'] && $before['order'] === $after['order'], 'historical payment/order data changed');
        $check($before['log'][0] === $after['log'][0], 'historical ledger changed');
        if ($expected !== 0) {
            $check($before === $after, 'rejected/failed operation changed data');
        } elseif ($action === 'userlist-delete') {
            $target = $DB->get_row('SELECT * FROM qingka_wangke_user WHERE uid=20');
            $check($target && $target['deleted_at'] && $target['active'] === '0' && $target['key'] === '' && $target['yqm'] === '', 'account not disabled/deleted');
            $check($target['user'] === 'fixture20' && $target['pass'] === 'fixture-password' && $target['uuid'] === '10', 'historical user identity changed');
            $check(count($after['user']) === count($before['user']), 'user row physically removed');
            $check(count($after['log']) === 2 && (int) $after['log'][1]['uid'] === $actor, 'deletion audit missing/wrong actor');
        } elseif ($action === 'userlist-list' || $action === 'my-referrals') {
            $records = $response['data'][$action === 'userlist-list' ? 'records' : 'list'];
            $ids = array_column($records, 'uid');
            $check(in_array('20', $ids, true) && !in_array('30', $ids, true), 'deleted user visible or active child missing');
        } elseif ($action === 'user-batch-rate') {
            $check((float) $DB->get_row('SELECT addprice FROM qingka_wangke_user WHERE uid=30')['addprice'] === 0.3, 'batch rate changed deleted user');
            $check((float) $DB->get_row('SELECT addprice FROM qingka_wangke_user WHERE uid=20')['addprice'] === 0.5, 'batch rate skipped active child');
        } elseif ($action === 'user-create') {
            $created = $DB->get_row("SELECT uuid FROM qingka_wangke_user WHERE user='55555'");
            $check($created && (int) $created['uuid'] === $actor, 'created child has wrong parent');
        } elseif ($action === 'user-migrate') {
            $check((int) $DB->get_row("SELECT uuid FROM qingka_wangke_user WHERE uid='$actor'")['uuid'] === 21, 'migration did not update parent');
        } elseif (isset($case['balances'])) {
            $check((float) $DB->get_row('SELECT money FROM qingka_wangke_user WHERE uid=10')['money'] === (float) $case['balances'][0], 'parent balance incorrect');
            $check((float) $DB->get_row('SELECT money FROM qingka_wangke_user WHERE uid=20')['money'] === (float) $case['balances'][1], 'child balance incorrect');
            $check(count($after['log']) === ($actor === 1 ? 2 : 3), 'transfer ledger missing');
        }
        echo 'PASS: ' . $case['name'] . "\n";
    } catch (Throwable $error) {
        fwrite(STDERR, 'FAIL: ' . $case['name'] . ': ' . $error->getMessage() . "\n");
        exit(1);
    }
});

// Run the complete real API dispatcher with only its environment bootstrap replaced.
$directory = realpath(__DIR__ . '/../v1');
$source = file_get_contents($directory . '/index.php');
$source = str_replace("require_once __DIR__ . '/../../confing/common.php';", '', $source);
$source = str_replace('__DIR__', var_export($directory, true), $source);
eval(substr($source, 5));
