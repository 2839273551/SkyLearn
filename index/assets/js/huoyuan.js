// 删除所有MDI图标
function removeMDIIcons() {
    // 删除所有包含mdi类的i标签
    $('i[class*="mdi-"]').remove();
}

$(document).ready(function() {
    // 页面加载完成后删除图标
    setTimeout(removeMDIIcons, 100);
    // 页面加载时获取货源列表
    loadHuoyuanList();

    // 货源选择变化事件
    $('#huoyuanSelect').change(function() {
        const hid = $(this).val();
        if (hid) {
            loadClassData(hid);
            $('#copyFenleiBtn').prop('disabled', false);
        } else {
            hideDataArea();
            $('#copyFenleiBtn').prop('disabled', true);
        }
    });

    // 刷新按钮点击事件
    $('#refreshBtn').click(function() {
        loadHuoyuanList();
    });

    // 复制分类按钮点击事件
    $('#copyFenleiBtn').click(function() {
        const hid = $('#huoyuanSelect').val();
        if (!hid) {
            showStatus('请先选择货源', 'error');
            return;
        }
        // 显示复制模式选择弹窗
        const copyModal = new bootstrap.Modal(document.getElementById('copyModeModal'));
        copyModal.show();
    });

    // 复制模式选择事件
    $('.copy-mode-option').click(function() {
        $('.copy-mode-option').removeClass('selected');
        $(this).addClass('selected');
        $('#confirmCopyBtn').prop('disabled', false);
    });

    // 确认复制按钮事件
    $('#confirmCopyBtn').click(function() {
        const selectedMode = $('.copy-mode-option.selected').data('mode');
        const hid = $('#huoyuanSelect').val();
        
        if (!selectedMode || !hid) {
            showStatus('请选择复制模式', 'error');
            return;
        }
        
        bootstrap.Modal.getInstance(document.getElementById('copyModeModal')).hide();
        copyFenleiToDatabase(hid, selectedMode);
    });

    // 全选/取消全选功能（当前页）
    $(document).on('change', '#selectAll', function() {
        const isChecked = $(this).prop('checked');
        $('.item-checkbox').prop('checked', isChecked);
        updateSelectAllState();
        updateSelectedCount();
    });

    // 单个复选框变化事件
    $(document).on('change', '.item-checkbox', function() {
        updateSelectAllState();
        updateSelectedCount();
    });

    // 批量操作按钮事件
    $('#selectAllVisibleBtn').click(function() {
        $('.item-checkbox').prop('checked', true);
        updateSelectAllState();
        updateSelectedCount();
    });

    $('#selectAllPagesBtn').click(function() {
        // 选择所有页的数据
        selectAllPagesData();
    });

    $('#clearSelectionBtn').click(function() {
        $('.item-checkbox').prop('checked', false);
        $('#selectAll').prop('checked', false).prop('indeterminate', false);
        // 清空全选所有页的标记
        window.selectedAllPages = false;
        window.selectedAllPagesData = [];
        updateSelectedCount();
    });

    // 批量改价按钮事件
    $('#batchChangePriceBtn').click(function() {
        const selectedItems = getSelectedItemsForBatch();
        if (selectedItems.length === 0) {
            showStatus('请先选择要改价的课程', 'error');
            return;
        }
        $('#selectedItemsCount').text(selectedItems.length);
        const priceModal = new bootstrap.Modal(document.getElementById('batchPriceModal'));
        priceModal.show();
    });

    // 批量上架按钮事件
    $('#batchOnlineBtn').click(function() {
        const selectedItems = getSelectedItemsForBatch();
        if (selectedItems.length === 0) {
            showStatus('请先选择要上架的课程', 'error');
            return;
        }
        
        // 显示上架设置弹窗
        $('#onlineSelectedCount').text(selectedItems.length);
        const onlineModal = new bootstrap.Modal(document.getElementById('batchOnlineModal'));
        onlineModal.show();
        
        // 加载已有分类列表
        loadExistingCategories();
    });

    // 搜索功能
    $('#searchInput').on('input', function() {
        filterTableRows();
    });

    // 状态筛选功能
    $('#statusFilter').on('change', function() {
        filterTableRows();
    });

    // 清空搜索
    $('#clearSearchBtn').click(function() {
        $('#searchInput').val('');
        $('#statusFilter').val('');
        filterTableRows();
    });

    // 改价方式选择事件
    $('.price-change-option').click(function() {
        $('.price-change-option').removeClass('selected');
        $(this).addClass('selected');
        updatePriceButtons();
    });

    // 价格输入变化事件
    $('#fixedPrice, #multiplyFactor, #addAmount').on('input', function() {
        updatePriceButtons();
    });

    // 预览价格按钮事件
    $('#previewPriceBtn').click(function() {
        previewPriceChanges();
    });

    // 确认改价按钮事件
    $('#confirmPriceChangeBtn').click(function() {
        confirmPriceChanges();
    });

    // 弹窗关闭时重置状态
    $('#batchPriceModal').on('hidden.bs.modal', function() {
        resetPriceModal();
    });

    // 上架方式选择事件
    $(document).on('click', '#batchOnlineModal .copy-mode-option', function() {
        $('#batchOnlineModal .copy-mode-option').removeClass('selected');
        $(this).addClass('selected');
        
        const selectedType = $(this).data('type');
        
        // 显示/隐藏相应的输入控件
        $('.category-select-group, .category-input-group').hide();
        if (selectedType === 'existing') {
            $('.category-select-group').show();
        } else if (selectedType === 'custom') {
            $('.category-input-group').show();
        }
        
        updateOnlineButtons();
    });

    // 分类选择和输入变化事件
    $(document).on('change', '#existingCategorySelect', function() {
        updateOnlineButtons();
    });

    $(document).on('input', '#customCategoryName', function() {
        updateOnlineButtons();
    });

    // 预览上架按钮事件
    $('#previewOnlineBtn').click(function() {
        previewOnlineChanges();
    });

    // 确认上架按钮事件
    $('#confirmOnlineBtn').click(function() {
        confirmOnlineChanges();
    });

    // 上架弹窗关闭时重置状态
    $('#batchOnlineModal').on('hidden.bs.modal', function() {
        resetOnlineModal();
    });
});

// 加载货源列表
function loadHuoyuanList() {
    showStatus('正在加载货源列表...', 'info');
    $('#refreshBtn').prop('disabled', true).html('<div class="loading-spinner"></div> 加载中...');
    
    $.ajax({
        url: '../apisub.php?act=gethuoyuan',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.code === 1) {
                updateHuoyuanSelect(response.data);
                showStatus('货源列表加载成功', 'success');
            } else {
                showStatus('加载失败: ' + response.msg, 'error');
            }
        },
        error: function(xhr, status, error) {
            showStatus('网络错误: ' + error, 'error');
        },
        complete: function() {
            $('#refreshBtn').prop('disabled', false).html('刷新货源列表');
        }
    });
}

// 更新货源下拉框
function updateHuoyuanSelect(data) {
    const select = $('#huoyuanSelect');
    select.empty();
    select.append('<option value="">请选择货源...</option>');
    
    data.forEach(function(item) {
        select.append(`<option value="${item.hid}">${item.name} (ID: ${item.hid})</option>`);
    });
}

// 加载接口数据
function loadClassData(hid) {
    showStatus('正在调用货源接口...', 'info');
    hideDataArea();
    showLoadingProgress();
    
    $.ajax({
        url: '../apisub.php?act=getclasss',
        type: 'POST',
        data: { hid: hid },
        dataType: 'json',
        timeout: 120000, // 增加超时时间到2分钟
        success: function(response) {
            hideLoadingProgress();
            if (response.code === 1) {
                displayClassData(response.data);
                showStatus('接口调用成功', 'success');
            } else {
                showStatus('接口调用失败: ' + response.msg, 'error');
                showEmptyState();
            }
        },
        error: function(xhr, status, error) {
            hideLoadingProgress();
            if (status === 'timeout') {
                showStatus('请求超时，数据量可能过大，请稍后重试', 'error');
            } else {
                showStatus('网络错误: ' + error, 'error');
            }
            showEmptyState();
        }
    });
}

// 全局变量存储所有数据
let allClassData = [];
let currentPage = 1;
let itemsPerPage = 50; // 每页显示50条数据

// 显示接口数据（分页版本）
function displayClassData(data) {
    if (!data || data.length === 0) {
        showEmptyState();
        return;
    }
    
    // 存储所有数据
    allClassData = data;
    window.originalClassData = [...data]; // 保存原始数据副本
    currentPage = 1;
    
    // 统计上架状态
    let onlineCount = 0;
    let offlineCount = 0;
    data.forEach(function(item) {
        if (item.is_online === 1) {
            onlineCount++;
        } else {
            offlineCount++;
        }
    });
    
    // 更新统计信息
    $('#totalDataCount').text(data.length);
    $('#onlineCount').text(onlineCount);
    $('#offlineCount').text(offlineCount);
    
    // 显示分页控件
    setupPagination();
    
    // 显示第一页数据
    displayCurrentPage();
    
    $('#dataArea').show();
    $('#emptyState').hide();
    $('#searchSection').show();
    
    // 重置复选框状态
    $('#selectAll').prop('checked', false).prop('indeterminate', false);
    
    // 清空搜索和筛选
    $('#searchInput').val('');
    $('#statusFilter').val('');
    
    showStatus(`数据加载完成，共 ${data.length} 条课程`, 'success');
}

// 显示当前页数据
function displayCurrentPage() {
    const tbody = $('#dataTableBody');
    tbody.empty();
    
    // 获取当前页的数据
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, allClassData.length);
    const currentPageData = allClassData.slice(startIndex, endIndex);
    
    currentPageData.forEach(function(item, index) {
        const globalIndex = startIndex + index;
        const price = parseFloat(item.price || 0).toFixed(2);
        const checkboxId = `item_${globalIndex}`;
        const isOnline = item.is_online === 1;
        const statusBadge = isOnline 
            ? '<span class="badge badge-success">已上架</span>'
            : '<span class="badge badge-secondary">未上架</span>';
        
        const row = `
            <tr data-cid="${item.cid}" data-name="${item.name || ''}" data-fenlei="${item.fenlei || ''}" data-fenleiname="${item.fenleiname || ''}" data-online="${isOnline ? 1 : 0}">
                <td>
                    <div class="custom-checkbox">
                        <input type="checkbox" id="${checkboxId}" class="custom-checkbox-input item-checkbox" data-cid="${item.cid}">
                        <label for="${checkboxId}" class="custom-checkbox-label"></label>
                    </div>
                </td>
                <td><span class="badge badge-primary">${item.cid}</span></td>
                <td><strong>${item.name || '-'}</strong></td>
                <td><span class="price-tag">¥${price}</span></td>
                <td><span class="badge badge-info">${item.fenlei || '-'}</span></td>
                <td><span class="badge badge-warning">${item.fenleiname || '-'}</span></td>
                <td>${statusBadge}</td>
                <td><small title="${item.content || '-'}">${item.content || '-'}</small></td>
            </tr>
        `;
        tbody.append(row);
    });
    
    // 更新页面信息
    updatePageInfo();
    
    // 重置复选框状态
    $('#selectAll').prop('checked', false).prop('indeterminate', false);
    updateSelectedCount();
}

// 设置分页控件
function setupPagination() {
    const totalPages = Math.ceil(allClassData.length / itemsPerPage);
    
    if (totalPages <= 1) {
        // 如果只有一页或没有数据，不显示分页
        $('.simple-pagination-container').remove();
        return;
    }
    
    // 计算显示的页码范围
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, currentPage + 2);
    
    // 确保显示5个页码（如果总页数足够）
    if (endPage - startPage < 4) {
        if (startPage === 1) {
            endPage = Math.min(totalPages, startPage + 4);
        } else if (endPage === totalPages) {
            startPage = Math.max(1, endPage - 4);
        }
    }
    
    let pageNumbersHtml = '';
    
    // 上一页按钮
    if (currentPage > 1) {
        pageNumbersHtml += `<li><a href="javascript:void(0)" class="page-link" data-page="${currentPage - 1}">上一页</a></li>`;
    }
    
    // 首页
    if (startPage > 1) {
        pageNumbersHtml += `<li><a href="javascript:void(0)" class="page-link" data-page="1">1</a></li>`;
        if (startPage > 2) {
            pageNumbersHtml += `<li><span class="page-ellipsis">...</span></li>`;
        }
    }
    
    // 页码按钮
    for (let i = startPage; i <= endPage; i++) {
        const isActive = i === currentPage;
        pageNumbersHtml += `
            <li><a href="javascript:void(0)" class="page-link ${isActive ? 'active' : ''}" data-page="${i}">${i}</a></li>
        `;
    }
    
    // 末页
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            pageNumbersHtml += `<li><span class="page-ellipsis">...</span></li>`;
        }
        pageNumbersHtml += `<li><a href="javascript:void(0)" class="page-link" data-page="${totalPages}">${totalPages}</a></li>`;
    }
    
    // 下一页按钮
    if (currentPage < totalPages) {
        pageNumbersHtml += `<li><a href="javascript:void(0)" class="page-link" data-page="${currentPage + 1}">下一页</a></li>`;
    }
    
    let paginationHtml = `
        <div class="simple-pagination-container">
            <div class="pagination-info">
                每页显示：
                <select class="page-size-select">
                    <option value="50" ${itemsPerPage === 50 ? 'selected' : ''}>50</option>
                    <option value="100" ${itemsPerPage === 100 ? 'selected' : ''}>100</option>
                    <option value="200" ${itemsPerPage === 200 ? 'selected' : ''}>200</option>
                </select>
            </div>
            <ul class="simple-pagination">
                ${pageNumbersHtml}
            </ul>
            <div class="pagination-summary">
                ${(currentPage - 1) * itemsPerPage + 1}/${Math.min(currentPage * itemsPerPage, allClassData.length)} 共${totalPages}页
            </div>
        </div>
    `;
    
    // 移除已存在的分页控件
    $('.simple-pagination-container').remove();
    
    // 添加分页控件到表格容器后面
    $('.table-container').after(paginationHtml);
    
    // 绑定分页事件
    bindPaginationEvents(totalPages);
}

// 绑定分页事件
function bindPaginationEvents(totalPages) {
    // 页码按钮点击事件
    $('.page-link').off('click').on('click', function() {
        const newPage = parseInt($(this).data('page'));
        if (newPage !== currentPage && newPage >= 1 && newPage <= totalPages) {
            currentPage = newPage;
            displayCurrentPage();
            setupPagination();
        }
    });
    
    // 每页显示数量变化事件
    $('.page-size-select').off('change').on('change', function() {
        const newSize = parseInt($(this).val());
        if (newSize !== itemsPerPage) {
            // 更新每页显示数量
            itemsPerPage = newSize;
            currentPage = 1; // 重置到第一页
            setupPagination();
            displayCurrentPage();
        }
    });
}

// 更新页面信息
function updatePageInfo() {
    const totalPages = Math.ceil(allClassData.length / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage + 1;
    const endIndex = Math.min(currentPage * itemsPerPage, allClassData.length);
    
    $('#totalCount').text(`显示第 ${startIndex}-${endIndex} 条，共 ${allClassData.length} 条数据`);
    $('#searchResultCount').text(allClassData.length);
}

// 全选所有页数据
function selectAllPagesData() {
    window.selectedAllPages = true;
    window.selectedAllPagesData = [...allClassData];
    
    // 选中当前页的所有复选框
    $('.item-checkbox').prop('checked', true);
    $('#selectAll').prop('checked', true).prop('indeterminate', false);
    
    // 更新显示
    const totalSelected = allClassData.length;
    const currentInfo = $('#totalCount').text();
    const baseInfo = currentInfo.split('，已选择')[0];
    $('#totalCount').html(`${baseInfo}，已选择 <span style="color: #667eea; font-weight: bold;">${totalSelected}</span> 条（所有页）`);
    $('.batch-actions').show();
    
    showStatus(`已选择所有 ${totalSelected} 条课程数据`, 'success');
}

// 获取选中的项目（支持全选所有页）
function getSelectedItemsForBatch() {
    if (window.selectedAllPages && window.selectedAllPagesData) {
        return window.selectedAllPagesData.map(function(item, index) {
            return {
                cid: item.cid,
                name: item.name,
                price: parseFloat(item.price || 0),
                fenlei: item.fenlei,
                fenleiname: item.fenleiname,
                content: item.content,
                row: null // 全选时没有对应的DOM行
            };
        });
    } else {
        // 只获取当前页选中的项目
        const selectedItems = [];
        $('.item-checkbox:checked').each(function() {
            const row = $(this).closest('tr');
            const priceText = row.find('.price-tag').text().replace('¥', '');
            selectedItems.push({
                cid: $(this).data('cid'),
                name: row.data('name'),
                price: parseFloat(priceText) || 0,
                fenlei: row.data('fenlei'),
                fenleiname: row.data('fenleiname'),
                content: row.find('td:nth-child(8) small').attr('title') || row.find('td:nth-child(8) small').text(),
                row: row
            });
        });
        return selectedItems;
    }
}

// 显示右上角提示 - 使用Toastify.js
function showToast(message, type = 'info', duration = 8000) {
    // 确定背景颜色
    let backgroundColor = '';
    switch(type) {
        case 'success':
            backgroundColor = 'linear-gradient(to right, #00b09b, #96c93d)';
            break;
        case 'error':
            backgroundColor = 'linear-gradient(to right, #ff5f6d, #ffc371)';
            break;
        case 'warning':
            backgroundColor = 'linear-gradient(to right, #f093fb, #f5576c)';
            break;
        default:
            backgroundColor = 'linear-gradient(to right, #4facfe, #00f2fe)';
    }
    
    Toastify({
        text: message,
        duration: duration,
        close: false,
        gravity: "top",
        position: "right",
        stopOnFocus: true,
        style: {
            background: backgroundColor,
            borderRadius: "4px",
            fontSize: "12px",
            padding: "6px 12px",
            minHeight: "28px",
            width: "auto",
            maxWidth: "none"
        }
    }).showToast();
}

// 兼容旧的showStatus函数
function showStatus(message, type) {
    showToast(message, type);
}

// 兼容旧的hideStatus函数
function hideStatus() {
    // 不需要做任何事情，因为提示会自动消失
}

// 显示加载进度
function showLoadingProgress() {
    $('#dataArea').hide();
    $('#emptyState').hide();
    $('#searchSection').hide();
    $('#loadingProgress').show();
}

// 隐藏加载进度
function hideLoadingProgress() {
    $('#loadingProgress').hide();
}

// 隐藏数据区域
function hideDataArea() {
    $('#dataArea').hide();
    $('#emptyState').hide();
    $('#searchSection').hide();
    $('#loadingProgress').hide();
}

// 显示空状态
function showEmptyState() {
    $('#dataArea').hide();
    $('#emptyState').show();
    $('#loadingProgress').hide();
}

// 搜索过滤表格行（支持分页）
function filterTableRows() {
    const searchTerm = $('#searchInput').val().toLowerCase().trim();
    const statusFilter = $('#statusFilter').val();
    
    if (searchTerm === '' && statusFilter === '') {
        // 如果没有搜索条件，恢复原始数据
        allClassData = window.originalClassData || allClassData;
        currentPage = 1;
        setupPagination();
        displayCurrentPage();
        return;
    }
    
    // 保存原始数据（如果还没保存的话）
    if (!window.originalClassData) {
        window.originalClassData = [...allClassData];
    }
    
    // 过滤数据
    const filteredData = window.originalClassData.filter(function(item) {
        const courseName = (item.name || '').toLowerCase();
        const fenleiName = (item.fenleiname || '').toLowerCase();
        const content = (item.content || '').toLowerCase();
        const isOnline = item.is_online === 1;
        
        // 文本搜索匹配
        const textMatch = searchTerm === '' || 
                         courseName.includes(searchTerm) || 
                         fenleiName.includes(searchTerm) || 
                         content.includes(searchTerm);
        
        // 状态筛选匹配
        const statusMatch = statusFilter === '' || 
                           (statusFilter === 'online' && isOnline) ||
                           (statusFilter === 'offline' && !isOnline);
        
        return textMatch && statusMatch;
    });
    
    // 更新当前显示的数据
    allClassData = filteredData;
    currentPage = 1;
    
    // 统计过滤后的数据
    let onlineCount = 0;
    let offlineCount = 0;
    filteredData.forEach(function(item) {
        if (item.is_online === 1) {
            onlineCount++;
        } else {
            offlineCount++;
        }
    });
    
    // 更新统计信息
    $('#searchResultCount').text(filteredData.length);
    $('#onlineCount').text(onlineCount);
    $('#offlineCount').text(offlineCount);
    
    // 重新设置分页和显示数据
    if (filteredData.length > 0) {
        setupPagination();
        displayCurrentPage();
    } else {
        // 没有搜索结果时显示空表格
        $('#dataTableBody').empty();
        $('.simple-pagination-container').remove();
        $('#totalCount').text('未找到匹配的课程');
        
        const filterDesc = searchTerm ? `包含 "${searchTerm}" 的` : '';
        const statusDesc = statusFilter === 'online' ? '已上架' : statusFilter === 'offline' ? '未上架' : '';
        showStatus(`未找到${filterDesc}${statusDesc}课程`, 'info');
    }
    
    // 重置复选框状态
    $('.item-checkbox').prop('checked', false);
    $('#selectAll').prop('checked', false).prop('indeterminate', false);
    updateSelectedCount();
}

// 更新全选状态
function updateSelectAllState() {
    const totalCheckboxes = $('.item-checkbox').length;
    const checkedCheckboxes = $('.item-checkbox:checked').length;
    const selectAllCheckbox = $('#selectAll');
    
    if (checkedCheckboxes === 0) {
        selectAllCheckbox.prop('checked', false);
        selectAllCheckbox.prop('indeterminate', false);
    } else if (checkedCheckboxes === totalCheckboxes) {
        selectAllCheckbox.prop('checked', true);
        selectAllCheckbox.prop('indeterminate', false);
    } else {
        selectAllCheckbox.prop('checked', false);
        selectAllCheckbox.prop('indeterminate', true);
    }
}

// 更新选中数量显示
function updateSelectedCount() {
    const checkedCount = $('.item-checkbox:checked').length;
    
    if (checkedCount > 0) {
        const currentInfo = $('#totalCount').text();
        const baseInfo = currentInfo.split('，已选择')[0]; // 获取基础信息部分
        $('#totalCount').html(`${baseInfo}，已选择 <span style="color: #667eea; font-weight: bold;">${checkedCount}</span> 条`);
        $('.batch-actions').show();
    } else {
        // 恢复原始显示
        updatePageInfo();
        $('.batch-actions').hide();
    }
}

// 获取选中的项目
function getSelectedItems() {
    const selectedItems = [];
    $('.item-checkbox:checked').each(function() {
        const row = $(this).closest('tr');
        selectedItems.push({
            cid: $(this).data('cid'),
            name: row.data('name'),
            fenlei: row.data('fenlei'),
            fenleiname: row.data('fenleiname')
        });
    });
    return selectedItems;
}

// 获取选中的项目（包含价格信息）
function getSelectedItemsWithPrice() {
    const selectedItems = [];
    $('.item-checkbox:checked').each(function() {
        const row = $(this).closest('tr');
        const priceText = row.find('.price-tag').text().replace('¥', '');
        selectedItems.push({
            cid: $(this).data('cid'),
            name: row.data('name'),
            price: parseFloat(priceText) || 0,
            row: row
        });
    });
    return selectedItems;
}

// 获取选中的项目（用于上架）
function getSelectedItemsForOnline() {
    const selectedItems = [];
    $('.item-checkbox:checked').each(function() {
        const row = $(this).closest('tr');
        // 获取当前显示的价格（如果改过价就是修改后的价格，否则是原始价格）
        const priceText = row.find('.price-tag').text().replace('¥', '');
        const currentPrice = parseFloat(priceText) || 0;
        
        selectedItems.push({
            cid: $(this).data('cid'),
            name: row.data('name'),
            price: currentPrice, // 使用当前显示的价格
            fenlei: row.data('fenlei'),
            fenleiname: row.data('fenleiname'),
            content: row.find('td:nth-child(8) small').attr('title') || row.find('td:nth-child(8) small').text(),
            row: row
        });
    });
    return selectedItems;
}

// 复制分类到数据库
function copyFenleiToDatabase(hid, copyMode) {
    const modeText = copyMode === 'fenlei_and_class' ? '分类和课程数据' : '分类';
    showStatus(`正在复制${modeText}到数据库...`, 'info');
    $('#copyFenleiBtn').prop('disabled', true).html('<div class="loading-spinner"></div> 复制中...');
    
    $.ajax({
        url: '../apisub.php?act=copyfenlei',
        type: 'POST',
        data: { 
            hid: hid,
            copy_mode: copyMode 
        },
        dataType: 'json',
        success: function(response) {
            if (response.code === 1) {
                showStatus(response.msg, 'success');
            } else {
                showStatus('复制失败: ' + response.msg, 'error');
            }
        },
        error: function(xhr, status, error) {
            let errorMsg = '网络错误: ' + error;
            if (xhr.responseText) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.msg) {
                        errorMsg = '错误: ' + response.msg;
                    }
                } catch (e) {
                    errorMsg = '服务器返回错误: ' + xhr.responseText.substring(0, 200);
                }
            }
            showStatus(errorMsg, 'error');
        },
        complete: function() {
            $('#copyFenleiBtn').prop('disabled', false).html('复制分类');
            // 重置弹窗状态
            $('.copy-mode-option').removeClass('selected');
            $('#confirmCopyBtn').prop('disabled', true);
        }
    });
}

// 更新改价按钮状态
function updatePriceButtons() {
    const selectedType = $('.price-change-option.selected').data('type');
    let isValid = false;
    
    if (selectedType === 'fixed') {
        const price = parseFloat($('#fixedPrice').val());
        isValid = !isNaN(price) && price >= 0;
    } else if (selectedType === 'multiply') {
        const factor = parseFloat($('#multiplyFactor').val());
        isValid = !isNaN(factor) && factor > 0;
    } else if (selectedType === 'add') {
        const amount = parseFloat($('#addAmount').val());
        isValid = !isNaN(amount);
    }
    
    $('#previewPriceBtn').prop('disabled', !isValid);
    $('#confirmPriceChangeBtn').prop('disabled', !isValid);
}

// 预览价格变化
function previewPriceChanges() {
    const selectedItems = getSelectedItemsForBatch();
    const selectedType = $('.price-change-option.selected').data('type');
    const previewContent = $('#previewContent');
    
    let previewHtml = '';
    
    // 限制预览显示的数量，避免页面过长
    const previewLimit = 10;
    const itemsToShow = selectedItems.slice(0, previewLimit);
    
    itemsToShow.forEach(function(item) {
        let newPrice = 0;
        
        if (selectedType === 'fixed') {
            newPrice = parseFloat($('#fixedPrice').val());
        } else if (selectedType === 'multiply') {
            const factor = parseFloat($('#multiplyFactor').val());
            newPrice = item.price * factor;
        } else if (selectedType === 'add') {
            const amount = parseFloat($('#addAmount').val());
            newPrice = item.price + amount;
        }
        
        newPrice = Math.max(0, newPrice); // 确保价格不为负数
        
        previewHtml += `
            <div class="preview-item">
                <div class="preview-name">${item.name}</div>
                <div class="preview-price">
                    <span class="old-price">¥${item.price.toFixed(2)}</span>
                    <span class="price-arrow">→</span>
                    <span class="new-price">¥${newPrice.toFixed(2)}</span>
                </div>
            </div>
        `;
    });
    
    if (selectedItems.length > previewLimit) {
        previewHtml += `
            <div class="preview-item">
                <div class="preview-name text-muted">... 还有 ${selectedItems.length - previewLimit} 个课程</div>
                <div class="preview-price">
                    <span class="text-muted">同样的价格变化规则</span>
                </div>
            </div>
        `;
    }
    
    previewContent.html(previewHtml);
    $('#pricePreview').show();
}

// 确认价格变化
function confirmPriceChanges() {
    const selectedItems = getSelectedItemsForBatch();
    const selectedType = $('.price-change-option.selected').data('type');
    
    let changeCount = 0;
    
    selectedItems.forEach(function(item) {
        let newPrice = 0;
        
        if (selectedType === 'fixed') {
            newPrice = parseFloat($('#fixedPrice').val());
        } else if (selectedType === 'multiply') {
            const factor = parseFloat($('#multiplyFactor').val());
            newPrice = item.price * factor;
        } else if (selectedType === 'add') {
            const amount = parseFloat($('#addAmount').val());
            newPrice = item.price + amount;
        }
        
        newPrice = Math.max(0, newPrice); // 确保价格不为负数
        
        // 如果是全选所有页，更新内存中的数据
        if (window.selectedAllPages) {
            // 在allClassData中找到对应项目并更新价格
            const dataItem = allClassData.find(dataItem => dataItem.cid === item.cid);
            if (dataItem) {
                dataItem.price = newPrice;
            }
            // 同时更新原始数据
            const originalItem = window.originalClassData.find(dataItem => dataItem.cid === item.cid);
            if (originalItem) {
                originalItem.price = newPrice;
            }
        } else if (item.row) {
            // 更新表格中的价格显示
            item.row.find('.price-tag').text(`¥${newPrice.toFixed(2)}`);
            // 标记该行已修改价格
            item.row.attr('data-price-modified', 'true');
        }
        
        changeCount++;
    });

    // 如果是全选所有页，重新显示当前页以反映价格变化
    if (window.selectedAllPages) {
        displayCurrentPage();
        // 重新选择所有页
        selectAllPagesData();
    }
    
    bootstrap.Modal.getInstance(document.getElementById('batchPriceModal')).hide();
    showStatus(`成功修改了 ${changeCount} 个课程的价格，上架时将使用修改后的价格`, 'success');
}

// 重置改价弹窗
function resetPriceModal() {
    $('.price-change-option').removeClass('selected');
    $('#fixedPrice, #multiplyFactor, #addAmount').val('');
    $('#multiplyFactor').val('1.0');
    $('#pricePreview').hide();
    $('#previewPriceBtn, #confirmPriceChangeBtn').prop('disabled', true);
}

// 加载已有分类列表
function loadExistingCategories() {
    $.ajax({
        url: '../apisub.php?act=getcategories',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            const select = $('#existingCategorySelect');
            select.empty();
            
            if (response.code === 1 && response.data && response.data.length > 0) {
                select.append('<option value="">请选择分类...</option>');
                response.data.forEach(function(category) {
                    select.append(`<option value="${category.id}">${category.name} (ID: ${category.id})</option>`);
                });
            } else {
                select.append('<option value="">暂无可用分类</option>');
            }
        },
        error: function() {
            $('#existingCategorySelect').html('<option value="">加载分类失败</option>');
        }
    });
}

// 更新上架按钮状态
function updateOnlineButtons() {
    const selectedType = $('#batchOnlineModal .copy-mode-option.selected').data('type');
    let isValid = false;
    
    if (selectedType === 'default') {
        isValid = true;
    } else if (selectedType === 'existing') {
        const categoryId = $('#existingCategorySelect').val();
        isValid = categoryId && categoryId !== '';
    } else if (selectedType === 'custom') {
        const categoryName = $('#customCategoryName').val().trim();
        isValid = categoryName.length > 0;
    }
    
    $('#previewOnlineBtn').prop('disabled', !isValid);
    $('#confirmOnlineBtn').prop('disabled', !isValid);
}

// 预览上架变化
function previewOnlineChanges() {
    const selectedItems = getSelectedItemsForBatch();
    const selectedType = $('#batchOnlineModal .copy-mode-option.selected').data('type');
    const previewContent = $('#onlinePreviewContent');
    
    let previewHtml = '';
    let categoryInfo = '';
    
    // 限制预览显示的数量
    const previewLimit = 10;
    const itemsToShow = selectedItems.slice(0, previewLimit);
    
    if (selectedType === 'default') {
        categoryInfo = '使用原有分类';
        itemsToShow.forEach(function(item) {
            previewHtml += `
                <div class="preview-item">
                    <div class="preview-name">${item.name}</div>
                    <div class="preview-price">
                        <span class="badge badge-info">${item.fenleiname || '未知分类'}</span>
                        <span class="price-arrow">→</span>
                        <span class="new-price">¥${item.price.toFixed(2)}</span>
                    </div>
                </div>
            `;
        });
    } else if (selectedType === 'existing') {
        const selectedOption = $('#existingCategorySelect option:selected');
        categoryInfo = `统一分类到: ${selectedOption.text()}`;
        itemsToShow.forEach(function(item) {
            previewHtml += `
                <div class="preview-item">
                    <div class="preview-name">${item.name}</div>
                    <div class="preview-price">
                        <span class="old-price">${item.fenleiname || '原分类'}</span>
                        <span class="price-arrow">→</span>
                        <span class="badge badge-success">${selectedOption.text()}</span>
                        <span class="new-price">¥${item.price.toFixed(2)}</span>
                    </div>
                </div>
            `;
        });
    } else if (selectedType === 'custom') {
        const customName = $('#customCategoryName').val().trim();
        categoryInfo = `创建新分类: ${customName}`;
        itemsToShow.forEach(function(item) {
            previewHtml += `
                <div class="preview-item">
                    <div class="preview-name">${item.name}</div>
                    <div class="preview-price">
                        <span class="old-price">${item.fenleiname || '原分类'}</span>
                        <span class="price-arrow">→</span>
                        <span class="badge badge-warning">${customName} (新)</span>
                        <span class="new-price">¥${item.price.toFixed(2)}</span>
                    </div>
                </div>
            `;
        });
    }
    
    if (selectedItems.length > previewLimit) {
        previewHtml += `
            <div class="preview-item">
                <div class="preview-name text-muted">... 还有 ${selectedItems.length - previewLimit} 个课程</div>
                <div class="preview-price">
                    <span class="text-muted">同样的上架规则</span>
                </div>
            </div>
        `;
    }
    
    previewContent.html(`
        <div style="margin-bottom: 1rem;">
            <strong>${categoryInfo}</strong>
        </div>
        ${previewHtml}
    `);
    $('#onlinePreview').show();
}

// 确认上架变化
function confirmOnlineChanges() {
    const selectedItems = getSelectedItemsForBatch();
    const selectedType = $('#batchOnlineModal .copy-mode-option.selected').data('type');
    
    let categoryData = {};
    
    if (selectedType === 'default') {
        categoryData.type = 'default';
    } else if (selectedType === 'existing') {
        categoryData.type = 'existing';
        categoryData.category_id = $('#existingCategorySelect').val();
        categoryData.category_name = $('#existingCategorySelect option:selected').text().split(' (ID:')[0];
    } else if (selectedType === 'custom') {
        categoryData.type = 'custom';
        categoryData.category_name = $('#customCategoryName').val().trim();
    }
    
    bootstrap.Modal.getInstance(document.getElementById('batchOnlineModal')).hide();
    batchOnlineCoursesWithCategory(selectedItems, categoryData);
}

// 重置上架弹窗
function resetOnlineModal() {
    $('#batchOnlineModal .copy-mode-option').removeClass('selected');
    $('#existingCategorySelect').val('');
    $('#customCategoryName').val('');
    $('.category-select-group, .category-input-group').hide();
    $('#onlinePreview').hide();
    $('#previewOnlineBtn, #confirmOnlineBtn').prop('disabled', true);
}

// 批量上架课程（带分类选择）
function batchOnlineCoursesWithCategory(selectedItems, categoryData, handleDuplicates = 'check') {
    const hid = $('#huoyuanSelect').val();
    if (!hid) {
        showStatus('请先选择货源', 'error');
        return;
    }

    showStatus(`正在上架 ${selectedItems.length} 个课程...`, 'info');
    $('#batchOnlineBtn').prop('disabled', true).html('<div class="loading-spinner"></div> 上架中...');
    
    // 清理数据，移除不能序列化的row对象
    const cleanedItems = selectedItems.map(function(item) {
        return {
            cid: item.cid || '',
            name: item.name || '',
            price: item.price || 0,
            fenlei: item.fenlei || '',
            fenleiname: item.fenleiname || '',
            content: item.content || ''
        };
    });
    
    $.ajax({
        url: '../apisub.php?act=batchonline',
        type: 'POST',
        data: { 
            hid: hid,
            courses: JSON.stringify(cleanedItems),
            handle_duplicates: handleDuplicates,
            category_data: JSON.stringify(categoryData)
        },
        dataType: 'json',
        success: function(response) {
            if (response.code === 1) {
                // 正常完成
                showStatus(response.msg, 'success');
                
                // 更新状态显示
                selectedItems.forEach(function(item) {
                    // 如果是全选所有页，更新内存中的数据
                    if (window.selectedAllPages) {
                        const dataItem = allClassData.find(dataItem => dataItem.cid === item.cid);
                        if (dataItem) {
                            dataItem.is_online = 1;
                        }
                        const originalItem = window.originalClassData.find(dataItem => dataItem.cid === item.cid);
                        if (originalItem) {
                            originalItem.is_online = 1;
                        }
                    } else if (item.row) {
                        // 更新表格中的状态显示
                        const statusCell = item.row.find('td:nth-child(7)');
                        statusCell.html('<span class="badge badge-success">已上架</span>');
                        item.row.data('online', 1);
                        // 移除价格修改标记
                        item.row.removeAttr('data-price-modified');
                    }
                });
                
                // 如果是全选所有页，重新显示当前页以反映状态变化
                if (window.selectedAllPages) {
                    displayCurrentPage();
                }
                
                // 清空选择
                $('.item-checkbox').prop('checked', false);
                $('#selectAll').prop('checked', false).prop('indeterminate', false);
                window.selectedAllPages = false;
                window.selectedAllPagesData = [];
                updateSelectedCount();
                
                // 更新统计
                filterTableRows();
                
            } else if (response.code === 2) {
                // 检测到重复课程，显示处理选择弹窗
                showDuplicateHandleModal(response.data, selectedItems, categoryData);
                // 重新启用按钮，因为第一阶段已经完成
                $('#batchOnlineBtn').prop('disabled', false).html('批量上架');
            } else {
                showStatus('上架失败: ' + response.msg, 'error');
            }
        },
        error: function(xhr, status, error) {
            let errorMsg = '网络错误: ' + error;
            if (xhr.responseText) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.msg) {
                        errorMsg = '错误: ' + response.msg;
                    }
                } catch (e) {
                    errorMsg = '服务器返回错误: ' + xhr.responseText.substring(0, 200);
                }
            }
            showStatus(errorMsg, 'error');
        },
        complete: function() {
            $('#batchOnlineBtn').prop('disabled', false).html('批量上架');
        }
    });
}

// 批量上架课程（原函数，保持兼容性）
function batchOnlineCourses(selectedItems, handleDuplicates = 'check') {
    const hid = $('#huoyuanSelect').val();
    if (!hid) {
        showStatus('请先选择货源', 'error');
        return;
    }

    showStatus(`正在上架 ${selectedItems.length} 个课程...`, 'info');
    $('#batchOnlineBtn').prop('disabled', true).html('<div class="loading-spinner"></div> 上架中...');
    
    // 清理数据，移除不能序列化的row对象
    const cleanedItems = selectedItems.map(function(item) {
        return {
            cid: item.cid || '',
            name: item.name || '',
            price: item.price || 0,
            fenlei: item.fenlei || '',
            fenleiname: item.fenleiname || '',
            content: item.content || ''
        };
    });
    
    $.ajax({
        url: '../apisub.php?act=batchonline',
        type: 'POST',
        data: { 
            hid: hid,
            courses: JSON.stringify(cleanedItems),
            handle_duplicates: handleDuplicates
        },
        dataType: 'json',
        success: function(response) {
            if (response.code === 1) {
                // 正常完成
                showStatus(response.msg, 'success');
                
                // 更新表格中的状态显示
                selectedItems.forEach(function(item) {
                    const statusCell = item.row.find('td:nth-child(7)');
                    statusCell.html('<span class="badge badge-success">已上架</span>');
                    item.row.data('online', 1);
                    // 移除价格修改标记
                    item.row.removeAttr('data-price-modified');
                });
                
                // 清空选择
                $('.item-checkbox').prop('checked', false);
                $('#selectAll').prop('checked', false).prop('indeterminate', false);
                window.selectedAllPages = false;
                window.selectedAllPagesData = [];
                updateSelectedCount();
                
                // 更新统计
                filterTableRows();

            } else if (response.code === 2) {
                // 检测到重复课程，显示处理选择弹窗
                showDuplicateHandleModal(response.data, selectedItems);
                // 重新启用按钮，因为第一阶段已经完成
                $('#batchOnlineBtn').prop('disabled', false).html('批量上架');
            } else {
                showStatus('上架失败: ' + response.msg, 'error');
            }
        },
        error: function(xhr, status, error) {
            let errorMsg = '网络错误: ' + error;
            if (xhr.responseText) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.msg) {
                        errorMsg = '错误: ' + response.msg;
                    }
                } catch (e) {
                    errorMsg = '服务器返回错误: ' + xhr.responseText.substring(0, 200);
                }
            }
            showStatus(errorMsg, 'error');
        },
        complete: function() {
            $('#batchOnlineBtn').prop('disabled', false).html('批量上架');
        }
    });
}

// 显示重复课程处理弹窗
function showDuplicateHandleModal(data, originalSelectedItems, categoryData = null) {
    const duplicateCourses = data.duplicate_courses;
    const successCount = data.success_count;
    
    if (!duplicateCourses || duplicateCourses.length === 0) {
        showStatus('没有检测到重复课程数据', 'error');
        return;
    }
    
    // 构建重复课程列表HTML
    let duplicateListHtml = '';
    duplicateCourses.forEach(function(course, index) {
        const priceChangeClass = course.price_changed ? 'text-warning' : 'text-muted';
        const priceChangeIcon = course.price_changed ? '' : '';
        const priceInfo = course.price_changed 
            ? `<span class="text-warning">¥${course.old_price} → ¥${course.new_price}</span>`
            : `<span class="text-muted">¥${course.old_price}</span>`;
        
        duplicateListHtml += `
            <div class="duplicate-item" data-index="${index}">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <strong>${course.name}</strong><br>
                        <small class="text-muted">CID: ${course.cid}</small>
                    </div>
                    <div class="col-md-4">
                        ${priceInfo}
                    </div>
                    <div class="col-md-2 text-right">
                        ${course.price_changed ? '<span class="badge badge-warning">价格变化</span>' : '<span class="badge badge-info">价格相同</span>'}
                    </div>
                </div>
            </div>
        `;
    });
    
    // 创建弹窗HTML
    const modalHtml = `
        <div class="modal fade" id="duplicateHandleModal" tabindex="-1" role="dialog" aria-labelledby="duplicateHandleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content copy-modal">
                    <div class="modal-header copy-modal-header">
                        <h5 class="modal-title" id="duplicateHandleModalLabel">
                            检测到重复课程
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body copy-modal-body">
                        <div class="alert alert-info">
                            已成功上架 <strong>${successCount}</strong> 个新课程，检测到 <strong>${duplicateCourses.length}</strong> 个重复课程需要处理。
                        </div>
                        
                        <h6>重复课程列表：</h6>
                        <div class="duplicate-courses-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; margin-bottom: 1rem;">
                            ${duplicateListHtml}
                        </div>
                        
                        <h6>请选择处理方式：</h6>
                        <div class="copy-mode-options">
                            <div class="copy-mode-option" data-action="skip">
                                <div class="copy-mode-icon">
                                    ⏭
                                </div>
                                <div class="copy-mode-content">
                                    <h6>跳过重复课程</h6>
                                    <p>保持数据库中的原有课程数据不变</p>
                                </div>
                                <div class="copy-mode-check">
                                    ✓
                                </div>
                            </div>
                            <div class="copy-mode-option" data-action="update">
                                <div class="copy-mode-icon">
                                    🔄
                                </div>
                                <div class="copy-mode-content">
                                    <h6>更新重复课程</h6>
                                    <p>用新数据覆盖数据库中的原有课程数据</p>
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
                        <button type="button" class="btn btn-success" id="confirmDuplicateHandleBtn" disabled>
                            确认处理
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // 移除已存在的弹窗
    $('#duplicateHandleModal').remove();
    
    // 添加新弹窗到页面
    $('body').append(modalHtml);
    
    // 绑定事件
    $('#duplicateHandleModal .copy-mode-option').click(function() {
        $('#duplicateHandleModal .copy-mode-option').removeClass('selected');
        $(this).addClass('selected');
        $('#confirmDuplicateHandleBtn').prop('disabled', false);
    });
    
    $('#confirmDuplicateHandleBtn').click(function() {
        const selectedAction = $('#duplicateHandleModal .copy-mode-option.selected').data('action');
        if (selectedAction) {
            bootstrap.Modal.getInstance(document.getElementById('duplicateHandleModal')).hide();
            
            // 重新构建包含重复课程的选中项目列表
            const duplicateSelectedItems = [];
            duplicateCourses.forEach(function(dupCourse) {
                // 使用后端返回的course_data重新构建项目
                const courseData = dupCourse.course_data;
                
                // 找到对应的原始选中项目以获取row对象
                const originalItem = originalSelectedItems.find(item => item.cid === dupCourse.cid);
                
                if (courseData) {
                    // 构建完整的课程数据
                    const duplicateItem = {
                        cid: courseData.cid || dupCourse.cid || '',
                        name: courseData.name || dupCourse.name || '',
                        price: parseFloat(courseData.price || dupCourse.new_price || 0),
                        fenlei: courseData.fenlei || '',
                        fenleiname: courseData.fenleiname || '',
                        content: courseData.content || '',
                        row: originalItem ? originalItem.row : null // 保留原始的row对象用于更新UI
                    };
                    
                    // 确保所有必需字段都有值
                    if (duplicateItem.cid && duplicateItem.name) {
                        duplicateSelectedItems.push(duplicateItem);
                    } else {
                        console.error('课程数据不完整:', duplicateItem);
                    }
                } else {
                    console.error('无法找到课程数据:', dupCourse.cid, '原始项目:', originalItem, '课程数据:', courseData);
                }
            });
            
            if (duplicateSelectedItems.length === 0) {
                showStatus('没有找到有效的重复课程数据', 'error');
                $('#batchOnlineBtn').prop('disabled', false).html('批量上架');
                return;
            }
            
            // 重新调用上架函数，这次处理重复课程
            setTimeout(function() {
                // 在开始处理重复课程时重新禁用按钮
                $('#batchOnlineBtn').prop('disabled', true).html('<div class="loading-spinner"></div> 处理重复课程中...');
                
                // 根据是否有分类数据选择调用哪个函数
                if (categoryData) {
                    batchOnlineCoursesWithCategory(duplicateSelectedItems, categoryData, selectedAction);
                } else {
                    batchOnlineCourses(duplicateSelectedItems, selectedAction);
                }
            }, 100); // 缩短延迟时间
        }
    });
    
    // 绑定弹窗关闭事件 - 弹窗关闭后立刻启用按钮
    $('#duplicateHandleModal').on('hidden.bs.modal', function(e) {
        // 弹窗关闭后立刻启用按钮
        $('#batchOnlineBtn').prop('disabled', false).html('批量上架');
    });
    
    // 显示弹窗
    const modal = new bootstrap.Modal(document.getElementById('duplicateHandleModal'));
    modal.show();
} 