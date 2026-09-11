<?php
include('../confing/common.php');
if(!file_exists('../install/install.lock')){
    header('location:/install/');
}
if($islogin!=1){exit("<script language='javascript'>window.location.href='login';</script>");  }
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
<link rel="stylesheet" href="https://unpkg.com/element-ui/lib/theme-chalk/index.css">
<script src="https://unpkg.com/element-ui/lib/index.js"></script>
<!--<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">-->
<link rel="stylesheet" href="assets/css/apps.css" type="text/css" />
<link rel="stylesheet" href="assets/css/app.css" type="text/css" />
<link rel="stylesheet" href="assets/layui/css/layui.css" type="text/css" />
<!--<link href="http://cdn.bootcss.com/font-awesome/4.6.3/css/font-awesome.min.css" rel="stylesheet">-->
<link rel="stylesheet" href="assets/LightYear/js/bootstrap-multitabs/multitabs.min.css">
<link href="assets/LightYear/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/LightYear/css/style.min.css" rel="stylesheet">
<link href="assets/LightYear/css/materialdesignicons.min.css" rel="stylesheet">
<script src="//lib.baomitu.com/jquery/1.12.4/jquery.min.js"></script>
<script src="layer/3.1.1/layer.js"></script>

<script src="assets/js/jquery.min.js"></script>
    <link rel="stylesheet" href="assets/layuiadmin/layui/css/layui.css" media="all">
    <link rel="stylesheet" href="assets/layuiadmin/style/admin.css" media="all">
    <link rel="stylesheet" href="assets/css/element.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- 弹窗组件 -->
    <link rel="stylesheet" href="assets/layer/3.1.1/theme/default/layer.css" type="text/css" />
    <link rel="stylesheet" href="assets/css/apps.css" type="text/css" />
    <link rel="stylesheet" href="assets/css/app.css" type="text/css" />
    <link rel="stylesheet" href="assets/layui/css/layui.css" type="text/css" />
    <link rel="stylesheet" href="assets/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/LightYear/js/bootstrap-multitabs/multitabs.min.css">
    <link rel="stylesheet" href="assets/LightYear/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/LightYear/css/style.min.css">
    <script src="assets/js/jquery.min.js"></script>
    <script src="../assets/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="assets/LightYear/css/materialdesignicons.min.css">
    <script src="assets/layer/3.1.1/layer.js"></script>




</head>
<?php
if($userrow['active']=="0"){
alert('您的账号已被封禁！','login');
}
?>
<body>
    
    
    <script>
// 	//按键触发
// document.onkeydown = function(){
//   //禁止ctrl+u
//   if (event.ctrlKey && window.event.keyCode==85){
//   return false;
//   }
//   }
// </script>
 <script type="text/javascript">
// ((function() {
// 	var callbacks = [],
// 	timeLimit = 50,
// 	open = false;
// 	setInterval(loop, 1);
// 	return {
// 		addListener: function(fn) {
// 			callbacks.push(fn);
// 		},
// 		cancleListenr: function(fn) {
// 			callbacks = callbacks.filter(function(v) {
// 				return v !== fn;
// 			});
// 		}
// 	}
// 	function loop() {
// 		var startTime = new Date();
// 		debugger;
// 		if (new Date() - startTime > timeLimit) {
// 			if (!open) {
// 				callbacks.forEach(function(fn) {
// 					fn.call(null);
// 				});
// 			}
// 			open = true;
// 			window.stop();
// 			alert('禁止查看');
// 			window.location.reload();
// 		} else {
// 			open = false;
// 		}
// 	}
// })())
 
// .addListener(function() {
// 	window.location.reload();
// });
</script>
    	<script>
// 			document.onkeydown=function(){
// 				var e = window.event||arguments[0];
// 				window.console.log(e);
// 				if(e.keyCode == 123){//防止F12
// 						return false;
// 				}else if((e.ctrlKey)&&(e.shiftKey)&&(e.keyCode == 73)){//查看源代码快捷键
// 						return false;
// 				}else if((e.ctrlKey)&&(e.keyCode==85)){//查看源代码快捷键
// 						return false;
// 				}else if((e.ctrlKey)&&(e.keyCode==83)){//保持网页快捷键
// 					   return false;
// 				}
// 			}
// 			document.oncontextmenu=function(){//监听鼠标右键
// 				return false;
// 			}
// 		</script>
		
		
 		<script>

// onsoleManager.onOpen = function() {
//           //打开控制台，跳转
//           let target = "";
//           try {
//             window.open("about:blank", (target = "_self"));
//           } catch (err) {
//             let a = document.createElement("button");
//             a.onclick = function() {
//               window.open("about:blank", (target = "_self"));
//             };
//             a.click();
//           }
//         };
//         ConsoleManager.onClose = function() {
//           alert("Console is closed!!!!!");
//         };
//         ConsoleManager.init();
//       }
</script>
		
    
    
    
    
    
    
    
    
    
    
<script type="text/javascript">

// const handler = setInterval(function () { console.clear(); const before = new Date(); debugger; const after = new Date(); const cost = after.getTime() - before.getTime(); if (cost > 100) { } }, 1);
//         //屏蔽右键菜单
//         document.oncontextmenu = function (event) {
//             if (window.event) {
//                 event = window.event;
//             }
//             try {
//                 var the = event.srcElement;
//                 if (!((the.tagName == "INPUT" && the.type.toLowerCase() == "text") || the.tagName == "TEXTAREA")) {
//                     return false;
//                 }
//                 return true;
//             } catch (e) {
//                 return false;
//             }
//         }
//         //禁止f12
//         function fuckyou() {
//             window.open("/", "_blank"); //新窗口打开页面
//             window.close(); //关闭当前窗口(防抽)
//             window.location = "about:blank"; //将当前窗口跳转置空白页
//         }
//          //禁止Ctrl+U
//         var arr = [123, 17, 18];
//         document.oncontextmenu = new Function("event.returnValue=false;"), //禁用右键
 
//             window.onkeydown = function (e) {
//                 var keyCode = e.keyCode || e.which || e.charCode;
//                 var ctrlKey = e.ctrlKey || e.metaKey;
//                 console.log(keyCode + "--" + keyCode);
//                 if (ctrlKey && keyCode == 85) {
//                     e.preventDefault();
//                 }
//                 if (arr.indexOf(keyCode) > -1) {
//                     e.preventDefault();
//                 }
//             }

</script>