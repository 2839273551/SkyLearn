<?php
include('header.php');
?>



<body>
	<div class="layui-fluid">
		<div class="wrapper-md control" id="userindex">
			<div class="layui-row layui-col-space15 layui-anim">
				<?php if ($userrow['uuid'] == 1) { ?>
					<div class="layui-col-sm12 layui-col-md12 layui-col-xs12">
						<div class="layui-card">
							<div class="layui-card-header">
								站长直属代理公告
								<a href="/index//smgz" class="layui-badge layui-bg-green layuiadmin-badge">加入通知频道</a>
							</div>
							<div class="layui-card-body layuiadmin-card-list">

								<?= $conf['zsgonggao']; ?>
							</div>
						</div>
					</div>
				<? } ?>



				<style>
					.el-col {
						margin-bottom: 5px;
					}

					.layui-icon {
						font-size: inherit;
					}

					@media (max-width: 880px) {
						.el-card__body>div {
							height: auto !important;
						}

						.el-card__body>div>.layui-hide-xs {
							display: none;
						}
					}

					.layui-card-header {
						border-bottom: 1px solid #ededed;
					}
				</style>

				<div class="layui-col-sm6 layui-col-md4 layui-col-xs6">
					<div class="layui-card">
						<div class="layui-card-header">
							新增订单
							<a lay-href="add" class="layui-badge layui-bg-green layuiadmin-badge">网课下单</a>
						</div>
						<div class="layui-card-body layuiadmin-card-list">
							<p class="layuiadmin-big-font">{{row.dailitongji.jrjd}}</p>
							<p>
								全部订单
								<span class="layuiadmin-span-color">{{row.dd}} <i
										class="console-link-block-icon layui-icon layui-icon-list"></i></span>
							</p>
						</div>
					</div>
				</div>
				<div class="layui-col-sm6 layui-col-md4 layui-col-xs6">
					<div class="layui-card">
						<div class="layui-card-header">
							新增代理
							<a lay-href="list2" class="layui-badge layui-bg-orange layuiadmin-badge">订单管理</a>
						</div>
						<div class="layui-card-body layuiadmin-card-list">
							<p class="layuiadmin-big-font">{{row.dailitongji.dlzc}}</p>
							<p>
								全部代理
								<span class="layuiadmin-span-color">{{row.dailitongji.dlzs}} <i
										class="console-link-block-icon layui-icon layui-icon-user"></i></span>
							</p>
						</div>
					</div>
				</div>
				<div class="layui-col-sm6 layui-col-md4 layui-col-xs6">
					<div class="layui-card">
						<div class="layui-card-header">
							今日上线
							<a lay-href="userlist" class="layui-badge layui-bg-cyan layuiadmin-badge">代理管理</a>
						</div>
						<div class="layui-card-body layuiadmin-card-list">
							<p class="layuiadmin-big-font">{{row.dailitongji.dldl}}</p>
							<p>
								今日上线的代理
								<span class="layuiadmin-span-color"><i
										class="console-link-block-icon layui-icon layui-icon-date"></i></span>
							</p>
						</div>
					</div>
				</div>
				<div class="layui-col-sm6 layui-col-md4 layui-col-xs6">
					<div class="layui-card">
						<div class="layui-card-header">
							我的费率
							<a @click="sjfl" class="layui-badge layui-bg-black layuiadmin-badge">升级费率</a>
						</div>
						<div class="layui-card-body layuiadmin-card-list">
							<p class="layuiadmin-big-font">{{row.addprice}}</p>
							我的等级
							<span class="layuiadmin-span-color" id="Level">
								<p v-if="row.addprice == 0.60">
									五级<i class="console-link-block-icon layui-icon layui-icon-diamond"></i>
								</p>
								<p v-if="row.addprice == 0.50">
									四级<i class="console-link-block-icon layui-icon layui-icon-diamond"></i>
								</p>
								<p v-if="row.addprice == 0.40">
									三级<i class="console-link-block-icon layui-icon layui-icon-diamond"></i>
								</p>
								<p v-if="row.addprice == 0.30">
									二级<i class="console-link-block-icon layui-icon layui-icon-diamond"></i>
								</p>
								<p v-if="row.addprice == 0.20">
									顶级<i class="console-link-block-icon layui-icon layui-icon-diamond"></i>
								</p>
							</span>
							<p></p>
						</div>
					</div>
				</div>
				<div class="layui-col-sm6 layui-col-md4 layui-col-xs6">
					<div class="layui-card">
						<div class="layui-card-header">
							邀请费率
							<a @click="szyqprice" class="layui-badge layui-bg-blue layuiadmin-badge">设置费率</a>
						</div>
						<div class="layui-card-body layuiadmin-card-list">
							<p class="layuiadmin-big-font">{{row.yqprice==''?'暂无':row.yqprice}}</p>
							<p>
								邀请码
								<span class="layuiadmin-span-color">{{row.yqm==''?'暂无':row.yqm}}<i
										class="console-link-block-icon layui-icon layui-icon-release"></i></span>
							</p>
						</div>
					</div>
				</div>

				<div class="layui-col-sm6 layui-col-md4 layui-col-xs6">
					<div class="layui-card">
						<div class="layui-card-header">
							我的余额
							<?php if ($userrow['uuid'] != 1) { ?>
								<?php if ($conf['zxczkg'] == 1) { ?>
									<a lay-href="list2" class="layui-badge layui-bg-red layuiadmin-badge"> <?php } ?>
									<?php if ($conf['zxczkg'] == 0) { ?>
										<a class="layui-badge layui-bg-red layuiadmin-badge"> <?php } ?>订单管理</a>
								<?php } ?>

								<?php if ($userrow['uuid'] == 1) { ?>	<?php if ($conf['zxczkg'] == 1) { ?>
										<a lay-href="pay" class="layui-badge layui-bg-red layuiadmin-badge"> <?php } ?>
										<?php if ($conf['zxczkg'] == 0) { ?>
											<a class="layui-badge layui-bg-red layuiadmin-badge"> <?php } ?>在线充值</a><?php } ?>
						</div>
						<div class="layui-card-body layuiadmin-card-list">
							<p class="layuiadmin-big-font">{{row.money}}</p>
							<p>
								总充值
								<span class="layuiadmin-span-color">{{row.zcz==null?'0':row.zcz}}<i
										class="console-link-block-icon layui-icon layui-icon-rmb"></i></span>
							</p>
						</div>
					</div>
				</div>

				<div class="layui-col-xs12">
					<div class="layui-card">
						<div class="layui-card-header">便捷导航</div>
						<div class="layui-card-body">
							<center>
								<div class="layui-btn-container">
									<a class="layui-btn layui-btn-radius layui-btn-sm"
										style="background-color: rgb(1, 170, 237)" @click='sjgg'>上级公告</a>
									<a class="layui-btn layui-btn-radius layui-btn-sm"
										style="background-color: rgb(102, 187, 106)" @click="ggxg()">设置公告</a>
									<a lay-href="docking" class="layui-btn layui-btn-radius layui-btn-sm"
										style="background-color: rgb(48, 63, 159)">对接文档</a>
									<a lay-href="help" class="layui-btn layui-btn-radius layui-btn-sm"
										style="background-color: rgb(41, 3, 251)">帮助中心</a>
									<a class="layui-btn layui-btn-radius layui-btn-sm"
										style="background-color: rgb(41, 3, 251)" @click="yecz">上级联系方式</a>
									<?php if ($conf['qdkg'] == 1) { ?>
										<!--<a class="layui-btn layui-btn-radius layui-btn-sm" style="background-color: rgb(30, 159, 255)" @click="qd">每日签到</a>-->
										<!-- <? } ?>-->


									<?php if ($userrow['uid'] == 1) { ?>
										<a class="layui-btn layui-btn-radius layui-btn-sm"
											style="background-color: rgb(1, 170, 237)" @click='jrsj'>今日数据</a>
									<?php } ?>

									<?php if ($userrow['uuid'] == 1) { ?>
										<!--<a class="layui-btn layui-btn-radius layui-btn-sm" style="background-color: rgb(1, 170, 237)" @click='vip'>会员</a>-->
									<?php } ?>


								</div>
							</center>
						</div>
					</div>

				</div>

				<!--<?php if ($userrow['uuid'] == 1) { ?>-->
				<?php } ?>
				<!-- 新卡片：QQ 群二维码 -->
				<div class="layui-row layui-col-space15">
					<div class="layui-col-sm12 layui-col-md12 layui-col-xs12">
						<div class="layui-card">
							<div class="layui-card-header">
								<span>公众号</span>
								&nbsp;<a href="/index/smgz" target="_blank" class="layui-btn layui-btn-xs">点击订阅</a>
								<?php if ($conf['qdkg'] == 1) { ?> <a class="layui-btn layui-btn-xs layui-bg-red"
										@click="qd">每日签到</a> <? } ?>

							</div>
							<div class="layui-card-body">
								注意：订阅后，请到个人资料里面token
							</div>
						</div>
					</div>
				</div>










				<div class="layui-row layui-col-space15">
					<div class="layui-col-sm12 layui-col-md12 layui-col-xs12">
						<div class="layui-card">
							<div class="layui-card-header">
								<span><i class="layui-icon layui-icon-notice"></i> 实时 CPU 和负载状态</span>
							</div>
							<div class="layui-card-body">
								<p>CPU 使用率: <span id="cpu"></span></p>
								<p>负载状态: <span id="loadavg"></span></p>
							</div>
						</div>
					</div>
				</div>

				<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
				<script>
					$(document).ready(function () {
						function updateStatus() {
							$.ajax({
								url: 'status.php', // 服务器端脚本获取 CPU 和负载状态
								dataType: 'json', // 期望的响应数据类型
								success: function (data) {
									// 更新页面元素
									$('#cpu').text(data.cpu);
									$('#loadavg').text(data.loadavg);
								},
								error: function (xhr, status, error) {
									// 处理错误
									console.error(error);
								}
							});
						}

						// 初始加载状态
						updateStatus();

						// 使用 setTimeout 代替 setInterval，确保前一个 AJAX 完成后再发送下一个请求
						(function updateLoop() {
							setTimeout(function () {
								updateStatus();
								updateLoop();
							}, 5000);
						})();
					});
				</script>



				<!--<div class="layui-col-xs12 layui-col-sm6">-->
				<!--    <div class="layui-card">-->
				<!--        <div class="layui-card-header">-->
				<!--            平台公告-->

				<!--        </div>-->
				<!--        <div class="layui-card-body">-->

				<!--                <span v-html="row.notice"></span>-->
				<!--            </div>-->
				<!--            <div v-else>-->
				<!--                <div style="padding: 20px 0;text-align: center;">-->
				<!--                    暂无公告-->
				<!--                </div>-->
				<!--            </div>-->
				<!--        </div>-->
				<!--    </div>-->


				<div class="layui-row layui-col-space15">
					<div class="layui-col-sm12 layui-col-md12 layui-col-xs12">
						<div class="layui-card">
							<div class="layui-card-header">
								实时通知公告
							</div>
							<div class="ok-card-body clearfix">
								<div class="layui-row layui-col-space10">
									<div class="layui-col-md12">
										<div class="layui-card">
											<div class="layui-card-header"><b>上级公告</b>&nbsp;&nbsp;<div
													class="layui-btn layui-btn-normal layui-btn-xs" @click="ggxg">修改我的
												</div>
											</div>
											<div class="layui-card-body">
												<p><span v-html="row.sjnotice"></span></p>
												<div class="layui-btn layui-btn-xs">上级UID：<?= $userrow['uuid'] ?></div>
											</div>
										</div>
									</div>
									<div v-for="res1 in row1.data" class="layui-col-md12">
										<div class="layui-card">
											<div class="layui-card-header"><b>{{res1.title}}</b></div>
											<div class="layui-card-body">
												<p><span v-html="res1.content"></span></p>
												<div class="layui-btn layui-btn-danger layui-btn-xs"
													v-if="res1.zhiding==1">置顶</div>
												<div class="layui-btn layui-btn-xs" v-if="res1.uid==1">大大</div>
												<div class="layui-btn layui-btn-normal layui-btn-xs">{{res1.time}}</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>









		<!--<div class="layui-col-xs12 layui-col-sm6">-->
		<!--  <div class="layui-card">-->
		<!--    <div class="layui-card-header">-->
		<!--      个人信息-->
		<!--<button-->
		<!--         type="button"-->
		<!--         class="layui-btn layui-btn-xs"-->
		<!--         style="background-color: rgb(30, 159, 255)"-->
		<!--         @click="qd"-->
		<!--       >-->
		<!--     每日签到-->
		<!--       </button>-->
		<!--          <a lay-href="passwd" class="layui-badge layui-bg-red layuiadmin-badge">修改密码</a>-->
		<!--        </div>-->
		<!--        <div class="layui-card-body">-->
		<!--          <form action="" lay-filter="component-form-element" class="layui-form">-->
		<!--            <div class="layui-row layui-col-space10 layui-form-item">-->
		<!--              <div class="layui-row layui-col-space10 layui-form-item">-->



		<!--<div class="layui-col-lg12">-->
		<!--  <label class="layui-form-label">登录账号：</label>-->
		<!--  <div class="layui-input-block">-->
		<!--    <input-->
		<!--      type="text"-->
		<!--      name="source"-->
		<!--      lay-tips="登录账号"-->
		<!--      disabled="disabled"-->
		<!--      autocomplete="off"-->
		<!--      :value="row.user"-->
		<!--      class="layui-input"-->
		<!--    />-->
		<!--  </div>-->
		<!--</div>-->
		<!--<div class="layui-col-lg12">-->
		<!--  <label class="layui-form-label">我的UID：</label>-->
		<!--  <div class="layui-input-block">-->
		<!--    <input-->
		<!--      type="text"-->
		<!--      name="source"-->
		<!--      lay-tips="我的UID"-->
		<!--      disabled="disabled"-->
		<!--      autocomplete="off"-->
		<!--      :value="row.uid"-->
		<!--      class="layui-input"-->
		<!--    />-->
		<!--  </div>-->
		<!--</div>-->
		<!--<div class="layui-col-lg12">-->
		<!--  <label class="layui-form-label">我的KEY：</label>-->
		<!--  <div class="layui-input-block" >-->
		<!--当row.key等于0时显示开通KEY -->
		<!--      <a v-if="row.key == 0" class="layui-badge layui-bg-blue layuiadmin-badge" @click="ktapi">开通KEY</a>-->
		<!--否则显示更换KEY -->
		<!--      <a v-else class="layui-badge layui-bg-blue layuiadmin-badge" @click="ghapi">更换KEY</a>-->
		<!--    <span v-if="row.key!=0">-->
		<!--    <input-->
		<!--      type="text"-->
		<!--      name="source"-->
		<!--      lay-tips="我的KEY"-->
		<!--      disabled="disabled"-->
		<!--      autocomplete="off"-->
		<!--      :value="row.key"-->
		<!--      class="layui-input"-->
		<!--    />-->
		<!--    </span>-->
		<!--    <span v-else="row.key==0">-->
		<!--    <input-->
		<!--      type="text"-->
		<!--      name="source"-->
		<!--      lay-tips="我的KEY"-->
		<!--      disabled="disabled"-->
		<!--      autocomplete="off"-->
		<!--      placeholder="未获取对接KEY"-->
		<!--      class="layui-input"-->
		<!--    />-->
		<!--    </span>-->
		<!--  </div>-->
		<!--</div>-->
		<!--<div class="layui-col-lg12">-->
		<!--  <label class="layui-form-label">登录 IP：</label>-->
		<!--  <div class="layui-input-block">-->
		<!--    <input-->
		<!--      type="text"-->
		<!--      name="source"-->
		<!--      lay-tips="登录IP"-->
		<!--      disabled="disabled"-->
		<!--      autocomplete="off"-->
		<!--      placeholder="未绑定QQ,无法获取QQID" -->
		<!--      value="<?php echo $userrow['ip'] ?>"-->
		<!--      class="layui-input"-->
		<!--    />-->
		<!--  </div>-->
		<!--</div>-->
		<!--              </div>-->
		<!--            </div>-->

		<!--          </form>-->
		<!--        </div>-->
		<!--      </div>-->
		<!--    </div>-->









		<!--<div class="layui-col-xs12 layui-col-sm6">-->

		<!--  <div class="layui-card" >-->
		<!--      <div class="layui-card-header">订单数据</div>-->
		<!--      <div class="layui-card-body">-->


		<!--                  <ul class="layui-row layui-col-space10">-->
		<!--                      <li class="layui-col-xs6">-->
		<!--                          <a lay-href="list" class="layui-card-body">-->
		<!--                              <h3>已下单</h3>-->
		<!--                              <p><cite style="color: #000000;">-->
		<?php
		// if ($userrow['uid'] != "1") {
		//     $a = $DB->count("select count(*) from qingka_wangke_order where uid='{$userrow['uid']}'");
		//     echo $a . "条";
		// } else {
		//     $a = $DB->count("select count(*) from qingka_wangke_order");
		//     echo $a . "条";
		// }
		?>
		<!--        </cite></p>-->
		<!--    </a>-->
		<!--</li>-->
		<!--<li class="layui-col-xs6">-->
		<!--    <a lay-href="list" class="layui-card-body">-->
		<!--        <h3>进行中</h3>-->
		<!--        <p><cite style="color: #6699FF;">-->
		<?php
		// if ($userrow['uid'] != "1") {
		//     $jxz = $DB->count("select count(*) from qingka_wangke_order where status ='进行中' AND uid='{$userrow['uid']}' ");
		//     echo $jxz . "条";
		// } else {
		//     $a = $DB->count("select count(*) from qingka_wangke_order where status ='进行中'");
		//     echo $a . "条";
		// }
		?>
		<!--        </cite></p>-->
		<!--    </a>-->
		<!--</li>-->
		<!--<li class="layui-col-xs6">-->
		<!--    <a lay-href="list" class="layui-card-body">-->
		<!--        <h3>已完成</h3>-->
		<!--        <p><cite style="color: #99CC66;">-->
		<?php
		// if ($userrow['uid'] != "1") {
		//     $a = $DB->count("select count(*) from qingka_wangke_order where status='已完成' AND uid='{$userrow['uid']}' ");
		//     echo $a . "条";
		// } else {
		//     $a = $DB->count("select count(*) from qingka_wangke_order where status='已完成'");
		//     echo $a . "条";
		// }
		?>
		<!--        </cite></p>-->
		<!--    </a>-->
		<!--</li>-->
		<!--<li class="layui-col-xs6">-->
		<!--    <a href="list" class="layui-card-body">-->
		<!--        <h3>异常订单</h3>-->
		<!--        <p><cite style="color: #FF5722;">-->
		<?php
		// if ($userrow['uid'] != "1") {
		//     $yc = $DB->count("select count(*) from qingka_wangke_order where status='异常' AND uid='{$userrow['uid']}'");
		//     echo $yc . "条";
		// } else {
		//     $a = $DB->count("select count(*) from qingka_wangke_order where status='异常'");
		//     echo $a . "条";
		// }
		?>
		<!--                         </cite></p>-->
		<!--                     </a>-->
		<!--                 </li>-->
		<!--             </ul>-->
		<!--         </div>-->
		<!--     </div>-->

		<!--</div>-->








		<?php require_once("footer.php"); ?>

		<link href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css" rel="stylesheet" />
		<script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/toastr.min.js"></script>
		<style>
			/* 移除默认的背景图像 */
			#toast-container>.toast-success {
				background-image: none !important;
			}

			/* 使用 ::before 伪元素添加笑脸符号 */
			#toast-container>.toast-success:before {
				content: "😊";
				font-size: 20px;
				/* 符号的大小 */
				position: absolute;
				/* 使用绝对定位来精确控制位置 */
				top: 50%;
				/* 垂直居中 */
				left: 15px;
				/* 根据 Toastr 容器的内边距调整 */
				transform: translateY(-50%);
				/* 确保垂直居中 */
			}
		</style>
		<script>
			// 设置Toastr的配置项
			toastr.options = {
				"closeButton": false,
				"debug": false,
				"newestOnTop": true,
				"progressBar": true,
				"positionClass": "toast-top-right",
				"preventDuplicates": false,
				"onclick": null,
				"showDuration": "300",
				"hideDuration": "1000",
				"timeOut": "5000",
				"extendedTimeOut": "1000",
				"showEasing": "swing",
				"hideEasing": "linear",
				"showMethod": "fadeIn",
				"hideMethod": "fadeOut"
			}

			// 检查localStorage中的toastrDisplayed时间戳
			var lastToastTime = localStorage.getItem('toastrDisplayed');
			var currentTime = new Date().getTime();

			// 如果没有记录或者记录的时间超过了半小时（1800000毫秒），显示通知
			if (lastToastTime === null || currentTime - lastToastTime > 1200000) {
				toastr.success('欢迎老板登录,有问题滴滴');
				// 更新localStorage中的时间戳
				localStorage.setItem('toastrDisplayed', currentTime);
			}
		</script>
		<script>
			layui.config({
				base: './assets/layuiadmin/' //静态资源所在路径
			}).extend({
				index: 'lib/index' //主入口模块
			}).use(['index'], function () {
				var $ = layui.$
					, admin = layui.admin
					, element = layui.element
					, router = layui.router();

				// element.render();
				// layui.use('layer',function(){
				//     layer.ready(function(){
				//             admin.popup({
				//             title: '公告'
				//             ,shade: 0
				//             ,anim: -1
				//             ,area: ['280px', '150px']
				//             ,id: 'layadmin-layer-skin-test'
				//             ,skin: 'layui-anim layui-anim-upbit'
				//             ,content: '我特么来了'
				//         })
				//     });  
				// });
			});

			var vm = new Vue({
				el: "#userindex",
				data: {
					row: null,
					uid: '<?= $userrow['uid'] ?>' === '1' ? true : false,

					row1: null,
					inte: '',
					oldpass: '',
					newpass: '',
					version: {
						v: '',
						c: '',
						msg: ''
					},
					home_top_notice_open: <?= json_encode($conf['home_top_notice_open']) ?>,
					notice_open: <?= json_encode($conf['notice_open']) ?>,



				},
				methods: {





					userinfo: function () {
						var load = layer.load();
						this.$http.post("/apisub.php?act=userinfo").then(function (data) {
							layer.close(load);
							if (data.data.code == 1) {
								this.row = data.data;
							} else {
								layer.alert(data.data.msg, { icon: 2 });
							}
						});
					},

					vip: function () {
						let vipStatus = parseInt(this.row.vip); // 转换为数字

						if (vipStatus === 1) {
							layer.alert(`会员到期时间：${this.row.vipexpire}`, { icon: 1, title: '会员信息' });
						} else {
							layer.confirm(
								'<div class="form-check mx-2">' +
								'<input class="form-check-input" type="radio" id="kt30" name="kttime" value="30" checked>' +
								'<label class="form-check-label" for="kt30">' +
								'30天（24元）' +
								'</label>' +
								'</div>' +
								'<div class="form-check mx-2">' +
								'<input class="form-check-input" type="radio" id="kt90" name="kttime" value="90">' +
								'<label class="form-check-label" for="kt90">' +
								'90天（54元）' +
								'</label>' +
								'</div>' +
								'<div class="form-check mx-2">' +
								'<input class="form-check-input" type="radio" id="kt180" name="kttime" value="180">' +
								'<label class="form-check-label" for="kt180">' +
								'180天（72元）' +
								'</label>' +
								'</div>',
								{
									title: '开通会员',
									btn: ['确认开通', '取消'],
									btn1: function (index, layero) {
										var viptime = $('input:radio[name="kttime"]:checked').val();
										var load = layer.load(2);
										$.ajax({
											type: "POST",
											url: "../apivip.php?act=ktvip",
											data: { "viptime": viptime },
											dataType: 'json',
											success: function (data) {
												layer.close(load);
												if (data.code == 1) {
													layer.msg(data.msg, { icon: 1, time: 1000 }, function () {
														location.href = '';
													});
												} else {
													layer.msg(data.msg, { icon: 2 });
												}
											}
										});
									},
									btn2: function (index, layero) {
										// 取消按钮的回调
									}
								}
							);
						}
					},



					jrsj: function () {
						layer.alert(
							"总用户:<?php $a = $DB->count("select count(*) from qingka_wangke_user ");
							echo $a; ?><br>今日新增用户:<?php $a = $DB->count("select count(*) from qingka_wangke_user where addtime>'$jtdate'  ");
							 echo $a; ?><br>今日订单:<?php $a = $DB->count("select count(*) from qingka_wangke_order where addtime>'$jtdate'  ");
							  echo $a; ?><br>今日销售:<?php $a = $DB->query("select * from qingka_wangke_order where addtime>'$jtdate'  ");
							   while ($c = $DB->fetch($a)) {
								   $zcz += $c['fees'];
							   }
							   echo $zcz; ?>元",
							{ icon: 1, title: "今日数据" }
						);
					},

					yecz: function () {
						layer.alert(
							"任何问题请联系上级QQ：" + "【" +
							this.row.sjuser + "】" +
							"，人工充值请联系你的上级。（下级点充值，此处将显示您的QQ）",
							{ icon: 1, title: "上级联系方式" }
						);
					},
					sjfl: function () {
						layer.alert(
							"请联系上级QQ：" + "【" +
							this.row.sjuser + "】" +
							"，进行升级）",
							{ icon: 1, title: "升级费率" }
						);
					},
					ktapi: function () {
						layer.confirm(
							"后台余额满300元可免费开通，反之需花费10元开通",
							{
								title: "温馨提示",
								icon: 1,
								btn: ["确定", "取消"] //按钮
							},
							function () {
								var load = layer.load();
								axios.get("/apisub.php?act=ktapi&type=1").then(function (data) {
									layer.close(load);
									if (data.data.code == 1) {
										layer.alert(
											data.data.msg,
											{ icon: 1, title: "温馨提示" },
											function () {
												setTimeout(function () {
													window.location.href = "";
												});
											}
										);
									} else {
										layer.msg(data.data.msg, { icon: 2 });
									}
								});
							}
						);
					},



					//                       gglist: function () {
					//       var load = layer.load();
					//       this.$http.post("/apisub.php?act=gglist").then(function (data) {
					//         layer.close(load);
					//         if (data.data.code == 1) {
					//           this.row1 = data.data;
					//         } else {
					//           layer.alert(data.data.msg, { icon: 2 });
					//         }
					//       });
					//     }
					//   },



					gglist: function () {
						this.$http.post("/apisub.php?act=gglist")
							.then(function (data) {
								if (data.data.code == 1) {
									this.row1 = data.data
									layer.open({
										type: 1,
										title: '本台公告',
										content: '<div style="padding: 20px; line-height: 22px; #fff; font-weight: 300;">' + data.data.shoot + '</div>',
										btn: '关闭',
										btnAlign: 'c',
										shade: 0,
									});
								}
								else {
									layer.alert(data.data.msg, { icon: 2 });
								}
							});
					},

					ggxg: function () {
						layer.open({
							type: 2,
							title: '公告修改',
							shadeClose: true,
							shade: 0.8,
							area: ['380px', '70%'],
							content: 'usernotice' //iframe的url
						});
					},


					ghapi: function () {
						layer.confirm(
							"更换key会扣除1余额，更换之后，之前的密钥就不可用了",
							{
								title: "温馨提示",
								icon: 1,
								btn: ["确定", "取消"] //按钮
							},
							function () {
								var load = layer.load();
								axios.get("/apisub.php?act=ghapi&type=1").then(function (data) {
									layer.close(load);
									if (data.data.code == 1) {
										layer.alert(
											data.data.msg,
											{ icon: 1, title: "温馨提示" },
											function () {
												setTimeout(function () {
													window.location.href = "";
												});
											}
										);
									} else {
										layer.msg(data.data.msg, { icon: 2 });
									}
								});
							}
						);
					},




					qd: function () {
						layer.confirm(
							"今日是否签到？",
							{
								title: "签到",
								icon: 1,
								btn: ["确定"] //按钮
							},
							function () {
								var load = layer.load();
								axios.get("/apisub.php?act=mrqd&type=1").then(function (data) {
									layer.close(load);
									if (data.data.code == 1) {
										layer.alert(
											data.data.msg,
											{ icon: 1, title: "签到提示" },
											function () {
												setTimeout(function () {
													window.location.href = "";
												});
											}
										);
									} else {
										layer.msg(data.data.msg, { icon: 2 });
									}
								});
							}
						);
					},

					szyqprice: function () {
						layer.prompt(
							{ title: "设置下级默认费率，首次自动生成邀请码", formType: 3 },
							function (yqprice, index) {
								layer.close(index);
								var load = layer.load();
								$.post("/apisub.php?act=yqprice", { yqprice }, function (data) {
									layer.close(load);
									if (data.code == 1) {
										vm.userinfo();
										layer.alert(data.msg, { icon: 1 });
									} else {
										layer.msg(data.msg, { icon: 2 });
									}
								});
							}
						);
					},
					level: function () {
						layer.alert(`0.20 => 化神期<br/>0.30 => 元婴期<br/>0.40 => 结丹期<br/>0.50 => 筑基期<br/>0.60 => 练气期<br/>`)
					},
					connect_qq: function () {
						var ii = layer.load(0, { shade: [0.1, "#fff"] });
						$.ajax({
							type: "POST",
							url: "../apisub.php?act=connect",
							data: {},
							dataType: "json",
							success: function (data) {
								layer.close(ii);
								if (data.code == 0) {
									window.location.href = data.url;
								} else {
									layer.alert(data.msg, { icon: 7 });
								}
							}
						});
					},
					szgg: function () {
						layer.prompt(
							{ title: "设置代理公告，您的代理可看到", formType: 2 },
							function (notice, index) {
								layer.close(index);
								var load = layer.load();
								$.post("/apisub.php?act=user_notice", { notice }, function (data) {
									layer.close(load);
									if (data.code == 1) {
										vm.userinfo();
										layer.msg(data.msg, { icon: 1 });
									} else {
										layer.msg(data.msg, { icon: 2 });
									}
								});
							}
						);
					},
					sjgg: function () {
						if (this.row.sjnotice != '') {
							layer.alert(this.row.sjnotice)
						} else {
							layer.msg('你的上级屁都没给你留')
						}
					}
				},


				mounted() {
					this.gglist();
					this.userinfo();

				}
			});
		</script>











</body>