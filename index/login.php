<?php
include('../confing/common.php');

if (!file_exists('../install/install.lock')) {
	header('location:/install/');
}
$yqm = $_GET['yqm'];
if ($yqm == "") {
	$loginType = true;
} else {
	$loginType = false;
}
if ($islogin == 1) {
	exit("<script language='javascript'>window.location.href='../';</script>");
}


?>
<!DOCTYPE html>
<html lang="en">


<head>
	<meta charset="UTF-8">

	<title><?= $conf['sitename'] ?></title>
	<link rel="stylesheet" href="css/style.css">

	<link rel="icon" href="../favicon.ico" type="image/ico">
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title><?= $conf['sitename'] ?></title>
	<meta name="keywords" content="<?= $conf['keywords']; ?>" />
	<meta name="description" content="<?= $conf['description']; ?>" />


	<script src="./js/jquery.js"></script>

	<link href="assets/LightYear/css/bootstrap.min.css" rel="stylesheet">

</head>

<div class="box" id="login">
	<!-- 登录 - 开始 -->
	<div class="forms" v-if="loginType">
		<div class="form-wrapper">
			<div class="title">
				<h1>登录</h1>
				<span>欢迎来到我的空间</span>
			</div>
			<div class="input-wrapper">
				<div class="input-item">
					<span class="input-title">账号</span>
					<input type="text" class="ipt" v-model="dl.user" placeholder="Uesr">
				</div>
				<div class="input-item">
					<span class="input-title">密码</span>
					<input type="password" class="ipt" v-model="dl.pass" placeholder="Password">
				</div>

				<button class="btn" style="margin-top: 1.25rem;" @click="login">登录</button>

				<div class="login-tips">
					没有账户?<span @click="newlogin">注册一下呗！</span>
				</div>
			</div>
		</div>
	</div>
	<!-- 登录 - 结束 -->
	<!-- 注册 - 开始 -->
	<div class="forms" v-else>
		<div class="form-wrapper">
			<div class="title">
				<h1>注册</h1>
				<span>开始你的奇幻之旅</span>
			</div>
			<div class="input-wrapper">
				<div class="input-item">
					<span class="input-title">昵称*</span>
					<input type="text" class="ipt" v-model="reg.name" placeholder="NickName">
				</div>
				<div class="input-item">
					<span class="input-title">邀请码*</span>
					<input type="text" class="ipt" v-model="reg.yqm" placeholder="InvateCode">
				</div>



				<div class="input-item">
					<span class="input-title">账号*</span>
					<input type="text" class="ipt" v-model="reg.user" placeholder="User">
				</div>






				<style>

				</style>







				<div class="input-item">
					<span class="input-title">密码*</span>
					<input type="password" class="ipt" v-model="reg.pass" placeholder="Password">
					<span class="tips">必须至少有8个字符</span>
				</div>



				<button class="btn" @click="register">开始吧</button>

				<div class="login-tips">
					已经有一个帐户?<span @click="newlogin">去登录</span>
				</div>
			</div>
		</div>
	</div>
	<!-- 注册 - 结束 -->
	<div class="bg">
		<div class="text">REACH FOR THE STARS</div>
		<img src="assets/asset/1.jpg" class="bg-img img-one" alt="img-one">
		<img src="assets/asset/2.jpg" class="bg-img img-two" alt="img-two">
	</div>
</div>


<script type="text/javascript" src="assets/LightYear/js/jconfirm/jquery-confirm.min.js"></script>
<script type="text/javascript" src="assets/LightYear/js/main.min.js"></script>
<script src="layer/3.1.1/layer.js"></script>
<script src="assets/js/vue.min.js"></script>
<script src="assets/js/vue-resource.min.js"></script>
<script src="assets/layui/js/axios.min.js"></script>
<script>
	var vm = new Vue({
		el: "#login",
		data: {
			loginType: true,
			title: "你在看什么呢？我写的代码好看吗",
			dl: {},
			reg: {
				yqm: "<?= $yqm; ?>",
			}
		},
		methods: {
			newlogin: function () {
				this.loginType = !this.loginType
			},
			login: function () {
				if (!this.dl.user || !this.dl.pass


				) {
					layer.msg('账号、密码及验证码不能为空', {
						icon: 2
					});
					return
				}
				var loading = layer.load();
				vm.$http.post("/apisub.php?act=login", {
					user: this.dl.user,
					pass: this.dl.pass,

				}, {
					emulateJSON: true
				}).then(function (data) {
					layer.close(loading);
					if (data.data.code == 1) {
						layer.msg(data.data.msg, {
							icon: 1
						});
						setTimeout(function () {
							window.location.href = "/"
						}, 1000);
					} else if (data.data.code == 5) {
						vm.login2();
					} else {
						layer.msg(data.data.msg, {
							icon: 2
						});
					}
				});

			},
			register: function () {
				if (!this.reg.user || !this.reg.pass || !this.reg.name || !this.reg.yqm) {
					layer.msg('用户名、密码、昵称和邀请码不能为空', {
						icon: 2
					});
					return;
				}

				var loading = layer.load();

				// 声明 postData 对象
				var postData = {
					name: this.reg.name,
					user: this.reg.user,
					pass: this.reg.pass,
					yqm: this.reg.yqm
				};

				// 如果 pushplus 有值，则添加到 postData 中
				if (this.reg.pushplus) {
					postData.pushplus = this.reg.pushplus;
				}

				this.$http.post("/apisub.php?act=register", postData, {
					emulateJSON: true
				}).then(function (data) {
					layer.close(loading);
					if (data.data.code == 1) {
						this.loginType = true;
						this.dl.user = this.reg.user;
						this.dl.pass = this.reg.pass;
						layer.msg(data.data.msg, {
							icon: 1
						});
					} else {
						layer.msg(data.data.msg, {
							icon: 2
						});
					}
				});
			},

			login2: function () {
				layer.prompt({
					title: '管理二次验证',
					formType: 3
				}, function (pass2, index) {
					var loading = layer.load();
					vm.$http.post("/apisub.php?act=login", {
						user: vm.dl.user,
						pass: vm.dl.pass,
						pass2: pass2
					}, {
						emulateJSON: true
					}).then(function (data) {
						layer.close(loading);
						if (data.data.code == 1) {
							layer.msg(data.data.msg, {
								icon: 1
							});
							setTimeout(function () {
								window.location.href = "/"
							}, 1000);
						} else {
							layer.msg(data.data.msg, {
								icon: 2
							});
						}
					});
				});
			}
		}
	});

	$('#connect_qq').click(function () {
		var ii = layer.load(0, {
			shade: [0.1, '#fff']
		});
		$.ajax({
			type: "POST",
			url: "../qq_login.php",
			data: { "type": 'qq' },
			dataType: 'json',
			success: function (data) {
				layer.close(ii);
				if (data.code == 1) {
					window.location.href = data.url;
				} else {
					layer.alert(data.msg, {
						icon: 7
					});
				}
			}
		});
	});

</script>
<script>
	const imgs = document.querySelectorAll('.bg-img')
	let flag = false
	setInterval(function () {
		if (flag) {
			imgs[0].style.opacity = 0
			imgs[1].style.opacity = 1
		} else {
			imgs[0].style.opacity = 1
			imgs[1].style.opacity = 0
		}
		flag = !flag
	}, 5000)
</script>

<body>
</body>

</html>