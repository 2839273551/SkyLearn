<?php
require_once('head.php');
$addsalt=md5(mt_rand(0,999).time());
$_SESSION['addsalt']=$addsalt;
?>
<link rel="stylesheet" href="assets/LightYear/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/apps.css" type="text/css" />
<link rel="stylesheet" href="assets/css/app.css" type="text/css" />
<link rel="stylesheet" href="assets/layui/css/layui.css" type="text/css" />
<link href="../assets/css/font-awesome.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/LightYear/js/bootstrap-multitabs/multitabs.min.css">
<link href="assets/LightYear/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/LightYear/css/style.min.css" rel="stylesheet">
<link href="assets/LightYear/css/materialdesignicons.min.css" rel="stylesheet">
<script src="layer/3.1.1/layer.js"></script>
<link rel="stylesheet" href="assets/css/element.css">
<link rel="stylesheet" href="assets/index.css">
<script src="assets/index.js"></script>
</script>     <div class="app-content-body ">

        <div class="wrapper-md control" id="add">
            <div class="layui-row layui-col-space10">
            <div class="layui-col-md6">
	    <div class="panel panel-default" id="orderlist" style="box-shadow: 3px 3px 8px #d1d9e6, -3px -3px 8px #d1d9e6;border-radius: 10px;">
		      <div class="panel-heading font-bold bg-white" style="border-radius: 10px;">查询课程&nbsp;&nbsp;


			   </div>
				<div class="panel-body" >
                  <form class="form-horizontal devform">
				    <div class="form-group">
					        <label class="col-sm-2 control-label">所有分类：</label>
					        <div class="col-sm-9">
					            
					                <div class="example-box">
					                    <div role="radiogroup" class="el-radio-group">
			 <label role="radio" tabindex="0" class="el-radio-button el-radio-button--small is-active" aria-checked="true">
             <input type="radio" tabindex="-1" autocomplete="off" class="el-radio-button__orig-radio" name="e" checked="" @change="fenlei('');">
             <span class="el-radio-button__inner">［全部］可搜索</span>
			 </label>
			 </div>&nbsp; 
					                    <?php
					                    $a=$DB->query("select * from qingka_wangke_fenlei where status=1 ORDER BY `id` asc ");
					                    while($rs=$DB->fetch($a)){
					                    ?>
					                    
					                    <div role="radiogroup" class="el-radio-group">
		    <label role="radio" tabindex="0" class="el-radio-button el-radio-button--small is-active" aria-checked="true">
            <input type="radio" tabindex="-1" autocomplete="off" class="el-radio-button__orig-radio" name="e" @change="fenlei(<?=$rs['id']?>);">
            <span class="el-radio-button__inner"><span><?=$rs['name']?></span></label>
            </div>&nbsp;
					                    <?php } ?>
					                </div>
					            
					         </div>
						</div>

                     <div class="form-group">
                        <label class="col-sm-2 control-label">选择项目：</label>
                        <div class="col-sm-9">
                           <form class="form-horizontal" id="form-update">
                              <template>
                                 <el-select id="select" v-model="cid" @change="tips(cid)" filterable placeholder="请先选择具体分类，单击输入框可进行搜索" style=" background: url('../index/arrow.png') no-repeat scroll 99%; width:100%">
                                    <el-option v-for="class2 in class1" :label="class2.name+'→'+class2.price+'积分'" :value="class2.cid">
                                       <span style="float: left">{{ class2.name }}</span>
                                       <span style="float: right; color: #8492a6; font-size: 13px">{{ class2.price }}积分</span>
                                    </el-option>
                                 </el-select>
                              </template>
                        </div>
                     </div>
							<!--这里是让商品注释在学习平台下显示-->
							<div class="form-group">
							<label class="col-sm-2 control-label">商品介绍：</label>
							<div class="col-sm-9">
							    <b>
						    <span class="help-block m-b-none" style="color: rgb(54, 154, 255);"><span v-html="content"></span>
						    </span>
						    </b>
							</div>
						</div>
                     <div v-show="show">
                        <div class="form-group" v-if="activems==true">
							<label class="col-sm-2 control-label" for="checkbox1">是否秒刷</label>
							<div class="col-sm-9">
								<div class="checkbox checkbox-success"  @change="tips2">
        				            <input type="checkbox" v-model="miaoshua">
        				            	<label for="checkbox1" id="miaoshua"></label>
							    </div>
							</div>
						</div>
                        <div class="form-group">
                           <label class="col-sm-2 control-label">填写账号：</label>
                           <div class="col-sm-9">
                              <textarea rows="5" class="layui-textarea" v-model="userinfo" placeholder="填写：学校 (手机号/学号) 密码/手机号 密码" style="border-radius: 8px;">
                              </textarea>
						</div>
							</div>


                        <div class="col-sm-offset-4 col-xs-12 , col-sm-12 , col-md-12 , col-lg-12">
<button type="button" @click="get" style="border-radius: 10px;" class="layui-btn layui-btn-normal layui-btn-radius" /><i class="layui-icon layui-icon-search"></i>查询课程</button>
<button type="button" @click="add" style="border-radius: 10px;" class="layui-btn layui-btn-warm layui-btn-radius" /><i class="layui-icon layui-icon-addition"></i>立即提交</button>
                        </div>
                     </div>
                     </form>
               </div>
            </div>
         </div>
         <div class="layui-col-md6" >
	    <div class="panel panel-default" id="orderlist" style="box-shadow: 3px 3px 8px #d1d9e6, -3px -3px 8px #d1d9e6;border-radius: 10px;">
<div class="panel-heading font-bold bg-white" style="border-radius: 10px;">查询结果</a></div>
				<div class="panel-body">
					<form class="form-horizontal devform">		
					<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
							  <div v-for="(rs,key) in row">
								  <div class="panel panel-default">
								    <div class="panel-heading" role="tab" id="headingOne">
								      <h4 class="panel-title" style="display:flex;align-items:center;">
								        <a role="button" data-toggle="collapse" data-parent="#accordion" :href="'#'+key" aria-expanded="true" >
								         <b>{{rs.userName}}</b>  {{rs.userinfo}} <span v-if="rs.msg=='查询成功'"><b style="color: green;">{{rs.msg}}</b></span><span v-else-if="rs.msg!='查询成功'"><b style="color: red;">{{rs.msg}}</b></span>
								        </a>
								      </h4>
								    </div>
								    <div :id="key" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
								      <div class="panel-body">
								      	  <div v-for="(res,key) in rs.data" class="checkbox checkbox-success">
								      	  	   <li><input type="checkbox" v-model="res.checkstatus" @click="checkResources(rs.userinfo,rs.userName,rs.data,res.id,res.name)"><label for="checkbox1"></label><span>{{res.name}}</span><span v-if="res.id!=''">[课程ID:{{res.id}}]{{res.state}}</span></li>
								      
									    </div>
								      </div>
								    </div>
								  </div>
						    	</div>
					</div>			
			        </form>
		            </div>
	     </div>
	     </div>
	     </div>
         </div>
<script>
function TgTips() {
	layer.alert('<span style="color: red;"> 1.•✅学习通推荐：<?=$conf['xxttj']?><br> 2.•✅学习通考试推荐：<?=$conf['xxtks']?><br>  3.•✅智慧树推荐：<?=$conf['zhs']?><br>  4.•✅职教云推荐：<?=$conf['ykt']?><br>  5.•✅英语课推荐：<?=$conf['yy']?><br>  6.•✅国开推荐：<?=$conf['guokai']?><br>7.•✅冷门推荐：<?=$conf['lm']?><br> 推荐分类排在前面的都是扛得住考验的，搜不到就发起工单，申请上架新商品，百亿分类都是好东西。<br></blockquote>', {
		title: '今日推荐',
		skin: 'layui-layer-molv layui-layer-wxd'
		  , shadeClose: true
	})
}
</script>
<script type="text/javascript" src="assets/LightYear/js/jquery.min.js"></script>
<script type="text/javascript" src="assets/LightYear/js/bootstrap.min.js"></script>
<script type="text/javascript" src="assets/LightYear/js/perfect-scrollbar.min.js"></script>
<script type="text/javascript" src="assets/LightYear/js/main.min.js"></script>
<script src="assets/js/aes.js"></script>
<script src="js/vue.min.js"></script>
<script src="js/vue-resource.min.js"></script>
<script src="js/axios.min.js"></script>
<script src="./assets/js/element.js"></script>

<script>
var vm=new Vue({
	el:"#add",
	data:{	
	    row:[],
	    check_row:[],
		userinfo:'',
		cid:'',
		id:'',
		miaoshua:'',
		class1:'',
		class3:'',
		show: true,
		show1: true,
		content:'',
		activems:false
	},
	methods:{
	    get:function(){
	    	if(this.cid=='' || this.userinfo==''){
	    		layer.msg("所有项目不能为空");
	    		return false;
	    	}
		    userinfo=this.userinfo.replace(/\r\n/g, "[br]").replace(/\n/g, "[br]").replace(/\r/g, "[br]");      	           	    
	   	    userinfo=userinfo.split('[br]');//分割
	   	    this.row=[];
	   	    this.check_row=[];    	
	   	    for(var i=0;i<userinfo.length;i++){	
	   	    	info=userinfo[i]
	   	    	var hash=getENC('<?php echo $addsalt;?>');
	   	    	var loading=layer.load(2);
	    	    this.$http.post("/apisub.php?act=get",{cid:this.cid,userinfo:info,hash},{emulateJSON:true}).then(function(data){
	    		     layer.close(loading);	    	
	    		     this.show1 = true;
	    			 this.row.push(data.body);
	    	    });
	   	    }	   	    	    
	    },
	    add:function(){
	    	if(this.row.length<1){
	    	    if(this.nochake!=1){
    	    		layer.msg("请先查课");
    	    		return false;
	    	    }
	    	} 	
	    	if(this.check_row.length<1){
	    	    if(this.nochake!=1){
    	    		layer.msg("请先选择课程");
    	    		return false;
	    	    }
	    	} 	
	    	//console.log(this.check_row);
	        var loading=layer.load();
	    	this.$http.post("/apisub.php?act=add",{cid:this.cid,data:this.check_row,shu:this.shu,bei:this.bei,userinfo:this.userinfo,nochake:this.nochake,additional:this.additional},{emulateJSON:true}).then(function(data){
	    		layer.close(loading);
	    		if(data.data.code==1){
	    			this.row=[];
	    			this.check_row=[];
	    			this.userinfo='';
	    			this.$message({type: 'success', showClose: true,message: data.data.msg});
	    		}else{
	    			this.$message({type: 'error', showClose: true,message: data.data.msg});
	    		}
	    	});
	    },
	    checkAll: function(rs){
	       vm.check_row = [];
	       for(var i=0;i<rs.data.length;i++){
	            this.checkResources(rs.userinfo,rs.userName,rs.data,rs.data[i].id,rs.data[i].name,true);
	            rs.data[i].checkstatus = rs.checkall ? false : true;
	       }
	       
	    },
	    checkResources:function(userinfo,userName,rs,id,name){
	        for(i=0;i<rs.length;i++){
	            if(id==""){
	                if(rs[i].name==name){
	                    aa=rs[i]
	                }
	            }else{
	                if(rs[i].id==id && rs[i].name==name){
	                    aa=rs[i]
	                }
	            }
	    	}
	    	data={userinfo,userName,data:aa}
	    	if(this.check_row.length<1){
	    		vm.check_row.push(data); 
	    	}else{
	    	    var a=0;
		    	for(i=0;i<this.check_row.length;i++){		    		
		    		if(vm.check_row[i].userinfo==data.userinfo && vm.check_row[i].data.name==data.data.name){		    			
	            		var a=1;
	            		vm.check_row.splice(i,1);	
		    		}	    		
		    	}	    	   	    	               
               if(a==0){
               	   vm.check_row.push(data);
               }
	    	} 
	    },
	    fenlei:function(id){
		  var load=layer.load(2);
 			this.$http.post("/apisub.php?act=getclassfl",{id:id},{emulateJSON:true}).then(function(data){	
	          	layer.close(load);
	          	if(data.data.code==1){			                     	
	          		this.class1=data.body.data;			             			                     
	          	}else{
	                layer.msg(data.data.msg,{icon:2});
	          	}
	        });	
	    	
	    },
	    getclass:function(){
		  var load=layer.load(2);
 			this.$http.post("/apisub.php?act=getclass").then(function(data){	
	          	layer.close(load);
	          	if(data.data.code==1){			                     	
	          		this.class1=data.body.data;			             			                     
	          	}else{
	                layer.msg(data.data.msg,{icon:2});
	          	}
	        });	
	    	
	    },
        tips: function (message) {
        	 for(var i=0;this.class1.length>i;i++){
        	 	if(this.class1[i].cid==message){
        	 	    this.show = true;
        	 	    this.content = this.class1[i].content;
	    		return false;	
        	 		if(this.class1[i].miaoshua==1){
					   	 this.activems=true;
					   }else{
					   	 this.activems=false;
					   }
        	 		return false;
        	 		
        	 	}
        	 	
        	 }
	
        },
        tips2: function () {
        	layer.tips('开启秒刷将额外收0.05的费用', '#miaoshua');      	  
		  
        }    
	},
	mounted(){
		this.getclass();		
	}
	
	
});
</script>
 <?php if ($conf['xdggkg']==1) {?>
      <script>
layer.alert('<?=$conf['xdgg'];?>', {
  time: 8*1000
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
