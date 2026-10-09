<?php
include('../confing/common.php');
$redis=new Redis();
$redis->connect("127.0.0.1","6379");
$redis->select(11);
while(true){
    $oid=$redis->lpop('csoid');
    $a=$DB->get_row("select * from qingka_wangke_order where oid='$oid'");
    if($oid!=''){
        $result=budanWk($oid);
        for($i=0;$i<count($result);$i++){
        $today_day=date("Y-m-d H:i:s");
        echo ("订单ID：".$oid."|重刷成功！$today_day\r\n");
        $DB->query("update qingka_wangke_order set `status`='补刷中',`bsnum`=bsnum+1 where `oid`='$oid' ");
                                }
                                        }
    sleep(3);
}