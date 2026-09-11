<?php
include('../confing/common.php');

// 检查用户是否登录
if (!$islogin) {
    @header('Content-Type: text/html; charset=UTF-8');
    exit("<script language='javascript'>alert('请先登录！');window.location.href='index';</script>");
}

// 检查是否为管理员
if ($userrow['uid'] != 1) {
    @header('Content-Type: text/html; charset=UTF-8');
    exit("<script language='javascript'>alert('权限不足，只有管理员可以访问此页面！');window.location.href='index';</script>");
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>货源接口管理 - <?php echo $conf['sitename']; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <!-- Animate.css -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet" integrity="sha512-c42qTSw/wPZ3/5LBzD+Bw5f7bSF2oxou6wEb+I/lqeaKV5FDIfMvvRp772y4jcJLKuGUOpbJMdg/BTl50fJYAw==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <!-- Toastify CSS -->
    <link href="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.css" rel="stylesheet">
    <!-- Font Awesome CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <!-- 货源管理样式 -->
    <link href="assets/css/huoyuan.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid main-container">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-11">
                <div class="card main-card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            货源接口管理
                        </h4>
                        <p class="mb-0 mt-2 text-muted" style="font-size: 0.875rem;">
                            选择货源 → 查看课程 → 勾选需要的课程 → 可选择批量改价 → 点击批量上架保存到数据库
                        </p>
                    </div>
                    <div class="card-body">
                        <!-- 货源选择区域 -->
                        <div class="control-section">
                            <label for="huoyuanSelect" class="form-label">
                                选择货源
                            </label>
                            <div class="row align-items-center">
                                <div class="col-md-6 col-12">
                                    <select id="huoyuanSelect" class="form-control">
                                        <option value="">请选择要查看的货源...</option>
                                    </select>
                                </div>
                                <div class="col-md-3 col-6">
                                    <button id="refreshBtn" class="btn btn-primary w-100">
                                        刷新货源列表
                                    </button>
                                </div>
                                <div class="col-md-3 col-6">
                                    <button id="copyFenleiBtn" class="btn btn-success w-100" disabled>
                                        复制分类
                                    </button>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <a href="../jgjk.php" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-chart-line"></i> 价格监控管理
                                    </a>
                                    <small class="text-muted ms-2">自动监控货源价格变化并同步更新</small>
                                </div>
                            </div>
                        </div>

                        <!-- 搜索区域 -->
                        <div id="searchSection" class="control-section" style="display: none;">
                            <label for="searchInput" class="form-label">
                                搜索课程名称
                            </label>
                            <div class="row align-items-center">
                                <div class="col-md-5 col-12">
                                    <input type="text" id="searchInput" class="form-control" placeholder="输入课程名称、分类、状态进行模糊搜索...">
                                </div>
                                <div class="col-md-2 col-6">
                                    <select id="statusFilter" class="form-control">
                                        <option value="">全部状态</option>
                                        <option value="online">已上架</option>
                                        <option value="offline">未上架</option>
                                    </select>
                                </div>
                                <div class="col-md-2 col-6">
                                    <button id="clearSearchBtn" class="btn btn-warning w-100">
                                        清空
                                    </button>
                                </div>
                                <div class="col-md-3 col-12">
                                    <div class="search-stats">
                                        <small class="text-muted">
                                            显示 <span id="searchResultCount">0</span> / <span id="totalDataCount">0</span> 条
                                            | 已上架 <span id="onlineCount">0</span> 条
                                            | 未上架 <span id="offlineCount">0</span> 条
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <!-- 接口数据显示区域 -->
                        <div id="dataArea" class="data-section" style="display: none;">
                            <div class="data-header">
                                <div class="row align-items-center">
                                    <div class="col-md-6 col-12">
                                        <h5 class="mb-0">
                                            接口返回数据
                                        </h5>
                                    </div>
                                    <div class="col-md-6 col-12 text-right">
                                        <div class="batch-actions" style="display: none;">
                                            <button class="btn btn-sm btn-info" id="selectAllVisibleBtn">
                                                全选当前页
                                            </button>
                                            <button class="btn btn-sm btn-info" id="selectAllPagesBtn">
                                                全选所有页
                                            </button>
                                            <button class="btn btn-sm btn-warning" id="clearSelectionBtn">
                                                清空
                                            </button>
                                            <button class="btn btn-sm btn-primary" id="batchChangePriceBtn">
                                                批量改价
                                            </button>
                                            <button class="btn btn-sm btn-success" id="batchOnlineBtn">
                                                批量上架
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="table-container">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 5%;">
                                                <div class="custom-checkbox">
                                                    <input type="checkbox" id="selectAll" class="custom-checkbox-input">
                                                    <label for="selectAll" class="custom-checkbox-label"></label>
                                                </div>
                                            </th>
                                            <th style="width: 8%;">CID</th>
                                            <th style="width: 18%;">名称</th>
                                            <th style="width: 10%;">价格</th>
                                            <th style="width: 8%;">分类ID</th>
                                            <th style="width: 12%;">分类名称</th>
                                                                        <th style="width: 10%;">状态</th>
                            <th style="width: 29%;">描述</th>
                                        </tr>
                                    </thead>
                                    <tbody id="dataTableBody">
                                    </tbody>
                                </table>
                            </div>
                            <div id="dataStats" class="stats-info">
                                <small>
                                    共 <span id="totalCount">0</span> 条数据
                                </small>
                            </div>
                        </div>

                        <!-- 加载进度显示 -->
                        <div id="loadingProgress" class="loading-progress" style="display: none;">
                            <div class="text-center" style="padding: 3rem 2rem;">
                                <div class="loading-spinner-large" style="width: 50px; height: 50px; border: 4px solid rgba(0,123,255,.3); border-radius: 50%; border-top-color: #007bff; animation: spin 1s ease-in-out infinite; margin: 0 auto 1rem;"></div>
                                <h5 class="text-primary">正在加载数据...</h5>
                                <p class="text-muted">数据量较大，请耐心等待</p>
                                <div class="progress" style="height: 8px; margin-top: 1rem;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                                </div>
                                <small class="text-muted mt-2 d-block">正在从货源接口获取课程数据...</small>
                            </div>
                        </div>

                        <!-- 空状态 -->
                        <div id="emptyState" class="empty-state" style="display: none;">
                            <h5 class="text-muted">暂无数据</h5>
                            <p class="text-muted">请选择货源查看接口数据</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!-- 批量改价弹窗 -->
    <div class="modal fade" id="batchPriceModal" tabindex="-1" role="dialog" aria-labelledby="batchPriceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content copy-modal">
                <div class="modal-header copy-modal-header">
                    <h5 class="modal-title" id="batchPriceModalLabel">
                        批量改价
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body copy-modal-body">
                    <p class="copy-modal-desc">已选择 <span id="selectedItemsCount">0</span> 个课程，请选择改价方式：</p>
                    
                    <div class="price-change-options">
                        <div class="price-change-option" data-type="fixed">
                            <div class="copy-mode-icon">
                                ¥
                            </div>
                            <div class="copy-mode-content">
                                <h6>固定价格</h6>
                                <p>将所有选中课程设置为相同价格</p>
                                <div class="price-input-group" style="margin-top: 10px;">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">¥</span>
                                        </div>
                                        <input type="number" class="form-control" id="fixedPrice" placeholder="输入固定价格" min="0" step="0.01">
                                    </div>
                                </div>
                            </div>
                            <div class="copy-mode-check">
                                ✓
                            </div>
                        </div>
                        
                        <div class="price-change-option" data-type="multiply">
                            <div class="copy-mode-icon">
                                ×
                            </div>
                            <div class="copy-mode-content">
                                <h6>倍数调整</h6>
                                <p>按倍数调整现有价格（如1.2表示涨价20%）</p>
                                <div class="price-input-group" style="margin-top: 10px;">
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="multiplyFactor" placeholder="输入倍数" min="0.1" step="0.1" value="1.0">
                                        <div class="input-group-append">
                                            <span class="input-group-text">倍</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="copy-mode-check">
                                ✓
                            </div>
                        </div>
                        
                        <div class="price-change-option" data-type="add">
                            <div class="copy-mode-icon">
                                +
                            </div>
                            <div class="copy-mode-content">
                                <h6>增减金额</h6>
                                <p>在现有价格基础上增加或减少固定金额</p>
                                <div class="price-input-group" style="margin-top: 10px;">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">¥</span>
                                        </div>
                                        <input type="number" class="form-control" id="addAmount" placeholder="输入金额（负数为减少）" step="0.01">
                                    </div>
                                </div>
                            </div>
                            <div class="copy-mode-check">
                                ✓
                            </div>
                        </div>
                    </div>
                    
                    <div class="price-preview" id="pricePreview" style="display: none;">
                        <div class="alert alert-info">
                            <h6>价格预览</h6>
                            <div id="previewContent"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer copy-modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        取消
                    </button>
                    <button type="button" class="btn btn-warning" id="previewPriceBtn" disabled>
                        预览
                    </button>
                    <button type="button" class="btn btn-success" id="confirmPriceChangeBtn" disabled>
                        确认改价
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 批量上架分类选择弹窗 -->
    <div class="modal fade" id="batchOnlineModal" tabindex="-1" role="dialog" aria-labelledby="batchOnlineModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content copy-modal">
                <div class="modal-header copy-modal-header">
                    <h5 class="modal-title" id="batchOnlineModalLabel">
                        批量上架设置
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body copy-modal-body">
                    <div class="alert alert-info">
                        已选择 <strong id="onlineSelectedCount">0</strong> 个课程准备上架
                    </div>
                    
                    <h6>选择上架方式：</h6>
                    <div class="copy-mode-options">
                        <div class="copy-mode-option" data-type="default">
                            <div class="copy-mode-icon">
                                📁
                            </div>
                            <div class="copy-mode-content">
                                <h6>默认分类上架</h6>
                                <p>使用课程原有的分类信息进行上架</p>
                            </div>
                            <div class="copy-mode-check">
                                ✓
                            </div>
                        </div>
                        
                        <div class="copy-mode-option" data-type="existing">
                            <div class="copy-mode-icon">
                                📂
                            </div>
                            <div class="copy-mode-content">
                                <h6>选择已有分类</h6>
                                <p>将所有选中课程上架到指定的已有分类中</p>
                                <div class="category-select-group" style="margin-top: 10px; display: none;">
                                    <select class="form-control" id="existingCategorySelect">
                                        <option value="">正在加载分类...</option>
                                    </select>
                                </div>
                            </div>
                            <div class="copy-mode-check">
                                ✓
                            </div>
                        </div>
                        
                        <div class="copy-mode-option" data-type="custom">
                            <div class="copy-mode-icon">
                                📁+
                            </div>
                            <div class="copy-mode-content">
                                <h6>自定义新分类</h6>
                                <p>创建新分类并将所有选中课程上架到新分类中</p>
                                <div class="category-input-group" style="margin-top: 10px; display: none;">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">📁+</span>
                                        </div>
                                        <input type="text" class="form-control" id="customCategoryName" placeholder="输入新分类名称">
                                    </div>
                                </div>
                            </div>
                            <div class="copy-mode-check">
                                ✓
                            </div>
                        </div>
                    </div>
                    
                    <div id="onlinePreview" class="price-preview" style="display: none;">
                        <div class="alert alert-info">
                            <h6>上架预览</h6>
                            <div id="onlinePreviewContent"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer copy-modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        取消
                    </button>
                    <button type="button" class="btn btn-info" id="previewOnlineBtn" disabled>
                        预览
                    </button>
                    <button type="button" class="btn btn-success" id="confirmOnlineBtn" disabled>
                        确认上架
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 复制模式选择弹窗 -->
    <div class="modal fade" id="copyModeModal" tabindex="-1" role="dialog" aria-labelledby="copyModeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content copy-modal">
                <div class="modal-header copy-modal-header">
                    <h5 class="modal-title" id="copyModeModalLabel">
                        选择复制模式
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body copy-modal-body">
                    <p class="copy-modal-desc">请选择您要复制的内容：</p>
                    <div class="copy-mode-options">
                        <div class="copy-mode-option" data-mode="fenlei_only">
                            <div class="copy-mode-icon">
                                📁
                            </div>
                            <div class="copy-mode-content">
                                <h6>仅复制分类名</h6>
                                <p>只将分类名称保存到分类表中</p>
                            </div>
                            <div class="copy-mode-check">
                                ✓
                            </div>
                        </div>
                        <div class="copy-mode-option" data-mode="fenlei_and_class">
                            <div class="copy-mode-icon">
                                💾
                            </div>
                            <div class="copy-mode-content">
                                <h6>复制分类和课程数据</h6>
                                <p>将分类名称和分类下的所有课程数据都保存到数据库中</p>
                            </div>
                            <div class="copy-mode-check">
                                ✓
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer copy-modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        取消
                    </button>
                    <button type="button" class="btn btn-success" id="confirmCopyBtn" disabled>
                        确认复制
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    <!-- Toastify JS -->
    <script src="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.js"></script>
    <!-- 货源管理脚本 -->
    <script src="assets/js/huoyuan.js"></script>
</body>
</html> 