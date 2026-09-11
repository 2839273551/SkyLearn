<?php


include('confing/common.php');
include('ayconfig.php');

// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);






if (CONFIG_KEY !== '8848') {
    
	 jsonReturn(-1,"文件损害，已记录");
}



$redis=new Redis();
$redis->connect("127.0.0.1","6379");




// 尝试从多个来源获取act参数
$act = null;
if (isset($_GET['act']) && !empty($_GET['act'])) {
    $act = trim($_GET['act']);
} elseif (isset($_POST['act']) && !empty($_POST['act'])) {
    $act = trim($_POST['act']);
} else {
    // 尝试从REQUEST_URI中解析
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('/[?&]act=([^&]+)/', $uri, $matches)) {
        $act = trim($matches[1]);
    }
}

// 如果仍然没有act参数，检查原始输入
if (empty($act)) {
    $raw_input = file_get_contents('php://input');
    if (!empty($raw_input)) {
        parse_str($raw_input, $parsed_data);
        if (isset($parsed_data['act'])) {
            $act = trim($parsed_data['act']);
        }
    }
}






switch ($act) {
	
	

    
    case 'adduser':
	    if($conf['user_htkh']=='0'){
	    	jsonReturn(-1,"暂停开户，具体开放时间等通知");
	    }
        parse_str(daddslashes($_POST['data']),$row);
        $type=daddslashes($_POST['type']);
        $row['user'] = trim($row['user']); 
        $row['pass'] = trim($row['pass']); 
        $row['addpriceid']= trim($row['addpriceid']);
        if($row['name']=='' || $row['user']==''|| $row['pass']==''||$row['addpriceid']==''){
        	exit('{"code":-2,"msg":"所有项目不能为空"}');
        }
        $a=$DB->get_row("select * from qingka_wangke_dengji where id='{$row['addpriceid']}'");
        $row['addprice']=$a['rate'];
        // 匹配QQ号码的正则表达式
$pattern = "/^[1-9]\d{4,14}$/";

if (!preg_match($pattern, $row['user'])) {
    exit('{"code":-1,"msg":"账号必须为QQ号码"}');
}
        if($DB->get_row("select * from qingka_wangke_user where user='{$row['user']}' ")){
	  	    exit('{"code":-1,"msg":"该账号已存在"}');
	    }
	    if($DB->get_row("select * from qingka_wangke_user where name='{$row['name']}' ")){
	  	    exit('{"code":-1,"msg":"该昵称已存在"}');
	    }		

		if($row['addprice']<$userrow['addprice']){
			exit('{"code":-1,"msg":"费率不能比自己低哦"}');
		}
		
		if($row['addprice']*100 % 5 !=0){
    		jsonReturn(-1,"请输入单价为0.05的倍数");
	    }
			$cz=0;
			if ($a['addkf']==1) {
			    $cz=$a['money'];
			}
            $kochu=round($cz*($userrow['addprice']/$row['addprice']),2);
		    $kochu2=$kochu+$conf['user_ktmoney'];
		    if($type!=1){
        	   jsonReturn(1,"开通扣{$conf['user_ktmoney']}元开户费，并自动给下级充值{$cz}元，将扣除{$kochu}余额");
            }
			if($userrow['money']>=$kochu2){
	           $DB->query("insert into qingka_wangke_user (uuid,user,pass,name,addprice,addtime) values ('{$userrow['uid']}','{$row['user']}','{$row['pass']}','{$row['name']}','{$row['addprice']}','$date') ");
	           $DB->query("update qingka_wangke_user set `money`=`money`-'{$conf['user_ktmoney']}' where uid='{$userrow['uid']}' ");
	           wlog($userrow['uid'],"添加商户","添加商户{$row['user']}成功!扣费{$conf['user_ktmoney']}元!","-{$conf['user_ktmoney']}");          
	           if($cz!=0){
	           	 $DB->query("update qingka_wangke_user set money='$cz',zcz=zcz+'$cz' where user='{$row['user']}' ");
	           	 $DB->query("update qingka_wangke_user set `money`=`money`-'$kochu' where uid='{$userrow['uid']}' ");
	           	 wlog($userrow['uid'],"代理充值","成功给账号为[{$row['user']}]的靓仔充值{$cz}元,扣除{$kochu}元",-$kochu);
	             $is=$DB->get_row("select uid from qingka_wangke_user where user='{$row['user']}' limit 1");
	             wlog($is['uid'],"上级充值","你上面的靓仔[{$userrow['name']}]成功给你充值{$cz}元",+$cz);
	           }
	           exit('{"code":1,"msg":"添加成功"}');
		   }else{ 
		    	jsonReturn(-1,"余额不足开户，开户需扣除开户费{$conf['user_ktmoney']}元，及余额{$kochu}元");		    	
		    }
		
    break;


case 'gethuoyuan':
        // 获取所有货源列表
        try {
            $data = [];
            $query = $DB->query("SELECT * FROM qingka_wangke_huoyuan WHERE status=1 ORDER BY hid ASC");
            
            if ($query) {
                while ($row = $DB->fetch($query)) {
                    $data[] = [
                        'hid' => $row['hid'],
                        'name' => $row['name'],
                        'pt' => $row['pt'],
                        'url' => $row['url'],
                        'status' => $row['status']
                    ];
                }
            }
            
            exit(json_encode(['code' => 1, 'msg' => '获取成功', 'data' => $data]));
        } catch (Exception $e) {
            exit(json_encode(['code' => -1, 'msg' => '数据库错误: ' . $e->getMessage()]));
        }
        break;
        
    case 'getclasss':
        $hid = trim(strip_tags(daddslashes($_POST['hid'])));
        $huoyuan = validateAndGetHuoyuan($hid);
        
        $api_result = callHuoyuanAPI($huoyuan, 'getclass');
        
        if (!$api_result['success']) {
            exit(json_encode(['code' => -1, 'msg' => 'API调用失败', 'debug' => $api_result['error']]));
        }
        
        $result = $api_result['data'];
        
        // 检查每个课程的上架状态
        if (isset($result['data']) && is_array($result['data'])) {
            foreach ($result['data'] as &$item) {
                if (isset($item['cid'])) {
                    $safe_cid = daddslashes($item['cid']);
                    $existing = $DB->get_row("SELECT cid FROM qingka_wangke_class WHERE (noun='$safe_cid' OR getnoun='$safe_cid') AND docking='$hid' LIMIT 1");
                    $item['is_online'] = $existing ? 1 : 0;
                } else {
                    $item['is_online'] = 0;
                }
            }
        }
        
        exit(json_encode($result));
        break;
        
    case 'copyfenlei':
        try {
            $hid = trim(strip_tags(daddslashes($_POST['hid'])));
            $copy_mode = trim(strip_tags(daddslashes($_POST['copy_mode'] ?? 'fenlei_only')));
            $huoyuan = validateAndGetHuoyuan($hid);
        
            $api_result = callHuoyuanAPI($huoyuan, 'getclass');
        
            if (!$api_result['success']) {
                exit(json_encode(['code' => -1, 'msg' => 'API调用失败', 'debug' => $api_result['error']]));
            }
        
            $result = $api_result['data'];
            if (!$result || $result['code'] != 1) {
                exit(json_encode(['code' => -1, 'msg' => '获取分类数据失败']));
            }
        
            // 提取所有分类名称
            $fenlei_names = [];
            if (isset($result['data']) && is_array($result['data'])) {
                foreach ($result['data'] as $item) {
                    if (isset($item['fenleiname']) && !empty($item['fenleiname'])) {
                        $fenlei_name = trim($item['fenleiname']);
                        if (!in_array($fenlei_name, $fenlei_names)) {
                            $fenlei_names[] = $fenlei_name;
                        }
                    }
                }
            }
            
            if (empty($fenlei_names)) {
                exit(json_encode(['code' => -1, 'msg' => '未找到有效的分类数据']));
            }
            
            // 开始处理数据
            $fenlei_success_count = 0;
            $fenlei_skip_count = 0;
            $class_success_count = 0;
            $class_skip_count = 0;
            
            // 创建分类名称到ID的映射
            $fenlei_map = [];
            
            foreach ($fenlei_names as $fenlei_name) {
                $safe_name = daddslashes($fenlei_name);
                
                // 检查分类是否已存在
                $existing = $DB->get_row("SELECT id FROM qingka_wangke_fenlei WHERE name='$safe_name' LIMIT 1");
                if ($existing) {
                    $fenlei_skip_count++;
                    $fenlei_map[$fenlei_name] = $existing['id'];
                    continue;
                }
                
                // 获取最大排序值
                $max_sort = $DB->get_row("SELECT MAX(sort) as max_sort FROM qingka_wangke_fenlei");
                $new_sort = ($max_sort['max_sort'] ?? 0) + 1;
                
                // 插入新分类
                $current_time = date('Y-m-d H:i:s');
                $new_fenlei_id = $DB->insert("INSERT INTO qingka_wangke_fenlei (sort, name, status, time, mall_custom) VALUES ('$new_sort', '$safe_name', '1', '$current_time', NULL)");
                
                if ($new_fenlei_id) {
                    $fenlei_success_count++;
                    $fenlei_map[$fenlei_name] = $new_fenlei_id;
                }
            }
            
            // 如果选择复制课程数据
            if ($copy_mode === 'fenlei_and_class' && isset($result['data']) && is_array($result['data'])) {
                foreach ($result['data'] as $item) {
                    if (!isset($item['fenleiname']) || !isset($fenlei_map[$item['fenleiname']])) {
                        continue;
                    }
                    
                    $fenlei_id = $fenlei_map[$item['fenleiname']];
                    $safe_cid = daddslashes($item['cid'] ?? '');
                    $safe_name = daddslashes($item['name'] ?? '');
                    $safe_price = daddslashes($item['price'] ?? '0');
                    $safe_content = daddslashes($item['content'] ?? '');
                    
                    // 跳过空名称的课程
                    if (empty($safe_name)) {
                        continue;
                    }
                    
                    // 检查课程是否已存在（将列表cid与数据库noun或getnoun比对）
                    // 避免同一货源平台在同一分类下重复添加相同课程
                    $existing_class = $DB->get_row("SELECT cid FROM qingka_wangke_class WHERE (noun='$safe_cid' OR getnoun='$safe_cid') AND docking='$hid' AND fenlei='$fenlei_id' LIMIT 1");
                    if ($existing_class) {
                        $class_skip_count++;
                        continue;
                    }
                    
                    // 获取最大排序值
                    $max_class_sort = $DB->get_row("SELECT MAX(sort) as max_sort FROM qingka_wangke_class");
                    $new_class_sort = ($max_class_sort['max_sort'] ?? 0) + 1;
                    
                    // 插入课程数据
                    $current_time = date('Y-m-d H:i:s');
                    $sql = "INSERT INTO qingka_wangke_class 
                        (sort, name, getnoun, noun, nocheck, changePass, price, queryplat, docking, yunsuan, content, addtime, status, fenlei, vipprice, vipyunsuan) 
                        VALUES 
                        ('$new_class_sort', '$safe_name', '$safe_cid', '$safe_cid', '0', '0', '$safe_price', '$hid', '$hid', '*', '$safe_content', '$current_time', '1', '$fenlei_id', '$safe_price', '*')";
                    
                    $new_class_id = $DB->insert($sql);
                    
                    if ($new_class_id) {
                        $class_success_count++;
                    }
                }
            }
            
            // 记录日志
            $log_msg = "从货源[{$huoyuan['name']}]复制数据，分类成功：{$fenlei_success_count}个，跳过：{$fenlei_skip_count}个";
            if ($copy_mode === 'fenlei_and_class') {
                $log_msg .= "，课程成功：{$class_success_count}个，跳过：{$class_skip_count}个";
            }
            
            // 构建返回消息
            $msg = "复制完成！成功添加 {$fenlei_success_count} 个分类";
            if ($fenlei_skip_count > 0) {
                $msg .= "，跳过 {$fenlei_skip_count} 个已存在的分类";
            }
            if ($copy_mode === 'fenlei_and_class') {
                $msg .= "，成功添加 {$class_success_count} 个课程";
                if ($class_skip_count > 0) {
                    $msg .= "，跳过 {$class_skip_count} 个已存在的课程";
                }
            }
            
            exit(json_encode([
                'code' => 1, 
                'msg' => $msg,
                'data' => [
                    'fenlei_success_count' => $fenlei_success_count,
                    'fenlei_skip_count' => $fenlei_skip_count,
                    'class_success_count' => $class_success_count,
                    'class_skip_count' => $class_skip_count,
                    'total_fenlei' => count($fenlei_names),
                    'copy_mode' => $copy_mode
                ]
            ]));
            
        } catch (Exception $e) {
            exit(json_encode(['code' => -1, 'msg' => '操作失败: ' . $e->getMessage()]));
        }
        break;
        
    case 'getcategories':
        // 获取所有分类列表
        $data = [];
        $a = $DB->query("SELECT id, name, sort FROM qingka_wangke_fenlei WHERE status=1 ORDER BY sort ASC, id ASC");
        while ($row = $DB->fetch($a)) {
            $data[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'sort' => $row['sort']
            ];
        }
        exit(json_encode(['code' => 1, 'msg' => '获取成功', 'data' => $data]));
        break;
        
    case 'batchonline':
        try {
            // 批量上架课程到数据库
            $hid = trim(strip_tags(daddslashes($_POST['hid'])));
            $courses_json = $_POST['courses'] ?? ''; // 不要对JSON数据使用daddslashes
            $handle_duplicates = $_POST['handle_duplicates'] ?? 'check'; // check=检查重复, skip=跳过重复, update=更新重复
            $category_data_json = $_POST['category_data'] ?? ''; // 分类数据
            
            if (empty($hid)) {
                exit(json_encode(['code' => -1, 'msg' => '货源ID不能为空']));
            }
            
            if (empty($courses_json)) {
                exit(json_encode(['code' => -1, 'msg' => '课程数据不能为空']));
            }
            
            $courses = json_decode($courses_json, true);
            if (!$courses || !is_array($courses)) {
                // 添加调试信息
                $debug_info = [
                    'json_error' => json_last_error_msg(),
                    'raw_data_length' => strlen($courses_json),
                    'raw_data_sample' => substr($courses_json, 0, 200),
                    'handle_duplicates' => $handle_duplicates
                ];
                exit(json_encode(['code' => -1, 'msg' => '课程数据格式错误', 'debug' => $debug_info]));
            }
            
            // 解析分类数据
            $category_data = null;
            if (!empty($category_data_json)) {
                $category_data = json_decode($category_data_json, true);
                if (!$category_data) {
                    exit(json_encode(['code' => -1, 'msg' => '分类数据格式错误']));
                }
            }
            
            // 添加调试日志
            if ($handle_duplicates !== 'check') {
                wlog($userrow['uid'], '重复课程处理调试', "处理模式: {$handle_duplicates}, 课程数量: " . count($courses), 0);
            }
            
            // 获取货源信息
            $huoyuan = $DB->get_row("SELECT * FROM qingka_wangke_huoyuan WHERE hid='$hid' AND status=1 LIMIT 1");
            if (!$huoyuan) {
                exit(json_encode(['code' => -1, 'msg' => '货源不存在或已禁用']));
            }
            
            $success_count = 0;
            $skip_count = 0;
            $error_count = 0;
            $update_count = 0;
            $duplicate_courses = []; // 存储重复的课程
            $error_details = []; // 存储详细错误信息
            
            foreach ($courses as $course) {
                $safe_cid = daddslashes($course['cid'] ?? '');
                $safe_name = daddslashes($course['name'] ?? '');
                $safe_price = floatval($course['price'] ?? 0); // 确保价格是数值型
                $safe_fenlei = daddslashes($course['fenlei'] ?? '');
                $safe_content = daddslashes($course['content'] ?? '');
                
                // 跳过空名称的课程
                if (empty($safe_name)) {
                    $error_count++;
                    $error_details[] = "课程名称为空: CID={$safe_cid}";
                    continue;
                }
                
                // 检查课程是否已存在
                $existing_class = $DB->get_row("SELECT cid, name, price FROM qingka_wangke_class WHERE (noun='$safe_cid' OR getnoun='$safe_cid') AND docking='$hid' LIMIT 1");
                
                if ($existing_class) {
                    // 如果是第一次检查重复，收集重复课程信息
                    if ($handle_duplicates === 'check') {
                        $duplicate_courses[] = [
                            'cid' => $safe_cid,
                            'name' => $safe_name,
                            'new_price' => $safe_price,
                            'old_price' => $existing_class['price'],
                            'old_name' => $existing_class['name'],
                            'price_changed' => ($safe_price != $existing_class['price']),
                            'course_data' => $course
                        ];
                        continue;
                    } elseif ($handle_duplicates === 'skip') {
                        $skip_count++;
                        continue;
                    } elseif ($handle_duplicates === 'update') {
                        // 更新现有课程
                        $fenlei_id = 1; // 默认分类ID
                        
                        // 根据分类数据确定分类ID（与新课程逻辑相同）
                        if ($category_data) {
                            if ($category_data['type'] === 'existing') {
                                $fenlei_id = intval($category_data['category_id']);
                            } elseif ($category_data['type'] === 'custom') {
                                $custom_name = daddslashes($category_data['category_name']);
                                $existing_custom = $DB->get_row("SELECT id FROM qingka_wangke_fenlei WHERE name='$custom_name' LIMIT 1");
                                
                                if ($existing_custom) {
                                    $fenlei_id = $existing_custom['id'];
                                } else {
                                    $max_sort = $DB->get_row("SELECT MAX(sort) as max_sort FROM qingka_wangke_fenlei");
                                    $new_sort = ($max_sort['max_sort'] ?? 0) + 1;
                                    $current_time = date('Y-m-d H:i:s');
                                    
                                    $new_fenlei_id = $DB->insert("INSERT INTO qingka_wangke_fenlei (sort, name, status, time, mall_custom) VALUES ('$new_sort', '$custom_name', '1', '$current_time', NULL)");
                                    if ($new_fenlei_id) {
                                        $fenlei_id = $new_fenlei_id;
                                    }
                                }
                            } else {
                                // 默认分类模式
                                $fenleiname = $course['fenleiname'] ?? '';
                                
                                if (!empty($fenleiname)) {
                                    $safe_fenleiname = daddslashes($fenleiname);
                                    $existing_fenlei = $DB->get_row("SELECT id FROM qingka_wangke_fenlei WHERE name='$safe_fenleiname' LIMIT 1");
                                    if ($existing_fenlei) {
                                        $fenlei_id = $existing_fenlei['id'];
                                    }
                                } elseif (!empty($safe_fenlei)) {
                                    $existing_fenlei = $DB->get_row("SELECT id FROM qingka_wangke_fenlei WHERE id='$safe_fenlei' LIMIT 1");
                                    if ($existing_fenlei) {
                                        $fenlei_id = $safe_fenlei;
                                    }
                                }
                            }
                        } else {
                            // 没有分类数据时使用原有逻辑
                            $fenleiname = $course['fenleiname'] ?? '';
                            
                            if (!empty($fenleiname)) {
                                $safe_fenleiname = daddslashes($fenleiname);
                                $existing_fenlei = $DB->get_row("SELECT id FROM qingka_wangke_fenlei WHERE name='$safe_fenleiname' LIMIT 1");
                                if ($existing_fenlei) {
                                    $fenlei_id = $existing_fenlei['id'];
                                }
                            } elseif (!empty($safe_fenlei)) {
                                $existing_fenlei = $DB->get_row("SELECT id FROM qingka_wangke_fenlei WHERE id='$safe_fenlei' LIMIT 1");
                                if ($existing_fenlei) {
                                    $fenlei_id = $safe_fenlei;
                                }
                            }
                        }
                        
                        $current_time = date('Y-m-d H:i:s');
                        $update_sql = "UPDATE qingka_wangke_class SET 
                            name='$safe_name', 
                            price='$safe_price', 
                            content='$safe_content', 
                            fenlei='$fenlei_id',
                            vipprice='$safe_price',
                            uptime='$current_time'
                            WHERE (noun='$safe_cid' OR getnoun='$safe_cid') AND docking='$hid'";
                        
                        if ($DB->query($update_sql)) {
                            $update_count++;
                        } else {
                            $error_count++;
                            $error_details[] = "更新失败: CID={$safe_cid}, 错误: " . $DB->error();
                        }
                        continue;
                    }
                }
                
                // 处理新课程
                // 根据分类数据确定分类ID
                $fenlei_id = 1; // 默认分类ID
                
                if ($category_data) {
                    if ($category_data['type'] === 'existing') {
                        // 使用指定的已有分类
                        $fenlei_id = intval($category_data['category_id']);
                    } elseif ($category_data['type'] === 'custom') {
                        // 创建或查找自定义分类
                        $custom_name = daddslashes($category_data['category_name']);
                        $existing_custom = $DB->get_row("SELECT id FROM qingka_wangke_fenlei WHERE name='$custom_name' LIMIT 1");
                        
                        if ($existing_custom) {
                            $fenlei_id = $existing_custom['id'];
                        } else {
                            // 创建新分类
                            $max_sort = $DB->get_row("SELECT MAX(sort) as max_sort FROM qingka_wangke_fenlei");
                            $new_sort = ($max_sort['max_sort'] ?? 0) + 1;
                            $current_time = date('Y-m-d H:i:s');
                            
                            $new_fenlei_id = $DB->insert("INSERT INTO qingka_wangke_fenlei (sort, name, status, time) VALUES ('$new_sort', '$custom_name', '1', '$current_time')");
                            if ($new_fenlei_id) {
                                $fenlei_id = $new_fenlei_id;
                            } else {
                                $error_details[] = "创建分类失败: {$custom_name}, 错误: " . $DB->error();
                            }
                        }
                    } else {
                        // 默认分类模式，使用原有分类逻辑
                        $fenleiname = $course['fenleiname'] ?? '';
                        
                        if (!empty($fenleiname)) {
                            // 先尝试根据分类名称查找
                            $safe_fenleiname = daddslashes($fenleiname);
                            $existing_fenlei = $DB->get_row("SELECT id FROM qingka_wangke_fenlei WHERE name='$safe_fenleiname' LIMIT 1");
                            if ($existing_fenlei) {
                                $fenlei_id = $existing_fenlei['id'];
                            }
                        } elseif (!empty($safe_fenlei)) {
                            // 如果没有分类名称，尝试根据分类ID查找
                            $existing_fenlei = $DB->get_row("SELECT id FROM qingka_wangke_fenlei WHERE id='$safe_fenlei' LIMIT 1");
                            if ($existing_fenlei) {
                                $fenlei_id = $safe_fenlei;
                            }
                        }
                    }
                } else {
                    // 没有分类数据时使用原有逻辑
                    $fenleiname = $course['fenleiname'] ?? '';
                    
                    if (!empty($fenleiname)) {
                        // 先尝试根据分类名称查找
                        $safe_fenleiname = daddslashes($fenleiname);
                        $existing_fenlei = $DB->get_row("SELECT id FROM qingka_wangke_fenlei WHERE name='$safe_fenleiname' LIMIT 1");
                        if ($existing_fenlei) {
                            $fenlei_id = $existing_fenlei['id'];
                        }
                    } elseif (!empty($safe_fenlei)) {
                        // 如果没有分类名称，尝试根据分类ID查找
                        $existing_fenlei = $DB->get_row("SELECT id FROM qingka_wangke_fenlei WHERE id='$safe_fenlei' LIMIT 1");
                        if ($existing_fenlei) {
                            $fenlei_id = $safe_fenlei;
                        }
                    }
                }
                
                // 获取最大排序值
                $max_sort = $DB->get_row("SELECT MAX(sort) as max_sort FROM qingka_wangke_class");
                $new_sort = ($max_sort['max_sort'] ?? 0) + 1;
                
                // 构建INSERT语句 - 修复字段对应问题
                $current_time = date('Y-m-d H:i:s');
                
                // 先检查表结构，只插入存在的字段
                $table_check = $DB->query("DESCRIBE qingka_wangke_class");
                $existing_fields = [];
                while ($field = $DB->fetch($table_check)) {
                    $existing_fields[] = $field['Field'];
                }
                
                // 构建动态SQL
                $fields = [];
                $values = [];
                
                // 必需字段
                if (in_array('sort', $existing_fields)) {
                    $fields[] = 'sort';
                    $values[] = "'$new_sort'";
                }
                if (in_array('name', $existing_fields)) {
                    $fields[] = 'name';
                    $values[] = "'$safe_name'";
                }
                if (in_array('getnoun', $existing_fields)) {
                    $fields[] = 'getnoun';
                    $values[] = "'$safe_cid'";
                }
                if (in_array('noun', $existing_fields)) {
                    $fields[] = 'noun';
                    $values[] = "'$safe_cid'";
                }
                if (in_array('price', $existing_fields)) {
                    $fields[] = 'price';
                    $values[] = "'$safe_price'";
                }
                if (in_array('queryplat', $existing_fields)) {
                    $fields[] = 'queryplat';
                    $values[] = "'$hid'";
                }
                if (in_array('docking', $existing_fields)) {
                    $fields[] = 'docking';
                    $values[] = "'$hid'";
                }
                if (in_array('content', $existing_fields)) {
                    $fields[] = 'content';
                    $values[] = "'$safe_content'";
                }
                if (in_array('addtime', $existing_fields)) {
                    $fields[] = 'addtime';
                    $values[] = "'$current_time'";
                }
                if (in_array('status', $existing_fields)) {
                    $fields[] = 'status';
                    $values[] = "'1'";
                }
                if (in_array('fenlei', $existing_fields)) {
                    $fields[] = 'fenlei';
                    $values[] = "'$fenlei_id'";
                }
                
                // 可选字段
                if (in_array('vipprice', $existing_fields)) {
                    $fields[] = 'vipprice';
                    $values[] = "'$safe_price'";
                }
                if (in_array('nocheck', $existing_fields)) {
                    $fields[] = 'nocheck';
                    $values[] = "'0'";
                }
                if (in_array('changePass', $existing_fields)) {
                    $fields[] = 'changePass';
                    $values[] = "'0'";
                }
                if (in_array('yunsuan', $existing_fields)) {
                    $fields[] = 'yunsuan';
                    $values[] = "'*'";
                }
                if (in_array('vipyunsuan', $existing_fields)) {
                    $fields[] = 'vipyunsuan';
                    $values[] = "'*'";
                }
                
                $sql = "INSERT INTO qingka_wangke_class (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
                
                $new_class_id = $DB->insert($sql);
                
                if ($new_class_id) {
                    $success_count++;
                } else {
                    $error_count++;
                    $db_error = $DB->error();
                    $error_details[] = "插入失败: CID={$safe_cid}, 名称={$safe_name}, 错误: {$db_error}";
                    
                    // 记录详细错误到日志
                    wlog($userrow['uid'], '上架错误', "课程上架失败: {$safe_name} (CID: {$safe_cid}) - 数据库错误: {$db_error}", 0);
                }
            }
            
            // 如果是第一次检查且有重复课程，返回重复课程信息
            if ($handle_duplicates === 'check' && !empty($duplicate_courses)) {
                exit(json_encode([
                    'code' => 2, // 特殊代码表示有重复课程需要处理
                    'msg' => '检测到重复课程，请选择处理方式',
                    'data' => [
                        'success_count' => $success_count,
                        'error_count' => $error_count,
                        'duplicate_courses' => $duplicate_courses,
                        'duplicate_count' => count($duplicate_courses),
                        'error_details' => $error_details
                    ]
                ]));
            }
            
            // 记录日志
            $log_msg = "批量上架课程，成功：{$success_count}个，跳过：{$skip_count}个，更新：{$update_count}个，失败：{$error_count}个";
            if (!empty($error_details)) {
                $log_msg .= " | 错误详情: " . implode('; ', array_slice($error_details, 0, 3));
            }
            wlog($userrow['uid'], '批量上架课程', $log_msg, 0);
            
            // 构建返回消息
            $msg = "上架完成！成功上架 {$success_count} 个课程";
            if ($skip_count > 0) {
                $msg .= "，跳过 {$skip_count} 个重复课程";
            }
            if ($update_count > 0) {
                $msg .= "，更新 {$update_count} 个重复课程";
            }
            if ($error_count > 0) {
                $msg .= "，{$error_count} 个课程处理失败";
            }
            
            $response_data = [
                'success_count' => $success_count,
                'skip_count' => $skip_count,
                'update_count' => $update_count,
                'error_count' => $error_count,
                'total_count' => count($courses)
            ];
            
            // 如果有错误，包含错误详情
            if (!empty($error_details)) {
                $response_data['error_details'] = $error_details;
            }
            
            exit(json_encode([
                'code' => 1, 
                'msg' => $msg,
                'data' => $response_data
            ]));
            
        } catch (Exception $e) {
            // 记录异常到日志
            wlog($userrow['uid'], '上架异常', '批量上架发生异常: ' . $e->getMessage(), 0);
            exit(json_encode(['code' => -1, 'msg' => '上架失败: ' . $e->getMessage()]));
        }
        break;
        
    // case 'getmoney':
    //     $hid = trim(strip_tags(daddslashes($_POST['hid'])));
    //     $huoyuan = validateAndGetHuoyuan($hid);
        
    //     $api_result = callHuoyuanAPI($huoyuan, 'getmoney');
        
    //     if (!$api_result['success']) {
    //         exit(json_encode(['code' => -1, 'msg' => 'API调用失败']));
    //     }
        
    //     $result = $api_result['data'];
    //     if (isset($result['money'])) {
    //         exit(json_encode(['code' => 1, 'msg' => '获取成功', 'data' => ['money' => $result['money']]]));
    //     } else {
    //         exit(json_encode(['code' => -1, 'msg' => '获取余额失败']));
    //     }
    //     break;


	

case 'user_orderlist1':
            $cx = daddslashes($_POST['cx']);
            $limit = $cx['limit'];
            if ($limit == "") {
                $pagesize = 25;
            } else {
                $pagesize = $limit;
            }
            $page = trim(strip_tags(daddslashes($_POST['page'])));
            $pageu = ($page - 1) * $pagesize;
            // 当前界面
            $qq = trim(strip_tags(daddslashes($cx['qq'])));
            $cid = trim(strip_tags(daddslashes($cx['cid'])));
            $mh = trim(strip_tags(daddslashes($cx['mh'])));
            $search = trim(strip_tags(daddslashes($cx['search'])));
            // SQL查询条件构建
            $sql1 = "where 1=1 ";
                if ($cid != '') {
                    $sql2 = " and cid='{$cid}'";
                }
                  if ($search === 'kcname' && $mh !== '') {
                    $sql4 = " and kcname='".$mh."'";
                }
                    $sql5 = "";
                if ($search === '' && $mh !== '') {
                    $sql5 = " and (ptname LIKE '%" . $mh . "%' OR school LIKE '%" . $mh . "%' OR kcname LIKE '%" . $mh . "%' ) and status = '已完成'";
                }
                if ($cx['kcname'] != '') {
                    $sql7 = " and kcname='{$cx['kcname']}'";
                }
                $sql6 = " and status = '已完成'";
                $sql12="and addtime >= CURDATE() - INTERVAL 1000 DAY";
                $sql = $sql1 . $sql2  . $sql4 . $sql5 . $sql6 . $sql7 . $sql12;
            
            // 执行数据库查询
            $a = $DB->query("SELECT ptname, kcname, status, process, remarks, addtime FROM qingka_wangke_order {$sql} ORDER BY oid DESC LIMIT $pageu, $pagesize");
            $count1 = $DB->count("select count(*) from qingka_wangke_order {$sql} ");
            
            // 检查查询结果的uid是否等于当前用户的uid
            while ($row = $DB->fetch($a)) {
                if ($row['name'] == '' || $row['name'] == 'undefined') {
                    $row['name'] = 'null';
                }
                $data[] = $row;
            }
            
            $last_page = ceil($count1 / $pagesize);
            
            // 取最大页数
            $data = array('code' => 1, 'data' => $data, "current_page" => (int)$page, "last_page" => $last_page, "uid" => (int)$userrow['uid']);
            exit(json_encode($data));
            break;

case 'updatePushPlusToken':
		$pushPlusToken = trim(strip_tags(daddslashes($_POST['pushPlusToken'])));
		if (empty($pushPlusToken)) {
			jsonReturn(-1, "PushPlus Token不能为空");
			break;
		}
		if ($DB->query("UPDATE qingka_wangke_user SET pushPlusToken='{$pushPlusToken}' WHERE uid='{$userrow['uid']}'")) {
			wlog($userrow['uid'], "更新PushPlus Token", "更新PushPlus Token: {$pushPlusToken}", 0);
			jsonReturn(1, "PushPlus Token更新成功");
		} else {
			jsonReturn(-1, "更新失败或Token未改变");
		}

	// 删除货源
	case 'huoyuan_del':

		$hid = daddslashes($_POST['hid']);
		if ($userrow['uid'] != '1') {
			jsonReturn(-1, "滚");
		}
		$DB->query("delete from qingka_wangke_huoyuan where hid='$hid' ");
		jsonReturn(1, "删除成功");
		break;


	case 'checkbalance':
		if ($userrow['uid'] == 1) {
			$hid = intval($_POST['hid']);
			if (!$hid) {
				jsonReturn(0, '请选择货源');
			}
			$row = $DB->get_row("SELECT * FROM `qingka_wangke_huoyuan` WHERE hid = '$hid' LIMIT 1");
			if (!$row) {
				jsonReturn(0, '未找到该货源');
			}
			$url = $row['url'];
			$user = $row['user'];
			$pass = $row['pass'];
			$name = $row['name'];
			$er_url = $url . "/api.php?act=getmoney";
			$data = array("uid" => $user, "key" => $pass);
			$result = get_url($er_url, $data);
			$result_array = json_decode($result, true);
			if (isset($result_array['money'])) {
				$balance = $result_array['money'];
				$message = "当前接口 $name 余额为 $balance";
				//$DB->query("UPDATE qingka_wangke_huoyuan SET money='{$balance}' WHERE hid='{$hid}'");
				jsonReturn(1, $message);
			} else {
				jsonReturn(0, '查询余额失败');
			}
		} else {
			exit('{"code":-1,"msg":"无权限"}');
		}
		break;



case 'cha_logwk':
        $oid = trim(strip_tags(daddslashes($_GET['oid'])));
        $a = $DB->get_row("select *from qingka_wangke_order where oid='{$oid}' ");
        if ($a['dockstatus'] == '1') {
            if ($userrow['uid'] == $a["uid"] || $userrow['uid'] == '1') {
                $res = logWK($oid);
                // exit($res);
                if ($res['code'] == 1) {
                    $data = array('code' => 1, 'data' => $res['data'], "msg" => '获取成功');
                    exit(json_encode($data));
                } else {
                    jsonReturn(-1, $res['msg']);
                }
            } else {
                jsonReturn(-1, "该订单都不是你的，你看你妈呢");
            }
        } else {
            jsonReturn(-1, "订单都还没处理过去呢，你就查看，你礼貌吗");
        }
        break;



	case 'zztk':
		$sex = daddslashes($_POST['sex']);
		for ($i = 0; $i < count($sex); $i++) {
			$oid = $sex[$i];
			$order = $DB->get_row("select * from qingka_wangke_order where oid='{$oid}' ");
			$user = $DB->get_row("select * from qingka_wangke_user where uid='{$order['uid']}' ");
			// 检查 status 字段是否为 "已退款"
			if ($order['status'] != '已退款' && $order['dockstatus'] != '3' && $order['dockstatus'] != '2') {
				exit('{"code":-2,"msg":"订单ID：' . $order['oid'] . ' 不能退款哦！！！"}');
			}

			// 检查 qingka_wangke_log 中是否已经存在相同订单ID的记录
			$log_check = $DB->get_row("select * from qingka_wangke_log where text like '%订单ID：{$order['oid']}%'");
			if ($log_check) {
				exit('{"code":-2,"msg":"订单ID：' . $order['oid'] . ' <br>叼毛！还想重复退款！！！"}');
			}

			$DB->query("update qingka_wangke_user set money=money+'{$order['fees']}' where uid='{$user['uid']}'");
			$DB->query("update qingka_wangke_order set status='已退款',dockstatus='4' where oid='{$oid}'");
			wlog($user['uid'], "订单退款", "订单ID：{$order['oid']} 订单信息：{$order['user']} {$order['pass']} {$order['kcname']}自助退款成功", "+{$order['fees']}");
		}
		exit('{"code":1,"msg":"选择的订单已批量退款！可在日志中查看！"}');
		break;









	case 'kcidlist':
		$page = trim(daddslashes($_GET['page']));
		$limit = trim(daddslashes($_GET['limit']));
		$pageu = ($page - 1) * $limit;//当前界面	



		if ($userrow['uid'] == 1) {
			$searchOid = isset($_GET['oid']) ? trim(daddslashes($_GET['oid'])) : '';
			$searchUser = isset($_GET['user']) ? trim(daddslashes($_GET['user'])) : '';
			$whereClause = "";

			if (!empty($searchOid)) {
				$whereClause .= " AND oid LIKE '%$searchOid%'";
			}
			if (!empty($searchUser)) {
				$whereClause .= " AND user LIKE '%$searchUser%'";
			}

			$a = $DB->query("SELECT * FROM qingka_wangke_order WHERE 1 $whereClause ORDER BY oid DESC LIMIT $pageu,$limit");
			$count = $DB->count("SELECT COUNT(*) FROM qingka_wangke_order WHERE 1 $whereClause");
		} else {
			$searchOid = isset($_GET['oid']) ? trim(daddslashes($_GET['oid'])) : '';
			$searchUser = isset($_GET['user']) ? trim(daddslashes($_GET['user'])) : '';
			$whereClause = " AND uid='{$userrow['uid']}'";

			if (!empty($searchOid)) {
				$whereClause .= " AND oid LIKE '%$searchOid%'";
			}
			if (!empty($searchUser)) {
				$whereClause .= " AND user LIKE '%$searchUser%'";
			}

			$a = $DB->query("SELECT * FROM qingka_wangke_order WHERE 1 $whereClause ORDER BY oid DESC LIMIT $pageu,$limit");
			$count = $DB->count("SELECT COUNT(*) FROM qingka_wangke_order WHERE 1 $whereClause");
		}








		// if($userrow['uid']==1){
		//     $a=$DB->query("select * from qingka_wangke_order order by oid desc limit $pageu,$limit");
		//     $count=$DB->count("select count(*) from qingka_wangke_order");
		// }else{
		//     $a=$DB->query("select * from qingka_wangke_order  where uid='{$userrow['uid']}' order by oid desc limit $pageu,$limit");
		//     $count=$DB->count("select count(*) from qingka_wangke_order  where uid='{$userrow['uid']}'");
		// }
		while ($row = $DB->fetch($a)) {
			$data[] = array(
				'oid' => $row['oid'],
				'ptname' => $row['ptname'],
				'user' => $row['user'],
				'kcname' => $row['kcname'],
				'kcid' => $row['kcid'],
				'addtime' => $row['addtime'],
				'status' => $row['status'],
			);
		}

		array_multisort($sort, SORT_ASC, $rate, SORT_ASC, $data);
		$data = array('code' => 1, 'data' => $data, "count" => $count);
		exit(json_encode($data));
		break;

	case 'status_order':
		$a = trim(strip_tags(daddslashes($_GET['a'])));
		$sex = daddslashes($_POST['sex']);
		$type = trim(strip_tags(daddslashes($_POST['type'])));
		if ($a == " " or empty($sex)) {
			jsonReturn(-1, "请先选择订单");
		}
		if ($userrow['uid'] != 1) {
			jsonReturn(-1, "老铁，求您别干我");
		}

		if ($type == 1) {
			$sql = "`status`='$a'";
		} elseif ($type == 2) {
			$sql = "`dockstatus`='$a'";
		}

		if ($userrow['uid'] == 1) {
			for ($i = 0; $i < count($sex); $i++) {
				$oid = $sex[$i];
				$b = $DB->query("update qingka_wangke_order set {$sql} where oid='{$oid}' ");
			}
			if ($b) {
				jsonReturn(1, "修改成功");
			} else {
				jsonReturn(-1, "未知异常");
			}
		} else {
			exit('{"code":-1,"msg":"无权限"}');
		}
		break;

	// 	case 'aaaclassrank':
// 		$page = trim(strip_tags(daddslashes($_POST['page']))) ? trim(strip_tags(daddslashes($_POST['page']))) : 1;
// 		$pagesize = 1000;
// 		$pageu = ($page - 1) * $pagesize;
// 		// 当前界面
// 		$count1 = $DB->count("select count(*) from qingka_wangke_class where status = 0");
// 		$last_page = ceil($count1 / $pagesize);
// 		// 取最大页数
// 		$a = $DB->query("select * from qingka_wangke_class where status = 0 ORDER BY cid DESC limit $pageu,$pagesize ");
// 		$data = []; // 初始化$data数组
// 		while ($row = $DB->fetch($a)) {
// 			// ... (省略了与之前相同的代码)
// 			$data[] = $row; // 将记录添加到$data数组
// 		}
// 		// ... (省略了与之前相同的代码)
// 		if (empty($data)) {
// 			$data = array('code' => 1, 'data' => [], "current_page" => (int) $page, "last_page" => $last_page);
// 		} else {
// 			foreach ($data as $key => $rows) {
// 				// ... (省略了与之前相同的代码)
// 			}
// 			array_multisort($cid, SORT_DESC, $data);
// 			$data = array('code' => 1, 'data' => $data, "current_page" => (int) $page, "last_page" => $last_page);
// 		}
// 		exit(json_encode($data));
// 		break;




	case 'newclassrank':
		$page = trim(strip_tags(daddslashes($_POST['page'])))
			? trim(strip_tags(daddslashes($_POST['page'])))
			: 1;
		$pagesize = 1000;
		$pageu = ($page - 1) * $pagesize;
		// 当前界面
		$newTime = date('Y-m-d H:i:s', strtotime('-8 day'));

		// 获取总记录数
		$count1 = $DB->count("SELECT cid, name, content, price, addtime FROM qingka_wangke_class WHERE status=1 ORDER BY cid ASC");

		// 获取用户信息
		$user = $DB->get_row("SELECT * FROM qingka_wangke_user WHERE uid='{$userrow['uid']}'");

		// 检查用户是否存在且 addprice 是有效的

		$addprice = $user['addprice']; // 如果没有有效的 addprice，默认为 1


		$last_page = ceil($count1 / $pagesize); // 取最大页数

		// 查询课程数据，并计算 price
		$a = $DB->query("SELECT * FROM qingka_wangke_class WHERE addtime > '$newTime' ORDER BY cid, fenlei DESC LIMIT $pageu, $pagesize");
		$data = [];
		while ($row = $DB->fetch($a)) {




			// 计算价格
			$row['price'] = round($row['price'] * $addprice, 2);

			$data[] = $row;
		}

		// 不需要再次对 $data 进行排序，因为我们已经在查询时按照需要的顺序获取了数据
		foreach ($data as $key => $rows) {
			$sort[$key] = $rows['sort'];
			$cid[$key] = $rows['cid'];
			$fenlei[$key] = $rows['fenlei'];
			$name[$key] = $rows['name'];
			// $getnoun[$key] = $rows['getnoun'];
			// $noun[$key] = $rows['noun'];
			$price[$key] = $rows['price'];
			// $queryplat[$key] = $rows['queryplat'];
			// $yunsuan[$key] = $rows['yunsuan'];
			// $content[$key] = $rows['content'];
			$addtime[$key] = $rows['addtime'];
			// $status[$key] = $rows['status'];
			// $cx_names[$key] = $rows['cx_names'];
			// $add_name[$key] = $rows['add_name'];
		}
		array_multisort($cid, SORT_DESC, $data);
		$data = array('code' => 1, 'data' => $data, "current_page" => (int) $page, "last_page" => $last_page);
		exit(json_encode($data));
		break;






case 'classrank':
    // 参数处理（与newclassrank统一风格）
    $page = trim(strip_tags(daddslashes($_POST['page']))) ? trim(strip_tags(daddslashes($_POST['page']))) : 1;
    $pagesize = 1000; // 保持与newclassrank相同的分页大小
    $pageu = ($page - 1) * $pagesize;

    // 获取总记录数（优化查询，只计算数量）
    $count1 = $DB->count("SELECT COUNT(*) FROM qingka_wangke_class c WHERE c.status=0");

    // 获取用户信息（与newclassrank保持相同逻辑）
    $user = $DB->get_row("SELECT * FROM qingka_wangke_user WHERE uid='{$userrow['uid']}'");
    $addprice = $user['addprice'] ?? 1; // 默认值为1

    // 查询课程数据（添加关联查询，与页面需求一致）
    $a = $DB->query("SELECT c.cid, c.name as course_name, c.fenlei as category_id, 
                    f.name as category_name, c.price, c.addtime 
                    FROM qingka_wangke_class c
                    JOIN qingka_wangke_fenlei f ON c.fenlei = f.id
                    WHERE c.status=0
                    ORDER BY c.cid DESC 
                    LIMIT $pageu, $pagesize");
    
    $data = [];
    while ($row = $DB->fetch($a)) {
        // 统一价格计算逻辑
        $row['price'] = round($row['price'] * $addprice, 2);
        $data[] = $row;
    }

    // 分页信息（与newclassrank相同结构）
    $last_page = ceil($count1 / $pagesize);
    
    // 返回数据结构统一
    $data = array(
        'code' => 1, 
        'data' => $data, 
        'current_page' => (int)$page, 
        'last_page' => $last_page,
        'total' => $count1 // 新增总记录数字段
    );
    exit(json_encode($data));
break;















	case 'pchangelist': // 价格变动记录查询
        // 1. 参数安全处理
        $page = max(1, intval($_POST['page'] ?? 1)); // 页码（最小为1）
        $pagesize = 50;                              // 每页条数
        $offset = ($page - 1) * $pagesize;

        // 2. 可选筛选参数
        $cid = intval($_POST['cid'] ?? 0);           // 按商品ID筛选
        $start_time = daddslashes($_POST['start_time'] ?? '');
        $end_time = daddslashes($_POST['end_time'] ?? '');

        // 3. 构建查询条件
        $where = [];
        if ($cid) $where[] = "cid='{$cid}'";
        if ($start_time) $where[] = "updatetime>='{$start_time}'";
        if ($end_time) $where[] = "updatetime<='{$end_time}'";
        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // 4. 查询数据（分页）
        $data = [];
        $query = $DB->query("SELECT cid, kcname, oldprice, newprice, updatetime 
                            FROM qingka_wangke_pchange 
                            {$where_sql}
                            ORDER BY updatetime DESC 
                            LIMIT {$offset}, {$pagesize}");

        while ($row = $DB->fetch($query)) {
            $data[] = [
                'cid' => $row['cid'],
                'kcname' => $row['kcname'],
                'oldprice' => round($row['oldprice'], 2),
                'newprice' => round($row['newprice'], 2),
                'updatetime' => $row['updatetime']
            ];
        }

        // 5. 获取总条数（用于分页）
        $total = $DB->get_var("SELECT COUNT(*) FROM qingka_wangke_pchange {$where_sql}");

        // 6. 返回JSON响应
        exit(json_encode([
            'code' => 1,
            'msg' => '查询成功',
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'pagesize' => $pagesize,
                'total' => $total,
                'total_page' => ceil($total / $pagesize)
            ]
        ]));
        break;
case 'pchangestats':
    // 获取总记录数
    $total = $DB->get_var("SELECT COUNT(*) FROM qingka_wangke_pchange");
    
    // 获取今日变动数
    $today = $DB->get_var("SELECT COUNT(*) FROM qingka_wangke_pchange 
                          WHERE DATE(updatetime) = CURDATE()");
    
    // 获取价格上涨数量
    $priceUp = $DB->get_var("SELECT COUNT(*) FROM qingka_wangke_pchange 
                            WHERE newprice > oldprice");
    
    // 获取价格下降数量
    $priceDown = $DB->get_var("SELECT COUNT(*) FROM qingka_wangke_pchange 
                              WHERE newprice < oldprice");
    
    exit(json_encode([
        'code' => 1,
        'data' => [
            'total' => $total,
            'today' => $today,
            'priceUp' => $priceUp,
            'priceDown' => $priceDown
        ]
    ]));
    break;

	case 'getclassrank':
		$a = $DB->query("SELECT cid, ptname, COUNT(*) as total FROM qingka_wangke_order WHERE addtime>'$jtdate' GROUP BY cid ORDER BY total DESC LIMIT 50;");
		while ($row = $DB->fetch($a)) {
			$data[] = array(
				'cid' => $row['cid'],
				'ptname' => $row['ptname'],
				'total' => $row['total']
			);
		}
		$data = array('code' => 1, 'data' => $data);
		exit(json_encode($data));
		break;


	case 'xgmm':
		$xgmm = trim(strip_tags(daddslashes($_GET['xgmm'])));
		$oid = $_GET['oid'];
		if (empty($xgmm)) {
			jsonReturn(-1, "密码不能为空");
		}
		if (strlen($xgmm) < 3) {
			jsonReturn(-1, "密码长度至少为3位");
		} else {
			$b = xgmm($oid, $xgmm);
			if ($b['code'] == 1) {

				$DB->query("UPDATE qingka_wangke_order SET pass = '{$xgmm}' WHERE oid = '{$oid}'");
				$DB->query("update qingka_wangke_user set money=money-0.01 where uid='{$userrow['uid']}' limit 1 ");
				wlog($userrow['uid'], "修改密码", "订单{$oid}修改密码成功扣除0.01", -0.01);
				jsonReturn(1, $b['msg']);
			} else {
				jsonReturn(-1, $b['msg']);
			}
		}
		break;





	case 'zt':
		$oid = trim(strip_tags(daddslashes($_GET['oid'])));
		$b = $DB->get_row("select hid,cid,dockstatus from qingka_wangke_order where oid='{$oid}' ");
		$DB->query("update qingka_wangke_order set status='已停止',`bsnum`=bsnum+1 where oid='{$oid}' ");
		if ($b['dockstatus'] == '99') {
			jsonReturn(1, "我的订单");
		} else {
			$b = ztWk($oid);
			if ($b['code'] == 1) {
				$DB->query("update qingka_wangke_order set status='已停止',`bsnum`=bsnum+1 where oid='{$oid}' ");
				wlog($userrow['uid'], "暂停", "暂停订单: {$oid}", 0);
				jsonReturn(1, $b['msg']);
			} else {
				jsonReturn(-1, $b['msg']);
			}
		}
		break;


	case 'updatekeywords':
		if ($userrow['uid'] == 1) {
			$oldKeyword = $_POST['oldKeyword'];
			$newKeyword = $_POST['newKeyword'];
			$effectScope = $_POST['effectScope'];
			$scopeId = $_POST['scopeId'];

			$where = "1";
			if ($effectScope == 'category') {
				$where = "`fenlei` = '$scopeId'";
			} elseif ($effectScope == 'docking') {
				$where = "`docking` = '$scopeId'";
			}

			$sql = "UPDATE `qingka_wangke_class` SET `name` = REPLACE(`name`, '$oldKeyword', '$newKeyword') WHERE $where";
			$result = $DB->query($sql);

			if ($result) {
				echo json_encode(['code' => 1, 'msg' => '关键词替换成功']);
			} else {
				echo json_encode(['code' => 0, 'msg' => '关键词替换失败']);
			}
		} else {
			exit('{"code":-1,"msg":"无权限"}');
		}
		break;
	case 'addprefix':
		if ($userrow['uid'] == 1) {
			$prefix = $_POST['prefix'];
			$prefixEffectScope = $_POST['prefixEffectScope'];
			$prefixScopeId = $_POST['prefixScopeId'];
			$where = "1";
			if ($prefixEffectScope == 'category') {
				$where = "`fenlei` = '$prefixScopeId'";
			} elseif ($prefixEffectScope == 'docking') {
				$where = "`docking` = '$prefixScopeId'";
			}
			$sql = "UPDATE `qingka_wangke_class` SET `name` = CONCAT('$prefix', `name`) WHERE $where";
			$result = $DB->query($sql);
			if ($result) {
				echo json_encode(['code' => 1, 'msg' => '前缀添加成功']);
			} else {
				echo json_encode(['code' => 0, 'msg' => '前缀添加失败']);
			}
		} else {
			exit('{"code":-1,"msg":"无权限"}');
		}
		break;



	case 'checkdeployedcount':
		if ($userrow['uid'] == 1) {
			$hid = intval($_POST['hid']);
			if (!$hid) {
				jsonReturn(0, '请选择货源');
			}
			$count1 = $DB->count("select count(*) from qingka_wangke_class where docking = '{$hid}' ");
			$message = "当前货源已上架数为 $count1";
			jsonReturn(1, $message);
		} else {
			exit('{"code":-1,"msg":"无权限"}');
		}
		break;

	case 'startintegration':
		if ($userrow['uid'] == 1) {
			$hid = trim(strip_tags(daddslashes($_POST['hid'])));
			$upstreamCategoryId = trim(strip_tags(daddslashes($_POST['upstreamCategoryId'])));
			$localCategoryId = trim(strip_tags(daddslashes($_POST['localCategoryId'])));
			$markupMultiplier = trim(strip_tags(daddslashes($_POST['markupMultiplier'])));
			$multiplyByFive = (int) daddslashes($_POST['multiplyByFive']);
			$skipExisting = (int) daddslashes($_POST['skipExisting']);

			$a = $DB->get_row("SELECT * FROM qingka_wangke_huoyuan WHERE hid='{$hid}'");
			if (!$a) {
				jsonReturn(-1, "货源信息不存在");
			}

			$data = array("uid" => $a["user"], "key" => $a["pass"]);
			$er_url = "{$a["url"]}/api.php?act=getclass";
			$result = get_url($er_url, $data);
			$result1 = json_decode($result, true);

			if (json_last_error() !== JSON_ERROR_NONE || !isset($result1["data"])) {
				jsonReturn(-1, "API 返回数据格式错误或缺失");
			}

			$categories = $result1["data"];
			$numItemsInserted = 0;

			// 获取当前分类下的最大 sort 值
			$maxSortRow = $DB->get_row("SELECT MAX(sort) AS max_sort FROM qingka_wangke_class WHERE fenlei='{$localCategoryId}'");
			$maxSort = $maxSortRow ? $maxSortRow['max_sort'] : 0;

			foreach ($categories as $value) {
				if ($upstreamCategoryId !== 'all' && $value['fenlei'] != $upstreamCategoryId) {
					continue;
				}

				if ($multiplyByFive == 2) {
					$price = $value['price'] * $markupMultiplier * 5;
				} elseif ($multiplyByFive == 1) {
					$price = $value['price'] * $markupMultiplier;
				} else {
					$price = $value['price'] + $markupMultiplier;
				}

				if ($skipExisting) {
					$existingProduct = $DB->get_row("SELECT * FROM qingka_wangke_class WHERE docking='{$hid}' AND noun='{$value['cid']}'");
					if ($existingProduct) {
						continue;
					}
				}

				$sort = $maxSort + 1;
				$DB->query("INSERT INTO qingka_wangke_class (name, getnoun, noun, fenlei, queryplat, docking, price, sort, content, addtime, status) 
                        VALUES ('{$value['name']}', '{$value['cid']}', '{$value['cid']}', '{$localCategoryId}', '$hid', '$hid', '{$price}', '{$sort}', '{$value['content']}', NOW(), '1')");
				$maxSort++;
				$numItemsInserted++;
			}

			jsonReturn(1, "本次对接上架了{$numItemsInserted}个商品");
		} else {
			exit('{"code":-1,"msg":"无权限"}');
		}
		break;
	case 'startintegrationbyrange':
		if ($userrow['uid'] == 1) {
			$hid = trim(strip_tags(daddslashes($_POST['hid'])));
			$startId = trim(strip_tags(daddslashes($_POST['startId'])));
			$endId = trim(strip_tags(daddslashes($_POST['endId'])));
			$localCategoryId = trim(strip_tags(daddslashes($_POST['localCategoryId'])));
			$markupMultiplier = trim(strip_tags(daddslashes($_POST['markupMultiplier'])));
			$multiplyByFive = (int) daddslashes($_POST['multiplyByFive']);

			$a = $DB->get_row("SELECT * FROM qingka_wangke_huoyuan WHERE hid='{$hid}'");
			if (!$a) {
				jsonReturn(-1, "货源信息不存在");
			}

			$data = array("uid" => $a["user"], "key" => $a["pass"]);
			$er_url = "{$a["url"]}/api.php?act=getclass";
			$result = get_url($er_url, $data);
			$result1 = json_decode($result, true);

			if (json_last_error() !== JSON_ERROR_NONE || !isset($result1["data"])) {
				jsonReturn(-1, "API 返回数据格式错误或缺失");
			}

			$categories = $result1["data"];
			$numItemsInserted = 0;

			// 获取当前分类下的最大 sort 值
			$maxSortRow = $DB->get_row("SELECT MAX(sort) AS max_sort FROM qingka_wangke_class WHERE fenlei='{$localCategoryId}'");
			$maxSort = $maxSortRow ? $maxSortRow['max_sort'] : 0;

			foreach ($categories as $value) {
				if ($value['cid'] < $startId || $value['cid'] > $endId) {
					continue;
				}

				if ($multiplyByFive == 2) {
					$price = $value['price'] * $markupMultiplier * 5;
				} elseif ($multiplyByFive == 1) {
					$price = $value['price'] * $markupMultiplier;
				} else {
					$price = $value['price'] + $markupMultiplier;
				}

				$sort = $maxSort + 1;
				$DB->query("INSERT INTO qingka_wangke_class (name, getnoun, noun, fenlei, queryplat, docking, price, sort, content, addtime, status) 
                        VALUES ('{$value['name']}', '{$value['cid']}', '{$value['cid']}', '{$localCategoryId}', '$hid', '$hid', '{$price}', '{$sort}', '{$value['content']}', NOW(), '1')");
				$maxSort++; // 更新最大 sort 值
				$numItemsInserted++;
			}

			jsonReturn(1, "本次对接上架了{$numItemsInserted}个新商品");
		} else {
			exit('{"code":-1,"msg":"无权限"}');
		}
		break;
	case 'deleteDuplicates':
		if ($userrow['uid'] == 1) {
			$scope = trim(strip_tags(daddslashes($_POST['scope'])));
			$scopeId = trim(strip_tags(daddslashes($_POST['scopeId'])));
			$strategy = trim(strip_tags(daddslashes($_POST['strategy'])));
			$where = '';
			if ($scope == 'category') {
				$where = "AND t1.fenlei = '$scopeId'";
			} elseif ($scope == 'docking') {
				$where = "AND t1.docking = '$scopeId'";
			}
			$order = ($strategy == 'keep_larger') ? 't1.cid < t2.cid' : 't1.cid > t2.cid';

			$sql = "DELETE t1 FROM qingka_wangke_class t1
                JOIN qingka_wangke_class t2
                ON t1.noun = t2.noun AND t1.docking = t2.docking AND t1.fenlei = t2.fenlei $where
                WHERE $order";
			$result = $DB->query($sql);
			if ($result) {
				echo json_encode(['code' => 1, 'msg' => '删除重复商品成功']);
			} else {
				echo json_encode(['code' => 0, 'msg' => '删除重复商品失败']);
			}
		} else {
			exit('{"code":-1,"msg":"无权限"}');
		}
		break;
	case 'updateprice':
		if ($userrow['uid'] == 1) {
			$hid = trim(strip_tags(daddslashes($_POST['hid'])));
			$upstreamCategoryId = trim(strip_tags(daddslashes($_POST['upstreamCategoryId'])));
			$priceRatio = trim(strip_tags(daddslashes($_POST['priceRatio'])));
			$multiplyByFive = trim(strip_tags(daddslashes($_POST['multiplyByFive'])));

			// 获取货源信息
			$a = $DB->get_row("SELECT * FROM qingka_wangke_huoyuan WHERE hid='{$hid}'");
			if (!$a) {
				jsonReturn(-1, "货源信息不存在");
			}

			// 构建请求数据
			$data = array("uid" => $a["user"], "key" => $a["pass"]);
			$er_url = "{$a["url"]}/api.php?act=getclass";

			// 发送请求获取商品分类信息
			$result = get_url($er_url, $data);
			$result1 = json_decode($result, true);

			// 检查API返回数据是否正确
			if (json_last_error() !== JSON_ERROR_NONE || !isset($result1["data"])) {
				jsonReturn(-1, "API 返回数据格式错误或缺失");
			}

			$categories = $result1["data"];
			$numItemsUpdated = 0;

			// 如果 $multiplyByFive 为 5，先执行下架操作
			if ($multiplyByFive == '5') {
				$DB->query("UPDATE qingka_wangke_class SET status=0 WHERE docking='{$hid}'");
			}

			// 遍历所有分类，更新商品价格或内容
			foreach ($categories as $value) {
				// 如果指定了分类ID，则只更新该分类下的商品
				if ($upstreamCategoryId && $value['fenlei'] != $upstreamCategoryId) {
					continue;
				}

				// 根据计算方式计算新价格
				switch ($multiplyByFive) {
					case '2': // 乘法计算且乘5
						$price = $value['price'] * $priceRatio * 5;
						break;
					case '1': // 乘法计算且不乘5
						$price = $value['price'] * $priceRatio;
						break;
					case '0': // 加法计算直接加价
						$price = $value['price'] + $priceRatio;
						break;
					default:
						$price = $value['price'];
						break;
				}
				$existingProduct = $DB->get_row("SELECT * FROM qingka_wangke_class WHERE docking='{$hid}' AND noun='{$value['cid']}'");
				if ($existingProduct) {
					$updateFields = array();
					if ($multiplyByFive != '3' && $multiplyByFive != '4' && $multiplyByFive != '5') {
						$updateFields['price'] = $price;
					}
					if ($multiplyByFive == '3' || $multiplyByFive == '4') {
						$updateFields['content'] = $value['content'];
					}
					if ($multiplyByFive == '4') {
						$updateFields['name'] = $value['name'];
					}
					if ($multiplyByFive == '5') {
						$updateFields['status'] = 1; // 上架操作
					}
					$updateQuery = "UPDATE qingka_wangke_class SET ";
					$updateQuery .= implode(", ", array_map(function ($key) use ($updateFields) {
						return "$key='" . addslashes($updateFields[$key]) . "'";
					}, array_keys($updateFields)));
					$updateQuery .= " WHERE docking='{$hid}' AND noun='{$value['cid']}'";

					$DB->query($updateQuery);
					$numItemsUpdated++;
				}
			}
			jsonReturn(1, "本次更新了{$numItemsUpdated}个商品的价格或内容");
		}
		break;



	case 'passwd':
		$oldpass = trim(strip_tags(daddslashes($_POST['oldpass'])));
		$newpass = trim(strip_tags(daddslashes($_POST['newpass'])));
		$newpass1 = trim(strip_tags(daddslashes($_POST['newpass1'])));

		if ($oldpass != $userrow['pass']) {
			exit('{"code":-1,"msg":"原密码错误"}');
		}
		if ($newpass == '') {
			exit('{"code":-1,"msg":"新密码不能为空"}');
		}
		if ($newpass != $newpass1) {
			exit('{"code":-1,"msg":"两次输入的密码不一样"}');
		}
		$sql = "update `qingka_wangke_user` set `pass` ='{$newpass}' where `uid`='{$userrow['uid']}'";
		if ($DB->query($sql)) {
			exit('{"code":1,"msg":"修改成功,请牢记密码"}');
		} else {
			exit('{"code":-1,"msg":"修改失败"}');
		}
		break;
	case 'webset':
		if ($userrow['active'] == 0) {
			jsonReturn(-1, "账号已被封禁");
		}
		parse_str(daddslashes($_POST['data']), $row);
		if ($userrow['uid'] != 1) {
			exit('{"code":-1,"msg":"滚，傻逼！你没妈了？"}');
		} else if ($userrow['uid'] == 1) {
			foreach ($row as $k => $value) {
				if ($k == 'dklcookie' || $k == 'nanatoken' || $k == 'akcookie' || $k == 'vpercookie') {
					$value = authcode($value, 'ENCODE', 'qingka');
				}
				$DB->query("UPDATE `qingka_wangke_config` SET k='{$value}' WHERE v='{$k}'");
			}
			exit('{"code":1,"msg":"修改成功"}');
		}
		break;
	case 'szyqm':
		if ($userrow['active'] == 0) {
			jsonReturn(-1, "账号已被封禁");
		}
		$uid = trim(strip_tags(daddslashes($_POST['uid'])));
		$yqm = trim(strip_tags(daddslashes($_POST['yqm'])));
		if (strlen($yqm) < 4) {
			jsonReturn(-1, "邀请码最少4位，且必须为数字");
		}
		if (!is_numeric($yqm)) {
			jsonReturn(-1, "请正确输入邀请码，必须为数字");
		}
		if ($DB->get_row("select * from qingka_wangke_user where yqm='$yqm' ")) {
			jsonReturn(-1, "该邀请码已被使用，请换一个");
		}
		$a = $DB->get_row("select * from qingka_wangke_user where uid='$uid' ");
		if ($userrow['uid'] == '1') {
			$DB->query("update qingka_wangke_user set yqm='{$yqm}' where uid='$uid' ");
			wlog($userrow['uid'], "设置邀请码", "给下级设置邀请码{$yqm}成功", '0');
			jsonReturn(1, "设置成功");
		} elseif ($userrow['uid'] == $a['uuid']) {
			$DB->query("update qingka_wangke_user set yqm='{$yqm}' where uid='$uid' ");
			wlog($userrow['uid'], "设置邀请码", "给下级设置邀请码{$yqm}成功", '0');
			jsonReturn(1, "设置成功");
		} else {
			jsonReturn(-1, "无权限");
		}

		break;
	case 'wlogin':
		if ($userrow['active'] == 0) {
			jsonReturn(-1, "账号已被封禁");
		}
		jsonReturn(1, "不写也罢");
		if ($userrow['uid'] == 1) {
			$uid = daddslashes($_POST['uid']);
			$row = $DB->get_row("SELECT * FROM qingka_wangke_user WHERE uid='$uid' limit 1");
			$session = md5($user . $pass . $password_hash);
			$token = authcode("{$user}\t{$session}", 'ENCODE', SYS_KEY);
			setcookie("admin_token", $token, time() + 3000);
			exit('{"code":1,"msg":"登录成功"}');
		} else {
			jsonReturn(-1, "你在干啥？");
		}
		break;
	case 'userinfo':
		if ($islogin != 1) {
			exit('{"code":-10,"msg":"请先登录"}');
		}
		$a = $DB->get_row("select uid,user,notice from qingka_wangke_user where uid='{$userrow['uuid']}' ");
		$dd = $DB->count("select count(oid) from qingka_wangke_order where uid='{$userrow['uid']}' ");
		if ($userrow['addprice'] < 0.1) {
			$DB->query("update qingka_wangke_user set addprice='1' where uid='{$userrow['uid']}' ");
			jsonReturn(-9, "大佬，我得罪不起您啊，有什么做的不好的地方尽管提出来，我小本生意，经不起折腾，还望多多包涵");
		}
		if ($userrow['uid'] != 1) {
			if ((int) $userrow['money'] - (int) '0.1' > (int) $userrow['zcz']) {
				$DB->query("update qingka_wangke_user set money='$zcz',active='0' where uid='{$userrow['uid']}' ");
				jsonReturn(-9, "账号异常，请联系你老大");
			}
		}
		$dlzs = $DB->count("select count(uid) from qingka_wangke_user where uuid='{$userrow['uid']}' ");
		$dldl = $DB->count("select count(uid) from qingka_wangke_user where uuid='{$userrow['uid']}' and endtime>'$jtdate' ");
		$dlzc = $DB->count("select count(uid) from qingka_wangke_user where uuid='{$userrow['uid']}' and addtime>'$jtdate' ");
		$jrjd = $DB->count("select count(uid) from qingka_wangke_order where uid='{$userrow['uid']}' and addtime>'$jtdate' ");
		$dailitongji = array(
			'dlzc' => $dlzc,
			'dldl' => $dldl,
			'dlxd' => $dlxd,
			'dlzs' => $dlzs,
			'jrjd' => $jrjd
		);
		$data = array(
			'code' => 1,
			'msg' => '查询成功',
			'uid' => $userrow['uid'],
			'user' => $userrow['user'],
			'qq_openid' => $userrow['qq_openid'],
			'nickname' => $userrow['nickname'],
			'faceimg' => $userrow['faceimg'],
			'money' => round($userrow['money'], 2),
			'addprice' => $userrow['addprice'],
			'key' => $userrow['key'],
			'sjuser' => $a['user'],
			'dd' => $dd,
			'pushPlusToken' => $userrow['pushPlusToken'],
			'freeadd' => isset($conf['mfxdkg']) && intval($conf['mfxdkg']) === 1 ? $userrow['freeadd'] : 0,
			'zcz' => $userrow['zcz'],
			'yqm' => $userrow['yqm'],
			'yqlj' => "http://" . $_SERVER['SERVER_NAME'] . "/index/login?yqm=" . $userrow['yqm'],
			'yqprice' => $userrow['yqprice'],
			'notice' => $conf['notice'],
			'sjnotice' => $a['notice'],
			'dailitongji' => $dailitongji,


		);
		exit(json_encode($data));
		break;
	case 'ktapi':
		$type = trim(strip_tags(daddslashes($_GET['type'])));
		$uid = trim(strip_tags(daddslashes($_GET['uid'])));
		$key = random(12);
		if ($type == 1) {
			if ($userrow['money'] < 50) {
				if ($userrow['money'] >= 5) {
					$DB->query("update qingka_wangke_user set `key`='$key',`money`=`money`-5 where uid='{$userrow['uid']}' ");
					wlog($userrow['uid'], "开通接口", "开通接口成功!扣费5元", '-5');
					exit('{"code":1,"msg":"花费5元开通接口成功","key":"' . $key . '"}');
				} else {
					exit('{"code":-1,"msg":"余额不足"}');
				}
			} else {
				$DB->query("update qingka_wangke_user set `key`='$key' where uid='{$userrow['uid']}' ");
				wlog($userrow['uid'], "开通接口", "免费开通接口成功!", '0');
				exit('{"code":1,"msg":"免费开通成功","key":"' . $key . '"}');
			}
		} elseif ($type == 2) {
			if ($userrow['money'] < 5) {
				wlog($userrow['uid'], "开通接口", "尝试给下级UID{$uid}开通接口失败! 原因：余额不足", '0');
				jsonReturn(-2, "余额不足以开通");
			} else {
				if ($uid == "") {
					jsonReturn(-2, "uid不能为空");
				}
				$DB->query("update qingka_wangke_user set `key`='$key' where uid='{$uid}' ");
				$DB->query("update qingka_wangke_user set `money`=`money`-5 where uid='{$userrow['uid']}' ");
				wlog($userrow['uid'], "开通接口", "给下级代理UID{$uid}开通接口成功!扣费5元", '-5');
				wlog($uid, "开通接口", "你上级给你开通API接口成功!", '0');
				exit('{"code":1,"msg":"花费5元开通成功"}');
			}
		} elseif ($type == 3) {
			if ($userrow['key'] == -1) {
				exit('{"code":-1,"msg":"请先开通key""}');
			} elseif ($userrow['key'] != "") {
				$DB->query("update qingka_wangke_user set `key`='$key' where uid='{$userrow['uid']}' ");
				wlog($userrow['uid'], "开通接口", "更换接口{$key}成功", '0');
				exit('{"code":1,"msg":"更换成功","key":"' . $key . '"}');
			}

		}
		jsonReturn(-2, "未知异常");
		break;

	case 'get':
		$cid = trim(strip_tags(daddslashes($_POST['cid'])));

		$userinfo = daddslashes($_POST['userinfo']);
		$hash = daddslashes($_POST['hash']);
		$rs = $DB->get_row("select * from qingka_wangke_class where cid='$cid' limit 1 ");
		$kms = str_replace(array("\r\n", "\r", "\n"), "[br]", $userinfo);
		$info = explode("[br]", $kms);

		$key = 'AES_Encryptwords';
		$iv = '0123456789abcdef';
		$hash = openssl_decrypt($hash, 'aes-128-cbc', $key, 0, $iv);
		$money = $rs['ckkf'] * $userrow['addprice'];
		if ($userrow['money'] < $money && $userrow['uid'] != '1064') {
			exit('{"code":-1,"msg":"余额不足以查课"}');
		}



		if ((empty($_SESSION['addsalt']) || $hash != $_SESSION['addsalt'])) {
			exit('{"code":-1,"msg":"验证失败，请刷新页面重试"}');
		}
		if ($conf['ckkg'] == 1) {
			if ((empty($_SESSION['addsalt']) || $hash != $_SESSION['addsalt'])) {
				exit('{"code":-1,"msg":"验证失败，请刷新页面重试"}');
			}

			for ($i = 0; $i < count($info); $i++) {
				$str = merge_spaces(trim($info[$i]));
				$userinfo2 = explode(" ", $str);
				if (count($userinfo2) > 2) {
					$result = getWk($rs['queryplat'], $rs['getnoun'], trim($userinfo2[0]), trim($userinfo2[1]), trim($userinfo2[2]), $rs['name']);
				} else {
					$result = getWk($rs['queryplat'], $rs['getnoun'], "自动识别", trim($userinfo2[0]), trim($userinfo2[1]), $rs['name']);
				}
				$userinfo3 = trim($userinfo2[0] . " " . $userinfo2[1] . " " . $userinfo2[2]);
				$result['userinfo'] = $userinfo3;
				if ($userrow['uid'] != '1064') {
					$DB->query("update qingka_wangke_user set `money`=`money`-$money where uid='{$userrow['uid']}' ");
					wlog($userrow['uid'], "查课", "{$rs['name']}-查课信息：{$userinfo3}", -$money);
				} else {
					wlog($userrow['uid'], "查课", "{$rs['name']}-查课信息：{$userinfo3}", 0);
				}

			}
			exit(json_encode($result));

		} else {
			exit('{"code":-1,"msg":"管理员已关闭查课功能，使用请联系管理员！"}');
		}

		break;

	case 'pay':
		$zdpay = $conf['zdpay'];
		$money = trim(strip_tags(daddslashes($_POST['money'])));
		$name = "零食购买-" . $money . "";
		if (!preg_match('/^[0-9.]+$/', $money))
			exit('{"code":-1,"msg":"订单金额不合法"}');
		if ($money < $zdpay) {
			jsonReturn(-1, "在线充值最低{$zdpay}元");
		}
		$row = $DB->get_row("select * from qingka_wangke_user where uid='{$userrow['uuid']}' ");
		if ($row['uid'] == '1') {
			$out_trade_no = date("YmdHis") . rand(111, 999);
			$wz = $_SERVER['SERVER_NAME'];
			$sql = "insert into `qingka_wangke_pay` (`out_trade_no`,`uid`,`num`,`name`,`money`,`ip`,`addtime`,`domain`,`status`) values ('" . $out_trade_no . "','" . $userrow['uid'] . "','" . $money . "','" . $name . "','" . $money . "','" . $clientip . "','" . $date . "','" . $wz . "','0')";
			if ($DB->query($sql)) {
				exit('{"code":1,"msg":"生成订单成功！","out_trade_no":"' . $out_trade_no . '","need":"' . $money . '"}');
			} else {
				exit('{"code":-1,"msg":"生成订单失败！' . $DB->error() . '"}');
			}
		} else {
			jsonReturn(-1, "请您联系上家充值。");
		}

		break;

	// 		case 'pay'://在线充值
//     $zdpay = $conf['zdpay'];
//     $money = trim(strip_tags(daddslashes($_POST['money'])));
//     $name = "糖葫芦购买-" . $money . "";
//     if ($conf['zxczkg'] != 0) {
//         if (!preg_match('/^[0-9.]+$/', $money))
//             exit('{"code":-1,"msg":"订单金额不合法"}');
//         $row = $DB->get_row("select * from qingka_wangke_user where uid='{$userrow['uuid']}' ");
//         $has_special_price = false; // 检查用户是否存在密价
//         $special_price_row = $DB->get_row("SELECT * FROM qingka_wangke_mijia WHERE uid='{$userrow['uuid']}'");
//         if ($special_price_row) {
//             $has_special_price = true;
//             $zdpay = 100; 
//         }
//         if ($money < $zdpay) {
//             jsonReturn(-1, "密价用户在线充值最低{$zdpay}元");
//         }
//         $out_trade_no = date("YmdHis") . rand(111, 999); // 生成本地订单号
//         $wz = $_SERVER['SERVER_NAME'];
//         $sql = "insert into `qingka_wangke_pay` (`out_trade_no`,`uid`,`num`,`name`,`money`,`ip`,`addtime`,`domain`,`status`) values ('" . $out_trade_no . "','" . $userrow['uid'] . "','" . $money . "','" . $name . "','" . $money . "','" . $clientip . "','" . $date . "','" . $wz . "','0')";
//         if ($DB->query($sql)) {
//             exit('{"code":1,"msg":"生成订单成功！","out_trade_no":"' . $out_trade_no . '","need":"' . $money . '"}');
//         } else {
//             exit('{"code":-1,"msg":"生成订单失败！' . $DB->error() . '"}');
//         }
//     } else {
//         jsonReturn(-1, "管理员已关闭在线充值，还想给我送钱？能不能联系你上级充？都已经关闭了你访问个毛，卡我网站bug的狗");
//     }
//     break;

	case 'getclass_pl':
		$a = $DB->query("select price,yunsuan,cid,name,sort,noun,content,status from qingka_wangke_class where status=1 and fenlei='wck' order by sort desc");
		while ($row = $DB->fetch($a)) {
			if ($userrow['vip'] == 0) {
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
				$mijia = $DB->get_row("select * from qingka_wangke_mijia where uid='{$userrow['uid']}' and cid='{$row['cid']}' ");
				if ($mijia) {
					if ($mijia['mode'] == 0) {
						$price = round($price - $mijia['price'], 2);
						if ($price <= 0) {
							$price = 0;
						}
					} elseif ($mijia['mode'] == 1) {
						$price = round(($row['price'] - $mijia['price']) * $userrow['addprice'], 2);
						if ($price <= 0) {
							$price = 0;
						}
					} elseif ($mijia['mode'] == 2) {
						$price = $mijia['price'];
						if ($price <= 0) {
							$price = 0;
						}
					}
					$row['name'] = "密*{$row['name']}";
				}
				if ($price >= $price1) { //密价价格大于原价，恢复原价
					$price = $price1;
				}
			} else {
				//会员价
				if ($row['yunsuan'] == "*") {
					// 如果addprice小于0.2，则按最低0.2乘
					$multiplier = $userrow['addprice'] < 0.2 ? 0.2 : $userrow['addprice'];
					$price = round($row['vipprice'] * $multiplier, 2);
				} elseif ($row['yunsuan'] == "+") {
					$price = round($row['vipprice'] + $userrow['addprice'], 2);
				} else {
					// 如果addprice小于0.2，则按最低0.2乘
					$multiplier = $userrow['addprice'] < 0.2 ? 0.2 : $userrow['addprice'];
					$price = round($row['vipprice'] * $multiplier, 2);
				}

				$price1 = $price;
				//密价
				$mijia = $DB->get_row("select * from qingka_wangke_mijia where uid='{$userrow['uid']}' and cid='{$row['cid']}' ");
				if ($mijia) {

					if ($mijia['mode'] == 0) {
						$price = round($price - $mijia['price'], 2);
						if ($price <= 0) {
							$price = 0;
						}
					} elseif ($mijia['mode'] == 1) {
						$price = round(($row['price'] - $mijia['price']) * $userrow['addprice'], 2);
						if ($price <= 0) {
							$price = 0;
						}
					} elseif ($mijia['mode'] == 2) {
						$price = $mijia['price'];
						if ($price <= 0) {
							$price = 0;
						}
					}
					if ($price < $price1) {
						$row['name'] = "密*" . $row['name'];
					} else {
						$row['name'] = "内*" . $row['name'];
					}
				} else {
					$row['name'] = "内*" . $row['name'];
				}
				if ($price >= $price1) { //密价价格大于会员，恢复会员价
					$price = $price1;
				}

			}

			$data[] = array(
				'sort' => $row['sort'],
				'cid' => $row['cid'],
				'name' => $row['name'],
				'noun' => $row['noun'],
				'price' => $price,
				'vipprice' => $row['vipprice'],
				'content' => $row['content'],
				'status' => $row['status'],
				'miaoshua' => $miaoshua
			);
		}
		foreach ($data as $key => $row) {
			$sort[$key] = $row['sort'];
			$cid[$key] = $row['cid'];
			$name[$key] = $row['name'];
			$noun[$key] = $row['noun'];
			$price[$key] = $row['price'];
			$vipprice[$key] = $row['vipprice'];
			$info[$key] = $row['info'];
			$content[$key] = $row['content'];
			$status[$key] = $row['status'];
			$miaoshua[$key] = $row['miaoshua'];
		}
		array_multisort($sort, SORT_ASC, $cid, SORT_DESC, $data);
		$data = array('code' => 1, 'data' => $data);
		exit(json_encode($data));

		break;

	//     case 'add':
// 	   		$cid = trim(strip_tags(daddslashes($_POST['cid'])));
// 		$data = daddslashes($_POST['data']);
// 		$clientip = real_ip();
// 		$rs = $DB->get_row("select * from qingka_wangke_class where cid='$cid' limit 1 ");
// 		if ($conf['xdkg'] == 1) {
// 			if ($cid == '' || $data == '') {
// 				exit('{"code":-1,"msg":"请选择课程"}');
// 			}

	// 			if ($userrow['vip'] == 0) {

	// 				if ($rs['yunsuan'] == "*") {
// 					$danjia = round($rs['price'] * $userrow['addprice'], 2);
// 				} elseif ($rs['yunsuan'] == "+") {
// 					$danjia = round($rs['price'] + $userrow['addprice'], 2);
// 				} else {
// 					$danjia = round($rs['price'] * $userrow['addprice'], 2);
// 				}
// 				//密价
// 				$mijia = $DB->get_row("select * from qingka_wangke_mijia where uid='{$userrow['uid']}' and cid='$cid' ");
// 				if ($mijia) {
// 					if ($mijia['mode'] == 0) {
// 						$danjia = round($danjia - $mijia['price'], 2);
// 						if ($danjia <= 0) {
// 							$danjia = 0;
// 						}
// 					} elseif ($mijia['mode'] == 1) {
// 						$danjia = round(($rs['price'] - $mijia['price']) * $userrow['addprice'], 2);
// 						if ($danjia <= 0) {
// 							$danjia = 0;
// 						}
// 					} elseif ($mijia['mode'] == 2) {
// 						$danjia = $mijia['price'];
// 						if ($danjia <= 0) {
// 							$danjia = 0;
// 						}
// 					}
// 				}
// 			} else {
// 				// 原价计算
// 				if ($rs['yunsuan'] == "*") {
// 					$yj = round($rs['price'] * $userrow['addprice'], 2);
// 				} elseif ($rs['yunsuan'] == "+") {
// 					$yj = round($rs['price'] + $userrow['addprice'], 2);
// 				} else {
// 					$yj = round($rs['price'] * $userrow['addprice'], 2);
// 				}
// 				// 会员价
// 				if ($userrow['vip'] == 1) {
//                   if ($userrow['addprice'] < 0.2) {
//                      $vip1 = round($rs['vipprice'] * 0.2, 2);
//                  } else {
//                      $vip1 = round($rs['vipprice'] * $userrow['addprice'], 2);
//     }
// }

	// 				//密价
// 				$mijia = $DB->get_row("select * from qingka_wangke_mijia where uid='{$userrow['uid']}' and cid='$cid' ");
// 				if ($mijia) {
// 					if ($mijia['mode'] == 0) {
// 						$mj = round($danjia - $mijia['price'], 2);
// 						if ($mj <= 0) {
// 							$mj = 0;
// 						}
// 					} elseif ($mijia['mode'] == 1) {
// 						$mj = round(($shopprice - $mijia['price']) * $userrow['addprice'], 2);
// 						if ($mj <= 0) {
// 							$mj = 0;
// 						}
// 					} elseif ($mijia['mode'] == 2) {
// 						$mj = $mijia['price'];
// 						if ($mj <= 0) {
// 							$mj = 0;
// 						}
// 					}
// 				}

	// 				$variables = [$yj, $mj, $vip1];
// 				$positiveVariables = [];

	// 				foreach ($variables as $var) {
// 					if ($var > 0) {
// 						$positiveVariables[] = $var;
// 					}
// 				}

	// 				if (!empty($positiveVariables)) {
// 					$danjia = min($positiveVariables);
// 				} else {
// 					$danjia = $yj;
// 				}



	// 			}
// 			if ($danjia == 0 || $userrow['addprice'] < 0.1) {
// 				exit('{"code":-1,"msg":"大佬，我得罪不起您，我小本生意，有哪里得罪之处，还望多多包涵"}');
// 			}

	// 			$money = count($data) * $danjia;
// 			if ($userrow['money'] < $money) {
// 				exit('{"code":-1,"msg":"余额不足"}');
// 			}
// 	if ($money >= 0.6 && ($cid == '3' || $cid == '2'|| $cid == '1'|| $cid == '4'|| $cid == '6'|| $cid == '7'|| $cid == '10'|| $cid == '1504'|| $cid == '1505'|| $cid == '3'|| $cid == '2049'|| $cid == '2050'|| $cid == '2051')){
//                     $DB->query("update qingka_wangke_user set money=money+0.03 where uid='{$userrow['uuid']}' limit 1 ");
//                     wlog($userrow['uuid'], "返利", "UID为{$userrow['uid']}的代理下单成功，返利0.03元给上级用户！", +0.05);
//                 }


	// 			foreach ($data as $row) {
// 				$userinfo = $row['userinfo'];
// 				$userName = $row['userName'];
// 				$userinfo = explode(" ", $userinfo); //分割账号密码
// 				if (count($userinfo) > 2) {
// 					$school = $userinfo[0];
// 					$user = $userinfo[1];
// 					$pass = $userinfo[2];
// 				} else {
// 					$school = "自动识别";
// 					$user = $userinfo[0];
// 					$pass = $userinfo[1];
// 				}

	// 				$kcid = $row['data']['id'];
// 				$kcname = $row['data']['name'];
// 				$kcjs = $row['data']['kcjs'];
// 				if ($DB->get_row("select * from qingka_wangke_order where ptname='{$rs['name']}' and school='$school' and user='$user' and pass='$pass' and kcid='$kcid' and kcname='$kcname' ")) {
// 					$dockstatus = '3'; //重复下单
// 					die('{"code":-1,"msg":"重复下单，请取消订单再试！"}');
// 				} elseif ($rs['docking'] == 0) {
// 					$dockstatus = '99';
// 				} else {
// 					$dockstatus = '0';
// 				}
// 				$is = $DB->query("insert into qingka_wangke_order (uid,cid,hid,ptname,school,name,user,pass,kcid,kcname,courseEndTime,fees,noun,miaoshua,addtime,ip,dockstatus,docknum) values ('{$userrow['uid']}','{$rs['cid']}','{$rs['docking']}','{$rs['name']}','{$school}','$userName','$user','$pass','$kcid','$kcname','{$kcjs}','{$danjia}','{$rs['noun']}','$miaoshua','$date','$clientip','$dockstatus',0) "); //将对应课程写入数据库	               	       	      	
// 				if ($is) {
// 					$DB->query("update qingka_wangke_user set money=money-'{$danjia}' where uid='{$userrow['uid']}' limit 1 ");
// 					wlog($userrow['uid'], "添加任务", "  {$rs['name']} {$user} {$pass} {$kcname} 扣除{$danjia}元！", -$danjia);
// 				}
// 			}
// 			if ($is) {
// 				exit('{"code":1,"msg":"提交成功"}');
// 			} else {
// 				exit('{"code":-1,"msg":"提交失败"}');
// 			}
// 		} else {
// 			exit('{"code":-1,"msg":"管理员已关闭下单功能，使用请联系管理员！"}');
// 		}
// 		break;




	case 'add': //处理下单
		$cid = trim(strip_tags(daddslashes($_POST['cid'])));
		$data = daddslashes($_POST['data']);
		$clientip = real_ip();
		$region = daddslashes($_POST['region']);
		$rs = $DB->get_row("select * from qingka_wangke_class where cid='$cid' limit 1");
		if ($conf['xdkg'] == 1) {
			if ($cid == '' || $data == '') {
				exit('{"code":-1,"msg":"请选择课程"}');
			}
			$isFreeOrder = false;
			$totalCourses = count($data);
			// 免费下单逻辑
			// 假设 $conf['mfxd'] 是一个字符串，例如 "3,2,1"
			$validCids = explode(',', $conf['mfxd']); // 将字符串分割成数组

			if (isset($conf['mfxdkg']) && intval($conf['mfxdkg']) === 1
				&& $userrow['freeadd'] >= $totalCourses && in_array($cid, $validCids)) {
				$danjia = 0;
				$userrow['freeadd'] -= $totalCourses; // 根据课程数量减少免费次数
				$DB->query("update qingka_wangke_user set freeadd=freeadd-{$totalCourses} where uid='{$userrow['uid']}' limit 1");
				$isFreeOrder = true;
			} else {
				if ($userrow['vip'] == 1) {
					if ($userrow['addprice'] < 0.15) {
						$danjia = round($rs['vipprice'] * 0.15, 2);
					} else {
						$danjia = round($rs['vipprice'] * $userrow['addprice'], 2);
					}
				} else {
					if ($rs['yunsuan'] == "*") {
						$danjia = round($rs['price'] * $userrow['addprice'], 2);
					} elseif ($rs['yunsuan'] == "+") {
						$danjia = round($rs['price'] + $userrow['addprice'], 2);
					} else {
						$danjia = round($rs['price'] * $userrow['addprice'], 2);
					}
				}

				// 密价逻辑
				$mijia = $DB->get_row("select * from qingka_wangke_mijia where uid='{$userrow['uid']}' and cid='$cid'");
				if ($mijia) {
					if ($mijia['mode'] == 0) {
						$danjia = max(round($danjia - $mijia['price'], 2), 0);
					} elseif ($mijia['mode'] == 1) {
						$danjia = max(round(($rs['price'] - $mijia['price']) * $userrow['addprice'], 2), 0);
					} elseif ($mijia['mode'] == 2) {
						$danjia = max($mijia['price'], 0);
					}
				}
			}

			// 检查价格是否有效
			if ($danjia <= -0.01 || $userrow['addprice'] < 0.1) {
				exit('{"code":-1,"msg":"大佬，我得罪不起您，我小本生意，有哪里得罪之处，还望多多包涵"}');
			}

			$money = $totalCourses * $danjia;
			if ($userrow['money'] < $money) {
				exit('{"code":-1,"msg":"余额不足"}');
			}
			$selectedProxy = getRandomProxy($region);
			// 开始处理下单
			foreach ($data as $row) {
				$userinfo = $row['userinfo'];
				$userName = $row['userName'];
				$userinfo = explode(" ", $userinfo); // 分割账号密码
				if (count($userinfo) > 2) {
					$school = $userinfo[0];
					$user = $userinfo[1];
					$pass = $userinfo[2];
				} else {
					$school = "自动识别";
					$user = $userinfo[0];
					$pass = $userinfo[1];
				}

				$kcid = $row['data']['id'];
				$kcname = $row['data']['name'];
				$kcjs = $row['data']['kcjs'];

				// 查询数据库中最新的订单
				$order = $DB->get_row("SELECT * FROM qingka_wangke_order WHERE ptname='{$rs['name']}' AND school='$school' AND user='$user' AND pass='$pass' AND kcid='$kcid' AND kcname='$kcname' ORDER BY addtime DESC LIMIT 1");

				// 检查是否有订单记录
				if ($order) {
					// 检查订单对接状态
					if ($order['dockstatus'] == '3') {
						die('{"code":-1,"msg":"重复下单，请取消订单再试！"}');
					} elseif ($order['dockstatus'] == '4') {
						if ($rs['docking'] == 0) {
							$dockstatus = '99'; // 不允许对接
						} else {
							$dockstatus = '0'; // 允许对接
						}
					} else {
						die('{"code":-1,"msg":"重复下单，请取消订单再试！！"}');
					}
				} else {
					// 如果没有找到订单记录
					if ($rs['docking'] == 0) {
						$dockstatus = '99'; // 不允许对接
					} else {
						$dockstatus = '0'; // 允许对接
					}
				}

				if ($isFreeOrder) {
					wlog($userrow['uid'], "免费下单", "  {$rs['name']} {$user} {$pass} {$kcname} 免费下单！", 0);
				} else {
					wlog($userrow['uid'], "添加任务", "  {$rs['name']} {$user} {$pass} {$kcname} 扣除{$danjia}元！", -$danjia);
				}

				$is = $DB->query("insert into qingka_wangke_order (uid, cid, hid, ptname, school, name, user, pass, kcid, kcname, courseEndTime, fees, noun, miaoshua, addtime, ip, dockstatus, docknum,region) values ('{$userrow['uid']}', '{$rs['cid']}', '{$rs['docking']}', '{$rs['name']}', '$school', '$userName', '$user', '$pass', '$kcid', '$kcname', '{$kcjs}', '{$danjia}', '{$rs['noun']}', '$miaoshua', '$date', '$clientip', '$dockstatus', 0, '$selectedProxy')");

				if (!$isFreeOrder) {
					$DB->query("update qingka_wangke_user set money=money-'{$danjia}' where uid='{$userrow['uid']}' limit 1");
				}
			}

			if ($is) {
				exit('{"code":1,"msg":"提交成功"}');
			} else {
				exit('{"code":-1,"msg":"提交失败"}');
			}
		} else {
			exit('{"code":-1,"msg":"管理员已关闭下单功能，使用请联系管理员！"}');
		}
		break;
















	// case 'add': //处理下单
//     $cid = trim(strip_tags(daddslashes($_POST['cid'])));
//     $data = daddslashes($_POST['data']);
//     $clientip = real_ip();
//     $rs = $DB->get_row("select * from qingka_wangke_class where cid='$cid' limit 1");
//     if ($conf['xdkg'] == 1) {
//         if ($cid == '' || $data == '') {
//             exit('{"code":-1,"msg":"请选择课程"}');
//         }

	//         $isFreeOrder = false; // Flag to determine if the order is a free order
//         $totalCourses = count($data); // 计算订单中课程的总数




	//         // 免费下单逻辑
//         if ($userrow['freeadd'] >= $totalCourses && ($cid == '3' || $cid == '2'|| $cid == '1'|| $cid == '4'|| $cid == '6'|| $cid == '7'|| $cid == '10'|| $cid == '1504'|| $cid == '1505'|| $cid == '3'|| $cid == '2049'|| $cid == '2050'|| $cid == '2051')) {
//             $danjia = 0;
//             $userrow['freeadd'] -= $totalCourses; // 根据课程数量减少免费次数
//             $DB->query("update qingka_wangke_user set freeadd=freeadd-{$totalCourses} where uid='{$userrow['uid']}' limit 1");
//             $isFreeOrder = true; // Set the flag as true for free order
//         } else {
//             // 正常下单计算逻辑
//             if ($userrow['vip'] == 1) {
//                 if ($userrow['addprice'] < 0.15) {
//                     $danjia = round($rs['vipprice'] * 0.15, 2);
//                 } else {
//                     $danjia = round($rs['vipprice'] * $userrow['addprice'], 2);
//                 }
//             } else {
//                 if ($rs['yunsuan'] == "*") {
//                     $danjia = round($rs['price'] * $userrow['addprice'], 2);
//                 } elseif ($rs['yunsuan'] == "+") {
//                     $danjia = round($rs['price'] + $userrow['addprice'], 2);
//                 } else {
//                     $danjia = round($rs['price'] * $userrow['addprice'], 2);
//                 }
//             }

	//             // 密价逻辑
//             $mijia = $DB->get_row("select * from qingka_wangke_mijia where uid='{$userrow['uid']}' and cid='$cid'");
//             if ($mijia) {
//                 if ($mijia['mode'] == 0) {
//                     $danjia = max(round($danjia - $mijia['price'], 2), 0);
//                 } elseif ($mijia['mode'] == 1) {
//                     $danjia = max(round(($rs['price'] - $mijia['price']) * $userrow['addprice'], 2), 0);
//                 } elseif ($mijia['mode'] == 2) {
//                     $danjia = max($mijia['price'], 0);
//                 }
//             }
//         }

	//         // 检查价格是否有效
//         if ($danjia <= -0.01 || $userrow['addprice'] < 0.1) {
//             exit('{"code":-1,"msg":"大佬，我得罪不起您，我小本生意，有哪里得罪之处，还望多多包涵"}');
//         }

	//         $money = $totalCourses * $danjia;
//         if ($userrow['money'] < $money) {
//             exit('{"code":-1,"msg":"余额不足"}');
//         }

	//         // 开始处理下单
//         foreach ($data as $row) {
//             $userinfo = $row['userinfo'];
//             $userName = $row['userName'];
//             $userinfo = explode(" ", $userinfo); // 分割账号密码
//             if (count($userinfo) > 2) {
//                 $school = $userinfo[0];
//                 $user = $userinfo[1];
//                 $pass = $userinfo[2];
//             } else {
//                 $school = "自动识别";
//                 $user = $userinfo[0];
//                 $pass = $userinfo[1];
//             }

	//             $kcid = $row['data']['id'];
//             $kcname = $row['data']['name'];
//             $kcjs = $row['data']['kcjs'];

	//             // 查询数据库中最新的订单
//             $order = $DB->get_row("SELECT * FROM qingka_wangke_order WHERE ptname='{$rs['name']}' AND school='$school' AND user='$user' AND pass='$pass' AND kcid='$kcid' AND kcname='$kcname' ORDER BY addtime DESC LIMIT 1");

	//             // 检查是否有订单记录
//             if ($order) {
//                 // 检查订单对接状态
//                 if ($order['dockstatus'] == '3') {
//                     die('{"code":-1,"msg":"重复下单，请取消订单再试！"}');
//                 } elseif ($order['dockstatus'] == '4') {
//                     if ($rs['docking'] == 0) {
//                         $dockstatus = '99'; // 不允许对接
//                     } else {
//                         $dockstatus = '0'; // 允许对接
//                     }
//                 } else {
//                     die('{"code":-1,"msg":"重复下单，请取消订单再试！！"}');
//                 }
//             } else {
//                 // 如果没有找到订单记录
//                 if ($rs['docking'] == 0) {
//                     $dockstatus = '99'; // 不允许对接
//                 } else {
//                     $dockstatus = '0'; // 允许对接
//                 }
//             }

	//             if ($isFreeOrder) {
//                 wlog($userrow['uid'], "免费下单", "  {$rs['name']} {$user} {$pass} {$kcname} 免费下单！", 0);
//             } else {
//                 wlog($userrow['uid'], "添加任务", "  {$rs['name']} {$user} {$pass} {$kcname} 扣除{$danjia}元！", -$danjia);
//             }

	//             $is = $DB->query("insert into qingka_wangke_order (uid, cid, hid, ptname, school, name, user, pass, kcid, kcname, courseEndTime, fees, noun, miaoshua, addtime, ip, dockstatus, docknum) values ('{$userrow['uid']}', '{$rs['cid']}', '{$rs['docking']}', '{$rs['name']}', '$school', '$userName', '$user', '$pass', '$kcid', '$kcname', '{$kcjs}', '{$danjia}', '{$rs['noun']}', '$miaoshua', '$date', '$clientip', '$dockstatus', 0)");

	//             if (!$isFreeOrder) {
//                 $DB->query("update qingka_wangke_user set money=money-'{$danjia}' where uid='{$userrow['uid']}' limit 1");
//             }
//         }

	//         if ($is) {
//             exit('{"code":1,"msg":"提交成功"}');
//         } else {
//             exit('{"code":-1,"msg":"提交失败"}');
//         }
//     } else {
//         exit('{"code":-1,"msg":"管理员已关闭下单功能，使用请联系管理员！"}');
//     }
//     break;







	case 'bs':
		$oid = trim(strip_tags(daddslashes($_GET['oid'])));
		$b = $DB->get_row("select hid,cid,dockstatus from qingka_wangke_order where oid='{$oid}' ");
		$DB->query("update qingka_wangke_order set status='补刷中',`bsnum`=bsnum+1 where oid='{$oid}' ");
		if ($b['dockstatus'] == '99') {
			jsonReturn(1, "成功加入线程，排队补刷中");
		} else {


			$b = budanWk($oid);
			if ($b['code'] == 1) {
				$DB->query("update qingka_wangke_order set status='补刷中',`bsnum`=bsnum+1 where oid='{$oid}' ");
				jsonReturn(1, $b['msg']);
			} else {
				jsonReturn(-1, $b['msg']);
			}
		}


		//  $oid=trim(strip_tags(daddslashes($_GET['oid'])));
//       $b=$DB->get_row("select hid,cid,dockstatus from qingka_wangke_order where oid='{$oid}' "); 
// 	   $DB->query("update qingka_wangke_order set status='补刷中',`bsnum`=bsnum+1 where oid='{$oid}' ");
// 	   if($b['dockstatus']=='99'){
//              jsonReturn(1,"成功加入线程，排队补刷中");       
//       }else{


		//         	  $b=budanWk($oid);
//         	  if($b['code']==1){
//         	  	$DB->query("update qingka_wangke_order set status='补刷中',`bsnum`=bsnum+1 where oid='{$oid}' ");
//         	  	$DB->query("update qingka_wangke_user set money=money-0.05 where uid='{$userrow['uid']}' limit 1 ");
//       wlog($userrow['uid'], "提交补刷", "订单{$oid}提交补刷成功扣除0.05", -0.05);
//         	  	jsonReturn(1,$b['msg']);
//         	  }else{
//         	  	jsonReturn(-1,$b['msg']);
//         	  }          
// 	    }  








		break;

	// 	case 'uporder'://进度刷新
// 		$oid = trim(strip_tags(daddslashes($_GET['oid'])));
// 		$row = $DB->get_row("select * from qingka_wangke_order where oid='$oid'");
// 		if ($row['hid'] == '1112') {
// 			exit('{"code":-2,"msg":"当前订单接口异常，请去查询补单","url":"http://ck.wmv.life"}');
// 		} elseif ($row['dockstatus'] == '5') {
// 			// // $result=pre_zy($oid);
// 			// exit(json_encode($result));
// 			jsonReturn(1, '实时进度无需更新');
// 		}
// 		$result = processCx($oid);
// 		for ($i = 0; $i < count($result); $i++) {
// 			$DB->query("update qingka_wangke_order set `name`='{$result[$i]['name']}',`yid`='{$result[$i]['yid']}',`status`='{$result[$i]['status_text']}',`courseStartTime`='{$result[$i]['kcks']}',`finalupdate`='{$result[$i]['zhgx']}',`courseEndTime`='{$result[$i]['kcjs']}',`examStartTime`='{$result[$i]['ksks']}',`examEndTime`='{$result[$i]['ksjs']}',`process`='{$result[$i]['process']}',`remarks`='{$result[$i]['remarks']}' where `user`='{$result[$i]['user']}' and `kcname`='{$result[$i]['kcname']}' and `oid`='{$oid}'");
// 		}
// 		exit('{"code":1,"msg":"同步成功"}');
// 		break;
	case 'uporder': //进度刷新
		$oid = trim(strip_tags(daddslashes($_GET['oid'])));
		$row = $DB->get_row("select * from qingka_wangke_order where oid='$oid'");
		if ($row['hid'] == '0') {
			exit('{"code":1,"msg":"实时进度，无需刷新","url":""}');
		} elseif ($row['dockstatus'] == '4') {
			exit('{"code":-1,"msg":"该订单已取消，不允许刷新"}'); // 订单已取消，不允许刷新
		} elseif ($row['dockstatus'] == '99') {
			$result = pre_zy($oid);
			exit(json_encode($result));
		}
		$result = processCx($oid);
		for ($i = 0; $i < count($result); $i++) {
			$DB->query("update qingka_wangke_order set `name`='{$result[$i]['name']}',
			`yid`='{$result[$i]['yid']}',
			`status`='{$result[$i]['status_text']}',
			`courseStartTime`='{$result[$i]['kcks']}',
			`courseEndTime`='{$result[$i]['kcjs']}',
			`examStartTime`='{$result[$i]['ksks']}',
			`examEndTime`='{$result[$i]['ksjs']}',
			`process`='{$result[$i]['process']}',
			`finalupdate`='{$result[$i]['zhgx']}',
			`remarks`='{$result[$i]['remarks']}' 
			where `user`='{$result[$i]['user']}' and `kcname`='{$result[$i]['kcname']}' and `oid`='{$oid}'");
		}
		exit('{"code":1,"msg":"同步成功"}');
		break;

	// 	case 'ms_order'://列表提交秒刷
// 		$oid = trim(strip_tags(daddslashes($_GET['oid'])));
// 		$b = $DB->get_row("select cid,dockstatus from qingka_wangke_order where oid='{$oid}' ");
// 		if ($b['dockstatus'] == '99') {
// 			jsonReturn(1, "我的订单");
// 		} else {
// 			$b = msWk($oid);
// 			if ($b['code'] == 1) {
// 				$DB->query("update qingka_wangke_user set money=money-0.05 where uid='{$userrow['uid']}' limit 1 ");
// 				wlog($userrow['uid'], "提交秒刷", "订单{$oid}提交秒刷成功扣除0.05", -0.05);
// 				jsonReturn(1, $b['msg']);
// 			} else {
// 				jsonReturn(-1, $b['msg']);
// 			}
// 		}
// 		break;
	case 'ms_order': //列表提交秒刷
		$oid = trim(strip_tags(daddslashes($_GET['oid'])));
		$b = $DB->get_row("select hid,cid,dockstatus from qingka_wangke_order where oid='{$oid}' ");
		$sql = "UPDATE qingka_wangke_order SET dockstatus = 0, noun = '学习通(快刷)' , miaoshua=1 WHERE oid = '{$oid}'";

		if ($DB->query($sql)) {
			$DB->query("update qingka_wangke_user set money=money-0.05 where uid='{$userrow['uid']}' limit 1 ");
			wlog($userrow['uid'], "提交秒刷", "订单{$oid}提交秒刷成功扣除0.05", -0.05);
			$b = ztWk($oid);
			if ($b['code'] == 1) {
				jsonReturn(1, "提交秒刷成功之前订单已暂停");
			}
		} else {
			jsonReturn(-1, "提交秒刷失败");
		}
		break;


	case 'qx_order': // 取消订单
		$oid = trim(strip_tags(daddslashes($_GET['oid'])));
		$row = $DB->get_row("select * from qingka_wangke_order where oid='{$oid}' ");
		if ($row['uid'] != $userrow['uid'] && $userrow['uid'] != 1) {
			jsonReturn(-1, "无权限");
		} else {

			$DB->query("update qingka_wangke_order set `status`='已取消',`dockstatus`=4 where oid='$oid' ");
			$b = $DB->get_row("select hid,cid,dockstatus from qingka_wangke_order where oid='{$oid}' ");
			if ($b['dockstatus'] == '99') {
				jsonReturn(1, "订单已取消并成功暂停");
			} else {
				$b = ztWk($oid);
				if ($b['code'] == 1) {
					$DB->query("update qingka_wangke_order set status='已取消',`bsnum`=bsnum+1 where oid='$oid' ");
					wlog($userrow['uid'], "暂停", "暂停并取消订单: {$oid}", 0);
					jsonReturn(1, "订单已取消并" . $b['msg']);
				} else {
					jsonReturn(1, "订单已取消" . $b['msg']);
				}
			}
			// 订单取消成功
			jsonReturn(1, "取消成功");
		}
		break;

	case 'orderlist':
		$cx = daddslashes($_POST['cx']);

		$limit = $cx['limit'];
		if ($limit == "") {
			$pagesize = 20;
		} else {
			$pagesize = $limit;
		}
		$page = trim(strip_tags(daddslashes($_POST['page'])));
		$pageu = ($page - 1) * $pagesize;
		//当前界面
		$qq = trim(strip_tags($cx['qq']));
		$status_text = trim(strip_tags($cx['status_text']));
		$dock = trim(strip_tags($cx['dock']));
		$cid = trim(strip_tags($cx['cid']));
		$oid = trim(strip_tags($cx['oid']));
		$uid = trim(strip_tags($cx['uid']));
		$mh = trim(strip_tags($cx['mh']));
		$kcname = trim(strip_tags($cx['kcname']));
		$search = trim(strip_tags($cx['search']));
		$ptname = trim(strip_tags($cx['ptname']));
		$remarks = trim(strip_tags($cx['remarks']));
		$pass = trim(strip_tags($cx['pass']));
		$school = trim(strip_tags($cx['school']));
		if ($userrow['uid'] != '1') {
			$sql1 = "where uid='{$userrow['uid']}'";
		} else {
			$sql1 = "where 1=1";
		}
		if ($cid != '') {
			$sql2 = " and cid='{$cid}'";
		}
		if ($qq != '') {
			$sql3 = " and user='{$qq}'";
		}
		if ($oid != '') {
			$sql4 = " and oid='{$oid}'";
		}
		if ($uid != '') {
			$sql5 = " and uid='{$uid}'";
		}
		if ($kcname != '') {
			$sql6 = "  and kcname like '%" . $kcname . "%'";
		}
		if ($remarks != '') {
			$sql7 = " and remarks like '%" . $remarks . "%' ";
		}
		if ($school != '') {
			$sql8 = " and school like '%" . $school . "%' ";
		}
		if ($status_text != '') {
			$sql9 = " and status='{$status_text}'";
		}

		if ($ptname != '') {
			$ptnameA = $DB->query("select cid from qingka_wangke_class where name like '%" . $ptname . "%'");
			while ($row = $DB->fetch($ptnameA)) {
				$ptnameB = $row['cid'] . ',' . $ptnameB;
			}
			$ptnameB = rtrim($ptnameB, ',');
			$sql10 = " and cid in ({$ptnameB})";
		}


		if ($pass != '') {
			$sql11 = " and pass='{$pass}'";
		}

		if ($school != '') {
			$sql12 = " and school like '%" . $school . "%' ";
		}
		if ($dock != '') {
			$sql13 = " and dockstatus='{$dock}'";
		}

		if ($mh != '' && $search != '') {
			$sql14 .= " and {$search} LIKE '%{$mh}%'";
		} else if ($mh != '') {
			// 如果没有提供search参数，则对所有字段进行模糊查询
			$sql15 .= " and (uid LIKE '%{$mh}%' or oid LIKE '%{$mh}%' or user LIKE '%{$mh}%'or ptname LIKE '%{$mh}%' or kcname LIKE '%{$mh}%' or school LIKE '%{$mh}%' or process LIKE '%{$mh}%' or remarks LIKE '%{$mh}%' or pass LIKE '%{$mh}%')";
		}

		$sql = $sql1 . $sql2 . $sql2 . $sql4 . $sql5 . $sql6 . $sql7 . $sql8 . $sql9 . $sql10 . $sql11 . $sql12 . $sql13 . $sql14 . $sql15;
		$a = $DB->query("select * from qingka_wangke_order {$sql} order by oid desc limit $pageu,$pagesize ");
		$count1 = $DB->count("select count(*) from qingka_wangke_order {$sql} ");
		while ($row = $DB->fetch($a)) {
			if ($row['name'] == '' || $row['name'] == 'undefined') {
				$row['name'] = 'null';
			}
			$data[] = $row;
		}
		$last_page = ceil($count1 / $pagesize);
		//取最大页数
		$data = array('code' => 1, 'data' => $data, "current_page" => (int) $page, "last_page" => $last_page, "uid" => (int) $userrow['uid']);
		exit(json_encode($data));
		break;











// 	case 'yjdj':
// 		$page = trim(strip_tags(daddslashes($_GET['page'])));
// 		$pagesize = trim(strip_tags(daddslashes($_GET['limit'])));
// 		$hid = trim(strip_tags(daddslashes($_GET['hid'])));
// 		$type = trim(strip_tags(daddslashes($_GET['type'])));
// 		$cb = trim(strip_tags(daddslashes($_GET['cb'])));
// 		$name = trim(strip_tags(daddslashes($_GET['name'])));
// 		$fenlei = trim(strip_tags(daddslashes($_GET['fenlei'])));
// 		$sort = trim(strip_tags(daddslashes($_GET['sort'])));
// 		$djfl = trim(strip_tags(daddslashes($_GET['djfl'])));
// 		$pageu = ($page - 1) * $pagesize;
// 		if ($userrow['uid'] != 1) {
// 			exit('{"code":-1,"msg":"无权限"}');
// 		}
// 		if ($hid == '') {
// 			exit('{"code":-1,"msg":"请先选择对接平台"}');
// 		}
// 		if ($type == '') {
// 			exit('{"code":-1,"msg":"请先选择对接类型"}');
// 		}
// 		if ($fenlei == '') {
// 			exit('{"code":-1,"msg":"请先选择分类"}');
// 		}
// 		$b = yjdj($hid, $type, $cb, $name, $fenlei, $sort, $djfl);
// 		exit(json_encode($b));
// 		break;
// 	case 'yjadd':
// 		$row = daddslashes($_POST['data']);
// 		if ($userrow['uid'] == 1) {
// 			if ($row['status'] == 1) {
// 				exit('{"code":-1,"msg":"项目已上架"}');
// 			}
// 			if ($row['kcid'] != 1 && $row['kcid'] != 0) {
// 				$row['kcid'] != 0;
// 			}
// 			if ($DB->query("insert into qingka_wangke_class (sort,name,getnoun,noun,price,queryplat,docking,content,addtime,status,fenlei,kcid) values ('{$row['sort']}','{$row['name']}','{$row['cid']}','{$row['cid']}','{$row['price']}','{$row['hid']}','{$row['hid']}','{$row['content']}','{$date}','1','{$row['fenlei']}','{$row['kcid']}')")) {
// 				exit('{"code":1,"msg":"操作成功"}');
// 			} else {
// 				exit('{"code":-1,"msg":"数据库连接失败"}');
// 			}
// 		} else {
// 			exit('{"code":-1,"msg":"无权限"}');
// 		}
// 		break;
// 	case 'plsj':
// 		$sex = daddslashes($_POST['sex']);
// 		if (empty($sex)) {
// 			jsonReturn(-1, "请先选择订单");
// 		}
// 		if ($userrow['uid'] != 1) {
// 			exit('{"code":-1,"msg":"无权限"}');
// 		}
// 		$z = 0;
// 		$p = 0;
// 		$p = 0;
// 		$o = 0;
// 		;
// 		$i = 0;
// 		foreach ($sex as $k => $row) {
// 			$i++;
// 			if ($row['status'] == 1) {
// 				$o++;
// 			} else {
// 				if ($row['kcid'] != 1 && $row['kcid'] != 0) {
// 					$row['kcid'] != 0;
// 				}
// 				if ($DB->query("insert into qingka_wangke_class (sort,name,getnoun,noun,price,queryplat,docking,content,addtime,status,fenlei,kcid) values ('{$row['sort']}','{$row['name']}','{$row['cid']}','{$row['cid']}','{$row['price']}','{$row['hid']}','{$row['hid']}','{$row['content']}','{$date}','1','{$row['fenlei']}','{$row['kcid']}')")) {
// 					$z++;
// 				} else {
// 					$p++;
// 				}
// 			}
// 		}
// 		wlog($userrow['uid'], "批量上架", "共批量上架{$i}条，成功上架{$z}条，重复项目{$o}条，上架失败{$p}条", 0);
// 		jsonReturn(1, "共批量上架{$i}条，成功上架{$z}条，重复项目{$o}条，上架失败{$p}条");
// 		break;
// 	case 'yjtbjg':
// 		$row = daddslashes($_POST['data']);
// 		if ($userrow['uid'] == 1) {
// 			if ($row['price2'] == $row['price']) {
// 				exit('{"code":-1,"msg":"价格与对接站一致无需同步"}');
// 			}
// 			if ($row['status'] == 0) {
// 				exit('{"code":-1,"msg":"请先上架"}');
// 			}
// 			if ($DB->query("update `qingka_wangke_class` set `price`='{$row['price']}' where cid='{$row['id']}' ")) {
// 				exit('{"code":1,"msg":"操作成功"}');
// 			} else {
// 				exit('{"code":-1,"msg":"数据库连接失败"}');
// 			}
// 		} else {
// 			exit('{"code":-1,"msg":"无权限"}');
// 		}
// 		break;
// 	case 'pltb':
// 		$sex = daddslashes($_POST['sex']);
// 		if (empty($sex)) {
// 			jsonReturn(-1, "请先选择订单");
// 		}
// 		if ($userrow['uid'] != 1) {
// 			exit('{"code":-1,"msg":"无权限"}');
// 		}
// 		$z = 0;
// 		$p = 0;
// 		$p = 0;
// 		$o = 0;
// 		$e = 0;
// 		$i = 0;
// 		foreach ($sex as $k => $row) {
// 			$i++;
// 			if ($row['price2'] == $row['price']) {
// 				$o++;
// 			} else {
// 				if ($row['status'] == 1) {
// 					if ($DB->query("update `qingka_wangke_class` set `price`='{$row['price']}' where cid='{$row['id']}' ")) {
// 						$z++;
// 					} else {
// 						$p++;
// 					}
// 				} else {
// 					$e++;
// 				}

// 			}
// 		}
// 		wlog($userrow['uid'], "批量同步价格", "共批量同步{$i}条，成功同步{$z}条，价格一致无需{$o}条，未上架{$e}条，同步失败{$p}条", 0);
// 		jsonReturn(1, "共批量同步{$i}条，成功同步{$z}条，价格一致无需{$o}条，未上架{$e}条，同步失败{$p}条");
// 		break;






	case 'duijie':
		$oid = trim(strip_tags(daddslashes($_GET['oid'])));
		$b = $DB->get_row("select * from qingka_wangke_order where oid='$oid' limit 1 ");
		if ($userrow['uid'] != 1) {
			exit('{"code":-2,"msg":"无权限"}');
		}
		$d = $DB->get_row("select * from qingka_wangke_class where cid='{$b['cid']}' ");
		$result = addWk($oid);
		if ($result['code'] == '1') {
			$DB->query("update qingka_wangke_order set `hid`='{$d['docking']}',`status`='进行中',`dockstatus`=1,`yid`='{$result['yid']}',`remarks`='订单已录入服务器，等待进程自动开始' where oid='{$oid}' "); //对接成功           
		} else {
			$DB->query("update qingka_wangke_order set `dockstatus`=2 where oid='{$oid}' ");
		}
		exit(json_encode($result, true));
		break;
	case 'plzt':
		$sex = daddslashes($_POST['sex']);
		$rediscode = $redis->ping();
		if ($rediscode == true) {
			for ($i = 0; $i < count($sex); $i++) {
				$oid = $sex[$i];
				$redis->lPush("plztoid", $oid);

			}
			wlog($userrow['uid'], "批量同步状态", "批量同步状态入队成功，共入队{$i}条", 0);
			jsonReturn(1, "批量同步状态入队成功，共入队{$i}条，请耐心等待同步");
		} else {
			jsonReturn(-1, "入队失败");
		}

		break;
	case 'plbs1':
		$a = trim(strip_tags(daddslashes($_GET['a'])));
		$sex = daddslashes($_POST['sex']);
		$type = trim(strip_tags(daddslashes($_POST['type'])));
		if ($a == " " or empty($sex)) {
			jsonReturn(-1, "请先选择订单");
		}
		if ($userrow['uid'] != 1 && $a != "待重刷") {
			jsonReturn(-1, "老铁，求您别干我");
		}

		if ($type == 1) {
			$sql = "`status`='$a'";
		} elseif ($type == 2) {
			$sql = "`dockstatus`='$a'";
		}

		if ($a == "待重刷") {
			$count = count($sex);
			for ($i = 0; $i < count($sex); $i++) {
				$oid = $sex[$i];
				$b = $DB->query("update qingka_wangke_order set {$sql} ,`bsnum`=bsnum+1 where oid='{$oid}' ");
			}
			wlog($userrow['uid'], "批量重刷", "批量重刷了 $count 条订单", 0);
			if ($b) {
				jsonReturn(1, "重刷成功");
			} else {
				jsonReturn(-1, "未知异常");
			}
		} else {
			exit('{"code":-1,"msg":"无权限"}');
		}
		break;
	case 'plbs':
		$sex = daddslashes($_POST['sex']);
		$rediscode = $redis->ping();
		if ($rediscode == true) {
			for ($i = 0; $i < count($sex); $i++) {
				$oid = $sex[$i];
				$redis->lPush("plbsoid", $oid);
				$DB->query("update qingka_wangke_order set status='待重刷' where oid='{$oid}' ");
			}
			wlog($userrow['uid'], "批量补刷", "批量补刷入队成功，共入队{$i}条", 0);
			jsonReturn(1, "批量同步状态入队成功，共入队{$i}条，请耐心等待补刷成功");
		} else {
			jsonReturn(-1, "入队失败");
		}
		break;

	// 	case 'getclass':

	//     $fenlei=trim(strip_tags(daddslashes($_POST['id'])));

	// 	  if ($fenlei == "") {
// 			$a = $DB->query("select * from qingka_wangke_class where status=1 and fenlei<>0 order by sort desc");
// 		} else {
// 			$a = $DB->query("select * from qingka_wangke_class where status=1 and fenlei='$fenlei' order by sort desc");
// 		}




	// 	    while ($row = $DB->fetch($a)) {
// 			if ($userrow['vip'] == 0) {
// 				if ($row['yunsuan'] == "*") {
// 					$price = round($row['price'] * $userrow['addprice'], 2);
// 					$price1 = $price;
// 				} elseif ($row['yunsuan'] == "+") {
// 					$price = round($row['price'] + $userrow['addprice'], 2);
// 					$price1 = $price;
// 				} else {
// 					$price = round($row['price'] * $userrow['addprice'], 2);
// 					$price1 = $price;
// 				}
// 				//密价
// 				$mijia = $DB->get_row("select * from qingka_wangke_mijia where uid='{$userrow['uid']}' and cid='{$row['cid']}' ");
// 				if ($mijia) {
// 					if ($mijia['mode'] == 0) {
// 						$price = round($price - $mijia['price'], 2);
// 						if ($price <= 0) {
// 							$price = 0;
// 						}
// 					} elseif ($mijia['mode'] == 1) {
// 						$price = round(($row['price'] - $mijia['price']) * $userrow['addprice'], 2);
// 						if ($price <= 0) {
// 							$price = 0;
// 						}
// 					} elseif ($mijia['mode'] == 2) {
// 						$price = $mijia['price'];
// 						if ($price <= 0) {
// 							$price = 0;
// 						}
// 					}
// 					$row['name'] = "密*{$row['name']}";
// 				}
// 				if ($price >= $price1) { //密价价格大于原价，恢复原价
// 					$price = $price1;
// 				}
// 			} else {
// 				//会员价
// 				if ($row['yunsuan'] == "*") {
//     // 如果addprice小于0.2，则按最低0.2乘
//     $multiplier = $userrow['addprice'] < 0.2 ? 0.2 : $userrow['addprice'];
//     $price = round($row['vipprice'] * $multiplier, 2);
// } elseif ($row['yunsuan'] == "+") {
//     $price = round($row['vipprice'] + $userrow['addprice'], 2);
// } else {
//     // 如果addprice小于0.2，则按最低0.2乘
//     $multiplier = $userrow['addprice'] < 0.2 ? 0.2 : $userrow['addprice'];
//     $price = round($row['vipprice'] * $multiplier, 2);
// }

	// $price1 = $price;
// 				//密价
// 				$mijia = $DB->get_row("select * from qingka_wangke_mijia where uid='{$userrow['uid']}' and cid='{$row['cid']}' ");
// 				if ($mijia) {

	// 					if ($mijia['mode'] == 0) {
// 						$price = round($price - $mijia['price'], 2);
// 						if ($price <= 0) {
// 							$price = 0;
// 						}
// 					} elseif ($mijia['mode'] == 1) {
// 						$price = round(($row['price'] - $mijia['price']) * $userrow['addprice'], 2);
// 						if ($price <= 0) {
// 							$price = 0;
// 						}
// 					} elseif ($mijia['mode'] == 2) {
// 						$price = $mijia['price'];
// 						if ($price <= 0) {
// 							$price = 0;
// 						}
// 					}
// 					if ($price < $price1) {
// 						$row['name'] = "密*" . $row['name'];
// 					} else {
// 						$row['name'] = "内*" . $row['name'];
// 					}
// 				} else {
// 					$row['name'] = "内*" . $row['name'];
// 				}
// 				if ($price >= $price1) { //密价价格大于会员，恢复会员价
// 					$price = $price1;
// 				}

	// 			}

	// 			$data[] = array(
// 				'sort' => $row['sort'],
// 				'cid' => $row['cid'],
// 				'name' => $row['name'],
// 				'noun' => $row['noun'],
// 				'price' => $price,
// 				'vipprice' => $row['vipprice'],
// 				'content' => $row['content'],
// 				'status' => $row['status'],
// 				'miaoshua' => $miaoshua
// 			);
// 		}
// 		foreach ($data as $key => $row) {
// 			$sort[$key] = $row['sort'];
// 			$cid[$key] = $row['cid'];
// 			$name[$key] = $row['name'];
// 			$noun[$key] = $row['noun'];
// 			$price[$key] = $row['price'];
// 			$vipprice[$key] = $row['vipprice'];
// 			$info[$key] = $row['info'];
// 			$content[$key] = $row['content'];
// 			$status[$key] = $row['status'];
// 			$miaoshua[$key] = $row['miaoshua'];
// 		}
// 		array_multisort($sort, SORT_ASC, $cid, SORT_DESC, $data);
// 		$data = array('code' => 1, 'data' => $data);
// 		exit(json_encode($data));

	// 		break;







	case 'getclass': // 全部获取商品和价格
		$fenlei = trim(strip_tags(daddslashes($_POST['id'])));

		if ($fenlei == "") {
			$a = $DB->query("select * from qingka_wangke_class where status=1 and fenlei<>0 order by sort desc");
		} else {
			$a = $DB->query("select * from qingka_wangke_class where status=1 and fenlei='$fenlei' order by sort desc");
		}

		while ($row = $DB->fetch($a)) {
			// 免费下单逻辑
			$validCids = explode(',', $conf['mfxd']);
			//  $validCids = array($conf['mfxd']);

			if (isset($conf['mfxdkg']) && intval($conf['mfxdkg']) === 1
				&& $userrow['freeadd'] > 0 && in_array($row['cid'], $validCids)) {
				$price = 0.0;
				$row['name'] = "免费*" . $row['name'];
			} else {
				if ($userrow['vip'] == 1) {
					// 会员价逻辑
					if ($row['vipyunsuan'] == "*") {
						$multiplier = max($userrow['addprice'], 0.15);
						$price = round($row['vipprice'] * $multiplier, 2);
					} elseif ($row['vipyunsuan'] == "+") {
						$price = round($row['vipprice'] + $userrow['addprice'], 2);
					} else {
						$multiplier = max($userrow['addprice'], 0.1);
						$price = round($row['vipprice'] * $multiplier, 2);
					}
				} else {
					// 非会员价逻辑
					if ($row['yunsuan'] == "*") {
						$price = round($row['price'] * max($userrow['addprice'], 0.15), 2);
					} elseif ($row['yunsuan'] == "+") {
						$price = round($row['price'] + $userrow['addprice'], 2);
					} else {
						$price = round($row['price'] * max($userrow['addprice'], 0.15), 2);
					}
				}
				$price1 = $price; // 密价逻辑前存储原价
				// 密价逻辑
				$mijia = $DB->get_row("select * from qingka_wangke_mijia where uid='{$userrow['uid']}' and cid='{$row['cid']}'");
				if ($mijia) {
					if ($mijia['mode'] == 0) {
						$price = round($price - $mijia['price'], 2);
					} elseif ($mijia['mode'] == 1) {
						$price = round(($row['vipprice'] - $mijia['price']) * $userrow['addprice'], 2);
					} elseif ($mijia['mode'] == 2) {
						$price = $mijia['price'];
					}
					$row['name'] = $price < $price1 ? "密*" . $row['name'] : "内*" . $row['name'];
					if ($price >= $price1) {
						$price = $price1;
					}
				} else {
					$row['name'] = $userrow['vip'] == 1 ? "内*" . $row['name'] : $row['name'];
				}
			}

			$data[] = array(
				'sort' => $row['sort'],
				'cid' => $row['cid'],
				'name' => $row['name'],
				'noun' => $row['noun'],
				'price' => $price,
				'vipprice' => $row['vipprice'],
				'content' => $row['content'],
				'status' => $row['status'],
				'fenlei' => $row['fenlei'],
				'miaoshua' => $miaoshua
			);
		}

		// 数据排序和返回
		foreach ($data as $key => $row) {
			$sort[$key] = $row['sort'];
			$cid[$key] = $row['cid'];
			$name[$key] = $row['name'];
			$noun[$key] = $row['noun'];
			$price[$key] = $row['price'];
			$vipprice[$key] = $row['vipprice'];
			$info[$key] = $row['info'];
			$content[$key] = $row['content'];
			$status[$key] = $row['status'];
			$miaoshua[$key] = $row['miaoshua'];
		}
		array_multisort($sort, SORT_ASC, $cid, SORT_DESC, $data);
		$data = array('code' => 1, 'data' => $data);
		exit(json_encode($data));

		break;



	case 'orderall':
		$page = trim(strip_tags(daddslashes($_GET['page'])));
		$pagesize = trim(strip_tags(daddslashes($_GET['limit'])));
		$cid = trim(strip_tags(daddslashes($_GET['cid'])));
		$status_text = trim(strip_tags(daddslashes($_GET['status_text'])));
		$status_text1 = trim(strip_tags(daddslashes($_GET['status_text1'])));
		$dock = trim(strip_tags(daddslashes($_GET['dock'])));
		$oid = trim(strip_tags(daddslashes($_GET['oid'])));
		$uid = trim(strip_tags(daddslashes($_GET['uid'])));
		$user = trim(strip_tags(daddslashes($_GET['user'])));
		$kcname = trim(strip_tags(daddslashes($_GET['kcname'])));
		$pageu = ($page - 1) * $pagesize;
		if ($userrow['uid'] = '') {
			$sql1 = "where uid='{$userrow['uid']}'";
		} else {
			$sql1 = "where 1=1";
		}
		if ($cid != '') {
			$sql2 = " and cid='{$cid}'";
		}
		if ($user != '') {
			$sql3 = " and user='{$user}'";
		}
		if ($oid != '') {
			$sql4 = " and oid='{$oid}'";
		}
		if ($uid != '') {
			$sql5 = " and uid='{$uid}'";
		}
		if ($status_text != '') {
			$sql6 = " and status='{$status_text}'";
		}
		if ($dock != '') {
			$sql7 = " and dockstatus='{$dock}'";
		}
		if ($status_text1 != '') {
			$sql6 = " and status='{$status_text1}'";
		}
		if ($kcname != '') {
			$sql8 = " and kcname LIKE '%{$kcname}%'";
		}
		$sql = $sql1 . $sql2 . $sql3 . $sql4 . $sql5 . $sql6 . $sql7 . $sql8;
		$a = $DB->query("select ptname,kcname,status,process,remarks,addtime,dockstatus,fees from qingka_wangke_order {$sql} and leixing='' order by oid desc limit $pageu,$pagesize ");
		$count1 = $DB->count("select count(oid) from qingka_wangke_order {$sql} and leixing='' ");
		while ($row = $DB->fetch($a)) {
			if ($row['name'] == '' || $row['name'] == 'undefined') {
				$row['name'] = 'null';
			}
			if ($userrow['uid'] != 1) {
				$row['dockstatus'] = '';
				$row['uid'] = '';
			}

			$data[] = $row;
		}
		$last_page = ceil($count1 / $pagesize);
		$data = array('code' => 0, 'data' => $data, "count" => $count1, );
		exit(json_encode($data));
		break;
	// 			case 'getclassfl':
// 	    $fenlei=trim(strip_tags(daddslashes($_POST['id'])));

	// 	  if ($fenlei == "") {
// 			$a = $DB->query("select * from qingka_wangke_class where status=1 and fenlei<>0 order by sort desc");
// 		} else {
// 			$a = $DB->query("select * from qingka_wangke_class where status=1 and fenlei='$fenlei' order by sort desc");
// 		}

	// 		while ($row = $DB->fetch($a)) {
// 			if ($userrow['vip'] == 0) {
// 				if ($row['yunsuan'] == "*") {
// 					$price = round($row['price'] * $userrow['addprice'], 2);
// 					$price1 = $price;
// 				} elseif ($row['yunsuan'] == "+") {
// 					$price = round($row['price'] + $userrow['addprice'], 2);
// 					$price1 = $price;
// 				} else {
// 					$price = round($row['price'] * $userrow['addprice'], 2);
// 					$price1 = $price;
// 				}
// 				//密价
// 				$mijia = $DB->get_row("select * from qingka_wangke_mijia where uid='{$userrow['uid']}' and cid='{$row['cid']}' ");
// 				if ($mijia) {
// 					if ($mijia['mode'] == 0) {
// 						$price = round($price - $mijia['price'], 2);
// 						if ($price <= 0) {
// 							$price = 0;
// 						}
// 					} elseif ($mijia['mode'] == 1) {
// 						$price = round(($row['price'] - $mijia['price']) * $userrow['addprice'], 2);
// 						if ($price <= 0) {
// 							$price = 0;
// 						}
// 					} elseif ($mijia['mode'] == 2) {
// 						$price = $mijia['price'];
// 						if ($price <= 0) {
// 							$price = 0;
// 						}
// 					}
// 					$row['name'] = "密*{$row['name']}";
// 				}
// 				if ($price >= $price1) { //密价价格大于原价，恢复原价
// 					$price = $price1;
// 				}
// 			} else {
// 				//会员价
// 				if ($row['yunsuan'] == "*") {
// 					$price = round($row['vipprice'] * $userrow['addprice'], 2);
// 					$price1 = $price;
// 				} elseif ($row['yunsuan'] == "+") {
// 					$price = round($row['vipprice'] + $userrow['addprice'], 2);
// 					$price1 = $price;
// 				} else {
// 					$price = round($row['vipprice'] * $userrow['addprice'], 2);
// 					$price1 = $price;
// 				}
// 				//密价
// 				$mijia = $DB->get_row("select * from qingka_wangke_mijia where uid='{$userrow['uid']}' and cid='{$row['cid']}' ");
// 				if ($mijia) {

	// 					if ($mijia['mode'] == 0) {
// 						$price = round($price - $mijia['price'], 2);
// 						if ($price <= 0) {
// 							$price = 0;
// 						}
// 					} elseif ($mijia['mode'] == 1) {
// 						$price = round(($row['price'] - $mijia['price']) * $userrow['addprice'], 2);
// 						if ($price <= 0) {
// 							$price = 0;
// 						}
// 					} elseif ($mijia['mode'] == 2) {
// 						$price = $mijia['price'];
// 						if ($price <= 0) {
// 							$price = 0;
// 						}
// 					}
// 					if ($price < $price1) {
// 						$row['name'] = "密*" . $row['name'];
// 					} else {
// 						$row['name'] = "内*" . $row['name'];
// 					}
// 				} else {
// 					$row['name'] = "内*" . $row['name'];
// 				}
// 				if ($price >= $price1) { //密价价格大于会员，恢复会员价
// 					$price = $price1;
// 				}

	// 			}

	// 			//全站一个价
// 			if ($row['suo'] != 0) {
// 				$price = $row['suo'];
// 			}
// 			$data[] = array(
// 				'sort' => $row['sort'],
// 				'cid' => $row['cid'],
// 				'name' => $row['name'],
// 				'noun' => $row['noun'],
// 				'price' => $price,
// 				'content' => $row['content'],
// 				'status' => $row['status'],
// 				'miaoshua' => $miaoshua
// 			);
// 		}
// 		foreach ($data as $key => $row) {
// 			$sort[$key] = $row['sort'];
// 			$cid[$key] = $row['cid'];
// 			$name[$key] = $row['name'];
// 			$noun[$key] = $row['noun'];
// 			$price[$key] = $row['price'];
// 			$info[$key] = $row['info'];
// 			$content[$key] = $row['content'];
// 			$status[$key] = $row['status'];
// 			$miaoshua[$key] = $row['miaoshua'];
// 		}
// 		array_multisort($sort, SORT_ASC, $cid, SORT_DESC, $data);
// 		$data = array('code' => 1, 'data' => $data);
// 		exit(json_encode($data));

	// 		break;


	case 'getclassfl': // 全部获取商品和价格
		$fenlei = trim(strip_tags(daddslashes($_POST['id'])));

		if ($fenlei == "") {
			$a = $DB->query("select * from qingka_wangke_class where status=1 and fenlei<>0 order by sort desc");
		} else {
			$a = $DB->query("select * from qingka_wangke_class where status=1 and fenlei='$fenlei' order by sort desc");
		}

		while ($row = $DB->fetch($a)) {
			// 免费下单逻辑
			$validCids = explode(',', $conf['mfxd']);

			// 检查用户是否有剩余的免费次数并且课程ID是否在有效课程ID列表中
			if (isset($conf['mfxdkg']) && intval($conf['mfxdkg']) === 1
				&& $userrow['freeadd'] > 0 && in_array($row['cid'], $validCids)) {
				$price = 0.0;
				$row['name'] = "免费*" . $row['name'];
			} else {
				if ($userrow['vip'] == 1) {
					// 会员价逻辑
					if ($row['vipyunsuan'] == "*") {
						$multiplier = max($userrow['addprice'], 0.15);
						$price = round($row['vipprice'] * $multiplier, 2);
					} elseif ($row['vipyunsuan'] == "+") {
						$price = round($row['vipprice'] + $userrow['addprice'], 2);
					} else {
						$multiplier = max($userrow['addprice'], 0.1);
						$price = round($row['vipprice'] * $multiplier, 2);
					}
				} else {
					// 非会员价逻辑
					if ($row['yunsuan'] == "*") {
						$price = round($row['price'] * max($userrow['addprice'], 0.15), 2);
					} elseif ($row['yunsuan'] == "+") {
						$price = round($row['price'] + $userrow['addprice'], 2);
					} else {
						$price = round($row['price'] * max($userrow['addprice'], 0.15), 2);
					}
				}
				$price1 = $price; // 密价逻辑前存储原价
				// 密价逻辑
				$mijia = $DB->get_row("select * from qingka_wangke_mijia where uid='{$userrow['uid']}' and cid='{$row['cid']}'");
				if ($mijia) {
					if ($mijia['mode'] == 0) {
						$price = round($price - $mijia['price'], 2);
					} elseif ($mijia['mode'] == 1) {
						$price = round(($row['vipprice'] - $mijia['price']) * $userrow['addprice'], 2);
					} elseif ($mijia['mode'] == 2) {
						$price = $mijia['price'];
					}
					$row['name'] = $price < $price1 ? "密*" . $row['name'] : "内*" . $row['name'];
					if ($price >= $price1) {
						$price = $price1;
					}
				} else {
					$row['name'] = $userrow['vip'] == 1 ? "内*" . $row['name'] : $row['name'];
				}
			}

			$data[] = array(
				'sort' => $row['sort'],
				'cid' => $row['cid'],
				'name' => $row['name'],
				'noun' => $row['noun'],
				'price' => $price,
				'vipprice' => $row['vipprice'],
				'content' => $row['content'],
				'status' => $row['status'],
				'fenlei' => $row['fenlei'],
				'miaoshua' => $miaoshua
			);
		}

		// 数据排序和返回
		foreach ($data as $key => $row) {
			$sort[$key] = $row['sort'];
			$cid[$key] = $row['cid'];
			$name[$key] = $row['name'];
			$noun[$key] = $row['noun'];
			$price[$key] = $row['price'];
			$vipprice[$key] = $row['vipprice'];
			$info[$key] = $row['info'];
			$content[$key] = $row['content'];
			$status[$key] = $row['status'];
			$miaoshua[$key] = $row['miaoshua'];
		}
		array_multisort($sort, SORT_ASC, $cid, SORT_DESC, $data);
		$data = array('code' => 1, 'data' => $data);
		exit(json_encode($data));

		break;



	case 'classlist':
		$page = trim(strip_tags(daddslashes($_POST['page'])));
		$fenlei = trim(strip_tags(daddslashes($_POST['fenlei'])));
		$shangjiastatus = trim(strip_tags(daddslashes($_POST['shangjiastatus'])));
		$keyword = trim(strip_tags(daddslashes($_POST['keyword'])));
		$pagesize = 100;
		$pageu = ($page - 1) * $pagesize; //当前界面		

		// 初始化查询条件
		$where = [];

		// 添加分类条件
		if (!empty($fenlei)) {
			$where[] = "fenlei = '$fenlei'";
		}

		// 添加状态条件
		if ($shangjiastatus !== '') {
			$where[] = "status = '$shangjiastatus'";
		}
		// 添加关键词条件
		if (!empty($keyword)) {
			$where[] = "name LIKE '%$keyword%'";
		}
		// 构建最终的查询条件
		$where = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

		// 计算总记录数和总页数
		$count1 = $DB->count("SELECT COUNT(*) FROM qingka_wangke_class $where");
		$last_page = ceil($count1 / $pagesize); //取最大页数

		if ($userrow['uid'] == '1') {
			$a = $DB->query("SELECT * FROM qingka_wangke_class $where LIMIT $pageu, $pagesize");
			while ($row = $DB->fetch($a)) {
				$c = $DB->get_row("SELECT * FROM qingka_wangke_huoyuan WHERE hid='{$row['queryplat']}'");
				$d = $DB->get_row("SELECT * FROM qingka_wangke_huoyuan WHERE hid='{$row['docking']}'");
				$row['cx_name'] = $c['name'];
				$row['add_name'] = $d['name'];
				if ($row['queryplat'] == '0') {
					$row['cx_name'] = '自营';
				}
				if ($row['docking'] == '0') {
					$row['add_name'] = '自营';
				}
				$f = $DB->get_row("select name from qingka_wangke_fenlei where id='{$row['fenlei']}' ");
				$row['fenlei_name'] = $f['name'];
				if ($row['fenlei'] == "wck") {
					$row['fenlei_name'] = "无查课";
				}
				$data[] = $row;
			}
			foreach ($data as $key => $rows) {
				$sort[$key] = $rows['sort'];
				$cid[$key] = $rows['cid'];
				$name[$key] = $rows['name'];
				$getnoun[$key] = $rows['getnoun'];
				$noun[$key] = $rows['noun'];
				$price[$key] = $rows['price'];
				$queryplat[$key] = $rows['queryplat'];
				$yunsuan[$key] = $rows['yunsuan'];
				$content[$key] = $rows['content'];
				$addtime[$key] = $rows['addtime'];
				$status[$key] = $rows['status'];
				$cx_names[$key] = $rows['cx_names'];
				$add_name[$key] = $rows['add_name'];
				$fenlei_name[$key] = $rows['fenlei_name'];
			}
			array_multisort($sort, SORT_ASC, $cid, SORT_DESC, $data);
			$data = array('code' => 1, 'data' => $data, "current_page" => (int) $page, "last_page" => $last_page);
			exit(json_encode($data));
		} else {
			exit('{"code":-2,"msg":"你在干啥"}');
		}
		break;







	// 	case 'classlist':
// 	    $page=trim(strip_tags(daddslashes($_POST['page'])));
// 		$pagesize=50;
// 	    $pageu = ($page - 1) * $pagesize;
// 		$count1=$DB->count("select count(*) from qingka_wangke_class");
// 		$last_page=ceil($count1/$pagesize);
// 		if($userrow['uid']=='1'){
// 			$a=$DB->query("select * from qingka_wangke_class limit $pageu,$pagesize ");
// 		    while($row=$DB->fetch($a)){
// 		    	$c=$DB->get_row("select * from qingka_wangke_huoyuan where hid='{$row['queryplat']}' ");
// 		    	$d=$DB->get_row("select * from qingka_wangke_huoyuan where hid='{$row['docking']}' ");
// 		   	   $row['cx_name']=$c['name'];
// 		   	   $row['add_name']=$d['name'];
// 		   	   if($row['queryplat']=='0'){
// 		   	   	  $row['cx_name']='自营';
// 		   	   }
// 		   	   if($row['docking']=='0'){
// 		   	   	  $row['add_name']='自营';
// 		   	   }
// 		   	   $f=$DB->get_row("select name from qingka_wangke_fenlei where id='{$row['fenlei']}' ");
// 		   	   $row['fenlei_name']=$f['name'];
// 		   	   if ($row['fenlei']=="wck") {
// 		   	       $row['fenlei_name']="无查课";
// 		   	   }
// 		   	   if ($row['fenlei']=="daka") {
// 		   	       $row['fenlei_name']="打卡";
// 		   	   }
// 		   	   $data[]=$row;
// 		    }
// 		    foreach ($data as $key => $rows)
//             {
//                 $sort[$key]  = $rows['sort'];
//                 $cid[$key] = $rows['cid'];
//                 $name[$key] = $rows['name'];
//                 $getnoun[$key] = $rows['getnoun'];
//                 $noun[$key] = $rows['noun'];
//                 $price[$key] = $rows['price'];
//                 $queryplat[$key] = $rows['queryplat'];
//                 $yunsuan[$key] = $rows['yunsuan'];
//                 $content[$key] = $rows['content'];
//                 $addtime[$key] = $rows['addtime'];
//                 $status[$key] = $rows['status'];
//                 $cx_names[$key] = $rows['cx_names'];
//                 $add_name[$key] = $rows['add_name'];
//                 $fenlei_name[$key] = $rows['fenlei_name'];
//             }
// 	       array_multisort($sort, SORT_ASC, $cid,SORT_DESC , $data);
// 		    $data=array('code'=>1,'data'=>$data,"current_page"=>(int)$page,"last_page"=>$last_page);
// 		    exit(json_encode($data));
// 	  }else{
// 	    	exit('{"code":-2,"msg":"你在干啥"}');
// 	  }
// 	break;



	case 'deleteclass':
		$cid = trim(strip_tags(daddslashes($_POST['cid'])));
		if ($userrow['uid'] == 1) {
			$DB->query("DELETE FROM qingka_wangke_class WHERE cid = '$cid'");
			exit('{"code":1,"msg":"操作成功"}');
		} else {
			exit('{"code":-2,"msg":"无权限"}');
		}
		break;
	case 'batchdeleteclass':
		if ($userrow['uid'] == 1) {
			if (isset($_POST['cids']) && is_array($_POST['cids'])) {
				$cids = array_map('intval', $_POST['cids']); // 确保 cids 是整数数组
				foreach ($cids as $cid) {
					$DB->query("DELETE FROM qingka_wangke_class WHERE cid = '$cid'");
				}
				exit('{"code":1,"msg":"操作成功"}');
			} else {
				exit('{"code":-1,"msg":"无效的参数"}');
			}
		} else {
			exit('{"code":-2,"msg":"无权限"}');
		}
		break;


	case 'batchupdatestatus':
		$status = trim(strip_tags(daddslashes($_POST['status'])));
		if ($userrow['uid'] == 1) {
			if (isset($_POST['cids']) && is_array($_POST['cids'])) {
				$cids = array_map('intval', $_POST['cids']); // 确保 cids 是整数数组
				foreach ($cids as $cid) {
					$DB->query("UPDATE qingka_wangke_class SET status = '$status' WHERE cid = '$cid'");
				}
				exit('{"code":1,"msg":"操作成功"}');
			} else {
				exit('{"code":-1,"msg":"无效的参数"}');
			}
		} else {
			exit('{"code":-2,"msg":"无权限"}');
		}
		break;
	case 'batchupdate':
		$status = trim(strip_tags(daddslashes($_POST['status'])));

		// 检查用户权限
		if ($userrow['uid'] == 1) {
			// 检查是否传入了课程ID数组
			if (isset($_POST['cids']) && is_array($_POST['cids'])) {
				$cids = array_map('intval', $_POST['cids']); // 确保 cids 是整数数组

				// 遍历数组，更新每个课程的状态
				foreach ($cids as $cid) {
					// 更新状态为1
					$DB->query("UPDATE qingka_wangke_class SET status = '1' WHERE cid = '$cid'");
				}
				exit('{"code":1,"msg":"操作成功"}');
			} else {
				exit('{"code":-1,"msg":"无效的参数"}');
			}
		} else {
			exit('{"code":-2,"msg":"无权限"}');
		}
		break;
	case 'batchupdatepricesort':
		if ($userrow['uid'] == 1) {
			if (isset($_POST['updates']) && is_array($_POST['updates'])) {
				$updates = $_POST['updates'];
				foreach ($updates as $update) {
					$cid = intval($update['cid']);

					$newPrice = floatval($update['newPrice']);
					$newSort = intval($update['newSort']);
					$DB->query("UPDATE qingka_wangke_class SET price = '$newPrice', sort = '$newSort' WHERE cid = '$cid'");
				}
				exit('{"code":1,"msg":"操作成功"}');
			} else {
				exit('{"code":-1,"msg":"无效的参数"}');
			}
		} else {
			exit('{"code":-2,"msg":"无权限"}');
		}
		break;
	// 	case 'searchPlatforms':
//     $keyword = trim(strip_tags(daddslashes($_POST['keyword'])));
//     if ($userrow['uid'] == '1') {
//         $sql = "SELECT * FROM qingka_wangke_class WHERE name LIKE '%$keyword%' OR cid LIKE '%$keyword%'";
//         $a = $DB->query($sql);
//         while ($row = $DB->fetch($a)) {
//             $c = $DB->get_row("SELECT * FROM qingka_wangke_huoyuan WHERE hid='{$row['queryplat']}' ");
//             $d = $DB->get_row("SELECT * FROM qingka_wangke_huoyuan WHERE hid='{$row['docking']}' ");
//             $row['cx_name'] = $c['name'];
//             $row['add_name'] = $d['name'];
//             if ($row['queryplat'] == '0') {
//                 $row['cx_name'] = '自营';
//             }
//             if ($row['docking'] == '0') {
//                 $row['add_name'] = '自营';
//             }
//             $f = $DB->get_row("SELECT name FROM qingka_wangke_fenlei WHERE id='{$row['fenlei']}' ");
//             $row['fenlei_name'] = $f['name'];
//             if ($row['fenlei'] == "wck") {
//                 $row['fenlei_name'] = "无查课";
//             }
//             if ($row['fenlei'] == "daka") {
//                 $row['fenlei_name'] = "打卡";
//             }
//             $data[] = $row;
//         }
//         $data = array('code' => 1, 'data' => $data);
//         exit(json_encode($data));
//     } else {
//         exit('{"code":-2,"msg":"你在干啥"}');
//     }
//     break;

	// 	case 'upclass':
// 	    parse_str(daddslashes($_POST['data']),$row);
// 	     if($userrow['uid']==1){
//           if($row['action']=='add'){
//           	$DB->query("insert into qingka_wangke_class (sort,name,getnoun,noun,price,vipprice,ckkf,queryplat,docking,content,addtime,status,fenlei,kcid) values ('{$row['sort']}','{$row['name']}','{$row['getnoun']}','{$row['noun']}','{$row['price']}','{$row['vipprice']}','{$row['ckkf']}','{$row['queryplat']}','{$row['docking']}','{$row['content']}','{$date}','{$row['status']}','{$row['fenlei']}','{$row['kcid']}')");
//     	    exit('{"code":1,"msg":"操作成功"}');
//           }else{		   
// 	        $DB->query("update `qingka_wangke_class` set `sort`='{$row['sort']}',`name`='{$row['name']}',`getnoun`='{$row['getnoun']}',`noun`='{$row['noun']}',`price`='{$row['price']}',`vipprice`='{$row['vipprice']}',`ckkf`='{$row['ckkf']}',`queryplat`='{$row['queryplat']}',`docking`='{$row['docking']}',`yunsuan`='{$row['yunsuan']}',`content`='{$row['content']}',`status`='{$row['status']}',`fenlei`='{$row['fenlei']}',`kcid`='{$row['kcid']}' where cid='{$row['cid']}' ");	        
// 	        exit('{"code":1,"msg":"操作成功"}');
// 	      }
// 	    }else{
// 		    exit('{"code":-2,"msg":"无权限"}');
// 		}
// 	break;



	case 'upclass':
		parse_str(daddslashes($_POST['data']), $row);//将字符串解析成多个变量
		if ($userrow['uid'] == 1) {
			if ($row['action'] == 'add') {
				$DB->query("insert into qingka_wangke_class (sort,name,getnoun,noun,price,vipprice,ckkf,queryplat,docking,content,addtime,status,fenlei,kcid) values ('{$row['sort']}','{$row['name']}','{$row['getnoun']}','{$row['noun']}','{$row['price']}','{$row['vipprice']}','{$row['ckkf']}','{$row['queryplat']}','{$row['docking']}','{$row['content']}','{$date}','{$row['status']}','{$row['fenlei']}','{$row['kcid']}')");
				exit('{"code":1,"msg":"操作成功"}');
			} else {
				$DB->query("update `qingka_wangke_class` set `sort`='{$row['sort']}',`name`='{$row['name']}',`getnoun`='{$row['getnoun']}',`noun`='{$row['noun']}',`price`='{$row['price']}',`vipprice`='{$row['vipprice']}',`ckkf`='{$row['ckkf']}',`queryplat`='{$row['queryplat']}',`docking`='{$row['docking']}',`yunsuan`='{$row['yunsuan']}',`content`='{$row['content']}',`status`='{$row['status']}',`fenlei`='{$row['fenlei']}',`kcid`='{$row['kcid']}' where cid='{$row['cid']}' ");
				exit('{"code":1,"msg":"操作成功"}');
			}
		} else {
			exit('{"code":-2,"msg":"无权限"}');
		}
		break;




case 'huoyuanlist':
 		$page = daddslashes($_POST['page']);
		$pagesize = 50;
		$pageu = ($page - 1) * $pagesize;
 		$count1 = $DB->count("select count(*) from qingka_wangke_huoyuan");
 		$last_page = ceil($count1 / $pagesize);
 		if ($userrow['uid'] == '1') {
 			$a = $DB->query("select * from qingka_wangke_huoyuan limit $pageu,$pagesize ");
 			while ($row = $DB->fetch($a)) {
				$data[] = $row;
 			}
			$data = array('code' => 1, 'data' => $data, "current_page" => (int) $page, "last_page" => $last_page);
			exit(json_encode($data));
		} else {
			exit('{"code":-2,"msg":"你在干啥"}');
 		}
 		break;
	case 'uphuoyuan':
		parse_str(daddslashes($_POST['data']), $row);

		if ($userrow['uid'] == 1) {
			if ($row['action'] == 'add') {
				$DB->query("insert into qingka_wangke_huoyuan (pt,name,url,user,pass,token,ip,cookie,addtime) values ('{$row['pt']}','{$row['name']}','{$row['url']}','{$row['user']}','{$row['pass']}','{$row['token']}','{$row['ip']}','{$row['cookie']}',NOW())");
				exit('{"code":1,"msg":"操作成功"}');
			} else {
				$DB->query("update `qingka_wangke_huoyuan` set `pt`='{$row['pt']}',`name`='{$row['name']}',`url`='{$row['url']}',`user`='{$row['user']}',`pass`='{$row['pass']}',`token`='{$row['token']}',`ip`='{$row['ip']}',`cookie`='{$row['cookie']}',`endtime`=NOW() where hid='{$row['hid']}' ");
				exit('{"code":1,"msg":"操作成功"}');
			}
		} else {
			exit('{"code":-2,"msg":"无权限"}');
		}
		break;
	case 'tk':
		$sex = daddslashes($_POST['sex']);
		if ($userrow['uid'] == 1) {
			for ($i = 0; $i < count($sex); $i++) {
				$oid = $sex[$i];
				$order = $DB->get_row("select * from qingka_wangke_order where oid='{$oid}' ");
				$user = $DB->get_row("select * from qingka_wangke_user where uid='{$order['uid']}' ");
				$DB->query("update qingka_wangke_user set money=money+'{$order['fees']}' where uid='{$user['uid']}'");
				$DB->query("update qingka_wangke_order set status='已退款',dockstatus='4' where oid='{$oid}'");
				wlog($user['uid'], "订单退款", "订单ID：{$order['oid']} 订单信息：{$order['user']} {$order['pass']} {$order['kcname']}被管理员退款", "+{$order['fees']}");
			}
			exit('{"code":1,"msg":"选择的订单已批量退款！可在日志中查看！"}');
		} else {
			exit('{"code":-1,"msg":"无权限"}');
		}
		break;
	case 'delorder'://删除订单
		$sex = daddslashes($_POST['sex']);

		if (empty($sex)) {
			jsonReturn(-1, "请先选择订单！");
		}
		$deleted_orders = [];
		for ($x = 0; $x < count($sex); $x++) {
			$oid = $sex[$x];
			$order = $DB->get_row("select * from qingka_wangke_order where oid='$oid' ");
			if ($order) {
				$a = $DB->query("delete from qingka_wangke_order where oid='$oid' ");
				$deleted_orders[] = $oid; // 将已删除的订单ID添加到数组中
			}
		}
		if (count($deleted_orders) > 0) {
			$deleted_orders_str = implode(',', $deleted_orders); // 将已删除的订单ID数组转换为逗号分隔的字符串
			wlog($userrow['uid'], "删除订单", "订单 {$deleted_orders_str} 已删除", 0);
			exit('{"code":1,"msg":"删除成功！"}');
		} else {
			exit('{"code":0,"msg":"没有找到要删除的订单！"}');
		}
		break;

	case 'userlist':
		$page = trim(strip_tags(daddslashes($_GET['page'])));
		$pagesize = trim(strip_tags(daddslashes($_GET['limit'])));
		$qq = trim(strip_tags(daddslashes($_GET['qq'])));
		$type = trim(strip_tags(daddslashes($_GET['type'])));
		$pageu = ($page - 1) * $pagesize;
		// if (!preg_match('/^\d+$/', $qq) && $qq !== '') {
//          exit(json_encode(['code' => -1, 'msg' => '还想看，看鸡毛？']));
//          }
		if ($userrow['uid'] == '1') {
			if ($qq != "" and $type == 1) {
				$sql = "where uid=" . $qq;
			} elseif ($qq != "" and $type == 2) {
				$sql = "where user='" . $qq . "'";
			} elseif ($qq != "" and $type == 3) {
				$sql = "where yqm='" . $qq . "'";
			} elseif ($qq != "" and $type == 4) {
				$sql = "where name='" . $qq . "'";
			} elseif ($qq != "" and $type == 5) {
				$sql = "where addprice='" . $qq . "'";
			} elseif ($qq != "" and $type == 6) {
				$sql = "where money='" . $qq . "'";
			} elseif ($qq != "" and $type == 7) {
				$sql = "where endtime>'" . $qq . "'";
			}
		} else {
			if ($qq != "" and $type == 1) {
				if ($qq = "1 or 1=1") {
					$sql = "where uuid='{$userrow['uid']}' and uid=" . "1";
					//  jsonReturn(-1,"你查你妈个逼呢？");
				} else {
					$sql = "where uuid='{$userrow['uid']}' and uid=" . $qq;
				}

			} else


				if ($qq != "" and $type == 1) {
					$sql = "where uuid='{$userrow['uid']}' and uid=" . $qq;
				} elseif ($qq != "" and $type == 2) {
					$sql = "where uuid='{$userrow['uid']}' and user='" . $qq . "'";
				} elseif ($qq != "" and $type == 3) {
					$sql = "where uuid='{$userrow['uid']}' and yqm='" . $qq . "'";
				} elseif ($qq != "" and $type == 4) {
					$sql = "where uuid='{$userrow['uid']}' and name='" . $qq . "'";
				} elseif ($qq != "" and $type == 5) {
					$sql = "where uuid='{$userrow['uid']}' and addprice='" . $qq . "'";
				} elseif ($qq != "" and $type == 6) {
					$sql = "where uuid='{$userrow['uid']}' and money='" . $qq . "'";
				} elseif ($qq != "" and $type == 7) {
					$sql = "where endtime>'" . $qq . "' and uuid='{$userrow['uid']}'";
				} else {
					$sql = "where uuid='{$userrow['uid']}'";
				}
		}

		$a = $DB->query("select * from qingka_wangke_user {$sql} order by uid desc limit $pageu,$pagesize ");
		$count1 = $DB->count("select count(*) from qingka_wangke_user {$sql}");
		while ($row = $DB->fetch($a)) {
			$zcz = 0;
			$row['pass'] = "这还能让你知道？";
			if ($row['key'] != '0') {
				$row['key'] = '1';
			}

			$dd = $DB->count("select count(oid) from qingka_wangke_order where uid='{$row['uid']}' ");
			$row['dd'] = $dd;
			$data[] = $row;
		}
		$data = array('code' => 0, 'data' => $data, "count" => $count1);
		exit(json_encode($data));
		break;
	case 'adddjlist':
		$a = $DB->query("select * from qingka_wangke_dengji where status=1 and rate>='{$userrow['addprice']}' order by sort desc");
		while ($row = $DB->fetch($a)) {
			$data[] = array(
				'sort' => $row['sort'],
				'name' => $row['name'],
				'rate' => $row['rate'],
			);
		}
		foreach ($data as $key => $row) {
			$sort[$key] = $row['sort'];
			$name[$key] = $row['name'];
			$rate[$key] = $row['rate'];
		}
		array_multisort($sort, SORT_ASC, $rate, SORT_ASC, $data);
		$data = array('code' => 1, 'data' => $data);
		exit(json_encode($data));
		break;
	case 'user_notice':
		parse_str(daddslashes($_POST['data']), $row);
		$notice = $row['desc'];
		if ($DB->query("update qingka_wangke_user set notice='{$notice}' where uid='{$userrow['uid']}' ")) {
			wlog($userrow['uid'], "设置公告", "设置公告: {$notice}", 0);
			jsonReturn(1, "设置成功，请关闭弹窗即可");
		} else {
			jsonReturn(-1, "未知异常");
		}
		break;
	// 	case 'userjk':
// 	    $uid=trim(strip_tags(daddslashes($_POST['uid'])));
// 	    $money=trim(strip_tags(daddslashes($_POST['money'])));
// 	    if(!preg_match('/^[0-9.]+$/', $money))exit('{"code":-1,"msg":"充值金额不合法"}');
// 	    if($money<10 && $userrow['uid']!=1){
// 	    	exit('{"code":-1,"msg":"最低充值10元"}');
// 	    }
//         $row=$DB->get_row("select * from qingka_wangke_user where uid='$uid' limit 1");
// 	    if($row['uuid']!=$userrow['uid'] && $userrow['uid']!=1){
// 	    	exit('{"code":-1,"msg":"该用户你的不是你的下级,无法充值"}');
// 	    }
// 	    if($userrow['uid']==$uid){
// 	    	exit('{"code":-1,"msg":"自己不能给自己充值哦"}');
// 	    }

	// 	    $kochu=round($money*($userrow['addprice']/$row['addprice']),2);

	// 	    if($userrow['money']<$kochu){
// 	    	exit('{"code":-1,"msg":"您当前余额不足,无法充值"}');
// 	    }
// 	    $wdkf=round($userrow['money']-$kochu,2);
// 	    $xjkf=round($row['money']+$money,2);    
// 	    $DB->query("update qingka_wangke_user set money='$wdkf' where uid='{$userrow['uid']}' ");
// 	    $DB->query("update qingka_wangke_user set money='$xjkf',zcz=zcz+'$money' where uid='$uid' ");
// 	    wlog($userrow['uid'],"代理充值","成功给账号为[{$row['user']}]的靓仔充值{$money}元,扣除{$kochu}元",-$kochu);
// 	    wlog($row['uid'],"上级充值","{$userrow['name']}成功给你充值{$money}元",+$money);
// 	    exit('{"code":1,"msg":"充值'.$money.'元成功,实际扣费'.$kochu.'元"}');

	// 	break;


	case 'userjk': // 用户充值
		$uid = trim(strip_tags(daddslashes($_POST['uid'])));
		$money = trim(strip_tags(daddslashes($_POST['money'])));
		if (!preg_match('/^[0-9.]+$/', $money))
			exit('{"code":-1,"msg":"充值金额不合法"}');
		if ($money < 10 && $userrow['uid'] != 1) {
			exit('{"code":-1,"msg":"最低充值10元"}');
		}
		// 修改这行，以包括pushPlusToken字段
		$row = $DB->get_row("SELECT * FROM qingka_wangke_user WHERE uid='$uid' LIMIT 1");
		if ($row['uuid'] != $userrow['uid'] && $userrow['uid'] != 1) {
			exit('{"code":-1,"msg":"该用户不是你的下级,无法充值"}');
		}
		if ($userrow['uid'] == $uid && $userrow['uid'] != 1) {
			exit('{"code":-1,"msg":"自己不能给自己充值哦"}');
		}

		$kochu = round($money * ($userrow['addprice'] / $row['addprice']), 2); // 充值计算

		if ($userrow['money'] < $kochu) {
			exit('{"code":-1,"msg":"您当前余额不足,无法充值"}');
		}
		$wdkf = round($userrow['money'] - $kochu, 2);
		$xjkf = round($row['money'] + $money, 2);
		$DB->query("UPDATE qingka_wangke_user SET money='$wdkf' WHERE uid='{$userrow['uid']}'"); // 我的扣费
		$DB->query("UPDATE qingka_wangke_user SET money='$xjkf',zcz=zcz+'$money' WHERE uid='$uid'"); // 下级增加
		wlog($userrow['uid'], "代理充值", "成功给账号为[{$row['user']}]的靓仔充值{$money}元,扣除{$kochu}元", -$kochu);
		wlog($row['uid'], "上级充值", "{$userrow['name']}成功给你充值{$money}元", +$money);

		exit('{"code":1,"msg":"充值' . $money . '元成功,实际扣费' . $kochu . '元"}');
		break;














	// 	case 'userkf':
// 		$uid = trim(strip_tags(daddslashes($_POST['uid'])));
// 		$money = trim(strip_tags(daddslashes($_POST['money'])));
// 		if ($userrow['uid'] != '1') {
// 			jsonReturn(-1, "滚你妈");
// 		}
// 		if (!preg_match('/^[0-9.]+$/', $money))
// 			exit('{"code":-1,"msg":"扣款金额不合法"}');
// 		$row = $DB->get_row("select * from qingka_wangke_user where uid='$uid' limit 1");
// 		if ($row['money'] < $money) {
// 			exit('{"code":-1,"msg":"下级余额不足"}');
// 		}
// 		$xjkf = round($row['money'] - $money, 2);
// 		$DB->query("update qingka_wangke_user set money='$xjkf' where uid='$uid' ");
// 		wlog($userrow['uid'], "代理扣费", "成功给账号为[{$row['user']}]的靓仔扣除{$money}元", 0);
// 		wlog($row['uid'], "余额扣除", "管理员扣除您的余额{$money}元", -$money);
// 		$pushPlusToken = $row['pushPlusToken'];
// 		$title = '管理员扣款';
// 		$content = "管理员扣除您的余额了{$money}元，账号为[{$row['user']}]";
// 		sendPushNotification($pushPlusToken, $title, $content);
// 		exit('{"code":1,"msg":"扣除【' . $uid . '】余额' . $money . '元成功"}');

	// 		break;

	case 'userkf': //用户扣款
		$uid = trim(strip_tags(daddslashes($_POST['uid'])));
		$money = trim(strip_tags(daddslashes($_POST['money'])));
		if (!preg_match('/^[0-9.]+$/', $money))
			exit('{"code":-1,"msg":"扣款金额不合法"}');
		$row = $DB->get_row("select * from qingka_wangke_user where uid='$uid' limit 1");
		if ($row['uuid'] != $userrow['uid'] && $userrow['uid'] != 1) {
			exit('{"code":-1,"msg":"该用户不是你的下级,无法扣款"}');
		}
		// 限制只有uid=1的用户可以使用此功能
		if ($userrow['uid'] != 1) {
			exit('{"code":-1,"msg":"只有特定用户可以进行扣款操作"}');
		}

		if ($row['money'] < $money) {
			exit('{"code":-1,"msg":"被扣款用户余额不足，无法扣款"}');
		}

		// 根据充值计算公式计算返还金额
		$kochu = round($money * ($userrow['addprice'] / $row['addprice']), 2); // 扣款计算
		$wdkf = round($userrow['money'] + $kochu, 2); // 执行扣款用户新余额（返还金额）

		$xjkf = round($row['money'] - $money, 2); // 被扣款用户新余额
		$DB->query("update qingka_wangke_user set money='$wdkf' where uid='{$userrow['uid']}' "); // 更新执行扣款用户余额（返还金额）
		$DB->query("update qingka_wangke_user set money='$xjkf',zcz=zcz-'$money' where uid='$uid' "); // 更新被扣款用户余额

		wlog($userrow['uid'], "代理扣款", "成功给账号为[{$row['user']}]的用户扣款{$money}元,您获得返还{$kochu}元", +$kochu);
		wlog($row['uid'], "上级扣款", "{$userrow['name']}成功给你扣款{$money}元", -$money);
		exit('{"code":1,"msg":"扣款' . $money . '元成功,您获得返还' . $kochu . '元"}');
		break;
	case 'mrqd'://签到赠送
		$type = trim(strip_tags(daddslashes($_GET['type'])));
		$uid = trim(strip_tags(daddslashes($_GET['uid'])));
		if ($type == 1) {
			$userSignQuery = $DB->query("SELECT `last_sign_in_date`, `uid`, `freeadd`, `zcz`, `addprice`, `money` FROM qingka_wangke_user WHERE uid='{$userrow['uid']}' LIMIT 1");
			if ($userSignQuery) {
				$userSignRow = $userSignQuery->fetch_assoc();
				$lastSignInDate = $userSignRow['last_sign_in_date'];
				$currentDate = date('Y-m-d');
				if ($lastSignInDate == $currentDate) {
					exit('{"code":-1,"msg":"今日已签到，叼毛！"}');
				} else {
					$userLevel = $userSignRow['addprice'];

					if ($userLevel > 0.2) {
						// 低于0.2等级，赠送余额
						$randomMoney = (mt_rand(1, 100) <= 80) ? mt_rand(1, 2) / 100 : mt_rand(3, 5) / 100;
						$updateQuery = $DB->query("UPDATE qingka_wangke_user SET `money`=`money` + {$randomMoney}, `zcz`=`zcz` + {$randomMoney}, `last_sign_in_date`='{$currentDate}' WHERE uid='{$userrow['uid']}'");

						if ($updateQuery) {
							wlog($userrow['uid'], "签到成功", "恭喜你签到成功，余额增加{$randomMoney}元", 0);
							exit('{"code":1,"msg":"恭喜你签到成功，今日余额增加' . $randomMoney . '元"}');
						} else {
							jsonReturn(-2, "签到过程中出现错误");
						}
					} else {
						// 0.2及以上等级，赠送免费次数
						if (!isset($conf['mfxdkg']) || intval($conf['mfxdkg']) !== 1) {
							jsonReturn(-1, "签到免费下单功能未开启");
						}
						$randomFreeAdds = (mt_rand(1, 100) <= 70) ? 1 : mt_rand(2, 3);

						// 先重置freeadd次数为0
						$resetFreeAdds = $DB->query("UPDATE qingka_wangke_user SET `freeadd`=0 WHERE uid='{$userrow['uid']}'");

						// 检查重置是否成功
						if (!$resetFreeAdds) {
							jsonReturn(-2, "重置免费次数失败");
						}

						$updateQuery = $DB->query("UPDATE qingka_wangke_user SET `freeadd`=`freeadd` + {$randomFreeAdds}, `last_sign_in_date`='{$currentDate}' WHERE uid='{$userrow['uid']}'");
						if ($updateQuery) {
							wlog($userrow['uid'], "签到成功", "恭喜你签到成功，免费下单次数增加{$randomFreeAdds}单", 0);
							exit('{"code":1,"msg":"恭喜你签到成功，今日免费下单次数为' . $randomFreeAdds . '单"}');
						} else {
							jsonReturn(-2, "签到过程中出现错误");
						}
					}
				}
			} else {
				jsonReturn(-2, "未找到用户信息");
			}
		}
		jsonReturn(-2, "未知异常");
		break;


	// case 'mrqd':
//     $type = trim(strip_tags(daddslashes($_GET['type'])));
//     $uid = trim(strip_tags(daddslashes($_GET['uid'])));
//     if ($type == 1) {
//         $userSignQuery = $DB->query("SELECT `last_sign_in_date`, `uid`, `freeadd`, `zcz` FROM qingka_wangke_user WHERE uid='{$userrow['uid']}' LIMIT 1");
//         if ($userSignQuery) {
//             $userSignRow = $userSignQuery->fetch_assoc();
//             $lastSignInDate = $userSignRow['last_sign_in_date'];
//             $currentDate = date('Y-m-d');
//             if ($lastSignInDate == $currentDate) {
//                 exit('{"code":-1,"msg":"今日已签到，叼毛！"}');
//             } else {
//                 // 先重置freeadd次数为0
//                 $resetFreeAdds = $DB->query("UPDATE qingka_wangke_user SET `freeadd`=0 WHERE uid='{$userrow['uid']}'");

	//                 // 检查重置是否成功
//                 if (!$resetFreeAdds) {
//                     jsonReturn(-2, "重置免费次数失败");
//                 }

	//                 $totalRecharge = $userSignRow['zcz'];
//                 $randomFreeAdds = 1; // 默认值

	//                 if ($totalRecharge < 100) {
//                     $randomFreeAdds = 1;
//                 } elseif ($totalRecharge >= 100 && $totalRecharge < 300) {
//                     $randomFreeAdds = mt_rand(1, 2);
//                 } elseif ($totalRecharge >= 300 && $totalRecharge < 500) {
//                     $randomFreeAdds = mt_rand(1, 2);
//                 } elseif ($totalRecharge >= 500 && $totalRecharge < 700) {
//                     $randomFreeAdds = mt_rand(1, 3);
//                 } elseif ($totalRecharge >= 1000) {
//                     if (mt_rand(1, 100) <= 90) {
//                         $randomFreeAdds = mt_rand(1, 3);
//                     } else {
//                         $randomFreeAdds = mt_rand(4, 5);
//                     }
//                 }

	//                 $updateQuery = $DB->query("UPDATE qingka_wangke_user SET `freeadd`=`freeadd` + {$randomFreeAdds}, `last_sign_in_date`='{$currentDate}' WHERE uid='{$userrow['uid']}'");
//                 if ($updateQuery) {
//                     wlog($userrow['uid'], "签到成功", "恭喜你签到成功，免费下单次数增加{$randomFreeAdds}单", 0);
//                     exit('{"code":1,"msg":"恭喜你签到成功，今日免费下单次数为'.$randomFreeAdds.'单<br>累计充值越多，每日签到获取免费次数越多"}');
//                 } else {
//                     jsonReturn(-2, "签到过程中出现错误");
//                 }
//             }
//         } else {
//             jsonReturn(-2, "未找到用户信息");
//         }
//     }
//     jsonReturn(-2, "未知异常");
//     break;






	case 'usergj':
		parse_str(daddslashes($_POST['data']), $row);
		$uid = trim(strip_tags(daddslashes(trim($row['uid']))));
		$addpriceid = trim(strip_tags(daddslashes($row['addpriceid'])));
		$type = trim(strip_tags(daddslashes($_POST['type'])));
		$a = $DB->get_row("select * from qingka_wangke_dengji where id='{$row['addpriceid']}'");
		$addprice = $a['rate'];
		if (!preg_match('/^[0-9.]+$/', $addprice))
			exit('{"code":-1,"msg":"费率不合法"}');
		$row = $DB->get_row("select * from qingka_wangke_user where uid='$uid' limit 1");
		if ($row['uuid'] != $userrow['uid'] && $userrow['uid'] != 1) {
			exit('{"code":-1,"msg":"该用户你的不是你的下级,无法修改价格"}');
		}
		if ($userrow['uid'] == $uid && $userrow['uid'] != 1) {
			exit('{"code":-1,"msg":"自己不能给自己改价哦"}');
		}
		if ($userrow['addprice'] > $addprice && $userrow['uid'] != 1) {
			exit('{"code":-1,"msg":"你下级的费率不能低于你哦"}');
		}
		if ($addprice * 100 % 5 != 0) {
			jsonReturn(-1, "请输入单价为0.05的倍数");
		}

		if ($addprice == $row['addprice']) {
			jsonReturn(-1, "该商户已经是{$addprice}费率了，你还修改啥");
		}
		if ($addprice > $row['addprice'] && $userrow['uid'] != 1) {
			jsonReturn(-1, "下调费率，请联系管理员");
		}
		if ($addprice < '0.2' && $userrow['uid'] != 1) {
			exit('{"code":-1,"msg":"你在干什么？"}');
		}

		$cz = 0;
		if ($a['gjkf'] == 1) {
			$cz = $a['money'];
		}
		$kochu = round($cz * ($userrow['addprice'] / $addprice), 2);//充值计算后要扣的钱

		$money = round($row['money'] / $row['addprice'] * $addprice, 2) + $cz;//改价之后的余额+充值的金额
		$zcz = round($row['zcz'] / $row['addprice'] * $addprice, 2) + $cz;//改价之后的总充值+充值的金额

		$kochu2 = $kochu + $conf['user_gjmoney'];//上级总扣费
		if ($type != 1) {
			jsonReturn(1, "改价手续费{$conf['user_gjmoney']}元，并自动给下级[UID:{$uid}]充值{$cz}元，总扣除{$kochu2}余额，调整价格及充值后下级总余额为{$money}");
		}
		if ($userrow['money'] < $kochu2) {
			jsonReturn(-1, "余额不足,改价需扣{$conf['user_gjmoney']}元手续费,及余额{$kochu}元");
		} else {
			$DB->query("update qingka_wangke_user set money=money-{$kochu2} where uid='{$userrow['uid']}' ");
			$DB->query("update qingka_wangke_user set money='$money',addprice='$addprice',zcz='$zcz' where uid='$uid'");
			wlog($userrow['uid'], "修改费率", "改价手续费{$conf['user_gjmoney']}元，并自动给下级[UID:{$uid}]充值{$cz}元，总扣除{$kochu2}余额", "-{$kochu2}");
			wlog($uid, "修改费率", "{$userrow['name']}修改你的费率为：{$addprice},系统根据比例自动调整价格", $money);
			if ($cz != 0) {
				wlog($uid, "上级充值", "{$userrow['name']}成功给你充值{$cz}元", +$cz);
			}
			exit('{"code":1,"msg":"改价成功"}');
		}
		break;
	case 'user_czmm':
		$uid = trim(strip_tags(daddslashes($_POST['uid'])));
		if ($userrow['uid'] == $uid) {
			jsonReturn(-1, "自己不能给自己重置哦");
		}
		$row = $DB->get_row("select * from qingka_wangke_user where uid='$uid' limit 1");
		if ($row['uuid'] != $userrow['uid'] && $userrow['uid'] != 1) {
			exit('{"code":-1,"msg":"该用户你的不是你的下级,无法修改密码"}');
		} else {
			$DB->query("update qingka_wangke_user set pass='23456789' where uid='{$uid}' ");
			wlog($row['uid'], "重置密码", "成功重置UID为{$uid}的密码为23456789", 0);
			jsonReturn(1, "成功重置密码为23456789");
		}
		break;
	case 'user_ban':
		$uid = trim(strip_tags(daddslashes($_POST['uid'])));
		$active = trim(strip_tags(daddslashes($_POST['active'])));
		if ($userrow['uid'] != 1) {
			jsonReturn(-1, "无权限");
		}
		if ($active == 1) {
			$a = 0;
			$b = "封禁商户";
		} else {
			$a = 1;
			$b = "解封商户";
		}
		$DB->query("update qingka_wangke_user set active='$a' where uid='{$uid}' ");
		wlog($userrow['uid'], $b, "{$b}[UID {$uid}]成功", 0);
		jsonReturn(1, "操作成功");

		break;
	case 'rechargebyuid':
		$uid = trim(strip_tags(daddslashes($_POST['uid'])));
		$money = trim(strip_tags(daddslashes($_POST['money'])));
		// 是否开启该充值方式
		$recharge_kg = 1; // 1：开启，0：关闭
		if ($recharge_kg !== 1)
			exit('{"code":-1,"msg":"该充值方式暂未开启"}');
		// 判断金额合法性
		if (!preg_match('/^[0-9.]+$/', $money))
			exit('{"code":-1,"msg":"充值金额不合法"}');
		// 被充值用户对象
		$row = $DB->get_row("select * from qingka_wangke_user where uid='$uid' limit 1");
		// 判断用户是否存在
		if (!$row)
			exit('{"code":-1,"msg":"该用户不存在"}');
		// 充值对象不能为自己
		if ($userrow['uid'] == $uid) {
			exit('{"code":-1,"msg":"自己不能给自己充值哦"}');
		}
		// 我需要扣除费用=充值金额*(我的费率/他的费率)
		$kochu = round($money * ($userrow['addprice'] / $row['addprice']), 2);
		// 判断我的余额是否充足
		if ($userrow['money'] < $kochu) {
			exit('{"code":-1,"msg":"您当前余额不足,无法充值"}');
		}
		// 我的最终余额
		$mymoney = round($userrow['money'] - $kochu, 2);
		// 他的最终余额
		$hismoney = round($row['money'] + $money, 2);
		// 写入数据库
		$DB->query("update qingka_wangke_user set money='$mymoney' where uid='{$userrow['uid']}' ");
		$DB->query("update qingka_wangke_user set money='$hismoney', zcz=zcz+'$money' where uid='$uid' ");
		// 写入日志
		wlog($userrow['uid'], "用户转账", "成功给UID为[{$uid}]的靓仔转账{$money}元,扣除{$kochu}元", -$kochu);
		wlog($row['uid'], "用户转账", "{$userrow['name']}成功给你转账{$money}元", +$money);

		exit('{"code":1,"msg":"成功给TA转账' . $money . '元,实际扣费' . $kochu . '元"}');
		break;
	case 'user_vip':
		$uid = trim(strip_tags(daddslashes($_POST['uid'])));
		$active = trim(strip_tags(daddslashes($_POST['active'])));
		if ($userrow['uid'] != 1) {
			jsonReturn(-1, "无权限");
		}
		if ($active == 1) {
			$a = 0;
			$b = "关闭会员";
		} else {
			$a = 1;
			$b = "开通会员";
		}
		$DB->query("update qingka_wangke_user set vip='$a' where uid='{$uid}' ");
		wlog($userrow['uid'], $b, "{$b}[UID {$uid}]成功", 0);
		jsonReturn(1, "操作成功");
		break;


	case 'loglist':
		$page = trim(strip_tags(daddslashes($_GET['page'])));
		$pagesize = trim(strip_tags(daddslashes($_GET['limit'])));
		$type = trim(strip_tags(daddslashes(trim($_GET['type']))));
		$types = trim(strip_tags(daddslashes(trim($_GET['types']))));
		$qq = trim(strip_tags(daddslashes(trim($_GET['qq']))));
		$pagesize = 20;
		$pageu = ($page - 1) * $pagesize;
		if ($userrow['uid'] != '1') {
			$sql1 = "where uid='{$userrow['uid']}'";
		} else {
			$sql1 = "where 1=1";
		}
		if ($type != '') {
			$sql2 = " and type='$type'";
		}
		if ($types != '') {
			if ($types == '1') {
				$sql3 = " and uid='$qq'";
			} else if ($types == '2') {
				$sql3 = " and money='$qq'";
			} else if ($types == '3') {
				$sql3 = " and`addtime` LIKE '%{$qq}%'";
			} else if ($types == '4') {
				$sql3 = " and`text` LIKE '%{$qq}%'";
			}
		}
		$sql = $sql1 . $sql2 . $sql3;
		$a = $DB->query("select id,uid,type,money,smoney,text,addtime,ip from qingka_wangke_log {$sql} order by id desc limit  $pageu,$pagesize ");
		$count1 = $DB->count("select count(id) from qingka_wangke_log {$sql}");
		while ($row = $DB->fetch($a)) {
			$data[] = $row;
		}
		$data = array('code' => 0, 'data' => $data, "count" => $count1);
		exit(json_encode($data));
		break;
	case 'djlist':
		$page = trim(strip_tags(daddslashes($_POST['page'])));
		$pagesize = 500;
		$pageu = ($page - 1) * $pagesize;
		if ($userrow['uid'] != '1') {
			jsonReturn(-1, "滚");
		}
		$a = $DB->query("select * from qingka_wangke_dengji");
		$count1 = $DB->count("select count(*) from qingka_wangke_dengji");
		while ($row = $DB->fetch($a)) {
			$data[] = array(
				'id' => $row['id'],
				'sort' => $row['sort'],
				'name' => $row['name'],
				'rate' => $row['rate'],
				'money' => $row['money'],
				'addkf' => $row['addkf'],
				'gjkf' => $row['gjkf'],
				'status' => $row['status'],
				'time' => $row['time'],
			);
		}
		foreach ($data as $key => $row) {
			$id[$key] = $row['id'];
			$sort[$key] = $row['sort'];
			$name[$key] = $row['name'];
			$rate[$key] = $row['rate'];
			$money[$key] = $row['money'];
			$addkf[$key] = $row['addkf'];
			$gjkf[$key] = $row['gjkf'];
			$status[$key] = $row['status'];
			$time[$key] = $row['time'];
		}
		array_multisort($sort, SORT_ASC, $rate, SORT_ASC, $data);
		$last_page = ceil($count1 / $pagesize);
		$data = array('code' => 1, 'data' => $data, "current_page" => (int) $page, "last_page" => $last_page);
		exit(json_encode($data));
		break;
	case 'dj':
		$data = daddslashes($_POST['data']);
		$active = trim(strip_tags(daddslashes(trim($_POST['active']))));
		$id = trim(strip_tags(daddslashes(trim($data['id']))));
		$sort = trim(strip_tags(daddslashes(trim($data['sort']))));
		$name = trim(strip_tags(daddslashes(trim($data['name']))));
		$rate = trim(strip_tags(daddslashes(trim($data['rate']))));
		$money = trim(strip_tags(daddslashes(trim($data['money']))));
		$status = trim(strip_tags(daddslashes(trim($data['status']))));
		$addkf = trim(strip_tags(daddslashes(trim($data['addkf']))));
		$gjkf = trim(strip_tags(daddslashes(trim($data['gjkf']))));
		if ($userrow['uid'] != '1') {
			jsonReturn(-1, "滚！");
		}
		if ($active == '1') {
			$DB->query("insert into qingka_wangke_dengji (sort,name,rate,money,addkf,gjkf,status,time) values ('$sort','$name','$rate','$money','$addkf','$gjkf','1',NOW())");
			jsonReturn(1, "添加成功");
		} elseif ($active == '2') {
			$DB->query("update qingka_wangke_dengji set `sort`='$sort',`name`='$name',`rate`='$rate',`money`='$money',`addkf`='$addkf',`gjkf`='$gjkf',`status`='$status' where id='$id'");
			jsonReturn(1, "修改成功");
		} else {
			jsonReturn(-1, "不知道你在干什么");
		}
		break;
	case 'dj_del':
		$id = daddslashes($_POST['id']);
		if ($userrow['uid'] != '1') {
			jsonReturn(-1, "滚");
		}
		$DB->query("delete from qingka_wangke_dengji where id='$id' ");
		jsonReturn(1, "删除成功");
		break;
	case 'fllist':
		$page = trim(strip_tags(daddslashes($_POST['page'])));
		$pagesize = 500;
		$pageu = ($page - 1) * $pagesize;
		if ($userrow['uid'] != '1') {
			jsonReturn(-1, "滚");
		}
		$a = $DB->query("select * from qingka_wangke_fenlei");
		$count1 = $DB->count("select count(*) from qingka_wangke_fenlei");
		while ($row = $DB->fetch($a)) {
			$data[] = array(
				'id' => $row['id'],
				'sort' => $row['sort'],
				'name' => $row['name'],
				'rate' => $row['rate'],
				'money' => $row['money'],
				'addkf' => $row['addkf'],
				'gjkf' => $row['gjkf'],
				'status' => $row['status'],
				'time' => $row['time'],
			);
		}
		foreach ($data as $key => $row) {
			$id[$key] = $row['id'];
			$sort[$key] = $row['sort'];
			$name[$key] = $row['name'];
			$rate[$key] = $row['rate'];
			$money[$key] = $row['money'];
			$addkf[$key] = $row['addkf'];
			$gjkf[$key] = $row['gjkf'];
			$status[$key] = $row['status'];
			$time[$key] = $row['time'];
		}
		array_multisort($sort, SORT_ASC, $rate, SORT_ASC, $data);
		$last_page = ceil($count1 / $pagesize);
		$data = array('code' => 1, 'data' => $data, "current_page" => (int) $page, "last_page" => $last_page);
		exit(json_encode($data));
		break;
	case 'fl':
		$data = daddslashes($_POST['data']);
		$active = trim(strip_tags(daddslashes(trim($_POST['active']))));
		$id = trim(strip_tags(daddslashes(trim($data['id']))));
		$sort = trim(strip_tags(daddslashes(trim($data['sort']))));
		$name = trim(strip_tags(daddslashes(trim($data['name']))));
		$status = trim(strip_tags(daddslashes(trim($data['status']))));
		if ($userrow['uid'] != '1') {
			jsonReturn(-1, "滚！");
		}
		if ($active == '1') {
			$DB->query("insert into qingka_wangke_fenlei (sort,name,status,time) values ('$sort','$name','1',NOW())");
			jsonReturn(1, "添加成功");
		} elseif ($active == '2') {
			$DB->query("update qingka_wangke_fenlei set `sort`='$sort',`name`='$name',`status`='$status' where id='$id'");
			jsonReturn(1, "修改成功");
		} else {
			jsonReturn(-1, "不知道你在干什么");
		}
		break;
	case 'fl_del':
		$id = daddslashes($_POST['id']);
		if ($userrow['uid'] != '1') {
			jsonReturn(-1, "滚");
		}
		$DB->query("delete from qingka_wangke_fenlei where id='$id' ");
		jsonReturn(1, "删除成功");
		break;
	case 'mijialist':
	    $page=trim(strip_tags(daddslashes($_POST['page'])));
	    $uid=trim(strip_tags(daddslashes($_POST['type'])));
		$pagesize=5000;
	    $pageu = ($page - 1) * $pagesize;//当前界面		
		if($userrow['uid']!='1'){
          	jsonReturn(-1,"滚");
		}
		
		if($uid!=''){
	    	$sql="where uid='$uid'";
	    }

		$a=$DB->query("select * from qingka_wangke_mijia {$sql}");
		$count1=$DB->count("select count(*) from qingka_wangke_mijia {$sql} ");
	    while($row=$DB->fetch($a)){    	
	       $r=$DB->get_row("select * from qingka_wangke_class where cid='{$row['cid']}' ");
	       $row['name']=$r['name'];
	   	   $data[]=$row;
	    }
	    $last_page=ceil($count1/$pagesize);//取最大页数
	    $data=array('code'=>1,'data'=>$data,"current_page"=>(int)$page,"last_page"=>$last_page,"uid"=>$userrow['uid']);
	    exit(json_encode($data));
	break;
	case 'mijia':
		$data = daddslashes($_POST['data']);
		$active = trim(strip_tags(daddslashes(trim($_POST['active']))));
		$uid = trim(strip_tags(daddslashes(trim($data['uid']))));
		$mid = trim(strip_tags(daddslashes(trim($data['mid']))));
		$mode = trim(strip_tags(daddslashes(trim($data['mode']))));
		$cid = trim(strip_tags(daddslashes(trim($data['cid']))));
		$price = trim(strip_tags(daddslashes(trim($data['price']))));
		if ($userrow['uid'] != '1') {
			jsonReturn(-1, "不知道你在干什么");
		}
		if ($active == '1') {
			$DB->query("insert into qingka_wangke_mijia (uid,cid,mode,price,addtime) values ('$uid','$cid','$mode','$price',NOW())");
			jsonReturn(1, "添加成功");
		} elseif ($active == '2') {
			$DB->query("update qingka_wangke_mijia set `price`='$price',`mode`='$mode',`uid`='$uid',`cid`='$cid' where mid='$mid' ");
			jsonReturn(1, "修改成功");
		} else {
			jsonReturn(-1, "不知道你在干什么");
		}
		break;
	case 'mijia_del':
		$mid = daddslashes($_POST['mid']);
		if ($userrow['uid'] != '1') {
			jsonReturn(-1, "滚");
		}
		$DB->query("delete from qingka_wangke_mijia where mid='$mid' ");
		jsonReturn(1, "删除成功");
		break;
	case 'sjqy':
		$uuid = daddslashes($_POST['uid']);
		$yqm = daddslashes($_POST['yqm']);
		if ($uuid == '' || $yqm == '') {
			exit('{"code":0,"msg":"所有项目不能为空"}');
		}
		if ($conf['sjqykg'] == 0) {
			exit('{"code":0,"msg":"管理员未打开迁移功能"}');
		} elseif ($conf['sjqykg'] == 1) {
			$row = $DB->get_row("select * from qingka_wangke_user where uid='$uuid' limit 1");
			if ($row) {
				if ($yqm == $row['yqm']) {
					$row1 = $DB->get_row("select * from qingka_wangke_user where uid='{$userrow['uid']}' limit 1");
					if ($row1['uuid'] != $uuid) {
						if ($row1['uid'] != $uuid) {
							$ztdate = date("Y-m-d", strtotime("-7 day"));
							$row8848 = $DB->get_row("select * from qingka_wangke_user where uid='{$userrow['uuid']}' limit 1");
							if ($row8848['endtime'] < $zhdl) {
								$DB->query("update qingka_wangke_user set `uuid`='$uuid' where uid='{$userrow['uid']}' ");
								if ($DB) {
									jsonReturn(1, "迁移成功,您已迁移至[UID$uuid]的名下");
								} else {
									jsonReturn(-1, "迁移失败,未知错误");
								}
							} else {
								jsonReturn(-1, "上级在七天内有登陆记录，禁止转移");
							}
						} else {
							jsonReturn(-1, "禁止填写自己的UID");
						}
					} else {
						jsonReturn(-1, "该用户已经是你的上级了");
					}
				} else {
					jsonReturn(-1, "非该用户邀请码，请重新输入");
				}
			} else {
				jsonReturn(-1, "UID不存在，请重新输入");
			}
		}


		break;
	case 'navs':
		if ($userrow['uid'] == 1) {
			$data = file_get_contents('user/data/navs.json');
			$data = json_decode($data, true);
			if ($conf['flkg'] == 1 && $conf['fllx'] == 0) {
				$data[2]['children'][0] = array('title' => "项目分类", 'href' => "", 'icon' => "&#xe63c;", 'spread' => false, 'children' => [], );
				$a = $DB->query("select * from qingka_wangke_fenlei where status=1 ORDER BY `sort` ASC");
				while ($row = $DB->fetch($a)) {
					$data[2]['children'][0]['children'][] = array('title' => $row['name'], 'href' => "add?id=" . $row['id'], 'fontFamily' => "layui-icon", 'icon' => "&#xe657;", 'spread' => false, );
					$data[2]['children'][1] = array('title' => "所有项目", 'href' => "add", 'fontFamily' => "layui-icon", 'icon' => "&#xe657;", 'spread' => false, );
				}
			} else {
				$data[2] = array('title' => "提交订单", 'href' => "add", 'icon' => "&#xe6af;", 'spread' => true, 'fontFamily' => "ok-icon", );
			}

			$data = json_encode($data);
			exit($data);
		} else if ($userrow['uid'] != 1) {
			$data = file_get_contents('user/data/navs1.json');
			$data = json_decode($data, true);
			if ($conf['flkg'] == 1 && $conf['fllx'] == 0) {
				$data[1]['children'][0] = array('title' => "项目分类", 'href' => "", 'icon' => "&#xe63c;", 'spread' => false, 'children' => [], );
				$a = $DB->query("select * from qingka_wangke_fenlei where status=1 ORDER BY `sort` ASC");
				while ($row = $DB->fetch($a)) {
					$data[1]['children'][0]['children'][] = array('title' => $row['name'], 'href' => "add?id=" . $row['id'], 'fontFamily' => "layui-icon", 'icon' => "&#xe657;", 'spread' => false, );
					$data[1]['children'][1] = array('title' => "所有项目", 'href' => "add", 'fontFamily' => "layui-icon", 'icon' => "&#xe657;", 'spread' => false, );
				}
			} else {
				$data[1] = array('title' => "提交订单", 'href' => "add", 'icon' => "&#xe6af;", 'spread' => true, 'fontFamily' => "ok-icon", );
			}
			$data = json_encode($data);
			exit($data);
		}

		break;
	// 	case 'plzt':
// 		$sex = daddslashes($_POST['sex']);
// 		$rediscode = $redis->ping();
// 		if ($rediscode == true) {
// 			for ($i = 0; $i < count($sex); $i++) {
// 				$oid = $sex[$i];
// 				$redis->lPush("plztoid", $oid);

	// 			}
// 			wlog($userrow['uid'], "批量同步状态", "批量同步状态入队成功，共入队{$i}条", 0);
// 			jsonReturn(1, "批量同步状态入队成功，共入队{$i}条，请耐心等待同步");
// 		} else {
// 			jsonReturn(-1, "入队失败");
// 		}

	// 		break;
	case 'gglist':
		$a = $DB->query("select * from qingka_wangke_gonggao where status='1'");
		while ($row = $DB->fetch($a)) {
			$data[] = $row;
		}
		foreach ($data as $key => $row) {
			$id[$key] = $row['id'];
			$title[$key] = $row['title'];
			$content[$key] = $row['content'];
			$time[$key] = $row['time'];
			$uid[$key] = $row['uid'];
			$status[$key] = $row['status'];
			$zhiding[$key] = $row['zhiding'];
		}
		array_multisort($zhiding, SORT_DESC, $time, SORT_DESC, $id, SORT_ASC, $data);
		$data = array('code' => 1, 'data' => $data, 'shoot' => $conf['tcgonggao']);
		exit(json_encode($data));
		break;
	case 'gglist1':
		if ($userrow['uid'] != 1) {
			jsonReturn(-1, "无权限");
		}

		$a = $DB->query("select * from qingka_wangke_gonggao");
		while ($row = $DB->fetch($a)) {
			$data[] = $row;
		}
		foreach ($data as $key => $row) {
			$id[$key] = $row['id'];
			$title[$key] = $row['title'];
			$content[$key] = $row['content'];
			$time[$key] = $row['time'];
			$uid[$key] = $row['uid'];
			$status[$key] = $row['status'];
			$zhiding[$key] = $row['zhiding'];
		}
		array_multisort($zhiding, SORT_DESC, $time, SORT_DESC, $id, SORT_ASC, $data);
		$data = array('code' => 1, 'data' => $data);
		exit(json_encode($data));
		break;
	case 'ggadd':
		$data = daddslashes($_POST['data']);
		$active = trim(strip_tags(daddslashes(trim($_POST['active']))));
		$title = trim(strip_tags(daddslashes(trim($data['title']))));
		$content = $data['content'];
		$status = trim(strip_tags(daddslashes(trim($data['status']))));
		$zhiding = trim(strip_tags(daddslashes(trim($data['zhiding']))));
		$id = trim(strip_tags(daddslashes(trim($data['id']))));
		if ($userrow['uid'] != '1') {
			jsonReturn(-1, "不知道你在干什么");
		}
		if ($active == '1') {
			$DB->query("insert into qingka_wangke_gonggao (title,content,status,zhiding,uid,time) values ('$title','$content','$status','$zhiding','{$userrow['uid']}',NOW())");
			jsonReturn(1, "添加成功");
		} elseif ($active == '2') {
			$DB->query("update qingka_wangke_gonggao set `title`='$title',`content`='$content',`status`='$status',`zhiding`='$zhiding' where id='$id' ");
			jsonReturn(1, "修改成功");
		} else {
			jsonReturn(-1, "不知道你在干什么");
		}
		break;
	case 'gg_del':
		$id = daddslashes($_POST['id']);
		if ($userrow['uid'] != '1') {
			jsonReturn(-1, "滚");
		}
		$DB->query("delete from qingka_wangke_gonggao where id='$id' ");
		jsonReturn(1, "删除成功");
		break;







		case 'upgg':
			// Remote announcement, recommendation and update feeds were removed.
			$data = array();
			$recommend = array();
			$version_list = array();
		$ip = getServerIp();
		$url = $_SERVER['SERVER_NAME'];
		$data = array('code' => 1, 'data' => $data, 'recommend' => $recommend, 'version_list' => $version_list, 'app_version' => $auth['app_version'], 'app_name' => $auth['app_name'], 'app_author' => $auth['app_author'], 'url' => $url, 'ip' => $ip);
		exit(json_encode($data));
		break;
















	case 'czkpay':
		$card = trim(strip_tags(daddslashes($_POST['card'])));
		$type = trim(strip_tags(daddslashes($_POST['type'])));
		$uid = trim(strip_tags(daddslashes($_POST['uid']))) ? trim(strip_tags(daddslashes($_POST['uid']))) : $userrow['uid'];
		$date = date("Y-m-d H:i:s");
		if ($userrow['money'] <= 1) {
			exit('{"code":-1,"msg":"不要白嫖"}');
		}



		if ($card == '') {
			jsonReturn(-1, "充值卡不能为空。");
		}
		if ($type != 1) {
			$row = $DB->get_row("select * from qingka_wangke_czk where card='{$card}' limit 1");
			if ($row) {
				if ($row['status'] == 1) {
					jsonReturn(-1, "卡密已被使用");
				}
				if ($row['endtime'] != 0 && $row['endtime'] <= $date) {
					jsonReturn(-1, "卡密已到期");
				}
				exit('{"code":1,"msg":"卡密信息获取成功","money":"' . $row['money'] . '","addtime":"' . $row['addtime'] . '","endtime":"' . $row['endtime'] . '"}');
			} else {
				jsonReturn(-1, "卡密错误获取失败");
			}
		} else if ($type == 1) {
			$row = $DB->get_row("select * from qingka_wangke_czk where card='{$card}' limit 1");
			if ($row) {
				if ($row['status'] == 1) {
					jsonReturn(-1, "卡密已被使用");
				}
				if ($row['endtime'] != 0 && $row['endtime'] <= $date) {
					jsonReturn(-1, "卡密已到期");
				}

				// 检查用户是否已经使用过该批次的卡密
				$batch_id = $row['batch_id'];
				$used_batches = explode(',', $user['used_batches']);
				if (in_array($batch_id, $used_batches)) {
					wlog($userrow['uid'], "卡密充值", "尝试卡密充值失败，你已经使用过了", 0);
					exit('{"code":-1,"msg":"叼毛！您已经使用过本次的活动卡密了"}');

				}
				// 更新用户的 used_batches 字段
				$used_batches[] = $batch_id;
				$used_batches_str = implode(',', $used_batches);
				$DB->query("UPDATE qingka_wangke_user SET used_batches='$used_batches_str' WHERE uid='$uid'");

				$DB->query("update qingka_wangke_user set money=money+'{$row['money']}',zcz=zcz+'{$row['money']}' where uid='{$userrow['uid']}' ");
				$DB->query("update qingka_wangke_czk set status='1',usetime='{$date}',uid='{$userrow['uid']}' where card='{$card}' ");
				wlog($userrow['uid'], "卡密充值", "{$userrow['uid']}成功使用卡密充值{$row['money']}元", +$row['money']);
				jsonReturn(1, "UID[" . $userrow['uid'] . "] 充值[" . $row['money'] . "]元成功");
			} else {
				jsonReturn(-1, "卡密错误获取失败");
			}
		}

		break;
	case 'czklist':
		$page = trim(strip_tags(daddslashes($_GET['page'])));
		$pagesize = trim(strip_tags(daddslashes($_GET['limit'])));
		$id = trim(strip_tags(daddslashes($_GET['id'])));
		$card = trim(strip_tags(daddslashes($_GET['card'])));
		$money = trim(strip_tags(daddslashes($_GET['money'])));
		$addtime = trim(strip_tags(daddslashes($_GET['addtime'])));
		$endtime = trim(strip_tags(daddslashes($_GET['endtime'])));
		$status = trim(strip_tags(daddslashes($_GET['status'])));
		$usetime = trim(strip_tags(daddslashes($_GET['usetime'])));
		$batch_id = trim(strip_tags(daddslashes($_GET['batch_id'])));//字段batch_id
		$uid = trim(strip_tags(daddslashes($_GET['uid'])));
		$pageu = ($page - 1) * $pagesize;
		if ($userrow['uid'] != '1') {
			jsonReturn(-1, "滚");
		} else {
			$sql1 = "where 1=1";
		}
		if ($id != '') {
			$sql2 = " and id='{$id}'";
		}
		if ($card != '') {
			$sql3 = " and card='{$card}'";
		}
		if ($money != '') {
			$sql4 = " and money='{$money}'";
		}
		if ($addtime != '') {
			$sql5 = " and addtime='{$addtime}'";
		}
		if ($endtime != '') {
			$sql6 = " and endtime='{$endtime}'";
		}
		if ($status != '') {
			$sql7 = " and status='{$status}'";
		}
		if ($usetime != '') {
			$sql8 = " and usetime='{$usetime}'";
		}
		if ($uid != '') {
			$sql9 = " and uid='{$uid}'";
		}
		$sql = $sql1 . $sql2 . $sql3 . $sql4 . $sql5 . $sql6 . $sql7 . $sql8 . $sql9;
		$a = $DB->query("select * from qingka_wangke_czk {$sql} order by id desc limit $pageu,$pagesize ");
		$count1 = $DB->count("select count(id) from qingka_wangke_czk {$sql} ");
		while ($row = $DB->fetch($a)) {
			$data[] = $row;
		}
		$last_page = ceil($count1 / $pagesize);
		$data = array('code' => 0, 'data' => $data, "count" => $count1, );
		exit(json_encode($data));
		break;
	case 'czk_del':
		$sex = daddslashes($_POST['sex']);
		if ($userrow['uid'] != '1') {
			jsonReturn(-1, "滚");
		}
		if (empty($sex)) {
			jsonReturn(-1, "请先选择卡密");
		}
		$z = 0;
		$q = 0;
		for ($i = 0; $i < count($sex); $i++) {
			$id = $sex[$i]['id'];
			$a = $DB->query("DELETE FROM `qingka_wangke_czk` WHERE id='$id' ");
			if ($a) {
				$z = $z + 1;
			} else {
				$q = $q + 1;
			}
		}
		jsonReturn(1, "共操作{$i}张卡密，成功删除{$z}张，删除失败{$q}张");
		break;
	case 'addczk':
		if ($userrow['uid'] != '1') {
			jsonReturn(-1, "滚");
		}
		parse_str(daddslashes($_POST['data']), $row);
		$type = daddslashes($_POST['type']);
		$row['qianzhui'] = trim($row['qianzhui']);
		$row['money'] = trim($row['money']);
		$row['num'] = trim($row['num']);
		$row['batch_id'] = trim($row['batch_id']); // 接收 batch_id 字段
		$row['endtime'] = trim($row['endtime']);
		if ($row['money'] == '' || $row['num'] == '' || $row['endtime'] == '' || $row['qianzhui'] == '' || $row['batch_id'] == '') {
			exit('{"code":-2,"msg":"所有项目不能为空"}');
		}

		if ($row['money'] <= 0) {
			exit('{"code":-2,"msg":"面值要大于0"}');
		}
		if ($row['num'] <= 0) {
			exit('{"code":-2,"msg":"数量要大于0"}');
		}

		$date = date("Y-m-d H:i:s");
		for ($i = 0; $i < $row['num']; $i++) {
			$care = random(32);
			$care = $row['qianzhui'] . '_' . $care . '_' . $row['money'] . '_' . $row['batch_id'];
			if ($DB->get_row("select id from qingka_wangke_czk where card='$care'")) {
				$i = $i - 1;
			} else {
				$is = $DB->query("INSERT INTO qingka_wangke_czk (card, money, addtime, endtime, status, batch_id) VALUES ('{$care}', '{$row['money']}', '{$date}', '{$row['endtime']}', '0', '{$row['batch_id']}')");
				$data = $care . "<br>" . $data;
			}
		}
		$b = array('code' => 1, 'data' => $data, 'msg' => '成功');
		exit(json_encode($b));
		break;
	case 'userqy':
		$uid = trim(strip_tags(daddslashes($_POST['uid'])));
		$uuid = trim(strip_tags(daddslashes($_POST['uuid'])));
		if ($userrow['uid'] != '1') {
			jsonReturn(-1, "滚你妈");
		}
		if (!$a = $DB->get_row("select * from qingka_wangke_user where uid='$uuid' limit 1")) {
			jsonReturn(-1, "没有这个上级uid");
		}
		$b = $DB->get_row("select * from qingka_wangke_user where uid='$uid' limit 1");
		if ($b['uid'] == $uuid) {
			jsonReturn(-1, "上级的uid不能为代理自己的uid");
		}
		if ($b['uuid'] == $uuid) {
			jsonReturn(-1, "这已经是代理的上级了");
		}
		$row = $DB->query("update qingka_wangke_user set uuid='$uuid' where uid='$uid'");
		if ($row) {
			wlog($userrow['uid'], "代理迁移", "成功给账号为[{$uid}]的靓仔迁移至[{$uid}]旗下", 0);
			wlog($uid, "上级迁移", "管理员将你成功迁移至[{$uid}]旗下", 0);
			exit('{"code":1,"msg":"为【' . $uid . '】迁移成功"}');
		} else {
			exit('{"code":-1,"msg":"失败"}');
		}
		break;
	case 'myprice':
		$fenlei = trim(strip_tags(daddslashes($_GET['fenlei'])));
		$name = trim(strip_tags(daddslashes($_GET['name'])));
		$pageu = ($page - 1) * $pagesize;
		$sql1 = "where status=1";
		if ($fenlei != '') {
			$sql2 = " and fenlei='{$fenlei}'";
		}
		if ($name != '') {
			$sql3 = " and `name` LIKE '%{$name}%'";
		}
		$sql = $sql1 . $sql2 . $sql3;
		$a = $DB->query("select cid,name,price,fenlei,yunsuan from qingka_wangke_class {$sql} ORDER BY `fenlei` ASC , `sort` ASC ,`cid` DESC");
		$i = 0;
		while ($rs = $DB->fetch($a)) {
			$rs['beishu'] = "({$rs['price']}倍) x {$userrow['addprice']}";
			if ($rs['yunsuan'] == "*") {
				$price = round($rs['price'] * $userrow['addprice'], 2);
				$price1 = $price;
			} elseif ($rs['yunsuan'] == "+") {
				$price = round($rs['price'] + $userrow['addprice'], 2);
				$price1 = $price;
			} else {
				$price = round($rs['price'] * $userrow['addprice'], 2);
				$price1 = $price;
			}
			$mijia = $DB->get_row("select * from qingka_wangke_mijia where uid='{$userrow['uid']}' and cid='{$rs['cid']}' ");
			if ($mijia) {
				if ($mijia['mode'] == 0) {
					$price = round($price - $mijia['price'], 2);
					if ($price <= 0) {
						$price = 0;
					}
				} elseif ($mijia['mode'] == 1) {
					$price = round(($rs['price'] - $mijia['price']) * $userrow['addprice'], 2);
					if ($price <= 0) {
						$price = 0;
					}
				} elseif ($mijia['mode'] == 2) {
					$price = $mijia['price'];
					if ($price <= 0) {
						$price = 0;
					}
				}
				$rs['name'] = "密*{$rs['name']}";
			}
			if ($price >= $price1) {
				$price = $price1;
			}
			$rs['my'] = round($price, 2);
			$b = $DB->query("select * from qingka_wangke_dengji where status=1 and rate>='{$userrow['addprice']}' ORDER BY `sort` ASC");
			while ($row = $DB->fetch($b)) {
				$rs[$row['id']] = round($rs['price'] * $row['rate'], 2);
			}

			$data[] = $rs;
			$i = $i + 1;
		}

		$data = array('code' => 0, 'data' => $data, "count" => $i);
		exit(json_encode($data));
		break;

        
    default:
        exit(json_encode(['code' => -1, 'msg' => '无效的操作']));


}

?>
