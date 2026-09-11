<?php




function validateAndGetHuoyuan($hid) {
    global $DB;
    if (empty($hid)) {
        exit(json_encode(['code' => -1, 'msg' => '货源ID不能为空']));
    }
    $huoyuan = $DB->get_row("SELECT * FROM qingka_wangke_huoyuan WHERE hid='$hid' AND status=1 LIMIT 1");
    if (!$huoyuan) {
        exit(json_encode(['code' => -1, 'msg' => '货源不存在或已禁用']));
    }
    return $huoyuan;
}



// 货源API调用函数
function callHuoyuanAPI($huoyuan, $action = 'getclass') {
    $base_url = $huoyuan['url'];
    // 确保URL格式正确
    if (!preg_match('/^https?:\/\//', $base_url)) {
        $base_url = 'http://' . $base_url;
    }
    
    // 准备认证数据
    $auth_data = [
        'uid' => $huoyuan['user'],
        'key' => $huoyuan['pass']
    ];
    
    // 构建API URL
    $api_url = rtrim($base_url, '/') . '/api.php?act=' . $action;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($auth_data));
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $effective_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    
    // 如果请求成功，尝试解析JSON
    if ($http_code == 200 && !$error) {
        $result = json_decode($response, true);
        if ($result && isset($result['code'])) {
            // 返回成功的结果
            return [
                'success' => true,
                'data' => $result,
                'api_url' => $api_url,
                'effective_url' => $effective_url
            ];
        }
    }
    
    // 如果失败，返回错误信息
    return [
        'success' => false,
        'error' => [
            'http_code' => $http_code,
            'curl_error' => $error,
            'api_url' => $api_url,
            'effective_url' => $effective_url,
            'response_preview' => substr($response, 0, 200)
        ]
    ];
}




function ApiFail($msg) {
    jsonReturn(0, $msg);
}

function ApiSuccess($msg, $data = null) {
    $response = array("code" => 1, "msg" => $msg);
    if ($data !== null) {
        $response['data'] = $data;
    }
    return exit(json_encode($response));
}
function encrypt($string, $key) {
    $iv = random_bytes(16); // Generate a new random IV each time
    $encrypted = openssl_encrypt($string, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($iv . $encrypted); // Concatenate IV and encrypted data
}

function decrypt($encryptedString, $key) {
    $encryptedData = base64_decode($encryptedString);
    $iv = substr($encryptedData, 0, 16); // Extract IV from encrypted data
    $encryptedPayload = substr($encryptedData, 16);
    $decrypted = openssl_decrypt($encryptedPayload, 'aes-256-cbc', $key, 0, $iv);
    return $decrypted;
}

function msg($msg){
    die('<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" />
                    <style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style>
                    <div style="padding: 24px 48px;"> <h1>:) </h1>
                    <h3>'.$msg.'</h3>');
}

function curl_request($url, $post = '', $referer = '', $cookie = '', $returnCookie = 0, $ua = 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:43.0) Gecko/20100101 Firefox/43.0', $timeout = 10) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_USERAGENT, $ua);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout); // 设置超时时间
    curl_setopt($curl, CURLOPT_REFERER, $referer);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    $httpheader[] = "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8";
    $httpheader[] = "Accept-Encoding:gzip, deflate";
    $httpheader[] = "Accept-Language:zh-CN,zh;q=0.9";
    $httpheader[] = "Connection:close";
    curl_setopt($curl, CURLOPT_HTTPHEADER, $httpheader);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    if ($post) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    if ($cookie) {
        curl_setopt($curl, CURLOPT_COOKIE, $cookie);
    }
    curl_setopt($curl, CURLOPT_HEADER, $returnCookie);
    curl_setopt($curl, CURLOPT_ENCODING, "gzip");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($curl);
    if (curl_errno($curl)) {
        return curl_error($curl);
    }
    curl_close($curl);
    if ($returnCookie) {
        list($header, $body) = explode("\r\n\r\n", $data, 2);
        preg_match_all("/Set\-Cookie:([^;]*);/", $header, $matches);
        $info['cookie'] = substr($matches[1][1], 1);
        $info['content'] = $body;
        return $info;
    } else {
        return $data;
    }
}


function jsonReturn($code, $msg)
{
	$data = array("code" => $code, "msg" => $msg);
	return exit(json_encode($data));
}




function daddslashes($string, $force = 0, $strip = FALSE)
{
	!defined("MAGIC_QUOTES_GPC") && define("MAGIC_QUOTES_GPC", get_magic_quotes_gpc());
	if (!MAGIC_QUOTES_GPC || $force) {
		if (is_array($string)) {
			foreach ($string as $key => $val) {
				$string[$key] = daddslashes($val, $force, $strip);
			}
		} else {
			$string = addslashes($strip ? stripslashes($string) : $string);
		}
	}
	return $string;
}












function httpRequest($method, $url, $data = [], $headers = [], $isJSON = false)
{
  
  $ch = curl_init();

  
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

  
  if (strtoupper($method) === 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    if ($isJSON) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    } else {
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
  } else {
    
    if (!empty($data)) {
      $url = $url . '?' . http_build_query($data);
      curl_setopt($ch, CURLOPT_URL, $url);
    }
  }

  
  if ($isJSON) {
    $headersArray = ['Content-Type: application/json'];
  } else {
    $headersArray = ['Content-Type: application/x-www-form-urlencoded'];
  }
  if (!empty($headers)) {
    $headersArray = array_merge($headersArray, $headers);
  }
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headersArray);

  
  $response = curl_exec($ch);

  
  if ($response === false) {
    echo '请求错误: ' . curl_error($ch);
  }

  
  curl_close($ch);

  return $response;
}





function real_ip()
{
	$ip = $_SERVER["REMOTE_ADDR"];
	if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) && preg_match_all("#\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}#s", $_SERVER["HTTP_X_FORWARDED_FOR"], $matches)) {
		foreach ($matches[0] as $xip) {
			if (!preg_match("#^(10|172\\.16|192\\.168)\\.#", $xip)) {
				$ip = $xip;
				break;
			}
		}
	} elseif (isset($_SERVER["HTTP_CLIENT_IP"]) && preg_match("/^([0-9]{1,3}\\.){3}[0-9]{1,3}\$/", $_SERVER["HTTP_CLIENT_IP"])) {
		$ip = $_SERVER["HTTP_CLIENT_IP"];
	} elseif (isset($_SERVER["HTTP_CF_CONNECTING_IP"]) && preg_match("/^([0-9]{1,3}\\.){3}[0-9]{1,3}\$/", $_SERVER["HTTP_CF_CONNECTING_IP"])) {
		$ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
	} elseif (isset($_SERVER["HTTP_X_REAL_IP"]) && preg_match("/^([0-9]{1,3}\\.){3}[0-9]{1,3}\$/", $_SERVER["HTTP_X_REAL_IP"])) {
		$ip = $_SERVER["HTTP_X_REAL_IP"];
	}
	return $ip;
}

function get_ip_city($ip)
{
	$url = "https://ip.taobao.com/outGetIpInfo?accessKey=alibaba-inc&ip=";
	@($data = file_get_contents($url . $ip));
	$arr = json_decode($data, true);
	if (array_key_exists("code", $arr) && $arr["code"] == 0) {
		if ($arr["data"]["city"]) {
			$location="{$arr['data']['country']} {$arr['data']['region']} {$arr['data']['city']} {$arr['data']['county']} {$arr['data']['isp']}";
		} else {
			$location = $arr["data"]["region"];
		}
	}
	if ($location) {
		return $location;
	} else {
		return false;
	}
}
function getSubstr($str, $leftStr, $rightStr)
{
	$left = strpos($str, $leftStr);
	$right = strpos($str, $rightStr, $left);
	if ($left < 0 or $right < $left) {
		return '';
	}
	return substr($str, $left + strlen($leftStr), $right - $left - strlen($leftStr));
}
function authcode($string, $operation = "DECODE", $key = '', $expiry = 0)
{
	$ckey_length = 4;
	$key = md5($key ? $key : ENCRYPT_KEY);
	$keya = md5(substr($key, 0, 16));
	$keyb = md5(substr($key, 16, 16));
	$keyc = $ckey_length ? $operation == "DECODE" ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length) : '';
	$cryptkey = $keya . md5($keya . $keyc);
	$key_length = strlen($cryptkey);
	$string = $operation == "DECODE" ? base64_decode(substr($string, $ckey_length)) : sprintf("%010d", $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
	$string_length = strlen($string);
	$result = '';
	$box = range(0, 255);
	$rndkey = array();
	for ($i = 0; $i <= 255; $i++) {
		$rndkey[$i] = ord($cryptkey[$i % $key_length]);
	}
	$j = $i = 0;
	while ($i < 256) {
		$j = ($j + $box[$i] + $rndkey[$i]) % 256;
		$tmp = $box[$i];
		$box[$i] = $box[$j];
		$box[$j] = $tmp;
		$i++;
	}
	$a = $j = $i = 0;
	while ($i < $string_length) {
		$a = ($a + 1) % 256;
		$j = ($j + $box[$a]) % 256;
		$tmp = $box[$a];
		$box[$a] = $box[$j];
		$box[$j] = $tmp;
		$result .= chr(ord($string[$i]) ^ $box[($box[$a] + $box[$j]) % 256]);
		$i++;
	}
	if ($operation == "DECODE") {
		if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
			return substr($result, 26);
		} else {
			return '';
		}
	} else {
		return $keyc . str_replace("=", '', base64_encode($result));
	}
}
function getNonceStr($code)
{
	for ($i = 0; $i > 10; $i++) {
		$code .= mt_rand(1000);
	}
	$nonceStrTemp = md5($code);
	$nonce_str = mb_substr($nonceStrTemp, 5, 37);
	return $nonce_str;
}
function random($length, $numeric = 0)
{
	$seed = base_convert(md5(microtime() . $_SERVER["DOCUMENT_ROOT"]), 16, $numeric ? 10 : 35);
	$seed = $numeric ? str_replace("0", '', $seed) . "012340567890" : $seed . "zZ" . strtoupper($seed);
	$hash = '';
	$max = strlen($seed) - 1;
	for ($i = 0; $i < $length; $i++) {
		$hash .= $seed[mt_rand(0, $max)];
	}
	return $hash;
}
function showmsg($content = "未知的异常", $type = 4, $back = false, $back_name = false)
{
	switch ($type) {
		case 1:
			$panel = "success";
			break;
		case 2:
			$panel = "info";
			break;
		case 3:
			$panel = "warning";
			break;
		case 4:
			$panel = "danger";
			break;
	}
	echo "<div class=\"container\" style=\"padding-top:70px;\"> <div class=\"col-xs-12 col-sm-10 col-lg-8 center-block\" style=\"float: none;\">";
	echo "<div class=\"panel panel-" . $panel . "\">\r\n      <div class=\"panel-heading\">\r\n        <h3 class=\"panel-title\">提示信息</h3>\r\n        </div>\r\n        <div class=\"panel-body\">";
	echo $content;
	if ($back) {
		if ($back_name) {
			echo "<hr/><a href=\"" . $back . "\"><< " . $back_name . "</a>";
		} else {
			echo "<hr/><a href=\"" . $back . "\"><< 返回列表</a>";
		}
		echo "<br/><a href=\"javascript:history.back(-1)\"><< 返回上一页</a>";
	} else {
		echo "<hr/><a href=\"javascript:history.back(-1)\"><< 返回上一页</a>";
	}
	echo "</div>\r\n    </div>";
}
function checkRefererHost()
{
	if (!$_SERVER["HTTP_REFERER"]) {
		return false;
	}
	$url_arr = parse_url($_SERVER["HTTP_REFERER"]);
	$http_host = $_SERVER["HTTP_HOST"];
	if (strpos($http_host, ":")) {
		$http_host = substr($http_host, 0, strpos($http_host, ":"));
	}
	return $url_arr["host"] === $http_host;
}
function merge_spaces($string)
{
	return preg_replace("/\\s+/", " ", $string);
}
function alert($a, $wz = false)
{
	if ($wz == '') {
		$wz = $_SERVER["SCRIPT_NAME"];
	}
	exit("<script language='javascript'>layer.alert('{$a}',function(){window.location.href='{$wz}'});</script>");
}

function get_curl($url, $post = 0, $referer = 0, $cookie = 0, $header = 0, $ua = 0, $nobaody = 0)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	$httpheader[] = "Accept: */*";
	$httpheader[] = "Accept-Encoding: gzip,deflate,sdch";
	$httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
	$httpheader[] = "Connection: close";
	curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	if ($post) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}
	curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
	if ($header) {
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
	}
	if ($cookie) {
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	}
	if ($referer) {
		if ($referer == 1) {
			curl_setopt($ch, CURLOPT_REFERER, "http://m.qzone.com/infocenter?g_f=");
		} else {
			curl_setopt($ch, CURLOPT_REFERER, $referer);
		}
	}
	if ($ua) {
		curl_setopt($ch, CURLOPT_USERAGENT, $ua);
	} else {
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36");
	}
	if ($nobaody) {
		curl_setopt($ch, CURLOPT_NOBODY, 1);
	}
	curl_setopt($ch, CURLOPT_ENCODING, "gzip");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$ret = curl_exec($ch);
	curl_close($ch);
	return $ret;
}
function get_url($url, $post = false, $cookie = false, $header = false)
{
	$ch = curl_init();
	if ($header) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	} else {
		curl_setopt($ch, CURLOPT_HEADER, 0);
	}
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.62 Safari/537.36");
	if ($post) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
	}
	if ($cookie) {
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	}
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}
function get_url2($url, $post = false, $cookie = false, $header = false)
{
	$ch = curl_init();
	if ($header) {
		curl_setopt($ch, CURLOPT_HEADER, 1);
	} else {
		curl_setopt($ch, CURLOPT_HEADER, 0);
	}
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, "application/json;charset=UTF-8 user-agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.25 Safari/537.36 Core/1.70.3741.400 QQBrowser/10.5.3863.400");
	if ($post) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}
	if ($cookie) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("content-type:application/json;charset=UTF-8"));
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	}
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}
function get_url3($url, $post = false, $cookie = false, $header = false)
{
	$ch = curl_init();
	if ($header) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	} else {
		curl_setopt($ch, CURLOPT_HEADER, 0);
	}
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, $header);
	if ($post) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}
	if ($cookie) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("content-type:application/json;charset=UTF-8"));
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	}
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}
function wlog($uid, $type, $text, $money)
{
	global $DB;
	global $clientip;
	$a = $DB->get_row("select money from qingka_wangke_user where uid='{$uid}' ");
	$smoney = $a["money"];
	$DB->query("insert into qingka_wangke_log (uid,type,text,money,smoney,ip) values ('{$uid}','{$type}','{$text}','{$money}','{$smoney}','{$clientip}') ");
}
function qcookie()
{
	global $DB;
	$a = $DB->query("select * from qingka_wangke_huoyuan where status=1 ");
	while ($b = $DB->fetch($a)) {
		loginWk($b["hid"]);
	}
}

function get_cookie($url, $data)
{
    $ch = curl_init();curl_setopt($ch, CURLOPT_URL, $url);  
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');  curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    //curl_setopt($curl, CURLOPT_AUTOREFERER, 1);    
    curl_setopt($ch, CURLOPT_POST, 1);             
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);    curl_setopt($ch, CURLOPT_TIMEOUT, 30);         curl_setopt($ch, CURLOPT_HEADER, 1);         
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
    $output = curl_exec($ch); curl_close($ch);
  $cki =   (preg_match_all('|Set-Cookie: (.*);|U', $output));
     if($cki == 1 ){
         preg_match_all('|Set-Cookie: (.*);|U', $output, $arr); 
     }else{
         preg_match_all('|set-cookie: (.*);|U', $output, $arr);  
     }
    $cookies = implode(';', $arr[1]);
 return $cookies;
}
function post($url, $data, $header = [])
{
      
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); curl_setopt($ch, CURLOPT_USERAGENT, isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/68.0.3440.106 Safari/537.36');
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}


include_once('ckjk.php');
include_once('xdjk.php');
include_once('gmjk.php');
include_once('ztjk.php');
include_once('jdjk.php');
include_once('bsjk.php');
include_once('logjk.php');
include_once('zhjk.php');




function getServerIp()
{
   $url  = 'https://www.bt.cn/Api/getIpAddress';
   $url2 = 'http://members.3322.org/dyndns/getip';
   if ($data = file_get_contents($url)) {
       return $data;
   } else {
       $data = file_get_contents($url2);
       return $data;
   }
}

define('CONFIG_KEY', '8848');


?>