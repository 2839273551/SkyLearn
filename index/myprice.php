<?php
$mod='blank';
$title='价格列表';
include('../confing/common.php');
if($islogin!=1){exit("<script language='javascript'>window.location.href='login';</script>");  }
?>
<!DOCTYPE html>
<html>
<div class="app-content-body" style="background: #f4f6f9;">
    <div class="wrapper-md control">
        <div class="row">
            <div class="col-sm-12">
                <!-- 优化标题样式 -->
                <div class="panel-heading font-bold" style="background: #fff; color: #2c3e50; padding: 20px; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.035); border-radius: 15px; margin-bottom: 25px; display: flex; align-items: center;">
                    <i class="fa fa-list-alt mr-2" style="font-size: 24px; color: #1890ff; margin-right: 10px;"></i>
                    <span style="font-size: 20px; font-weight: 500;">项目列表</span>
                </div>
                
                <!-- 优化统计卡片 -->
                <div class="row" style="margin-bottom: 25px;">
                    <div class="col-sm-4">
                        <div class="stat-card" style="background: #fff; border-radius: 15px; overflow: hidden;">
                            <div class="stat-card-body" style="padding: 25px; position: relative;">
                                <div class="stat-card-icon" style="position: absolute; right: -20px; top: 50%; transform: translateY(-50%);">
                                    <i class="fa fa-shopping-bag" style="font-size: 80px; opacity: 0.1; color: #36D1DC;"></i>
                                </div>
                                <h3 style="margin: 0; font-size: 32px; font-weight: 600; color: #36D1DC;" id="totalProducts">-</h3>
                                <p style="margin: 10px 0 0 0; color: #718096; font-size: 15px;">总商品数量</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="stat-card" style="background: #fff; border-radius: 15px; overflow: hidden;">
                            <div class="stat-card-body" style="padding: 25px; position: relative;">
                                <div class="stat-card-icon" style="position: absolute; right: -20px; top: 50%; transform: translateY(-50%);">
                                    <i class="fa fa-th-large" style="font-size: 80px; opacity: 0.1; color: #FF416C;"></i>
                                </div>
                                <h3 style="margin: 0; font-size: 32px; font-weight: 600; color: #FF416C;" id="totalCategories">-</h3>
                                <p style="margin: 10px 0 0 0; color: #718096; font-size: 15px;">商品分类数</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="stat-card" style="background: #fff; border-radius: 15px; overflow: hidden;">
                            <div class="stat-card-body" style="padding: 25px; position: relative;">
                                <div class="stat-card-icon" style="position: absolute; right: -20px; top: 50%; transform: translateY(-50%);">
                                    <i class="fa fa-calculator" style="font-size: 80px; opacity: 0.1; color: #11998e;"></i>
                                </div>
                                <h3 style="margin: 0; font-size: 32px; font-weight: 600; color: #11998e;">0.20×</h3>
                                <p style="margin: 10px 0 0 0; color: #718096; font-size: 15px;">你的价格系数</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 优化搜索框 -->
                <div class="search-container" style="background: #fff; padding: 20px; border-radius: 15px; margin-bottom: 25px; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.035);">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="search-wrapper" style="position: relative;">
                                <i class="fa fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #a0aec0;"></i>
                                <input type="text" id="searchInput" class="modern-search" placeholder="输入商品名称或说明搜索...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 优化表格样式 -->
                <div class="table-container" style="background: #fff; border-radius: 15px; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.035);">
                    <div class="table-responsive">
                        <table class="table" id="productTable">
                            <thead>
                                <tr>
                                    <th style="padding: 20px; color: #4a5568; font-weight: 500; border-bottom: 2px solid #f7fafc;">对接ID</th>
                                    <th style="padding: 20px; color: #4a5568; font-weight: 500; border-bottom: 2px solid #f7fafc;">分类ID</th>
                                    <th style="padding: 20px; color: #4a5568; font-weight: 500; border-bottom: 2px solid #f7fafc;">商品名称</th>
                                    <th style="padding: 20px; color: #4a5568; font-weight: 500; border-bottom: 2px solid #f7fafc;">商品说明</th>
                                    <th style="padding: 20px; color: #4a5568; font-weight: 500; border-bottom: 2px solid #f7fafc;">价格</th>
                                </tr>
                            </thead>
                            <tbody id="productTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 保留原有的JS引入 -->

<script type="text/javascript" src="assets/LightYear/js/jquery.min.js"></script>
<script type="text/javascript" src="assets/LightYear/js/bootstrap.min.js"></script>
<script type="text/javascript" src="assets/LightYear/js/perfect-scrollbar.min.js"></script>
<script type="text/javascript" src="assets/LightYear/js/main.min.js"></script>
<script src="assets/js/aes.js"></script>
<script src="js/vue.min.js"></script>
<script src="js/vue-resource.min.js"></script>
<script src="js/axios.min.js"></script>

<style>
/* 现代化样式优化 */
body {
    background: #f4f6f9;
}

.stat-card {
    transition: transform 0.3s ease;
}

.modern-search {
    width: 100%;
    height: 50px;
    padding: 10px 20px 10px 45px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 15px;
    color: #4a5568;
    transition: all 0.3s ease;
}

.modern-search:focus {
    border-color: #1890ff;
    box-shadow: 0 0 0 3px rgba(24, 144, 255, 0.1);
    outline: none;
}

.table-row {
    transition: background-color 0.2s ease;
}

.table-row:hover {
    background-color: #f8fafc !important;
}

.highlight {
    background: rgba(24, 144, 255, 0.1);
    color: #1890ff;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 500;
}

/* 添加响应式设计 */
@media (max-width: 768px) {
    .stat-card {
        margin-bottom: 20px;
    }
    
    .modern-search {
        height: 45px;
        font-size: 14px;
    }
    
    .table-container {
        margin-top: 20px;
    }
}

/* 添加平滑滚动 */
html {
    scroll-behavior: smooth;
}

/* 添加加载动画 */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.stat-card, .search-container, .table-container {
    animation: fadeIn 0.5s ease-out forwards;
}

/* 只在必要时使用动画 */
@media (prefers-reduced-motion: reduce) {
    .stat-card, .table-row {
        transition: none;
    }
    
    .stat-card:hover {
        transform: none;
    }
}

/* 添加加载动画样式 */
.spinner-border {
    display: inline-block;
    width: 2rem;
    height: 2rem;
    vertical-align: text-bottom;
    border: 0.25em solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: spinner-border .75s linear infinite;
}

@keyframes spinner-border {
    to { transform: rotate(360deg); }
}
</style>

<script>
// 获取商品列表数据
async function fetchProductList() {
    try {
        const response = await fetch('/apisub.php?act=get_class_list', {
            method: 'POST',
        });
        const data = await response.json();
        
        if (data.code === 1) {
            // 更新商品列表
            const tbody = document.getElementById('productTableBody');
            tbody.innerHTML = data.data.list.map(item => `
                <tr class='table-row'>
                    <td style='padding: 20px; color: #718096;'>${item.cid}</td>
                    <td style='padding: 20px; color: #718096;'>${item.cateId}</td>
                    <td style='padding: 20px; color: #2d3748; font-weight: 500;'>${item.name}</td>
                    <td style='padding: 20px; color: #718096;'>${item.content}</td>
                    <td style='padding: 20px; color: #1890ff; font-weight: 600;'>${item.price}</td>
                </tr>
            `).join('');

            // 更新统计数据
            const uniqueCategories = new Set(data.data.list.map(item => item.cateId));
            document.getElementById('totalProducts').textContent = data.data.list.length;
            document.getElementById('totalCategories').textContent = uniqueCategories.size;
        } else {
            console.error('获取商品列表失败:', data.msg);
        }
    } catch (error) {
        console.error('请求失败:', error);
    }
}

// 页面加载完成后获取商品列表
document.addEventListener('DOMContentLoaded', fetchProductList);

// 优化搜索功能
function searchTable() {
    const input = document.getElementById("searchInput");
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll("#productTableBody tr");
    
    requestAnimationFrame(() => {
        rows.forEach(row => {
            const nameCell = row.cells[2];
            const contentCell = row.cells[3];
            const nameText = nameCell.textContent.toLowerCase();
            const contentText = contentCell.textContent.toLowerCase();
            
            row.style.display = 
                nameText.includes(filter) || contentText.includes(filter) 
                ? "" 
                : "none";
        });
    });
}

// 搜索框事件监听
const searchInput = document.getElementById("searchInput");
let searchTimeout;

searchInput.addEventListener("input", () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(searchTable, 200);
});
</script>
