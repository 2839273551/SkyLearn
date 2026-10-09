<?php
require_once('head.php');
if ($islogin != 1) {
	exit("<script language='javascript'>window.location.href='login';</script>");
}
if ($userrow['uid'] != 1) {
	exit("<script language='javascript'>window.location.href='login';</script>");
}
require_once('../confing/configuration.php');

if (CONFIG_KEY !== '8848') {
	msg('配置文件损坏或缺失');
}
?>
<div class="layui-container" style="padding: 20px;">
	<div class="layui-row" style="margin-bottom: 15px;">
		<div class="layui-card">
			<div class="layui-card-header" style="background-color: #F2F2F2; font-weight: bold;">货源排行榜</div>
			<div class="layui-card-body">
				<table class="layui-table" style="table-layout: fixed; width: 100%;">
					<thead>
						<tr>
							<th style="text-align: center;">货源名称</th>
							<th style="text-align: center;">今日销量</th>
							<th style="text-align: center;">昨日销量</th>
							<th style="text-align: center;">本周销量</th>
							<th style="text-align: center;">本月销量</th>
							<th style="text-align: center;">总销量</th>
							<th style="text-align: center;">最后下单时间</th>
						</tr>
					</thead>
					<tbody>
						<?php
						// 计算今日开始和结束日期
						$todayStart = date('Y-m-d 00:00:00');
						$todayEnd = date('Y-m-d 23:59:59');

						// 计算昨日开始和结束日期
						$yesterdayStart = date('Y-m-d 00:00:00', strtotime('yesterday'));
						$yesterdayEnd = date('Y-m-d 23:59:59', strtotime('yesterday'));

						// 计算本周开始日期（假设周一为一周的开始）
						$weekStart = date('Y-m-d', strtotime('monday this week'));

						// 修改查询语句，统计每个货源的今日销量、昨日销量、本周销量、本月销量和总销量，并获取最后下单时间
						$query = "SELECT hw.name, 
                                (SELECT COUNT(*) FROM qingka_wangke_order WHERE hid = hw.hid AND addtime >= '{$todayStart}' AND addtime <= '{$todayEnd}') AS today_count,
                                (SELECT COUNT(*) FROM qingka_wangke_order WHERE hid = hw.hid AND addtime >= '{$yesterdayStart}' AND addtime <= '{$yesterdayEnd}') AS yesterday_count,
                                (SELECT COUNT(*) FROM qingka_wangke_order WHERE hid = hw.hid AND addtime >= '{$weekStart}') AS week_count,
                                (SELECT COUNT(*) FROM qingka_wangke_order WHERE hid = hw.hid AND MONTH(addtime) = MONTH(CURDATE()) AND YEAR(addtime) = YEAR(CURDATE())) AS month_count,
                                COUNT(o.hid) AS total_count,
                                MAX(o.addtime) AS latest_order_time 
                                FROM qingka_wangke_huoyuan hw
                                LEFT JOIN qingka_wangke_order o ON hw.hid = o.hid
                                WHERE hw.status=1
                                GROUP BY hw.hid
                                ORDER BY total_count DESC, latest_order_time DESC"; // 按照总销量降序排列
						
						$result = $DB->query($query);

						while ($row = $DB->fetch($result)) {
							$formatted_time = $row['latest_order_time'] ? date('Y-m-d H:i:s', strtotime($row['latest_order_time'])) : '无订单';

							// 输出货源名称、今日销量、昨日销量、本周销量、本月销量、总销量和最后下单时间
							echo '<tr>
                                    <td style="text-align: center;">' . htmlspecialchars($row['name']) . '</td>
                                    <td style="text-align: center;">' . $row['today_count'] . '</td>
                                    <td style="text-align: center;">' . $row['yesterday_count'] . '</td>
                                    <td style="text-align: center;">' . $row['week_count'] . '</td>
                                    <td style="text-align: center;">' . $row['month_count'] . '</td>
                                    <td style="text-align: center;">' . $row['total_count'] . '</td>
                                    <td style="text-align: center;">' . $formatted_time . '</td>
                                  </tr>';
						}
						?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>



<?php

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

?>


<div class="layui-container" style="padding: 20px;">
	<div class="layui-row" style="margin-bottom: 15px;">
		<div class="layui-card">
			<div class="layui-card-header" style="background-color: #F2F2F2; font-weight: bold;">总销量排行榜</div>
			<div class="layui-card-body">
				<table class="layui-table" style="table-layout: fixed; width: 100%;">
					<thead>
						<tr>
							<th style="text-align: center;">货源名称</th>
							<th style="text-align: center;">今日销量</th>
							<th style="text-align: center;">昨日销量</th>
							<th style="text-align: center;">本周销量</th>
							<th style="text-align: center;">本月销量</th>
							<th style="text-align: center;">总销量</th>
							<th style="text-align: center;">最后下单时间</th>
						</tr>
					</thead>
					<tbody>
						<?php
						// 计算今日、昨日、本周起始日期
						$todayStart = date('Y-m-d 00:00:00');
						$yesterdayStart = date('Y-m-d 00:00:00', strtotime('yesterday'));
						$weekStart = date('Y-m-d', strtotime('monday this week'));

						// 修改查询语句，统计不同时间范围内的销量并获取最后下单时间
						$query = "SELECT ptname, 
                                SUM(CASE WHEN addtime >= '{$todayStart}' THEN 1 ELSE 0 END) AS today_count,
                                SUM(CASE WHEN addtime >= '{$yesterdayStart}' AND addtime < '{$todayStart}' THEN 1 ELSE 0 END) AS yesterday_count,
                                SUM(CASE WHEN addtime >= '{$weekStart}' THEN 1 ELSE 0 END) AS week_count,
                                SUM(CASE WHEN MONTH(addtime) = MONTH(CURDATE()) AND YEAR(addtime) = YEAR(CURDATE()) THEN 1 ELSE 0 END) AS month_count,
                                COUNT(*) AS total_count,
                                MAX(addtime) AS latest_order_time 
                                FROM qingka_wangke_order 
                                GROUP BY ptname 
                                ORDER BY total_count DESC";

						$result = $DB->query($query);

						while ($row = $DB->fetch($result)) {
							$formatted_time = $row['latest_order_time'] ? date('Y-m-d H:i:s', strtotime($row['latest_order_time'])) : '无订单';

							echo '<tr>
                                    <td style="text-align: center;">' . htmlspecialchars($row['ptname']) . '</td>
                                    <td style="text-align: center;">' . $row['today_count'] . '</td>
                                    <td style="text-align: center;">' . $row['yesterday_count'] . '</td>
                                    <td style="text-align: center;">' . $row['week_count'] . '</td>
                                    <td style="text-align: center;">' . $row['month_count'] . '</td>
                                    <td style="text-align: center;">' . $row['total_count'] . '</td>
                                    <td style="text-align: center;">' . $formatted_time . '</td>
                                  </tr>';
						}
						?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>