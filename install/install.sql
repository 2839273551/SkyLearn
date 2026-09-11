-- MySQL dump 10.13  Distrib 5.7.44, for Linux (x86_64)
--
-- Host: localhost    Database: 2
-- ------------------------------------------------------
-- Server version	5.7.44-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `qingka_wangke_class`
--

DROP TABLE IF EXISTS `qingka_wangke_class`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qingka_wangke_class` (
  `cid` int(11) NOT NULL AUTO_INCREMENT,
  `sort` int(11) NOT NULL DEFAULT '10',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '网课平台名字',
  `getnoun` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '查询参数',
  `noun` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '对接参数',
  `price` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '定价',
  `queryplat` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '查询平台',
  `docking` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '对接平台',
  `yunsuan` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '*' COMMENT '代理费率运算',
  `content` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '说明',
  `addtime` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '添加时间',
  `status` int(11) NOT NULL DEFAULT '1' COMMENT '状态0为下架。1为上架',
  `fenlei` varchar(11) COLLATE utf8_unicode_ci NOT NULL COMMENT '分类',
  `ckkf` varchar(11) COLLATE utf8_unicode_ci NOT NULL COMMENT '查课扣费',
  `kcid` varchar(11) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `vipprice` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '内部价',
  PRIMARY KEY (`cid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qingka_wangke_class`
--

LOCK TABLES `qingka_wangke_class` WRITE;
/*!40000 ALTER TABLE `qingka_wangke_class` DISABLE KEYS */;
/*!40000 ALTER TABLE `qingka_wangke_class` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qingka_wangke_config`
--

DROP TABLE IF EXISTS `qingka_wangke_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qingka_wangke_config` (
  `v` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `k` text COLLATE utf8_unicode_ci NOT NULL,
  UNIQUE KEY `v` (`v`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qingka_wangke_config`
--

LOCK TABLES `qingka_wangke_config` WRITE;
/*!40000 ALTER TABLE `qingka_wangke_config` DISABLE KEYS */;
INSERT INTO `qingka_wangke_config` VALUES ('akcookie','7321sPn6n+Yt9tGs1wy7f2ULOKbENP2W/J83w50jYbpDpQEXjkGRJnZOlXPY7XeOX5zCSU6vfhOLJSoKLMeWQ7cv9ghbEsFowYoCzQ'),('api_proportion','20'),('ckkg','1'),('czph','0'),('ddgg','进度条是辅助的，具体以上号查看为主，频繁补刷 封号'),('ddggkg','0'),('description','SkyLearn'),('dklcookie','d1abUN9efprWp3E93jrzR27IaTDLsmnV7AGLOb+rPKLrml1YDKRVBgXW0JxsfktUYm/Ym/Sk9J4eJiLfEdoPFXgKRuy/vtoV4YXYuRkarpydLsoeMgp93qAWuTWF9vRdk4xdew4QGgPIt/LzbMnUsDOHq/u5HGrnMhU8wbD35T/47sGC5cCVzGlGK565qMhH3kUzazhgiEoEpDJNAOFXttowDLKM+6G1iAApLyUNDxP7rluq383FyGxO3tcYR4VVuTEJA1V0LTNTOzhq8TJXl0l/hGE6JfDmLkDo4piWok2lnG5VTzYdMf6AJYSHwO+W4P2rAxgd3augn/kkJUfvxRiWZWPoddS4n0n1kRpIJrBNgTWuE3U9EQqxIMMwMWLI3IZvP0NZE/Nl0yqyFg'),('epay_api',''),('epay_key',''),('epay_pid',''),('flkg','1'),('fllx','2'),('flmoney','0.06'),('is_alipay','1'),('is_qqpay','0'),('is_wxpay','1'),('keywords','SkyLearn'),('login_apiurl',''),('login_appid',''),('login_appkey',''),('logo','/logo.png'),('lwid','15'),('lwxd','1'),('mfxd','2'),('nanatoken','14cen/DXBsnMXkKPE50giKP/KmWGgIwn/B2sdw9z+b7zL2riFzG2mvyb04YP/xdD5w5h8uvXIKwjCo7AIF3QgzvCoiOdJfK+a9IdjpFcb2mSpzqGg9jRrEeFOomuokkA0F971RhK'),('notice',''),('qdkg','1'),('recharge_kg','0'),('sitename','SkyLearn'),('sjqykg','0'),('sqzt','1'),('sykg','0'),('tcgonggao','欢迎来到元宇宙！本平台项目均为对接 来源网络，仅供海外华人使用，禁止在中国境内访问。\n有任何侵犯您权利之处，'),('user_gjmoney','3'),('user_htkh','1'),('user_ktmoney','5'),('user_yqzc','1'),('xdgg','福利类目 全部 0.1'),('xdggkg','0'),('xdkg','1'),('yjxj','1'),('yqjl','3'),('yqsq','1'),('yqsx','5'),('zddy','0.01'),('zdpay','10'),('zdxd','0.01'),('zm','4,5'),('zsgonggao',''),('zxczkg','1'),('zzqq','1111'),('zzvx','121212');
/*!40000 ALTER TABLE `qingka_wangke_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qingka_wangke_czk`
--

DROP TABLE IF EXISTS `qingka_wangke_czk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qingka_wangke_czk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `card` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `money` decimal(10,2) NOT NULL,
  `addtime` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `endtime` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `usetime` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `uid` varchar(11) COLLATE utf8_unicode_ci NOT NULL,
  `batch_id` int(255) NOT NULL COMMENT '卡密批次',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=126 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qingka_wangke_czk`
--

LOCK TABLES `qingka_wangke_czk` WRITE;
/*!40000 ALTER TABLE `qingka_wangke_czk` DISABLE KEYS */;
/*!40000 ALTER TABLE `qingka_wangke_czk` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qingka_wangke_dengji`
--

DROP TABLE IF EXISTS `qingka_wangke_dengji`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qingka_wangke_dengji` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sort` varchar(11) NOT NULL,
  `name` varchar(11) NOT NULL,
  `rate` decimal(10,2) NOT NULL,
  `money` decimal(10,2) NOT NULL,
  `addkf` varchar(11) NOT NULL,
  `gjkf` varchar(11) NOT NULL,
  `status` varchar(11) NOT NULL,
  `time` varchar(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qingka_wangke_dengji`
--

LOCK TABLES `qingka_wangke_dengji` WRITE;
/*!40000 ALTER TABLE `qingka_wangke_dengji` DISABLE KEYS */;
INSERT INTO `qingka_wangke_dengji` VALUES (4,'2','二级代理',0.25,200.00,'1','1','1','2023-10-13 '),(5,'2','三级代理',0.30,100.00,'1','1','1','2023-10-13 '),(6,'2','四级代理',0.50,50.00,'1','1','0','2023-10-13 '),(7,'2','五级代理',0.60,8.00,'1','0','0','2023-10-13 '),(8,'2','六级代理',0.70,88.00,'1','1','0','2023-10-13 '),(9,'2','七级代理',0.80,8.00,'1','1','0','2023-10-13 '),(13,'1','禁止开户',0.20,9000.00,'1','1','1','2024-06-07 ');
/*!40000 ALTER TABLE `qingka_wangke_dengji` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qingka_wangke_fenlei`
--

DROP TABLE IF EXISTS `qingka_wangke_fenlei`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qingka_wangke_fenlei` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sort` varchar(11) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `name` varchar(11) COLLATE utf8_unicode_ci NOT NULL,
  `status` varchar(11) COLLATE utf8_unicode_ci NOT NULL,
  `time` varchar(11) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qingka_wangke_fenlei`
--

LOCK TABLES `qingka_wangke_fenlei` WRITE;
/*!40000 ALTER TABLE `qingka_wangke_fenlei` DISABLE KEYS */;
INSERT INTO `qingka_wangke_fenlei` VALUES (1,'1','测试','1','2024-10-13 ');
/*!40000 ALTER TABLE `qingka_wangke_fenlei` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qingka_wangke_gongdan`
--

DROP TABLE IF EXISTS `qingka_wangke_gongdan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qingka_wangke_gongdan` (
  `gid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `oid` int(11) NOT NULL COMMENT '订单ID',
  `region` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '工单类型',
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '暂无标题' COMMENT '工单标题',
  `content` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '工单内容',
  `answer` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '工单回复',
  `state` varchar(11) COLLATE utf8_unicode_ci NOT NULL COMMENT '工单状态',
  `addtime` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '添加时间',
  `last_responder_uid` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '最后一次回复工单的用户',
  PRIMARY KEY (`gid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qingka_wangke_gongdan`
--

LOCK TABLES `qingka_wangke_gongdan` WRITE;
/*!40000 ALTER TABLE `qingka_wangke_gongdan` DISABLE KEYS */;
/*!40000 ALTER TABLE `qingka_wangke_gongdan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qingka_wangke_gonggao`
--

DROP TABLE IF EXISTS `qingka_wangke_gonggao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qingka_wangke_gonggao` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `content` text NOT NULL,
  `time` text NOT NULL,
  `uid` int(11) NOT NULL,
  `status` varchar(11) NOT NULL COMMENT '状态',
  `zhiding` varchar(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qingka_wangke_gonggao`
--

LOCK TABLES `qingka_wangke_gonggao` WRITE;
/*!40000 ALTER TABLE `qingka_wangke_gonggao` DISABLE KEYS */;
INSERT INTO `qingka_wangke_gonggao` VALUES (16,'公告','<div class=\"layui-timeline\">\n  <div class=\"layui-timeline-item\">\n    <i class=\"layui-icon layui-timeline-axis layui-icon-fire\"></i>\n    <div class=\"layui-timeline-content layui-text\">\n      <div class=\"layui-timeline-title\">微信扫码进入通知频道<a href=\"/index//smgz\" target=\"_blank\">点我直达</a></div>\n    </div>\n  </div>\n  <div class=\"layui-timeline-item\">\n    <i class=\"layui-icon layui-timeline-axis layui-icon-fire\"></i>\n    <div class=\"layui-timeline-content layui-text\">\n      <div class=\"layui-timeline-title\">电报客服【需要翻墙】<a href=\"https://t.me/fw8689\" target=\"_blank\">点我直达</a></div>\n    </div>\n  </div>\n  <div class=\"layui-timeline-item\">\n    <i class=\"layui-icon layui-timeline-axis layui-icon-face-smile\"></i>\n    <div class=\"layui-timeline-content layui-text\">\n      <div class=\"layui-timeline-title\">平台状态永远只是辅助工具。具体课刷没刷完，自行计算好时间后，再亲自上号查询后核实。并不是无状态更新，或者状态不动，就是不走单！</div>\n    </div>\n  </div>\n  <div class=\"layui-timeline-item\">\n    <i class=\"layui-icon layui-timeline-axis layui-icon-fire\"></i>\n    <div class=\"layui-timeline-content layui-text\">\n      <div class=\"layui-timeline-title\">平台全对接无自营，非本站问题，不退不换，谨慎充值，下单前请先测试</div>\n    </div>\n  </div>\n  <div class=\"layui-timeline-item\">\n    <i class=\"layui-icon layui-timeline-axis layui-icon-circle\"></i>\n    <div class=\"layui-timeline-content layui-text\">\n      <div class=\"layui-timeline-title\">上架更多渠道，方便各位下单！</div>\n    </div>\n  </div>\n  <div class=\"layui-timeline-item\">\n    <i class=\"layui-icon layui-anim layui-anim-rotate layui-anim-loop layui-timeline-axis\"></i>\n    <div class=\"layui-timeline-content layui-text\">\n      <div class=\"layui-timeline-title\">支持一键对接，联系客服索要快速对接地址</div>\n    </div>\n  </div>\n</div>\n','2024-04-25 13:15:38',1,'1','0'),(19,'关于订单','欢迎使用本系统-本平台利润微薄不提供零售的售后服务=0售后（平台能提供的是把您的问题反馈给供应商 能否成功处理不保证谢谢 不支持废话拉扯）','2024-05-23 02:48:27',1,'1','1');
/*!40000 ALTER TABLE `qingka_wangke_gonggao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qingka_wangke_huodong`
--

DROP TABLE IF EXISTS `qingka_wangke_huodong`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qingka_wangke_huodong` (
  `hid` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '活动名字',
  `yaoqiu` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '要求',
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '1为邀人活动 2为订单活动',
  `num` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '要求数量',
  `money` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '奖励',
  `addtime` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '活动开始时间',
  `endtime` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '活动结束时间',
  `status_ok` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1' COMMENT '1为正常 2为结束',
  `status` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1' COMMENT '1为进行中  2为待领取 3为已完成',
  PRIMARY KEY (`hid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qingka_wangke_huodong`
--

LOCK TABLES `qingka_wangke_huodong` WRITE;
/*!40000 ALTER TABLE `qingka_wangke_huodong` DISABLE KEYS */;
/*!40000 ALTER TABLE `qingka_wangke_huodong` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qingka_wangke_huoyuan`
--

DROP TABLE IF EXISTS `qingka_wangke_huoyuan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qingka_wangke_huoyuan` (
  `hid` int(11) NOT NULL AUTO_INCREMENT,
  `pt` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '不带http 顶级',
  `user` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pass` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ip` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `cookie` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `money` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `status` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `addtime` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `endtime` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`hid`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qingka_wangke_huoyuan`
--

LOCK TABLES `qingka_wangke_huoyuan` WRITE;
/*!40000 ALTER TABLE `qingka_wangke_huoyuan` DISABLE KEYS */;
INSERT INTO `qingka_wangke_huoyuan` VALUES (102,'2xx','1111','','1','','','','','0','1','2024-10-13 12:04:14','2024-10-16 21:36:33');
/*!40000 ALTER TABLE `qingka_wangke_huoyuan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qingka_wangke_km`
--

DROP TABLE IF EXISTS `qingka_wangke_km`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qingka_wangke_km` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '卡密id',
  `content` varchar(255) NOT NULL COMMENT '卡密内容',
  `money` int(11) NOT NULL COMMENT '卡密金额',
  `status` int(11) DEFAULT NULL COMMENT '卡密状态',
  `uid` int(11) DEFAULT NULL COMMENT '使用者id',
  `addtime` varchar(255) DEFAULT NULL COMMENT '添加时间',
  `usedtime` varchar(255) DEFAULT NULL COMMENT '使用时间',
  `batch_id` int(255) NOT NULL COMMENT '卡密批次',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=168 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qingka_wangke_km`
--

LOCK TABLES `qingka_wangke_km` WRITE;
/*!40000 ALTER TABLE `qingka_wangke_km` DISABLE KEYS */;
/*!40000 ALTER TABLE `qingka_wangke_km` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qingka_wangke_log`
--

DROP TABLE IF EXISTS `qingka_wangke_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qingka_wangke_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `text` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `money` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `smoney` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ip` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `addtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qingka_wangke_log`
--

LOCK TABLES `qingka_wangke_log` WRITE;
/*!40000 ALTER TABLE `qingka_wangke_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `qingka_wangke_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qingka_wangke_mijia`
--

DROP TABLE IF EXISTS `qingka_wangke_mijia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qingka_wangke_mijia` (
  `mid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `cid` int(11) NOT NULL,
  `mode` int(11) NOT NULL COMMENT '0.价格的基础上扣除 1.倍数的基础上扣除 2.直接定价',
  `price` varchar(100) NOT NULL,
  `addtime` varchar(100) NOT NULL,
  PRIMARY KEY (`mid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qingka_wangke_mijia`
--

LOCK TABLES `qingka_wangke_mijia` WRITE;
/*!40000 ALTER TABLE `qingka_wangke_mijia` DISABLE KEYS */;
/*!40000 ALTER TABLE `qingka_wangke_mijia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qingka_wangke_order`
--

DROP TABLE IF EXISTS `qingka_wangke_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qingka_wangke_order` (
  `oid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `cid` int(11) NOT NULL COMMENT '平台ID',
  `hid` int(11) NOT NULL COMMENT '接口ID',
  `yid` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '对接站ID',
  `ptname` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '平台名字',
  `school` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '学校',
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '姓名',
  `user` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '账号',
  `pass` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '密码',
  `phone` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '手机号',
  `kcid` text COLLATE utf8_unicode_ci NOT NULL COMMENT '课程ID',
  `kcname` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '课程名字',
  `courseStartTime` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '课程开始时间',
  `courseEndTime` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '课程结束时间',
  `examStartTime` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '考试开始时间',
  `examEndTime` text COLLATE utf8_unicode_ci NOT NULL COMMENT '考试结束时间',
  `chapterCount` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '总章数',
  `unfinishedChapterCount` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '剩余章数',
  `cookie` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'cookie',
  `fees` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '扣费',
  `noun` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '对接标识',
  `miaoshua` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' COMMENT '0不秒 1秒',
  `addtime` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '添加时间',
  `ip` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '下单ip',
  `dockstatus` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' COMMENT '对接状态 0待 1成  2失 3重复 4取消',
  `loginstatus` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `status` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '待处理',
  `process` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `bsnum` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' COMMENT '补刷次数',
  `remarks` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '备注',
  `dakatime` text COLLATE utf8_unicode_ci NOT NULL,
  `leixing` varchar(11) COLLATE utf8_unicode_ci NOT NULL,
  `detailed` text COLLATE utf8_unicode_ci NOT NULL,
  `dlip` text COLLATE utf8_unicode_ci NOT NULL,
  `docknum` int(1) NOT NULL DEFAULT '0',
  `finalupdate` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '最后更新',
  `region` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '随机地区',
  PRIMARY KEY (`oid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qingka_wangke_order`
--

LOCK TABLES `qingka_wangke_order` WRITE;
/*!40000 ALTER TABLE `qingka_wangke_order` DISABLE KEYS */;
/*!40000 ALTER TABLE `qingka_wangke_order` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qingka_wangke_pay`
--

DROP TABLE IF EXISTS `qingka_wangke_pay`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qingka_wangke_pay` (
  `oid` int(11) NOT NULL AUTO_INCREMENT,
  `out_trade_no` varchar(64) NOT NULL,
  `trade_no` varchar(100) NOT NULL,
  `type` varchar(20) DEFAULT NULL,
  `uid` int(11) NOT NULL,
  `num` int(11) NOT NULL DEFAULT '1',
  `addtime` datetime DEFAULT NULL,
  `endtime` datetime DEFAULT NULL,
  `name` varchar(64) DEFAULT NULL,
  `money` varchar(32) DEFAULT NULL,
  `ip` varchar(20) DEFAULT NULL,
  `domain` varchar(64) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`oid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qingka_wangke_pay`
--

LOCK TABLES `qingka_wangke_pay` WRITE;
/*!40000 ALTER TABLE `qingka_wangke_pay` DISABLE KEYS */;
/*!40000 ALTER TABLE `qingka_wangke_pay` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `qingka_wangke_user`
--

DROP TABLE IF EXISTS `qingka_wangke_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qingka_wangke_user` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` int(11) NOT NULL,
  `user` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `pass` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `qq_openid` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'QQuid',
  `nickname` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'QQ昵称',
  `faceimg` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'QQ头像',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00',
  `zcz` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `addprice` decimal(10,2) NOT NULL DEFAULT '1.00' COMMENT '加价',
  `key` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `yqm` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '邀请码',
  `yqprice` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '邀请单价',
  `notice` text COLLATE utf8_unicode_ci NOT NULL,
  `addtime` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '添加时间',
  `endtime` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ip` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `grade` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `active` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '1',
  `vip` int(11) NOT NULL DEFAULT '0' COMMENT '是否为会员',
  `vipexpire` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '会员到期时间',
  `secret_price` decimal(10,2) DEFAULT NULL COMMENT '用户密价',
  `last_sign_in_date` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT '最后签到时间',
  `used_batches` text COLLATE utf8_unicode_ci NOT NULL COMMENT '福利卡密使用',
  `pushPlusToken` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'pushPlusToken',
  `daily_invites` int(11) DEFAULT '0' COMMENT '邀请人数',
  `freeadd` int(11) NOT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=1277 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `qingka_wangke_user`
--

LOCK TABLES `qingka_wangke_user` WRITE;
/*!40000 ALTER TABLE `qingka_wangke_user` DISABLE KEYS */;
INSERT INTO `qingka_wangke_user` VALUES (1,1,'admin','12345678','admin','','','',439.83,'14490.73',0.20,'0','0000','0.2','低调开户，低调宣传','','2024-10-16 23:59:43','54.248.149.129','','1',0,'2028-01-29',NULL,'2024-10-16','m,5,2,1,9,22,33','',5,1),(1275,1,'10086','123456','david','','','',1.00,'1',0.20,'0','','','','2024-10-12 19:13:57','2024-10-16 21:00:48','54.238.208.16','','1',0,NULL,NULL,'','',NULL,0,0),(1276,1,'657436675','657436675','657436675','','','',1.00,'1',0.20,'0','','','','2024-10-15 19:29:40','2024-10-15 19:31:23','34.81.61.88','','1',0,NULL,NULL,'','',NULL,0,0);
/*!40000 ALTER TABLE `qingka_wangke_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database '2'
--

--
-- Dumping routines for database '2'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2024-10-17  0:11:31

--
-- Table structure for table `qingka_wangke_docking_log`
--

DROP TABLE IF EXISTS `qingka_wangke_docking_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `qingka_wangke_docking_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `direction` varchar(16) NOT NULL DEFAULT 'in' COMMENT 'in:外部对接我, out:我对接上游',
  `action` varchar(64) NOT NULL DEFAULT '' COMMENT '接口动作名称',
  `caller` varchar(64) NOT NULL DEFAULT '' COMMENT '调用方(UID/用户名/系统)',
  `uid` int(11) NOT NULL DEFAULT '0' COMMENT '关联用户UID',
  `target` varchar(128) NOT NULL DEFAULT '' COMMENT '目标(接口路径或上游货源名称)',
  `method` varchar(16) NOT NULL DEFAULT 'POST' COMMENT '请求方式',
  `ip` varchar(64) NOT NULL DEFAULT '' COMMENT '调用方或目标IP',
  `params` mediumtext COMMENT '请求入参摘要(脱敏)',
  `response` mediumtext COMMENT '响应摘要(脱敏)',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1:成功, 0:失败',
  `cost_ms` int(11) NOT NULL DEFAULT '0' COMMENT '耗时毫秒',
  `bytes_in` int(11) NOT NULL DEFAULT '0' COMMENT '请求流量(字节)',
  `bytes_out` int(11) NOT NULL DEFAULT '0' COMMENT '响应流量(字节)',
  `traffic_total` int(11) NOT NULL DEFAULT '0' COMMENT '总流量(字节)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '调用时间',
  PRIMARY KEY (`id`),
  KEY `idx_direction_time` (`direction`,`created_at`),
  KEY `idx_uid_time` (`uid`,`created_at`),
  KEY `idx_action` (`action`),
  KEY `idx_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='对接接口与串货调用流水表';
/*!40101 SET character_set_client = @saved_cs_client */;
