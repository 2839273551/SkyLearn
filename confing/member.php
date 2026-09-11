<?php
if(!defined('IN_CRONLITE'))exit();

$my=isset($_GET['my'])?$_GET['my']:null;

$clientip=real_ip();
$city=get_ip_city($clientip);
if(isset($_COOKIE["admin_token"]))
{
	$token=authcode(daddslashes($_COOKIE['admin_token']), 'DECODE', SYS_KEY);
	list($user, $sid) = explode("\t", $token);
	$udata = $DB->get_row("SELECT * FROM qingka_wangke_user WHERE user='$user' limit 1");
	$session=md5($udata['user'].$udata['pass'].$password_hash);
	if($session==$sid) {
		$DB->query("UPDATE qingka_wangke_user SET endtime='$date',ip='$clientip' WHERE user = '$user' ");
		$islogin=1;
		$userrow = $DB->get_row("SELECT * FROM qingka_wangke_user WHERE user='$user' limit 1");
		if($udata['active']==0){
			@header('Content-Type: text/html; charset=UTF-8');
			exit('您的账号已被封禁！');
		}
	}
}
?>