<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import {
  NButton,
  NCard,
  NDescriptions,
  NDescriptionsItem,
  NEmpty,
  NGi,
  NGrid,
  NList,
  NListItem,
  NProgress,
  NSpace,
  NSpin,
  NTag,
  NText,
  NThing
} from 'naive-ui';
import { fetchDashboard, fetchGglistList, fetchUserSignIn } from '@/service/api';
import { useAuthStore } from '@/store/modules/auth';
import { useEcharts } from '@/hooks/common/echarts';

defineOptions({ name: 'Main' });

const router = useRouter();
const authStore = useAuthStore();
const loading = ref(false);
const signingIn = ref(false);

const summary = ref<Api.Dashboard.Summary>({
  orderTotal: 0,
  todayOrders: 0,
  runningOrders: 0,
  completedOrders: 0,
  userTotal: 0,
  balance: '0.00',
  announcement: '',
  trend: { dates: [], counts: [] },
  distribution: []
});

const noticeList = ref<any[]>([]);

// 比例计算
const completedPercent = computed(() => {
  if (!summary.value.orderTotal) return 0;
  return Math.min(100, Math.round((summary.value.completedOrders / summary.value.orderTotal) * 100));
});

const runningPercent = computed(() => {
  if (!summary.value.orderTotal) return 0;
  return Math.min(100, Math.round((summary.value.runningOrders / summary.value.orderTotal) * 100));
});

const todayPercent = computed(() => {
  if (!summary.value.orderTotal) return 0;
  return Math.min(100, Math.round((summary.value.todayOrders / summary.value.orderTotal) * 100));
});

const cards = computed(() => [
  {
    label: '今日订单',
    value: summary.value.todayOrders,
    icon: 'ph:calendar-check',
    color: '#2563eb',
    subText: `今日活跃新增占比 ${todayPercent.value}%`,
    tag: '今日实时',
    tagType: 'info' as const,
    percent: todayPercent.value
  },
  {
    label: '全部订单',
    value: summary.value.orderTotal,
    icon: 'ph:list-checks',
    color: '#7c3aed',
    subText: '平台累计接单总吞吐',
    tag: '历史沉淀',
    tagType: 'warning' as const,
    percent: 100
  },
  {
    label: '进行中',
    value: summary.value.runningOrders,
    icon: 'ph:spinner-gap',
    color: '#d97706',
    subText: `在跑队列占比 ${runningPercent.value}%`,
    tag: '调度活跃',
    tagType: 'warning' as const,
    percent: runningPercent.value
  },
  {
    label: '已完成',
    value: summary.value.completedOrders,
    icon: 'ph:check-circle',
    color: '#059669',
    subText: `结课交付达成率 ${completedPercent.value}%`,
    tag: '归档交付',
    tagType: 'success' as const,
    percent: completedPercent.value
  }
]);

// 1. 近7日订单趋势折线渐变图
const { domRef: trendDomRef, updateOptions: updateTrendOptions } = useEcharts(() => ({
  tooltip: {
    trigger: 'axis',
    axisPointer: {
      type: 'line',
      lineStyle: {
        color: '#3b82f6',
        width: 1,
        type: 'dashed'
      }
    }
  },
  grid: {
    left: '2%',
    right: '4%',
    bottom: '4%',
    top: '12%',
    containLabel: true
  },
  xAxis: {
    type: 'category',
    boundaryGap: false,
    data: ['09/10', '09/11', '09/12', '09/13', '09/14', '09/15', '09/16'],
    axisLine: { lineStyle: { color: '#cbd5e1' } },
    axisLabel: { color: '#64748b', fontSize: 11 }
  },
  yAxis: {
    type: 'value',
    minInterval: 1,
    axisLine: { show: false },
    splitLine: { lineStyle: { type: 'dashed', color: '#f1f5f9' } },
    axisLabel: { color: '#64748b', fontSize: 11 }
  },
  series: [
    {
      name: '订单提交量',
      type: 'line',
      smooth: 0.35,
      symbol: 'circle',
      symbolSize: 6,
      itemStyle: { color: '#3b82f6' },
      lineStyle: { width: 3, color: '#3b82f6' },
      areaStyle: {
        color: {
          type: 'linear',
          x: 0,
          y: 0,
          x2: 0,
          y2: 1,
          colorStops: [
            { offset: 0, color: 'rgba(59, 130, 246, 0.32)' },
            { offset: 1, color: 'rgba(59, 130, 246, 0.02)' }
          ]
        }
      },
      data: [0, 0, 0, 0, 0, 0, 0]
    }
  ]
}));

const PROJECT_PALETTE = [
  '#3b82f6', // 科技蓝
  '#10b981', // 翡翠绿
  '#8b5cf6', // 优雅紫
  '#f59e0b', // 琥珀橙
  '#06b6d4', // 蓝青色
  '#ec4899', // 霓虹粉
  '#14b8a6', // 青绿
  '#f43f5e', // 玫瑰红
  '#6366f1', // 靛青
  '#84cc16'  // 青柠绿
];

// 2. 网课各项目出单分布环形图 (全自动按项目动态聚合，新增项目自适应统计)
const { domRef: pieDomRef, updateOptions: updatePieOptions } = useEcharts(() => ({
  color: PROJECT_PALETTE,
  tooltip: {
    trigger: 'item',
    formatter: '{b}: <b>{c}</b> 单 ({d}%)'
  },
  legend: {
    type: 'scroll',
    bottom: '2%',
    left: 'center',
    icon: 'circle',
    itemGap: 10,
    textStyle: { color: '#64748b', fontSize: 11 }
  },
  series: [
    {
      name: '项目出单量',
      type: 'pie',
      radius: ['40%', '68%'],
      center: ['50%', '42%'],
      avoidLabelOverlap: false,
      itemStyle: {
        borderRadius: 6,
        borderColor: '#fff',
        borderWidth: 2
      },
      label: {
        show: false,
        position: 'center'
      },
      emphasis: {
        label: {
          show: true,
          fontSize: 14,
          fontWeight: 'bold',
          formatter: '{b}\n{c} 单'
        }
      },
      labelLine: { show: false },
      data: [{ value: 0, name: '暂无项目出单' }]
    }
  ]
}));

async function handleSignIn() {
  signingIn.value = true;
  const { data, error } = await fetchUserSignIn();
  signingIn.value = false;
  if (!error && data) {
    authStore.userInfo.hasSignedIn = true;
    authStore.userInfo.balance = data.balance;
  }
}

async function loadData() {
  loading.value = true;
  const [dashRes, ggRes] = await Promise.all([
    fetchDashboard(),
    fetchGglistList()
  ]);
  loading.value = false;

  if (!dashRes.error && dashRes.data) {
    summary.value = dashRes.data;

    // 驱动图表 1：折线图
    if (dashRes.data.trend?.dates?.length) {
      updateTrendOptions(opts => {
        opts.xAxis = {
          ...opts.xAxis,
          data: dashRes.data.trend?.dates || []
        };
        if (opts.series && opts.series[0]) {
          opts.series[0].data = dashRes.data.trend?.counts || [];
        }
        return opts;
      });
    }

    // 驱动图表 2：各网课项目出单分布环形图 (双重色彩赋能，保障图例与扇区色彩鲜明)
    if (dashRes.data.distribution?.length) {
      updatePieOptions(opts => {
        opts.color = PROJECT_PALETTE;
        if (opts.series && opts.series[0]) {
          opts.series[0].data = (dashRes.data.distribution || []).map((item, idx) => ({
            name: item.name,
            value: item.value,
            itemStyle: {
              color: PROJECT_PALETTE[idx % PROJECT_PALETTE.length]
            }
          }));
        }
        return opts;
      });
    }
  }

  if (!ggRes.error && ggRes.data) {
    noticeList.value = ggRes.data.list;
  }
}

onMounted(loadData);
</script>

<template>
  <div class="flex flex-col gap-16px p-10px sm:p-16px">
    <!-- 顶部欢迎与操作横幅 -->
    <NCard :bordered="false" class="rounded-12px shadow-sm border border-gray-100 dark:border-dark-600 bg-white dark:bg-dark-700">
      <div class="flex flex-wrap items-center justify-between gap-16px">
        <div>
          <NText depth="3" class="text-12px tracking-wider uppercase font-semibold text-primary">
            {{ authStore.userInfo.siteName || '网课管理中心' }} · 工作台
          </NText>
          <h1 class="mt-4px text-22px sm:text-24px font-bold text-gray-800 dark:text-gray-100">
            你好，{{ authStore.userInfo.displayName || authStore.userInfo.userName }}
          </h1>
          <p class="text-13px text-gray-400 mt-2px">
            欢迎登录控制台，在此一站式查看全站系统通告、核心资产与业务运行看板。
          </p>
        </div>
        <NSpace align="center" :size="10">
          <NButton
            v-if="authStore.userInfo.qdkg"
            :type="authStore.userInfo.hasSignedIn ? 'default' : 'error'"
            :disabled="Boolean(authStore.userInfo.hasSignedIn)"
            :loading="signingIn"
            @click="handleSignIn"
          >
            {{ authStore.userInfo.hasSignedIn ? '今日已签到' : '每日签到' }}
          </NButton>
          <NButton type="primary" @click="router.push('/add')">马上学习</NButton>
          <NButton @click="router.push('/userlist')">代理管理</NButton>
          <NButton @click="router.push('/list')">订单汇总</NButton>
        </NSpace>
      </div>
    </NCard>

    <!-- 站长置顶公告（一等公民呈现） -->
    <NCard
      v-if="summary.announcement"
      title="📢 站长置顶公告"
      :bordered="false"
      class="rounded-12px shadow-sm border border-amber-300/80 bg-amber-50/50 dark:bg-dark-700 dark:border-dark-500"
    >
      <template #header-extra>
        <NTag size="small" type="warning" round>置顶通告</NTag>
      </template>
      <div class="whitespace-pre-wrap text-14px sm:text-15px text-gray-800 dark:text-gray-100 leading-relaxed font-medium">
        {{ summary.announcement }}
      </div>
    </NCard>

    <!-- 核心业务统计指标卡片（带微图进度） -->
    <NSpin :show="loading">
      <NGrid cols="1 s:2 m:4" responsive="screen" :x-gap="16" :y-gap="16">
        <NGi v-for="card in cards" :key="card.label">
          <NCard :bordered="false" class="rounded-12px shadow-sm border border-gray-100 dark:border-dark-600">
            <div class="flex items-center justify-between">
              <div>
                <div class="flex items-center gap-6px">
                  <NText depth="3" class="text-13px font-medium">{{ card.label }}</NText>
                  <NTag size="tiny" :type="card.tagType" round :bordered="false" class="text-10px px-4px">
                    {{ card.tag }}
                  </NTag>
                </div>
                <div class="mt-8px text-28px font-bold font-mono tracking-tight text-gray-900 dark:text-gray-50">
                  {{ card.value }}
                </div>
              </div>
              <div
                class="h-46px w-46px flex items-center justify-center rounded-12px text-24px shadow-sm"
                :style="{ backgroundColor: `${card.color}15`, color: card.color }"
              >
                <SvgIcon :icon="card.icon" />
              </div>
            </div>
            <!-- 底部微可视化进度条 -->
            <div class="mt-12px pt-8px border-t border-gray-100 dark:border-dark-500">
              <div class="flex items-center justify-between text-11px text-gray-500 dark:text-gray-400 mb-4px">
                <span>{{ card.subText }}</span>
                <span class="font-mono font-semibold">{{ card.percent }}%</span>
              </div>
              <NProgress
                type="line"
                :percentage="card.percent"
                :show-indicator="false"
                :height="4"
                :color="card.color"
                rail-color="rgba(0,0,0,0.06)"
              />
            </div>
          </NCard>
        </NGi>
      </NGrid>
    </NSpin>

    <!-- 核心可视化数据驾驶舱 (ECharts 趋势走势 + 状态环形占比) -->
    <NGrid cols="1 m:12" responsive="screen" :x-gap="16" :y-gap="16">
      <!-- 7日趋势走势 -->
      <NGi span="1 m:7">
        <NCard title="📈 近 7 日订单交付走势" :bordered="false" class="rounded-12px shadow-sm border border-gray-100 dark:border-dark-600 h-full">
          <template #header-extra>
            <NTag size="tiny" type="primary" round>实时走势</NTag>
          </template>
          <div ref="trendDomRef" class="w-full h-280px"></div>
        </NCard>
      </NGi>

      <!-- 网课各项目出单分布环形图 -->
      <NGi span="1 m:5">
        <NCard title="📊 网课项目出单分布" :bordered="false" class="rounded-12px shadow-sm border border-gray-100 dark:border-dark-600 h-full">
          <template #header-extra>
            <NTag size="tiny" type="success" round>项目占比</NTag>
          </template>
          <div ref="pieDomRef" class="w-full h-280px"></div>
        </NCard>
      </NGi>
    </NGrid>

    <!-- 账户资产与常用入口 -->
    <NGrid cols="1 m:2" responsive="screen" :x-gap="16" :y-gap="16">
      <NGi>
        <NCard title="💼 账户资产概览" :bordered="false" class="rounded-12px shadow-sm border border-gray-100 dark:border-dark-600 h-full">
          <NDescriptions label-placement="left" :column="1" class="text-14px">
            <NDescriptionsItem label="可用余额">
              <strong class="text-18px font-mono text-emerald-600 font-bold">¥ {{ summary.balance }}</strong>
            </NDescriptionsItem>
            <NDescriptionsItem label="名下代理团队">
              <span class="font-mono font-bold">{{ summary.userTotal }} 位商户</span>
            </NDescriptionsItem>
            <NDescriptionsItem label="当前账户角色">
              <NTag :type="authStore.userInfo.roles.includes('R_SUPER') ? 'error' : 'info'" round>
                {{ authStore.userInfo.roles.includes('R_SUPER') ? '超级管理员' : '代理商户' }}
              </NTag>
            </NDescriptionsItem>
          </NDescriptions>
        </NCard>
      </NGi>
      <NGi>
        <NCard title="⚡ 常用快捷通道" :bordered="false" class="rounded-12px shadow-sm border border-gray-100 dark:border-dark-600 h-full">
          <NGrid cols="2" :x-gap="12" :y-gap="12">
            <NGi><NButton block secondary type="primary" size="large" @click="router.push('/add')">马上学习</NButton></NGi>
            <NGi><NButton block secondary type="info" size="large" @click="router.push('/userlist')">代理管理</NButton></NGi>
            <NGi><NButton block secondary type="success" size="large" @click="router.push('/list')">订单汇总</NButton></NGi>
            <NGi><NButton block secondary size="large" @click="router.push('/userinfo')">个人中心</NButton></NGi>
          </NGrid>
        </NCard>
      </NGi>
    </NGrid>

    <!-- 历史通告与更新动态 -->
    <NCard title="📋 平台通告与更新日志" :bordered="false" class="rounded-12px shadow-sm border border-gray-100 dark:border-dark-600">
      <template #header-extra>
        <NTag size="tiny" type="info" round>实时通告流</NTag>
      </template>
      <div v-if="!noticeList.length" class="py-24px">
        <NEmpty description="暂无历史系统通告" />
      </div>
      <NList v-else hoverable clickable class="flex flex-col gap-10px">
        <NListItem v-for="item in noticeList" :key="item.id" class="rounded-8px p-12px border border-gray-100 dark:border-dark-600">
          <NThing :title="item.title" :description="`发布时间: ${item.time || item.addtime}`">
            <template #header-extra>
              <NTag size="tiny" type="primary" round>官方发布</NTag>
            </template>
            <div class="mt-8px rounded-6px bg-gray-50/80 p-10px text-13px text-gray-600 dark:bg-dark-600 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">
              {{ item.content }}
            </div>
          </NThing>
        </NListItem>
      </NList>
    </NCard>
  </div>
</template>

<style scoped></style>