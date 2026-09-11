# SkyLearn

SkyLearn 是一款集网课代理、多货源聚合分发、订单自动化流转与现代化管理后台于一体的综合业务管理系统。

## 架构概览

- **统一管理后台 (`admin-web/`)**：基于 SoybeanAdmin、Vue 3、Vite、TypeScript 与 Naive UI 构建的现代单页管理系统。
- **统一接口服务 (`admin-api/v1/`)**：为现代化前端提供标准化 RESTful 风格的 JSON API，集成 CSRF 防御、角色权限拦截与安全审计。
- **业务中枢与适配 (`Checkorder/`, `apisub.php`)**：对接多平台货源，处理自动查课、订单提交、进度回传、自动补刷、密码修改等自动化流转。
- **异步队列与定时任务 (`redis/`, `cron/`)**：基于 Redis 的高并发异步订单处理与状态轮询。
- **支付对接 (`epay/`)**：支持易支付等通用支付回调。

## 快速上手

### 1. 运行环境要求
- PHP 7.4+
- MySQL 5.7+
- Redis 6.0+
- Nginx / Apache
- Node.js 18+ & pnpm (仅前端开发与构建时需要)

### 2. 数据库初始化
- 导入数据库结构及预置数据：`xm.sql` 或通过 `install/` 安装向导进行初始化。
- 在 `confing/config.php` 及 `xm/config.php` 中配置数据库连接。

### 3. 前端编译 (admin-web)
```bash
cd admin-web
pnpm install
pnpm build
```

## 许可证
MIT License
