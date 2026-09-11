<?php
$title = '提交订单';
include '../confing/common.php';
$addsalt = md5(mt_rand(0, 999) . time());
$_SESSION['addsalt'] = $addsalt;
?>
<link rel="stylesheet" href="assets/css/element.css">
<meta name="author" content="qingka">
<link rel="stylesheet" href="../assets/css/bootstrap.css" type="text/css" />
<link rel="stylesheet" href="../assets/css/app.css" type="text/css" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/layui/2.9.1/css/layui.css" rel="stylesheet">
<link href="assets/LightYear/css/materialdesignicons.min.css" rel="stylesheet">
<link href="assets/LightYear/css/style.min.css" rel="stylesheet"/>
<script src="../assets/js/bootstrap.min.js"></script>
<script src="//lib.baomitu.com/jquery/1.12.4/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/layer/2.3/layer.js"></script>
<!--<link href="./assets/css/tailwind.min.css" rel="stylesheet">-->
<script src="https://at.alicdn.com/t/font_1185698_xknqgkk0oph.js?spm=a313x.7781069.1998910419.40&file=font_1185698_xknqgkk0oph.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/layui/2.9.1/css/layui.css" rel="stylesheet">
<style>
.custom-notice-list {
  list-style-type: none;
  padding: 0;
  margin: 0;
}

.custom-notice-list li {
  font-weight: bold;
  padding: 10px;
  border-radius: 4px;
  margin-bottom: 10px;
  text-align: center;
}

/* 分类选择器样式 */
.category-box {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 10px;
    position: relative;
}

.category-box.collapsed .category-item:nth-child(n+12) {
    display: none;
}

.category-toggle {
    cursor: pointer;
    color: #3f87f5;
    font-size: 13px;
    padding: 4px 10px;
    border-radius: 8px;
    background: #f0f7ff;
    border: 1px dashed #3f87f5;
    display: inline-flex;
    align-items: center;
    margin-top: 5px;
    transition: all 0.3s;
}

.category-toggle:hover {
    background: #e0f0ff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.category-toggle i {
    margin-left: 4px;
    transition: transform 0.3s;
}

.category-toggle.expanded i {
    transform: rotate(180deg);
}

.category-item {
    position: relative;
    margin: 3px 3px 3px 0;
    cursor: pointer;
}

.category-item input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.category-item .category-btn {
    display: flex;
    align-items: center;
    padding: 4px 6px;
    border-radius: 8px;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
}

.category-item:hover .category-btn {
    background: #e9f5fe;
    transform: translateY(-2px);
    box-shadow: 0 3px 8px rgba(0,0,0,0.08);
}

.category-item input[type="radio"]:checked + .category-btn {
    background: #e0f2ff;
    border-color: #2196F3;
    box-shadow: 0 0 0 2px rgba(33, 150, 243, 0.3);
}

.category-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 10px;
    height: 10px;
    background: #3f87f5;
    border-radius: 50%;
    margin-right: 3px;
}

.category-item input[type="radio"]:checked + .category-btn .category-icon {
    background: #1e88e5;
    box-shadow: 0 0 0 2px rgba(30, 136, 229, 0.2);
}

.category-text {
    color: #333333;
    font-weight: 500;
    font-size: 13px;
}

.category-item input[type="radio"]:checked + .category-btn .category-text {
    color: #1e88e5;
    font-weight: 600;
}

/* 虚拟滚动选择器样式 */
.virtual-select-wrapper {
    width: 100%;
    position: relative;
}

.virtual-select-input {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #dcdfe6;
    border-radius: 4px;
    transition: border-color .2s;
    cursor: pointer;
}

.virtual-select-input:hover {
    border-color: #c0c4cc;
}

.virtual-select-input:focus {
    outline: none;
    border-color: #409eff;
}

.virtual-select-dropdown {
    position: absolute;
    z-index: 1001;
    width: 100%;
    max-height: 300px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid #e4e7ed;
    border-radius: 4px;
    box-shadow: 0 2px 12px 0 rgba(0,0,0,.1);
    margin-top: 5px;
    display: none;
}

.virtual-select-dropdown.show {
    display: block;
}

.virtual-select-option {
    padding: 8px 10px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.virtual-select-option:hover {
    background-color: #f5f7fa;
}

.virtual-select-option.selected {
    color: #409eff;
    font-weight: bold;
}

.virtual-select-loading {
    text-align: center;
    padding: 10px;
    color: #909399;
}

.virtual-select-empty {
    text-align: center;
    padding: 10px;
    color: #909399;
}

.virtual-select-search {
    padding: 8px;
    position: sticky;
    top: 0;
    background: white;
    z-index: 2;
    border-bottom: 1px solid #ebeef5;
}

/* 收藏功能样式 */
.favorite-icon {
    color: #bbb;
    cursor: pointer;
    margin-left: 5px;
    transition: all 0.3s;
}

.favorite-icon.active {
    color: #f1c40f;
}



/* 批量输入模式样式覆盖 */
.form-group .el-textarea .el-textarea__inner {
    color: #606266 !important;
}

/* 响应式按钮组样式 */
.button-group-responsive {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

.btn-responsive {
    flex: 0 0 auto;
    min-width: 90px;
    margin: 0 !important;
    font-size: 13px;
    height: 32px;
    padding: 6px 12px;
}

/* Element UI 按钮PC端样式 */
.el-button.btn-responsive {
    height: 32px !important;
    font-size: 13px !important;
    padding: 6px 12px !important;
    border-radius: 16px;
}

/* Bootstrap 按钮图标间距 */
.btn.btn-responsive i {
    margin-right: 4px;
}

/* 移动端优化 */
@media (max-width: 768px) {
    .button-group-responsive {
        flex-direction: row;
        width: 100%;
        gap: 6px;
        justify-content: space-between;
    }
    
    .btn-responsive {
        flex: 1;
        min-width: 0;
        font-size: 12px;
    }
    
    .col-sm-offset-2 {
        margin-left: 0 !important;
        padding: 0 15px;
    }
    
    /* Element UI 按钮在移动端的优化 */
    .el-button.btn-responsive {
        height: 32px;
        font-size: 12px !important;
        border-radius: 16px;
        padding: 0 8px !important;
    }
    
    /* Bootstrap 按钮在移动端的优化 */
    .btn.btn-responsive {
        height: 32px;
        font-size: 12px !important;
        padding: 6px 8px;
        border-radius: 16px;
    }
}

/* 平板端优化 */
@media (min-width: 769px) and (max-width: 1024px) {
    .button-group-responsive {
        justify-content: flex-start;
    }
    
    .btn-responsive {
        flex: 0 1 auto;
        min-width: 100px;
        font-size: 13px;
        height: 32px;
    }
}
</style>
<style>
    /* 在屏幕宽度小于768px时，使用这个CSS类隐藏元素 */
    @media screen and (max-width: 767px) {
        .hide-on-mobile {
            display: none;
        }
    }

    /* 颜色类合并 */
    .color-blue { color: #3498db; }
    .color-red { color: #e74c3c; }
    .color-green { color: #2ecc71; }
    .color-purple { color: #9b59b6; }
    .color-yellow { color: #f1c40f; }
    .color-navy { color: #34495e; }
    .color-teal { color: #1abc9c; }
    .color-orange { color: #d35400; }
    .color-grey { color: #7f8c8d; }

    /* 图标类合并 */
    .susuicon, .susuicon2, .flex, .flex2 {
        position: absolute;
        vertical-align: -0.15em;
        fill: currentColor;
        overflow: hidden;
    }

    .susuicon {
        left: 21px;
        top: 14px;
        width: 1.3em;
        height: 1.3em;
    }

    .susuicon2, .flex2 {
        top: 50%;
        right: 20px;
        margin-top: -7px;
        transition: transform .3s;
        width: 1.1em;
        height: 1.1em;
    }

    .flex {
        bottom: 7px;
        left: 5px;
        width: 2em;
        height: 2em;
    }

    .flex2 {
        float: left;
        margin-top: 2px;
        margin-right: 5px;
        width: 1.4em;
        height: 1.4em;
    }

    .malet {
        padding-left: 20px;
        font-size: 11px;
    }

    .nav>li:hover {
        background-color: #f8f8ff;
    }

    hr {
        height: 1px;
        margin: 4px;
    }

    /* 表单样式合并 */
    .frosss, .frosss2 {
        height: 38px;
        border-radius: 8px !important;
        border: 2px solid #ebebeb;
        padding: 5px 12px;
        line-height: inherit;
        transition: 0.2s linear;
        box-shadow: none;
    }

    .frosss2 {
        display: block;
        width: 100%;
    }

    .table>thead>tr>th {
        padding: 20px;
    } /* 修复了缺少闭合括号的问题 */

    /* 响应式布局 */
    .column-container {
        columns: 1;
        -webkit-columns: 1;
        -moz-columns: 1;
    }

    .column-item {
        break-inside: avoid;
        page-break-inside: avoid;
        -webkit-column-break-inside: avoid;
    }

    @media (min-width: 768px) {
        .column-container {
            columns: 2;
            -webkit-columns: 2;
            -moz-columns: 2;
        }
    }

    /* Element UI样式覆盖 */
    .form-group .el-textarea .el-textarea__inner {
        color: blue !important;
    }

    .lioverhide {
        width: 300px;
    }

    .flex-row {
        display: flex;
        align-items: stretch;
    }

    @media (min-width: 992px) {
        .col-md-7 {
            flex: 0 0 70%;
            max-width: 70%;
        }

        .col-md-3 {
            flex: 0 0 30%;
            max-width: 30%;
        }
    }

    .custom-notification-list {
        list-style: none;
        padding: 0;
    }

    .custom-notification-list li {
        position: relative;
        padding-left: 20px;
        margin-bottom: 10px;
    }

    .custom-notification-list li:before {
        content: "";
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background-color: #3498db;
    }

    .notification-number {
        font-weight: bold;
        color: #3498db;
        margin-right: 5px;
    }
    
.layui-row {
  display: flex;
  flex-wrap: wrap; /* 允许元素在必要时换行 */
}

.layui-col-xs12 {
  display: flex;
  flex-direction: column; /* 让子元素垂直排列 */
}

.panel {
  display: flex;
  flex-direction: column; /* 让.panel内的元素垂直排列 */
  flex: 1; /* 让.panel元素伸展并占据父元素的全部高度 */
}

.panel-body {
  flex-grow: 1; /* 让.panel-body元素在有多余空间时伸展 */
}
.panel-body img {
  max-height: 300px; /* 设置图片的最大高度，根据需要调整 */
  object-fit: cover; /* 如果您希望图片覆盖整个区域，可以使用这个属性 */
  /* 其他样式根据需要添加 */
}
</style>
<style>
  /* 在宽度小于768px的设备上不显示该元素 */
  @media only screen and (max-width: 768px) {
    .hide-on-mobile {
      display: none;
    }
  }
  
</style>
   <div class="app-content-body ">
        <div class="wrapper-md control" id="add">
             <div class="layui-row">
      <div class="layui-col-xs12" style="box-sizing: border-box;">
    <div class="grid-demo grid-demo-bg1">
         
	       <div class="panel panel-default" style="box-shadow: 8px 8px 15px #d1d9e6, -18px -18px 30px #fff; border-radius:8px;">
		    <div class="panel-heading font-bold " style="border-top-left-radius: 8px; border-top-right-radius: 8px;background-color:#fff;">
		    <div style="float:right;margin-right:10px;">
		        <el-link type="primary"></el-link>
		        <!-- AI矫正开关 -->
		        <div style="display: inline-flex; align-items: center; margin-left: 20px;">
		            <el-switch
		                v-model="aiCorrection"
		                active-text=""
		                inactive-text=""
		                active-color="#13ce66"
		                inactive-color="#dcdfe6"
		                style="margin-right: 8px;">
		            </el-switch>
		            <span style="margin-right: 4px;">AI矫正</span>
		            <el-tooltip placement="bottom">
		                <div slot="content">
		                    新增AI矫正功能（自动删减除账号密码的其他内容）。<br/>
		                    如:账号:188888888 密码:8888888，<br/>
		                    自动删减为:188888888 8888888。<br/>
		                    方便查课，避免客户发一串内容需要自己替换。<br/>
		                    AI校正会自动提取客户发来的账号密码信息，<br/>
		                    大部分情况都可用，<br/>
		                    如果查课时确定账号密码正确但是显示密码错误，<br/>
		                    请关闭AI校正之后重新查课。<br/>
		                    如遇到提取信息有误，请工单反馈问题，<br/>
		                    写清楚校正前的格式
		                </div>
		                <i class="el-icon-question" style="color: #909399; cursor: help; margin-left: 4px;"></i>
		            </el-tooltip>
		        </div>
		    </div>
			    <b style="font-weight: bold; color: black;">订单提交</b>
			    <span>余额：{{ money }} 积分</span>
			    <span v-if="freeadd > 0">剩余下单次数：{{ freeadd }} 次</span>
			     <a href="tjuser" class="btn btn-xs btn-success" v-if="cid==100|cid==101|cid==102|cid==103|cid==104|cid==105" target="_blank">强国录入账号</a>
		     </div>
		    
				<div class="panel-body" style="margin-left:0;">
					<el-form class="form-horizontal devform">
					    <div class="form-group">
					        <label class="col-sm-2 control-label">项目分类</label>
					        <div class="col-sm-9">
					            <div class="col-xs-12">
					                <div class="example-box category-box" :class="{'collapsed': !categoryExpanded}">
					                    <label class="category-item">
					                        <input type="radio" name="e" checked="" @change="fenlei('');">
					                        <div class="category-btn">
					                            <span class="category-icon"></span>
					                            <span class="category-text">全部项目</span>
					                        </div>
					                    </label>
					                    
					                    <!-- 收藏项目选项 -->
					                    <label class="category-item">
					                        <input type="radio" name="e" @change="showFavorites();">
					                        <div class="category-btn">
					                            <span class="category-icon" style="background-color: #ff0000;"></span>
					                            <span class="category-text">收藏项目</span>
					                        </div>
					                    </label>
					                    
					                    <?php
                         $a = $DB->query(
                             "select * from qingka_wangke_fenlei where status=1  ORDER BY `sort` ASC"
                         );
                         $colors = ['#4285F4', '#F1C40F', '#FBBC05', '#34A853', '#8E44AD', '#2980B9', '#16A085', '#27AE60', '#F39C12', '#D35400', '#1ABC9C', '#3498DB', '#9B59B6', '#E74C3C', '#F1C40F', '#2ECC71', '#E67E22', '#95A5A6'];
                         $colorIndex = 0;
                         while ($rs = $DB->fetch($a)) { 
                             $color = $colors[$colorIndex % count($colors)];
                             $colorIndex++;
                         ?>
					                    <label class="category-item">
					                        <input type="radio" name="e" @change="fenlei(<?= $rs['id'] ?>);">
					                        <div class="category-btn">
					                            <span class="category-icon" style="background-color: <?= $color ?>;"></span>
					                            <span class="category-text"><?= $rs['name'] ?></span>
					                        </div>
					                    </label>
					                    <?php } ?>
					                    
					                    <div class="category-toggle" :class="{'expanded': categoryExpanded}" @click="toggleCategories">
					                        {{ categoryExpanded ? '收起分类' : '展开全部' }}
					                        <i class="mdi mdi-chevron-down"></i>
					                    </div>
					                </div>
					            </div>
					         </div>
					    </div>
						<div class="form-group">
							<label class="col-sm-2 control-label">选择平台</label>&nbsp;&nbsp;&nbsp;&nbsp; 
						<div class="col-sm-9">
							<!-- 使用自定义的虚拟滚动选择器 -->
							<div v-if="!showingFavorites" class="virtual-select-wrapper">
								<input 
									type="text" 
									class="virtual-select-input" 
									:placeholder="selectedProduct ? selectedProduct.name : '点击选择下单平台，也可直接输入关键字搜索'" 
									readonly
									@click="toggleProductSelector"
									:value="selectedProduct ? selectedProduct.name + '(' + selectedProduct.price + '积分)' : ''"
								>
								<div class="virtual-select-dropdown" :class="{'show': showProductSelector}">
									<!-- 搜索框 -->
									<div class="virtual-select-search">
										<input 
											type="text" 
											class="form-control" 
											v-model="productSearchKeyword" 
											placeholder="搜索商品..." 
											@click.stop
											autofocus
										>
									</div>
									<div v-if="isLoadingProducts" class="virtual-select-loading">
										<i class="el-icon-loading"></i> 加载中...
									</div>
									<div v-else-if="filteredProducts.length === 0" class="virtual-select-empty">
										未找到匹配的商品
									</div>
									<div v-else>
										<div 
											v-for="product in displayedProducts" 
											:key="product.cid" 
											class="virtual-select-option"
											:class="{'selected': cid === product.cid}"
											@click="selectProduct(product)"
										>
											<span>{{ product.name }} ({{ product.price }}积分)</span>
											<i 
												class="mdi mdi-star favorite-icon" 
												:class="{'active': isFavorite(product)}" 
												@click.stop="toggleFavorite(product)"
											></i>
										</div>
										<div v-if="hasMoreProducts" class="virtual-select-loading" v-intersect="loadMoreProducts">
											<i class="el-icon-loading"></i> 加载更多...
										</div>
									</div>
								</div>
							</div>
							
							<!-- 收藏商品使用原始el-select -->
							<el-select 
								v-else
								v-model="cid" 
								filterable 
								@change="tips(cid)" 
								placeholder="选择收藏的商品" 
								style="width:100%">
								<div v-if="filteredFavorites.length === 0" style="padding: 10px; text-align: center; color: #909399;">
									暂无收藏项目，请在选择平台下选择商品后点击星标收藏
								</div>
								<el-option
									v-for="item in filteredFavorites"
									:key="item.cid"
									:label="item.name+'('+item.price+'积分)'"
									:value="item.cid">
									<div style="display: flex; justify-content: space-between; width: 100%;">
										<span>{{ item.name }} ({{ item.price }}积分)</span>
										<i class="mdi mdi-star favorite-icon active" 
										   @click.stop="toggleFavorite(item)"></i>
									</div>
								</el-option>
							</el-select>
						</div>
						</div>
						<div class="form-group">
							<label class="col-sm-2 control-label">信息填写</label>
							<div class="col-sm-9">
								<div style="display: flex; align-items: center; width: 100%;">
									<el-input 
										v-if="!isBatchMode" 
										v-model="userinfo" 
										placeholder="请输入下单信息：学校 账号 密码" 
										prefix-icon="el-icon-search" 
										style="flex-grow: 1;"
										@blur="handleAiCorrection">
									</el-input>
									<el-input 
										v-if="isBatchMode" 
										type="textarea" 
										:rows="4" 
										v-model="userinfo" 
										placeholder="每行一条信息，例如：&#13;&#10;学校1 账号1 密码1&#13;&#10;学校2 账号2 密码2" 
										style="flex-grow: 1;"
										@blur="handleAiCorrection">
									</el-input>
									<el-tooltip :content="isBatchMode ? '切换到单条输入' : '切换到批量输入'" placement="top" style="margin-left: 10px;">
										<el-button @click="toggleInputMode" :icon="isBatchMode ? 'el-icon-user' : 'el-icon-notebook-2'"></el-button>
									</el-tooltip>
									<el-tooltip content="手动执行AI矫正" placement="top" style="margin-left: 5px;" v-if="aiCorrection">
										<el-button @click="executeAiCorrection" icon="el-icon-magic-stick" size="small" type="success"></el-button>
									</el-tooltip>
								</div>
							</div>
						</div>
						<div class="form-group">
    <label class="col-sm-2 control-label">网课说明</label>
    <div class="col-sm-9">
        <el-input
            type="textarea"
            :rows="4"
            placeholder="请选择商品查看说明"
            v-model="content"
            disabled>
        </el-input>
    </div>
</div>
				  	    <div class="col-sm-offset-2">
				  	    	<div class="button-group-responsive">
				  	    		<el-button type="primary" @click="get" icon="el-icon-search" round class="btn-responsive">立即查询</el-button>
				  	    		<el-button type="primary" @click="add" icon="el-icon-circle-check" round class="btn-responsive">提交订单</el-button>
				  	    		<button class="btn btn-label btn-round btn-warning btn-responsive" type="reset" value="清空数据">
				  	    			<i class="mdi mdi-delete-empty"></i> 清空数据
				  	    		</button>
				  	    	</div>
 	        
		       </div>
			 </el-form>
		   </div>
	     </div>
	    </div>
	  </div>


	   
	   
 </div>
	     

<div class="row">
    <div class="col-xs-12">
	    <div class="panel panel-default" style="box-shadow: 8px 8px 15px #d1d9e6, -18px -18px 30px #fff; border-radius:8px;">
  <div class="panel-heading font-bold" style="border-top-left-radius: 8px; border-top-right-radius: 8px;background-color:#fff;">
    查询结果 &nbsp;
    <a class="el-button el-button--primary is-plain el-button--mini" style="padding: 4px 10px;" @click="selectAll()">全选</a>
  </div>
  <div class="panel-body">
    <form class="form-horizontal devform">    
      <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
        <div v-for="(rs, key) in row">
          <div class="panel panel-default">
            <div class="panel-heading" role="tab" id="headingOne">
              <h4 class="panel-title">                
                <a role="button" data-toggle="collapse" data-parent="#accordion" :href="'#'+key" aria-expanded="true" >
                  <b>{{ rs.userName }}</b>  {{ rs.userinfo }} <span v-if="rs.msg=='查询成功'"><b style="color: green;">{{ rs.msg }}</b></span><span v-else-if="rs.msg!='查询成功'"><b style="color: red;">{{ rs.msg }}</b></span>
                </a>
              </h4>
            </div>
            <div :id="key" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
              <div class="panel-body">
                <!-- Column container -->
                <div class="column-container">
                  <!-- Column items -->
                  <div v-for="(res, key) in rs.data" class="column-item">
                    <label class="layui-table lyear-checkbox checkbox-inline checkbox-success">
                      <li>
                        <input style="margin-left: 0px;" :checked="checked" name="checkbox" type="checkbox" :value="res.name" @click="checkResources(rs.userinfo, rs.userName, rs.data, res.id, res.name)">
                        <span>
                          <b>{{ res.name }}</b><span v-if="res.state!=''" style="color: orange;">  {{ res.state }} </span><span v-else style="color: green;"> - 开课中 </span>
                        </span>
                        <br>
                        <span style="color: red;">
                          [ ID:{{ res.id }} ] &nbsp; [ 老师:{{ res.teacher ? res.teacher : '无' }} ]
                        </span>
                      </li>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>      
    </form>
  </div>
</div>

        <div class="panel panel-default" style="box-shadow: 8px 8px 15px #d1d9e6, -18px -18px 30px #fff; border-radius:8px;">
    <div class="panel-heading font-bold bg-white" style="border-radius: 10px; color: #3498db;">注意事项</div>
    <div class="panel-body">
        <ul class="layui-timeline">
            <li class="layui-timeline-item">
                <i class="layui-icon layui-timeline-axis" style="color: #e74c3c;"></i>
                <div class="layui-timeline-content layui-text" style="color: #2ecc71;">
                    <p>请务必查看项目下单须知和说明，防止出现错误！</p>
                </div>
            </li>
            <li class="layui-timeline-item">
                <i class="layui-icon layui-timeline-axis" style="color: #9b59b6;"></i>
                <div class="layui-timeline-content layui-text" style="color: #f1c40f;">
                    <p>同商品重复下单，请修改密码后再下！</p>
                </div>
            </li>
            <li class="layui-timeline-item">
                <i class="layui-icon layui-timeline-axis" style="color: #34495e;"></i>
                <div class="layui-timeline-content layui-text" style="color: #1abc9c;">
                    <p>默认下单格式为学校、账号、密码(空格分开)！</p>
                </div>
            </li>
            <li class="layui-timeline-item">
                <i class="layui-icon layui-timeline-axis" style="color: #d35400;"></i>
                <div class="layui-timeline-content layui-text" style="color: #7f8c8d;">
                    <p>查课出问题及时反馈！</p>
                </div>
            </li>
        </ul>
    </div>
</div>
    </div>
<script>
    
$(document).ready(function(){
 $("#btn4").click(function(){ 
$("input[name='checkbox']").each(function(){ 
if($(this).attr("checked")) 
{ 
$(this).removeAttr("checked"); 
} 
else
{ 
$(this).attr("checked","true"); 
} 
}) 
}) 




}); 
</script>

<script type="text/javascript" src="assets/LightYear/js/jquery.min.js"></script>
<script type="text/javascript" src="assets/LightYear/js/bootstrap.min.js"></script>
<script type="text/javascript" src="assets/LightYear/js/perfect-scrollbar.min.js"></script>
<script type="text/javascript" src="assets/LightYear/js/main.min.js"></script>
<script src="assets/js/aes.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.6.11/vue.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/vue-resource/1.5.1/vue-resource.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.18.0/axios.min.js"></script>
<script src="./assets/js/element.js"></script>




<script>
// 添加交叉观察器指令，用于检测元素可见性，实现无限滚动
Vue.directive('intersect', {
    inserted: function (el, binding) {
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {
                binding.value();
            }
        }, {
            rootMargin: '0px 0px 100px 0px'
        });
        observer.observe(el);
        el._observer = observer;
    },
    unbind: function (el) {
        if (el._observer) {
            el._observer.disconnect();
        }
    }
});

var vm=new Vue({
	el:"#add",
	data:{	
	    row:[],
	    shu:'',
	    bei:'',
	    nochake:0,
	    bujiu:'',
	    check_row:[],
		userinfo:'',
		cid:'',
		miaoshua:'',
		class1:'',
		class3:'',
		activems:false,
		checked:false,
		content:'',
		money: 0, // 余额
		freeadd: 0, // 免费次数
		isBatchMode: false, // 批量输入模式
		categoryExpanded: false, // 分类展开状态
		favorites: [], // 收藏的项目列表
		showingFavorites: false, // 是否正在显示收藏项目
		cacheExpiration: 60 * 60 * 1000, // 缓存有效期：1小时
		fullClass1: [], // 存储完整数据
		aiCorrection: false, // AI矫正开关
		
		// 虚拟滚动相关数据
		showProductSelector: false,
		filteredProducts: [],
		displayedProducts: [],
		currentDisplayCount: 50, // 初始显示50个商品
		loadingThrottle: false,
		isLoadingProducts: false,
		selectedProduct: null,
		productSearchKeyword: ''
	},
	computed: {
		// 过滤收藏的项目
		filteredFavorites: function() {
			return this.favorites.filter(item => item != null);
		},
		
		// 是否有更多商品可加载
		hasMoreProducts: function() {
			return this.displayedProducts.length < this.filteredProducts.length;
		}
	},
	watch: {
        bujiu: function(newValue, oldValue) {
            this.miao = newValue ? 1 : 0; // 根据复选框选中状态设置miao的值为1或0
        },
		
		// 监听CID变化，同步到自定义选择器
		cid: function(val) {
			if (!this.showingFavorites && val) {
				const product = this.fullClass1.find(item => item.cid == val);
				if (product) {
					this.selectedProduct = product;
				}
			}
		},
		
		// 监听搜索关键词变化
		productSearchKeyword: function(val) {
			this.debounceSearch();
		}
    },
	created() {
		// 从本地存储加载收藏项目
		const savedFavorites = localStorage.getItem('favorites');
		if (savedFavorites) {
			try {
				this.favorites = JSON.parse(savedFavorites);
			} catch (e) {
				console.error('加载收藏项目失败', e);
				this.favorites = [];
			}
		}
		
		// 注册点击外部关闭下拉框
		document.addEventListener('click', this.closeProductSelector);
		
		// 初始化获取分类、信息
		this.getclass();
		this.getUserInfo();
		
		// 创建防抖搜索函数
		this.debounceSearch = this.debounce(function() {
			this.filterProducts();
			this.currentDisplayCount = 50;
			this.updateDisplayedProducts();
		}, 300);
	},
	beforeDestroy() {
		// 清理事件监听
		document.removeEventListener('click', this.closeProductSelector);
	},
	methods:{
	    get: function(salt) {
    if (this.cid == '' || this.userinfo == '') {
        layer.msg("所有项目不能为空");
        return false;
    }
    var userinfo_processed = this.userinfo.replace(/\r\n/g, "[br]").replace(/\n/g, "[br]").replace(/\r/g, "[br]");
    var userinfo_array = userinfo_processed.split('[br]'); 

    this.row = [];
    this.check_row = [];
    this.checked = false; 
    for (var i = 0; i < userinfo_array.length; i++) {
        let info_item = userinfo_array[i];
        if (info_item == '') { continue; }
        var hash = getENC('<?php echo $addsalt; ?>');
        var loading = layer.load(3);
        this.$http.post("/apisub.php?act=get", {
            cid: this.cid,
            userinfo: info_item,
            hash
        }, {
            emulateJSON: true
        }).then(function(data) {
            layer.close(loading);
            if (data.body.code == -7) {
                salt = getENC(data.body.msg);
                vm.get(salt);
            } else {
                this.row.push(data.body);
            }
        });
    }
},
	    add: function() {
    if (this.cid == '') {
        if (this.nochake != 1) {
            layer.msg("请先查课");
            return false;
        }
    }
    if (this.check_row.length < 1) {
        if (this.nochake != 1) {
            layer.msg("请先选择课程");
            return false;
        }
    }
    //console.log(this.check_row);
    var loading = layer.load(3);
    this.$http.post("/apisub.php?act=add", {
        cid: this.cid,
        data: this.check_row,
        shu: this.shu,
        bei: this.bei,
        userinfo: this.userinfo,
        nochake: this.nochake
    }, {
        emulateJSON: true
    }).then(function(data) {
        layer.close(loading);
        if (data.data.code == 1) {
            var submittedCoursesCount = this.check_row.length; // 获取提交的课程数量
            this.row = [];
            this.check_row = [];
            // 显示提交成功的消息，并包含提交的课程数量
            layer.msg('提交成功' + submittedCoursesCount + '门课程', {icon: 1, time: 2000});
        } else {
            this.$message({type: 'error', showClose: true, message: data.data.msg});
        }
    });
},
	    check888:function(userinfo,userName,rs,name){
	        var btns=document.getElementById("btns");
	        var  zk= document.getElementById("s1");
	        var x= zk.getElementsByTagName("input");
        	if(btns.checked==true) {
        		for(var i=0   ; i < x.length; ++i) {
                    data={userinfo,userName,data:rs[i]};
        			x[i].checked=true;
        		    vm.check_row.push(data);
        		}
        	}else {
        		for(var i=0; i < x.length; ++i) {
        			x[i].checked=false; 
        		}
        		 this.check_row = []
        	}
	    },
	    selectAll:function () {            
            if(this.cid==''){
	    		layer.msg("请先查课");
	    		return false;
	    	} 	
	    	this.checked=!this.checked;  
	    	if(this.check_row.length<1){
		    	for(i=0;i<vm.row.length;i++){
		    		console.log(i);
		    		userinfo=vm.row[i].userinfo
		    		userName=vm.row[i].userName
		    		rs=vm.row[i].data
		            for(a=0;a<rs.length;a++){
			    		aa=rs[a]
			    		data={userinfo,userName,data:aa}
			    		vm.check_row.push(data);
			        } 				    	
				}     	          
            }else{
            	vm.check_row=[]
            }   	    
	    	console.log(vm.check_row);                            
        },
	       checkResources: function(userinfo, userName, rs, id, name) {
    var course;
    if (id) {
        course = rs.find(function(course) {
            return course.id === id;
        });
    } else {
        course = rs.find(function(course) {
            return course.name === name;
        });
    }

    var data = {
        userinfo: userinfo,
        userName: userName,
        data: course
    };

    var index = this.check_row.findIndex(function(item) {
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
	    fenlei:function(id){
	    	// 切换分类
	    	this.showingFavorites = false;
	    	this.productSearchKeyword = ''; // 重置搜索关键词
	    	
		  var load=layer.load(3);
 			this.$http.post("/apisub.php?act=getclassfl",{id:id},{emulateJSON:true}).then(function(data){	
	          	layer.close(load);
	          	if(data.data.code==1){			                     	
	          		this.fullClass1 = data.body.data;
	          		this.class1=data.body.data;
	          		// 预筛选商品
	          		this.filterProducts();			             			                     
	          	}else{
	                layer.msg(data.data.msg,{icon:2});
	          	}
	        });	
	    	
	    },
	    getclass:function(){
		  var load=layer.load(3);
		  
 			this.$http.post("/apisub.php?act=getclass").then(function(data){	
	          	layer.close(load);
	          	if(data.data.code==1){			                     	
	          		this.fullClass1 = data.body.data;
	          		this.class1=data.body.data;
	          		// 预筛选初始商品列表
	          		this.filterProducts();			             			                     
	          	}else{
	                layer.msg(data.data.msg,{icon:2});
	          	}
	        });	
	    	
	    },
	    getnock:function(cid){
 			this.$http.post("/apisub.php?act=getnock").then(function(data){	
	          	if(data.data.code==1){			                     	
	          		this.nock=data.body.data;	
	          		for(i=0;this.nock.length>i;i++){
	          		    if(cid==this.nock[i].cid){
	          		        this.nochake=1;
	          		        break;
	          		    }else{
	          		        this.nochake=0;
	          		    }
	          		}
	          	}else{
	                layer.msg(data.data.msg,{icon:2});
	          	}
	        });	
	    	
	    },
	    tips: function (message) {
    for (var i = 0; this.class1.length > i; i++) {
        if (this.class1[i].cid == message) {
            this.show = true;
            // 在 content 中加入 CID 信息
            this.content = '[对接CID=' + this.class1[i].cid + '] ' + this.class1[i].content;
            if (this.class1[i].miaoshua == 1) {
                this.activems = true;
            } else {
                this.activems = false;
            }
            return false; // 早期 return，防止继续循环
        }
    }
},
        tips2: function() {
				layer.tips('勾选补救消耗0.1费用', '#bujiu');
			},
			
			// 新增方法
			getUserInfo() {
				// 获取用户信息，更新余额等
				this.$http.get('/apisub.php?act=userinfo').then(response => {
					if(response.body.code === 1) {
						this.money = response.body.money;
						this.freeadd = response.body.freeadd;
					} else {
						console.error("获取用户信息失败:", response.body.msg);
					}
				}).catch(error => {
					console.error("请求用户信息接口失败", error);
				});
			},
			
			toggleInputMode: function() {
				this.isBatchMode = !this.isBatchMode;
			},
			
			toggleCategories: function() {
				this.categoryExpanded = !this.categoryExpanded;
			},
			
			toggleFavorite(item) {
				event.stopPropagation();
				
				const index = this.favorites.findIndex(f => f.cid === item.cid);
				if (index !== -1) {
					this.favorites.splice(index, 1);
					layer.msg('已取消收藏', {icon: 5});
				} else {
					this.favorites.push({
						cid: item.cid,
						name: item.name,
						price: item.price,
						content: item.content,
						fenlei: item.fenlei,
						miaoshua: item.miaoshua
					});
					layer.msg('已添加到收藏', {icon: 1});
				}
				
				localStorage.setItem('favorites', JSON.stringify(this.favorites));
			},
			
			isFavorite(item) {
				return this.favorites.some(f => f.cid === item.cid);
			},
			
			showFavorites() {
				this.showingFavorites = true;
				
				var load = layer.load(3);
				
				setTimeout(() => {
					if (this.favorites && this.favorites.length > 0) {
						this.cid = this.favorites[0].cid;
						this.tips(this.cid);
					} else {
						this.cid = '';
						this.content = '请先收藏项目';
					}
					layer.close(load);
				}, 300);
			},
			
			// 防抖函数
			debounce: function(fn, delay) {
				let timeout;
				return function() {
					const context = this;
					const args = arguments;
					clearTimeout(timeout);
					timeout = setTimeout(() => {
						fn.apply(context, args);
					}, delay);
				};
			},
			
			// 切换商品选择器显示状态
			toggleProductSelector: function(event) {
				event.stopPropagation();
				this.showProductSelector = !this.showProductSelector;
				if (this.showProductSelector && this.fullClass1.length > 0) {
					if (this.filteredProducts.length === 0) {
						this.filterProducts();
						this.updateDisplayedProducts();
					}
					this.$nextTick(() => {
						const searchInput = document.querySelector('.virtual-select-search input');
						if (searchInput) searchInput.focus();
					});
				}
			},
			
			// 关闭商品选择器
			closeProductSelector: function() {
				this.showProductSelector = false;
			},
			
			// 筛选商品方法
			filterProducts: function() {
				this.isLoadingProducts = true;
				
				setTimeout(() => {
					let result = this.fullClass1 || [];
					
					if (this.productSearchKeyword) {
						const keyword = this.productSearchKeyword.toLowerCase();
						result = result.filter(item => 
							(item.name && item.name.toLowerCase().includes(keyword))
						);
					}
					
					this.filteredProducts = result;
					this.isLoadingProducts = false;
					this.updateDisplayedProducts();
				}, 0);
			},
			
			// 更新显示的商品列表
			updateDisplayedProducts: function() {
				this.displayedProducts = this.filteredProducts.slice(0, this.currentDisplayCount);
			},
			
			// 加载更多商品
			loadMoreProducts: function() {
				if (this.loadingThrottle || this.isLoadingProducts) return;
				
				this.loadingThrottle = true;
				setTimeout(() => {
					this.currentDisplayCount += 50;
					this.updateDisplayedProducts();
					this.loadingThrottle = false;
				}, 300);
			},
			
			// 选择商品
			selectProduct: function(product) {
				this.cid = product.cid;
				this.selectedProduct = product;
				this.tips(product.cid);
				this.showProductSelector = false;
			},
			
			// AI矫正处理函数 - 失去焦点时自动处理
			handleAiCorrection: function() {
				if (this.aiCorrection && this.userinfo.trim()) {
					const originalText = this.userinfo;
					const correctedText = this.performAiCorrection(originalText);
					
					if (correctedText !== originalText) {
						this.userinfo = correctedText;
						layer.msg('AI矫正：已自动提取账号密码信息', {icon: 1, time: 2000});
					}
				}
			},
			
			// 执行AI矫正
			executeAiCorrection: function() {
				if (!this.userinfo.trim()) {
					layer.msg('请先输入信息内容', {icon: 7});
					return;
				}
				
				const originalText = this.userinfo;
				const correctedText = this.performAiCorrection(originalText);
				
				// 检查处理后是否包含中文字符需要用户确认
				const chinesePattern = /[\u4e00-\u9fa5\u3000-\u303f\uff00-\uffef]/g;
				const hasChineseChars = chinesePattern.test(correctedText);
				
				if (correctedText !== originalText) {
					this.userinfo = correctedText;
					if (hasChineseChars) {
						layer.msg('AI矫正完成：已提取账号密码信息，请注意中文字符提示', {icon: 3, time: 3000});
					} else {
						layer.msg('AI矫正完成：已自动提取账号密码信息', {icon: 1, time: 2000});
					}
				} else {
					if (hasChineseChars) {
						layer.msg('内容格式正确，但包含中文字符，请注意提示', {icon: 3, time: 2500});
					} else {
						layer.msg('内容格式正确，无需矫正', {icon: 6, time: 1500});
					}
				}
			},
			
			// AI矫正核心算法
			performAiCorrection: function(text) {
				if (!text) return text;
				
				// 处理批量输入模式
				if (this.isBatchMode) {
					const lines = text.split(/\r?\n/);
					const correctedLines = lines.map(line => this.correctSingleLine(line.trim())).filter(line => line);
					return correctedLines.join('\n');
				} else {
					// 单行输入模式
					return this.correctSingleLine(text.trim());
				}
			},
			
			// 矫正单行文本
			correctSingleLine: function(line) {
				if (!line || line.trim() === '') return '';
				
				// 预处理：处理末尾中文字的情况
				line = this.removeTrailingChinese(line);
				
				// 常见的分隔符和关键词
				const accountKeywords = ['账号', '用户名', '帐号', 'username', 'account', 'user'];
				const passwordKeywords = ['密码', 'password', 'pwd', 'pass'];
				const schoolKeywords = ['学校', 'school', '院校', '大学', '学院'];
				
				// 提取的信息数组
				let extractedInfo = [];
				
				// 模式1: 标准格式 "学校 账号 密码" 
				const standardPattern = /^[\s]*([^\s]+)[\s]+([^\s]+)[\s]+([^\s]+)[\s]*$/;
				const standardMatch = line.match(standardPattern);
				if (standardMatch && !this.containsKeywords(line, [...accountKeywords, ...passwordKeywords])) {
					// 如果已经是标准格式且不包含关键词，直接返回
					return standardMatch[1] + ' ' + standardMatch[2] + ' ' + standardMatch[3];
				}
				
				// 模式2: 简单账号密码格式（两个用空格分隔的字段）
				const simplePattern = /^[\s]*([^\s]+)[\s]+([^\s]+)[\s]*$/;
				const simpleMatch = line.match(simplePattern);
				if (simpleMatch && !this.containsKeywords(line, [...accountKeywords, ...passwordKeywords])) {
					// 验证第一个是账号格式，第二个是密码格式
					const potentialAccount = simpleMatch[1];
					const potentialPassword = simpleMatch[2];
					if (this.looksLikeAccount(potentialAccount) && this.looksLikePassword(potentialPassword)) {
						return potentialAccount + ' ' + potentialPassword;
					}
				}
				
				// 模式3: 包含关键词的复杂格式
				let school = '', account = '', password = '';
				
				// 使用更精确的提取方法
				const extractedData = this.extractAccountPasswordWithKeywords(line);
				if (extractedData.account) account = extractedData.account;
				if (extractedData.password) password = extractedData.password;
				if (extractedData.school) school = extractedData.school;
				
				// 模式4: 数字和特殊字符识别 (如果没有找到明确的账号密码)
				if (!account || !password) {
					const fallbackData = this.extractAccountPasswordFallback(line);
					if (!account && fallbackData.account) account = fallbackData.account;
					if (!password && fallbackData.password) password = fallbackData.password;
				}
				
				// 组装结果
				const result = [];
				if (school) result.push(school);
				if (account) result.push(account);
				if (password) result.push(password);
				
				// 如果只提取到账号密码，尝试补充学校信息
				if (result.length === 2 && !school) {
					// 从原文中寻找可能的学校名称
					const possibleSchool = this.extractSchoolName(line);
					if (possibleSchool) {
						result.unshift(possibleSchool);
					}
				}
				
				return result.length >= 2 ? result.join(' ') : line;
			},
			
			// 使用关键词精确提取账号密码
			extractAccountPasswordWithKeywords: function(text) {
				const result = { account: '', password: '', school: '' };
				
				// 账号提取模式
				const accountPatterns = [
					/(?:账号|用户名|帐号|username|account|user)[\s：:：]*([a-zA-Z0-9@._-]+)/gi,
					/(?:账号|用户名|帐号|username|account|user)[\s：:：]*(\d+)/gi,
					/(?:账号|用户名|帐号|username|account|user)([a-zA-Z0-9@._-]+)/gi  // 无分隔符的情况
				];
				
				// 密码提取模式 - 捕获所有字符（包括中文符号）
				const passwordPatterns = [
					/(?:密码|password|pwd|pass)[\s：:：]*([^\s：:：]+)/gi,
					/(?:密码|password|pwd|pass)([^\s：:：]+)/gi  // 无分隔符的情况
				];
				
				// 学校提取模式
				const schoolPatterns = [
					/(?:学校|school|院校|大学|学院)[\s：:：]*([^\s：:：]{2,15})/gi
				];
				
				// 提取账号
				for (let pattern of accountPatterns) {
					const match = text.match(pattern);
					if (match && match[0]) {
						const extracted = match[0].replace(/(?:账号|用户名|帐号|username|account|user)[\s：:：]*/gi, '');
						if (this.isValidAccount(extracted)) {
							result.account = this.cleanChineseCharacters(extracted);
							break;
						}
					}
				}
				
				// 提取密码
				for (let pattern of passwordPatterns) {
					const match = text.match(pattern);
					if (match && match[0]) {
						const extracted = match[0].replace(/(?:密码|password|pwd|pass)[\s：:：]*/gi, '');
						if (this.isValidPassword(extracted)) {
							result.password = this.processPasswordCharacters(extracted);
							break;
						}
					}
				}
				
				// 提取学校
				for (let pattern of schoolPatterns) {
					const match = text.match(pattern);
					if (match && match[0]) {
						const extracted = match[0].replace(/(?:学校|school|院校|大学|学院)[\s：:：]*/gi, '');
						if (extracted.length >= 2) {
							result.school = extracted;
							break;
						}
					}
				}
				
				return result;
			},
			
			// 后备提取方法
			extractAccountPasswordFallback: function(text) {
				const result = { account: '', password: '' };
				
				// 使用空格和常见分隔符来分割，获取所有可能的候选项
				const candidates = text.split(/[\s：:：,，、；;|｜\-_=]+/)
					.filter(item => item.trim().length >= 3)
					.sort((a, b) => {
						// 优先考虑包含数字的字符串
						const aHasNumber = /\d/.test(a);
						const bHasNumber = /\d/.test(b);
						if (aHasNumber && !bHasNumber) return -1;
						if (!aHasNumber && bHasNumber) return 1;
						return b.length - a.length;
					});
				
				if (candidates.length >= 2) {
					result.account = this.cleanChineseCharacters(candidates[0]);
					result.password = this.processPasswordCharacters(candidates[1]);
				}
				
				return result;
			},
			
			// 验证账号有效性
			isValidAccount: function(account) {
				if (!account || account.length < 3) return false;
				// 账号通常是数字或字母数字组合
				return /^[a-zA-Z0-9@._-]+$/.test(account);
			},
			
			// 验证密码有效性
			isValidPassword: function(password) {
				if (!password || password.length < 3) return false;
				// 密码可以包含英文字符、数字、英文符号，以及中文字符
				// 不限制字符类型，让后续的processPasswordCharacters函数来处理中文字符
				return true;
			},
			
			// 处理密码字符 - 保留英文符号，提示中文字符
			processPasswordCharacters: function(password) {
				// 检查是否包含中文字符（包括中文符号）
				// 中文汉字范围
				const chineseCharsPattern = /[\u4e00-\u9fa5]/g;
				// 中文标点符号范围  
				const chinesePunctuationPattern = /[\u3000-\u303f]/g;
				// 全角字符范围（包括全角符号如：！？，。等）
				const fullWidthPattern = /[\uff00-\uffef]/g;
				
				const chineseChars = password.match(chineseCharsPattern) || [];
				const chinesePunctuation = password.match(chinesePunctuationPattern) || [];
				const fullWidthChars = password.match(fullWidthPattern) || [];
				
				const allChineseMatches = [...chineseChars, ...chinesePunctuation, ...fullWidthChars];
				
				if (allChineseMatches.length > 0) {
					// 区分不同类型的中文字符进行提示
					this.showChineseCharacterPrompt(password, allChineseMatches, {
						chars: chineseChars,
						punctuation: chinesePunctuation, 
						fullWidth: fullWidthChars
					});
					return password; // 先返回原密码，等用户选择后再处理
				}
				
				// 保留所有英文字符、数字、英文符号
				// 包括: . ! @ # $ % ^ & * ( ) - _ + = [ ] { } | \ : ; " ' < > , ? / ~ `
				return password;
			},
			
			// 显示中文字符提示
			showChineseCharacterPrompt: function(originalPassword, chineseMatches, details) {
				const vm = this;
				setTimeout(() => {
					// 构建详细的提示信息
					let promptMessage = '输入内容中包含以下字符：<br/>';
					
					if (details.chars.length > 0) {
						promptMessage += '• 中文汉字：<span style="color: red;">' + details.chars.join(' ') + '</span><br/>';
					}
					if (details.punctuation.length > 0) {
						promptMessage += '• 中文标点：<span style="color: orange;">' + details.punctuation.join(' ') + '</span><br/>';
					}
					if (details.fullWidth.length > 0) {
						promptMessage += '• 中文符号：<span style="color: #ff6b6b;">' + details.fullWidth.join(' ') + '</span><br/>';
						
						// 对中文符号提供英文符号的对照
						const fullWidthToHalfWidth = {
							'！': '!', '？': '?', '：': ':', '；': ';', '，': ',', '。': '.', 
							'（': '(', '）': ')', '【': '[', '】': ']', '《': '<', '》': '>',
							'￥': '$', '￡': '£', '＠': '@', '＃': '#', '％': '%', '＆': '&',
							'＊': '*', '＋': '+', '－': '-', '＝': '=', '＿': '_', '｜': '|',
							'～': '~', '｀': '`', '＾': '^', '＼': '\\', '／': '/', '｛': '{', '｝': '}',
							'＂': '"', '＇': "'", '　': ' ', '．': '.', '，': ','
						};
						
						const suggestions = details.fullWidth.map(char => fullWidthToHalfWidth[char] || char).filter(char => char !== undefined);
						if (suggestions.length > 0 && suggestions.join('') !== details.fullWidth.join('')) {
							promptMessage += '• 可替换为：<span style="color: green; font-weight: bold;">' + suggestions.join(' ') + '</span><br/>';
						}
					}
					
					promptMessage += '<br/>请选择处理方式：<br/>';
					promptMessage += '<span style="color: #666; font-size: 12px;">• 移除中文：删除所有中文字符<br/>• 替换为英文：将中文符号转换为英文符号<br/>• 保留原样：不做任何修改</span>';
					
					layer.confirm(promptMessage, {
						btn: ['移除中文', '替换为英文', '保留原样'],
						btn1: function(index) {
							// 移除中文字符
							const chinesePattern = /[\u4e00-\u9fa5\u3000-\u303f\uff00-\uffef]/g;
							const cleanPassword = originalPassword.replace(chinesePattern, '');
							
							// 更新用户输入
							vm.updatePasswordInUserinfo(originalPassword, cleanPassword);
							
							layer.close(index);
							layer.msg('已移除密码中的中文字符：' + chineseMatches.join(''), {icon: 1, time: 3000});
						},
						btn2: function(index) {
							// 替换为英文符号
							const replacedPassword = vm.replaceChineseSymbolsToEnglish(originalPassword);
							
							// 更新用户输入
							vm.updatePasswordInUserinfo(originalPassword, replacedPassword);
							
							layer.close(index);
							layer.msg('已将中文符号替换为英文符号', {icon: 1, time: 3000});
						},
						btn3: function(index) {
							// 保留中文字符
							layer.close(index);
							layer.msg('已保留密码中的中文字符', {icon: 6, time: 2000});
						},
						icon: 3,
						title: 'AI矫正 - 中英文符号检测',
						area: ['480px', 'auto']
					});
				}, 100); // 延迟100ms确保DOM更新完成
			},
			
			// 清理中文字符（仅用于账号处理）
			cleanChineseCharacters: function(text) {
				return text.replace(/[\u4e00-\u9fa5]/g, '');
			},
			
			// 移除末尾的中文字（包括汉字、符号、标点）
			removeTrailingChinese: function(text) {
				// 移除末尾的中文字符，包括汉字、中文标点、全角符号
				return text.replace(/[\u4e00-\u9fa5\u3000-\u303f\uff00-\uffef\s]*$/, '').trim();
			},
			
			// 判断字符串是否像账号
			looksLikeAccount: function(str) {
				// 账号通常是手机号（11位数字）或邮箱或用户名
				if (/^\d{11}$/.test(str)) return true; // 11位手机号
				if (/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(str)) return true; // 邮箱
				if (/^[a-zA-Z0-9_-]{3,20}$/.test(str)) return true; // 用户名格式
				return false;
			},
			
			// 判断字符串是否像密码
			looksLikePassword: function(str) {
				// 密码通常包含字母数字或特殊字符，长度3-50
				if (str.length < 3 || str.length > 50) return false;
				return /[a-zA-Z0-9]/.test(str); // 至少包含字母或数字
			},
			
			// 将中文符号替换为对应的英文符号
			replaceChineseSymbolsToEnglish: function(password) {
				const fullWidthToHalfWidth = {
					'！': '!', '？': '?', '：': ':', '；': ';', '，': ',', '。': '.', 
					'（': '(', '）': ')', '【': '[', '】': ']', '《': '<', '》': '>',
					'￥': '$', '￡': '£', '＠': '@', '＃': '#', '％': '%', '＆': '&',
					'＊': '*', '＋': '+', '－': '-', '＝': '=', '＿': '_', '｜': '|',
					'～': '~', '｀': '`', '＾': '^', '＼': '\\', '／': '/', '｛': '{', '｝': '}',
					'｜': '|', '＂': '"', '＇': "'", '　': ' ', '．': '.', '，': ','
				};
				
				let result = password;
				
				// 只替换中文符号，保留中文汉字
				for (let chineseChar in fullWidthToHalfWidth) {
					result = result.replace(new RegExp(chineseChar.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), fullWidthToHalfWidth[chineseChar]);
				}
				
				return result;
			},
			
			// 统一更新用户输入中的密码
			updatePasswordInUserinfo: function(originalPassword, newPassword) {
				if (this.isBatchMode) {
					// 批量模式：替换当前行的密码
					this.userinfo = this.userinfo.replace(originalPassword, newPassword);
				} else {
					// 单行模式：重新执行矫正
					const correctedText = this.userinfo.replace(originalPassword, newPassword);
					this.userinfo = correctedText;
				}
			},
			
			// 检查文本是否包含关键词
			containsKeywords: function(text, keywords) {
				const lowerText = text.toLowerCase();
				return keywords.some(keyword => lowerText.includes(keyword.toLowerCase()));
			},
			
			// 提取学校名称
			extractSchoolName: function(text) {
				// 常见学校名称模式
				const schoolPatterns = [
					/([^\d\s]{2,10}?(?:大学|学院|学校|高校|职院|技校))/,
					/([^\d\s]{2,6}?(?:大|学院))/
				];
				
				for (let pattern of schoolPatterns) {
					const match = text.match(pattern);
					if (match) {
						return match[1];
					}
				}
				
				return '';
			}
		},
		
	mounted(){
		// 移动到created中了
	}
	
	
});
</script>