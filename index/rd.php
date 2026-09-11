<?php
include('head.php');
if ($islogin != 1) {
	exit("<script language='javascript'>window.location.href='login';</script>");
}
// 定义一个函数，用于对用户名进行脱敏处理
function processUsername($name)
{
	// 如果$name的长度大于4，则脱敏中间的字符
	if (is_numeric($name)) {
		$length = strlen($name);
		// 如果数字长度大于4，则进行脱敏处理
		if ($length > 4) {
			$front = substr($name, 0, 2); // 保留前两位
			$end = substr($name, -2); // 保留后两位
			$stars = str_repeat('*', $length - 4); // 中间用*代替
			return $front . $stars . $end;
		}
		// 如果数字长度小于等于4，则不进行脱敏处理
		return $name;
	}
	// 如果$name不是数字，则不进行脱敏处理
	return $name;
}
require_once('../confing/configuration.php');

if (CONFIG_KEY !== '8848') {
msg('配置文件损坏或缺失');
}
?>

<div class="layui-container" style="padding: 20px;">
	<div class="layui-row" style="margin-bottom: 15px;">
		<div class="layui-card">
			<div class="layui-card-header" style="background-color: #F2F2F2; font-weight: bold;">巅峰玩家排行榜(最近三个月)</div>
			<div class="layui-card-body">
				<table class="layui-table" style="table-layout: fixed; width: 100%;">
					<thead>
						<tr>
							<th style="color: blue; text-align: center;">序号</th>
							<th style="color: blue; text-align: center;">玩家</th>
							<th style="color: blue; text-align: center;">订单数量</th>

						</tr>
					</thead>
					<tbody>

						<?php

						//本周排行
						// $query = 
						
						// "SELECT u.name AS name, COUNT(o.uid) AS order_count FROM qingka_wangke_order AS o JOIN qingka_wangke_user AS u ON o.uid = u.uid WHERE YEARWEEK(o.addtime, 1) = YEARWEEK(NOW(), 1) GROUP BY o.uid ORDER BY order_count DESC LIMIT 10";
						



						$query = "SELECT u.name AS name, COUNT(o.uid) AS order_count 
FROM qingka_wangke_order AS o 
JOIN qingka_wangke_user AS u ON o.uid = u.uid 
WHERE o.addtime >= DATE_SUB(NOW(), INTERVAL 3 MONTH) 
GROUP BY o.uid 
ORDER BY order_count DESC 
LIMIT 20";//今年排行
						$i = 0;
						$result = $DB->query($query);
						$rank = 1;
						?>

						<?php

						function desensitize($name)
						{
							// 如果$name是数字
							if (is_numeric($name)) {
								$length = strlen($name);
								// 如果数字长度大于4，则进行脱敏处理
								if ($length > 4) {
									$front = substr($name, 0, 2); // 保留前两位
									$end = substr($name, -2); // 保留后两位
									$stars = str_repeat('*', $length - 4); // 中间用*代替
									return $front . $stars . $end;
								}
								// 如果数字长度小于等于4，则不进行脱敏处理
								return $name;
							}
							// 如果$name不是数字，则不进行脱敏处理
							return $name;
						}

						$i = 0; // 初始化计数器
						while ($row = $DB->fetch($result)) {
							$name = $row['name'];
							$orderCount = $row['order_count'];

							// 对$name进行脱敏处理
							$desensitized_name = desensitize($name);

							// 确定颜色：前三名用黄色，其余用红色
							$color = $i < 3 ? 'red' : 'black';

							echo '<tr>
              <td style="color: ' . $color . '; text-align: center;">TOP.' . ($i + 1) . '</td>
              <td style="color: ' . $color . '; text-align: center;">' . $desensitized_name . '</td>
              <td style="color: ' . $color . '; text-align: center;">' . $orderCount . '</td>
          </tr>';
							$i++;
						}


						?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>






<div class="layui-container" style="padding: 20px;">
	<div class="layui-row" style="margin-bottom: 15px;">
		<div class="layui-card">
			<div class="layui-card-header" style="background-color: #F2F2F2; font-weight: bold;">本周巅峰玩家排行榜</div>
			<div class="layui-card-body">
				<table class="layui-table" style="table-layout: fixed; width: 100%;">
					<thead>
						<tr>
							<th style="color: blue; text-align: center;">序号</th>
							<th style="color: blue; text-align: center;">玩家</th>
							<th style="color: blue; text-align: center;">订单数量</th>

						</tr>
					</thead>
					<tbody>

						<?php

						//本周排行
						$query =

							"SELECT u.name AS name, COUNT(o.uid) AS order_count FROM qingka_wangke_order AS o JOIN qingka_wangke_user AS u ON o.uid = u.uid WHERE YEARWEEK(o.addtime, 1) = YEARWEEK(NOW(), 1) GROUP BY o.uid ORDER BY order_count DESC LIMIT 10";




						//             $query = "SELECT u.name AS name, COUNT(o.uid) AS order_count 
						//   FROM qingka_wangke_order AS o 
						//   JOIN qingka_wangke_user AS u ON o.uid = u.uid 
						//   WHERE YEAR(o.addtime) = YEAR(NOW()) 
						//   GROUP BY o.uid 
						//   ORDER BY order_count DESC 
						//   LIMIT 20";//今年排行
						$i = 0;
						$result = $DB->query($query);
						$rank = 1;
						?>

						<?php

						function newDesensitize($name)
						{
							// 如果$name是数字
							if (is_numeric($name)) {
								$length = strlen($name);
								// 如果数字长度大于4，则进行脱敏处理
								if ($length > 4) {
									$front = substr($name, 0, 2); // 保留前两位
									$end = substr($name, -2); // 保留后两位
									$stars = str_repeat('*', $length - 4); // 中间用*代替
									return $front . $stars . $end;
								}
								// 如果数字长度小于等于4，则不进行脱敏处理
								return $name;
							}
							// 如果$name不是数字，则不进行脱敏处理
							return $name;
						}

						$i = 0; // 初始化计数器
						while ($row = $DB->fetch($result)) {
							$name = $row['name'];
							$orderCount = $row['order_count'];

							// 对$name进行脱敏处理
							$desensitized_name = desensitize($name);

							// 确定颜色：前三名用黄色，其余用红色
							$color = $i < 3 ? 'red' : 'black';

							echo '<tr>
              <td style="color: ' . $color . '; text-align: center;">TOP.' . ($i + 1) . '</td>
              <td style="color: ' . $color . '; text-align: center;">' . $desensitized_name . '</td>
              <td style="color: ' . $color . '; text-align: center;">' . $orderCount . '</td>
          </tr>';
							$i++;
						}


						?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>



















<?php if ($conf['czph'] == 1) { ?>
	<div class="layui-container" style="padding: 20px;">
		<div class="layui-row" style="margin-bottom: 15px;">
			<div class="layui-card">
				<div class="layui-card-header" style="background-color: #F2F2F2; font-weight: bold;">本周充值排行</div>
				<div class="layui-card-body">
					<table class="layui-table" style="table-layout: fixed; width: 100%;">
						<thead>
							<tr>
								<th style="color: blue; text-align: center;">序号</th>
								<th style="color: blue; text-align: center;">玩家</th>
								<th style="color: blue; text-align: center;">元子</th>
							</tr>
						</thead>
						<tbody>
							<?php
							$query = "SELECT u.name AS name, SUM(l.money) AS money 
FROM qingka_wangke_log AS l 
JOIN qingka_wangke_user AS u ON l.uid = u.uid 
WHERE YEARWEEK(l.addtime, 1) = YEARWEEK(CURDATE(), 1) AND l.money > 0 
GROUP BY l.uid 
ORDER BY money DESC 
LIMIT 10;
";
							$result = $DB->query($query);
							$i = 0;
							while ($row = $DB->fetch($result)) {
								$name = processUsername($row['name']); // 统一处理用户名
								$money = $row['money'];
								$color = $i < 3 ? 'red' : 'black';
								echo '<tr>
                                      <td style="color: ' . $color . '; text-align: center;">TOP.' . ($i + 1) . '</td>
                                      <td style="color: ' . $color . '; text-align: center;">' . $name . '</td>
                                      <td style="color: ' . $color . ' ;text-align: center;">' . number_format($money, 2) . '</td>
                                  </tr>';
								$i++;
							}
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

<? } ?>

<div class="layui-container" style="padding: 20px;">
	<div class="layui-row" style="margin-bottom: 15px;">
		<div class="layui-card">
			<div class="layui-card-header" style="background-color: #F2F2F2; font-weight: bold;">今日排行榜</div>
			<div class="layui-card-body">
				<table class="layui-table" style="table-layout: fixed; width: 100%;">
					<thead>
						<tr>
							<th style="color: red; text-align: center;">排名</th>
							<th style="color: red; text-align: center;">课程ID</th>
							<th style="color: red; text-align: center;">项目</th>
							<th style="color: blue; text-align: center;">最新下单时间</th>

						</tr>
					</thead>
					<tbody>
						<?php




						// 数据库查询，获取当前年份的销售记录
						$aaa = $DB->query('SELECT ptname, cid, MAX(addtime) as addtime, COUNT(*) as sales_count, SUM(fees) as total_fees 
FROM qingka_wangke_order 
WHERE DATE(addtime) = CURDATE() 
GROUP BY cid 
ORDER BY sales_count DESC, addtime DESC 
LIMIT 20;
');


						$i = 0; // 初始化计数器
						while ($row = $DB->fetch($aaa)) {
							$addtime = $row['addtime'];


							// 输出表格行，注意字符串连接的正确用法
							echo '<tr>
            <td style="text-align: center;">TOP.' . ($i + 1) . '</td>
             
              <td style="color: blue; text-align: center;">' . $row['cid'] . '</td>
               <td style="color: blue; text-align: center;">' . $row['ptname'] . '</td>
              <td style="color: blue; text-align: center;">' . $addtime . '</td>
              
          </tr>';
							$i++;
						}


						?>
					</tbody>
				</table>
			</div>
		</div>
	</div>





	<div class="layui-col-sm6" style="padding: 10px;">
		<div class="layui-card">
			<div class="layui-card-header" style="background-color: #F2F2F2; font-weight: bold;">昨日排行榜</div>
			<div class="layui-card-body">
				<table class="layui-table" style="table-layout: fixed; width: 100%;">
					<thead>
						<tr>
							<th style="text-align: center;">排名</th>
							<th style="color: red;text-align: center;">课程ID</th>
							<th style="color: red;text-align: center;">项目</th>


						</tr>
					</thead>
					<tbody>
						<?php




						// 数据库查询，获取当前年份的销售记录
						$aaa = $DB->query('SELECT ptname, cid, MAX(addtime) as addtime, COUNT(*) as sales_count, SUM(fees) as total_fees 
FROM qingka_wangke_order 
WHERE DATE(addtime) = CURDATE() - INTERVAL 1 DAY 
GROUP BY cid 
ORDER BY sales_count DESC, addtime DESC 
LIMIT 20;
');



						$i = 0; // 初始化计数器
						while ($row = $DB->fetch($aaa)) {
							$addtime = $row['addtime'];


							// 输出表格行，注意字符串连接的正确用法
							echo '<tr>
             <td style="color: red; text-align: center;">TOP.' . ($i + 1) . '</td>
              <td style="color: blue;text-align: center;">' . $row['cid'] . '</td>
              <td style="color: blue;text-align: center;">' . $row['ptname'] . '</td>
              
              
          </tr>';
							$i++;
						}


						?>
					</tbody>
				</table>
			</div>
		</div>
	</div>









	<div class="layui-col-sm6" style="padding: 10px;">
		<div class="layui-card">
			<div class="layui-card-header" style="background-color: #F2F2F2; font-weight: bold;">本周排行榜</div>
			<div class="layui-card-body">
				<table class="layui-table" style="table-layout: fixed; width: 100%;">
					<thead>
						<tr>
							<th style="text-align: center;">排名</th>
							<th style="color: red;text-align: center;">课程ID</th>
							<th style="color: red;text-align: center;">项目</th>

						</tr>
					</thead>
					<tbody>
						<?php




						// 数据库查询，获取当前年份的销售记录
						$aaa = $DB->query('SELECT ptname, cid, MAX(addtime) as addtime, COUNT(*) as sales_count, SUM(fees) as total_fees 
FROM qingka_wangke_order 
WHERE YEARWEEK(addtime, 1) = YEARWEEK(CURDATE(), 1) 
GROUP BY cid 
ORDER BY sales_count DESC, addtime DESC 
LIMIT 20;
');



						$i = 0; // 初始化计数器
						while ($row = $DB->fetch($aaa)) {
							$addtime = $row['addtime'];


							// 输出表格行，注意字符串连接的正确用法
							echo '<tr>
          
             <td style="color: red; text-align: center;">TOP.' . ($i + 1) . '</td>
              <td style="color: blue;text-align: center;">' . $row['cid'] . '</td>
              <td style="color: blue;text-align: center;">' . $row['ptname'] . '</td>
          </tr>';
							$i++;
						}


						?>
					</tbody>
				</table>
			</div>
		</div>
	</div>


	</body>