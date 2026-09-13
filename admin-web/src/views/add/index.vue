<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import {
  NAlert,
  NAvatar,
  NButton,
  NButtonGroup,
  NCard,
  NCheckbox,
  NCollapse,
  NCollapseItem,
  NDivider,
  NEmpty,
  NForm,
  NFormItem,
  NGi,
  NGrid,
  NInput,
  NPopconfirm,
  NRadioGroup,
  NRadioButton,
  NSelect,
  NSpace,
  NSpin,
  NStatistic,
  NSwitch,
  NTag,
  NTimeline,
  NTimelineItem,
  NTooltip
} from 'naive-ui';
import { fetchCourseQuery, fetchOrderCatalog, fetchOrderSubmit } from '@/service/api';
import { useAuthStore } from '@/store/modules/auth';
import { useAppStore } from '@/store/modules/app';

defineOptions({ name: 'Add' });

const FAVORITES_KEY = 'COURSE_ADMIN_order_favorites';
const authStore = useAuthStore();
const appStore = useAppStore();

const catalogLoading = ref(false);
const queryLoading = ref(false);
const submitLoading = ref(false);

const categories = ref<Api.OrderEntry.Category[]>([]);
const products = ref<Api.OrderEntry.Product[]>([]);
const categoryId = ref('all');
const productId = ref('');
const userinfo = ref('');
const batchMode = ref(false);
const aiCorrection = ref(false);
const results = ref<Api.OrderEntry.QueryResult[]>([]);
const selections = ref<Api.OrderEntry.Selection[]>([]);
const balance = ref('0.00');
const freeAdd = ref(0);
const freeOrderEnabled = ref(false);
const queryEnabled = ref(true);
const orderEnabled = ref(true);
const notice = ref('');
const favoriteIds = ref<string[]>(loadFavorites());

const selectedProduct = computed(() => products.value.find(item => item.id === productId.value));

const visibleProducts = computed(() => {
  if (categoryId.value === 'favorites') {
    return products.value.filter(item => favoriteIds.value.includes(item.id));
  }
  if (categoryId.value === 'all') return products.value;
  return products.value.filter(item => item.categoryId === categoryId.value);
});

const productOptions = computed(() =>
  visibleProducts.value.map(item => ({
    label: `${item.name} (¥${item.price} / 门)`,
    value: item.id
  }))
);

const inputLines = computed(() =>
  userinfo.value
    .split(/\r?\n/)
    .map(item => item.trim())
    .filter(Boolean)
);

const totalCourses = computed(() => results.value.reduce((total, result) => total + result.courses.length, 0));

const inputPlaceholder = computed(() => {
  return batchMode.value
    ? '请输入多行账号，每行一条：\n北京大学 2024001122 Abc123456\n清华大学 2024009988 Pwd@1234'
    : '请输入下单信息：学校 账号 密码（空格分隔，如无学校可直接：账号 密码）';
});

const allSelected = computed(() => totalCourses.value > 0 && selections.value.length === totalCourses.value);

// 预计提交总扣费
const estimatedSubmitCost = computed(() => {
  if (!selectedProduct.value) return '0.00';
  const price = Number(selectedProduct.value.price) || 0;
  return (selections.value.length * price).toFixed(2);
});

function loadFavorites() {
  try {
    const value = localStorage.getItem(FAVORITES_KEY);
    return value ? (JSON.parse(value) as string[]) : [];
  } catch {
    return [];
  }
}

function saveFavorites() {
  localStorage.setItem(FAVORITES_KEY, JSON.stringify(favoriteIds.value));
}

async function loadCatalog() {
  catalogLoading.value = true;
  const { data, error } = await fetchOrderCatalog();
  if (!error && data) {
    categories.value = data.categories;
    products.value = data.products;
    balance.value = data.balance;
    freeAdd.value = data.freeAdd;
    freeOrderEnabled.value = data.freeOrderEnabled;
    queryEnabled.value = data.queryEnabled;
    orderEnabled.value = data.orderEnabled;
    notice.value = data.notice;
    authStore.userInfo.balance = data.balance;
    authStore.userInfo.freeAdd = data.freeAdd;
    if (!productId.value && data.products.length > 0) {
      productId.value = data.products[0].id;
    }
  }
  catalogLoading.value = false;
}

function toggleFavorite() {
  if (!productId.value) {
    window.$message?.warning('请先选择平台');
    return;
  }

  const index = favoriteIds.value.indexOf(productId.value);
  if (index >= 0) {
    favoriteIds.value.splice(index, 1);
    window.$message?.info('已取消收藏');
  } else {
    favoriteIds.value.push(productId.value);
    window.$message?.success('已添加到收藏');
  }
  saveFavorites();
}

function toggleInputMode() {
  batchMode.value = !batchMode.value;
}

function replaceFullWidthSymbols(value: string) {
  const replacements: Record<string, string> = {
    '！': '!',
    '？': '?',
    '：': ':',
    '；': ';',
    '，': ',',
    '。': '.',
    '（': '(',
    '）': ')',
    '＠': '@',
    '＃': '#',
    '％': '%',
    '＆': '&',
    '＊': '*',
    '＋': '+',
    '－': '-',
    '＝': '=',
    '＿': '_',
    '｜': '|',
    '～': '~',
    '／': '/',
    '　': ' '
  };

  return value.replace(/[！？？：；，。（）＠＃％＆＊＋－＝＿｜～／　]/g, char => replacements[char] || char);
}

function correctSingleLine(rawLine: string) {
  const line = replaceFullWidthSymbols(rawLine.trim());
  if (!line) return '';

  const school = line.match(/(?:学校|school|院校)\s*[:=]?\s*([^\s,;]+)/i)?.[1] || '';
  const account = line.match(/(?:账号|用户名|帐号|username|account|user)\s*[:=]?\s*([^\s,;]+)/i)?.[1] || '';
  const password = line.match(/(?:密码|password|pwd|pass)\s*[:=]?\s*([^\s,;]+)/i)?.[1] || '';

  if (account && password) return [school, account, password].filter(Boolean).join(' ');

  const cleaned = line
    .replace(/(?:学校|school|院校|账号|用户名|帐号|username|account|user|密码|password|pwd|pass)\s*[:=]?/gi, ' ')
    .replace(/[,;|]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
  const parts = cleaned.split(' ').filter(Boolean);
  return parts.length >= 2 ? parts.slice(0, 3).join(' ') : line;
}

function applyAiCorrection(showMessage = true) {
  if (!aiCorrection.value || !userinfo.value.trim()) return;
  const corrected = userinfo.value
    .split(/\r?\n/)
    .map(correctSingleLine)
    .filter(Boolean)
    .join('\n');

  if (corrected !== userinfo.value.trim()) {
    userinfo.value = corrected;
    if (showMessage) window.$message?.success('AI矫正：已自动提取格式');
  } else if (showMessage) {
    window.$message?.info('格式规范，无需纠偏');
  }
}

function fillSampleData() {
  userinfo.value = '北京大学 2024001122 123456';
  window.$message?.info('已填入下单示例');
}

async function queryCourses() {
  if (!productId.value || inputLines.value.length === 0) {
    window.$message?.warning('请先选择网课平台并填写账号信息');
    return;
  }
  if (!queryEnabled.value) {
    window.$message?.error('管理员已关闭查课功能');
    return;
  }

  if (aiCorrection.value) applyAiCorrection(false);
  queryLoading.value = true;
  selections.value = [];
  results.value = [];
  const { data, error } = await fetchCourseQuery(productId.value, inputLines.value);
  if (!error && data) {
    results.value = data.results;
    balance.value = data.balance;
    authStore.userInfo.balance = data.balance;
    const successCount = data.results.filter(item => item.courses.length > 0).length;
    if (successCount > 0) window.$message?.success(`查询完成，共找到 ${successCount} 个账号的课程`);
  }
  queryLoading.value = false;
}

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

function toggleSelectAll() {
  if (allSelected.value) {
    selections.value = [];
    return;
  }

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

// 仅选开课中
function selectOnlyOngoing() {
  const next: Api.OrderEntry.Selection[] = [];
  results.value.forEach(result => {
    result.courses.forEach(course => {
      if (!course.state || course.state.includes('开课') || course.state.includes('进行')) {
        next.push({
          userinfo: result.userinfo,
          userName: result.userName,
          course
        });
      }
    });
  });
  selections.value = next;
  window.$message?.info(`已自动筛选勾选 ${next.length} 门有效课程`);
}

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
    freeAdd.value = data.freeAdd;
    authStore.userInfo.balance = data.balance;
    authStore.userInfo.freeAdd = data.freeAdd;
    selections.value = [];

    window.$notification?.success({
      title: '下单提交完成',
      content: `已成功提交 ${data.submitted} 笔订单，扣费 ¥${data.charged}`,
      duration: 5000
    });
  }
  submitLoading.value = false;
}

function clearForm() {
  userinfo.value = '';
  results.value = [];
  selections.value = [];
  window.$message?.info('已清空录入与查课结果');
}

watch(categoryId, () => {
  if (!visibleProducts.value.some(item => item.id === productId.value)) productId.value = '';
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
  <div class="flex flex-col gap-16px p-10px sm:p-16px">
    <!-- 顶部高光 Hero 资产看板 -->
    <div class="rounded-12px bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-16px text-white shadow-sm border border-slate-700/50">
      <div class="flex flex-wrap items-center justify-between gap-12px">
        <div class="flex items-center gap-12px">
          <div class="flex h-44px w-44px items-center justify-center rounded-10px bg-primary/20 border border-primary/40 text-22px">
            🎓
          </div>
          <div>
            <div class="flex items-center gap-8px">
              <h2 class="text-18px font-bold tracking-wide">在线智能查课与提交中心</h2>
              <NTag size="tiny" type="success" round class="font-mono text-10px">FAST DOCKING</NTag>
            </div>
            <div class="mt-4px flex flex-wrap items-center gap-12px text-13px text-slate-300">
              <span>
                可用余额：<strong class="font-mono text-16px text-emerald-400 font-bold">¥ {{ balance }}</strong> 积分
              </span>
              <template v-if="freeOrderEnabled && freeAdd > 0">
                <span class="text-slate-500">|</span>
                <span class="rounded bg-emerald-950/60 px-6px py-2px text-11px text-emerald-300 border border-emerald-800">
                  🎁 赠送免费下单：{{ freeAdd }} 次
                </span>
              </template>
            </div>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-12px">
          <!-- AI 纠偏开关 -->
          <div class="flex items-center gap-8px rounded-8px bg-slate-800/80 px-10px py-6px border border-slate-700">
            <NSwitch v-model:value="aiCorrection" size="small" />
            <span class="text-12px font-medium">AI 格式矫正</span>
            <NTooltip>
              <template #trigger><span class="cursor-help text-13px text-slate-400">ℹ️</span></template>
              自动从杂乱聊天记录中智能提取学校、账号和密码。
            </NTooltip>
          </div>

          <!-- 模式切换 -->
          <NButtonGroup size="small">
            <NButton :type="!batchMode ? 'primary' : 'default'" @click="batchMode = false">
              单账号
            </NButton>
            <NButton :type="batchMode ? 'primary' : 'default'" @click="batchMode = true">
              多账号批量
            </NButton>
          </NButtonGroup>
        </div>
      </div>
    </div>

    <!-- 下单公告横幅 -->
    <NAlert v-if="notice" type="warning" title="全站下单特别通知" :show-icon="true" class="rounded-8px">
      <div class="whitespace-pre-wrap leading-relaxed text-13px">{{ notice }}</div>
    </NAlert>

    <!-- 核心下单主面板 -->
    <NCard title="网课平台与账号配置" :bordered="false" class="rounded-12px shadow-sm">
      <NSpin :show="catalogLoading">
        <NForm label-placement="top">
          <!-- 项目分类标签栏 -->
          <NFormItem label="项目所属分类：">
            <div class="w-full overflow-x-auto pb-4px">
              <NRadioGroup v-model:value="categoryId" size="small">
                <NSpace :wrap="false">
                  <NRadioButton value="all">🌟 全部平台</NRadioButton>
                  <NRadioButton value="favorites">⭐ 我的收藏</NRadioButton>
                  <NRadioButton v-for="item in categories" :key="item.id" :value="item.id">
                    {{ item.name }}
                  </NRadioButton>
                </NSpace>
              </NRadioGroup>
            </div>
          </NFormItem>

          <!-- 平台选择与收藏 -->
          <NFormItem label="选择网课平台：">
            <div class="w-full flex items-center gap-8px">
              <NSelect
                v-model:value="productId"
                class="flex-1"
                filterable
                clearable
                :options="productOptions"
                placeholder="点击选择下单平台，支持拼音与关键字即时搜索"
                :virtual-scroll="true"
              />
              <NTooltip>
                <template #trigger>
                  <NButton circle secondary :disabled="!productId" @click="toggleFavorite">
                    <template #icon>
                      <span :class="favoriteIds.includes(productId) ? 'text-amber-500' : 'text-gray-400'">★</span>
                    </template>
                  </NButton>
                </template>
                {{ favoriteIds.includes(productId) ? '取消收藏' : '添加收藏' }}
              </NTooltip>
            </div>
          </NFormItem>

          <!-- 选定平台高光卡片 -->
          <div v-if="selectedProduct" class="mb-16px rounded-8px bg-slate-50 p-12px dark:bg-dark-600 border border-slate-200 dark:border-dark-500">
            <div class="flex flex-wrap items-center justify-between gap-8px">
              <div class="flex items-center gap-8px">
                <span class="text-14px font-bold text-gray-800 dark:text-gray-100">{{ selectedProduct.name }}</span>
                <NTag size="tiny" type="info">CID: {{ selectedProduct.id }}</NTag>
              </div>
              <div class="flex items-center gap-12px text-13px">
                <span class="text-gray-500">
                  下单单价：<strong class="font-mono text-primary font-bold">¥ {{ selectedProduct.price }}</strong> / 门
                </span>
                <span class="text-gray-500">
                  查课扣费：<strong class="font-mono text-gray-700 dark:text-gray-300 font-medium">¥ {{ selectedProduct.queryFee }}</strong> / 账号
                </span>
              </div>
            </div>
            <div v-if="selectedProduct.content" class="mt-8px text-12px text-gray-500 bg-white dark:bg-dark-500 p-8px rounded-6px border border-gray-100 dark:border-dark-400">
              平台要求与说明：{{ selectedProduct.content }}
            </div>
          </div>

          <!-- 账号信息填写 -->
          <NFormItem label="学习账号信息录入：">
            <div class="w-full flex flex-col gap-8px">
              <div class="flex flex-wrap items-center justify-between gap-8px text-12px text-gray-400">
                <span>
                  {{ batchMode ? '每行一条信息：学校 账号 密码（空格分隔）' : '录入格式：学校 账号 密码（无学校可直接输入账号 密码）' }}
                </span>
                <div class="flex items-center gap-6px">
                  <NButton size="tiny" secondary @click="fillSampleData">填入示例</NButton>
                  <NButton v-if="aiCorrection" size="tiny" type="success" secondary @click="applyAiCorrection(true)">
                    一键AI纠偏
                  </NButton>
                  <NButton size="tiny" secondary type="warning" @click="userinfo = ''">清空输入</NButton>
                </div>
              </div>

              <NInput
                v-model:value="userinfo"
                :type="batchMode ? 'textarea' : 'text'"
                :autosize="batchMode ? { minRows: 4, maxRows: 10 } : false"
                :placeholder="inputPlaceholder"
                class="font-mono text-13px leading-relaxed"
                @blur="applyAiCorrection(false)"
              />
            </div>
          </NFormItem>

          <!-- 动作操作条 -->
          <div class="mt-12px flex flex-wrap items-center gap-12px">
            <NButton type="primary" size="large" :loading="queryLoading" class="px-24px font-bold" @click="queryCourses">
              🔍 立即在线查课
            </NButton>

            <NPopconfirm
              positive-text="确认立即提交"
              negative-text="取消"
              @positive-click="submitOrders"
            >
              <template #trigger>
                <NButton
                  type="success"
                  size="large"
                  :loading="submitLoading"
                  :disabled="selections.length === 0"
                  class="px-24px font-bold"
                >
                  🚀 提交已选课程（{{ selections.length }} 门）
                </NButton>
              </template>
              确定提交已勾选的 {{ selections.length }} 门课程吗？预计扣除 ¥{{ estimatedSubmitCost }} 积分。
            </NPopconfirm>

            <NButton size="large" secondary @click="clearForm">
              清空数据
            </NButton>

            <span v-if="selections.length > 0" class="ml-auto font-mono text-14px text-gray-500">
              已选 <strong class="text-primary font-bold">{{ selections.length }}</strong> 门课程 | 预计扣费：<strong class="text-rose-500 font-bold">¥ {{ estimatedSubmitCost }}</strong>
            </span>
          </div>
        </NForm>
      </NSpin>
    </NCard>

    <!-- 查课结果卡片流（核心呈现加强） -->
    <NCard v-if="results.length" :bordered="false" class="rounded-12px shadow-sm">
      <template #header>
        <div class="flex flex-wrap items-center justify-between gap-10px">
          <div class="flex items-center gap-10px">
            <span class="text-16px font-bold">在线查课结果清单</span>
            <NTag size="small" type="primary" round>
              已选 {{ selections.length }} / {{ totalCourses }} 门
            </NTag>
          </div>
          <div class="flex items-center gap-8px">
            <NButton size="small" secondary @click="toggleSelectAll">
              {{ allSelected ? '取消全选' : '全选所有' }}
            </NButton>
            <NButton size="small" secondary type="primary" @click="selectOnlyOngoing">
              仅勾选开课中
            </NButton>
          </div>
        </div>
      </template>

      <NCollapse :default-expanded-names="results.map(item => item.userinfo)" class="flex flex-col gap-12px">
        <NCollapseItem v-for="result in results" :key="result.userinfo" :name="result.userinfo" class="rounded-8px border border-gray-100 bg-gray-50/50 p-6px dark:border-dark-600 dark:bg-dark-600/30">
          <template #header>
            <div class="flex flex-wrap items-center gap-10px text-13px">
              <span class="font-bold text-gray-800 dark:text-gray-100">{{ result.userName || '学生姓名' }}</span>
              <span class="font-mono text-gray-400">[{{ result.userinfo }}]</span>
              <NTag :type="result.courses.length > 0 ? 'success' : 'error'" size="tiny" round>
                {{ result.msg }} ({{ result.courses.length }} 门)
              </NTag>
            </div>
          </template>

          <div v-if="result.courses.length === 0" class="py-16px">
            <NEmpty :description="result.msg || '未查询到任何开课记录'" />
          </div>

          <!-- 课程卡片栅格 (重磅美化) -->
          <NGrid v-else cols="1 s:2 l:3" responsive="screen" :x-gap="12" :y-gap="12" class="mt-8px">
            <NGi v-for="course in result.courses" :key="`${result.userinfo}-${course.id || course.name}`">
              <div
                class="relative rounded-8px border p-12px transition-all cursor-pointer select-none"
                :class="
                  isSelected(result, course)
                    ? 'border-primary bg-primary/5 shadow-sm dark:bg-primary/10'
                    : 'border-gray-200 bg-white hover:border-gray-300 dark:border-dark-500 dark:bg-dark-600'
                "
                @click="toggleCourse(!isSelected(result, course), result, course)"
              >
                <div class="flex items-start gap-10px">
                  <NCheckbox
                    :checked="isSelected(result, course)"
                    @click.stop
                    @update:checked="checked => toggleCourse(checked, result, course)"
                  />
                  <div class="flex-1 min-w-0">
                    <div class="font-bold text-14px text-gray-800 dark:text-gray-100 truncate">
                      {{ course.name }}
                    </div>
                    <div class="mt-4px flex flex-wrap items-center gap-8px text-11px text-gray-400 font-mono">
                      <span>ID: {{ course.id || '-' }}</span>
                      <span>教师: {{ course.teacher || '未知' }}</span>
                    </div>
                    <div class="mt-6px flex items-center justify-between">
                      <NTag
                        size="tiny"
                        :type="course.state && !course.state.includes('结课') ? 'success' : 'default'"
                        round
                      >
                        {{ course.state || '开课中' }}
                      </NTag>
                      <span v-if="selectedProduct" class="font-mono text-12px font-bold text-primary">
                        ¥ {{ selectedProduct.price }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- 选中微标记 -->
                <div
                  v-if="isSelected(result, course)"
                  class="absolute right-0 top-0 h-0 w-0 border-t-16px border-r-16px border-t-transparent border-r-primary"
                ></div>
              </div>
            </NGi>
          </NGrid>
        </NCollapseItem>
      </NCollapse>

      <!-- 底部吸底结算工具栏 (极具电商大盘质感) -->
      <div v-if="selections.length > 0" class="mt-16px flex flex-wrap items-center justify-between gap-12px rounded-8px bg-slate-900 p-12px text-white shadow-lg">
        <div class="flex items-center gap-12px">
          <span class="text-13px text-slate-300">
            已勾选 <strong class="text-16px font-bold text-emerald-400 font-mono">{{ selections.length }}</strong> 门课程
          </span>
          <span class="text-slate-600">|</span>
          <span class="text-13px text-slate-300">
            预计结算总计：<strong class="text-20px font-bold text-emerald-400 font-mono">¥ {{ estimatedSubmitCost }}</strong>
          </span>
        </div>
        <div class="flex items-center gap-8px">
          <NButton secondary type="warning" size="small" @click="selections = []">清空勾选</NButton>
          <NButton type="primary" size="medium" :loading="submitLoading" class="px-20px font-bold" @click="submitOrders">
            立即提交并扣费
          </NButton>
        </div>
      </div>
    </NCard>

    <!-- 下单注意事项 -->
    <NCard title="网课下单核心指引与注意事项" :bordered="false" class="rounded-12px shadow-sm">
      <NTimeline>
        <NTimelineItem type="error" title="查课与扣费规则" content="请务必在提交前查看对应平台的说明，不同平台支持的平时分和作业进度规则各有不同。" />
        <NTimelineItem type="warning" title="重复订单防冲突" content="同账号同课程如需复跑或补单，请在订单汇总中操作重跑，或修改密码后再重新下单。" />
        <NTimelineItem type="info" title="格式规范建议" content="默认标准录入格式为：学校 账号 密码（空格分隔），亦支持自动识别。" />
        <NTimelineItem type="success" title="异常售后保障" content="查课或下单若遇接口波动，可通过左侧【问题反馈】提交工单，技术客服将快速跟进排查。" />
      </NTimeline>
    </NCard>
  </div>
</template>
