<?php
include('../confing/common.php');
$uid=$_POST['uid'];
$money=$_POST['money'];
$token =$_POST['token'];

$utk = "houmentoken"; // 对接token  token不对充值不上 防止被抓接口 建议使用md5加密的数据 没事 这里随便写别人不知道就行

if($token!=$utk){
    exit('{"code":-1,"msg":"操作失败"}');
}


$userrow['uid'] =1; //假定是管理员
$userrow=$DB->get_row("select * from qingka_wangke_user where uid='1' limit 1");

if(!preg_match('/^[0-9.]+$/', $money))exit('{"code":-1,"msg":"充值金额不合法"}');
//充值扣费计算：扣除费用=充值金额*(我的总费率/代理费率-等级差*2%)
if($money<10 && $userrow['uid']!=1){
	exit('{"code":-1,"msg":"最低充值10元"}');
}

$row=$DB->get_row("select * from qingka_wangke_user where uid='$uid' limit 1");
if($row['uuid']!=$userrow['uid'] && $userrow['uid']!=1){
	exit('{"code":-1,"msg":"该用户你的不是你的下级,无法充值"}');
}

if($userrow['uid']==$uid){
	exit('{"code":-1,"msg":"自己不能给自己充值哦"}');
}

$kochu=round($money*($userrow['addprice']/$row['addprice']),2);//充值	
if($userrow['money']<$kochu){
	exit('{"code":-1,"msg":"您当前余额不足,无法充值"}');
}

$wdkf=round($userrow['money']-$kochu,2);
$xjkf=round($row['money']+$money,2);    
$DB->query("update qingka_wangke_user set money='$wdkf' where uid='{$userrow['uid']}' ");//我的扣费
$DB->query("update qingka_wangke_user set money='$xjkf',zcz=zcz+'$money' where uid='$uid' ");//下级增加	    
wlog($userrow['uid'],"代理充值","成功给账号为[{$row['user']}]的靓仔充值{$money}元,扣除{$kochu}元",-$kochu);
wlog($row['uid'],"上级充值","{$userrow['name']}成功给你充值{$money}元",+$money);
exit('{"code":0,"msg":"充值'.$money.'元成功,实际扣费'.$kochu.'元"}');

?>