			var vm = new Vue({
							el: "#add",
							data: {
								row: [],
								check_row: [],
								userinfo: '',
								cid: '',
								id: '',
								miaoshua: '',
								class1: '',
								class3: '',
								duijieid: '',
								show: false,
								show1: false,
								content: '',

								showImages: true,
								showCourseId: true,
								activems: false,
								useVfidder: true,
								money: 0, // 初始化余额为0
								freeadd: 0,
								selectedRegion: '随机',
							},
							methods: {
								get: function () {
									if (this.cid == '' || this.userinfo == '') {
										layer.msg("所有项目不能为空");
										return false;
									}
									if (this.useVfidder) {
										this.userinfo = vfidder(this.userinfo);
									}
									userinfo = this.userinfo.replace(/\r\n/g, "[br]").replace(/\n/g, "[br]").replace(/\r/g, "[br]");
									userinfo = userinfo.split('[br]');//分割
									this.row = [];
									this.check_row = [];
									for (var i = 0; i < userinfo.length; i++) {
										info = userinfo[i]
										var hash = getENC('<?php echo $addsalt; ?>');
										var loading = layer.load(5);
										this.$http.post("/apisub.php?act=get", { cid: this.cid, userinfo: info, hash }, { emulateJSON: true }).then(function (data) {
											layer.close(loading);
											this.show1 = true;
											this.row.push(data.body);
										});
									}
								},
								add: function () {
									if (this.cid == '') {
										layer.msg("请先查课");
										return false;
									}
									if (this.check_row.length < 1) {
										layer.msg("请先选择课程");
										return false;
									}
									//console.log(this.check_row);
									var loading = layer.load();
									score = $("#range_02").val();
									shichang = $("#range_01").val();
									this.$http.post("/apisub.php?act=add", {
										cid: this.cid,
										data: this.check_row,
										shichang: shichang,
										score: score,
										shu: this.shu,
										bei: this.bei,
										userinfo: this.userinfo,
										nochake: this.nochake,
										region: this.selectedRegion
									}, { emulateJSON: true }).then(function (data) {
										layer.close(loading);
										if (data.data.code == 1) {
											var submittedCoursesCount = this.check_row.length; // 获取提交的课程数量
											this.row = [];
											this.check_row = [];
											/*this.$message({type: 'success', showClose: true,message: data.data.msg});*/
											//     	    	layer.msg('提交成功',{icon:1,time:500}, function(){
											//                 setTimeout('window.location.reload()',500);
											//                 });
											// 		}else{
											// 			this.$message({type: 'error', showClose: true,message: data.data.msg});
											// 		}
											// 	});
											// },
											// 显示提交成功的消息，并包含提交的课程数量
											layer.msg('提交成功' + submittedCoursesCount + '门课程', { icon: 1, time: 2000 });
										} else {
											this.$message({ type: 'error', showClose: true, message: data.data.msg });

											layer.alert(data.data.msg, { icon: 2, title: "温馨提示" });
										}



									});
								},
								getUserInfo() {//调用主页的用户信息接口
									this.$http.get('/apisub.php?act=userinfo').then(response => {
										if (response.body.code === 1) {
											this.money = response.body.money; // 更新余额
											this.freeadd = response.body.freeadd; // 更新余额
										} else {
											console.error("获取用户信息失败:", response.body.msg);
										}
									}).catch(error => {
										console.error("请求用户信息接口失败", error);
									});
								}, selectAll: function () {
									if (this.cid == '') {
										layer.msg("请先查课");
										return false;
									}
									this.checked = !this.checked;
									if (this.check_row.length < 1) {
										for (i = 0; i < vm.row.length; i++) {
											console.log(i);
											userinfo = vm.row[i].userinfo
											userName = vm.row[i].userName
											rs = vm.row[i].data
											for (a = 0; a < rs.length; a++) {
												aa = rs[a]
												data = { userinfo, userName, data: aa }
												vm.check_row.push(data);
											}
										}
									} else {
										vm.check_row = []
									}
									console.log(vm.check_row);
								},

								checkResources: function (userinfo, userName, rs, id, name) {
									var course;
									if (id) {
										course = rs.find(function (course) {
											return course.id === id;
										});
									} else {
										course = rs.find(function (course) {
											return course.name === name;
										});
									}

									var data = {
										userinfo: userinfo,
										userName: userName,
										data: course
									};

									var index = this.check_row.findIndex(function (item) {
										// 当id存在时，只通过id来判断；当id不存在时，只通过name来判断
										if (id) {
											return item.data.id === id && item.userinfo === userinfo;
										} else {
											return item.data.name === name && item.userinfo === userinfo;
										}
									});

									// 如果已经存在于数组中，则移除该课程
									if (index !== -1) {
										this.check_row.splice(index, 1);
										layer.msg("已取消选择该课程");
										return; // 早期返回，因为我们已经处理了取消选中的情况
									}

									// 如果课程不存在于数组中，则添加该课程
									this.check_row.push(data);
									layer.msg("已成功添加课程");
								},
								fenlei: function (id) {
									var load = layer.load(5);
									this.$http.post("/apisub.php?act=getclassfl", { id: id }, { emulateJSON: true }).then(function (data) {
										layer.close(load);
										if (data.data.code == 1) {
											this.class1 = data.body.data;
										} else {
											layer.msg(data.data.msg, { icon: 2 });
										}
									});

								}, saveSettings() {
									localStorage.setItem('showImages', this.showImages);
									localStorage.setItem('showCourseId', this.showCourseId);
								},
								getclass: function () {
									var load = layer.load(5);
									this.$http.post("/apisub.php?act=getclass").then(function (data) {
										layer.close(load);
										if (data.data.code == 1) {
											this.class1 = data.body.data;
										} else {
											layer.msg(data.data.msg, { icon: 2 });
										}
									});

								},
								tips: function (message) {
									for (var i = 0; this.class1.length > i; i++) {
										if (this.class1[i].cid == message) {
											this.show = true;
											this.content = this.class1[i].content;
											this.duijieid = this.class1[i].cid;
											return false;
											if (this.class1[i].miaoshua == 1) {
												this.activems = true;
											} else {
												this.activems = false;
											}
											return false;

										}

									}

								},
								tips2: function () {
									layer.tips('开启秒刷将额外收0.05的费用', '#miaoshua');

								}
							},
							mounted() {
								this.getclass();
								this.getUserInfo();
								this.showImages = localStorage.getItem('showImages') !== 'false';
								this.showCourseId = localStorage.getItem('showCourseId') !== 'false'
							}


						});