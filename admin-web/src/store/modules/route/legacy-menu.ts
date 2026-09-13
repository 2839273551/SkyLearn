import type { RouteKey } from '@elegant-router/types';
import { useSvgIcon } from '@/hooks/common/icon';

type MenuSection =
  | { route: RouteKey }
  | {
      key: string;
      label: string;
      icon: string;
      routes?: RouteKey[];
      nested?: { key: string; label: string; icon?: string; routes: RouteKey[] }[];
    };

const menuSections: MenuSection[] = [
  { key: 'legacy-home', label: '主页', icon: 'ph:house', routes: ['main', 'home'] },
  {
    key: 'legacy-settings',
    label: '设置',
    icon: 'ph:gear-six',
    nested: [
      {
        key: 'settings-system',
        label: '系统配置',
        icon: 'ph:sliders',
        routes: ['webset', 'gglist', 'webmsg', 'zzbz']
      },
      {
        key: 'settings-goods',
        label: '商品货源',
        icon: 'ph:package',
        routes: ['class', 'fenlei', 'huoyuan', 'yjdj']
      },
      {
        key: 'settings-docking',
        label: '对接监控',
        icon: 'ph:plugs-connected',
        routes: ['dockinglog']
      },
      {
        key: 'settings-scheduler',
        label: '任务调度',
        icon: 'ph:clock-clockwise',
        routes: ['scheduler']
      },
      {
        key: 'settings-pricing',
        label: '分销定价',
        icon: 'ph:users-three',
        routes: ['dengji', 'mijia']
      },
      {
        key: 'settings-finance',
        label: '资金财务',
        icon: 'ph:credit-card',
        routes: ['paylist', 'guanx']
      },
      {
        key: 'settings-analytics',
        label: '数据报表',
        icon: 'ph:chart-line-up',
        routes: ['data', 'ddtj']
      }
    ]
  },
  { key: 'legacy-study', label: '学习中心', icon: 'ph:user', routes: ['add', 'addpl', 'addtj'] },
  { route: 'list' },
  {
    key: 'legacy-profile',
    label: '我的信息',
    icon: 'ph:squares-four',
    nested: [
      {
        key: 'profile-account',
        label: '账户与代理',
        icon: 'ph:user-circle',
        routes: ['userinfo', 'userlist', 'log']
      },
      {
        key: 'profile-recharge',
        label: '充值方式',
        icon: 'ph:wallet',
        routes: ['charge', 'pay']
      },
      {
        key: 'profile-market',
        label: '行情与价格',
        icon: 'ph:chart-line-up',
        routes: ['myprice', 'pchangelist', 'atest', 'atesa', 'rd']
      },
      {
        key: 'profile-docking',
        label: '项目与对接',
        icon: 'ph:plugs',
        routes: ['dingdan', 'kcid', 'docking', 'help']
      }
    ]
  },
  { route: 'workorder' },
  { route: 'chati' },
  { route: 'logout' }
];

export function restoreLegacyMenuGroups(menus: App.Global.Menu[]): App.Global.Menu[] {
  const { SvgIconVNode } = useSvgIcon();
  const available = new Map(menus.map(m => [m.key, m]));
  const take = (keys?: RouteKey[]) => (keys || []).flatMap(k => {
    const m = available.get(k);
    if (!m) return [];
    available.delete(k);
    return [m];
  });

  const groupedMenus: App.Global.Menu[] = [];

  for (const section of menuSections) {
    if ('route' in section) {
      const m = available.get(section.route);
      if (m) {
        groupedMenus.push(m);
        available.delete(section.route);
      }
      continue;
    }

    const children = take(section.routes).map(m => ({
      ...m,
      icon: section.key === 'legacy-study' ? m.icon : undefined
    }));

    const nested = (section.nested || []).flatMap(item => {
      const itemChildren = take(item.routes);
      if (!itemChildren.length) return [];
      return [
        {
          ...itemChildren[0],
          key: item.key,
          label: item.label,
          i18nKey: null,
          icon: item.icon ? SvgIconVNode({ icon: item.icon, fontSize: 18 }) : undefined,
          children: itemChildren
        }
      ];
    });

    const allChildren = [...children, ...nested];
    if (!allChildren.length) continue;

    groupedMenus.push({
      ...allChildren[0],
      key: section.key,
      label: section.label,
      i18nKey: null,
      icon: SvgIconVNode({ icon: section.icon, fontSize: 20 }),
      children: allChildren
    });
  }

  return [...groupedMenus, ...available.values()];
}
