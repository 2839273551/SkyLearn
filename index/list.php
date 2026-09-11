<?php
$mod='blank';
$title='订单列表';
require_once('head.php');
?>

 <!--//解决ios点击两次问题 -->
<style lang="scss">
 .el-scrollbar .el-scrollbar__bar {
    opacity: 1 !important;
}
</style>
<!--解决超出屏幕问题 -->
<style>
.long-text-option {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  width: 100%;
  max-width: 100%;
}

.custom-dropdown {
  max-width: 100vw;
  width: auto !important;
  right: 0; 

  
}


.blue-text {
  color: blue;
}



</style>




<style>
[v-cloak] {
  display: none;
}
</style>
<style>

        .console-link-block {
            font-size: 16px;
            padding: 20px 20px;
            border-radius: 4px;
            background-color: #40D4B0;
            color: #FFFFFF !important;
            box-shadow: 0 2px 3px rgba(0, 0, 0, .05);
            position: relative;
            overflow: hidden;
            display: block;
            min-height: 80px;
        }

        .console-link-block .console-link-block-num {
            font-size: 40px;
            margin-bottom: 5px;
            opacity: .9;
        }

        .console-link-block .console-link-block-text {
            opacity: .8;
        }

        .console-link-block .console-link-block-icon {
            position: absolute;
            top: 50%;
            right: 20px;
            width: 50px;
            height: 50px;
            font-size: 50px;
            line-height: 50px;
            margin-top: -25px;
            color: #FFFFFF;
            opacity: .8;
        }

        .console-link-block .console-link-block-band {
            color: #fff;
            width: 100px;
            font-size: 12px;
            padding: 2px 0 3px 0;
            background-color: #E32A16;
            line-height: inherit;
            text-align: center;
            position: absolute;
            top: 8px;
            right: -30px;
            transform-origin: center;
            transform: rotate(45deg) scale(.8);
            opacity: .95;
            z-index: 2;
        }

       
        .layui-row > div:nth-child(2) .console-link-block {
            background-color: #55A5EA;
        }

        .layui-row > div:nth-child(3) .console-link-block {
            background-color: #9DAFFF;
        }

        .layui-row > div:nth-child(4) .console-link-block {
            background-color: #F591A2;
        }

        .layui-row > div:nth-child(5) .console-link-block {
            background-color: #FEAA4F;
        }

        .layui-row > div:last-child .console-link-block {
            background-color: #9BC539;
        }
        
 
        .console-app-group {
            padding: 16px;
            border-radius: 4px;
            text-align: center;
            background-color: #fff;
            cursor: pointer;
            display: block;
        }

        .console-app-group .console-app-icon {
            width: 32px;
            height: 32px;
            line-height: 32px;
            margin-bottom: 6px;
            display: inline-block;
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            box-sizing: border-box;
            font-size: 32px;
            color: #69c0ff;
        }

        .console-app-group:hover {
            box-shadow: 0 0 15px rgba(0, 0, 0, .08);
        }
</style>


<script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>
<link rel="stylesheet" href="assets/css/element.css">
<link rel="stylesheet" href="css/list.css" media="all">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" media="all">
<!--<link rel="stylesheet" href="http://cdn.bootcss.com/font-awesome/4.6.3/css/font-awesome.min.css" media="all">-->
<link href="assets/LightYear/css/bootstrap.min.css" rel="stylesheet">
<div class="app-content-body ">
        <div class="wrapper-md control">
	    <div class="panel panel-default" id="orderlist">
		    <div class="panel-heading font-bold bg-white">任务列表 (状态同步不及时，有时需手动更新)</div>
				 <div class="panel-body">
					<div class="form-horizontal devform" style="margin-left:10px">
						<div class="form-group">
						    
						   <div class="layui-row layui-col-space10">				
						    <div class="layui-col-md2 layui-col-sm3 layui-col-xs6" v-cloak>	
				 		   				<el-select id="select" v-model="cx.cid" filterable placeholder="请选择平台" style="background: url('../user/arrow.png') no-repeat scroll 99%;width:100%">
				 		   				    <el-option label="请选择平台" value=""></el-option>
								<?php
		                	     	$a=$DB->query("select * from qingka_wangke_class where status=1 ");
								    while($row=$DB->fetch($a)){
				                       echo '<el-option label="'.$row['name'].'" value="'.$row['cid'].'">'.$row['name'].'</el-option>';
								    }?>
							</el-select>
							  </div>  
							  <div class="layui-col-md2 layui-col-sm3 layui-col-xs6">	
							<el-select id="select" v-model="cx.status_text" filterable placeholder="请选择状态" style="background: url('../user/arrow.png') no-repeat scroll 99%;width:100%">
						
								            <el-option label="请选择状态" value=""></el-option>
				 		   				    <el-option label="待处理" value="待处理"></el-option>
				 		   				    <el-option label="进行中" value="进行中"></el-option>
				 		   				    <el-option label="已完成" value="已完成"></el-option>
				 		   				    <el-option label="补刷中" value="补刷中"></el-option>
				 		   				    <el-option label="已取消" value="已取消"></el-option>
				 		   				    <el-option label="已退款" value="已退款"></el-option>
				 		   				    <el-option label="已暂停" value="已暂停"></el-option>
				 		   				    <el-option label="考试中" value="考试中"></el-option>
				 		   				    <el-option label="待考试" value="待考试"></el-option>
				 		   				    <el-option label="异常中" value="异常"></el-option>
							</el-select>
							</div>  
							
							<div class="layui-col-md2 layui-col-sm3 layui-col-xs6">	
				 		   	<el-select id="select" v-model="cx.limit" filterable placeholder="选择每页订单数量" style="background: url('../user/arrow.png') no-repeat scroll 99%;width:100%">
				 		   				    <el-option label="选择每页订单数量" value=""></el-option>
				 		   				    <el-option label="20/页" value="20"></el-option>
				 		   				    <el-option label="50/页" value="50"></el-option>
				 		   				    <el-option label="100/页" value="100"></el-option>
				 		   				    <el-option label="200/页" value="200"></el-option>
				 		   				    <el-option label="500/页" value="500"></el-option>
				 		   				    <el-option label="1000/页" value="1000"></el-option>
				 		   				</el-select>	 
				                    </div> 
				                    
				                    <div class="layui-col-md2 layui-col-sm3 layui-col-xs6">
								<el-select id="select" v-model="dc2.gs" filterable placeholder="选择导出格式"
									style="background: url('../user/arrow.png') no-repeat scroll 99%;width:100%">
									<el-option label="选择导出格式" value=""></el-option>
									<el-option label="学校+账号+密码+课程名字" value="1"></el-option>
									<el-option label="账号+密码+课程名字" value="2"></el-option>
									<el-option label="学校+账号+密码" value="3"></el-option>
									<el-option label="账号+密码" value="4"></el-option>
								</el-select>
							</div>
							
								
							
							
							
							
							
							
							<div class="layui-col-md2 layui-col-sm3 layui-col-xs6" style="width: 160px;" v-if="row.uid==1"> 		          
					       <el-select id="select" v-model="cx.dock" filterable placeholder="处理状态" style="background: url('../user/arrow.png') no-repeat scroll 99%;width:100%">
					                        <el-option label="处理状态" value=""></el-option>
				 		   				    <el-option label="待处理" value="0"></el-option>
				 		   				    <el-option label="处理成功" value="1"></el-option>
				 		   				    <el-option label="处理失败" value="2"></el-option>
				 		   				    <el-option label="重复下单" value="3"></el-option>
				 		   				    <el-option label="已取消" value="4"></el-option>
				 		   				    <el-option label="我的" value="99"></el-option>
					              </el-select>  
				              </div>
							
							
							
						
                        
							
							
							
							
							
							
				                    
				  <!--                  <div class="layui-col-md2 layui-col-sm3 layui-col-xs6">-->
						<!--		<input type="text" v-model="cx.status_text" class="layui-input" placeholder="自定义状态查询" />-->
						<!--	</div>-->
				                    
				  <!--                   <div class="layui-col-md2 layui-col-sm3 layui-col-xs6" v-if="row.uid==1">-->
				 	<!--                  <input type="text"  v-model="cx.uid" value="" class="layui-input"  placeholder="请输入UID" required/> </div></div>-->
				 	<!--<div class="form-horizontal devform">	-->
				 	<!--          <div class="layui-row layui-col-space10">-->
				 	<!--              <div class="layui-col-md2 layui-col-sm3 layui-col-xs6">-->
				 	<!--                  <input type="text"  v-model="cx.oid" value="" class="layui-input"  placeholder="请输入订单ID" required/> </div>-->
				 	<!--              <div class="layui-col-md2 layui-col-sm3 layui-col-xs6">-->
				 	<!--                  <input type="text"  v-model="cx.qq" value="" class="layui-input"  placeholder="请输入下单账号" required/>-->
				 	<!--              </div>-->
				 	<!--              <div class="layui-col-md2 layui-col-sm3 layui-col-xs6">-->
				 	<!--                  <input type="text"  v-model="cx.kcname" value="" class="layui-input"  placeholder="请输入课程名关键字" required/></div>-->
				 	<!--                  <div class="layui-col-md2 layui-col-sm3 layui-col-xs6">-->
		    <!--             <input type="text"  v-model="cx.remarks" value="" class="layui-input"  placeholder="请输入日志关键词" required/>-->
			   <!--           </div>-->
			              
			   <!--                     <div class="layui-col-md2 layui-col-sm3 layui-col-xs6">-->
		    <!--             <input type="text"  v-model="cx.school" value="" class="layui-input"  placeholder="请输入学校关键词" required/>-->
			   <!--           </div>-->
				 	<!--                   <div class="layui-col-md2 layui-col-sm3 layui-col-xs6">-->
		    <!--             <input type="text"  v-model="cx.ptname" value="" class="layui-input"  placeholder="请输入渠道名称" required/>-->
			   <!--           </div>-->
				 	<!--               <div class="layui-col-md2 layui-col-sm3 layui-col-xs6">-->
		    <!--             <input type="text"  v-model="cx.pass" value="" class="layui-input"  placeholder="请输入学生密码" required/> </div>-->
		                 
		                 
		                <div class="form-group">
                                <div class="layui-row">
                                    <div class="col-sm-1 col-xs-4" style="width: 100%;">
                                        <el-input placeholder="模糊查询" v-model="cx.mh" class="input-with-select">
                                            <el-select v-model="cx.search" style="width:100px" placeholder="条件" slot="prepend">
                                                <el-option label="所有" value=""></el-option>
                                                <el-option label="UID" value="uid" v-if="row.uid === 1"></el-option>
                                                <el-option label="订单ID" value="oid" v-if="row.uid === 1"></el-option>
                                                <el-option label="学校" value="school"></el-option>
                                                <el-option label="账号" value="user"></el-option>
                                                <el-option label="密码" value="pass"></el-option>
                                                <el-option label="渠道名称" value="ptname"></el-option>
                                                <el-option label="课程名称" value="kcname"></el-option>
                                                <el-option label="进度条" value="process"></el-option>
                                                <el-option label="详细进度" value="remarks"></el-option>
                                                
                                      
                                     </el-select>  
                                     </el-input>
                                      <div style="height:5px"></div>
                                </div>
                            </div>
		                 </div>
		                 
		                 
		                 
		                 
		                 
		                 
				 	               
				 	              <div class="layui-col-md2 layui-col-sm3 layui-col-xs6" >
			              <input type="submit"value=" 查询" @click="get(1)" class="layui-btn"/>	
			              <input type="submit" value="导出" @click="showExportDialog"  class="layui-btn"/>
			             </div></div>
			             </div>	
			          </div>
				
						<?php if($userrow['uid']==1){ ?>
						<div class="form-group"><br/>任务状态
    						<a class="el-button el-button--warning   is-plain el-button--mini"
    							style="padding: 4px 10px;" @click="status_text('待处理')">1_待处理</a>
    						<a class="el-button el-button--success   is-plain el-button--mini"
    							style="padding: 4px 10px;" @click="status_text('已完成')">1_已完成</a>
    						<a class="el-button el-button--primary is-plain el-button--mini"
    							style="padding: 4px 10px;" @click="status_text('进行中')">1_进行中</a>
    						<a class="el-button el-button--danger  is-plain el-button--mini"
    							style="padding: 4px 10px;" @click="status_text('异常')">1_异常</a>
    						<a class="el-button el-button--default is-plain el-button--mini"
    							style="padding: 4px 10px;" @click="status_text('已取消')">1_已取消</a>
    							
    						<a class="el-button el-button--success   is-plain el-button--mini" 
    					     	style="padding: 4px 10px;" @click="status_text('已退款')">已退款</a>
    					     	
    					     	<a class="el-button el-button--purple   is-plain el-button--mini " @click="status_text('已录单进度联系客服')">手工录单</a>
							<a class="el-button el-button--default is-plain el-button--mini" @click="status_text('密码错误异常')">密错</a>
							<a class="el-button el-button--default is-plain el-button--mini" @click="status_text('任务完成|祝您前程似锦|壮志凌云')">自定义</a>
    					     	
    					     	
    					     	
    					     	
    						
    				    	<span style="margin-left:40px"><br/><br/>处理状态
    						<a class="el-button el-button--warning   is-plain el-button--mini"
    							style="padding: 4px 10px;" @click="dock(0)">2_待处理</a>
    						<a class="el-button el-button--success   is-plain el-button--mini"
    							style="padding: 4px 10px;" @click="dock(1)">2_已完成</a>
    						<a class="el-button el-button--danger  is-plain el-button--mini"
    							style="padding: 4px 10px;" @click="dock(2)">2_处理失败</a>
    						<a class="el-button el-button--default is-plain el-button--mini"
    							style="padding: 4px 10px;" @click="dock(3)">2_重复下单</a>
    						<a class="el-button el-button--default is-plain el-button--mini"
    							style="padding: 4px 10px;" @click="dock(4)">2_取消</a>
    						<a class="el-button el-button--default is-plain el-button--mini"
    							style="padding: 4px 10px;" @click="dock(99)">2_我的</a>
    							
    						<a class="el-button el-button--warning   is-plain el-button--mini"
    							style="padding: 4px 10px;" @click="tk(sex)">退款</a>
    				    
    					
    		
    					
        				</span>
						</div>
						<?php } ?>
					</div>
					
					
					
					  
					
					
					
					
					
                  	<div class="bg-gradient-tron">
                            <!--<a class="btn btn-xs btn btn-primary purple" id="checkboxAll" @click="selectAll()">全选</a>-->
                            批量操作:<br/>
                            <a class="btn btn-xs btn-info purple" @click="plzt(sex)">同步状态入队</a>&nbsp;&nbsp;
                            <a class="btn btn-xs btn-success purple" @click="plbs(sex)">补刷订单入队</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;/&nbsp;&nbsp;&nbsp;
                            <a class="btn btn-xs btn-danger" @click="del(sex)">删除订单</a>
                            <a class="btn btn-xs btn-info" href=http:// target="_blank" >单独查单</a>
                            <a class="btn btn-xs btn-danger" @click="zztk(sex)">自助退款</a>
                            <br/>
                            <span>注：批量同步禁止频繁点导致服务器堵塞罚款10RMB</span>
							<br />
							<span>注：批量补刷使用后 系统每5分钟自动提交补刷谢谢</span>
							<br />
							<span>注：删除订单功能，单纯删除本台订单，源头没删哈</span>
							<br />
							<span><span style="color:red;">注：系统问题，之前下过的同账号课程的进度，请用单独查单查询</span></span>
							<br />
							<span><span><span style="color:#4C33E5;"><strong>温馨提示：平台订单15-30分钟自动同步</strong></span><span style="color:#4C33E5;"><strong>1次 订单较多同步较慢 动动手手动同步一下谢谢配合 部分订单请手动同步</strong></span></span></span><span style="color:#4C33E5;"><span><span><span><span><strong></strong></span></span></span></span></span>
						
                  </div> 
                  <div><span style="color:#FF7F00;">本页面总共：{{ orderCount }}条数据</div>
                  	
		      <div class="layui-table table-responsive" lay-size="sm" >
		        <table class="table table-striped">
		          <thead><tr><th ><input type="checkbox" id="checkboxAll" @click="selectAll()" /></th>
	
		     
		    <th style="text-align: center ;width:120px"><b>操作</b></th>
		     
		           <th style="text-align: center;"><b>【订单ID】</b></th>
		     <th style="text-align: center;"><b>【渠道】</b></th>
		     <th style="text-align: center;"><b>【学校&nbsp;账号&nbsp;密码】</b></th>
		     <th style="text-align: center;width:10%"><b>【项目名】</b></th>
		  
		   
		     <th nowrap="nowrap" style="text-align: center;"><b>状态</b></th>
		
		    <th style="text-align: center;"><b>进度</b></th>
		   
		    <th style="text-align: center;"><b><span style="color:#4C33E5;">《日志》</b></th>
		    
		     <th style="text-align: center;"><b>提交时间</th>
		     <th v-if="row.uid==1">处理状态</th>
		     <th v-if="row.uid==1">UID</th>
		       <th style="text-align: center;"><b>¥</b></th>
		 
		     
		     
		     
		     
		     
		     
		     
		     
		     
		     </thead>
		          <tbody>
		            <tr v-for="res in row.data">		            					
								  <td  > 
								  	<span class=" ">
			                            <input type="checkbox" id="checkboxAll" :value="res.oid" v-model="sex"><label for="checkbox1"></label>
			                        </span>
								  </td>
								  <div class="center">
								      
								      
								      
								      
								      
								      
								      
								      
<td style="text-align: center;">
    <el-dropdown trigger="click" popper-class="center-dropdown">
        <el-button type="primary" size="mini" style="border-radius: 4px;">
            操作<i class="el-icon-arrow-down el-icon--right"></i>
        </el-button>
        <el-dropdown-menu slot="dropdown">
            <el-dropdown-item><span @click="ddinfo(res)"><i class="el-icon-search"></i>订单详细</span></el-dropdown-item>
            <el-dropdown-item><span @click="up(res.oid)"><i class="el-icon-refresh-right"></i>同步进度</span></el-dropdown-item>
            <el-dropdown-item><span @click="bs(res.oid)"><i class="el-icon-s-promotion"></i>补刷订单</span></el-dropdown-item>
            <el-dropdown-item><span @click="log(res.oid)" v-if="res.hid === '4'||res.hid === '103'||res.hid === '104'||res.hid === '70'"><i class="el-icon-s-order"></i>学习日志</span></el-dropdown-item>
            <el-dropdown-item><span @click="xgmm(res.oid)" v-if="res.hid === '4'||res.hid === '103'||res.hid === '104'||res.hid === '70'"><i class="el-icon-warning"></i>修改密码</span></el-dropdown-item>
            <el-dropdown-item><span @click="feedback(res.oid)"><i class="mdi mdi-comment-processing"></i>问题反馈</span></el-dropdown-item>
        </el-dropdown-menu>
    </el-dropdown>
</td>

<style>
.center-dropdown {
    left: 50% !important;
    transform: translateX(-50%) !important;
}
</style>
										
								      
								      
								      
								      
								      
								      
								      
								      
								      
								  
		           	<td  style="text-align: center;">{{res.oid}}</td>	
		            	
		           <td><center><span style="color:#333333;"><strong>{{res.ptname}}</strong><span v-if="res.miaoshua=='1'"
											style="color: red;">秒刷</span></center></td>         	      	
		            <td><div style="text-align:center;"><strong>{{res.school}}
											{{res.user}}
											{{res.pass}}</strong></div>
									</td>
		      
									<td><center><span style="color:#333333;"><strong>{{res.kcname}}</strong></span>
									
							
		            	
		            				<td>
                        <el-button style="color: green; border: 1px solid green;" @click="up(res.oid)" size="mini" v-if="res.status=='已完成'">{{res.status}}</el-button>
                        <el-button style="color: blue; border: 1px solid blue;" @click="up(res.oid)" size="mini" v-else-if="res.status=='待处理'">{{res.status}}</el-button>
                        <el-button style="color: red; border: 1px solid red;" @click="up(res.oid)" size="mini" v-else-if="res.status=='异常'">{{res.status}}</el-button>
                        <el-button style="color: orange; border: 1px solid orange;" @click="up(res.oid)" size="mini" v-else-if="res.status=='进行中'">{{res.status}}</el-button>
                        <el-button style="color: purple; border: 1px solid purple;" @click="up(res.oid)" size="mini" v-else>{{res.status}}</el-button>
	            	</td>
		            	
		            
		            	   
		            	    
	
		            	
		          

                                 
                                 
                                 
                                 
                                <!--蛇-->
                                 
                                 
                        
                                 
                                 
    <td  style="text-align: center; padding: 5; vertical-align: middle;">
    <el-progress
        v-if="!isNaN(parseFloat(res.process || 0))"
        type="circle"
        :percentage="parseFloat(res.process || 0)"
        width="60"
        :color="parseFloat(res.process || 0) < 10 ? '#FFC0CB' : 
                 parseFloat(res.process || 0) < 20 ? '#FF69B4' : 
                 parseFloat(res.process || 0) < 30 ? '#FF1493' : 
                 parseFloat(res.process || 0) < 40 ? '#DB7093' : 
                 parseFloat(res.process || 0) < 50 ? '#C71585' : 
                 parseFloat(res.process || 0) < 60 ? '#FF6347' : 
                 parseFloat(res.process || 0) < 70 ? '#FF4500' : 
                 parseFloat(res.process || 0) < 80 ? '#FF8C00' : 
                 parseFloat(res.process || 0) < 90 ? '#FFD700' : 
                 '#90EE90'">
    </el-progress>
    
    <div v-else>{{ res.process }}</div>
</td>


		            <td>
		            <div v-if="res.cid=='508'">
        <a :href="'https://exam.hm86.icu//score.php?username=' + res.user" target="_blank" class="btn btn-xs btn-info">查看证书</a>
        <a :href="'https://exam.hm86.icu//cert.php?username=' + res.user" target="_blank" class="btn btn-xs btn-info">截图分数</a>
    </div>
		            	
		            	<div v-else>
       <span style="color: #000080; font-weight: bold;">{{res.remarks}}
    </div>
	</td>	            	
		            	
		            

		            	<td><span style="color:#E53333;">{{res.addtime}}</td>
		            	
		            	
		            	
		            	
		            	
		            	
		            	
		            
		            	
		            <td nowrap="nowrap" v-if="row.uid==1" style="text-align: center;">
		            		<span @click="duijie(res.oid)" v-if="res.dockstatus==0" class="btn btn-xs btn-info">待处理</span>
		            		<span v-if="res.dockstatus==1" class="btn btn-xs btn-success">处理成功</span>
		            		<span @click="duijie(res.oid)" v-if="res.dockstatus==2" class="btn btn-xs btn-danger">处理失败</span>
		            		<span v-if="res.dockstatus==3" class="">重复下单</span>
		            		<span v-if="res.dockstatus==4" class="">已取消</span>
		            		<span v-if="res.dockstatus==99" class="btn btn-xs btn-warning">自营</span></td>
		            	
		            		<td v-if="row.uid==1" style="text-align: center;">{{res.uid}}</td>
		            			<td style="text-align: center;">{{res.fees}}</td>
		            			
		            			

		            </tr>
                        </div>
		          </tbody>
		        </table>
		      </div>
		      
			     <ul class="pagination pagination-circle" v-if="row.last_page>1"> 
			         <li class="disabled"><a @click="get(1)">首页</a></li>
			         <li class="disabled"><a @click="row.current_page>1?get(row.current_page-1):''">&laquo;</a></li>
		            <li  @click="get(row.current_page-3)" v-if="row.current_page-3>=1"><a>{{ row.current_page-3 }}</a></li>
						    <li  @click="get(row.current_page-2)" v-if="row.current_page-2>=1"><a>{{ row.current_page-2 }}</a></li>
						    <li  @click="get(row.current_page-1)" v-if="row.current_page-1>=1"><a>{{ row.current_page-1 }}</a></li>
						    <li :class="{'active':row.current_page==row.current_page}" @click="get(row.current_page)" v-if="row.current_page"><a>{{ row.current_page }}</a></li>
						    <li  @click="get(row.current_page+1)" v-if="row.current_page+1<=row.last_page"><a>{{ row.current_page+1 }}</a></li>
						    <li  @click="get(row.current_page+2)" v-if="row.current_page+2<=row.last_page"><a>{{ row.current_page+2 }}</a></li>
						    <li  @click="get(row.current_page+3)" v-if="row.current_page+3<=row.last_page"><a>{{ row.current_page+3 }}</a></li>		       			     
			         <li class="disabled"><a @click="row.last_page>row.current_page?get(row.current_page+1):''">&raquo;</a></li>
			         <li class="disabled"><a @click="get(row.last_page)">尾页</a></li>	    
			     </ul> 
			     
   

		  
		  
			    <div id="ddinfo2" style="display: none;"><!--订单详情-->                    
			       <li class="list-group-item">
			       	<b>课程类型：</b>{{ddinfo3.info.ptname}}<span v-if="ddinfo3.info.miaoshua=='1'" style="color: red;">&nbsp;秒刷</span></li>
			       	<li class="list-group-item" style="word-break:break-all;"><b>账号信息：</b>{{ddinfo3.info.school}}&nbsp;{{ddinfo3.info.user}}&nbsp;{{ddinfo3.info.pass}}</li>
			       	<li class="list-group-item"><b>课程名字：</b>{{ddinfo3.info.kcname}}</li>
			       	<li class="list-group-item" v-if="ddinfo3.info.name!='null'"><b>学生姓名：</b>{{ddinfo3.info.name}}</li>
			       	<li class="list-group-item"><b>下单时间：</b>{{ddinfo3.info.addtime}}</li>
			       	<li class="list-group-item" v-if="ddinfo3.info.finalupdate"> <b>最近更新：</b><span style="color: red;">{{ddinfo3.info.finalupdate}}</span></li>
			       	<li class="list-group-item" v-if="ddinfo3.info.courseStartTime"><b>课程开始时间：</b>{{ddinfo3.info.courseStartTime}}</li>
			       	<li class="list-group-item" v-if="ddinfo3.info.courseEndTime"><b>课程结束时间：</b>{{ddinfo3.info.courseEndTime}}</li>
			       	<li class="list-group-item" v-if="ddinfo3.info.examStartTime"><b>考试开始时间：</b>{{ddinfo3.info.examStartTime}}</li>
			       	<li class="list-group-item" v-if="ddinfo3.info.examEndTime"><b>考试结束时间：</b>{{ddinfo3.info.examEndTime}}</li>
			       	<li class="list-group-item"><b>订单状态：</b><span style="color: red;">{{ddinfo3.info.status}}</span>&nbsp;<button v-if="ddinfo3.info.dockstatus!='99'" @click="up(ddinfo3.info.oid)" class="btn btn-xs btn-success">更新</button>&nbsp;
			       	<button  v-if="ddinfo3.info.hid === '4' || ddinfo3.info.hid === '103' || ddinfo3.info.hid === '104' || ddinfo3.info.hid === '70'"  @click="zt(ddinfo3.info.oid)" class="btn btn-xs btn-purple"><li class="icon icon-refresh"></li>暂停</button>
			       	</li>
			       	<li class="list-group-item"><b>进度：</b>{{ddinfo3.info.process}}</li>
			       	<li class="list-group-item"v-if="ddinfo3.info.remarks"><b>备注：</b>{{ddinfo3.info.remarks}}</li>
			       	<li class="list-group-item" v-if="ddinfo3.info.kcid" ><b>kcid：</b>{{ddinfo3.info.kcid}}</li>
			       	<li class="list-group-item"><b>下单金额：</b>{{ddinfo3.info.fees}}</li>
			       	<li class="list-group-item" v-if="ddinfo3.info.status!='已取消'"><b>操作：</b>
			       	<button   v-if="ddinfo3.info.hid === '4' || ddinfo3.info.hid === '103' || ddinfo3.info.hid === '104' || ddinfo3.info.hid === '70'"        @click="ms(ddinfo3.info.oid)" class="btn btn-xs btn-warning "><li class="icon icon-refresh"></li>秒刷</button>&nbsp;
			       	
			       	<!--<button v-if="true" @click="layer.msg('更新中，近期开放')" class="btn btn-xs btn-info">修改密码</button>&nbsp;-->
			      
			       	<button  v-if="ddinfo3.info.hid === '4' || ddinfo3.info.hid === '103' || ddinfo3.info.hid === '104' || ddinfo3.info.hid === '70'"   @click="xgmm(ddinfo3.info.oid)" class="btn btn-xs btn-info">修改密码</button>&nbsp; 
			       	<button  v-if="ddinfo3.info.hid === '4' || ddinfo3.info.hid === '103' || ddinfo3.info.hid === '104' || ddinfo3.info.hid === '70'"           @click="bs(ddinfo3.info.oid)" class="btn btn-xs btn-primary">补刷</button>&nbsp;
			       <button  v-if="ddinfo3.info.hid === '4' || ddinfo3.info.hid === '103' || ddinfo3.info.hid === '104' || ddinfo3.info.hid === '70'"  @click="feedback(ddinfo3.info.oid)" class="layui-btn layui-btn-xs  layui-btn-success">反馈</button>&nbsp;
			       		<button  v-if="ddinfo3.info.hid === '4' || ddinfo3.info.hid === '103' || ddinfo3.info.hid === '104' || ddinfo3.info.hid === '70'"  @click="log(ddinfo3.info.oid)" class="btn btn-xs btn-primary">日志</button>&nbsp;
			       	
			       
			       	
			       	
			       	
			       	
			       	<button  @click="quxiao(ddinfo3.info.oid)"  class="btn btn-xs btn btn-info">取消</button></li>	       	  
		      </div>
		      
		    </div>
		  </div>
		  
		  

   
		  
  </div>
   </div>
    
 </div>


<script type="text/javascript" src="assets/LightYear/js/jquery.min.js"></script>
<script type="text/javascript" src="assets/LightYear/js/bootstrap.min.js"></script>
<script type="text/javascript" src="assets/LightYear/js/perfect-scrollbar.min.js"></script>
<script type="text/javascript" src="assets/LightYear/js/main.min.js"></script>
<script src="assets/js/aes.js"></script>
<script src="assets/cdn/axios.min.js"></script>
<script src="assets/js/vue.min.js"></script>
<script src="assets/js/vue-resource.min.js"></script>
<script src="assets/js/element.js"></script>
<script >
    
    vm=new Vue({
	el:"#orderlist",
	data:{
		  row:null,
	
		  phone:'',
		 row_logwk: {},
		  sex:[],
		  ddinfo3:{
		  	status:false,
		  	info:[]
		  },
		  dc:[],
		 dc2: {
                gs: ''
            },
            
            logs: [],
    logsPagination: {
        current_page: 0,
        last_page: 0,
        total: 0
    },
      logDialogVisible: false,
            
		  cx:{
		  	status_text:'',
		  	dock:'',
		  	qq:'',
		  	oid:'',
		  	uid:'',
		  	mh: '',
		  	search: '',
		  	school:'',
		  	kcname:'',
		  	ptname:'',
		  	pass:'',
		   	remarks:'',
		  
		  	cid:'',
		  	limit:''
                },
           orderCount: '',
            },methods:{
                 
                getOrderList(status) {
                this.cx.status_text = status;
                this.get(1);
            },
                
	   removepercent:function(text){
		    function isNumeric(value) {
  return !isNaN(parseFloat(value)) && isFinite(value) && typeof value !== 'boolean';
}
		    if (isNumeric(text.split('%').join(""))){
		        return text.split('%').join("");
		    }
		    return false;
		},download:function(savepath){
		  //   console.log("e ==> ",e);
		  //   return;
		     if(!savepath){
		     	layer.confirm('当前订单正在生成中，请稍侯在试！', {title:'温馨提示',icon:5,
							  btn: ['确定','取消'] //按钮
							}, function(){
							  vm.get(vm.row.current_page);  
							 //  layer.alert();
							 //layer.close();
							 layer.closeAll('dialog');

                });
		     }else{
		        layer.alert('外部文件下载已禁用，请联系管理员获取文件。', {
		            title: '温馨提示',
		            icon: 5
		        });
		     }
		 },


feedback: function(oid) {
    var self = this;
    layer.prompt({
        title: '请用简短的一句话描述问题，只需要输入问题！',
        formType: 2,
        placeholder: '请输入问题描述...'
    }, function(feedbackText, index) {
        layer.close(index);
        feedbackText = feedbackText.trim();

        if (feedbackText === '') {
            layer.msg('反馈内容不能为空', {icon: 2});
            return;
        }
        if (/\d|[a-zA-Z]/.test(feedbackText)) {
            layer.msg('反馈内容不能包含数字和字母', {icon: 2});
            return;
        }

        var load = layer.load();
        $.get("/gd.php?act=feedback&oid=" + oid, { feedback: feedbackText }, function(data) {
            layer.close(load);
            if (data.code === 1) {
                layer.msg('反馈成功，请在我的反馈中查看', {icon: 1});
            } else {
                layer.msg('反馈失败: ' + data.msg, {icon: 2});
            }
        });
    });
},





// tz: function(yid) {
//     layer.confirm('确定要停止任务吗？<br>点完以后刷新，看是否停止', {
//         title: '提示',
//         btn: ['确定', '取消']
//     }, function () {
//         var load = layer.load();
//         layer.msg("正在停止中....", { icon: 3 });
//         $.get("/apitz.php?act=tz&yid="+yid, function (data) {
//             layer.close(load);
//             if (data.code == 1) {
//                 layer.msg(data.msg, { icon: 1 });
//             } else {
//                 layer.msg(data.msg, { icon: 2 });
//             }
//         });
//     });
// },


	zt:function(oid){
				var load=layer.load();
				layer.msg("正在暂停中....",{icon:3});
          $.get("/apisub.php?act=zt&oid="+oid,function (data) {
		 	     layer.close(load);
	             if (data.code==1){
	             	  vm.get(vm.row.current_page);  
	             	  setTimeout(function() {
	             	  	for(i=0;i<vm.row.data.length;i++){           	
					            	 if(vm.row.data[i].oid==oid){
					            	 	  vm.ddinfo3.info=vm.row.data[i];
					            	 	  console.log(vm.row.data[i].oid);
					            	 	  console.log(vm.row.data[i].status);
					            	 	  console.log(vm.ddinfo3.info.status);
					            	 	  return true;
					            	 } 
					            } 
	             	  },1800);   	             	  		             	 
	                layer.msg(data.msg,{icon:1});	                               
	             }else{
	              	layer.msg(data.msg,{icon:2});	
	             }	              
         });			
		 },

	
	
	xgmm:function (oid) {
    layer.prompt(
        { title: "修改密码", formType: 3 },
        function (xgmm, index) {
            layer.close(index);
            var load = layer.load();
            $.get("/apisub.php?act=xgmm&oid="+oid, { xgmm }, function (data) {
                layer.close(load);
                if (data.code == 1) {
                     
    						vm.get(vm.row.current_page);
    						layer.msg(data.msg, {icon: 1});
                } else {
                    layer.msg(data.msg, { icon: 2 });
                }
            });
        }
    );
},

	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
		get:function(page){
		  var load=layer.load();
		  data={cx:this.cx,page}
 			this.$http.post("/apisub.php?act=orderlist",data,{emulateJSON:true}).then(function(data){	
	          	layer.close(load);
	          	if(data.data.code==1){			                     	
	          		this.row=data.body;
	          		this.orderCount = data.body.data.length
	          	}else{
	                layer.msg(data.data.msg,{icon:2});
	          	}
	        });	
		},

		
		
		
		
		TgTips:function(){
	layer.alert('1.可以点编辑目录推荐目录的基础上修改或者新增 [<span style="color:red;">章节/小节标题以及字数</span>] <br>2.成为目标目录，系统将通过目标目录为你撰写文章，注意格式 <br>3.可以自己稍微修改格式也可以让客户自己改<span style="color:red;">模板定制联系上级</span> <br>4.能过98%论文网址查重 <br><span style="color:red;">5.欢迎尝试，显示完成即可点保存下载！</span> <br><span style="color:red;"><strong>6.因编辑目录存在修改性，</strong><strong>已完成后重刷会失效！</strong></span> <br><span style="color:red;">7.如果显示失败点重刷，重新编辑即可，</span><span ><br>一直失败请反馈上级！</span>', {
	
		title: '论文订单教程规则',
		skin: 'layui-layer-molv layui-layer-wxd'
		  , shadeClose: true
	});	
		},lunw:function(){
	layer.alert('自动发货到邮箱本地不保存', {
	
		title: '论文订单教程规则',
		skin: 'layui-layer-molv layui-layer-wxd'
		  , shadeClose: true
	});	
		},
		plzt: function(sex) {
			    if(this.sex==''){layer.msg("请先选择订单！");return false;}
			    layer.confirm('是否确认入队，入队后等待线程执行即可，禁止一直重复入队！20分钟内订单禁止入队，切记', {title: '温馨提示',icon: 3,btn: ['确认', '取消']}, function() {
    				var load = layer.load();
    				$.post("/apisub.php?act=plzt",{sex: sex}, {emulateJSON: true}).then(function(data) {
    					layer.close(load);
    					if (data.code == 1) {
    						vm.selectAll();
    						vm.get(vm.row.current_page);
    						layer.msg(data.msg, {icon: 1});
    					} else {
    						layer.msg(data.msg, {
    							icon: 2
    						});
    					}
    				});
				});
			},
			
		
			plbs:function(a){
				var load=layer.load();
          $.post("/apisub.php?act=plbs&a="+a,{sex:this.sex,type:1},{emulateJSON:true}).then(function(data){
		 	     layer.close(load);
	             if (data.code==1){
	              	vm.selectAll();   
	             	  vm.get(vm.row.current_page);		             	            	 
	                layer.msg(data.msg,{icon:1});	                
	             }else{
	                layer.msg(data.msg,{icon:2});
	             }	              
         });
		 },plsx:function(a){
				var load=layer.load();
          $.post("/apisub.php?act=plbs&a="+a,{sex:this.sex,type:1},{emulateJSON:true}).then(function(data){
		 	     layer.close(load);
	             if (data.code==1){
	              	vm.selectAll();   
	             	  vm.get(vm.row.current_page);		             	            	 
	                layer.msg(data.msg,{icon:1});	                
	             }else{
	                layer.msg(data.msg,{icon:2});
	             }	              
         });
		 },
		
	bs:function(oid){
		 	 		 	layer.confirm('建议漏看或者进度被重置的情况下使用。<br>频繁点击补刷会出现不可预测的结果<br>请问是否补刷所选的任务？', {title:'温馨提示',icon:3,
							  btn: ['确定补刷','取消'] //按钮
							}, function(){
		 			     var load=layer.load(2);
		          $.get("/apisub.php?act=bs&oid="+oid,function (data) {
				 	     layer.close(load);
			             if (data.code==1){
			             	  vm.get(vm.row.current_page);		             	 
			                layer.alert(data.msg,{icon:1});	                
			             }else{
			                layer.msg(data.msg,{icon:2});
			             }	              
		         });
         });
		 },up:function(oid){
				var load=layer.load(2);
				layer.msg("正在努力获取中....",{icon:3});
          $.get("/apisub.php?act=uporder&oid="+oid,function (data) {
		 	     layer.close(load);
	             if (data.code==1){
	             	  vm.get(vm.row.current_page);  
	             	  setTimeout(function() {
	             	  	for(i=0;i<vm.row.data.length;i++){           	
					            	 if(vm.row.data[i].oid==oid){
					            	 	  vm.ddinfo3.info=vm.row.data[i];
					            	 	  console.log(vm.row.data[i].oid);
					            	 	  console.log(vm.row.data[i].status);
					            	 	  console.log(vm.ddinfo3.info.status);
					            	 	  return true;
					            	 } 
					            } 
	             	  },1800);   	             	  		             	 
	                layer.msg(data.msg,{icon:1});	                               
	             }else{
	              	layer.msg(data.msg,{icon:2});	
//	                layer.alert(data.msg,{icon:2,btn:'立即跳转'},function(){
//	                	window.location.href=data.url
//	                });
	             }	              
         });			
		 },
		 plzt: function(sex) {
			    if(this.sex==''){layer.msg("请先选择订单！");return false;}
			    layer.confirm('是否确认入队，入队后等待线程执行即可，禁止一直重复入队！20分钟内订单禁止入队，切记', {title: '温馨提示',icon: 3,btn: ['确认', '取消']}, function() {
    				var load = layer.load();
    				$.post("/apisub.php?act=plzt",{sex: sex}, {emulateJSON: true}).then(function(data) {
    					layer.close(load);
    					if (data.code == 1) {
    						vm.selectAll();
    						vm.get(vm.row.current_page);
    						layer.msg(data.msg, {icon: 1});
    					} else {
    						layer.msg(data.msg, {
    							icon: 2
    						});
    					}
    				});
				});
			},plbs: function(sex) {
			    if(this.sex==''){layer.msg("请先选择订单！");return false;}
			    layer.confirm('是否确认入队补刷，入队后等待线程执行即可，禁止一直重复入队！20分钟内订单禁止入队，切记', {title: '温馨提示',icon: 3,btn: ['确认', '取消']}, function() {
    				var load = layer.load();
    				$.post("/apisub.php?act=plbs",{sex: sex}, {emulateJSON: true}).then(function(data) {
    					layer.close(load);
    					if (data.code == 1) {
    						vm.selectAll();
    						vm.get(vm.row.current_page);
    						layer.msg(data.msg, {icon: 1});
    					} else {
    						layer.msg(data.msg, {
    							icon: 2
    						});
    					}
    				});
				});
			},
		 duijie:function(oid){
		 	layer.confirm('确定处理么?', {title:'温馨提示',icon:3,
							  btn: ['确定','取消'] //按钮
							}, function(){
		 			     var load=layer.load();
		          $.get("/apisub.php?act=duijie&oid="+oid,function (data) {
				 	     layer.close(load);
			             if (data.code==1){
			             	  vm.get(vm.row.current_page);		             	 
			                layer.alert(data.msg,{icon:1});	                
			             }else{
			                layer.msg(data.msg,{icon:2});
			             }	              
		         });
         });
		 },getname:function(oid){
				var load=layer.load(2);
          $.get("/apisub.php?act=getname&oid="+oid,function (data) {
		 	     layer.close(load);
	             if (data.code==1){	             		             	 
	                layer.msg(data.msg,{icon:1});	                
	             }else{
	                layer.msg(data.msg,{icon:2});
	             }	              
         });			
		 },ms:function(oid){
			 	layer.confirm('提交秒刷将扣除0.05元服务费', {title:'温馨提示',icon:3,
								  btn: ['确定','取消'] //按钮
								}, function(){
			 			     var load=layer.load();
			          $.get("/apisub.php?act=ms_order&oid="+oid,function (data) {
					 	     layer.close(load);
				             if (data.code==1){
				             	  vm.get(vm.row.current_page);		             	 
				                layer.alert(data.msg,{icon:1});	                
				             }else{
				                layer.msg(data.msg,{icon:2});
				             }	              
			         });
	         });		
		 },quxiao:function(oid){
		 	 		 	layer.confirm('取消订单将无法退款，确定取消吗', {title:'温馨提示',icon:3,
							  btn: ['确定','取消'] //按钮
							}, function(){
		 			     var load=layer.load();
		          $.get("/apisub.php?act=qx_order&oid="+oid,function (data) {
				 	     layer.close(load);
			             if (data.code==1){
			             	  vm.get(vm.row.current_page);		             	 
			                layer.alert(data.msg,{icon:1});	                
			             }else{
			                layer.msg(data.msg,{icon:2});
			             }	              
		         });
         });
		 },status_text:function(a){
				var load=layer.load(2);
          $.post("/apisub.php?act=status_order&a="+a,{sex:this.sex,type:1},{emulateJSON:true}).then(function(data){
		 	     layer.close(load);
	             if (data.code==1){
	              	vm.selectAll();   
	             	  vm.get(vm.row.current_page);		             	            	 
	                layer.msg(data.msg,{icon:1});	                
	             }else{
	                layer.msg(data.msg,{icon:2});
	             }	              
         });	        
		 },
		 log: function(oid) {
                var load = layer.load(2);
                $.get("/apisub.php?act=cha_logwk&oid=" + oid, function(data) {
                    layer.close(load);
                    if (data.code == 1) {
                        var contentHtml = '<div class="log-list" style="padding: 15px;">';
                        data.data.forEach(function(item) {
                            info_aa = '时间：' + item.time + '<br>' + 
                          '课程：' + item.course + '<br>' +
                          '状态：' + item.status + '<br>' +
                          '进度：' + item.process + '<br>' +
                          '备注：' + item.remarks + '<br>' +
                          '详细：' + item.detail;            contentHtml += '<pre style="color: #0960bd;">' + info_aa + '</pre>';
                        });
                        contentHtml += '</div>';

                        // 使用 layer.open 创建弹出层
                        layer.open({
                            type: 1,
                            title: '学习日志',
                            area: ['80%', '80%'], // 宽度和高度都
                            content: contentHtml,
                            offset: 'auto', // 自动居中
                            resize: false, // 禁用拖拽调整大小
                            success: function(layero, index) {
                                // 调整内容区域的高度
                                var $content = $(layero).find('.layui-layer-content');
                                var $title = $(layero).find('.layui-layer-title');
                                var layerHeight = $(layero).height();
                                var titleHeight = $title.outerHeight();
                                var contentHeight = layerHeight - titleHeight;

                                $content.css({
                                    'height': contentHeight + 'px',
                                    'overflow-y': 'auto'
                                });

                                // 添加resize事件监听器
                                $(window).on('resize.logList', function() {
                                    layer.full(index);
                                    layer.restore(index);
                                });
                            },
                            end: function() {
                                // 移除resize事件监听器
                                $(window).off('resize.logList');
                            }
                        });
                    } else {
                        layer.msg(data.msg, {
                            icon: 2
                        });
                    }
                });
            },
			
		 tk: function(sex) {
			    if(this.sex==''){layer.msg("请先选择订单！");return false;}
			    layer.confirm('确定要退款吗？陛下，三思三思！！！', {title: '温馨提示',icon: 3,btn: ['确定', '取消']}, function() {
    				var load = layer.load();
    				$.post("/apisub.php?act=tk",{sex: sex}, {emulateJSON: true}).then(function(data) {
    					layer.close(load);
    					if (data.code == 1) {
    						vm.selectAll();
    						vm.get(vm.row.current_page);
    						layer.msg(data.msg, {icon: 1});
    					} else {
    						layer.msg(data.msg, {
    							icon: 2
    						});
    					}
    				});
				});
			},
			
			dock:function(a){
				var load=layer.load();
          $.post("/apisub.php?act=status_order&a="+a,{sex:this.sex,type:2},{emulateJSON:true}).then(function(data){
		 	     layer.close(load);
	             if (data.code==1){
	              	vm.selectAll();   
	             	  vm.get(vm.row.current_page);		             	            	 
	                layer.msg(data.msg,{icon:1});	                
	             }else{
	                layer.msg(data.msg,{icon:2});
	             }	              
         });	        
		 },selectAll: function () {
            if(this.sex.length==0) {
	          	for(i=0;i<vm.row.data.length;i++){           	
	            	vm.sex.push(this.row.data[i].oid)
	            }    	     	
          	}else{
          		this.sex=[]
          	}                           
      },ddinfo: function(a){  
      	    this.ddinfo3.info=a;
      	    var load=layer.load(2,{time:300});
      	    setTimeout(function() {
	             layer.open({
							  type: 1,
							  title:'订单详情操作',
							  skin: 'layui-layer-demo',
							  closeBtn: 1,
							  anim: 2,
							  shadeClose: true,
							  content: $('#ddinfo2'),
							  end: function(){ 
							    $("#ddinfo2").hide();
							  }
							});  
            }, 100); 
            
      },
      
      

      
           zztk:function(sex){
		 	 layer.confirm('源站退款你才能退，确定退款吗', {title:'温馨提示',icon:3,
				btn: ['确定','取消'] //按钮
				}, function(){
		 		var load=layer.load();
             $.post("/apisub.php?act=zztk",{sex:sex},{emulateJSON:true}).then(function(data){
                 layer.close(load);
                 if(data.code==1){
                     vm.get(vm.row.current_page);
                 layer.msg(data.msg,{icon:1});
                }else{
                     layer.msg(data.msg,{icon:2});
			             }	              
		         });
         });
		 },
      
      
      
      
      del:function(sex){
		 	 layer.confirm('删除订单将无法退款，确定取消吗', {title:'温馨提示',icon:3,
				btn: ['确定','取消'] //按钮
				}, function(){
		 		var load=layer.load();
             $.post("/apisub.php?act=delorder",{sex:sex},{emulateJSON:true}).then(function(data){
                 layer.close(load);
                 if(data.code==1){
                     vm.get(vm.row.current_page);
                 layer.msg(data.msg,{icon:1});
                }else{
                     layer.msg(data.msg,{icon:2});
			             }	              
		         });
         });
		 },

showExportDialog: function() {
                // 弹出导出对话框
                this.$confirm('请选择导出格式', '导出', {
                    confirmButtonText: 'xlsx格式',
                    cancelButtonText: '直接弹出',
                    cancelButtonClass: 'direct-export-btn',
                    // 添加直接弹出按钮的样式类
                    type: 'warning'
                }).then(()=>{
                    // 用户点击xlsx格式按钮时执行导出操作
                    this.daochu();
                }
                ).catch(()=>{
                    // 用户点击直接弹出按钮时执行导出操作
                    this.daochu1();
                }
                );
            },
            daochu1: function() {
                if (this.dc2.gs == '') {
                    layer.msg("请先选择格式", {
                        icon: 2
                    });
                    return false;
                }
                if (!this.sex[0]) {
                    layer.msg("请先选择订单", {
                        icon: 2
                    });
                    return false;
                }
                for (i = 0; i < this.sex.length; i++) {
                    oid = this.sex[i];
                    for (x = 0; x < this.row.data.length; x++) {
                        if (this.row.data[x].oid == oid) {
                            school = this.row.data[x].school;
                            user = this.row.data[x].user;
                            pass = this.row.data[x].pass;
                            kcname = this.row.data[x].kcname;
                            if (this.dc2.gs == '1') {
                                a = school + ' ' + user + ' ' + pass + ' ' + kcname;
                            } else if (this.dc2.gs == '2') {
                                a = user + ' ' + pass + ' ' + kcname;
                            } else if (this.dc2.gs == '3') {
                                a = school + ' ' + user + ' ' + pass;
                            } else if (this.dc2.gs == '4') {
                                a = user + ' ' + pass;
                            }
                            this.dc.push(a)
                        }
                    }
                }
                layer.alert(this.dc.join("<br>"));
                this.dc = [];
            },
            daochu: function() {
                if (this.dc2.gs == '') {
                    layer.msg("请先选择格式", {
                        icon: 2
                    });
                    return false;
                }
                if (!this.sex[0]) {
                    layer.msg("请先选择订单", {
                        icon: 2
                    });
                    return false;
                }

                // 构建 Excel 数据
                const excelData = [];
                const header = [];

                // 根据用户选择的格式构建表头
                if (this.dc2.gs == '1') {
                    header.push('学校', '用户名', '密码', '课程名');
                } else if (this.dc2.gs == '2') {
                    header.push('用户名', '密码', '课程名');
                } else if (this.dc2.gs == '3') {
                    header.push('学校', '用户名', '密码');
                } else if (this.dc2.gs == '4') {
                    header.push('用户名', '密码');
                }

                excelData.push(header);

                // 构建表格数据
                for (let i = 0; i < this.sex.length; i++) {
                    const oid = this.sex[i];
                    for (let x = 0; x < this.row.data.length; x++) {
                        if (this.row.data[x].oid == oid) {
                            const rowData = [];

                            // 根据用户选择的格式构建表格数据
                            if (this.dc2.gs == '1') {
                                rowData.push(this.row.data[x].school, this.row.data[x].user, this.row.data[x].pass, this.row.data[x].kcname);
                            } else if (this.dc2.gs == '2') {
                                rowData.push(this.row.data[x].user, this.row.data[x].pass, this.row.data[x].kcname);
                            } else if (this.dc2.gs == '3') {
                                rowData.push(this.row.data[x].school, this.row.data[x].user, this.row.data[x].pass);
                            } else if (this.dc2.gs == '4') {
                                rowData.push(this.row.data[x].user, this.row.data[x].pass);
                            }

                            excelData.push(rowData);
                        }
                    }
                }

                // 设置列宽和行高
                const wscols = [{
                    wpx: 100
                }, // 学校列宽度
                {
                    wpx: 100
                }, // 用户名列宽度
                {
                    wpx: 100
                }, // 密码列宽度
                {
                    wpx: 150
                }, // 课程名列宽度
                ];

                const wshrows = [{
                    hpx: 30
                }];
                // 设置表头行高

                // 设置表格中每一行的行高
                for (let i = 0; i < excelData.length; i++) {
                    wshrows.push({
                        hpx: 30
                    });
                }

                // 弹出确认导出对话框
                layer.confirm('确认导出为xlsx文件？', {
                    btn: ['确认', '取消'],
                    icon: 3,
                    title: '提示'
                }, (index)=>{
                    // 用户点击确认按钮时执行导出操作
                    const ws = XLSX.utils.aoa_to_sheet(excelData);

                    // 设置列宽和行高
                    ws['!cols'] = wscols;
                    ws['!rows'] = wshrows;

                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
                    XLSX.writeFile(wb, '订单数据.xlsx');

                    // 关闭确认对话框
                    layer.close(index);
                }
                , (index)=>{
                    // 用户点击取消按钮时执行的操作
                    // 关闭确认对话框
                    layer.close(index);
                }
                );
            }
        },
	mounted(){
		this.get(1);
		this.getclass();
	}
});






    
</script>
    

<script src="assets/js/shuiyin.js"></script>
<!-- 引入样式 -->
<link rel="stylesheet" href="https://unpkg.com/element-ui/lib/theme-chalk/index.css">
<!-- 引入组件库 -->
<script src="https://unpkg.com/element-ui/lib/index.js"></script>



 <?php if ($conf['ddggkg']==1) {?>
      <script>
layer.alert('<?=$conf['ddgg'];?>', {
  time: 5*1000
  ,success: function(layero, index){
    var timeNum = this.time/1000, setText = function(start){
      layer.title((start ? timeNum : --timeNum) + ' 秒后关闭', index);
    };
    setText(!0);
    this.timer = setInterval(setText, 1000);
    if(timeNum <= 0) clearInterval(this.timer);
  }
  ,end: function(){
    clearInterval(this.timer);
  }
});
 </script>
   <?}?>
   
   
   
   
   
   <script src="https://cdn.bootcss.com/sweetalert/2.1.0/sweetalert.min.js"></script>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// document.addEventListener('DOMContentLoaded', function () {
//   var now = new Date().getTime();
//   var popupShownTime = localStorage.getItem('popupShownTime');
//   var timePassed = now - popupShownTime;
//   if (!popupShownTime || timePassed > 600000) {
//     Swal.fire({
//       title: '微信扫码关注',
//       html: '<p><span style="font-size: 10pt;"><img src="/2024/04/27/nVaDCYKn.png" alt="My alt text" width="88" height="89" /></span></p>',
//       showCancelButton: true, // 显示取消按钮
//       cancelButtonText: '关闭', // 按钮文本
//       showConfirmButton: false, // 不显示确认按钮
//     }).then((result) => {
//       /* 如果需要在关闭弹出框后执行一些操作，可以在这里添加 */
//     });
//     // 更新localStorage中的时间戳
//     localStorage.setItem('popupShownTime', now.toString());
//   }
// });
</script>

   
   
   
   
   
