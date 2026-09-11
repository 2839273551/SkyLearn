# 网课管理中心前端

这是替换原 `/index` 后台的唯一新版前端源码，基于 SoybeanAdmin、Vue 3、Vite、TypeScript 和 Naive UI。

## 本地命令

```powershell
pnpm install --frozen-lockfile
pnpm typecheck
pnpm build
```

- 开发模式默认把接口代理到 `.env.test` 的 `http://127.0.0.1`。
- 生产构建基准路径为 `/index/`，使用 history 路由并保留原页面地址；Nginx 仅对 `/index/` 配置 SPA fallback。
- 统一后端入口为 `/admin-api/v1/index.php?action=...`。

## 当前接入状态

- 已接入：Cookie 登录、退出、当前用户、权限角色、工作台统计、公告、完整查课下单链路、订单只读列表和个人资料。
- 已完成设置中心全部 14 个页面的全功能迁移与现代重构：
  1. 系统配置：`/index/webset`（系统设置）、`/index/gglist`（公告列表）、`/index/webmsg`（系统信息）、`/index/zzbz`（站长帮助）
  2. 商品货源：`/index/class`（网课设置）、`/index/fenlei`（分类设置）、`/index/huoyuan`（接口配置）、`/index/yjdj`（一键对接）
  3. 分销定价：`/index/dengji`（等级设置）、`/index/mijia`（密价设置）
  4. 资金财务：`/index/paylist`（支付订单）、`/index/guanx`（充值卡密）
  5. 数据报表：`/index/data`（今日数据）、`/index/ddtj`（货源统计）
- 已完成“我的信息”全部 14 个页面的全功能迁移与现代重构：
  1. 个人与团队：`/index/userinfo`（我的资料）、`/index/userlist`（代理管理与充值调费率）
  2. 商品行情与排行榜：`/index/atest`（最新上架8天内）、`/index/atesa`（下架专区）、`/index/rd`（热度排行）、`/index/pchangelist`（价格变动记录）
  3. 订单与对账：`/index/dingdan`（可用项目公示）、`/index/kcid`（课程ID对比）
  4. 财务充值：`/index/pay`（卡密充值）、`/index/charge`（在线充值）
  5. 技术与支持：`/index/docking`（串货对接API文档）、`/index/log`（操作与资金日志）、`/index/help`（必看说明手册）
- 后端 `/admin-api/v1/index.php` 已全部实现对应接口，包含全量数据校验、防注入与角色权限拦截。
- `/index/add` 已接入分类、平台与价格、收藏、单条/批量信息、AI矫正、查课扣费、课程选择、全选、免费次数、重复订单判断及提交扣费。
- `/index/webset` 已接入原系统设置的八个分组和完整保存链路；支付 KEY 不回显，空值不会覆盖原配置。
- 签到免费下单开关位于系统设置的课程配置，配置键为 `mfxdkg`，未配置时默认关闭。关闭时停止签到赠送免费次数，隐藏可用次数，所有下单入口按正常价格结算；已有免费次数和 `mfxd` 课程列表保留，签到余额奖励仍沿用原有规则。
- 导航沿用旧程序的主页、设置、学习中心、订单汇总和我的信息分组，已迁移页面保持原二级名称、排列顺序和 `/index/<原页面名>` 地址；设置分组仅对超级管理员显示。
- 已部署到 `https://sk.yunxnet.cn/index/`：
  - 2026-09-07 上传菜单分组与签到免费下单开关；
  - 2026-09-11 完成个人中心（`/index/userinfo`）全功能重构上线与统一接口实现；支持专属推广邀请码与带参推广注册链接一键复制、下级默认费率动态调整、API 接口密钥全生命周期管理（开通/重置/显隐/复制）、微信 PushPlus 推送绑定、个人资料与密码修改；同步重构独立改密页（`/index/passwd`）；优化全站 QQ 头像自动获取与展示机制（覆盖个人中心、顶部导航栏及代理列表）；优化登录二次验证交互，默认表单仅保留账号密码，仅在检测到超级管理员登录时弹出二次验证弹窗；同步将默认管理员账号更新为 2839273551。
  - 2026-09-09 完成设置中心 Sprint 1 核心闭环四大页面上线（`/index/fenlei`、`/index/huoyuan`、`/index/class`、`/index/yjdj`），并同步更新 `/admin-api/v1/index.php` 后端统一接口；优化设置菜单为二级结构（系统配置、商品货源、分销定价、资金财务、数据报表 5 大子分组）；优化主页与学习中心取消默认展开，仅按当前访问路径动态高亮展开；移除所有静态硬编码“Soybean 管理系统”，系统 Logo、页脚、浏览器标题、登录页等全站名称全面受系统设置中“站点名字”（`sitename`）动态控制，支持实时热更新；重构“我的信息”为 4 大子分组（账户与代理、充值方式、行情与价格、项目与对接）；全面打通系统设置中的 4 个功能开关至前台全站（防伪水印、订单公告、本周充值排行榜、工作台每日签到）；部署前服务器备份存档于 `/www/backups/sk.yunxnet.cn/20260909T193402-sprint1-settings/before`。

## 约束

- 浏览器不保存真实登录令牌，身份继续由服务端 `admin_token` HttpOnly Cookie 维护。
- 普通代理只可读取自己的订单；管理员页面同时依赖前端角色过滤和服务端权限校验。
- 货源凭据、支付配置和其他敏感字段不得返回前端。
