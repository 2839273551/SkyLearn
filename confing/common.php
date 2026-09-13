<?php







// error_reporting(E_ALL); ini_set("display_errors", 1);
error_reporting(0);
define('IN_CRONLITE', true);
define('ROOT', dirname(__FILE__).'/');//返回当前文件路径的 路径部分,就是取当前目录
require ROOT.'config.php';
define('config_path', dirname(__FILE__).'/');
$auth = require_once config_path.'authcode.php';
define('SYS_KEY', "qingka");
date_default_timezone_set("PRC");
$date = date("Y-m-d H:i:s");
$jtdate=date("Y-m-d");
$ztdate=date("Y-m-d",strtotime("-1 day"));

session_start();
if(is_file(ROOT.'360safe/360webscan.php')){//360网站卫士
    require_once(ROOT.'360safe/360webscan.php');
}
// if(phpversion() != 7.3){exit('要求PHP版本7.3');}
require ROOT.'sqlite.php';
$scriptpath=str_replace('\\','/',$_SERVER['SCRIPT_NAME']);
$sitepath = substr($scriptpath, 0, strrpos($scriptpath, '/'));
$siteurl = ($_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$sitepath.'/';
if(!isset($port))$port='3306';
//连接数据库
include_once(ROOT."sqlite.php");
$DB=new DB($host,$user,$pwd,$dbname,$port);
include ROOT."epay/epay.config.php"; 
$sql1=$DB->query("SELECT * FROM `qingka_wangke_config`");
while($row=$DB->fetch($sql1)){
	$conf[$row['v']]=$row['k'];
}
$password_hash='!@#%!s?';
include ROOT."../Checkorder/configuration.php";
require_once ROOT."docking_logger.php";     
$alipay_config['sign_type']    = strtoupper('MD5');
$alipay_config['input_charset']= strtolower('utf-8');
$alipay_config['transport']    = 'http';
$alipay_config['apiurl']        = $conf['epay_api'];
$alipay_config['partner']		= $conf['epay_pid'];
$alipay_config['key']			= $conf['epay_key'];


if(!defined('IN_CRONLITE'))exit();
$my=isset($_GET['my'])?$_GET['my']:null;
// $clientip = real_ip();
//$city=get_ip_city($clientip);
if(isset($_COOKIE["admin_token"]))
{
	$token=authcode(daddslashes($_COOKIE['admin_token']), 'DECODE', SYS_KEY);
	list($user, $sid) = explode("\t", $token);
	$udata = $DB->get_row("SELECT * FROM qingka_wangke_user WHERE user='$user' limit 1");
	$session=md5($udata['user'].$udata['pass'].$password_hash);
	if($session==$sid) {
		if (!isset($udata['endtime']) || (strtotime($date) - strtotime($udata['endtime']) > 60) || ($udata['ip'] !== $clientip)) {
			$DB->query("UPDATE qingka_wangke_user SET endtime='$date',ip='$clientip' WHERE user = '$user' ");
		}
		$islogin=1;
		$userrow = $DB->get_row("SELECT * FROM qingka_wangke_user WHERE user='$user' limit 1");
		if($udata['active']==0){
			@header('Content-Type: text/html; charset=UTF-8');
			exit('您的账号已被封禁！');
		}
	}
}
?>