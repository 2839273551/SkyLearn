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
  NInput,
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
    label: `${item.name} → ${item.price} 积分`,
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
    window.$message?.warning('请先选择项目');
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
    if (showMessage) window.$message?.success('AI矫正：已自动提取规范格式');
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
    window.$message?.warning('请先选择项目并填写账号信息');
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

function isAccountAllSelected(result: Api.OrderEntry.QueryResult): boolean {
  if (!result.courses || result.courses.length === 0) return false;
  return result.courses.every(c => isSelected(result, c));
}

function toggleAccountCourses(result: Api.OrderEntry.QueryResult) {
  const isAll = isAccountAllSelected(result);
  if (isAll) {
    selections.value = selections.value.filter(s => s.userinfo !== result.userinfo);
  } else {
    result.courses.forEach(c => {
      if (!isSelected(result, c)) {
        selections.value.push({
          userinfo: result.userinfo,
          userName: result.userName,
          course: c
        });
      }
    });
  }
}

// 1:1复刻 add1.php 的客服分享话术复制
function copyQueryInfo(result: Api.OrderEntry.QueryResult) {
  if (!result.courses || result.courses.length === 0) {
    window.$message?.warning('暂无可复制的课程');
    return;
  }
  let infoToCopy = "亲亲，我们已经将您账号的课程找好啦！\n" +
                   "请告诉我需要代看的课程序号【数字】，我们马上为您安排哈！\n" +
                   "------------------------\n";

  // 如果该账号下有选中的课程，优先列出选中的课程
  const selectedForAccount = selections.value.filter(s => s.userinfo === result.userinfo);
  const targetCourses = selectedForAccount.length > 0 ? selectedForAccount.map(s => s.course) : result.courses;

  targetCourses.forEach((c, idx) => {
    infoToCopy += `课程${idx + 1}：${c.name}\n`;
  });
  infoToCopy += "------------------------";

  navigator.clipboard.writeText(infoToCopy);
  window.$message?.success('查询话术已成功复制到剪贴板，快去发给客户吧！');
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
    // 提交后清空已选课程、查询结果列表和输入框，保持已选商品不变方便连续下单
    selections.value = [];
    results.value = [];
    userinfo.value = '';

    window.$notification?.success({
      title: '下单提交完成',
      content: `已成功提交 ${data.submitted} 笔订单，扣费 ¥${data.charged} 积分`,
      duration: 5000
    });
  }
  submitLoading.value = false;
}

function clearForm() {
  userinfo.value = '';
  results.value = [];
  selections.value = [];
  window.$message?.info('已重置面板');
}

watch(categoryId, () => {
  // 每个分类点开默认选中该分类下的第一个商品，绝不留空
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
  <div class="max-w-1140px mx-auto flex flex-col gap-16px p-12px sm:p-18px font-sans">
    <!-- 下单特别通知 -->
    <NAlert v-if="notice" type="warning" title="全站下单特别通知" :show-icon="true" class="rounded-8px">
      <div class="whitespace-pre-wrap leading-relaxed text-13px">{{ notice }}</div>
    </NAlert>

    <!-- 主面板：创建订单 (对标 add1.php 经典纯净面板) -->
    <NCard :bordered="false" class="rounded-8px shadow-sm bg-white dark:bg-dark-700">
      <template #header>
        <div class="flex items-center justify-between w-full">
          <div class="flex items-center gap-8px">
            <span class="text-16px font-bold text-gray-800 dark:text-gray-100">创建订单</span>
          </div>
          <div class="flex items-center gap-6px text-13px">
            <span class="text-gray-500 dark:text-gray-400">可用余额:</span>
            <strong class="font-mono text-16px font-bold text-emerald-600 dark:text-emerald-400">¥ {{ balance }} 积分</strong>
          </div>
        </div>
      </template>

      <NSpin :show="catalogLoading">
        <div class="flex flex-col gap-16px text-14px py-4px">
          <!-- 1. 选择项目 (add1.php 风格) -->
          <div class="flex flex-col sm:flex-row sm:items-center gap-8px sm:gap-16px">
            <label class="w-80px font-medium text-gray-700 dark:text-gray-200 shrink-0">选择项目</label>
            <div class="flex-1 min-w-0">
              <NSelect
                v-model:value="productId"
                filterable
                clearable
                :options="productOptions"
                placeholder="请先选择分类再搜索项目下单"
                :virtual-scroll="true"
                class="w-full"
              />
            </div>
          </div>

          <!-- 2. 渠道分类 (add1.php 经典扁平小按钮行) -->
          <div class="flex flex-col sm:flex-row sm:items-start gap-8px sm:gap-16px">
            <label class="w-80px font-medium text-gray-700 dark:text-gray-200 shrink-0 pt-4px">渠道分类</label>
            <div class="flex-1 flex flex-wrap items-center gap-6px">
              <button
                type="button"
                class="px-12px py-5px rounded-4px text-13px font-medium transition-colors cursor-pointer border"
                :class="
                  categoryId === 'all'
                    ? 'bg-primary text-white border-primary shadow-xs'
                    : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-200 dark:bg-dark-600 dark:border-dark-500 dark:text-gray-200'
                "
                @click="categoryId = 'all'"
              >
                全部分类
              </button>
              <button
                type="button"
                class="px-12px py-5px rounded-4px text-13px font-medium transition-colors cursor-pointer border flex items-center gap-3px"
                :class="
                  categoryId === 'favorites'
                    ? 'bg-amber-500 text-white border-amber-500 shadow-xs'
                    : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-200 dark:bg-dark-600 dark:border-dark-500 dark:text-gray-200'
                "
                @click="categoryId = 'favorites'"
              >
                <span>⭐</span> 我的收藏
              </button>
              <button
                v-for="item in categories"
                :key="item.id"
                type="button"
                class="px-12px py-5px rounded-4px text-13px font-medium transition-colors cursor-pointer border"
                :class="
                  categoryId === item.id
                    ? 'bg-primary text-white border-primary shadow-xs'
                    : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-200 dark:bg-dark-600 dark:border-dark-500 dark:text-gray-200'
                "
                @click="categoryId = item.id"
              >
                {{ item.name }}
              </button>
            </div>
          </div>

          <!-- 3. 项目介绍 / 说明 (add1.php 经典蓝色说明文字) -->
          <div v-if="selectedProduct" class="flex flex-col sm:flex-row sm:items-start gap-8px sm:gap-16px">
            <label class="w-80px font-medium text-gray-700 dark:text-gray-200 shrink-0">项目介绍</label>
            <div class="flex-1 flex flex-col gap-3px text-13px">
              <div class="text-blue-600 dark:text-blue-400 font-medium">
                下单单价：<strong class="font-mono text-14px font-bold">¥ {{ selectedProduct.price }}</strong> 积分 / 门
                <span class="mx-6px text-gray-300">|</span>
                查课扣费：<strong class="font-mono text-14px font-bold">¥ {{ selectedProduct.queryFee }}</strong> 积分 / 账号
              </div>
              <div v-if="selectedProduct.content" class="text-gray-500 dark:text-gray-400 text-12px leading-relaxed">
                平台考核要求与说明：{{ selectedProduct.content }}
              </div>
            </div>
          </div>

          <!-- 4. 信息填写 (add1.php 经典文本域与顶部微操作) -->
          <div class="flex flex-col sm:flex-row sm:items-start gap-8px sm:gap-16px">
            <label class="w-80px font-medium text-gray-700 dark:text-gray-200 shrink-0 pt-4px">信息填写</label>
            <div class="flex-1 flex flex-col gap-8px">
              <!-- 操作小按钮行 -->
              <div class="flex flex-wrap items-center justify-between gap-8px">
                <div class="flex items-center gap-6px">
                  <NButton
                    size="tiny"
                    :type="favoriteIds.includes(productId) ? 'warning' : 'default'"
                    secondary
                    :disabled="!productId"
                    @click="toggleFavorite"
                  >
                    {{ favoriteIds.includes(productId) ? '★ 移除收藏' : '⭐ 收藏项目' }}
                  </NButton>
                  <NButton size="tiny" secondary type="info" @click="fillSampleData">
                    填入示例
                  </NButton>
                  <NButton size="tiny" secondary @click="userinfo = ''">
                    清空输入
                  </NButton>
                </div>
                <!-- AI纠偏紧凑开关 -->
                <div class="flex items-center gap-4px text-12px text-gray-500 select-none">
                  <NSwitch v-model:value="aiCorrection" size="small" />
                  <span>AI 格式纠偏</span>
                </div>
              </div>

              <!-- 大文本域 (1:1 对标 add1.php 占位提示与样式) -->
              <NInput
                v-model:value="userinfo"
                type="textarea"
                :rows="5"
                placeholder="信息填写方式：&#10;账号 密码（中间用空格分隔）&#10;学校 账号 密码（中间用空格分隔）&#10;多账号下单必须换行，务必一行一条信息"
                class="font-mono text-13px rounded-6px"
                @blur="applyAiCorrection(false)"
              />
            </div>
          </div>

          <!-- 5. 动作操作栏 (add1.php 经典三个按钮排布) -->
          <div class="flex flex-col sm:flex-row sm:items-center gap-12px pt-10px border-t border-gray-100 dark:border-dark-600">
            <div class="w-80px shrink-0 hidden sm:block"></div>
            <div class="flex flex-wrap items-center gap-10px">
              <NButton
                type="primary"
                size="medium"
                :loading="queryLoading"
                class="px-22px font-bold rounded-6px shadow-xs"
                @click="queryCourses"
              >
                🔍 经典查询
              </NButton>

              <NButton
                type="info"
                size="medium"
                :loading="submitLoading"
                :disabled="selections.length === 0"
                class="px-22px font-bold rounded-6px shadow-xs"
                @click="submitOrders"
              >
                🚀 提交课程 {{ selections.length > 0 ? `(${selections.length}门)` : '' }}
              </NButton>

              <NButton
                size="medium"
                secondary
                class="px-16px rounded-6px"
                @click="clearForm"
              >
                重置面板
              </NButton>
            </div>

            <!-- 选课金额轻提示 -->
            <div v-if="selections.length > 0" class="sm:ml-auto text-13px text-gray-600 dark:text-gray-300 font-mono">
              已选 <strong class="text-primary font-bold">${selections.length}</strong> 门 | 预计扣费：<strong class="text-rose-500 font-bold text-16px">¥ ${estimatedSubmitCost}</strong> 积分
            </div>
          </div>
        </div>
      </NSpin>
    </NCard>

    <!-- 结果面板：查询结果 (1:1 还原 add1.php 手风琴 + 微信话术一键复制) -->
    <NCard v-if="results.length" :bordered="false" class="rounded-8px shadow-sm bg-white dark:bg-dark-700">
      <template #header>
        <div class="flex items-center justify-between w-full">
          <div class="flex items-center gap-8px">
            <span class="text-16px font-bold text-gray-800 dark:text-gray-100">查询结果</span>
            <NTag size="tiny" type="primary" round>
              已勾选 {{ selections.length }} / {{ totalCourses }} 门
            </NTag>
          </div>
          <NButton size="tiny" secondary @click="toggleSelectAll">
            {{ allSelected ? '取消全选' : '全选所有' }}
          </NButton>
        </div>
      </template>

      <!-- 账号列表手风琴流 (对标 add1.php) -->
      <NCollapse :default-expanded-names="results.map(item => item.userinfo)" class="flex flex-col gap-12px">
        <NCollapseItem
          v-for="result in results"
          :key="result.userinfo"
          :name="result.userinfo"
          class="rounded-8px border border-gray-100 bg-slate-50/60 p-4px dark:border-dark-600 dark:bg-dark-600/30 overflow-hidden"
        >
          <!-- 手风琴头部 (包含 add1.php 同款【课程全选】和【复制课程】) -->
          <template #header>
            <div class="flex flex-wrap items-center justify-between w-full gap-8px pr-8px">
              <div class="flex items-center gap-8px text-13px">
                <strong class="text-gray-900 dark:text-gray-100">{{ result.userName || '学员' }}</strong>
                <span class="font-mono text-gray-500">{{ result.userinfo }}</span>
                <span v-if="result.courses.length > 0" class="text-emerald-600 font-bold text-12px">
                  查询成功 ({{ result.courses.length }} 门)
                </span>
                <span v-else class="text-rose-600 font-bold text-12px">
                  {{ result.msg || '查询失败' }}
                </span>
              </div>
              <!-- 头部操作按钮 (add1.php 核心精髓：全选 + 微信客服话术复制) -->
              <div class="flex items-center gap-8px" @click.stop>
                <NButton
                  size="tiny"
                  type="warning"
                  secondary
                  :disabled="result.courses.length === 0"
                  class="rounded-4px text-12px"
                  @click="toggleAccountCourses(result)"
                >
                  {{ isAccountAllSelected(result) ? '取消本号' : '课程全选' }}
                </NButton>

                <NButton
                  size="tiny"
                  type="error"
                  secondary
                  :disabled="result.courses.length === 0"
                  class="rounded-4px text-12px"
                  title="生成并复制微信客服话术"
                  @click="copyQueryInfo(result)"
                >
                  📋 复制课程
                </NButton>
              </div>
            </div>
          </template>

          <div v-if="result.courses.length === 0" class="py-16px text-center">
            <NEmpty :description="result.msg || '未查询到任何开课记录'" size="small" />
          </div>

          <!-- 课程列表流 (1:1 对标 add1.php resource-item 列表风格) -->
          <div v-else class="flex flex-col gap-8px mt-6px px-4px pb-4px">
            <div
              v-for="course in result.courses"
              :key="`${result.userinfo}-${course.id || course.name}`"
              class="flex items-center gap-10px p-10px rounded-6px border transition-all cursor-pointer select-none bg-white dark:bg-dark-600"
              :class="
                isSelected(result, course)
                  ? 'border-blue-400 bg-blue-50/50 dark:border-blue-700'
                  : 'border-gray-200 hover:border-gray-300 dark:border-dark-500'
              "
              @click="toggleCourse(!isSelected(result, course), result, course)"
            >
              <NCheckbox
                :checked="isSelected(result, course)"
                @click.stop
                @update:checked="checked => toggleCourse(checked, result, course)"
              />
              <div class="flex-1 min-w-0 flex flex-col gap-2px">
                <div class="text-14px font-medium text-gray-900 dark:text-gray-100 truncate" :title="course.name">
                  {{ course.name }}
                </div>
                <div class="text-12px text-gray-400 font-mono truncate">
                  {{ course.id || '课程ID未知' }} · {{ course.teacher || '教师未知' }} · {{ course.state || '开课中' }}
                </div>
              </div>
              <div v-if="selectedProduct" class="text-right shrink-0">
                <strong class="font-mono text-14px font-bold text-primary">
                  {{ selectedProduct.price }} 积分
                </strong>
              </div>
            </div>
          </div>
        </NCollapseItem>
      </NCollapse>

      <!-- 底部吸底结算栏 -->
      <div v-if="selections.length > 0" class="mt-16px flex flex-wrap items-center justify-between gap-12px rounded-6px bg-slate-50 dark:bg-dark-600 p-12px border border-gray-200 dark:border-dark-500">
        <div class="text-14px text-gray-700 dark:text-gray-200 font-mono">
          已勾选 <strong class="text-primary font-bold text-16px">${selections.length}</strong> 门课程
          <span class="mx-8px text-gray-300">|</span>
          预计结算总计：<strong class="text-rose-500 font-bold text-18px">¥ ${estimatedSubmitCost}</strong> 积分
        </div>
        <div class="flex items-center gap-8px">
          <NButton size="small" secondary @click="selections = []">清空勾选</NButton>
          <NButton type="primary" size="medium" :loading="submitLoading" class="px-20px font-bold rounded-6px" @click="submitOrders">
            立即提交并扣费
          </NButton>
        </div>
      </div>
    </NCard>
  </div>
</template>
