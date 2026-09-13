<?php

$_GET['action'] = 'scheduler-cron';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_HOST'] = 'sk.yunxnet.cn';

require_once __DIR__ . '/../admin-api/v1/index.php';
