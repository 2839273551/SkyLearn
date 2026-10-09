<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品价格变动记录系统</title>
    <link rel="stylesheet" href="https://unpkg.com/element-ui/lib/theme-chalk/index.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Helvetica Neue", Helvetica, "PingFang SC", "Hiragino Sans GB", Arial, sans-serif;
            background-color: #f5f7fa;
        }
        .app-container {
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #409EFF 0%, #337ecc 100%);
            color: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
        }
        .filter-container {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
        }
        .table-container {
            background: white;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
        }
        .price-up {
            color: #F56C6C;
        }
        .price-down {
            color: #67C23A;
        }
        .price-change {
            font-weight: bold;
        }
        .dashboard-card {
            background: white;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 12px 0 rgba(0, 0, 0, 0.1);
        }
        .statistic-item {
            text-align: center;
            padding: 10px;
        }
        .statistic-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div id="app">
        <div class="app-container">
            <div class="header">
                <h1>商品价格变动记录系统</h1>
                <p>实时监控商品价格变化，助力企业决策</p>
            </div>

            <div class="dashboard-card">
                <el-row :gutter="20">
                    <el-col :span="6">
                        <div class="statistic-item">
                            <div>总记录数</div>
                            <div class="statistic-value">{{ statistics.total }}</div>
                        </div>
                    </el-col>
                    <el-col :span="6">
                        <div class="statistic-item">
                            <div>今日变动</div>
                            <div class="statistic-value">{{ statistics.today }}</div>
                        </div>
                    </el-col>
                    <el-col :span="6">
                        <div class="statistic-item">
                            <div>价格上涨</div>
                            <div class="statistic-value price-up">{{ statistics.priceUp }}</div>
                        </div>
                    </el-col>
                    <el-col :span="6">
                        <div class="statistic-item">
                            <div>价格下降</div>
                            <div class="statistic-value price-down">{{ statistics.priceDown }}</div>
                        </div>
                    </el-col>
                </el-row>
            </div>

            <div class="filter-container">
                <el-form :inline="true" :model="filterForm" class="demo-form-inline">
                    <el-form-item label="商品ID">
                        <el-input v-model="filterForm.cid" placeholder="请输入商品ID"></el-input>
                    </el-form-item>
                    <el-form-item label="日期范围">
                        <el-date-picker
                            v-model="filterForm.dateRange"
                            type="daterange"
                            range-separator="至"
                            start-placeholder="开始日期"
                            end-placeholder="结束日期"
                            value-format="yyyy-MM-dd">
                        </el-date-picker>
                    </el-form-item>
                    <el-form-item>
                        <el-button type="primary" @click="handleFilter">查询</el-button>
                        <el-button @click="resetFilter">重置</el-button>
                        <el-button type="success" @click="exportData">导出数据</el-button>
                    </el-form-item>
                </el-form>
            </div>

            <div class="table-container">
                <el-table
                    :data="tableData"
                    border
                    style="width: 100%"
                    v-loading="loading"
                    @sort-change="handleSortChange">
                    <el-table-column
                        prop="cid"
                        label="商品ID"
                        width="120"
                        sortable>
                    </el-table-column>
                    <el-table-column
                        prop="kcname"
                        label="商品名称"
                        width="180">
                    </el-table-column>
                    <el-table-column
                        prop="oldprice"
                        label="原价格"
                        width="120"
                        align="right">
                        <template slot-scope="scope">
                            {{ scope.row.oldprice | currency }}
                        </template>
                    </el-table-column>
                    <el-table-column
                        prop="newprice"
                        label="新价格"
                        width="120"
                        align="right">
                        <template slot-scope="scope">
                            {{ scope.row.newprice | currency }}
                        </template>
                    </el-table-column>
                    <el-table-column
                        label="价格变动"
                        width="150"
                        align="right">
                        <template slot-scope="scope">
                            <span :class="getPriceChangeClass(scope.row)">
                                {{ calculatePriceChange(scope.row) }}
                            </span>
                        </template>
                    </el-table-column>
                    <el-table-column
                        prop="updatetime"
                        label="变动时间"
                        width="180"
                        sortable>
                        <template slot-scope="scope">
                            {{ scope.row.updatetime | formatDateTime }}
                        </template>
                    </el-table-column>
                    <el-table-column
                        label="操作"
                        width="120">
                        <template slot-scope="scope">
                            <el-button
                                size="mini"
                                @click="handleDetail(scope.row)">详情</el-button>
                        </template>
                    </el-table-column>
                </el-table>

                <div class="pagination-container">
                    <el-pagination
                        @size-change="handleSizeChange"
                        @current-change="handleCurrentChange"
                        :current-page="pagination.page"
                        :page-sizes="[10, 20, 50, 100]"
                        :page-size="pagination.pagesize"
                        layout="total, sizes, prev, pager, next, jumper"
                        :total="pagination.total">
                    </el-pagination>
                </div>
            </div>
        </div>

        <el-dialog title="价格变动详情" :visible.sync="dialogVisible" width="50%">
            <el-descriptions :column="2" border>
                <el-descriptions-item label="商品ID">{{ currentItem.cid }}</el-descriptions-item>
                <el-descriptions-item label="商品名称">{{ currentItem.kcname }}</el-descriptions-item>
                <el-descriptions-item label="原价格">{{ currentItem.oldprice | currency }}</el-descriptions-item>
                <el-descriptions-item label="新价格">{{ currentItem.newprice | currency }}</el-descriptions-item>
                <el-descriptions-item label="变动金额">
                    <span :class="getPriceChangeClass(currentItem)">
                        {{ calculatePriceChange(currentItem) }}
                    </span>
                </el-descriptions-item>
                <el-descriptions-item label="变动比例">
                    <span :class="getPriceChangeClass(currentItem)">
                        {{ calculatePriceChangePercent(currentItem) }}
                    </span>
                </el-descriptions-item>
                <el-descriptions-item label="变动时间">{{ currentItem.updatetime | formatDateTime }}</el-descriptions-item>
            </el-descriptions>
            <div slot="footer" class="dialog-footer">
                <el-button @click="dialogVisible = false">关闭</el-button>
            </div>
        </el-dialog>
    </div>

    <script src="https://unpkg.com/vue@2.6.14/dist/vue.js"></script>
    <script src="https://unpkg.com/element-ui/lib/index.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.3.2/dist/echarts.min.js"></script>
    <script>
        new Vue({
            el: '#app',
            data() {
                return {
                    loading: false,
                    tableData: [],
                    filterForm: {
                        cid: '',
                        dateRange: []
                    },
                    pagination: {
                        page: 1,
                        pagesize: 50,
                        total: 0
                    },
                    sort: {
                        prop: 'updatetime',
                        order: 'descending'
                    },
                    statistics: {
                        total: 0,
                        today: 0,
                        priceUp: 0,
                        priceDown: 0
                    },
                    dialogVisible: false,
                    currentItem: {}
                }
            },
            filters: {
                currency(value) {
                    if (!value && value !== 0) return '¥0.00'
                    return '¥' + parseFloat(value).toFixed(2)
                },
                formatDateTime(value) {
                    if (!value) return ''
                    return new Date(value).toLocaleString()
                }
            },
            created() {
                this.fetchData()
                this.fetchStatistics()
            },
            methods: {
                fetchData() {
                    this.loading = true
                    let url = '/apisub.php?act=pchangelist'
                    const params = {
                        page: this.pagination.page,
                        cid: this.filterForm.cid,
                        pagesize: this.pagination.pagesize
                    }
                    
                    // 处理日期范围
                    if (this.filterForm.dateRange && this.filterForm.dateRange.length === 2) {
                        params.start_time = this.filterForm.dateRange[0]
                        params.end_time = this.filterForm.dateRange[1]
                    }
                    
                    // 处理排序
                    if (this.sort.prop) {
                        params.sort = this.sort.prop
                        params.order = this.sort.order === 'ascending' ? 'asc' : 'desc'
                    }
                    
                    // 将参数添加到URL
                    const queryString = Object.keys(params)
                        .filter(key => params[key] !== undefined && params[key] !== '')
                        .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`)
                        .join('&')
                    
                    if (queryString) {
                        url += `&${queryString}`
                    }
                    
                    axios.get(url)
                        .then(response => {
                            const res = response.data
                            if (res.code === 1) {
                                this.tableData = res.data || []
                                this.pagination.total = res.pagination ? res.pagination.total : 0
                            } else {
                                this.$message.error(res.msg || '获取数据失败')
                            }
                        })
                        .catch(error => {
                            this.$message.error('请求失败: ' + error.message)
                        })
                        .finally(() => {
                            this.loading = false
                        })
                },
                fetchStatistics() {
                    axios.get('/apisub.php?act=pchangestats')
                        .then(response => {
                            const res = response.data
                            if (res.code === 1) {
                                this.statistics = res.data || {}
                            }
                        })
                        .catch(error => {
                            console.error('获取统计信息失败:', error)
                            this.$message.error('获取统计信息失败')
                        })
                },
                handleFilter() {
                    this.pagination.page = 1
                    this.fetchData()
                },
                resetFilter() {
                    this.filterForm = {
                        cid: '',
                        dateRange: []
                    }
                    this.pagination.page = 1
                    this.fetchData()
                },
                handleSizeChange(val) {
                    this.pagination.pagesize = val
                    this.pagination.page = 1
                    this.fetchData()
                },
                handleCurrentChange(val) {
                    this.pagination.page = val
                    this.fetchData()
                },
                handleSortChange(column) {
                    this.sort.prop = column.prop
                    this.sort.order = column.order
                    this.fetchData()
                },
                handleDetail(row) {
                    this.currentItem = Object.assign({}, row)
                    this.dialogVisible = true
                },
                calculatePriceChange(row) {
                    if (!row || row.oldprice === undefined || row.newprice === undefined) return '+0.00'
                    const change = parseFloat(row.newprice) - parseFloat(row.oldprice)
                    return (change > 0 ? '+' : '') + change.toFixed(2)
                },
                calculatePriceChangePercent(row) {
                    if (!row || !row.oldprice || row.oldprice == 0) return '0.00%'
                    const percent = (parseFloat(row.newprice) - parseFloat(row.oldprice)) / parseFloat(row.oldprice) * 100
                    return (percent > 0 ? '+' : '') + percent.toFixed(2) + '%'
                },
                getPriceChangeClass(row) {
                    if (!row || row.oldprice === undefined || row.newprice === undefined) return {}
                    const change = parseFloat(row.newprice) - parseFloat(row.oldprice)
                    return {
                        'price-change': true,
                        'price-up': change > 0,
                        'price-down': change < 0
                    }
                },
                exportData() {
                    this.$message({
                        message: '正在准备导出数据...',
                        type: 'success',
                        duration: 2000
                    })
                }
            }
        })
    </script>
</body>
</html>