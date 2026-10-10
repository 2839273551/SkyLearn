<script setup lang="ts">
import { computed, h, onMounted, ref, watch } from 'vue';
import type { SelectOption } from 'naive-ui';
import {
  NAlert,
  NButton,
  NCheckbox,
  NEmpty,
  NInput,
  NSelect,
  NSpin,
  NSwitch
} from 'naive-ui';
import { fetchCourseQuery, fetchOrderCatalog, fetchOrderSubmit } from '@/service/api';
import { useAuthStore } from '@/store/modules/auth';

defineOptions({ name: 'Add' });

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

// 依分类过滤商品
const visibleProducts = computed(() => {
  if (categoryId.value === 'all') return products.value;
  return products.value.filter(item => item.categoryId === categoryId.value);
});

// 下拉选单选项
const productOptions = computed(() =>
  visibleProducts.value.map(item => ({
    label: item.name,
    value: item.id,
    price: item.price
  }))
);

// 渲染带价格提示的下拉选单项
function renderSelectOptionLabel(option: SelectOption) {
  return h('div', { class: 'flex items-center justify-between w-full py-1px' }, [
    h('span', { class: 'text-gray-800 dark:text-gray-200' }, String(option.label || '')),
    option.price ? h('span', { class: 'text-12px text-blue-600 dark:text-blue-400 font-mono ml-12px shrink-0 font-medium' }, `¥ ${option.price} 积分`) : null
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

// 格式化课程名称与进度（若 upstream 已经自带进度信息则不重复拼接进行中）
function formatCourseTitle(course: Api.OrderEntry.Course): string {
  const name = course.name || '';
  if (/【(?:课程)?进度[:：][^】]+】/.test(name)) {
    return name;
  }
  if (course.state && course.state.trim()) {
    const rawState = course.state.trim().replace(/^[【\[]+|[】\]]+$/g, '').trim();
    const progressText = rawState.includes('课程进度') ? rawState : `课程进度:${rawState}`;
    return `${name}【${progressText}】`;
  }
  return name;
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
    if (!productId.value && data.products.length > 0) {
      productId.value = data.products[0].id;
    }
  }
  catalogLoading.value = false;
}

// 切换分类
function handleCategorySelect(targetId: string) {
  categoryId.value = targetId;
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
    } else {
      window.$message?.warning('未查询到任何课程');
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

// 复制客服微信分享话术
function copyQueryInfo(result: Api.OrderEntry.QueryResult) {
  if (!result.courses || result.courses.length === 0) {
    window.$message?.warning('暂无可复制的课程');
    return;
  }
  let infoToCopy = "亲亲，我们已经将您账号的课程找好啦！\n" +
                   "请告诉我需要代看的课程序号【数字】，我们马上为您安排哈！\n" +
                   "------------------------\n";

  const selectedForAccount = selections.value.filter(s => s.userinfo === result.userinfo);
  const targetCourses = selectedForAccount.length > 0 ? selectedForAccount.map(s => s.course) : result.courses;

  targetCourses.forEach((c, idx) => {
    infoToCopy += `课程${idx + 1}：${c.name}\n`;
  });
  infoToCopy += "------------------------";

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
    // 保留 userinfo 输入栏内容，方便用户切换项目后继续使用该账号查课下单

    window.$notification?.success({
      title: '下单提交完成',
      content: `已成功提交 ${data.submitted} 笔订单，扣费 ¥${data.charged} 积分。账号信息已保留，您可直接切换项目继续查课下单！`,
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
  <div class="w-full max-w-1680px mx-auto p-12px sm:p-20px font-sans">
    <!-- 下单全站通知 -->
    <NAlert v-if="notice" type="warning" title="全站下单通知" :show-icon="true" class="mb-16px rounded-8px">
      <div class="whitespace-pre-wrap leading-relaxed text-13px">{{ notice }}</div>
    </NAlert>

    <!-- 左右分栏核心网格布局：桌面两栏等高对齐 (items-stretch)，移动端单列自适应堆叠 -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-16px items-stretch">
      <!-- ================= 左侧卡片：项目查询 ================= -->
      <div class="lg:col-span-6 bg-white dark:bg-dark-700 rounded-10px border border-gray-100 dark:border-dark-600 shadow-xs p-16px sm:p-24px flex flex-col justify-between h-full">
        <!-- 上半部分表单内容 -->
        <div class="flex-1 flex flex-col">
          <!-- 头部：标题与账户可用余额 (已按要求彻底去除批量开关) -->
          <div class="flex items-center justify-between pb-18px">
            <span class="text-18px font-bold text-gray-800 dark:text-gray-100 select-none">项目查询</span>
            <div class="text-13px text-gray-500 dark:text-gray-400">
              余额: <strong class="text-emerald-600 dark:text-emerald-400 font-bold font-mono text-15px">¥ {{ balance }}</strong>
            </div>
          </div>

          <NSpin :show="catalogLoading" class="flex-1 flex flex-col">
            <div class="flex-1 flex flex-col gap-18px">
              <!-- 渠道分类 Tab 列表 (文字标签 + 选中粗体黑色下划线) -->
              <div class="flex flex-wrap items-center gap-x-20px gap-y-12px border-b border-gray-100 dark:border-dark-600 pb-12px">
                <button
                  type="button"
                  class="relative pb-6px text-14px transition-colors cursor-pointer bg-transparent border-0 select-none whitespace-nowrap"
                  :class="
                    categoryId === 'all'
                      ? 'text-gray-900 font-bold dark:text-white'
                      : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 font-normal'
                  "
                  @click="handleCategorySelect('all')"
                >
                  全部
                  <span
                    v-if="categoryId === 'all'"
                    class="absolute bottom-0 left-0 right-0 h-2px bg-gray-900 dark:bg-white rounded-full transition-all"
                  ></span>
                </button>

                <button
                  v-for="item in categories"
                  :key="item.id"
                  type="button"
                  class="relative pb-6px text-14px transition-colors cursor-pointer bg-transparent border-0 select-none whitespace-nowrap"
                  :class="
                    categoryId === item.id
                      ? 'text-gray-900 font-bold dark:text-white'
                      : 'text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 font-normal'
                  "
                  @click="handleCategorySelect(item.id)"
                >
                  {{ item.name }}
                  <span
                    v-if="categoryId === item.id"
                    class="absolute bottom-0 left-0 right-0 h-2px bg-gray-900 dark:bg-white rounded-full transition-all"
                  ></span>
                </button>
              </div>

              <!-- 项目下拉框 -->
              <div>
                <NSelect
                  v-model:value="productId"
                  filterable
                  :options="productOptions"
                  placeholder="请选择下单项目"
                  :render-label="renderSelectOptionLabel"
                  class="w-full text-14px"
                />
                <!-- 单价与说明信息轻提示 -->
                <div v-if="selectedProduct" class="mt-8px text-12px text-blue-600 dark:text-blue-400 flex flex-wrap items-center gap-10px">
                  <span>单价: <strong class="font-bold">¥{{ selectedProduct.price }}</strong> 积分/门</span>
                  <span v-if="selectedProduct.queryFee && selectedProduct.queryFee !== '0.00'">查课扣费: <strong class="font-bold">¥{{ selectedProduct.queryFee }}</strong> 积分/账号</span>
                  <span v-if="selectedProduct.content" class="text-gray-400 dark:text-gray-500 truncate max-w-320px" :title="selectedProduct.content">
                    {{ selectedProduct.content }}
                  </span>
                </div>
              </div>

              <!-- 输入框区域 (放大文本框，无预设测试账号，单行/多行自动批量) -->
              <div class="flex-1 flex flex-col min-h-160px">
                <NInput
                  v-model:value="userinfo"
                  type="textarea"
                  :rows="7"
                  placeholder="请输入账号密码信息（多账号换行即可自动批量）&#10;支持格式：&#10;学校 账号 密码&#10;账号 密码"
                  class="w-full font-mono text-13px rounded-6px flex-1 min-h-150px"
                  clearable
                />
                <div class="mt-6px flex items-center justify-between text-12px text-gray-400 dark:text-gray-500 select-none">
                  <span>多账号换行即可批量查课（账号自动记忆）</span>
                  <span v-if="inputLines.length > 0">当前输入：<strong class="text-blue-600 dark:text-blue-400 font-bold">{{ inputLines.length }}</strong> 个账号</span>
                </div>
              </div>
            </div>
          </NSpin>
        </div>

        <!-- 底部操作按钮栏：与右侧卡片底部完美等高对齐 -->
        <div class="mt-18px pt-16px border-t border-gray-100 dark:border-dark-600 flex items-center justify-between">
          <NButton
            type="primary"
            size="medium"
            :loading="queryLoading"
            class="px-22px font-medium rounded-4px shadow-xs bg-blue-600 hover:bg-blue-700"
            @click="queryCourses"
          >
            <template #icon>
              <span class="text-14px">🔍</span>
            </template>
            查询课程
          </NButton>

          <NButton
            v-if="userinfo"
            size="small"
            secondary
            class="text-gray-500 hover:text-gray-700"
            @click="clearUserinfo"
          >
            清空输入
          </NButton>
        </div>
      </div>

      <!-- ================= 右侧卡片：查询结果 ================= -->
      <div class="lg:col-span-6 bg-white dark:bg-dark-700 rounded-10px border border-gray-100 dark:border-dark-600 shadow-xs p-16px sm:p-24px flex flex-col justify-between h-full">
        <!-- 上半部分内容 -->
        <div class="flex-1 flex flex-col">
          <!-- 头部：标题与展示ID开关 -->
          <div class="flex items-center justify-between pb-16px">
            <span class="text-18px font-bold text-gray-800 dark:text-gray-100 select-none">查询结果</span>
            <div class="flex items-center gap-6px select-none">
              <NSwitch v-model:value="showId" size="medium" />
              <span class="text-14px text-gray-700 dark:text-gray-300">展示ID</span>
            </div>
          </div>

          <!-- 关键字过滤输入框 -->
          <div class="mb-14px">
            <NInput
              v-model:value="filterKeyword"
              clearable
              placeholder="输入关键字过滤，空格分隔可多词匹配"
              class="w-full text-13px rounded-6px"
            />
          </div>

          <!-- 课程结果树状勾选流 -->
          <div class="flex-1 min-h-200px max-h-520px overflow-y-auto pr-4px">
            <!-- 空状态 -->
            <div v-if="!results.length" class="h-full flex items-center justify-center py-48px text-center text-gray-400 dark:text-gray-500 text-13px">
              <NEmpty description="暂无查询结果，请在左侧选择项目并输入账号后点击“查询课程”" size="small" />
            </div>

            <!-- 树状流 -->
            <div v-else class="flex flex-col gap-6px">
              <div
                v-for="result in filteredResults"
                :key="result.userinfo"
                class="flex flex-col"
              >
                <!-- 顶级：账号节点行 -->
                <div
                  class="flex items-center gap-8px py-7px px-4px rounded-4px hover:bg-gray-50 dark:hover:bg-dark-600/50 cursor-pointer select-none group transition-colors"
                  @click="toggleExpand(result.userinfo)"
                >
                  <!-- 展开/折叠三角箭头 -->
                  <span
                    class="text-12px text-gray-500 dark:text-gray-400 w-14px text-center shrink-0"
                  >
                    {{ isExpanded(result.userinfo) ? '▼' : '▶' }}
                  </span>

                  <!-- 账号全选复选框 -->
                  <NCheckbox
                    :checked="isAccountAllSelected(result)"
                    :indeterminate="isAccountPartiallySelected(result)"
                    :disabled="result.courses.length === 0"
                    @click.stop
                    @update:checked="checked => toggleAccountCourses(result, checked)"
                  />

                  <!-- 账号与学生信息文字 -->
                  <span class="text-14px text-gray-800 dark:text-gray-200 truncate flex-1">
                    {{ result.userinfo }}
                    <template v-if="result.userName"> - 学生姓名: {{ result.userName }}</template>
                    <span v-if="result.courses.length === 0" class="text-rose-500 text-12px ml-6px">
                      ({{ result.msg || '未查询到课程' }})
                    </span>
                  </span>

                  <!-- 客服话术一键复制按钮 -->
                  <button
                    v-if="result.courses.length > 0"
                    type="button"
                    class="opacity-0 group-hover:opacity-100 text-12px text-blue-600 dark:text-blue-400 hover:underline px-6px py-2px bg-transparent border-0 cursor-pointer transition-opacity shrink-0"
                    title="生成并复制微信客服话术"
                    @click.stop="copyQueryInfo(result)"
                  >
                    📋 复制话术
                  </button>
                </div>

                <!-- 子级：课程行 -->
                <div
                  v-show="isExpanded(result.userinfo)"
                  class="pl-32px flex flex-col gap-3px my-2px"
                >
                  <div
                    v-for="course in result.courses"
                    :key="`${result.userinfo}-${course.id || course.name}`"
                    class="flex items-center gap-8px py-5px px-4px rounded-4px hover:bg-gray-50 dark:hover:bg-dark-600/50 cursor-pointer select-none transition-colors"
                    @click="toggleCourse(!isSelected(result, course), result, course)"
                  >
                    <NCheckbox
                      :checked="isSelected(result, course)"
                      @click.stop
                      @update:checked="checked => toggleCourse(checked, result, course)"
                    />
                    <span class="text-14px text-gray-800 dark:text-gray-200">
                      {{ formatCourseTitle(course) }}<template v-if="showId && course.id">【ID: {{ course.id }}】</template>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- 底部提交与结算操作条：与左侧卡片底部等高对齐 -->
        <div
          class="mt-18px pt-16px border-t border-gray-100 dark:border-dark-600 flex flex-wrap items-center justify-between gap-12px"
        >
          <div class="flex items-center gap-8px text-13px text-gray-600 dark:text-gray-300">
            <span>
              已选 <strong class="text-blue-600 dark:text-blue-400 font-bold text-15px">{{ selections.length }}</strong> 门课程
            </span>
            <span v-if="selectedProduct && selections.length > 0">
              | 预计扣费: <strong class="text-rose-500 font-bold font-mono text-16px">¥ {{ estimatedSubmitCost }}</strong> 积分
            </span>
          </div>

          <div class="flex items-center gap-10px">
            <NButton size="small" secondary :disabled="results.length === 0" @click="toggleSelectAll">
              {{ allSelected ? '取消全选' : '全选所有' }}
            </NButton>
            <NButton
              type="primary"
              size="medium"
              :loading="submitLoading"
              :disabled="selections.length === 0"
              class="px-20px font-bold rounded-4px shadow-xs bg-blue-600 hover:bg-blue-700"
              @click="submitOrders"
            >
              🚀 提交订单 {{ selections.length > 0 ? `(${selections.length}门)` : '' }}
            </NButton>
          </div>
        </div>
      </div>
    </div>

    <!-- 移动端吸底便捷结算栏 (屏幕较小已勾选课程时在屏幕底部常驻展示，方便手机操作) -->
    <div
      v-if="selections.length > 0"
      class="fixed bottom-0 left-0 right-0 z-50 p-12px bg-white/95 dark:bg-dark-700/95 backdrop-blur border-t border-gray-200 dark:border-dark-500 shadow-2xl flex items-center justify-between lg:hidden"
    >
      <div class="text-13px text-gray-700 dark:text-gray-200">
        已选 <strong class="text-blue-600 dark:text-blue-400 font-bold">{{ selections.length }}</strong> 门
        <span class="text-rose-500 font-bold ml-6px font-mono">¥ {{ estimatedSubmitCost }} 积分</span>
      </div>
      <NButton
        type="primary"
        size="medium"
        :loading="submitLoading"
        class="px-18px font-bold rounded-4px bg-blue-600"
        @click="submitOrders"
      >
        立即提交订单
      </NButton>
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
</style>
