<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { fetchCourseQuery, fetchOrderCatalog, fetchOrderSubmit } from '@/service/api';
import { useAuthStore } from '@/store/modules/auth';

defineOptions({ name: 'Addpl' });

const FAVORITES_KEY = 'COURSE_ADMIN_order_favorites';
const router = useRouter();
const authStore = useAuthStore();

const catalogLoading = ref(false);
const queryLoading = ref(false);
const submitLoading = ref(false);

const categories = ref<Api.OrderEntry.Category[]>([]);
const products = ref<Api.OrderEntry.Product[]>([]);
const categoryId = ref('all');
const productId = ref('');
const userinfo = ref('');
const results = ref<Api.OrderEntry.QueryResult[]>([]);
const selections = ref<Api.OrderEntry.Selection[]>([]);

const balance = ref('0.00');
const queryEnabled = ref(true);
const orderEnabled = ref(true);
const notice = ref('');
const favoriteIds = ref<string[]>(loadFavorites());
const expandedNames = ref<string[]>([]);

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
    label: `${item.name} (${item.price}积分)`,
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

const estimatedCost = computed(() => {
  if (!selectedProduct.value) return '0.00';
  const price = Number(selectedProduct.value.price) || 0;
  return (price * selections.value.length).toFixed(2);
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

function toggleFavorite() {
  if (!productId.value) {
    window.$message?.warning('请先选择平台项目');
    return;
  }
  const id = productId.value;
  if (favoriteIds.value.includes(id)) {
    favoriteIds.value = favoriteIds.value.filter(item => item !== id);
    window.$message?.info('已取消收藏该项目');
  } else {
    favoriteIds.value.push(id);
    window.$message?.success('已成功收藏该项目');
  }
  saveFavorites();
}

async function loadCatalog() {
  catalogLoading.value = true;
  const { data, error } = await fetchOrderCatalog();
  if (!error && data) {
    categories.value = data.categories;
    products.value = data.products;
    balance.value = data.balance;
    queryEnabled.value = data.queryEnabled;
    orderEnabled.value = data.orderEnabled;
    notice.value = data.notice;
    authStore.userInfo.balance = data.balance;
  }
  catalogLoading.value = false;
}

function replaceFullWidthSymbols(value: string) {
  const replacements: Record<string, string> = {
    '！': '!', '？': '?', '：': ':', '；': ';', '，': ',', '。': '.',
    '（': '(', '）': ')', '＠': '@', '＃': '#', '％': '%', '＆': '&',
    '＊': '*', '＋': '+', '－': '-', '＝': '=', '＿': '_', '｜': '|',
    '～': '~', '／': '/', '　': ' '
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

function applyAiCorrection() {
  if (!userinfo.value.trim()) {
    window.$message?.warning('请先输入或粘贴账号信息');
    return;
  }
  const corrected = userinfo.value
    .split(/\r?\n/)
    .map(correctSingleLine)
    .filter(Boolean)
    .join('\n');

  if (corrected !== userinfo.value.trim()) {
    userinfo.value = corrected;
    window.$message?.success('已完成智能格式清洗与标准化');
  } else {
    window.$message?.info('文本格式规范，无需清洗');
  }
}

function insertSample() {
  userinfo.value = '北京大学 20240101 Pwd123456\n清华大学 20240102 Pwd654321\n13800138000 Password888';
  window.$message?.info('已填入批量账号格式示例');
}

async function queryCourses() {
  if (!productId.value) {
    window.$message?.warning('请先选择要查询的项目平台');
    return;
  }
  if (inputLines.value.length === 0) {
    window.$message?.warning('请输入至少一行待查账号信息');
    return;
  }
  if (!queryEnabled.value) {
    window.$message?.error('管理员已关闭查课功能');
    return;
  }

  queryLoading.value = true;
  selections.value = [];
  results.value = [];

  const { data, error } = await fetchCourseQuery(productId.value, inputLines.value);
  queryLoading.value = false;

  if (!error && data) {
    results.value = data.results;
    balance.value = data.balance;
    authStore.userInfo.balance = data.balance;
    expandedNames.value = data.results.map((_, idx) => String(idx));
    window.$message?.success(`批量查课完成！共查询 ${data.results.length} 个账号`);
  }
}

function isSelected(userinfo: string, courseId: string, courseName: string) {
  return selections.value.some(
    item => item.userinfo === userinfo && item.course.id === courseId && item.course.name === courseName
  );
}

function toggleCourse(userinfo: string, userName: string, course: Api.OrderEntry.Course) {
  const index = selections.value.findIndex(
    item => item.userinfo === userinfo && item.course.id === course.id && item.course.name === course.name
  );
  if (index >= 0) {
    selections.value.splice(index, 1);
  } else {
    selections.value.push({ userinfo, userName, course });
  }
}

function isAccountAllSelected(result: Api.OrderEntry.QueryResult) {
  if (!result.courses.length) return false;
  return result.courses.every(c => isSelected(result.userinfo, c.id, c.name));
}

function toggleAccountAll(result: Api.OrderEntry.QueryResult) {
  const allSel = isAccountAllSelected(result);
  if (allSel) {
    selections.value = selections.value.filter(s => s.userinfo !== result.userinfo);
  } else {
    for (const c of result.courses) {
      if (!isSelected(result.userinfo, c.id, c.name)) {
        selections.value.push({ userinfo: result.userinfo, userName: result.userName, course: c });
      }
    }
  }
}

function toggleSelectAll() {
  if (allSelected.value) {
    selections.value = [];
    return;
  }
  const next: Api.OrderEntry.Selection[] = [];
  for (const result of results.value) {
    for (const course of result.courses) {
      next.push({ userinfo: result.userinfo, userName: result.userName, course });
    }
  }
  selections.value = next;
}

function selectUnfinishedOnly() {
  const next: Api.OrderEntry.Selection[] = [];
  for (const result of results.value) {
    for (const course of result.courses) {
      const state = (course.state || '').trim();
      const isDone = state.includes('100%') || state.includes('已完成') || state.includes('满分');
      if (!isDone) {
        next.push({ userinfo: result.userinfo, userName: result.userName, course });
      }
    }
  }
  selections.value = next;
  window.$message?.info(`已自动勾选 ${next.length} 门未完成课程`);
}

async function submitOrders() {
  if (!productId.value) {
    window.$message?.warning('请先选择项目平台');
    return;
  }
  if (!orderEnabled.value) {
    window.$message?.error('管理员已关闭下单功能');
    return;
  }
  if (selections.value.length === 0) {
    window.$message?.warning('请先勾选需要提交的课程');
    return;
  }

  submitLoading.value = true;
  const { data, error } = await fetchOrderSubmit(productId.value, selections.value);
  submitLoading.value = false;

  if (!error && data) {
    window.$message?.success(`批量下单成功！本次成功提交 ${data.submitted} 笔订单，实扣 ¥ ${data.charged}`);
    balance.value = data.balance;
    authStore.userInfo.balance = data.balance;
    selections.value = [];
    results.value = [];
    userinfo.value = '';
  }
}

watch(visibleProducts, items => {
  if (!items.some(item => item.id === productId.value)) {
    productId.value = items[0]?.id || '';
  }
});

onMounted(() => {
  loadCatalog();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <!-- 下单公告提示 -->
    <NAlert v-if="notice" type="info" title="全站下单公告" :show-icon="true" closable>
      <div class="whitespace-pre-wrap leading-relaxed">{{ notice }}</div>
    </NAlert>

    <!-- 顶部资产与状态横幅 -->
    <div class="flex flex-wrap items-center justify-between gap-12px rounded-8px bg-white p-14px shadow-sm dark:bg-dark-700">
      <div class="flex flex-wrap items-center gap-16px">
        <div class="flex items-center gap-8px">
          <SvgIcon icon="ph:wallet" class="text-22px text-success" />
          <span class="text-13px text-gray-500">账户余额：</span>
          <span class="text-18px font-bold text-success">¥ {{ balance }}</span>
        </div>
      </div>
      <div class="flex items-center gap-8px">
        <NButton size="small" quaternary type="primary" @click="router.push('/charge')">
          余额充值
        </NButton>
        <NButton size="small" quaternary type="info" @click="router.push('/list')">
          我的订单
        </NButton>
      </div>
    </div>

    <!-- 主体双栏布局 -->
    <NGrid cols="1 m:2" responsive="screen" :x-gap="16" :y-gap="16">
      <!-- 左栏：项目选择与批量账号录入 -->
      <NGi>
        <NCard title="批量查课设置与账号导入" :bordered="false" class="h-full rounded-8px shadow-sm">
          <template #header-extra>
            <NButton size="small" quaternary @click="toggleFavorite">
              <template #icon>
                <SvgIcon
                  :icon="favoriteIds.includes(productId) ? 'ph:star-fill' : 'ph:star'"
                  :class="favoriteIds.includes(productId) ? 'text-warning' : 'text-gray-400'"
                />
              </template>
              {{ favoriteIds.includes(productId) ? '已收藏' : '收藏项目' }}
            </NButton>
          </template>

          <div class="flex flex-col gap-16px">
            <!-- 分类切换 -->
            <div>
              <label class="mb-6px block text-13px font-medium text-gray-600 dark:text-gray-300">
                选择分类类目：
              </label>
              <div class="flex flex-wrap gap-8px">
                <NButton
                  size="small"
                  :type="categoryId === 'all' ? 'primary' : 'default'"
                  :secondary="categoryId !== 'all'"
                  @click="categoryId = 'all'"
                >
                  全部项目
                </NButton>
                <NButton
                  v-if="favoriteIds.length > 0"
                  size="small"
                  :type="categoryId === 'favorites' ? 'warning' : 'default'"
                  :secondary="categoryId !== 'favorites'"
                  @click="categoryId = 'favorites'"
                >
                  我的收藏 ({{ favoriteIds.length }})
                </NButton>
                <NButton
                  v-for="cat in categories"
                  :key="cat.id"
                  size="small"
                  :type="categoryId === cat.id ? 'primary' : 'default'"
                  :secondary="categoryId !== cat.id"
                  @click="categoryId = cat.id"
                >
                  {{ cat.name }}
                </NButton>
              </div>
            </div>

            <!-- 项目选择 -->
            <div>
              <label class="mb-6px block text-13px font-medium text-gray-600 dark:text-gray-300">
                选择网课项目平台：
              </label>
              <NSelect
                v-model:value="productId"
                :options="productOptions"
                placeholder="输入关键字快速搜索课程平台..."
                filterable
                clearable
                :loading="catalogLoading"
              />
            </div>

            <!-- 项目资费卡片说明 -->
            <div v-if="selectedProduct" class="rounded-8px border border-primary/15 bg-primary/4 p-12px text-13px">
              <div class="flex items-center justify-between font-medium">
                <span class="text-primary font-bold">{{ selectedProduct.name }}</span>
                <span class="text-error font-bold">单价：¥ {{ selectedProduct.price }} / 门</span>
              </div>
              <div class="mt-4px flex items-center justify-between text-12px text-gray-500">
                <span>查课费用：¥ {{ selectedProduct.queryFee }} / 次 ({{ selectedProduct.noCheck ? '免查课直提' : '需查课' }})</span>
                <span v-if="selectedProduct.noun" class="font-mono text-gray-400">代码: {{ selectedProduct.noun }}</span>
              </div>
              <p v-if="selectedProduct.content" class="m-0 mt-6px text-12px text-gray-500 leading-relaxed">
                {{ selectedProduct.content }}
              </p>
            </div>

            <!-- 批量账号录入文本框 -->
            <div>
              <div class="mb-6px flex items-center justify-between">
                <div class="flex items-center gap-8px">
                  <label class="text-13px font-medium text-gray-600 dark:text-gray-300">
                    批量填写账号信息：
                  </label>
                  <NTag type="info" size="tiny" round>已录入 {{ inputLines.length }} 行</NTag>
                </div>
                <div class="flex items-center gap-8px">
                  <NButton text size="tiny" type="primary" @click="applyAiCorrection">
                    AI 格式清洗
                  </NButton>
                  <NButton text size="tiny" @click="insertSample">
                    示例格式
                  </NButton>
                  <NButton text size="tiny" type="error" @click="userinfo = ''">
                    清空
                  </NButton>
                </div>
              </div>

              <NInput
                v-model:value="userinfo"
                type="textarea"
                :rows="8"
                placeholder="每行输入一个账号，格式：&#10;学校 账号 密码（有学校填写）&#10;手机号 密码（无学校填写）&#10;支持直接粘贴表格或多行文本"
                class="font-mono text-13px"
              />
            </div>

            <!-- 操作按钮 -->
            <div class="pt-4px">
              <NButton
                type="primary"
                size="large"
                block
                :loading="queryLoading"
                :disabled="!queryEnabled || inputLines.length === 0"
                @click="queryCourses"
              >
                <template #icon><SvgIcon icon="ph:magnifying-glass" /></template>
                开始批量查课 ({{ inputLines.length }} 个账号)
              </NButton>
            </div>
          </div>
        </NCard>
      </NGi>

      <!-- 右栏：批量查课结果与多选提交工作区 -->
      <NGi>
        <NCard title="批量查课结果与下单" :bordered="false" class="h-full rounded-8px shadow-sm flex flex-col">
          <template #header-extra>
            <div v-if="results.length > 0" class="flex items-center gap-8px">
              <NButton size="small" type="primary" secondary @click="toggleSelectAll">
                {{ allSelected ? '全不选' : '全部全选' }}
              </NButton>
              <NButton size="small" secondary @click="selectUnfinishedOnly">
                仅选未完成
              </NButton>
            </div>
          </template>

          <!-- 空结果状态 -->
          <div v-if="results.length === 0" class="flex flex-col items-center justify-center py-60px text-center text-gray-400">
            <div class="size-64px flex-center rd-1/2 bg-gray-100 text-gray-400 dark:bg-dark-600">
              <SvgIcon icon="ph:queue" class="text-36px" />
            </div>
            <p class="m-0 mt-14px text-14px font-medium">暂无查课结果</p>
            <p class="m-0 mt-4px max-w-320px text-12px text-gray-400 leading-normal">
              请在左侧选择对应课程平台，并粘贴需要批量查询的学生账号信息后点击【开始批量查课】。
            </p>
          </div>

          <!-- 查课结果列表 -->
          <div v-else class="flex flex-col gap-12px">
            <!-- 结果摘要条 -->
            <div class="flex items-center justify-between rounded-6px bg-gray-50 p-10px text-13px dark:bg-dark-600">
              <span>查询总账号：<strong>{{ results.length }}</strong> 个 | 发现课程：<strong>{{ totalCourses }}</strong> 门</span>
              <span class="text-primary font-bold">已选: {{ selections.length }} 门</span>
            </div>

            <!-- 分账号折叠列表 -->
            <NCollapse v-model:expanded-names="expandedNames" arrow-placement="right">
              <NCollapseItem
                v-for="(result, idx) in results"
                :key="idx"
                :name="String(idx)"
                class="mb-8px rounded-8px border border-gray-100 p-8px dark:border-dark-400"
              >
                <template #header>
                  <div class="flex items-center gap-10px">
                    <NTag :type="result.code === 1 ? 'success' : 'error'" size="small" round>
                      {{ result.code === 1 ? '成功' : '失败' }}
                    </NTag>
                    <span class="font-bold text-gray-800 dark:text-gray-200">{{ result.userName || '未知学生' }}</span>
                    <span class="font-mono text-12px text-gray-400">{{ result.userinfo }}</span>
                    <span v-if="result.code === 1" class="text-12px text-success">({{ result.courses.length }} 门课)</span>
                    <span v-else class="text-12px text-error">({{ result.msg }})</span>
                  </div>
                </template>

                <template #header-extra>
                  <NButton
                    v-if="result.courses.length > 0"
                    size="tiny"
                    quaternary
                    type="primary"
                    @click.stop="toggleAccountAll(result)"
                  >
                    {{ isAccountAllSelected(result) ? '本账号全不选' : '本账号全选' }}
                  </NButton>
                </template>

                <div v-if="result.courses.length === 0" class="py-8px text-center text-12px text-gray-400">
                  {{ result.msg || '该账号未查询到课程' }}
                </div>

                <div v-else class="flex flex-col gap-6px pt-4px">
                  <div
                    v-for="course in result.courses"
                    :key="course.id + course.name"
                    class="flex cursor-pointer items-center justify-between rounded-6px p-8px transition-colors hover:bg-gray-100/70 dark:hover:bg-dark-500"
                    :class="isSelected(result.userinfo, course.id, course.name) ? 'bg-primary/8 dark:bg-primary/15' : 'bg-gray-50/50 dark:bg-dark-600'"
                    @click="toggleCourse(result.userinfo, result.userName, course)"
                  >
                    <div class="flex items-center gap-8px">
                      <NCheckbox
                        :checked="isSelected(result.userinfo, course.id, course.name)"
                        @click.stop="toggleCourse(result.userinfo, result.userName, course)"
                      />
                      <div class="flex flex-col">
                        <span class="font-medium text-13px text-gray-800 dark:text-gray-100">{{ course.name }}</span>
                        <div class="flex items-center gap-8px text-11px text-gray-400">
                          <span v-if="course.id">[ID: {{ course.id }}]</span>
                          <span v-if="course.teacher">教师: {{ course.teacher }}</span>
                        </div>
                      </div>
                    </div>
                    <div class="text-right">
                      <NTag size="small" :type="course.state && (course.state.includes('100') || course.state.includes('完成')) ? 'success' : 'info'">
                        {{ course.state || '学习中' }}
                      </NTag>
                    </div>
                  </div>
                </div>
              </NCollapseItem>
            </NCollapse>

            <!-- 底部提交下单栏 -->
            <div class="mt-12px flex items-center justify-between rounded-8px border border-primary/20 bg-primary/6 p-12px dark:bg-dark-600">
              <div class="flex flex-col">
                <span class="text-13px">已勾选：<strong class="text-primary">{{ selections.length }}</strong> 门课程</span>
                <span class="text-12px text-gray-500">预估结算费用：<strong class="text-error">¥ {{ estimatedCost }}</strong></span>
              </div>
              <NButton
                type="primary"
                size="large"
                class="px-24px"
                :loading="submitLoading"
                :disabled="!orderEnabled || selections.length === 0"
                @click="submitOrders"
              >
                立即批量下单 ({{ selections.length }})
              </NButton>
            </div>
          </div>
        </NCard>
      </NGi>
    </NGrid>
  </div>
</template>

<style scoped></style>
