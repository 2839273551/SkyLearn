<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
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
    if (showMessage) window.$message?.success('AI矫正：已自动提取账号密码信息');
  } else if (showMessage) {
    window.$message?.info('内容格式正确，无需矫正');
  }
}

async function queryCourses() {
  if (!productId.value || inputLines.value.length === 0) {
    window.$message?.warning('所有项目不能为空');
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
    if (successCount > 0) window.$message?.success(`查询完成，${successCount}个账号查询成功`);
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
  const index = selections.value.findIndex(item => selectionKey(item.userinfo, item.course) === key);

  if (checked && index < 0) {
    selections.value.push({ userinfo: result.userinfo, userName: result.userName, course });
  } else if (!checked && index >= 0) {
    selections.value.splice(index, 1);
  }
}

function toggleSelectAll() {
  if (allSelected.value) {
    selections.value = [];
    return;
  }

  selections.value = results.value.flatMap(result =>
    result.courses.map(course => ({ userinfo: result.userinfo, userName: result.userName, course }))
  );
}

async function submitOrders() {
  if (!productId.value || selections.value.length === 0) {
    window.$message?.warning('请先选择课程');
    return;
  }
  if (!orderEnabled.value) {
    window.$message?.error('管理员已关闭下单功能');
    return;
  }

  submitLoading.value = true;
  const { data, error } = await fetchOrderSubmit(productId.value, selections.value);
  if (!error && data) {
    balance.value = data.balance;
    freeAdd.value = data.freeAdd;
    authStore.userInfo.balance = data.balance;
    authStore.userInfo.freeAdd = data.freeAdd;
    results.value = [];
    selections.value = [];
    window.$notification?.success({
      title: '订单提交成功',
      content: `成功提交${data.submitted}门课程，扣除${data.charged}积分`,
      duration: 4000
    });
  }
  submitLoading.value = false;
}

function clearForm() {
  userinfo.value = '';
  results.value = [];
  selections.value = [];
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
  <NSpace vertical :size="16">
    <NCard :bordered="false" class="card-wrapper">
      <div class="flex flex-wrap items-center justify-between gap-12px">
        <div>
          <h2 class="text-22px font-600">订单提交</h2>
          <NText depth="3">余额：{{ balance }} 积分</NText>
          <template v-if="freeOrderEnabled && freeAdd > 0">
            <NDivider vertical />
            <NText type="success">剩余下单次数：{{ freeAdd }} 次</NText>
          </template>
        </div>
        <div class="flex items-center gap-8px">
          <NSwitch v-model:value="aiCorrection" />
          <NText>AI矫正</NText>
          <NTooltip>
            <template #trigger><SvgIcon icon="ph:question" class="text-18px text-gray" /></template>
            自动从客户发来的文字中提取学校、账号和密码；如识别不正确可关闭后重新查询。
          </NTooltip>
        </div>
      </div>
    </NCard>

    <NAlert v-if="notice" type="warning" title="下单通知" :show-icon="true">
      <div class="whitespace-pre-wrap">{{ notice }}</div>
    </NAlert>

    <NCard title="订单信息" :bordered="false" class="card-wrapper">
      <NSpin :show="catalogLoading">
        <NForm label-placement="top">
          <NFormItem label="项目分类">
            <NRadioGroup v-model:value="categoryId" size="small">
              <NSpace>
                <NRadioButton value="all">全部项目</NRadioButton>
                <NRadioButton value="favorites">收藏项目</NRadioButton>
                <NRadioButton v-for="item in categories" :key="item.id" :value="item.id">
                  {{ item.name }}
                </NRadioButton>
              </NSpace>
            </NRadioGroup>
          </NFormItem>

          <NFormItem label="选择平台">
            <div class="w-full flex items-center gap-8px">
              <NSelect
                v-model:value="productId"
                class="flex-1"
                filterable
                clearable
                :options="productOptions"
                placeholder="点击选择下单平台，也可直接输入关键字搜索"
                :virtual-scroll="true"
              />
              <NButton circle secondary :disabled="!productId" @click="toggleFavorite">
                <template #icon>
                  <SvgIcon
                    icon="ph:star-fill"
                    :class="favoriteIds.includes(productId) ? 'text-amber' : 'text-gray'"
                  />
                </template>
              </NButton>
            </div>
          </NFormItem>

          <NGrid cols="1 m:3" responsive="screen" :x-gap="16">
            <NGi span="1 m:2">
              <NFormItem label="信息填写">
                <div class="w-full flex items-start gap-8px">
                  <NInput
                    v-model:value="userinfo"
                    :type="batchMode ? 'textarea' : 'text'"
                    :autosize="batchMode ? { minRows: 4, maxRows: 12 } : false"
                    :placeholder="
                      batchMode
                        ? '每行一条信息，例如：\n学校1 账号1 密码1\n学校2 账号2 密码2'
                        : '请输入下单信息：学校 账号 密码'
                    "
                    @blur="applyAiCorrection(false)"
                  />
                  <NSpace vertical>
                    <NTooltip>
                      <template #trigger>
                        <NButton circle @click="toggleInputMode">
                          <template #icon>
                            <SvgIcon :icon="batchMode ? 'ph:user' : 'ph:notebook'" />
                          </template>
                        </NButton>
                      </template>
                      {{ batchMode ? '切换到单条输入' : '切换到批量输入' }}
                    </NTooltip>
                    <NTooltip v-if="aiCorrection">
                      <template #trigger>
                        <NButton circle type="success" secondary @click="applyAiCorrection(true)">
                          <template #icon><SvgIcon icon="ph:magic-wand" /></template>
                        </NButton>
                      </template>
                      手动执行AI矫正
                    </NTooltip>
                  </NSpace>
                </div>
              </NFormItem>
            </NGi>
            <NGi>
              <NFormItem label="网课说明">
                <NInput
                  type="textarea"
                  :rows="4"
                  readonly
                  :value="selectedProduct ? `[对接CID=${selectedProduct.id}] ${selectedProduct.content}` : '请选择商品查看说明'"
                />
              </NFormItem>
            </NGi>
          </NGrid>

          <NAlert v-if="selectedProduct" class="mb-16px" type="info" :show-icon="false">
            商品价格：{{ selectedProduct.price }}积分 / 门；查课费用：{{ selectedProduct.queryFee }}积分 / 个账号
          </NAlert>

          <NSpace>
            <NButton type="primary" :loading="queryLoading" @click="queryCourses">
              <template #icon><SvgIcon icon="ph:magnifying-glass" /></template>
              立即查询
            </NButton>
            <NPopconfirm
              positive-text="确认提交"
              negative-text="取消"
              @positive-click="submitOrders"
            >
              <template #trigger>
                <NButton type="primary" :loading="submitLoading" :disabled="selections.length === 0">
                  <template #icon><SvgIcon icon="ph:check-circle" /></template>
                  提交订单（{{ selections.length }}）
                </NButton>
              </template>
              确认提交已选的{{ selections.length }}门课程吗？
            </NPopconfirm>
            <NButton type="warning" secondary @click="clearForm">
              <template #icon><SvgIcon icon="ph:trash" /></template>
              清空数据
            </NButton>
          </NSpace>
        </NForm>
      </NSpin>
    </NCard>

    <NCard v-if="results.length" :bordered="false" class="card-wrapper">
      <template #header>
        <div class="flex items-center gap-12px">
          <span>查询结果</span>
          <NButton size="tiny" type="primary" secondary @click="toggleSelectAll">
            {{ allSelected ? '取消全选' : '全选' }}
          </NButton>
          <NText depth="3">已选择 {{ selections.length }} / {{ totalCourses }} 门</NText>
        </div>
      </template>

      <NCollapse :default-expanded-names="results.map(item => item.userinfo)">
        <NCollapseItem v-for="result in results" :key="result.userinfo" :name="result.userinfo">
          <template #header>
            <div class="flex flex-wrap items-center gap-8px">
              <NText strong>{{ result.userName || '未识别姓名' }}</NText>
              <NText depth="3">{{ result.userinfo }}</NText>
              <NTag :type="result.courses.length > 0 ? 'success' : 'error'" size="small" round>
                {{ result.msg }}
              </NTag>
            </div>
          </template>

          <NEmpty v-if="result.courses.length === 0" :description="result.msg" />
          <NGrid v-else cols="1 s:2 l:3" responsive="screen" :x-gap="12" :y-gap="12">
            <NGi v-for="course in result.courses" :key="`${result.userinfo}-${course.id || course.name}`">
              <NCard size="small" :bordered="true" hoverable>
                <NCheckbox
                  :checked="isSelected(result, course)"
                  @update:checked="checked => toggleCourse(checked, result, course)"
                >
                  <div class="pl-4px">
                    <NText strong>{{ course.name }}</NText>
                    <div class="mt-6px text-12px text-gray">
                      <span>[ ID: {{ course.id || '无' }} ]</span>
                      <span class="ml-8px">[ 老师: {{ course.teacher || '无' }} ]</span>
                    </div>
                    <NText :type="course.state ? 'warning' : 'success'" class="mt-4px block text-12px">
                      {{ course.state || '开课中' }}
                    </NText>
                  </div>
                </NCheckbox>
              </NCard>
            </NGi>
          </NGrid>
        </NCollapseItem>
      </NCollapse>
    </NCard>

    <NCard title="注意事项" :bordered="false" class="card-wrapper">
      <NTimeline>
        <NTimelineItem type="error" content="请务必查看项目下单须知和说明，防止出现错误！" />
        <NTimelineItem type="warning" content="同商品重复下单，请修改密码后再下！" />
        <NTimelineItem type="info" content="默认下单格式为学校、账号、密码（空格分开）！" />
        <NTimelineItem type="success" content="查课出问题请及时通过工单反馈！" />
      </NTimeline>
    </NCard>
  </NSpace>
</template>

<style scoped></style>
