<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import {
  NAlert,
  NButton,
  NCard,
  NCheckbox,
  NCollapse,
  NCollapseItem,
  NEmpty,
  NForm,
  NFormItem,
  NGi,
  NGrid,
  NInput,
  NRadioButton,
  NRadioGroup,
  NSelect,
  NSpace,
  NSpin,
  NSwitch,
  NTag,
  NTooltip
} from 'naive-ui';
import { fetchCourseQuery, fetchOrderCatalog, fetchOrderSubmit } from '@/service/api';
import { useAuthStore } from '@/store/modules/auth';

defineOptions({ name: 'Add' });

const FAVORITES_KEY = 'COURSE_ADMIN_order_favorites';
const authStore = useAuthStore();

const catalogLoading = ref(false);
const queryLoading = ref(false);
const submitLoading = ref(false);

const categories = ref<Api.OrderEntry.Category[]>([]);
const products = ref<Api.OrderEntry.Product[]>([]);
const categoryId = ref('all');
const productId = ref('');
const userinfo = ref('');
const batchMode = ref<'single' | 'batch'>('single');
const aiCorrection = ref(false);
const results = ref<Api.OrderEntry.QueryResult[]>([]);
const selections = ref<Api.OrderEntry.Selection[]>([]);
const balance = ref('0.00');
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
const allSelected = computed(() => totalCourses.value > 0 && selections.value.length === totalCourses.value);

const inputPlaceholder = computed(() => {
  return batchMode.value === 'batch'
    ? '请输入多行账号，每行一条（空格隔开）：\n北京大学 2024001122 Abc123456\n清华大学 2024009988 Pwd@1234'
    : '请输入下单信息：学校 账号 密码（空格分隔，如无学校可直接：账号 密码）';
});

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
    categories.value = (data.categories || []).filter(c => !c.name.includes('我的收藏') && !c.name.includes('收藏夹'));
    products.value = data.products;
    balance.value = data.balance;
    queryEnabled.value = data.queryEnabled;
    orderEnabled.value = data.orderEnabled;
    notice.value = data.notice;
    authStore.userInfo.balance = data.balance;
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
    authStore.userInfo.balance = data.balance;
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
  <div class="max-w-1180px mx-auto flex flex-col gap-12px p-10px sm:p-16px">
    <!-- 极简通透 Header：纯净标题 + 可用余额 -->
    <div class="flex flex-wrap items-center justify-between gap-12px bg-white dark:bg-dark-700 rounded-8px p-12px sm:px-16px border border-gray-100 dark:border-dark-600 shadow-xs">
      <div class="flex items-center gap-8px">
        <h1 class="text-16px font-bold text-gray-800 dark:text-gray-100">在线查课与下单</h1>
        <span class="text-12px text-gray-400">快速检索在学课程并一键交单</span>
      </div>
      <div class="flex items-center gap-6px text-13px">
        <span class="text-gray-500 dark:text-gray-400">可用余额:</span>
        <strong class="font-mono text-16px font-bold text-emerald-600 dark:text-emerald-400">¥ {{ balance }}</strong>
      </div>
    </div>

    <!-- 下单通知公告（若有则极简展示） -->
    <NAlert v-if="notice" type="info" :show-icon="true" class="rounded-8px text-12px leading-relaxed">
      <div class="whitespace-pre-wrap">{{ notice }}</div>
    </NAlert>

    <!-- 核心下单配置面板 -->
    <NCard :bordered="false" class="rounded-8px shadow-sm">
      <NSpin :show="catalogLoading">
        <NForm label-placement="top">
          <!-- 分类标签行 -->
          <NFormItem label="平台所属分类" class="mb-10px">
            <div class="w-full overflow-x-auto pb-2px">
              <NRadioGroup v-model:value="categoryId" size="small">
                <NSpace :wrap="false" :size="6">
                  <NRadioButton value="all">全部平台</NRadioButton>
                  <NRadioButton value="favorites">⭐ 收藏</NRadioButton>
                  <NRadioButton v-for="item in categories" :key="item.id" :value="item.id">
                    {{ item.name }}
                  </NRadioButton>
                </NSpace>
              </NRadioGroup>
            </div>
          </NFormItem>

          <!-- 平台选择与单行紧凑参数 -->
          <NFormItem label="选择网课平台" class="mb-10px">
            <div class="w-full flex flex-col gap-6px">
              <div class="w-full flex items-center gap-8px">
                <NSelect
                  v-model:value="productId"
                  class="flex-1"
                  filterable
                  clearable
                  :options="productOptions"
                  placeholder="搜索或选择下单平台"
                  :virtual-scroll="true"
                />
                <NTooltip>
                  <template #trigger>
                    <NButton circle secondary :disabled="!productId" @click="toggleFavorite">
                      <template #icon>
                        <span :class="favoriteIds.includes(productId) ? 'text-amber-500 text-15px' : 'text-gray-400 text-15px'">★</span>
                      </template>
                    </NButton>
                  </template>
                  {{ favoriteIds.includes(productId) ? '取消收藏' : '添加收藏' }}
                </NTooltip>
              </div>

              <!-- 选定平台优雅单行摘要（彻底消除厚重灰底大卡片） -->
              <div v-if="selectedProduct" class="flex flex-wrap items-center gap-8px text-12px text-gray-600 dark:text-gray-300 bg-slate-50 dark:bg-dark-600 px-10px py-6px rounded-6px border border-slate-100 dark:border-dark-500">
                <span class="font-medium text-gray-900 dark:text-gray-100">{{ selectedProduct.name }}</span>
                <span class="text-gray-300 dark:text-gray-600">·</span>
                <span>单价: <strong class="font-mono text-primary font-bold">¥{{ selectedProduct.price }}</strong>/门</span>
                <span class="text-gray-300 dark:text-gray-600">·</span>
                <span>查课费: <strong class="font-mono font-medium">¥{{ selectedProduct.queryFee }}</strong>/次</span>
                <template v-if="selectedProduct.content">
                  <span class="text-gray-300 dark:text-gray-600">·</span>
                  <span class="text-gray-400 truncate max-w-480px" :title="selectedProduct.content">说明: {{ selectedProduct.content }}</span>
                </template>
              </div>
            </div>
          </NFormItem>

          <!-- 学员账号信息输入区 -->
          <NFormItem class="mb-12px">
            <template #label>
              <div class="w-full flex flex-wrap items-center justify-between gap-8px">
                <span class="text-13px font-medium text-gray-700 dark:text-gray-200">
                  学员账号信息
                  <span class="text-11px text-gray-400 font-normal ml-6px">
                    {{ batchMode === 'batch' ? '每行一条：学校 账号 密码（空格隔开）' : '格式：学校 账号 密码（无学校可直接：账号 密码）' }}
                  </span>
                </span>
                <!-- 右上角紧凑功能条 -->
                <div class="flex items-center gap-8px text-12px font-normal">
                  <NRadioGroup v-model:value="batchMode" size="small">
                    <NRadioButton value="single">单条</NRadioButton>
                    <NRadioButton value="batch">多条批量</NRadioButton>
                  </NRadioGroup>
                  <div class="flex items-center gap-4px text-gray-500 select-none">
                    <NSwitch v-model:value="aiCorrection" size="small" />
                    <span class="text-11px">AI纠偏</span>
                  </div>
                  <NButton size="tiny" quaternary @click="fillSampleData">示例</NButton>
                  <NButton size="tiny" quaternary @click="userinfo = ''">清空</NButton>
                </div>
              </div>
            </template>

            <NInput
              v-model:value="userinfo"
              :type="batchMode === 'batch' ? 'textarea' : 'text'"
              :autosize="batchMode === 'batch' ? { minRows: 4, maxRows: 8 } : false"
              :placeholder="inputPlaceholder"
              class="font-mono text-13px"
              @blur="applyAiCorrection(false)"
            />
          </NFormItem>

          <!-- 标准扁平动作条 -->
          <div class="flex flex-wrap items-center justify-between gap-10px pt-10px border-t border-gray-100 dark:border-dark-600">
            <div class="flex items-center gap-8px">
              <NButton
                type="primary"
                size="medium"
                :loading="queryLoading"
                class="px-20px font-medium shadow-xs"
                @click="queryCourses"
              >
                在线查课
              </NButton>
              <NButton
                type="primary"
                secondary
                size="medium"
                :loading="submitLoading"
                :disabled="selections.length === 0"
                class="px-18px font-medium"
                @click="submitOrders"
              >
                提交订单 {{ selections.length > 0 ? `(${selections.length}门)` : '' }}
              </NButton>
              <NButton size="medium" quaternary @click="clearForm">
                重置
              </NButton>
            </div>
            <div v-if="selections.length > 0" class="text-13px text-gray-600 dark:text-gray-300 font-mono">
              已选 <strong class="text-primary font-bold">${selections.length}</strong> 门，预计扣费: <strong class="text-rose-500 font-bold text-15px">¥ ${estimatedSubmitCost}</strong>
            </div>
          </div>
        </NForm>
      </NSpin>
    </NCard>

    <!-- 查课结果卡片流（极简现代清单） -->
    <NCard v-if="results.length" :bordered="false" class="rounded-8px shadow-sm">
      <template #header>
        <div class="flex flex-wrap items-center justify-between gap-10px">
          <div class="flex items-center gap-8px">
            <span class="text-15px font-bold text-gray-800 dark:text-gray-100">查课结果</span>
            <span class="text-12px text-gray-500 font-mono">
              已勾选 <strong class="text-primary">{{ selections.length }}</strong> / {{ totalCourses }} 门
            </span>
          </div>
          <div class="flex items-center gap-6px">
            <NButton size="tiny" secondary @click="toggleSelectAll">
              {{ allSelected ? '取消全选' : '全选' }}
            </NButton>
            <NButton size="tiny" secondary type="primary" @click="selectOnlyOngoing">
              仅勾选开课中
            </NButton>
          </div>
        </div>
      </template>

      <!-- 账号列表手风琴 -->
      <NCollapse :default-expanded-names="results.map(item => item.userinfo)" class="flex flex-col gap-10px">
        <NCollapseItem
          v-for="result in results"
          :key="result.userinfo"
          :name="result.userinfo"
          class="rounded-6px border border-gray-100 bg-slate-50/50 p-4px dark:border-dark-600 dark:bg-dark-600/30"
        >
          <template #header>
            <div class="flex flex-wrap items-center gap-8px text-13px">
              <span class="font-medium text-gray-800 dark:text-gray-100">{{ result.userName || '学员' }}</span>
              <span class="font-mono text-11px text-gray-400">({{ result.userinfo }})</span>
              <NTag :type="result.courses.length > 0 ? 'success' : 'default'" size="tiny" round>
                {{ result.msg }} ({{ result.courses.length }} 门)
              </NTag>
            </div>
          </template>

          <div v-if="result.courses.length === 0" class="py-12px">
            <NEmpty :description="result.msg || '未查询到开课记录'" size="small" />
          </div>

          <!-- 极简课程卡片栅格 (无突兀切角，平整高级) -->
          <NGrid v-else cols="1 s:2 l:3" responsive="screen" :x-gap="10" :y-gap="10" class="mt-6px">
            <NGi v-for="course in result.courses" :key="`${result.userinfo}-${course.id || course.name}`">
              <div
                class="flex items-center gap-8px p-10px rounded-6px border transition-all cursor-pointer select-none"
                :class="
                  isSelected(result, course)
                    ? 'border-blue-400 bg-blue-50/60 dark:bg-blue-950/20 dark:border-blue-700'
                    : 'border-gray-200 bg-white hover:border-gray-300 dark:border-dark-500 dark:bg-dark-600'
                "
                @click="toggleCourse(!isSelected(result, course), result, course)"
              >
                <NCheckbox
                  :checked="isSelected(result, course)"
                  @click.stop
                  @update:checked="checked => toggleCourse(checked, result, course)"
                />
                <div class="flex-1 min-w-0">
                  <div class="text-13px font-medium text-gray-800 dark:text-gray-100 truncate" :title="course.name">
                    {{ course.name }}
                  </div>
                  <div class="mt-2px flex items-center justify-between text-11px text-gray-400 font-mono">
                    <span>{{ course.teacher || '默认' }} · {{ course.state || '开课中' }}</span>
                    <span v-if="selectedProduct" class="text-primary font-bold font-mono">
                      ¥{{ selectedProduct.price }}
                    </span>
                  </div>
                </div>
              </div>
            </NGi>
          </NGrid>
        </NCollapseItem>
      </NCollapse>

      <!-- 底部结算栏（极简浅色流） -->
      <div v-if="selections.length > 0" class="mt-14px flex flex-wrap items-center justify-between gap-10px pt-12px border-t border-gray-100 dark:border-dark-600">
        <div class="text-13px text-gray-600 dark:text-gray-300 font-mono">
          已选 <strong class="text-primary font-bold">${selections.length}</strong> 门，总计扣费：<strong class="text-rose-500 font-bold text-16px">¥ ${estimatedSubmitCost}</strong>
        </div>
        <div class="flex items-center gap-8px">
          <NButton quaternary size="small" @click="selections = []">清空选择</NButton>
          <NButton type="primary" size="small" :loading="submitLoading" class="px-16px font-medium" @click="submitOrders">
            确认提交下单
          </NButton>
        </div>
      </div>
    </NCard>
  </div>
</template>
