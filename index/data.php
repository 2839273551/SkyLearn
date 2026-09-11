
<?php
$title='数据统计';
require_once('head.php');
if($userrow['uid']!=1){exit("<script language='javascript'>window.location.href='login.php';</script>");}

 $php_Self = substr($_SERVER['PHP_SELF'],strripos($_SERVER['PHP_SELF'],"/")+1); 
if($php_Self!="data.php"){
    exit('文件损坏,请联系SkyLearn');
}
require_once('./head.php');
if ($userrow['uid'] != 1) {
  exit("<script language='javascript'>window.location.href='./404';</script>");
}
$jtdate = date('Y-m-d 00:00:00'); // 今天0点（午夜）的时间
$now = date('Y-m-d H:i:s'); // 当前时间
$yesterdayStart = date('Y-m-d 00:00:00', strtotime('yesterday'));

$uid=$_GET['uid'];
?>

 <div class="layui-container" style="padding: 20px;">
	<div class="layui-row" style="margin-bottom: 15px;">
		<div class="layui-card">
			<div class="layui-card-header" style="background-color: #F2F2F2; font-weight: bold;">数据统计</div>
			<div class="layui-card-body">
         
                
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <tr>
                            <td><i class="fas fa-users"></i> 总用户:<span class="badge badge-secondary"><?php echo $DB->count("select count(*) from qingka_wangke_user ") . "人"; ?></span></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-user-plus"></i> 今日新增用户:<span class="badge badge-secondary"><?php echo $DB->count("SELECT COUNT(*) FROM qingka_wangke_user WHERE addtime BETWEEN '$jtdate' AND '$now'") . "人"; ?></span></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-clipboard-list"></i> 总订单:<span class="badge badge-secondary"><?php echo $DB->count("select count(*) from qingka_wangke_order") . "条"; ?></span></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-clipboard-list"></i> 昨日订单:<span class="badge badge-secondary"><?php
                                $yesterdayStart = date('Y-m-d 00:00:00', strtotime('yesterday'));
                                $yesterdayEnd = date('Y-m-d 23:59:59', strtotime('yesterday'));
                                echo $DB->count("select count(*) from qingka_wangke_order where addtime>='$yesterdayStart' and addtime<='$yesterdayEnd'") . "条";
                                ?></span></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-dollar-sign"></i> 昨日销售:<span class="badge badge-secondary"><?php
                                $yesterdayStart = date('Y-m-d 00:00:00', strtotime('yesterday'));
                                $yesterdayEnd = date('Y-m-d 23:59:59', strtotime('yesterday'));
                                $zcz = 0;
                                $a = $DB->query("select * from qingka_wangke_order where addtime>='$yesterdayStart' and addtime<='$yesterdayEnd'");
                                while ($c = $DB->fetch($a)) {
                                    $zcz += $c['fees'];
                                }
                                echo $zcz . "元";
                                ?></span></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-clipboard-list"></i> 今日订单:<span class="badge badge-secondary"><?php echo $DB->count("SELECT COUNT(*) FROM qingka_wangke_order WHERE addtime BETWEEN '$jtdate' AND '$now'") . "条"; ?></span></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-dollar-sign"></i> 今日销售:<span class="badge badge-secondary"><?php
                                $zcz1 = 0;
                                $a = $DB->query("SELECT * FROM qingka_wangke_order WHERE addtime BETWEEN '$jtdate' AND '$now'");
                                while ($c = $DB->fetch($a)) {
                                    $zcz1 += $c['fees'];
                                }
                                echo $zcz1 . "元";
                                ?></span></td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-clipboard-list"></i> 最近7天内总订单:<span class="badge badge-secondary"><?php
                                $thirtyDaysAgo = date('Y-m-d', strtotime("-7 days"));
                                echo $DB->count("select count(*) from qingka_wangke_order where date(addtime) >= '$thirtyDaysAgo'") . "条";
                                ?></span></td>
                        </tr>
                        
                        
                           <tr>
                            <td><i class="fas fa-clipboard-list"></i> 今日总充值:<span class="badge badge-secondary"><?php
                               $a=$DB->query("select * from qingka_wangke_pay where status='1' and addtime>'$jtdate'  ");
						$jrcz=0;
						while($c=$DB->fetch($a)){
						    $jrcz+=$c['money'];
						}
						echo $jrcz;
						?>元</div>
                             
                             
                             </td>
                        </tr>
                        
                        
                        
                        
                    </table>
                </div>
            </div>
        </div>
    </div>

       <!--  

</div>
<?php require_once("footer.php"); ?>