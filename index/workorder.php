<?php
$title = '工单系统';
require_once('head.php');
?>
<link rel="stylesheet" href="assets/css/element.css">
<style>
    .liclass {
        font-size: 14px;
        text-indent: 2em;
        margin: 5px;
    }
    .null {
        font-size: 18px;
        text-align: center;
    }
    .short-title {
        max-width: 200px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .history-dialog {
        box-shadow: none;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        max-width: 600px;
        margin: 0 auto;
    }
    .history-content {
        padding: 15px;
        max-height: 400px;
        overflow-y: auto;
    }
    .search-box {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }
    .search-box > div {
        margin: 5px 0;
    }
</style>
<div class="app-content-body ">
    <div class="wrapper-md control" id="gdlist">
        <div class="col-sm-12">
            <el-card class="box-card">
                <div slot="header" class="clearfix">
                    <div class="panel-heading font-bold" style="border-top-left-radius: 8px; border-top-right-radius: 8px;background-color:#fff;">
                        <el-button type="info" size="small" @click="showAddTicketForm">新增工单</el-button> 
                        <el-dialog title="新增工单" :visible.sync="addTicketFormVisible" width="90%">
                            <el-form :model="newTicketForm">
                                <el-form-item label="请输入你遇到的"其它"问题（非已有订单的问题）">
                                    <el-input type="textarea" v-model="newTicketForm.content"></el-input>
                                </el-form-item>
                            </el-form>
                            <div slot="footer" class="dialog-footer">
                                <el-button @click="addTicketFormVisible = false">取 消</el-button>
                                <el-button type="primary" @click="submitNewTicket">确 定</el-button>
                            </div>
                        </el-dialog>
                    </div>
                </div>
                <div class="text item">
                    <div class="search-box">
                        <div>
                            <el-input v-model="pushToken" placeholder="推送Token,留空关闭" style="width: 160px;"></el-input>
                            <el-button type="danger" size="small" @click="testPushToken">测试</el-button>
                            <el-button type="success" size="small" @click="savePushToken">保存</el-button>
                        </div>
                        <div>
                            <el-select v-model="statusFilter" placeholder="筛选状态" style="width: 120px;">
                                <el-option label="全部状态" value=""></el-option>
                                <el-option label="待回复" value="待回复"></el-option>
                                <el-option label="已回复" value="已回复"></el-option>
                                <el-option label="已完成" value="已完成"></el-option>
                            </el-select>
                            <el-input v-model="searchQuery" placeholder="搜索订单ID/账号/密码/商品名" style="width: 250px;"></el-input>
                            <el-button type="primary" @click="get">搜索</el-button>
                        </div>
                    </div>
                    <div class="table-responsive" lay-size="sm" v-if="show == false">
                        <el-divider><span style="color:red">工单列表</span></el-divider>
                        <el-table ref="multipleTable" :data="order" size="small" header-cell-style="text-align:center;font-weight:1500" cell-style="text-align:center" empty-text=暂无反馈内容 highlight-current-row border>
                            <?php if ($userrow['uid'] == 1) { ?>
                                <el-table-column property="uid" label="提交者" width="100" sortable></el-table-column>
                            <?php } ?>
                            <el-table-column property="status" label="工单状态" width="100">
                                <template slot-scope="scope">
                                    <el-tag size="small" v-if="scope.row.state == '待处理'" effect="plain">{{ scope.row.state }}</el-tag>
                                    <el-tag type="success" size="small" v-else-if="scope.row.state == '已完成'" effect="plain">{{ scope.row.state }}</el-tag>
                                    <el-tag type="danger" size="small" v-else-if="scope.row.state == '已回复'" effect="plain">{{ scope.row.state }}</el-tag>
                                    <el-tag type="warning" size="small" v-else="" effect="plain">{{ scope.row.state }}</el-tag>
                                </template>
                            </el-table-column>
                            <?php if ($userrow['uid'] == 1) { ?>
                            <el-table-column label="操作" width="120">
                                <template slot-scope="scope">
                                    <el-dropdown trigger="click" @command="commandvalue">
                                        <el-button type="primary" size="mini" plain>
                                            操作<i class="el-icon-arrow-down el-icon--right"></i>
                                        </el-button>
                                        <el-dropdown-menu slot="dropdown">
                                            <el-dropdown-item :command="{ gid: scope.row.gid, type: 'hf' }">回复</el-dropdown-item>
                                            <el-dropdown-item :command="{ gid: scope.row.gid, type: 'bh' }">完成</el-dropdown-item>
                                        </el-dropdown-menu>
                                    </el-dropdown>
                                </template>
                            </el-table-column>
                            <?php } ?>
                            <el-table-column label="历史对话" width="110">
                                <template slot-scope="scope">
                                    <el-button type="primary" size="mini" @click="showHistoryDialog(scope.row)" plain>查看对话</el-button>
                                    <el-dialog :visible.sync="historyDialogVisible" width="80%" :before-close="handleClose" custom-class="history-dialog">
                                        <el-skeleton :rows="6" :loading="loading" animated>
                                            <div class="history-content" v-if="!loading">
                                                <div v-if="selectedTicket.title">
                                                    <h4>主题</h4>
                                                    <pre style="text-align: left;">{{ selectedTicket.title }}</pre>
                                                </div>
                                                <div v-if="selectedTicket.content">
                                                    <h4>聊天记录</h4>
                                                    <pre style="text-align: left;">{{ selectedTicket.content }}</pre>
                                                </div>
                                            </div>
                                        </el-skeleton>
                                        <span slot="footer" class="dialog-footer">
                                            <el-button @click="historyDialogVisible = false">关闭</el-button>
                                        </span>
                                    </el-dialog>
                                </template>
                            </el-table-column>
                            <el-table-column label="追加提问" width="110">
                                <template slot-scope="scope">
                                    <el-popconfirm placement="top-start" confirm-button-text='确定' cancel-button-text='取消' cancel-button-type="danger" icon="el-icon-info" icon-color="red" title="是否要追加提问？" @confirm="toanswer(scope.row.gid)">
                                        <el-button type="success" size="mini" slot="reference" plain>追加提问</el-button>
                                    </el-popconfirm>
                                </template>
                            </el-table-column>
                            <el-table-column property="region" label="订单ID" width="80" show-overflow-tooltip></el-table-column>
                            <el-table-column label="商品信息" width="280">
                                <template slot-scope="scope">
                                    <el-popover placement="top-start" trigger="click" width="400">
                                        <div>{{ scope.row.title }}</div>
                                        <span slot="reference" class="short-content">{{ scope.row.title | truncate(50) }}</span>
                                    </el-popover>
                                </template>
                            </el-table-column>
                            <el-table-column label="问题内容" width="280">
                                <template slot-scope="scope">
                                    <el-popover placement="top-start" trigger="click" width="400">
                                        <div>{{ scope.row.content }}</div>
                                        <span slot="reference" class="short-content">{{ scope.row.content | truncate(50) }}</span>
                                    </el-popover>
                                </template>
                            </el-table-column>
                            <el-table-column property="addtime" label="添加时间" width="100" sortable></el-table-column>
                            <el-table-column label="删除" width="80">
                                <template slot-scope="scope">
                                    <el-popconfirm placement="top-start" confirm-button-text='确定' cancel-button-text='取消' cancel-button-type="danger" icon="el-icon-info" icon-color="red" title="确认删除该工单？" @confirm="shan(scope.row.gid)">
                                        <el-button type="danger" size="mini" slot="reference" plain>删除</el-button>
                                    </el-popconfirm>
                                </template>
                            </el-table-column>
                        </el-table>
                    </div>
                    <div class="block" style="margin-top: 15px;">
                        <el-pagination
                            @size-change="handleSizeChange"
                            @current-change="handleCurrentChange"
                            :current-page.sync="currentPage"
                            :pager-count="5"
                            :page-size="pageSize"
                            layout="total, prev, pager, next, jumper"
                            :total="total">
                        </el-pagination>
                    </div>
                </div>
            </el-card>
        </div>
    </div>
</div>
<script type="text/javascript" src="assets/LightYear/js/jquery.min.js"></script>
<script type="text/javascript" src="assets/LightYear/js/bootstrap.min.js"></script>
<script type="text/javascript" src="assets/LightYear/js/perfect-scrollbar.min.js"></script>
<script type="text/javascript" src="assets/LightYear/js/main.min.js"></script>
<script src="assets/js/aes.js"></script>
<script src="assets/js/vue.min.js"></script>
<script src="assets/js/vue-resource.min.js"></script>
<script src="assets/js/element.js"></script>
<script>
    Vue.filter('truncate', function(value, length) {
        if (!value) return '';
        length = length || 20;
        if (value.length <= length) {
            return value;
        }
        return value.substring(0, length) + '...';
    });

    var vm = new Vue({
        el: "#gdlist",
        data: {
            row: null,
            show: false,
            ddsize: 'small',
            order: [],
            list: {
                region: '',
                title: '',
                content: '',
                answer: ''
            },
            form: {
                title: '',
                content: '',
                region: '',
            },
            rules: {
                region: [{
                    required: true,
                    message: '必填！',
                    trigger: 'blur'
                }],
                title: [{
                    required: true,
                    message: '必填！',
                    trigger: 'blur'
                }],
                content: [{
                    required: true,
                    message: '必填！',
                    trigger: 'blur'
                }]
            },
            searchQuery: '',
            statusFilter: '',
            isSearch: false,
            addTicketFormVisible: false,
            newTicketForm: {
                content: ''
            },
            pushToken: '',
            historyDialogVisible: false,
            selectedTicket: {},
            loading: true,
            currentPage: 1,
            pageSize: 10,
            total: 0,
        },
        methods: {
            showHistoryDialog(row) {
                this.selectedTicket = row;
                this.loading = true;
                this.historyDialogVisible = true;
                this.$nextTick(() => {
                    setTimeout(() => {
                        this.loading = false;
                    }, 500);
                });
            },
            handleClose(done) {
                this.selectedTicket = {};
                done();
            },
            showAddTicketForm() {
                this.addTicketFormVisible = true;
            },
            submitNewTicket() {
                var content = this.newTicketForm.content;
                if (!content) {
                    this.$message.error('问题内容不能为空');
                    return;
                }
                if (content.length > 100) {
                    this.$message.error('问题不能超过100个字');
                    return;
                }
                
                this.$http.get("/gd.php?act=addTicket&content=" + encodeURIComponent(content)).then(function(response) {
                    if (response.data.code == 1) {
                        this.addTicketFormVisible = false;
                        this.newTicketForm.content = '';
                        this.get();
                        this.$message({
                            message: '工单新增成功',
                            type: 'success'
                        });
                    } else {
                        this.$message.error(response.data.msg || '工单新增失败');
                    }
                }).catch(function(error) {
                    console.error(error);
                    this.$message.error('工单新增失败');
                });
            },
            savePushToken() {
                var token = this.pushToken;
                this.$http.get("/gd.php?act=savePushToken&token=" + encodeURIComponent(token)).then(function(response) {
                    if (response.data.code == 1) {
                        this.$message({
                            message: '推送Token保存成功',
                            type: 'success'
                        });
                    } else {
                        this.$message.error(response.data.msg || '推送Token保存失败');
                    }
                }).catch(function(error) {
                    console.error(error);
                    this.$message.error('推送Token保存失败');
                });
            },
            getPushToken() {
                this.$http.get("/gd.php?act=getPushToken").then(function(response) {
                    if (response.data.code == 1) {
                        this.pushToken = response.data.token;
                        if (!this.pushToken) {
                            this.$alert('系统检测到您未绑定推送token！<br> 为了您正常使用本功能，请先进行绑定！', '提示', {
                                confirmButtonText: '我知道了',
                                showClose: false,
                                dangerouslyUseHTMLString: true,
                                callback: action => {
                                    this.$alert('1、访问 <a href="http://cdnjson.com/image/S7OOpM" target="_blank" style="color: blue; text-decoration: underline;">PushShowDoc绑定图解</a> 查看 Token<br>2、将 Token 填入输入框并保存', '绑定教程', {
                                        confirmButtonText: '我明白了',
                                        showClose: false,
                                        dangerouslyUseHTMLString: true,
                                        callback: action => {}
                                    });
                                }
                            });
                        }
                    } else {
                        this.$message.error(response.data.msg || '获取推送Token失败');
                    }
                }).catch(function(error) {
                    console.error(error);
                    this.$message.error('获取推送Token失败');
                });
            },
            testPushToken() {
                if (!this.pushToken) {
                    this.$message.error('请先输入推送Token');
                    return;
                }

                this.$http.get("/gd.php?act=testPushToken&token=" + encodeURIComponent(this.pushToken)).then(function(response) {
                    if (response.data.code == 1) {
                        this.$message({
                            message: '测试消息已发送，请检查是否收到',
                            type: 'success'
                        });
                    } else {
                        this.$message.error(response.data.msg || '测试推送失败');
                    }
                }).catch(function(error) {
                    console.error(error);
                    this.$message.error('测试推送失败');
                });
            },
            commandvalue(command) {
                if (command.type == 'hf') {
                    this.$prompt('请输入回复内容', '工单回复', {
                        confirmButtonText: '确定',
                        cancelButtonText: '取消',
                        inputType: 'textarea',
                        inputValidator: (value) => {
                            if (!value) return '回复内容不能为空';
                            if (value.length > 500) return '回复内容不能超过500字';
                        }
                    }).then(({ value }) => {
                        this.$http.post("/gd.php?act=answer", {
                            gid: command.gid,
                            answer: value
                        }, {
                            emulateJSON: true
                        }).then(function(response) {
                            if (response.data.code == 1) {
                                this.$message({
                                    message: '回复成功',
                                    type: 'success'
                                });
                                this.get();
                            } else {
                                this.$message.error(response.data.msg || '回复失败');
                            }
                        }).catch(function(error) {
                            console.error(error);
                            this.$message.error('回复失败');
                        });
                    }).catch(() => {});
                }
                if (command.type == 'bh') {
                    this.$prompt('请输入完成信息', '完成工单', {
                        confirmButtonText: '确定',
                        cancelButtonText: '取消',
                        inputType: 'textarea',
                        inputValidator: (value) => {
                            if (!value) return '完成信息不能为空';
                            if (value.length > 500) return '信息不能超过500字';
                        }
                    }).then(({ value }) => {
                        this.$http.post("/gd.php?act=bohui", {
                            gid: command.gid,
                            answer: value
                        }, {
                            emulateJSON: true
                        }).then(function(response) {
                            if (response.data.code == 1) {
                                this.$message({
                                    message: '工单已完成',
                                    type: 'success'
                                });
                                this.get();
                            } else {
                                this.$message.error(response.data.msg || '操作失败');
                            }
                        }).catch(function(error) {
                            console.error(error);
                            this.$message.error('操作失败');
                        });
                    }).catch(() => {});
                }
            },
            handleSizeChange(val) {
                this.pageSize = val;
                this.get();
            },
            handleCurrentChange(val) {
                this.currentPage = val;
                this.get();
            },
            get: function() {
                var loading = this.$loading({
                    lock: true,
                    text: '加载中...',
                    spinner: 'el-icon-loading',
                    background: 'rgba(0, 0, 0, 0.7)'
                });
                
                var data = {
                    searchQuery: this.searchQuery,
                    statusFilter: this.statusFilter,
                    page: this.currentPage,
                    limit: this.pageSize
                };
                
                this.$http.post("/gd.php?act=gdlist", data, {
                    emulateJSON: true
                }).then(function(response) {
                    loading.close();
                    if (response.data.code == 1) {
                        this.order = response.body.data;
                        this.total = parseInt(response.body.total);
                        this.isSearch = false;
                    } else {
                        this.$message.error(response.data.msg || '获取工单列表失败');
                    }
                }).catch(function(error) {
                    loading.close();
                    console.error(error);
                    this.$message.error('获取工单列表失败');
                });
            },
            shan: function(gid) {
                this.$http.post("/gd.php?act=shan", {
                    gid: gid
                }, {
                    emulateJSON: true
                }).then(function(response) {
                    if (response.data.code == 1) {
                        this.$message({
                            message: '删除成功',
                            type: 'success'
                        });
                        this.get();
                    } else {
                        this.$message.error(response.data.msg || '删除失败');
                    }
                }).catch(function(error) {
                    console.error(error);
                    this.$message.error('删除失败');
                });
            },
            toanswer: function(gid) {
                this.$confirm('提交二次提问后，工单状态将更新为"待回复"，管理员会尽快处理', '提示', {
                    confirmButtonText: '继续',
                    cancelButtonText: '取消',
                    type: 'warning'
                }).then(() => {
                    this.$prompt('请描述您的问题', '追加提问', {
                        confirmButtonText: '提交',
                        cancelButtonText: '取消',
                        inputType: 'textarea',
                        inputValidator: (value) => {
                            if (!value) return '问题内容不能为空';
                            if (value.length > 100) return '问题不能超过100字';
                        }
                    }).then(({ value }) => {
                        this.$http.post("/gd.php?act=toanswer", {
                            gid: gid,
                            toanswer: value
                        }, {
                            emulateJSON: true
                        }).then(function(response) {
                            if (response.data.code == 1) {
                                this.$message({
                                    message: '提问已提交',
                                    type: 'success'
                                });
                                this.get();
                            } else {
                                this.$message.error(response.data.msg || '提交失败');
                            }
                        }).catch(function(error) {
                            console.error(error);
                            this.$message.error('提交失败');
                        });
                    }).catch(() => {});
                }).catch(() => {});
            }
        },
        mounted() {
            this.get();
            this.getPushToken();
        }
    });
</script>