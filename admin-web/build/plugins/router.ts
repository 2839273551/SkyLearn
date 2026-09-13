import type { RouteMeta } from 'vue-router';
import ElegantVueRouter from '@elegant-router/vue/vite';
import type { RouteKey } from '@elegant-router/types';

export function setupElegantRouter() {
  return ElegantVueRouter({
    layouts: {
      base: 'src/layouts/base-layout/index.vue',
      blank: 'src/layouts/blank-layout/index.vue'
    },
    routePathTransformer(routeName, routePath) {
      const key = routeName as RouteKey;

      if (key === 'login') {
        const modules: UnionKey.LoginModule[] = ['pwd-login', 'code-login', 'register', 'reset-pwd', 'bind-wechat'];

        const moduleReg = modules.join('|');

        return `/login/:module(${moduleReg})?`;
      }

      return routePath;
    },
    onRouteMetaGen(routeName) {
      const key = routeName as RouteKey;

      const constantRoutes: RouteKey[] = ['login', '403', '404', '500'];

      const routeMetaMap: Record<string, Partial<RouteMeta>> = {
        main: { title: '个人综合', icon: 'ph:squares-four', order: 1 },
        home: { title: '实时公告', icon: 'ph:megaphone', order: 2 },
        add: { title: '马上学习', icon: 'ph:shopping-cart-simple', order: 3 },
        addpl: { title: '批量学习', icon: 'ph:stack', order: 4 },
        addtj: { title: '无查提交', icon: 'ph:upload-simple', order: 5 },
        list: { title: '订单汇总', icon: 'ph:list-checks', order: 6 },
        userinfo: { title: '我的资料', icon: 'ph:user-circle', order: 7 },
        userlist: { title: '代理管理', icon: 'ph:users-three', order: 8 },
        atest: { title: '最新上架', icon: 'ph:arrow-up', order: 9 },
        atesa: { title: '下架专区', icon: 'ph:arrow-down', order: 10 },
        rd: { title: '热度排行', icon: 'ph:fire', order: 11 },
        kcid: { title: 'kcid对比', icon: 'ph:code', order: 12 },
        dingdan: { title: '可用项目', icon: 'ph:check-square', order: 13 },
        log: { title: '操作日志', icon: 'ph:note', order: 14 },
        help: { title: '必看说明', icon: 'ph:question', order: 15 },
        myprice: { title: '学习价格', icon: 'ph:currency-circle-dollar', order: 16 },
        docking: { title: '串货对接', icon: 'ph:plugs', order: 17 },
        pchangelist: { title: '价格变动', icon: 'ph:trend-up', order: 18 },
        pay: { title: '卡密充值', icon: 'ph:key', order: 19 },
        charge: { title: '在线充值', icon: 'ph:wallet', order: 20 },
        workorder: { title: '问题反馈', icon: 'ph:headset', order: 21 },
        chati: { title: '题库查询', icon: 'ph:read-cv-logo', order: 22 },
        class: { title: '网课设置', icon: 'ph:books', order: 30, roles: ['R_SUPER'] },
        huoyuan: { title: '接口配置', icon: 'ph:cloud-arrow-up', order: 31, roles: ['R_SUPER'] },
        webmsg: { title: '系统信息', icon: 'ph:info', order: 32, roles: ['R_SUPER'] },
        webset: { title: '系统设置', icon: 'ph:gear-six', order: 10, roles: ['R_SUPER'] },
        zzbz: { title: '站长帮助', icon: 'ph:lifebuoy', order: 33, roles: ['R_SUPER'] },
        gglist: { title: '公告列表', icon: 'ph:megaphone', order: 34, roles: ['R_SUPER'] },
        fenlei: { title: '分类设置', icon: 'ph:folders', order: 35, roles: ['R_SUPER'] },
        dengji: { title: '等级设置', icon: 'ph:stairs', order: 36, roles: ['R_SUPER'] },
        mijia: { title: '密价设置', icon: 'ph:tag', order: 37, roles: ['R_SUPER'] },
        ddtj: { title: '货源统计', icon: 'ph:chart-bar', order: 38, roles: ['R_SUPER'] },
        data: { title: '今日数据', icon: 'ph:calendar-check', order: 39, roles: ['R_SUPER'] },
        guanx: { title: '充值卡密', icon: 'ph:ticket', order: 40, roles: ['R_SUPER'] },
        paylist: { title: '支付订单', icon: 'ph:wallet', order: 41, roles: ['R_SUPER'] },
        yjdj: { title: '一键对接', icon: 'ph:link', order: 42, roles: ['R_SUPER'] },
        dockinglog: { title: '对接监控', icon: 'ph:plugs-connected', order: 23, roles: ['R_SUPER'] },
        scheduler: { title: '任务调度', icon: 'ph:clock-clockwise', order: 24, roles: ['R_SUPER'] },
        passwd: { title: '修改密码', icon: 'ph:lock-key', order: 50 },
        sjqy: { title: '上级迁移', icon: 'ph:arrows-merge', order: 51 },
        usernotice: { title: '站内通知', icon: 'ph:bell-simple', order: 52 },
        smgz: { title: '商户规则', icon: 'ph:shield-check', order: 53 },
        adduser: { title: '添加代理', icon: 'ph:user-plus', order: 54 },
        usergj: { title: '批量改价', icon: 'ph:currency-circle-dollar', order: 55, roles: ['R_SUPER'] },
        jgjk: { title: '价格监控', icon: 'ph:chart-line-up', order: 56, roles: ['R_SUPER'] },
        tjuser: { title: '分销推广', icon: 'ph:share-network', order: 57 },
        logout: { title: '退出登录', icon: 'ph:sign-out', order: 99 },
        'iframe-page': { title: 'iframe-page', constant: true, hideInMenu: true }
      };

      const meta: Partial<RouteMeta> = {
        title: key
      };

      if (constantRoutes.includes(key)) {
        meta.constant = true;
        meta.i18nKey = `route.${key}` as App.I18n.I18nKey;
      }

      return { ...meta, ...routeMetaMap[key] };
    }
  });
}
