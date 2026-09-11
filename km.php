<?php
include('confing/common.php');
$act=isset($_GET['act'])?daddslashes($_GET['act']):null;
@header('Content-Type: application/json; charset=UTF-8');



if (CONFIG_KEY !== '8848') {
	msg('配置文件损坏或缺失');
}


if (!checkRefererHost()) {
    exit("{\"code\":403}");
}

switch($act){
  case 'kmlist': // 卡密列表
    if($userrow['uid']!=1){
      jsonReturn(-1,"您暂无此权限");
    }
    $a=$DB->query("select * from qingka_wangke_km order by id desc");
    while($row=$DB->fetch($a)){
      $data[]=array(
        'id'=>$row['id'],
        'content'=>$row['content'],
        'money'=>$row['money'],
        'status'=>$row['status'],
        'uid'=>$row['uid'],
        'addtime'=>$row['addtime'],
        'usedtime'=>$row['usedtime'],
        'batch_id' => $row['batch_id']
      );
    }
    $data=array('code'=>1,'msg'=>'获取成功','data'=>$data);
    exit(json_encode($data));
  break;
  case 'addkm': // 添加卡密
    $content = trim(strip_tags(daddslashes($_POST['content'])));
    $money = trim(strip_tags(daddslashes($_POST['money'])));
    $batch_id = trim(strip_tags(daddslashes($_POST['batch_id']))); // 接收 batch_id 字段

    if ($userrow['uid'] != 1) {
      jsonReturn(-1, "您暂无此权限");
    }
    if ($content == '') {
      exit('{"code":-1,"msg":"卡密不能为空"}');
    }
    if ($money == '') {
      exit('{"code":-1,"msg":"卡密的余额不能为空"}');
    }
    if ($batch_id == '') {
      exit('{"code":-1,"msg":"批次ID不能为空"}');
    }
    
    $content= $content . '_' . $money;
    $ishas = $DB->get_row("SELECT * FROM qingka_wangke_km WHERE content='$content' LIMIT 1");
    if ($ishas) {
      exit('{"code":-1,"msg":"该卡密已存在"}');
    }

    $DB->query("INSERT INTO qingka_wangke_km (content, money, status, addtime, batch_id) VALUES ('$content', '$money', 0, NOW(), '$batch_id')");

    $data = array(
      'code' => 1,
      'msg' => '添加成功',
      'content' => $content
    );
    exit(json_encode($data));
    break;
  case 'querykm': // 查询卡密
    $content=trim(strip_tags(daddslashes($_POST['content'])));
//           if($userrow['uuid']!=1){
//      exit('{"code":-1,"msg":"无权限使用请联系上级充值"}');
//   }
    if($content==''){
      exit('{"code":-1,"msg":"卡密不能为空"}');
    }
    $a=$DB->query("select * from qingka_wangke_km where content='$content' ");
    while($row=$DB->fetch($a)){
      $data[]=array(
        'id'=>$row['id'],
        'content'=>$row['content'],
        'money'=>$row['money'],
        'status'=>$row['status'],
        'uid'=>$row['uid'],
        'addtime'=>$row['addtime'],
        'usedtime'=>$row['usedtime']
      );
    }
    if($data == null) {
      exit('{"code":-1,"msg":"卡密不存在"}');
      
    }
    $data=array('code'=>1,'msg'=>'查询成功','data'=>$data);
    
    exit(json_encode($data));
  break;
  case 'paykm': // 使用卡密
    $content = trim(strip_tags(daddslashes($_POST['content'])));
    $uid = trim(strip_tags(daddslashes($_POST['uid']))) ? trim(strip_tags(daddslashes($_POST['uid']))) : $userrow['uid'];

    if ($userrow['money'] <= 1) {
      exit('{"code":-1,"msg":"余额低于1积分,无权使用福利卡密"}');
    }
    if ($content == '') {
      exit('{"code":-1,"msg":"卡密不能为空"}');
    }
    if ($uid == '') {
      exit('{"code":-1,"msg":"给谁充值啊？"}');
    }

    $km = $DB->get_row("SELECT * FROM qingka_wangke_km WHERE content='$content' LIMIT 1");
    if (!$km) {
      exit('{"code":-1,"msg":"卡密不存在"}');
    }
    if ($km['status'] != 0) {
        wlog($userrow['uid'], "卡密充值", "尝试卡密充值失败,该卡密已被使用过了", 0);
      exit('{"code":-1,"msg":"该卡密已被使用过了"}');
      
    }

    $user = $DB->get_row("SELECT * FROM qingka_wangke_user WHERE uid='$uid' LIMIT 1");
    if ($user['active'] != '1') {
      exit('{"code":-1,"msg":"该用户账户状态异常，无法进行充值"}');
    }

    // 检查用户是否已经使用过该批次的卡密
    $batch_id = $km['batch_id'];
    $used_batches = explode(',', $user['used_batches']);
    if (in_array($batch_id, $used_batches)) {
        wlog($userrow['uid'], "卡密充值", "尝试卡密充值失败，你已经使用过本次活动卡密了", 0);
      exit('{"code":-1,"msg":"大哥 给别人留点机会！别人还没用呢，你已经用了本次活动卡密！"}');
      
    }

    $kmmoney = round($km['money'], 2);
    $useradd = round($user['money'] + $kmmoney, 2);
    $DB->query("UPDATE qingka_wangke_user SET money=money-'$kmmoney' WHERE uid='1'"); // 我的扣费
    $DB->query("UPDATE qingka_wangke_user SET money='$useradd', zcz=zcz+'$kmmoney' WHERE uid='$uid'"); // 下级增加

    // 更新用户的 used_batches 字段
    $used_batches[] = $batch_id;
    $used_batches_str = implode(',', $used_batches);
    $DB->query("UPDATE qingka_wangke_user SET used_batches='$used_batches_str' WHERE uid='$uid'");

    // 设置卡密状态
    $DB->query("UPDATE qingka_wangke_km SET `status`=1, `uid`='$uid', `usedtime`=NOW() WHERE id='{$km['id']}'");
    
    $data = array(
      'code' => 1,
      'msg' => '使用成功',
      'uid' => $uid,
      'name' => $user['name'],
      'usermoney' => $useradd,
      'kmmoney' => $km['money']
    );
    wlog($userrow['uid'], "卡密充值", "卡密充值成功", +$kmmoney);
    exit(json_encode($data));
    break;
  
  case 'deletekm': // 删除卡密
    $id=trim(strip_tags(daddslashes($_POST['id'])));
    if($userrow['uid']!=1){
      jsonReturn(-1,"您暂无此权限");
   }
    if($id==''){
      exit('{"code":-1,"msg":"卡密不能为空"}');
    }
    $DB->query("delete from qingka_wangke_km where id='$id' ");
    $data=array(
      'code'=>1,
      'msg'=>'删除成功'
    );
    exit(json_encode($data));
  break;
}

?>
