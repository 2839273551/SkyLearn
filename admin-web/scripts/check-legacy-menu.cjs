const assert = require('node:assert/strict');
const path = require('node:path');
const fs = require('node:fs');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

const base = process.env.PREVIEW_URL || 'http://127.0.0.1:4175';
const evidence = path.join(__dirname, '..', '.verification');
const expected = [
  ['主页', ['main', 'home']],
  [
    '设置',
    [
      'webmsg',
      'webset',
      'zzbz',
      'gglist',
      'huoyuan',
      'class',
      'fenlei',
      'dengji',
      'mijia',
      'ddtj',
      'data',
      'guanx',
      'paylist',
      'yjdj'
    ]
  ],
  ['学习中心', ['add', 'addpl', 'addtj']],
  ['订单汇总', null],
  [
    '我的信息',
    [
      'userinfo',
      'userlist',
      'atest',
      'atesa',
      'rd',
      'kcid',
      'dingdan',
      'log',
      'help',
      'myprice',
      'docking',
      'pchangelist',
      'legacy-recharge'
    ]
  ],
  ['问题反馈', null],
  ['题库查询', null],
  ['退出登录', null]
];
const existing = new Set(['main', 'home', 'add', 'list', 'userinfo', 'webset', 'logout']);
const auxiliary = ['passwd', 'sjqy', 'usernotice', 'smgz', 'adduser', 'usergj', 'jgjk', 'tjuser'];

async function readMenus(page) {
  return page.evaluate(() => {
    const pinia = document.querySelector('#app').__vue_app__.config.globalProperties.$pinia;
    const store = [...pinia._s.values()].find(value => Array.isArray(value.menus));
    const clean = menu => ({ key: menu.key, label: menu.label, children: menu.children?.map(clean) });
    return store.menus.map(clean);
  });
}

function leaves(menus) {
  return menus.flatMap(menu => (menu.children?.length ? leaves(menu.children) : [menu.key]));
}

(async () => {
  fs.mkdirSync(evidence, { recursive: true });
  const browser = await chromium.launch({ headless: true, executablePath: process.env.BROWSER_EXECUTABLE });
  try {
    for (const superUser of [true, false]) {
      const context = await browser.newContext({ viewport: { width: 1440, height: 960 } });
      await context.addInitScript(() => localStorage.setItem('COURSE_ADMIN_token', JSON.stringify('cookie-session')));
      await context.route('**/admin-api/v1/index.php?*', route => {
        const action = new URL(route.request().url()).searchParams.get('action');
        const data =
          action === 'session'
            ? {
                userId: superUser ? '1' : '2',
                userName: 'menu-test',
                displayName: 'menu-test',
                siteName: '网课管理中心',
                balance: '0.00',
                freeAdd: 0,
                csrfToken: 'test-only',
                roles: [superUser ? 'R_SUPER' : 'R_AGENT'],
                buttons: [],
                capabilities: [],
                canMigrateSuperior: superUser
              }
            : null;
        return route.fulfill({ json: { code: 0, msg: 'ok', data } });
      });
      const page = await context.newPage();
      const errors = [];
      page.on('pageerror', error => errors.push(error.message));
      await page.goto(`${base}/index/pay`);
      await page.getByRole('heading', { name: '卡密充值', exact: true }).waitFor();
      const menus = await readMenus(page);
      const expectedRole = superUser ? expected : expected.filter(item => item[0] !== '设置');
      assert.deepEqual(
        menus.map(menu => [menu.label, menu.children?.map(child => child.key) || null]),
        expectedRole
      );
      assert.deepEqual(
        menus
          .find(menu => menu.label === '我的信息')
          .children.at(-1)
          .children.map(menu => menu.key),
        ['pay', 'charge']
      );
      assert.equal(leaves(menus).length, superUser ? 37 : 23);
      if (superUser) {
        await page.screenshot({ path: path.join(evidence, 'menu-desktop.png'), fullPage: true });
        for (const key of [...leaves(menus).filter(key => !existing.has(key)), ...auxiliary]) {
          const response = await page.goto(`${base}/index/${key}`);
          assert.equal(response.status(), 200, key);
          await page.locator('h1').waitFor();
          assert.ok(!page.url().includes('/404'), key);
        }
        await page.goto(`${base}/index/docking`);
        await page.getByRole('heading', { name: '串货对接', exact: true }).waitFor();
        await page.screenshot({ path: path.join(evidence, 'docking-desktop.png'), fullPage: true });
        for (const title of [
          '29对接参数',
          '小储对接参数',
          '课程ID',
          '29对接代码',
          '查询课程',
          '查询进度',
          '课程补刷',
          'SkyLearn插件'
        ]) {
          await page.locator('.n-tabs-tab').filter({ hasText: title }).click();
        }
        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto(`${base}/index/pay`);
        await page.getByRole('heading', { name: '卡密充值', exact: true }).waitFor();
        await page.screenshot({ path: path.join(evidence, 'menu-mobile.png'), fullPage: true });
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth + 1), false);
      } else {
        await page.goto(`${base}/index/webmsg`);
        await page.waitForURL('**/index/403');
      }
      assert.deepEqual(errors, []);
      await context.close();
    }
    console.log(
      'PASS: admin 8 groups/37 leaves, agent 7 groups/23 leaves, all reserved routes, 8 docking tabs, mobile overflow, forbidden routes.'
    );
  } finally {
    await browser.close();
  }
})().catch(error => {
  console.error(error);
  process.exitCode = 1;
});
