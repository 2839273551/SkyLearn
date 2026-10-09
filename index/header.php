<?php
include('../confing/common.php');
if($islogin!=1){exit("<script language='javascript'>window.location.href='login';</script>");}
if(!file_exists('../install/install.lock')){
    header('location:/install/');
}
?>
<!DOCTYPE html>
<html lang="zh">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
<title><?=$conf['sitename']?></title>
<meta name="keywords" content="<?=$conf['keywords'];?>" />
<meta name="description" content="<?=$conf['description'];?>" />
<link rel="icon" href="../favicon.ico" type="image/ico">
<meta name="author" content=" ">
<script src="assets/js/jquery.min.js"></script>
<script src="layer/3.1.1/layer.js"></script>
<link rel="stylesheet" href="assets/layuiadmin/layui/css/layui.css" media="all">
<link rel="stylesheet" href="assets/layuiadmin/style/admin.css" media="all">
<link href="assets/css/style.css" rel="stylesheet">
<?php if ($separately != '') {?>
<link href="<?=$separately;?>" rel="stylesheet">
<? } ?>
<script src="assets/layuiadmin/layui/layui.js"></script>
</head>
<?php
if($userrow['active']=="0"){
alert('您的账号已被封禁！','login');
}
?>


