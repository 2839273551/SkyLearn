<?php
$dockingStartTime = microtime(true);
include('confing/common.php'); 
$act=isset($_GET['act'])?daddslashes($_GET['act']):null;
@header('Content-Type: application/json; charset=UTF-8');

$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        foreach ($decoded as $k => $v) {
            if (!isset($_POST[$k])) {
                $_POST[$k] = $v;
            }
            if (!isset($_REQUEST[$k])) {
                $_REQUEST[$k] = $v;
            }
        }
    }
}

$dockingBytesIn = !empty($rawInput) ? strlen($rawInput) : (strlen(http_build_query($_POST)) + strlen(isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : ''));

ob_start();

register_shutdown_function(function() use ($dockingStartTime, $dockingBytesIn, $act) {
    $output = ob_get_contents();
    ob_end_clean();
    echo $output;

    if (!function_exists('record_docking_log')) {
        return;
    }

    $costMs = round((microtime(true) - $dockingStartTime) * 1000);
    $bytesOut = strlen($output);

    $status = 0;
    $respData = null;
    if (!empty($output)) {
        $respData = json_decode($output, true);
        if (is_array($respData) && isset($respData['code']) && intval($respData['code']) === 1) {
            $status = 1;
        }
    }

    $actionMap = array(
        'balance' => '查询余额',
        'getmoney' => '查询余额',
        'query' => '在线查课',
        'get' => '在线查课',
        'order' => '提交下单',
        'add' => '提交下单',
        'docking_add' => '一步查课下单',
        'docking' => '一步查课下单',
        'status' => '查询订单进度',
        'order_status' => '查询订单进度',
        'cx' => '查询订单进度',
        'reorder' => '申请补单重跑',
        'bs' => '申请补单重跑',
        'goods' => '获取商品列表',
        'class' => '获取商品列表',
        'getcid' => '获取商品列表',
        'goods_detail' => '商品详情',
        'cid' => '商品详情',
        'login' => '账号鉴权登录',
        'dl' => '账号鉴权登录',
        'cancel' => '订单退款/取消'
    );
    $actionLabel = isset($actionMap[$act]) ? ($actionMap[$act] . " ({$act})") : ($act ? "外部对接 ({$act})" : "未识别动作");

    $uid = isset($_POST['uid']) ? intval($_POST['uid']) : (isset($_GET['uid']) ? intval($_GET['uid']) : 0);
    $caller = $uid > 0 ? "UID: {$uid}" : "外部商户/匿名";
    if ($uid > 0) {
        global $DB;
        if (isset($DB) && is_object($DB)) {
            $u = $DB->get_row("SELECT user FROM qingka_wangke_user WHERE uid='$uid' LIMIT 1");
            if (!empty($u['user'])) {
                $caller = "UID: {$uid} ({$u['user']})";
            }
        }
    }

    $inputParams = !empty($_POST) ? $_POST : (isset($_GET) ? $_GET : array());

    record_docking_log(array(
        'direction' => 'in',
        'action' => $actionLabel,
        'caller' => $caller,
        'uid' => $uid,
        'target' => '/api.php?act=' . ($act ?: 'unknown'),
        'method' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'POST',
        'params' => $inputParams,
        'response' => $respData !== null ? $respData : (strlen($output) > 300 ? substr($output, 0, 300) . '...' : $output),
        'status' => $status,
        'cost_ms' => $costMs,
        'bytes_in' => $dockingBytesIn,
        'bytes_out' => $bytesOut
    ));
});

switch($act){
	case 'getmoney':
    case 'balance'://查询当前余额
       $uid=trim(strip_tags(daddslashes($_POST['uid'])));
       $key=trim(strip_tags(daddslashes($_POST['key'])));
        if($uid=='' || $key==''){
     	   exit('{"code":0,"msg":"所有项目不能为空"}');
        }
        $row=$DB->get_row("select * from qingka_wangke_user where uid='$uid' limit 1");
	     if($row['key']=='0'){
	     	$result=array("code"=>-1,"msg"=>"你还没有开通接口哦");
	     	exit(json_encode($result));
	     }elseif($row['key']!=$key){
	     	$result=array("code"=>-2,"msg"=>"密匙错误");
	     	exit(json_encode($result));
	     }else{
            $result=array(
                'code'=>1,
                'msg'=>'查询成功',
                'money'=>$row['money']
            );
		    exit(json_encode($result));
     }
  break;
  case 'get':
  case 'query'://单查询
       $zdmoney=$conf['zddy'];
       $uid=daddslashes($_POST['uid']);
       $key=daddslashes($_POST['key']);
       $platform=daddslashes($_POST['platform']);
       $school=daddslashes($_POST['school']);
       $user=daddslashes($_POST['user']);
       $pass=daddslashes($_POST['pass']);
       $type=daddslashes($_POST['type']);
       if($conf['ckkg']==1){
        if($uid=='' || $key=='' || $platform=='' || $school=='' || $user=='' || $pass==''){
     	   exit('{"code":0,"msg":"所有项目不能为空"}');
        }
        
        $row=$DB->get_row("select * from qingka_wangke_user where uid='$uid' limit 1");
		$rs=$DB->get_row("select * from qingka_wangke_class where cid='$platform' limit 1 ");
	     if($row['key']=='0'){
	     	$result=array("code"=>-1,"msg"=>"你还没有开通接口哦");
	     	exit(json_encode($result));
	     }elseif($row['key']!=$key){
	     	$result=array("code"=>-2,"msg"=>"密匙错误");
	     	exit(json_encode($result));
	     }elseif($row['money'] < $zdmoney){
	   //  	$result=array("code"=>-2,"msg"=>"余额小于{$zdmoney}禁止调用查课");
	   $result=array("code"=>-2,"msg"=>"查课出现错误，请联系站长");
	     	exit(json_encode($result));
	     }elseif($rs['status'] == 0){
	     	$result=array("code"=>-2,"msg"=>"网课已下架禁止查课！");
	     	exit(json_encode($result));
	     }else{
	        $rs=$DB->get_row("select * from qingka_wangke_class where cid='$platform' limit 1 ");	    
            $result=getWk($rs['queryplat'],$rs['getnoun'],$school,$user,$pass,$rs['name']);					
	 		$result['userinfo']=$school." ".$user." ".$pass;
		    wlog($uid,"API查课","{$rs['name']}-查课信息：{$school} {$user} {$pass}",0);	
		    
		    if($type=="xiaochu"){
		    	foreach($result['data'] as $row){			    		    		
		    		if($value==''){
		    			$value=$row['name'];
		    		}else{
		    			$value=$value.','.$row['name'];
		    		}	
		    	}		 
		    	$v[0]=$rs['name'];   	
		    	$v[1]=$user;
		    	$v[2]=$pass;
		    	$v[3]=$school;
		    	$v[4]=$value;	
		    	$data=array(
		    	  'code'=>$result['code'],
		    	  'msg'=>$result['msg'],
		    	  'data'=>$v,
		    	  'js'=>'',
		    	  'info'=>'昔日之苦，安知异日不在尝之? 共勉'
		    	);
		    	exit(json_encode($data));
		    }else{
		    	exit(json_encode($result));
		    }		   		    
     }}else{exit('{"code":-1,"msg":"管理员已关闭api查课，调用请联系管理员！"}');}
  break;

 case 'add':
 case 'order'://单下单
       $uid=daddslashes($_POST['uid']);
       $key=daddslashes($_POST['key']);
       $platform=daddslashes($_POST['platform']);
       $school=daddslashes($_POST['school']);
       $user=daddslashes($_POST['user']);
       $pass=daddslashes($_POST['pass']);
       $kcid=daddslashes($_POST['kcid']);
       $kcname=daddslashes($_POST['kcname']);
       $clientip=real_ip();
       $zdxd=$conf['zdxd'];
        if($uid=='' || $key=='' || $platform=='' || $school=='' || $user=='' || $pass=='' || $kcname==''){
     	   exit('{"code":0,"msg":"所有项目不能为空"}');
        }
         $row=$DB->get_row("select * from qingka_wangke_user where uid='$uid' limit 1");
		 $rs=$DB->get_row("select * from qingka_wangke_class where cid='$platform' limit 1 ");
	     if($row['key']=='0'){
	     	exit('{"code":-1,"msg":"你还没有开通接口哦"}');
	     }if($row['key']!=$key){
	     	exit('{"code":-2,"msg":"密匙错误"}');
	     }elseif($rs['status'] == 0){
	     	$result=array("code"=>-2,"msg"=>"小老弟，商品都下架了你还下什么单呢！");
	     	exit(json_encode($result));
	     }elseif($row['money'] < $zdxd){
	   //  	$result=array("code"=>-2,"msg"=>"余额小于{$zdmoney}禁止调用查课");
	   $result=array("code"=>-2,"msg"=>"余额低于$zdxd禁止使用api下单");
	     	exit(json_encode($result));
	     }
	     else{ 
	     	$rs=$DB->get_row("select * from qingka_wangke_class where cid='$platform' limit 1 ");
	     	$res=$DB->get_row("select * from qingka_wangke_huoyuan where hid='{$docking}' limit 1 ");
	     	
	     	
	     	
	     	if ($row['vip'] != 1) { // 非会员
                if($rs['yunsuan']=="*"){
    	    		$danjia=round($rs['price']*$row['addprice'],2);
    	    		$danjia1=$danjia;
    	    	}elseif($rs['yunsuan']=="+"){
    	    		$danjia=round($rs['price']+$row['addprice'],2);
    	    		$danjia1=$danjia;
    	    	}else{
    	    		$danjia=round($rs['price']*$row['addprice'],2);
    	    		$danjia1=$danjia;
    	    	}
            } else { // 会员
                if ($rs['vipprice'] != "") {
                   if($rs['yunsuan']=="*"){
        	    		$danjia=round($rs['vipprice']*$row['addprice'],2);
        	    		$danjia1=$danjia;
        	    	}elseif($rs['yunsuan']=="+"){
        	    		$danjia=round($rs['vipprice']+$row['addprice'],2);
        	    		$danjia1=$danjia;
        	    	}else{
        	    		$danjia=round($rs['vipprice']*$row['addprice'],2);
        	    		$danjia1=$danjia;
        	    	}
                } else {
                    $danjia = round($rs['price'] * $userrow['addprice'], 2);
                    $danjia1=$danjia;
                }
            }
        
	     	
	     	
	     	
	     	
	     	
	     	
	     	
	     	
	     	
	     	
	     	
	     	
	     	
	     	
	     	
	     	
	     	
	     	
	     	
	     	
    //   	    if($rs['yunsuan']=="*"){
	   // 		$danjia=round($rs['price']*$row['addprice'],2);
	   // 		$danjia1=$danjia;
	   // 	}elseif($rs['yunsuan']=="+"){
	   // 		$danjia=round($rs['price']+$row['addprice'],2);
	   // 		$danjia1=$danjia;
	   // 	}else{
	   // 		$danjia=round($rs['price']*$row['addprice'],2);
	   // 		$danjia1=$danjia;
	   // 	}
	    	//密价
	    	$mijia=$DB->get_row("select * from qingka_wangke_mijia where uid='{$uid}' and cid='{$platform}' ");
	        if($mijia){
	            if ($mijia['mode']==0) {
	                $danjia=round($danjia-$mijia['price'],2);
	                if ($danjia<=0) {
	                    $danjia=0;
	                }
	            }elseif ($mijia['mode']==1) {
	                $danjia=round(($rs['price']-$mijia['price'])*$row['addprice'],2);
	                if ($danjia<=0) {
	                    $danjia=0;
	                }
	            }elseif ($mijia['mode']==2) {
	                $danjia=$mijia['price'];
	                if ($danjia<=0) {
	                    $danjia=0;
	                }
	            }
	        }	
	        if ($danjia>=$danjia1) {//密价价格大于原价，恢复原价
	            $danjia=$danjia1;
	        }
            if($danjia==0 || $row['addprice']<0.1){
            	exit('{"code":-1,"msg":"大佬，我得罪不起您，我小本生意，有哪里得罪之处，还望多多包涵"}');
            } 
		    if($res['pt']=='wkm4'){
		    	$m4=getWk($rs['queryplat'],$rs['getnoun'],$school,$user,$pass,$rs['name']);
		    	    if($m4['code']=='1'){
                    	 for($i=0;$i<count($m4['data']);$i++){
                    	 	$kcid=$m4['data'][$i]['id'];
                    	 	$kcname1=$m4['data'][$i]['name'];
                            if($kcname1==$kcname){
                            	break;
                            }else{
                            	exit('{"code":-1,"msg":"请完整输入课程名字","status":-1,"message":"请完整输入课程名字"}');
                            } 
                    	 }
                   }
		    }

	     	$c=explode(",",$kcname);
	     	$d=explode(",",$kcid);
	     	for($i=0;$i<count($c);$i++){
     		   if($row['money']<$danjia*count($c)){
		        	exit('{"code":-1,"msg":"余额不足以本次提交"}');
		        	return;
		        }
		       if($DB->get_row("select * from qingka_wangke_order where ptname='{$rs['name']}' and school='$school' and user='$user' and pass='$pass' and kcid='$kcid' and kcname='$kcname' ")){
                           $dockstatus='3';//重复下单
                           		die('{"code":-1,"msg":"重复下单，请取消订单再试！"}');
                           
                           
	           }elseif($rs['docking']=='0'){$dockstatus='99';}else{$dockstatus='0';}	      
			   $is=$DB->query("insert into qingka_wangke_order (uid,cid,hid,ptname,school,user,pass,kcid,kcname,fees,noun,miaoshua,addtime,ip,dockstatus,docknum) values ('{$uid}','{$rs['cid']}','{$rs['docking']}','{$rs['name']}','{$school}','$user','$pass','$d[$i]','$c[$i]','{$danjia}','{$rs['noun']}','$miaoshua','$date','$clientip','$dockstatus',0) ");//将对应课程写入数据库	               	               
	           if($is){
	           	  $DB->query("update qingka_wangke_user set money=money-'{$danjia}' where uid='{$row['uid']}' limit 1 "); 
	              wlog($row['uid'],"API添加任务","{$user} {$pass} {$c[$i]} 扣除{$danjia}元！",-$danjia);
	              $ok=1;
	              
	              $Res = $DB->get_row("select * from qingka_wangke_order where ptname='{$rs['name']}' and school='$school' and user='$user' and pass='$pass' and kcid='$kcid' and kcname='$kcname'  order by oid desc limit 1;"); 
                 $oid = $Res['oid'];
	           }  			     		
	     	}
            if($ok==1){
        // 	 	exit('{"code":0,"msg":"提交成功","status":0,"message":"提交成功","id":"订单号登录后台自行查看，老子懒得写了"}');
        	 	exit('{"code":0,"msg":"提交成功","status":0,"message":"提交成功","id":"'.$oid.'"}');
        	 }else{
        	 	exit('{"code":-1,"msg":"请完整输入课程名字","status":-1,"message":"请完整输入课程名字"}');
        	 }
         }
  break;
  case 'getadd'://查询判断下单
       $uid=daddslashes($_POST['uid']);
       $key=daddslashes($_POST['key']);
       $platform=daddslashes($_POST['platform']);
       $school=daddslashes($_POST['school']);
       $user=daddslashes($_POST['user']);
       $pass=daddslashes($_POST['pass']);
       $kcname=daddslashes($_POST['kcname']);
       $miaoshua=0;
       $clientip=real_ip();
        if($uid=='' || $key=='' || $platform=='' || $school=='' || $user=='' || $pass=='' || $kcname==''){
     	   exit('{"code":0,"msg":"所有项目不能为空"}');
        }
        $row=$DB->get_row("select * from qingka_wangke_user where uid='$uid' limit 1");
	    if($row['key']=='0'){
            exit('{"code":-1,"msg":"你还没有开通接口哦"}');
	    }if($row['key']!=$key){
            exit('{"code":-2,"msg":"密匙错误"}');
	    }else{
	     	$rs=$DB->get_row("select * from qingka_wangke_class where cid='$platform' limit 1 ");
	     	//$danjia=$rs['price']*$row['addprice'];
	     	
       	    if($rs['yunsuan']=="*"){
	    		$danjia=round($rs['price']*$row['addprice'],2);
	    	}elseif($rs['yunsuan']=="+"){
	    		$danjia=round($rs['price']+$row['addprice'],2);
	    	}else{
	    		$danjia=round($rs['price']*$row['addprice'],2);
	    	}
	     	
            if($danjia==0 || $row['addprice']<0.1){
            	exit('{"code":-1,"msg":"大佬，我得罪不起您，我小本生意，有哪里得罪之处，还望多多包涵"}');
            } 
        	if($row['money']<$danjia){
	        	exit('{"code":-1,"msg":"余额不足"}');
	        } 	    
               $a=getWk($rs['queryplat'],$rs['getnoun'],$school,$user,$pass,$rs['name']);
	     		
                    if($a['code']=='1'){
                    	 for($i=0;$i<count($a['data']);$i++){
                    	 	$kcid1=$a['data'][$i]['id'];
                    	 	$kcname1=$a['data'][$i]['name'];
                    	 	similar_text($kcname1,$kcname,$percent);
                    	 	if($percent>"90%"){
        	 	          	    if($rs['yunsuan']=="*"){
						    		$danjia=round($rs['price']*$row['addprice'],2);
						    	}elseif($rs['yunsuan']=="+"){
						    		$danjia=round($rs['price']+$row['addprice'],2);
						    	}else{
						    		$danjia=round($rs['price']*$row['addprice'],2);
						    	}		     
		           	           if($rs['docking']=='0'){
		           	            	$dockstatus='99';
		           	           }else{
		           	           	    $dockstatus='0';
		           	           }	           	           
		           	           $DB->query("insert into qingka_wangke_order (uid,cid,hid,ptname,school,user,pass,kcid,kcname,fees,noun,miaoshua,addtime,ip,dockstatus) values ('{$uid}','{$rs['cid']}','{$rs['docking']}','{$rs['name']}','{$school}','$user','$pass','$kcid1','$kcname1','{$danjia}','{$rs['noun']}','$miaoshua','$date','$clientip','$dockstatus') ");//将对应课程写入数据库	               	           	              	           	               
	           	               $DB->query("update qingka_wangke_user set money=money-'{$danjia}' where uid='$uid' limit 1 "); 
                               wlog($row['uid'],"API添加任务","{$user} {$pass} {$kcname} 扣除{$danjia}元！",-$danjia);
                               $ok=1;
                               break;
                    	 	}
                           
                    	 }
                    	 if($ok==1){
                    	 	exit('{"code":0,"msg":"提交成功","status":0,"message":"提交成功","id":"订单号登录后台自行查看，老子懒得写了"}');
                    	 }else{
                    	 	exit('{"code":-1,"msg":"请完整输入课程名字","status":-1,"message":"请完整输入课程名字"}');
                    	 }
                    	
                    }else{                    	
                    	$result=array("code"=>-1,'msg'=>$a[0]['msg']);
                    	exit(json_encode($result));
                    }
  
                  
       }
  break;
//   case 'chadan':
  case 'status':
//       $username=trim(strip_tags(daddslashes($_POST['username'])));
//       if($username==""){
//       	    $data=array('code'=>-1,'msg'=>"账号不能为空");
// 		    exit(json_encode($data)); 
//       }
//       $a=$DB->query("select * from qingka_wangke_order where user='$username' order by oid desc ");
//       if($a){
// 	       while($row=$DB->fetch($a)){
// 		   	   $data[]=array(
// 		   	      'id'=>$row['oid'],
// 	              'ptname'=>$row['ptname'],
// 	              'school'=>$row['school'],
// 	              'name'=>$row['name'],
// 	              'user'=>$row['user'],
// 	              'kcname'=>$row['kcname'],
// 	              'addtime'=>$row['addtime'],
// 	              'courseStartTime'=>$row['courseStartTime'],
// 	              'courseEndTime'=>$row['courseEndTime'],
// 	              'examStartTime'=>$row['examStartTime'],
// 	              'examEndTime'=>$row['examEndTime'],
// 	              'status'=>$row['status'],
// 	              'process'=>$row['process'],
// 	              'remarks'=>$row['remarks']
// 		   	   );
// 		    }
// 		    $data=array('code'=>1,'data'=>$data);
// 		    exit(json_encode($data)); 
// 	    }else{
// 	    	$data=array('code'=>-1,'msg'=>"未查到该账号的下单信息");
// 		    exit(json_encode($data)); 
// 	    } 
//   break;

  case 'chadan':
           $username=trim(strip_tags(daddslashes($_POST['username'])));
      
      $id=trim(strip_tags(daddslashes($_REQUEST['oid'])));
      if($username==""){
            if($id == ""){
                $data=array('code'=>-1,'msg'=>"账号不能为空");
                exit(json_encode($data)); 
            }else if($id == ""){
                $data=array('code'=>-1,'msg'=>"订单ID不能为空");
                exit(json_encode($data)); 
            }
              
      }
  
      
if($username != ""){
          $a=$DB->query("select * from qingka_wangke_order where user='$username' order by oid desc ");
      }else if($id != ""){
          $a=$DB->query("select * from qingka_wangke_order where oid='$id'");
      }

  
      if($a){
	       while($row=$DB->fetch($a)){
		   	   $data[]=array(
		   	      'id'=>$row['oid'],
	              'ptname'=>$row['ptname'],
	              'school'=>$row['school'],
	              'name'=>$row['name'],
	              'user'=>$row['user'],
	              'kcname'=>$row['kcname'],
	             
	              'addtime'=>$row['addtime'],
	              'courseStartTime'=>$row['courseStartTime'],
	              'courseEndTime'=>$row['courseEndTime'],
	              'examStartTime'=>$row['examStartTime'],
	              'examEndTime'=>$row['examEndTime'],
	              'status'=>$row['status'],
	              'process'=>$row['process'],
	              'remarks'=>$row['remarks']
		   	   );
		    }
		    $data=array('code'=>1,'data'=>$data);
		    if ($data['data']==null) {
		        $data=array('code'=>-1,'msg'=>"未查到该账号的下单信息");
		        exit(json_encode($data)); 
		    }
		    exit(json_encode($data)); 
	    }else{
	    	$data=array('code'=>-1,'msg'=>"未查到该账号的下单信息");
		    exit(json_encode($data)); 
	    } 
  break;
  case 'budan':
        $oid=daddslashes($_POST['id']);
		$b=$DB->get_row("select * from qingka_wangke_order where oid='{$oid}' ");
		if($b['bsnum']>100){
			exit('{"code":-1,"msg":"该订单补刷已超过100次，年轻人，要讲武德，我劝你好自为之"}');
		}
        	  $c=budanWk($oid);
        	  if($c['code']==1){
        	  	$DB->query("update qingka_wangke_order set status='补刷中',`bsnum`=bsnum+1 where oid='{$oid}' ");
        	  	jsonReturn(1,$c['msg']);
        	  }else{
        	  	jsonReturn(-1,$c['msg']);
        	  }          
	   
  break;


	 case 'upjd':
        $oid=daddslashes($_POST['id']);
	    $row=$DB->get_row("select hid from qingka_wangke_order where oid='{$oid}' ");
       if($row['hid']=='ximeng'){
         	exit('{"code":-2,"msg":"当前订单接口异常，请去查询补单","url":""}');
       }elseif($row['dockstatus']=='99'){
       	    $result=pre_zy($oid);
       	    exit(json_encode($result));
       }       	     
	       $result=processCx($oid);
	       for($i=0;$i<count($result);$i++){
	        	$a=$DB->query("update qingka_wangke_order set `yid`='{$result[$i]['yid']}',`status`='{$result[$i]['status_text']}',`courseStartTime`='{$result[$i]['kcks']}',`courseEndTime`='{$result[$i]['kcjs']}',`examStartTime`='{$result[$i]['ksks']}',`name`='{$result[$i]['name']}',`examEndTime`='{$result[$i]['ksjs']}',`process`='{$result[$i]['process']}',`remarks`='{$result[$i]['remarks']}' where `user`='{$result[$i]['user']}' and `pass`='{$result[$i]['pass']}' and `kcname`='{$result[$i]['kcname']}' and `oid`='{$oid}'");    	             
	       }
	       exit('{"code":1,"msg":"同步成功，请重新查询信息"}');
  break;





  case 'update':
     	$oid=daddslashes($_POST['id']);
     	$user=daddslashes($_POST['user']);
           $row=$DB->get_row("select * from qingka_wangke_order where oid='$oid'");
           if ($row['user'] != $user){
               exit('{"code":-1,"msg":"不是你的单子你同步个几把"}');
           }
           if($row['hid']=='ximeng'){
             	exit('{"code":-2,"msg":"当前订单接口异常，请去查询补单","url":""}');
           }elseif($row['dockstatus']=='99'){
           	    //$result=pre_zy($oid);
           	    //exit(json_encode($result));
           	    jsonReturn(1,'实时进度，无需更新');
           }       	     
    	       $result=processCx($oid);
    	       for($i=0;$i<count($result);$i++){
    	        	$DB->query("update qingka_wangke_order set `name`='{$result[$i]['name']}',`yid`='{$result[$i]['yid']}',`status`='{$result[$i]['status_text']}',`courseStartTime`='{$result[$i]['kcks']}',`courseEndTime`='{$result[$i]['kcjs']}',`examStartTime`='{$result[$i]['ksks']}',`examEndTime`='{$result[$i]['ksjs']}',`process`='{$result[$i]['process']}',`remarks`='{$result[$i]['remarks']}' where `user`='{$result[$i]['user']}' and `kcname`='{$result[$i]['kcname']}' and `oid`='{$oid}'");
    	       }
    	       exit('{"code":1,"msg":"同步成功"}');
  break;
//   case 'getclass':
case 'goods':
//      	$a=$DB->query("select * from qingka_wangke_class where status=1 ");
// 	    while($row=$DB->fetch($a)){
// 	   	   $data[]=array(
// 	   	        'cid'=>$row['cid'],
// 	   	        'name'=>$row['name'],
// 	   	        'price'=>$row['price'],
// 	   	        'content'=>$row['content'],
// 	   	        'sort' => $row['sort']
// 	   	   );
// 	    }
// 	    $data=array('code'=>1,'data'=>$data);
// 	    exit(json_encode($data));
//   break;
  
  
case 'getclass':
        $uid = trim(strip_tags(daddslashes($_POST['uid'])));
        $key = trim(strip_tags(daddslashes($_POST['key'])));
        $fenlei = trim(strip_tags(daddslashes($_POST['fenlei'])));
        if ($uid == '' || $key == '') {
            exit('{"code":0,"msg":"所有项目不能为空"}');
        }
        $userrow = $DB->get_row("select * from qingka_wangke_user where uid='$uid' limit 1");
        if ($userrow['key'] == '0') {
            $result = array("code" => -1, "msg" => "你还没有开通接口哦");
            exit(json_encode($result));
        } elseif ($userrow['key'] != $key) {
            $result = array("code" => -2, "msg" => "密匙错误");
            exit(json_encode($result));
        }
        if ($fenlei!='') {
            $sql="and fenlei={$fenlei}";
        }
        if ($_REQUEST['cid']) {
            $a = $DB->query("select * from qingka_wangke_class where status=1 and cid = '{$_REQUEST['cid']}' {$sql} order by sort desc");
        } else {
            $a = $DB->query("select * from qingka_wangke_class where status=1 {$sql} order by sort desc");
        }
        while ($row = $DB->fetch($a)) {
            if ($row['yunsuan'] == "*") {
                $price = round($row['price'] * $userrow['addprice'], 2);
                $price1 = $price;
            } elseif ($row['yunsuan'] == "+") {
                $price = round($row['price'] + $userrow['addprice'], 2);
                $price1 = $price;
            } else {
                $price = round($row['price'] * $userrow['addprice'], 2);
                $price1 = $price;
            }
            //密价
	    	$mijia=$DB->get_row("select * from qingka_wangke_mijia where uid='{$userrow['uid']}' and cid='{$row['cid']}' ");
	        if($mijia){
	            if ($mijia['mode']==0) {
	                $price=round($price-$mijia['price'],2);
	                if ($price<=0) {
	                    $price=0;
	                }
	            }elseif ($mijia['mode']==1) {
	                $price=round(($row['price']-$mijia['price'])*$userrow['addprice'],2);
	                if ($price<=0) {
	                    $price=0;
	                }
	            }elseif ($mijia['mode']==2) {
	                $price=$mijia['price'];
	                if ($price<=0) {
	                    $price=0;
	                }
	            }
	        	$row['name']="{$row['name']}";
	        }	
	        
	        //全站一个价
	    	if($row['suo']!=0){
	            $price=$row['suo'];
	        }
            $data[] = array(
                'sort' => $row['sort'],
                'cid' => $row['cid'],
                'kcid' => $row['nokcid'],
                'name' => $row['name'],
                // 'noun' => $row['noun'],
                'price' => "{$price}",
                'content' => $row['content'],
                'status' => $row['status'],
                'fenlei' => $row['fenlei'],
            );
        }
        foreach ($data as $key => $row) {
            $sort[$key]  = $row['sort'];
            $cid[$key] = $row['cid'];
            $name[$key] = $row['name'];
            
            //$noun[$key] = $row['noun'];
            $price[$key] = $row['price'];
            $content[$key] = $row['content'];
            $status[$key] = $row['status'];
        }
        array_multisort($sort, SORT_ASC, $cid, SORT_DESC, $data);
        $data = array('code' => 1, 'data' => $data);
        exit(json_encode($data));
        break;


  
  
  
  
  
  
  case 'zt'://暂停订单
        $uid=daddslashes($_POST['uid']);
        $key=trim(strip_tags(daddslashes($_POST['key'])));
        $row=$DB->get_row("select * from qingka_wangke_user where uid='$uid' limit 1");
        $oid=daddslashes($_POST['id']);
		$b=$DB->get_row("select * from qingka_wangke_order where oid='{$oid}' ");
		if($uid=='' || $key==''){
     	   exit('{"code":0,"msg":"所有项目不能为空"}');
    }   
        	  $c=ztWk($oid);
        	  if($c['code']==1){
        	  	$DB->query("update qingka_wangke_order set status='已暂停',`bsnum`=bsnum+1 where oid='{$oid}' ");
        	  	wlog($row['uid'], "API暂停", "订单{$oid}已暂停", 0);
        	  	jsonReturn(1,$c['msg']);
        	  }else{
        	  	jsonReturn(-1,$c['msg']);
        	  }          
	   
  break;
    case 'xgmm'://修改密码
    $key=trim(strip_tags(daddslashes($_POST['key'])));
    $xgmm = trim(strip_tags(daddslashes($_POST['xgmm'])));
    $uid=daddslashes($_POST['uid']);
    $row=$DB->get_row("select * from qingka_wangke_user where uid='$uid' limit 1");
    $oid=daddslashes($_POST['id']);
        if (empty($xgmm)) {
        jsonReturn(-1, "密码不能为空");
    }
        if (strlen($xgmm) < 3) {
        jsonReturn(-1, "密码长度至少为3位");
    }   
        if($uid=='' || $key==''){
     	   exit('{"code":0,"msg":"所有项目不能为空"}');
    }   else {
			$b = xgmm($oid,$xgmm);
			if ($b['code'] == 1) {
              
              $DB->query("UPDATE qingka_wangke_order SET pass = '{$xgmm}' WHERE oid = '{$oid}'");
              $DB->query("update qingka_wangke_user set money=money-0.01 where uid='{$row['uid']}' limit 1 ");
				wlog($row['uid'], "API修改密码", "订单{$oid}修改密码成功扣除0.01", -0.01);
				jsonReturn(1, $b['msg']);
			} else {
				jsonReturn(-1, $b['msg']);
			}
		}
    break;
    
       case 'ms_order'://列表提交秒刷
       $key=trim(strip_tags(daddslashes($_POST['key'])));
       $oid=daddslashes($_POST['id']);
       $b = $DB->get_row("select cid,dockstatus from qingka_wangke_order where oid='{$oid}' ");
       $uid=daddslashes($_POST['uid']);
       $row=$DB->get_row("select * from qingka_wangke_user where uid='$uid' limit 1");
       if($uid=='' || $key==''){
     	   exit('{"code":0,"msg":"所有项目不能为空"}');
    }    else {
			$b = msWk($oid);
			if ($b['code'] == 1) {
              $DB->query("update qingka_wangke_user set money=money-0.02 where uid='{$row['uid']}' limit 1 ");
				wlog($row['uid'], "API提交秒刷", "订单{$oid}提交秒刷成功扣除0.02", -0.02);
				jsonReturn(1, $b['msg']);
			} else {
				jsonReturn(-1, $b['msg']);
			}
		}
    break;
}

?>