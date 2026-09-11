vm=new Vue({
	el:"#orderlist",
	data:{
		  row:null,
	
		  phone:'',
		 
		  sex:[],
		  ddinfo3:{
		  	status:false,
		  	info:[]
		  },
		  dc:[],
		  dc2:{
		  	gs:1
		  },
		  cx:{
		  	status_text:'',
		  	dock:'',
		  	qq:'',
		  	oid:'',
		  	uid:'',
		  	school:'',
		  	kcname:'',
		  	ptname:'',
		  	pass:'',
		  
		  
		  	cid:'',
		  	limit:''
                },
           orderCount: '',
            },methods:{
                 
                getOrderList(status) {
                this.cx.status_text = status;
                this.get(1);
            },
                
	    jietu:function(user){
          window.open("http://61.136.162.34:1688/score.php?username="+user);
       },
        zhengshu:function(user){
          window.open("http://61.136.162.34:1688/cert.php?username="+user);
       },removepercent:function(text){
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




