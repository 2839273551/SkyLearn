<?php
$title = '卡密管理';
require_once('head.php');
if ($userrow['uid'] != 1) {
	exit("<script language='javascript'>window.location.href='login.php';</script>");
}
$uid = $_GET['uid'];
?>
<div class="app-content-body">
	<div class="wrapper-md control" id="card">
		<div class="card">
			<div class="card-body">
				<div class="d-flex align-items-center mb-4 gap-3">
					<h4 class="mt-2">卡密列表</h4>
					<div class="ms-auto">
						<button class="btn btn-primary mt-2 px-4 mt-lg-0" data-toggle="modal"
							data-target="#modal-add">添加卡密</button>
						<button class="btn btn-primary mt-2 px-4 mt-lg-0" @click="daochu">导出选中</button>
						<!--<button class="btn btn-primary mt-2 px-4 mt-lg-0" @click="delSelected">选中删除</button>-->
					</div>
				</div>
				<div class="table-responsive">
					<table class="table mb-0">
						<thead>
							<tr>
								<th scope="col">选择</th> <!-- 添加选择列 -->
								<th scope="col">ID</th>
								<th scope="col">卡号</th>
								<th scope="col">卡密金额</th>
								<th scope="col">使用者ID</th>
								<th scope="col">添加时间</th>
								<th scope="col">使用时间</th>
								<th scope="col">批次id</th>
								<th scope="col">状态</th>
								<th scope="col">操作</th>

							</tr>
						</thead>
						<tbody>

							<tr v-for="res in row.data">
								<td>
									<input type="checkbox" v-model="selectedIds" :value="res.id"
										@change="toggleSelection(res.id)"> <!-- 添加复选框 -->
								</td>
								<td>{{res.id}}</td>
								<td>{{res.content}}</td>
								<td>{{res.money}}</td>
								<td>{{res.uid}}</td>
								<td>{{res.addtime}}</td>
								<td>{{res.usedtime}}</td>
								<td>{{res.batch_id}}</td>
								<td>
									<span class="btn btn-success" v-if="res.status==1">已使用</span>
									<span class="btn btn-danger" v-else-if="res.status==0">未使用</span>
								</td>
								<td>
									<a href="javascript:void(0);" class="btn btn-danger btn-action"
										@click="del(res.id)">删除</a>
								</td>

							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="modal fade primary" id="modal-add">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span>
						</button>
						<h4 class="modal-title">分类添加</h4>
					</div>

					<div class="modal-body">
						<form class="form-horizontal" id="form-add">
							<input type="hidden" name="action" value="add" />
							<div class="form-group">
								<label class="col-sm-3 control-label">添加卡密</label>
								<div class="col-sm-9">
									<input type="text" v-model="addm.num" class="form-control" placeholder="请输入卡密数量">
								</div>
							</div>
							<div class="form-group">
								<label class="col-sm-3 control-label">卡密金额</label>
								<div class="col-sm-9">
									<input type="text" v-model="addm.money" class="form-control" placeholder="请输入卡密金额">
								</div>
							</div>
							<!-- 在这里添加批次ID的输入框 -->
							<div class="form-group">
								<label class="col-sm-3 control-label">卡密批次ID</label>
								<div class="col-sm-9">
									<input type="text" v-model="addm.batch_id" class="form-control"
										placeholder="请输入批次ID">
								</div>
							</div>
						</form>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-danger" data-dismiss="modal">取消</button>
						<button type="button" class="btn btn-success" data-dismiss="modal" @click="add()">确定</button>
					</div>
				</div>
			</div>
		</div>




		<?php require_once("footer.php"); ?>
		<script src="js/bootstrap.bundle.min.js"></script>
		<script>
			const cardVm = new Vue({
				el: '#card',
				data: {
					selectedIds: [],
					row: {},
					addm: {
						num: '',
						money: '',
						batch_id: '', // 添加批次ID数据模型
					},
				},
				methods: {
					get: function () {
						this.$http.post('/km.php?act=kmlist').then(function (data) {
							if (data.data.code == 1) {
								this.row = data.body
								this.$nextTick(function () {
									$('.preloader').fadeOut('slow')
								})
							} else {
								iziToast.error({
									title: data.data.msg,
									position: 'topRight',
								})
							}
						})
					},

					add: function () {
						// 获取当前日期并格式化为MMDD格式
						function getCurrentDateFormatted() {
							const date = new Date();
							const month = ('0' + (date.getMonth() + 1)).slice(-2);
							const day = ('0' + date.getDate()).slice(-2);
							return month + day;
						}

						// 生成复杂卡密
						function generateComplexKey() {
							const datePrefix = getCurrentDateFormatted();
							const totalLength = 18; // 卡密的总长度，包括日期前缀
							const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
							let key = datePrefix;
							for (let i = 0; i < totalLength - datePrefix.length; i++) {
								const randomIndex = Math.floor(Math.random() * characters.length);
								key += characters.charAt(randomIndex);
							}
							return key;
						}

						for (let i = 0; i < this.addm.num; i++) {
							var load = layer.load(2);
							let content = generateComplexKey();
							this.$http
								.post(
									'/km.php?act=addkm',
									{ content: content, money: this.addm.money, batch_id: this.addm.batch_id },
									{ emulateJSON: true }
								)
								.then((response) => {
									layer.close(load);
									if (response.data.code == 1) {
										$('#modal-add').modal('hide');
										cardVm.get(1);
										layer.msg(response.data.msg, { icon: 1, time: 2000, offset: 't' });
									} else {
										layer.msg(response.data.msg, { icon: 2, time: 2000, offset: 't' });
									}
								}, (error) => {
									// 处理错误的情况
									layer.close(load);
									layer.msg('发生错误，请稍后再试', { icon: 2, time: 2000, offset: 't' });
								});
						}
					},
					// daochu() {
					//         if (this.selectedIds.length === 0) {
					//             layer.msg("请先选择卡密", { icon: 2 });
					//             return;
					//         }
					//         let exportContent = "";
					//         this.selectedIds.forEach((id) => {
					//             let row = this.row.data.find((item) => item.id === id);
					//             if (row) {
					//                 // 只导出卡号
					//                 let line = `${row.content}\n`;
					//                 exportContent += line;
					//             }
					//         });
					//         const blob = new Blob([exportContent], { type: "text/plain;charset=utf-8" });
					//         const href = URL.createObjectURL(blob);
					//         const link = document.createElement("a");
					//         link.href = href;
					//         link.download = "卡号列表.txt"; // 文件名
					//         document.body.appendChild(link);
					//         link.click();
					//         document.body.removeChild(link);
					//         URL.revokeObjectURL(href);
					//     },
					daochu() {
						if (this.selectedIds.length === 0) {
							layer.msg("请先选择卡密", { icon: 2 });
							return;
						}
						let exportContent = "";
						this.selectedIds.forEach((id) => {
							let row = this.row.data.find((item) => item.id === id);
							if (row) {
								// 只收集卡号，并确保每个卡号后都有换行
								exportContent += `${row.content}\n`;
							}
						});
						// 使用 layui 弹窗显示卡号，并确保换行
						layer.msg(`<pre>${exportContent}</pre>`, { icon: 1, time: 0 });
					},
					delSelected() {
						if (this.selectedIds.length === 0) {
							layer.msg("请先选择卡密", { icon: 2 });
							return;
						}
						this.selectedIds.forEach((id) => {
							this.del(id);
						});
					},

					del(id) {
						layer.open({
							content: '您确定要删除该项吗？',
							btn: ['确认', '关闭'],
							yes: function (index, layero) {
								var load = layer.load(2);
								cardVm.$http
									.post('/km.php?act=deletekm', { id: id }, { emulateJSON: true })
									.then(function (data) {
										layer.close(load);
										if (data.data.code == 1) {
											// 删除成功后，重新获取卡密列表
											cardVm.get();
											layer.msg(data.data.msg, { icon: 1, time: 2000, offset: 't' });
											// 移除已删除的卡密ID
											cardVm.selectedIds = cardVm.selectedIds.filter(selectedId => selectedId !== id);
										} else {
											layer.msg(data.data.msg, { icon: 2, time: 2000, offset: 't' });
										}
									});
							}
						});
					},
				},
				created() {
					this.get()
				},
			})

		</script>