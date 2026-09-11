<?php
$mod = 'blank';
$title = '下架专区';
require_once('head.php');
?>

<div class="layui-container" style="padding: 20px;" id="xj">
    <div class="layui-row" style="margin-bottom: 15px;">
        <div class="layui-card">
            <div class="layui-card-header" style="background-color: #F2F2F2; font-weight: bold;">下架专区【暂停对接】</div>
            <div class="layui-card-body">
                <style>
                    table.layui-table th,
                    table.layui-table td {
                        text-align: center;
                    }
                    .layui-row .layui-col-md12 {
                        display: flex;
                        justify-content: center;
                    }
                </style>
                <table class="layui-table" style="table-layout: fixed; width: 100%;">
                    <thead>
                        <tr>
                            <th>项目名称</th>
                            <th>课程 ID</th>
                            <th>分类 ID</th>
                            <th>分类名称</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in classList" :key="item.cid">
                            <td>{{ item.course_name }}</td>
                            <td>{{ item.cid }}</td>
                            <td>{{ item.category_id }}</td>
                            <td>{{ item.category_name }}</td>
                        </tr>
                        <tr v-if="classList.length === 0">
                            <td colspan="4">暂无下架课程。</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="layui-row">
        <div class="layui-col-md12">
            <div class="layui-box layui-laypage layui-laypage-default">
                <a href="javascript:;" @click="changePage(1)" :style="pagination.current_page === 1 ? 'pointer-events: none; color: #ccc;' : ''">&lt;&lt;</a>
                <a href="javascript:;" @click="changePage(pagination.current_page - 1)" :style="pagination.current_page === 1 ? 'pointer-events: none; color: #ccc;' : ''">&lt;</a>
                <template v-for="page in pagination.total_pages">
                    <span v-if="page === pagination.current_page" class="layui-laypage-curr">{{ page }}</span>
                    <a v-else href="javascript:;" @click="changePage(page)">{{ page }}</a>
                </template>
                <a href="javascript:;" @click="changePage(pagination.current_page + 1)" :style="pagination.current_page === pagination.total_pages ? 'pointer-events: none; color: #ccc;' : ''">&gt;</a>
                <a href="javascript:;" @click="changePage(pagination.total_pages)" :style="pagination.current_page === pagination.total_pages ? 'pointer-events: none; color: #ccc;' : ''">&gt;&gt;</a>
            </div>
        </div>
    </div>
</div>

<?php require_once("footer.php"); ?>

<script src="https://cdn.staticfile.org/vue/2.6.11/vue.min.js"></script>
<script src="https://cdn.staticfile.org/vue-resource/1.5.1/vue-resource.min.js"></script>
<script>
    // 禁止开发者工具代码保持不变...
    
    new Vue({
        el: '#xj',
        data: {
            classList: [],
            pagination: {
                current_page: 1,
                total_pages: 1,
                total_records: 0
            },
            loading: false
        },
        created() {
            this.fetchData();
        },
        methods: {
            fetchData() {
                this.loading = true;
                this.$http.post('/apisub.php?act=classrank', {
                    page: this.pagination.current_page
                }).then(response => {
                    if (response.body.code === 1) {
                        this.classList = response.body.data;
                        this.pagination = {
                            current_page: response.body.pagination.current_page,
                            total_pages: response.body.pagination.total_pages,
                            total_records: response.body.pagination.total_records
                        };
                    } else {
                        layer.msg('获取数据失败', {icon: 2});
                    }
                    this.loading = false;
                }, error => {
                    layer.msg('网络请求失败', {icon: 2});
                    this.loading = false;
                });
            },
            changePage(page) {
                if (page < 1 || page > this.pagination.total_pages || page === this.pagination.current_page) {
                    return;
                }
                this.pagination.current_page = page;
                this.fetchData();
            }
        }
    });
</script>