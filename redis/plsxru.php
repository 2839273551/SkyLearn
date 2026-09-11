<?php
include('../confing/common.php');
$redis=new Redis();
$redis->connect("127.0.0.1","6379");
$redis->select(11);

echo "连通redis： " . $redis->ping() . "\r\n";
    $lenth=$redis->LLEN('csoid');
    if($lenth==0){
        $i=0;
        $a=$DB->query("select * from qingka_wangke_order where  status='待刷新' and dockstatus=1 order by oid asc");
        foreach($a as $b){
            $redis->lPush("csoid",$b['oid']);
            $i++;
        }
        echo "入队成功！本次入队订单共计：".$i."条\r\n";
    }else {
        echo("入队失败！队列池还有：".$redis->LLEN('csoid')."条订单正在执行\r\n");
    }
?>