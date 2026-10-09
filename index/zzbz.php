<?php
$title='站长帮助';
require_once('head.php');
if($userrow['uid']!=1){exit("<script language='javascript'>window.location.href='login.php';</script>");}
?>
<link href="//unpkg.com/layui@2.8.6/dist/css/layui.css" rel="stylesheet">
<div class="app-content-body ">
    <div class="wrapper-md control">
        <div class="layui-row layui-col-space8 layui-anim layui-anim-upbit">
            <div class="layui-card" style="box-shadow: 3px 3px 8px #d1d9e6, -3px -3px 8px #d1d9e6;border-radius: 7px;">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="active">
                      <a data-toggle="tab" href="#jc">配置教程</a>
                    </li>

                </ul>
                
               <div class="tab-content">  
    <div class="tab-pane fade active in" id="jc">  
        <blockquote class="layui-elem-quote layui-quote-nm">  
            <span style="color: #6699FF;">如果你能看到这里，恭喜你已经成功了一大步，那么接下来进行一些基础配置</span><br>
            <span style="color: #6699FF;">步骤1：安装PHP7.3并且安装redis扩展以及redis</span><br> 
            <span style='color: #6699FF;'>步骤2：宝塔—计划任务-访问URL 监控如下地址</span>  <br> 
            <span style='color: #6699FF;'>你必须百分之百照做教程安装，否则你不准提出任何问题</span>  
             <span style="color: #6699FF;">首先，你必须清楚，任何软件的安装都有严格的步骤和要求。安装时，只要偏离了教程的一丝一毫，问题就会随之而来。你必须百分之百照做教程安装，否则你不准提出任何问题。</span><br>
    <span style="color: #6699FF;">你看到的每一个步骤，都有其背后的原因，每一个操作，都是为了确保系统的稳定和高效运行。没有任何一步是多余的，遗漏任何一步，可能会导致整个系统无法正常工作。你必须百分之百照做教程安装，否则你不准提出任何问题。</span><br>
    <span style="color: #6699FF;">当你按照教程的指导去操作时，你会发现每个细节都是经过深思熟虑的。如果在安装过程中遇到问题，不要急于寻求帮助，而是回过头来检查是否遗漏了某个步骤。你必须百分之百照做教程安装，否则你不准提出任何问题。</span><br>
    <span style="color: #6699FF;">安装过程中的每一个指令、每一个设置，都是为了确保系统的最佳状态。如果你随意更改某个步骤，你很可能会遇到各种无法预料的问题。切记，你必须百分之百照做教程安装，否则你不准提出任何问题。</span><br>
    <span style="color: #6699FF;">有时候，用户可能会觉得教程中的某些步骤有些多余或繁琐，但请相信，这些步骤背后都有其存在的意义。任何跳过或者省略的步骤，都可能让你在后续的操作中遇到困难。你必须百分之百照做教程安装，否则你不准提出任何问题。</span><br>
    <span style="color: #6699FF;">许多技术问题看似复杂，但只要按照教程一步步走下去，问题自然能迎刃而解。不要试图走捷径，避免了某些步骤的快速安装可能导致后续的麻烦。如果你依照教程完成安装，那么大多数问题都能迎刃而解。你必须百分之百照做教程安装，否则你不准提出任何问题。</span><br>
    <span style="color: #6699FF;">有时，在安装过程中，出现错误信息并不代表你的操作失败，而是因为某些步骤没有正确执行。你必须回过头，按照教程重新核对每个步骤，确保没有遗漏。你必须百分之百照做教程安装，否则你不准提出任何问题。</span><br>
    <span style="color: #6699FF;">如果你忽略了教程中某个细节，那么在后续操作中，可能会因为不符合预期的配置而导致系统崩溃或运行缓慢。每个步骤的严格执行，都是为了确保系统的稳定性和安全性。所以请记住，你必须百分之百照做教程安装，否则你不准提出任何问题。</span><br>
    <span style="color: #6699FF;">此外，不同的系统和环境可能会导致安装过程中的一些小差异，但这些差异也能通过仔细查看教程中的具体说明来避免。无论如何，你必须百分之百照做教程安装，否则你不准提出任何问题。</span><br>
    <span style="color: #6699FF;">如果你在按照教程进行安装时，发现步骤不清楚或不理解某些操作，务必认真阅读教程内容，或参考相关资料进行查阅。在没有完全理解之前，绝对不能跳过任何一步。你必须百分之百照做教程安装，否则你不准提出任何问题。</span><br>
    <span style="color: #6699FF;">总结来说，教程的每一步都有其意义，按照教程进行操作，是确保成功安装的唯一途径。不要心急，也不要抱怨教程的复杂，每一步操作都需要认真对待。你必须百分之百照做教程安装，否则你不准提出任何问题。</span><br>
        </blockquote>  
        

<!--<blockquote class="layui-elem-quote layui-quote-nm">-->
<!--    <span style="color: #6699FF;">各模块配置说明：</span><br>-->
<!--    <span style="color: #6699FF;">1. 鲸鱼配置：修改 jingyu/jingyu.config.php，设置 uid 和 key，并按注释说明配置商品价格</span><br>-->
<!--    <span style="color: #6699FF;">2. 盘古配置：修改 pangu/pangu.config.php，设置 uid 和 key，并按注释说明配置商品价格</span><br>-->
<!--    <span style="color: #6699FF;">3. 雷电配置：修改 ldrun/ldrun.config.php，设置 uid 和 key，并按注释说明配置商品价格</span><br>-->
<!--    <span style="color: #6699FF;">4. 火腿肠配置：修改 huotui/config.php，设置 uid 和 key，并按注释说明配置商品价格</span><br>-->
<!--    <span style="color: #6699FF;">5. 爱神配置：修改 aishen/config.php，设置 uid 和 key，并按注释说明配置商品价格</span><br>-->
<!--    <span style="color: #6699FF;">6. APPUI打卡配置：修改 appui/config.php，设置 uid 和 key，并修改course.json配置商品价格</span><br>-->
<!--    <span style="color: #6699FF;">7. 运动世界配置：修改 ydsj/config.php，设置 uid 和 key，并按注释说明配置商品价格</span><br>-->
<!--    <span style="color: #FF0033;">注意：所有配置文件中的价格设置都有详细注释说明，请按实际需求调整</span>-->
<!--</blockquote>        -->
        
<pre class="layui-code code-demo" lay-title="玉帝实时进度,1-5分钟1次,视服务器性能而定">
<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/cron/YD.php'; ?>
</pre>
<pre class="layui-code code-demo" lay-title="邀请次数上限,每天0：10一次">
<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/cron/yqm.php'; ?>
</pre>
<pre class="layui-code code-demo" lay-title="提交入队,1分钟1次">
<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/redis/addru.php'; ?>
</pre>
<!--<pre class="layui-code code-demo" lay-title="同步入队,10分钟1次">-->
<!--<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/redis/addru.php'; ?>-->
<!--</pre>-->
<pre class="layui-code code-demo" lay-title="实时入队,1分钟1次">
<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/redis/ccru.php'; ?>
</pre>
<pre class="layui-code code-demo" lay-title="批量补刷入队,1分钟1次">
<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/redis/plbsru.php'; ?>
</pre>
<pre class="layui-code code-demo" lay-title="批量刷新入队,1分钟1次">
<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/redis/plsxru.php'; ?>
</pre>
<pre class="layui-code code-demo" lay-title="补刷入队,1分钟1次">
<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/redis/bsru.php'; ?>
</pre>
<!--<pre class="layui-code code-demo" lay-title="APPUI打卡同步">-->
<!--<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/appui/cron.php'; ?>-->
<!--</pre>-->

<!--<pre class="layui-code code-demo" lay-title="盘古同步">-->
<!--<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/pangu/cron.php'; ?>-->
<!--</pre>-->

<!--<pre class="layui-code code-demo" lay-title="鲸鱼同步">-->
<!--<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/jingyu/cron.php'; ?>-->
<!--</pre>-->

<!--<pre class="layui-code code-demo" lay-title="雷电同步">-->
<!--<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/ldrun/cron.php'; ?>-->
<!--</pre>-->

<!--<pre class="layui-code code-demo" lay-title="火腿肠同步">-->
<!--<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/huotui/cron.php'; ?>-->
<!--</pre>-->

<!--<pre class="layui-code code-demo" lay-title="爱神同步">-->
<!--<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/aishen/cron.php'; ?>-->
<!--</pre>-->


<pre class="layui-code code-demo" lay-title="价格同步">
<?php echo $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/cron/updateprice.php'; ?>
</pre>




<span style='color: #6699FF;'>步骤3：软件商店-进程守护-添加如下进程守护</span>  
        </blockquote>  
<pre class="layui-code code-demo" lay-title="提交出队">名称 add 数量 1 启动命令 nohup php addchu.php & 进程目录：<?php echo $_SERVER['DOCUMENT_ROOT']; ?>/redis</pre>
<pre class="layui-code code-demo" lay-title="批量补刷">名称 plbs 数量 5  启动命令 nohup php plbschu.php &  进程目录：<?php echo $_SERVER['DOCUMENT_ROOT']; ?>/redis</pre>
<pre class="layui-code code-demo" lay-title="批量刷新">名称 plsx 数量 5  启动命令 nohup php plsxchu.php &  进程目录：<?php echo $_SERVER['DOCUMENT_ROOT']; ?>/redis</pre>
<pre class="layui-code code-demo" lay-title="补刷">名称 bs 数量 5 启动命令 nohup php bschu.php & 进程目录：<?php echo $_SERVER['DOCUMENT_ROOT']; ?>/redis</pre>
<pre class="layui-code code-demo" lay-title="实时出队">名称 cc 数量 10 启动命令 nohup php ccchu.php & 进程目录：<?php echo $_SERVER['DOCUMENT_ROOT']; ?>/redis</pre>


<!--<pre class="layui-code code-demo" lay-title="运动世界同步">名称 ydsj 数量 1 启动命令 nohup php cron_order.php & 进程目录：<?php echo $_SERVER['DOCUMENT_ROOT']; ?>/ydsj</pre>-->



    </div>  

                    
                      <div class="tab-pane fade" id="ax">
                        <div class="modal-content">
                            <div class="table-responsive">
                                <table class="table table-striped">
        							<blockquote class="layui-elem-quote layui-quote-nm">  
            <span style="color: #6699FF;">修改index/qg.php  qg/api.php  qg/cron.php内的token</span><br> 
            <span style='color: #FF0033;'>记得联系爱学加白，如有需要可以联系我进行开户</span>  
        </blockquote>  
        							
                                        
        						</table>
                            </div>
                        </div>
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
<script src="js/vue.min.js"></script>
<script src="js/vue-resource.min.js"></script>
<script src="js/axios.min.js"></script>
<script src="//unpkg.com/layui@2.8.6/dist/layui.js"></script>  
<script src="assets/js/element.js"></script>


<script>
    //注意：选项卡 依赖 element 模块，否则无法进行功能性操作
    layui.use('element', function(){
      var element = layui.element;
      
      //…
    });
</script>

<script>
new Vue({
	el:"#loglist",
	data:{
		row:null
	},
	methods:{
		get:function(page){

		}
	},
	mounted(){
		this.get(1);
	}
});
</script>
<script>
layui.use(function(){
  // code
  layui.code({
    elem: '.code-demo',
    skin: 'dark',
    about: false,
    ln: false,
    header: true,
    preview: false,
    //tools: ['full', 'copy']
  });
})
//点击复制
/*function copyToClip(content, message = null) {
        var aux = document.createElement("input");
        aux.setAttribute("value", content);
        document.body.appendChild(aux);
        aux.select();
        document.execCommand("copy");
        document.body.removeChild(aux);
        if (message == null) {
            layer.msg("复制成功", {icon: 1});
        } else {
            layer.msg(message, {icon: 1});
        }
    }*/
</script>