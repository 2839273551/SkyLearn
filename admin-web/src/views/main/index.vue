<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import {
  NAlert,
  NButton,
  NCard,
  NDescriptions,
  NDescriptionsItem,
  NEmpty,
  NGi,
  NGrid,
  NList,
  NListItem,
  NSpace,
  NSpin,
  NTag,
  NText,
  NThing
} from 'naive-ui';
import { fetchDashboard, fetchGglistList, fetchUserSignIn } from '@/service/api';
import { useAuthStore } from '@/store/modules/auth';

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
  announcement: ''
});

const noticeList = ref<any[]>([]);

const cards = computed(() => [
  { label: '今日订单', value: summary.value.todayOrders, icon: 'ph:calendar-check', color: '#2563eb' },
  { label: '全部订单', value: summary.value.orderTotal, icon: 'ph:list-checks', color: '#7c3aed' },
  { label: '进行中', value: summary.value.runningOrders, icon: 'ph:spinner-gap', color: '#d97706' },
  { label: '已完成', value: summary.value.completedOrders, icon: 'ph:check-circle', color: '#059669' }
]);

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

    <!-- 核心业务统计指标 -->
    <NSpin :show="loading">
      <NGrid cols="1 s:2 m:4" responsive="screen" :x-gap="16" :y-gap="16">
        <NGi v-for="card in cards" :key="card.label">
          <NCard :bordered="false" class="rounded-12px shadow-sm border border-gray-100 dark:border-dark-600">
            <div class="flex items-center justify-between">
              <div>
                <NText depth="3" class="text-13px">{{ card.label }}</NText>
                <div class="mt-6px text-26px font-bold text-gray-800 dark:text-gray-100">{{ card.value }}</div>
              </div>
              <div class="h-44px w-44px flex items-center justify-center rounded-10px text-22px" :style="{ backgroundColor: `${card.color}15`, color: card.color }">
                <SvgIcon :icon="card.icon" />
              </div>
            </div>
          </NCard>
        </NGi>
      </NGrid>
    </NSpin>

    <!-- 账户资产与常用入口 -->
    <NGrid cols="1 m:2" responsive="screen" :x-gap="16" :y-gap="16">
      <NGi>
        <NCard title="💼 账户资产概览" :bordered="false" class="rounded-12px shadow-sm border border-gray-100 dark:border-dark-600">
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
        <NCard title="⚡ 常用快捷通道" :bordered="false" class="rounded-12px shadow-sm border border-gray-100 dark:border-dark-600">
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
