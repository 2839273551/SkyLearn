<?php
include('../confing/common.php');
$redis=new Redis();
$redis->connect("127.0.0.1","6379");
$redis->select(10);

echo "连通redis： " . $redis->ping() . "\r\n";
    $lenth=$redis->LLEN('oidstjxz');
    if($lenth==0){
        $i=0;
        $a=$DB->query("select * from qingka_wangke_order where (status='进行中' or status='补刷中') and dockstatus=1 and status not in ('已完成','已取消','已退款') and (process not like '100%' or status in ('待重刷','补刷中')) order by oid asc");
        foreach($a as $b){
            $redis->lPush("oidstjxz",$b['oid']);
            $i++;
        }
        echo "入队成功！本次入队订单共计：".$i."条\r\n";
    }else {
        echo("入队失败！队列池还有：".$redis->LLEN('oidstjxz')."条订单正在执行\r\n");
    }
?>