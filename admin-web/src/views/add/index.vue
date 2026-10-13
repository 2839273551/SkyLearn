<script setup lang="ts">
import { computed, h, nextTick, onMounted, ref, watch } from 'vue';
import type { SelectOption } from 'naive-ui';
import {
  NAlert,
  NButton,
  NCheckbox,
  NEmpty,
  NInput,
  NSelect,
  NSpin,
  NSwitch,
  NTag
} from 'naive-ui';
import { fetchCourseQuery, fetchOrderCatalog, fetchOrderSubmit } from '@/service/api';
import { useAppStore } from '@/store/modules/app';
import { useAuthStore } from '@/store/modules/auth';

defineOptions({ name: 'Add' });

const appStore = useAppStore();
const authStore = useAuthStore();

// 加载状态
const catalogLoading = ref(false);
const queryLoading = ref(false);
const submitLoading = ref(false);

// 目录与分类商品
const categories = ref<Api.OrderEntry.Category[]>([]);
const products = ref<Api.OrderEntry.Product[]>([]);
const categoryId = ref('all');
const productId = ref('');

// 页面交互控制
const showId = ref(false); // 展示ID开关
const filterKeyword = ref(''); // 查询结果关键字过滤
const USERINFO_STORAGE_KEY = 'SK_ADD_ORDER_USERINFO';
const userinfo = ref(localStorage.getItem(USERINFO_STORAGE_KEY) || ''); // 用户输入的账号信息（支持本地持久化记忆）

// 移动端当前激活步骤：'form' (选择项目与填写查课) 或 'results' (选课核对与下单)
const mobileStep = ref<'form' | 'results'>('form');
const formCardRef = ref<HTMLElement | null>(null);
const resultsCardRef = ref<HTMLElement | null>(null);

function switchMobileStep(step: 'form' | 'results') {
  mobileStep.value = step;
  nextTick(() => {
    const scrollEl = document.getElementById('__SCROLL_EL_ID__');
    if (scrollEl) {
      scrollEl.scrollTo({ top: 0, behavior: 'smooth' });
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
}

// 实时持久化输入内容，防止刷新或换项目丢失
watch(userinfo, val => {
  if (val) {
    localStorage.setItem(USERINFO_STORAGE_KEY, val);
  } else {
    localStorage.removeItem(USERINFO_STORAGE_KEY);
  }
});

function clearUserinfo() {
  userinfo.value = '';
  localStorage.removeItem(USERINFO_STORAGE_KEY);
  window.$message?.info('已清空输入的账号信息');
}

// 全角字符与中英文符号规范化
function normalizeSymbols(value: string): string {
  const map: Record<string, string> = {
    '！': '!', '？': '?', '：': ':', '；': ';', '，': ',', '。': '.',
    '（': '(', '）': ')', '【': '[', '】': ']', '＠': '@', '＃': '#',
    '％': '%', '＆': '&', '＊': '*', '＋': '+', '－': '-', '＝': '=',
    '＿': '_', '｜': '|', '～': '~', '／': '/', '　': ' '
  };
  return value.replace(/[！？？：；，。（）【】＠＃％＆＊＋－＝＿｜～／　]/g, c => map[c] || c);
}

// 智能清洗单行账号格式（去除微信中常见的“学校：/账号：/密码：”等前缀）
function sanitizeSingleLine(raw: string): string {
  let s = normalizeSymbols(raw.trim());
  if (!s) return '';

  // 1. 尝试提取具名标识
  const school = s.match(/(?:学校|school|院校|高校)\s*[:=]?\s*([^\s,;]+)/i)?.[1] || '';
  const account = s.match(/(?:账号|用户名|帐号|username|account|user|学号|手机号)\s*[:=]?\s*([^\s,;]+)/i)?.[1] || '';
  const password = s.match(/(?:密码|password|pwd|pass)\s*[:=]?\s*([^\s,;]+)/i)?.[1] || '';

  if (account && password) {
    return [school, account, password].filter(Boolean).join(' ');
  }

  // 2. 剥离常见前缀修饰词
  s = s.replace(/(?:学校|school|院校|高校|账号|用户名|帐号|username|account|user|学号|手机号|密码|password|pwd|pass)\s*[:=]?/gi, ' ');
  // 3. 将常见的 ---- 或 ---- 分隔符转为空格
  s = s.replace(/[-]{3,}/g, ' ').replace(/[,;|]+/g, ' ');
  s = s.replace(/\s+/g, ' ').trim();

  return s;
}

// 批量智能清洗账号输入框格式
function applyFormatSanitize() {
  if (!userinfo.value.trim()) {
    window.$message?.warning('请先输入或粘贴账号信息');
    return;
  }
  const lines = userinfo.value.split(/\r?\n/);
  const cleaned = lines.map(sanitizeSingleLine).filter(Boolean).join('\n');
  if (cleaned !== userinfo.value.trim()) {
    userinfo.value = cleaned;
    window.$message?.success('已完成智能格式清洗与标准化');
  } else {
    window.$message?.info('格式规范，无需调整');
  }
}

// 填入示例数据
function insertSampleAccounts() {
  userinfo.value = '武汉工程科技学院 3700241423 Kyn20040910\n4400240801 980319Saki';
  window.$message?.info('已填入标准账号格式示例');
}

// 手机端便捷读取剪贴板并填入（带自动清洗）
async function pasteFromClipboard() {
  try {
    if (navigator.clipboard && navigator.clipboard.readText) {
      const clipText = await navigator.clipboard.readText();
      if (clipText && clipText.trim()) {
        const cleanedClip = clipText.split(/\r?\n/).map(sanitizeSingleLine).filter(Boolean).join('\n');
        userinfo.value = userinfo.value ? `${userinfo.value.trim()}\n${cleanedClip}` : cleanedClip;
        window.$message?.success('已从剪贴板粘贴并自动清洗格式');
      } else {
        window.$message?.info('剪贴板中暂无文本内容');
      }
    } else {
      window.$message?.warning('当前浏览器暂不支持自动读取剪贴板，请长按输入框粘贴');
    }
  } catch {
    window.$message?.warning('读取剪贴板被拒绝，请长按输入框手动粘贴');
  }
}

// 查课结果与勾选
const results = ref<Api.OrderEntry.QueryResult[]>([]);
const selections = ref<Api.OrderEntry.Selection[]>([]);
const expandedAccounts = ref<Set<string>>(new Set());

// 余额与配置
const balance = ref('0.00');
const queryEnabled = ref(true);
const orderEnabled = ref(true);
const notice = ref('');

// 当前选中的商品对象
const selectedProduct = computed(() => products.value.find(item => item.id === productId.value));

// 依分类过滤商品（严格按照「分类排序优先 + 分类内序号从0递增」排布）
const visibleProducts = computed(() => {
  const catRankMap: Record<string, number> = {};
  categories.value.forEach((c, idx) => {
    catRankMap[c.id] = idx;
  });

  const filtered = categoryId.value === 'all'
    ? [...products.value]
    : products.value.filter(item => item.categoryId === categoryId.value);

  return filtered.sort((a, b) => {
    const rankA = catRankMap[a.categoryId] ?? 9999;
    const rankB = catRankMap[b.categoryId] ?? 9999;
    if (rankA !== rankB) return rankA - rankB;
    const sortA = Number(a.sort) || 0;
    const sortB = Number(b.sort) || 0;
    if (sortA !== sortB) return sortA - sortB;
    return Number(a.id) - Number(b.id);
  });
});

// 下拉选单选项
const productOptions = computed(() => {
  const catNameMap: Record<string, string> = {};
  categories.value.forEach(c => {
    catNameMap[c.id] = c.name;
  });

  return visibleProducts.value.map(item => ({
    label: item.name,
    value: item.id,
    price: item.price,
    categoryName: catNameMap[item.categoryId] || ''
  }));
});

// 渲染带价格提示与分类标识的下拉选单项
function renderSelectOptionLabel(option: SelectOption) {
  return h('div', { class: 'flex items-center justify-between w-full py-3px' }, [
    h('div', { class: 'flex items-center gap-6px min-w-0 mr-8px flex-1' }, [
      option.categoryName && categoryId.value === 'all'
        ? h(
            'span',
            {
              class:
                'px-5px py-1px rounded text-12px bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 shrink-0 font-medium'
            },
            String(option.categoryName)
          )
        : null,
      h('span', { class: 'text-gray-800 dark:text-gray-100 font-normal text-13px sm:text-14px break-words' }, String(option.label || ''))
    ]),
    option.price
      ? h(
          'span',
          { class: 'text-12px sm:text-13px text-blue-600 dark:text-blue-400 font-mono shrink-0 font-bold ml-4px' },
          `¥${option.price}`
        )
      : null
  ]);
}

// 分割待查询的账号行（自动识别单账号或多行批量，无需任何手动切换）
const inputLines = computed(() =>
  userinfo.value
    .split(/\r?\n/)
    .map(item => item.trim())
    .filter(Boolean)
);

// 总计课程门数
const totalCourses = computed(() => results.value.reduce((total, result) => total + result.courses.length, 0));
const allSelected = computed(() => totalCourses.value > 0 && selections.value.length === totalCourses.value);

// 预计提交总扣费
const estimatedSubmitCost = computed(() => {
  if (!selectedProduct.value) return '0.00';
  const price = Number(selectedProduct.value.price) || 0;
  return (selections.value.length * price).toFixed(2);
});

// 提取课程标题核心与进度标签
interface ParsedCourseInfo {
  cleanTitle: string;
  progressTag: string;
  isCompleted: boolean;
}

function parseCourseDisplay(course: Api.OrderEntry.Course): ParsedCourseInfo {
  const name = course.name || '';
  let progressTag = '';
  let cleanTitle = name;

  // 1. 优先提取 【课程进度:xx%】 标签
  const matchProg = name.match(/[【\(\（]课程进度[:：]?([^】\)\）]+)[】\)\）]/);
  if (matchProg) {
    progressTag = matchProg[1].trim();
    cleanTitle = name.replace(/[【\(\（]课程进度[:：]?[^】\)\）]+[】\)\）]/g, '').trim();
  } else if (course.state && course.state.trim()) {
    progressTag = course.state.trim().replace(/^[【\[]+|[】\]]+$/g, '').trim();
  }

  // 2. 去除流年/平台附加的 ---课程id---省份---城市 后缀
  cleanTitle = cleanTitle.replace(/---[\s\S]*$/u, '').trim();

  // 3. 判断是否已达 100% 或结课
  const isCompleted =
    progressTag.includes('100%') ||
    progressTag.includes('已完成') ||
    progressTag.includes('已结课') ||
    progressTag.includes('已学完');

  return { cleanTitle: cleanTitle || name, progressTag, isCompleted };
}

// 加载分类与商品目录
async function loadCatalog() {
  catalogLoading.value = true;
  const { data, error } = await fetchOrderCatalog();
  if (!error && data) {
    categories.value = (data.categories || []).filter(c => !c.name.includes('我的收藏') && !c.name.includes('收藏夹'));
    products.value = data.products;
    balance.value = data.balance;
    queryEnabled.value = data.queryEnabled;
    orderEnabled.value = data.orderEnabled;
    notice.value = data.notice;
    authStore.userInfo.balance = data.balance;
    if (!productId.value && visibleProducts.value.length > 0) {
      productId.value = visibleProducts.value[0].id;
    }
  }
  catalogLoading.value = false;
}

// 切换分类
function handleCategorySelect(targetId: string) {
  categoryId.value = targetId;
}

// 快速选项目（针对移动端直接点击卡片）
function quickSelectProduct(id: string) {
  productId.value = id;
}

// 查询课程
async function queryCourses() {
  if (!productId.value || inputLines.value.length === 0) {
    window.$message?.warning('请先选择项目并填写账号信息');
    return;
  }
  if (!queryEnabled.value) {
    window.$message?.error('管理员已关闭查课功能');
    return;
  }

  queryLoading.value = true;
  selections.value = [];
  results.value = [];
  filterKeyword.value = '';

  const { data, error } = await fetchCourseQuery(productId.value, inputLines.value);
  if (!error && data) {
    results.value = data.results;
    expandedAccounts.value = new Set(data.results.map(r => r.userinfo));
    balance.value = data.balance;
    authStore.userInfo.balance = data.balance;
    const successCount = data.results.filter(item => item.courses.length > 0).length;
    if (successCount > 0) {
      window.$message?.success(`查询完成，共找到 ${successCount} 个账号的课程`);
      // 移动端体验核心：自动无缝切换到“选课与下单”步骤！
      nextTick(() => {
        switchMobileStep('results');
      });
    } else {
      window.$message?.warning('未查询到任何课程，请检查账号密码是否正确');
    }
  }
  queryLoading.value = false;
}

// 展开/收起账号节点
function isExpanded(accountInfo: string): boolean {
  return expandedAccounts.value.has(accountInfo);
}

function toggleExpand(accountInfo: string) {
  if (expandedAccounts.value.has(accountInfo)) {
    expandedAccounts.value.delete(accountInfo);
  } else {
    expandedAccounts.value.add(accountInfo);
  }
}

// 勾选操作辅助
function selectionKey(userinfoValue: string, course: Api.OrderEntry.Course) {
  return `${userinfoValue}\u0000${course.id || course.name}`;
}

function isSelected(result: Api.OrderEntry.QueryResult, course: Api.OrderEntry.Course) {
  const key = selectionKey(result.userinfo, course);
  return selections.value.some(item => selectionKey(item.userinfo, item.course) === key);
}

function toggleCourse(checked: boolean, result: Api.OrderEntry.QueryResult, course: Api.OrderEntry.Course) {
  const key = selectionKey(result.userinfo, course);
  const next = selections.value.filter(item => selectionKey(item.userinfo, item.course) !== key);
  if (checked) {
    next.push({
      userinfo: result.userinfo,
      userName: result.userName,
      course
    });
  }
  selections.value = next;
}

function isAccountAllSelected(result: Api.OrderEntry.QueryResult): boolean {
  if (!result.courses || result.courses.length === 0) return false;
  return result.courses.every(c => isSelected(result, c));
}

function isAccountPartiallySelected(result: Api.OrderEntry.QueryResult): boolean {
  if (!result.courses || result.courses.length === 0) return false;
  const count = result.courses.filter(c => isSelected(result, c)).length;
  return count > 0 && count < result.courses.length;
}

function toggleAccountCourses(result: Api.OrderEntry.QueryResult, checked?: boolean) {
  const shouldSelect = typeof checked === 'boolean' ? checked : !isAccountAllSelected(result);
  if (shouldSelect) {
    result.courses.forEach(c => {
      if (!isSelected(result, c)) {
        selections.value.push({
          userinfo: result.userinfo,
          userName: result.userName,
          course: c
        });
      }
    });
  } else {
    selections.value = selections.value.filter(s => s.userinfo !== result.userinfo);
  }
}

function toggleSelectAll() {
  if (allSelected.value) {
    selections.value = [];
  } else {
    const next: Api.OrderEntry.Selection[] = [];
    results.value.forEach(result => {
      result.courses.forEach(course => {
        next.push({
          userinfo: result.userinfo,
          userName: result.userName,
          course
        });
      });
    });
    selections.value = next;
  }
}

// 移动端专属快捷功能：一键仅勾选未完结课程 (跳过 100% 课程)
function selectUnfinishedCoursesOnly() {
  const next: Api.OrderEntry.Selection[] = [];
  results.value.forEach(result => {
    result.courses.forEach(course => {
      const parsed = parseCourseDisplay(course);
      if (!parsed.isCompleted) {
        next.push({
          userinfo: result.userinfo,
          userName: result.userName,
          course
        });
      }
    });
  });
  selections.value = next;
  window.$message?.info(`已自动勾选 ${next.length} 门未完成课程`);
}

// 复制客服微信分享话术
function copyQueryInfo(result: Api.OrderEntry.QueryResult) {
  if (!result.courses || result.courses.length === 0) {
    window.$message?.warning('暂无可复制的课程');
    return;
  }
  let infoToCopy =
    '亲亲，我们已经将您账号的课程找好啦！\n' +
    '请告诉我需要代看的课程序号【数字】，我们马上为您安排哈！\n' +
    '------------------------\n';

  const selectedForAccount = selections.value.filter(s => s.userinfo === result.userinfo);
  const targetCourses = selectedForAccount.length > 0 ? selectedForAccount.map(s => s.course) : result.courses;

  targetCourses.forEach((c, idx) => {
    infoToCopy += `课程${idx + 1}：${c.name}\n`;
  });
  infoToCopy += '------------------------';

  navigator.clipboard.writeText(infoToCopy);
  window.$message?.success('话术已成功复制到剪贴板，快去发给客户吧！');
}

// 提交订单
async function submitOrders() {
  if (selections.value.length === 0) {
    window.$message?.warning('请先勾选需要下单的课程');
    return;
  }
  if (!orderEnabled.value) {
    window.$message?.error('管理员已暂停下单功能');
    return;
  }

  submitLoading.value = true;
  const { data, error } = await fetchOrderSubmit(productId.value, selections.value);
  if (!error && data) {
    balance.value = data.balance;
    authStore.userInfo.balance = data.balance;
    selections.value = [];
    results.value = [];
    mobileStep.value = 'form';

    window.$notification?.success({
      title: '下单提交成功',
      content: `已成功提交 ${data.submitted} 笔订单，实扣 ¥${data.charged} 积分。账号信息已自动保留，您可直接切换项目继续下单！`,
      duration: 5000
    });
  }
  submitLoading.value = false;
}

// 多关键字空格多词过滤计算
const filteredResults = computed(() => {
  const kw = filterKeyword.value.trim().toLowerCase();
  if (!kw) return results.value;
  const keywords = kw.split(/\s+/).filter(Boolean);
  return results.value
    .map(res => {
      const matchingCourses = res.courses.filter(course => {
        const fullCourseText = `${course.name} ${course.id || ''} ${course.state || ''} ${course.teacher || ''}`.toLowerCase();
        return keywords.every(k => fullCourseText.includes(k));
      });
      return {
        ...res,
        courses: matchingCourses
      };
    })
    .filter(res => res.courses.length > 0 || !res.courses);
});

watch(categoryId, () => {
  if (visibleProducts.value.length > 0) {
    productId.value = visibleProducts.value[0].id;
  } else {
    productId.value = '';
  }
  results.value = [];
  selections.value = [];
});

watch(productId, () => {
  results.value = [];
  selections.value = [];
});

onMounted(loadCatalog);
</script>

<template>
  <div class="w-full max-w-1680px mx-auto p-8px sm:p-20px font-sans">
    <!-- 下单全站通知 -->
    <NAlert v-if="notice" type="warning" title="全站下单通知" :show-icon="true" class="mb-10px sm:mb-16px rounded-8px text-12px sm:text-13px">
      <div class="whitespace-pre-wrap leading-relaxed">{{ notice }}</div>
    </NAlert>

    <!-- ================= 移动端分步指示 Tab 栏 (仅屏幕 < lg 时展现) ================= -->
    <div class="lg:hidden mb-10px">
      <div class="flex items-center p-3px bg-gray-100 dark:bg-dark-600 rounded-10px select-none shadow-xs">
        <button
          type="button"
          class="flex-1 py-8px text-center rounded-8px transition-all cursor-pointer border-0 flex items-center justify-center gap-6px text-13px"
          :class="
            mobileStep === 'form'
              ? 'bg-white dark:bg-dark-700 text-blue-600 dark:text-blue-400 font-bold shadow-xs'
              : 'bg-transparent text-gray-500 dark:text-gray-400 font-medium'
          "
          @click="switchMobileStep('form')"
        >
          <span>1. 选择与查课</span>
          <span v-if="inputLines.length > 0" class="text-11px font-mono opacity-80 bg-blue-50 dark:bg-blue-900/30 text-blue-600 px-5px py-0.5px rounded-full">
            {{ inputLines.length }}单
          </span>
        </button>

        <button
          type="button"
          class="flex-1 py-8px text-center rounded-8px transition-all cursor-pointer border-0 flex items-center justify-center gap-6px text-13px"
          :class="
            mobileStep === 'results'
              ? 'bg-white dark:bg-dark-700 text-blue-600 dark:text-blue-400 font-bold shadow-xs'
              : 'bg-transparent text-gray-500 dark:text-gray-400 font-medium'
          "
          @click="switchMobileStep('results')"
        >
          <span>2. 选课与下单</span>
          <span
            v-if="totalCourses > 0"
            class="px-6px py-1px text-11px rounded-full font-mono font-bold"
            :class="selections.length > 0 ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-dark-500 text-gray-700 dark:text-gray-300'"
          >
            {{ selections.length }}/{{ totalCourses }}
          </span>
        </button>
      </div>

      <!-- 移动端已查出课程时，在步骤1底部提供快捷跳转条 -->
      <div
        v-if="mobileStep === 'form' && totalCourses > 0"
        class="mt-6px p-8px bg-blue-50 dark:bg-blue-950/40 border border-blue-200/60 dark:border-blue-900/40 rounded-8px flex items-center justify-between text-12px text-blue-700 dark:text-blue-300"
      >
        <span class="flex items-center gap-4px">
          <span>✨</span>
          <span>已查出 <strong>{{ totalCourses }}</strong> 门课程（已选 {{ selections.length }} 门）</span>
        </span>
        <button
          type="button"
          class="px-10px py-4px rounded-6px bg-blue-600 text-white font-bold border-0 cursor-pointer text-11px shrink-0 active:scale-95"
          @click="switchMobileStep('results')"
        >
          去选课下单 ➔
        </button>
      </div>
    </div>

    <!-- 左右分栏核心网格布局：桌面端双栏并排展示，移动端依 mobileStep 沉浸式单步呈现 -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10px sm:gap-16px items-stretch">
      <!-- ================= 左侧卡片：项目查询表单 ================= -->
      <div
        ref="formCardRef"
        class="lg:col-span-6 bg-white dark:bg-dark-700 rounded-10px sm:rounded-12px border border-gray-100 dark:border-dark-600 shadow-xs p-12px sm:p-24px flex-col justify-between h-full"
        :class="mobileStep === 'form' ? 'flex' : 'hidden lg:flex'"
      >
        <div class="flex-1 flex flex-col">
          <!-- 头部：标题与账户可用余额 -->
          <div class="flex items-center justify-between pb-10px sm:pb-18px border-b border-gray-100 dark:border-dark-600 mb-10px sm:mb-16px">
            <span class="text-15px sm:text-18px font-bold text-gray-800 dark:text-gray-100 select-none flex items-center gap-6px">
              <span class="text-blue-600 dark:text-blue-400">⚡</span>
              <span>项目查询</span>
            </span>
            <div class="text-12px sm:text-13px text-gray-500 dark:text-gray-400 flex items-center gap-4px">
              <span>可用余额:</span>
              <strong class="text-emerald-600 dark:text-emerald-400 font-bold font-mono text-14px sm:text-16px">¥ {{ balance }}</strong>
            </div>
          </div>

          <NSpin :show="catalogLoading" class="flex-1 flex flex-col">
            <div class="flex-1 flex flex-col gap-12px sm:gap-18px">
              <!-- 渠道分类胶囊导航 (移动端横向平滑滚动药丸，桌面端优雅排布) -->
              <div>
                <div class="text-12px text-gray-500 dark:text-gray-400 mb-6px select-none font-medium">
                  选择课程大类：
                </div>
                <div class="flex items-center gap-6px sm:gap-8px overflow-x-auto pb-4px pt-1px scrollbar-none select-none -mx-2px px-2px">
                  <button
                    type="button"
                    class="px-10px sm:px-12px py-5px sm:py-6px rounded-6px sm:rounded-8px text-12px sm:text-13px shrink-0 transition-all cursor-pointer border-0 font-medium whitespace-nowrap active:scale-95"
                    :class="
                      categoryId === 'all'
                        ? 'bg-blue-600 text-white font-bold shadow-xs'
                        : 'bg-gray-100 dark:bg-dark-600 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-dark-500'
                    "
                    @click="handleCategorySelect('all')"
                  >
                    全部类目
                  </button>

                  <button
                    v-for="item in categories"
                    :key="item.id"
                    type="button"
                    class="px-10px sm:px-12px py-5px sm:py-6px rounded-6px sm:rounded-8px text-12px sm:text-13px shrink-0 transition-all cursor-pointer border-0 font-medium whitespace-nowrap active:scale-95"
                    :class="
                      categoryId === item.id
                        ? 'bg-blue-600 text-white font-bold shadow-xs'
                        : 'bg-gray-100 dark:bg-dark-600 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-dark-500'
                    "
                    @click="handleCategorySelect(item.id)"
                  >
                    {{ item.name }}
                  </button>
                </div>
              </div>

              <!-- 移动端项目快选磁贴卡片（当分类项目 <= 8 时，直接展示点选卡片，极大提升手机端点选效率） -->
              <div v-if="visibleProducts.length > 0 && visibleProducts.length <= 8" class="lg:hidden flex flex-col gap-6px">
                <div class="text-12px text-gray-500 dark:text-gray-400 select-none font-medium flex items-center justify-between">
                  <span>点击切换项目：</span>
                  <span class="text-11px text-blue-600 dark:text-blue-400 font-mono">{{ visibleProducts.length }}个可选</span>
                </div>
                <div class="grid grid-cols-2 gap-6px">
                  <button
                    v-for="p in visibleProducts"
                    :key="p.id"
                    type="button"
                    class="p-8px rounded-8px text-left border transition-all cursor-pointer flex flex-col justify-between gap-4px active:scale-98"
                    :class="
                      productId === p.id
                        ? 'bg-blue-50/80 dark:bg-blue-950/40 border-blue-500 shadow-xs text-blue-900 dark:text-blue-200'
                        : 'bg-gray-50/60 dark:bg-dark-600/40 border-gray-200/60 dark:border-dark-500 text-gray-700 dark:text-gray-300'
                    "
                    @click="quickSelectProduct(p.id)"
                  >
                    <span class="text-12px font-bold leading-snug line-clamp-2">{{ p.name }}</span>
                    <span class="text-11px font-mono font-bold text-blue-600 dark:text-blue-400">¥{{ p.price }} / 门</span>
                  </button>
                </div>
              </div>

              <!-- 项目下拉框 (全端通用支持搜索) -->
              <div>
                <div class="text-12px text-gray-500 dark:text-gray-400 mb-6px select-none font-medium flex items-center justify-between">
                  <span>项目平台选单：</span>
                  <span v-if="selectedProduct" class="text-11px text-emerald-600 dark:text-emerald-400 font-mono">
                    单价 ¥{{ selectedProduct.price }}/门
                  </span>
                </div>
                <NSelect
                  v-model:value="productId"
                  :filterable="!appStore.isMobile"
                  :options="productOptions"
                  placeholder="可在此搜索或选择下单项目平台"
                  :render-label="renderSelectOptionLabel"
                  class="w-full text-13px sm:text-14px"
                />

                <!-- 选定项目的单价与说明轻提示 -->
                <div
                  v-if="selectedProduct"
                  class="mt-6px sm:mt-8px p-8px sm:p-10px bg-blue-50/70 dark:bg-blue-950/30 border border-blue-100/70 dark:border-blue-900/40 rounded-8px text-12px text-blue-800 dark:text-blue-300 flex flex-wrap items-center justify-between gap-6px"
                >
                  <div class="flex items-center gap-8px font-medium">
                    <span>当前平台: <strong class="text-blue-700 dark:text-blue-300 font-bold">{{ selectedProduct.name }}</strong></span>
                    <span>| 单价: <strong class="font-bold font-mono text-13px text-rose-500">¥{{ selectedProduct.price }}</strong></span>
                  </div>
                  <span
                    v-if="selectedProduct.content"
                    class="text-gray-500 dark:text-gray-400 text-11px leading-relaxed w-full mt-2px pt-4px border-t border-blue-100/50 dark:border-blue-900/30"
                  >
                    {{ selectedProduct.content }}
                  </span>
                </div>
              </div>

              <!-- 账号信息录入区域 (带快捷操作工具条) -->
              <div class="flex-1 flex flex-col min-h-140px sm:min-h-160px">
                <div class="flex items-center justify-between pb-6px text-12px text-gray-500 dark:text-gray-400 select-none">
                  <span class="font-medium">账号密码（单行或多账号换行）：</span>
                  <div class="flex items-center gap-8px">
                    <button
                      type="button"
                      class="text-blue-600 dark:text-blue-400 hover:underline bg-transparent border-0 cursor-pointer text-11px flex items-center gap-2px font-medium active:scale-95"
                      @click="pasteFromClipboard"
                    >
                      <span>📋</span>
                      <span>一键粘贴</span>
                    </button>
                    <button
                      v-if="userinfo"
                      type="button"
                      class="text-emerald-600 dark:text-emerald-400 hover:underline bg-transparent border-0 cursor-pointer text-11px flex items-center gap-2px font-medium active:scale-95"
                      @click="applyFormatSanitize"
                    >
                      <span>✨</span>
                      <span>清洗格式</span>
                    </button>
                    <button
                      type="button"
                      class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 bg-transparent border-0 cursor-pointer text-11px active:scale-95"
                      @click="insertSampleAccounts"
                    >
                      示例
                    </button>
                    <button
                      v-if="userinfo"
                      type="button"
                      class="text-gray-400 hover:text-rose-500 bg-transparent border-0 cursor-pointer text-11px active:scale-95"
                      @click="clearUserinfo"
                    >
                      清空
                    </button>
                  </div>
                </div>

                <NInput
                  v-model:value="userinfo"
                  type="textarea"
                  :rows="5"
                  placeholder="每行一个账号，支持格式：&#10;学校 账号 密码&#10;账号 密码&#10;支持从微信直接复制粘贴并自动清洗格式"
                  class="w-full font-mono text-12px sm:text-13px rounded-8px flex-1 min-h-110px sm:min-h-150px leading-relaxed"
                  clearable
                />

                <div class="mt-6px flex items-center justify-between text-12px text-gray-600 dark:text-gray-300 dark:text-gray-500 select-none">
                  <span>多账号换行即批量，切换项目账号自动保留</span>
                  <span v-if="inputLines.length > 0">
                    已录入: <strong class="text-blue-600 dark:text-blue-400 font-bold font-mono">{{ inputLines.length }}</strong> 单
                  </span>
                </div>
              </div>
            </div>
          </NSpin>
        </div>

        <!-- 底部查课操作按钮栏 -->
        <div class="mt-14px sm:mt-18px pt-14px sm:pt-16px border-t border-gray-100 dark:border-dark-600 flex items-center justify-between gap-10px">
          <NButton
            type="primary"
            size="medium"
            :loading="queryLoading"
            :disabled="!productId || inputLines.length === 0"
            class="flex-1 lg:flex-initial px-22px font-bold rounded-8px shadow-xs bg-blue-600 hover:bg-blue-700 active:scale-98 transition-all"
            @click="queryCourses"
          >
            <template #icon>
              <span class="text-14px">🔍</span>
            </template>
            开始查询课程 {{ inputLines.length > 0 ? `(${inputLines.length}单)` : '' }}
          </NButton>

          <NButton
            v-if="userinfo"
            size="small"
            secondary
            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 rounded-6px px-12px shrink-0"
            @click="clearUserinfo"
          >
            清空输入
          </NButton>
        </div>
      </div>

      <!-- ================= 右侧卡片：查询结果与选课下单 ================= -->
      <div
        ref="resultsCardRef"
        class="lg:col-span-6 bg-white dark:bg-dark-700 rounded-10px sm:rounded-12px border border-gray-100 dark:border-dark-600 shadow-xs p-12px sm:p-24px flex-col justify-between h-full"
        :class="mobileStep === 'results' ? 'flex' : 'hidden lg:flex'"
      >
        <div class="flex-1 flex flex-col">
          <!-- 移动端专属返回顶部工具条 -->
          <div class="lg:hidden flex items-center justify-between pb-8px mb-10px border-b border-gray-100 dark:border-dark-600">
            <button
              type="button"
              class="text-12px font-bold text-blue-600 dark:text-blue-400 flex items-center gap-4px bg-transparent border-0 cursor-pointer p-0 active:scale-95"
              @click="switchMobileStep('form')"
            >
              <span>← 返回修改账号/切换项目</span>
            </button>
            <span v-if="selectedProduct" class="text-12px font-bold text-gray-700 dark:text-gray-300 font-mono truncate max-w-160px">
              {{ selectedProduct.name }} (¥{{ selectedProduct.price }})
            </span>
          </div>

          <!-- 头部：标题、课程统计与快捷操作开关 -->
          <div class="flex items-center justify-between pb-10px sm:pb-16px border-b border-gray-100 dark:border-dark-600 mb-10px sm:mb-12px">
            <div class="flex items-center gap-6px sm:gap-8px">
              <span class="text-15px sm:text-18px font-bold text-gray-800 dark:text-gray-100 select-none flex items-center gap-6px">
                <span class="text-emerald-600 dark:text-emerald-400">📋</span>
                <span>查询结果</span>
              </span>
              <span
                v-if="totalCourses > 0"
                class="px-6px sm:px-7px py-1px text-11px rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-mono font-bold"
              >
                共 {{ totalCourses }} 门
              </span>
            </div>

            <div class="flex items-center gap-10px select-none">
              <div class="flex items-center gap-4px">
                <NSwitch v-model:value="showId" size="small" />
                <span class="text-11px sm:text-12px text-gray-600 dark:text-gray-300">ID</span>
              </div>
            </div>
          </div>

          <!-- 选课快捷辅助工具条 (全选、跳过已完成、清空) -->
          <div v-if="totalCourses > 0" class="flex items-center justify-between gap-6px mb-10px select-none">
            <div class="flex items-center gap-6px overflow-x-auto scrollbar-none py-2px">
              <button
                type="button"
                class="px-8px py-4px rounded-6px text-11px font-medium bg-gray-100 dark:bg-dark-600 text-gray-700 dark:text-gray-300 border-0 cursor-pointer active:scale-95"
                @click="toggleSelectAll"
              >
                {{ allSelected ? '取消全选' : '全选所有' }}
              </button>

              <button
                type="button"
                class="px-8px py-4px rounded-6px text-11px font-medium bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border-0 cursor-pointer active:scale-95 whitespace-nowrap"
                @click="selectUnfinishedCoursesOnly"
              >
                仅选未完成课程
              </button>

              <button
                v-if="selections.length > 0"
                type="button"
                class="px-8px py-4px rounded-6px text-11px font-medium bg-gray-100 dark:bg-dark-600 text-gray-500 hover:text-rose-500 border-0 cursor-pointer active:scale-95 whitespace-nowrap"
                @click="selections = []"
              >
                清空勾选
              </button>
            </div>

            <span class="text-12px text-gray-600 dark:text-gray-300 shrink-0 font-mono">
              已选 <strong class="text-blue-600 dark:text-blue-400 font-bold">{{ selections.length }}</strong> 门
            </span>
          </div>

          <!-- 关键字过滤输入框 -->
          <div class="mb-10px">
            <NInput
              v-model:value="filterKeyword"
              clearable
              placeholder="搜索课程名过滤（支持空格多词组合搜索）"
              class="w-full text-12px sm:text-13px rounded-8px"
            >
              <template #prefix>
                <span class="text-gray-500 text-13px mr-4px">🔎</span>
              </template>
            </NInput>
          </div>

          <!-- 课程结果树状流 (移动端自然展示，桌面端等高滚动) -->
          <div class="flex-1 min-h-160px lg:max-h-520px lg:overflow-y-auto pr-0 lg:pr-4px">
            <!-- 空状态 -->
            <div
              v-if="!results.length"
              class="h-full flex flex-col items-center justify-center py-40px sm:py-48px text-center text-gray-400 dark:text-gray-500 text-13px"
            >
              <NEmpty description="暂无查询结果" size="small" />
              <button
                type="button"
                class="mt-12px lg:hidden px-14px py-6px rounded-8px bg-blue-600 text-white text-12px font-bold border-0 cursor-pointer active:scale-95"
                @click="switchMobileStep('form')"
              >
                前往第 1 步选择项目并查课 ➔
              </button>
            </div>

            <!-- 账号卡片树状流 -->
            <div v-else class="flex flex-col gap-8px">
              <div
                v-for="result in filteredResults"
                :key="result.userinfo"
                class="flex flex-col bg-gray-50/70 dark:bg-dark-600/30 rounded-10px p-6px sm:p-8px border border-gray-100/90 dark:border-dark-500/50"
              >
                <!-- 账号节点行 (加大移动端点击热区) -->
                <div
                  class="flex items-center gap-6px sm:gap-8px py-7px px-6px rounded-8px hover:bg-gray-100 dark:hover:bg-dark-600/60 cursor-pointer select-none transition-colors active:bg-gray-100"
                  @click="toggleExpand(result.userinfo)"
                >
                  <span class="text-12px text-gray-600 dark:text-gray-300 w-14px text-center shrink-0">
                    {{ isExpanded(result.userinfo) ? '▼' : '▶' }}
                  </span>

                  <!-- 账号全选复选框 -->
                  <NCheckbox
                    :checked="isAccountAllSelected(result)"
                    :indeterminate="isAccountPartiallySelected(result)"
                    :disabled="result.courses.length === 0"
                    class="shrink-0"
                    @click.stop
                    @update:checked="checked => toggleAccountCourses(result, checked)"
                  />

                  <!-- 账号名及姓名 -->
                  <div class="text-14px sm:text-15px text-gray-950 dark:text-white font-black truncate flex-1 min-w-0">
                    <span>{{ result.userinfo }}</span>
                    <span v-if="result.userName" class="text-gray-500 dark:text-gray-400 text-12px font-normal ml-6px">
                      ({{ result.userName }})
                    </span>
                    <span v-if="result.courses.length === 0" class="text-rose-500 text-11px ml-6px font-normal">
                      [{{ result.msg || '未查到课程' }}]
                    </span>
                  </div>

                  <!-- 客服微信话术一键复制按钮 -->
                  <button
                    v-if="result.courses.length > 0"
                    type="button"
                    class="text-11px text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-7px py-3px rounded-6px hover:bg-blue-100 border-0 cursor-pointer transition-all shrink-0 active:scale-95 font-medium"
                    title="生成并复制微信客服话术"
                    @click.stop="copyQueryInfo(result)"
                  >
                    📋 话术
                  </button>
                </div>

                <!-- 课程子列表 -->
                <div v-show="isExpanded(result.userinfo)" class="pl-20px sm:pl-28px flex flex-col gap-4px my-4px">
                  <div
                    v-for="course in result.courses"
                    :key="`${result.userinfo}-${course.id || course.name}`"
                    class="flex items-start gap-8px p-7px sm:p-6px rounded-8px cursor-pointer select-none transition-all active:scale-99 border"
                    :class="
                      isSelected(result, course)
                        ? 'bg-blue-50/90 dark:bg-blue-950/40 border-blue-300 dark:border-blue-800 shadow-xs'
                        : 'bg-white dark:bg-dark-600 border-gray-100 dark:border-dark-500 hover:border-gray-200'
                    "
                    @click="toggleCourse(!isSelected(result, course), result, course)"
                  >
                    <NCheckbox
                      :checked="isSelected(result, course)"
                      class="shrink-0 mt-2px"
                      @click.stop
                      @update:checked="checked => toggleCourse(checked, result, course)"
                    />

                    <!-- 课程核心信息区 (将进度和名称区分排布，移动端极易辨识) -->
                    <div class="flex-1 min-w-0">
                      <div class="flex flex-wrap items-center gap-6px">
                        <span class="text-15px sm:text-16px font-black text-gray-950 dark:text-white leading-snug break-words">
                          {{ parseCourseDisplay(course).cleanTitle }}
                        </span>

                        <!-- 进度 Badge -->
                        <span
                          v-if="parseCourseDisplay(course).progressTag"
                          class="px-5px py-0.5px rounded text-12px font-mono font-medium shrink-0"
                          :class="
                            parseCourseDisplay(course).isCompleted
                              ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400 border border-emerald-200/50'
                              : 'bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400 border border-blue-200/50'
                          "
                        >
                          {{ parseCourseDisplay(course).progressTag }}
                        </span>

                        <span v-if="showId && course.id" class="text-12px text-gray-400 font-mono">
                          #{{ course.id }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 卡片内常驻底部结算操作栏 -->
        <div
          class="flex mt-14px sm:mt-18px pt-14px sm:pt-16px border-t border-gray-100 dark:border-dark-600 flex-wrap items-center justify-between gap-10px"
        >
          <div class="flex items-center gap-6px text-13px text-gray-600 dark:text-gray-300">
            <span>已选 <strong class="text-blue-600 dark:text-blue-400 font-bold font-mono text-16px">{{ selections.length }}</strong> 门</span>
            <span v-if="selectedProduct && selections.length > 0">
              | 预计扣费: <strong class="text-rose-500 font-bold font-mono text-17px">¥ {{ estimatedSubmitCost }}</strong> 积分
            </span>
          </div>

          <div class="flex items-center gap-10px">
            <NButton
              size="medium"
              secondary
              :disabled="results.length === 0"
              class="rounded-8px"
              @click="toggleSelectAll"
            >
              {{ allSelected ? '取消全选' : '全选所有' }}
            </NButton>
            <NButton
              type="primary"
              size="medium"
              :loading="submitLoading"
              :disabled="selections.length === 0"
              class="px-22px font-bold rounded-8px shadow-xs bg-blue-600 hover:bg-blue-700 active:scale-98 transition-all"
              @click="submitOrders"
            >
              🚀 提交订单 {{ selections.length > 0 ? `(${selections.length}门)` : '' }}
            </NButton>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* 优雅平滑过渡 */
button,
div {
  transition-property: color, background-color, border-color, transform, opacity;
  transition-duration: 150ms;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* 隐藏横向滚动条保持极简整洁 */
.scrollbar-none::-webkit-scrollbar {
  display: none;
}
.scrollbar-none {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
