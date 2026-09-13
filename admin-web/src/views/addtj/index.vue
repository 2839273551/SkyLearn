<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import {
  NAlert,
  NButton,
  NCard,
  NDivider,
  NEmpty,
  NInput,
  NModal,
  NSelect,
  NSpace,
  NSpin,
  NStatistic,
  NTag,
  NTooltip
} from 'naive-ui';
import { fetchNocheckOptions, submitOrderNocheck } from '@/service/api';
import { useAppStore } from '@/store/modules/app';

defineOptions({ name: 'Addtj' });

const appStore = useAppStore();

const router = useRouter();
const loading = ref(false);
const submitting = ref(false);

const classOptions = ref<Api.Addtj.ClassOption[]>([]);
const userMoney = ref(0);
const userRate = ref(1);

const selectedCid = ref<string | null>(null);
const rawContent = ref('');

// 选中的网课详情
const currentClass = computed(() => {
  if (!selectedCid.value) return null;
  return classOptions.value.find(c => c.cid === selectedCid.value) || null;
});

// 下拉选项分类分组
const selectOptions = computed(() => {
  return classOptions.value.map(c => ({
    label: `${c.name} (¥${c.price}/门)`,
    value: c.cid,
    group: c.fenlei_name
  }));
});

// 实时智能解析文本行
interface ParsedItem {
  school: string;
  user: string;
  pass: string;
  kcnames: string[];
}

const parsedResults = computed(() => {
  const lines = rawContent.value.split(/[\r\n]+/);
  const list: ParsedItem[] = [];

  for (const rawLine of lines) {
    const line = rawLine.trim().replace(/\s+/g, ' ');
    if (!line) continue;

    const parts = line.split(' ');
    if (parts.length < 2) continue;

    let school = '自动识别';
    let user = '';
    let pass = '';
    const kcnames: string[] = [];

    // 若首段包含汉字，通常为学校
    if (/[\u4e00-\u9fa5]/.test(parts[0])) {
      school = parts[0];
      user = parts[1] || '';
      pass = parts[2] || '';
      for (let i = 3; i < parts.length; i++) {
        if (parts[i]) kcnames.push(parts[i]);
      }
    } else {
      user = parts[0];
      pass = parts[1] || '';
      for (let i = 2; i < parts.length; i++) {
        if (parts[i]) kcnames.push(parts[i]);
      }
    }

    if (!user || !pass) continue;
    if (!kcnames.length) {
      kcnames.push('全部课程');
    }

    list.push({ school, user, pass, kcnames });
  }

  return list;
});

// 计算课程单数与预计金额
const totalOrdersCount = computed(() => {
  return parsedResults.value.reduce((acc, cur) => acc + cur.kcnames.length, 0);
});

const estimatedCost = computed(() => {
  if (!currentClass.value) return 0;
  return Number((totalOrdersCount.value * currentClass.value.price).toFixed(2));
});

const isBalanceEnough = computed(() => {
  return userMoney.value >= estimatedCost.value;
});

// 填充示例
function fillExample() {
  rawContent.value = `北京大学 2024001122 Abc123456 马克思主义基本原理 毛泽东思想概论
清华大学 2024009988 Pwd@1234 大学英语(三)
2024112233 12345678 计算机网络基础`;
  window.$message?.info('已填入格式示例');
}

// 清空
function clearContent() {
  rawContent.value = '';
}

// 格式纠偏
function cleanFormat() {
  const lines = rawContent.value.split(/[\r\n]+/);
  const cleaned = lines
    .map(l => l.trim().replace(/\s+/g, ' '))
    .filter(Boolean)
    .join('\n');
  rawContent.value = cleaned;
  window.$message?.success('已完成格式清洗');
}

// 初始化加载配置
async function loadOptions() {
  loading.value = true;
  const { data, error } = await fetchNocheckOptions();
  loading.value = false;

  if (!error && data) {
    classOptions.value = data.classes;
    userMoney.value = data.user_money;
    userRate.value = data.user_rate;
    if (data.classes.length > 0 && !selectedCid.value) {
      selectedCid.value = data.classes[0].cid;
    }
  }
}

// 提交订单
async function handleSubmit() {
  if (!selectedCid.value) {
    window.$message?.warning('请先选择目标网课平台');
    return;
  }
  if (!totalOrdersCount.value) {
    window.$message?.warning('未解析出有效的账号课程信息，请检查输入格式');
    return;
  }
  if (!isBalanceEnough.value) {
    window.$message?.error(`余额不足！本次提交需 ${estimatedCost.value} 元，当前余额 ${userMoney.value} 元`);
    return;
  }

  window.$dialog?.warning({
    title: '确认提交交单',
    content: `已解析出 ${parsedResults.value.length} 个账号，共 ${totalOrdersCount.value} 门课程订单。预计扣费 ¥${estimatedCost.value}，确认立即交单吗？`,
    positiveText: '确认交单',
    negativeText: '取消',
    onPositiveClick: async () => {
      submitting.value = true;
      const { data, error } = await submitOrderNocheck({
        cid: Number(selectedCid.value),
        content: rawContent.value
      });
      submitting.value = false;

      if (!error && data) {
        window.$message?.success(
          `交单成功！已创建 ${data.success_count} 笔订单，扣费 ¥${data.deducted_money}`
        );
        userMoney.value = data.remain_money;
        rawContent.value = '';
      }
    }
  });
}

onMounted(() => {
  loadOptions();
});
</script>

<template>
  <div class="flex flex-col gap-14px p-10px sm:p-16px">
    <NAlert type="info" title="无查提交 / 批量直接交单说明" class="rounded-8px">
      适合无需在线查课、或仅需批量排队提交的学习平台。系统将自动解析录入的账号密码与课程名称，按所选平台单价自动扣除账户余额并推送上游。
    </NAlert>

    <div class="grid grid-cols-1 gap-16px lg:grid-cols-3">
      <!-- 左侧录入区域 (占 2 列) -->
      <NCard title="交单信息录入" :bordered="false" class="rounded-12px shadow-sm lg:col-span-2">
        <NSpin :show="loading">
          <div class="flex flex-col gap-16px">
            <!-- 平台选择 -->
            <div>
              <div class="mb-6px flex items-center justify-between">
                <label class="text-14px font-bold text-gray-700 dark:text-gray-200">
                  目标网课平台：
                </label>
                <span v-if="currentClass" class="text-12px text-primary font-mono font-medium">
                  当前平台单价：¥{{ currentClass.price }} / 门
                </span>
              </div>
              <NSelect
                v-model:value="selectedCid"
                :options="selectOptions"
                filterable
                placeholder="搜索或选择网课平台"
                class="w-full"
              />
              <p v-if="currentClass?.content" class="mt-6px text-12px text-gray-500 bg-gray-50 dark:bg-dark-600 p-8px rounded-6px">
                平台提示：{{ currentClass.content }}
              </p>
            </div>

            <NDivider style="margin: 4px 0" />

            <!-- 文本录入 -->
            <div>
              <div class="mb-8px flex flex-wrap items-center justify-between gap-8px">
                <div>
                  <label class="text-14px font-bold text-gray-700 dark:text-gray-200">
                    账号密码与课程清单（一行一条）：
                  </label>
                  <p class="text-12px text-gray-400">
                    支持格式：<code class="font-mono text-primary">账号 密码 课程名</code> 或 <code class="font-mono text-primary">学校 账号 密码 课程1 课程2</code>
                  </p>
                </div>
                <div class="flex items-center gap-6px">
                  <NButton size="tiny" secondary @click="fillExample">填充示例</NButton>
                  <NButton size="tiny" secondary @click="cleanFormat">格式清洗</NButton>
                  <NButton size="tiny" secondary type="warning" @click="clearContent">清空</NButton>
                </div>
              </div>

              <NInput
                v-model:value="rawContent"
                type="textarea"
                :rows="12"
                placeholder="请输入批量交单数据，格式如：&#10;北京大学 2024001122 123456 马克思主义基本原理&#10;清华大学 2024009988 888888 大学英语&#10;2024112233 mypassword 计算机网络基础"
                class="font-mono text-13px leading-relaxed"
              />
            </div>
          </div>
        </NSpin>
      </NCard>

      <!-- 右侧核算与交单面板 (占 1 列) -->
      <div class="flex flex-col gap-16px">
        <NCard title="订单费用与预检核算" :bordered="false" class="rounded-12px shadow-sm">
          <div class="flex flex-col gap-14px">
            <!-- 账户可用余额 -->
            <div class="flex items-center justify-between rounded-8px bg-slate-50 p-12px dark:bg-dark-600 border border-slate-200/60 dark:border-dark-500">
              <span class="text-13px text-gray-500">账户可用余额</span>
              <strong class="text-18px font-bold font-mono text-emerald-600">
                ¥ {{ userMoney.toFixed(2) }}
              </strong>
            </div>

            <!-- 实时统计指标 -->
            <div class="grid grid-cols-2 gap-10px">
              <div class="rounded-8px border border-gray-100 p-10px dark:border-dark-600">
                <NStatistic label="有效识别账号" :value="parsedResults.length">
                  <template #suffix><span class="text-12px text-gray-400">个</span></template>
                </NStatistic>
              </div>
              <div class="rounded-8px border border-gray-100 p-10px dark:border-dark-600">
                <NStatistic label="待提交课程数" :value="totalOrdersCount">
                  <template #suffix><span class="text-12px text-primary">门</span></template>
                </NStatistic>
              </div>
            </div>

            <!-- 预计费用对比 -->
            <div class="rounded-8px bg-primary/5 p-12px border border-primary/20 flex flex-col gap-6px">
              <div class="flex items-center justify-between">
                <span class="text-13px text-gray-600 dark:text-gray-300">单门课程费用：</span>
                <span class="font-mono font-bold">¥ {{ currentClass ? currentClass.price : '0.00' }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-13px text-gray-600 dark:text-gray-300">预计扣除总额：</span>
                <strong class="font-mono text-18px text-primary font-bold">¥ {{ estimatedCost.toFixed(2) }}</strong>
              </div>
              <div class="flex items-center justify-between text-11px mt-2px pt-4px border-t border-primary/10">
                <span>扣费后剩余预估：</span>
                <span
                  class="font-mono font-bold"
                  :class="isBalanceEnough ? 'text-emerald-500' : 'text-rose-500'"
                >
                  ¥ {{ (userMoney - estimatedCost).toFixed(2) }}
                </span>
              </div>
            </div>

            <!-- 提交按钮 -->
            <NButton
              type="primary"
              size="large"
              block
              :disabled="!totalOrdersCount || !isBalanceEnough"
              :loading="submitting"
              class="font-bold text-15px"
              @click="handleSubmit"
            >
              {{ isBalanceEnough ? `立即交单 (${totalOrdersCount} 门课程)` : '当前余额不足以支付' }}
            </NButton>

            <NButton
              quaternary
              block
              size="small"
              @click="router.push('/list')"
            >
              查看订单汇总进度 ➔
            </NButton>
          </div>
        </NCard>

        <!-- 实时解析预览列表 -->
        <NCard title="格式预检清单" size="small" :bordered="false" class="rounded-12px shadow-sm">
          <div v-if="!parsedResults.length" class="py-20px">
            <NEmpty description="暂无已解析的账号课程数据" />
          </div>
          <div v-else class="flex flex-col gap-8px max-h-320px overflow-y-auto pr-4px">
            <div
              v-for="(item, idx) in parsedResults"
              :key="idx"
              class="rounded-6px bg-gray-50 p-8px text-12px dark:bg-dark-600 border border-gray-100 dark:border-dark-500"
            >
              <div class="flex items-center justify-between">
                <span class="font-bold font-mono text-primary">{{ item.user }}</span>
                <NTag size="tiny" type="info">{{ item.school }}</NTag>
              </div>
              <div class="mt-4px text-gray-500 text-11px flex flex-wrap gap-4px">
                <span v-for="(kc, kidx) in item.kcnames" :key="kidx" class="rounded bg-white px-4px py-1px border dark:bg-dark-500">
                  {{ kc }}
                </span>
              </div>
            </div>
          </div>
        </NCard>
      </div>
    </div>
  </div>
</template>
