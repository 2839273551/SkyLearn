<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { fetchDashboard, fetchUserSignIn } from '@/service/api';
import { useAuthStore } from '@/store/modules/auth';

defineOptions({ name: 'Main' });

const router = useRouter();
const authStore = useAuthStore();
const loading = ref(false);
const signingIn = ref(false);

async function handleSignIn() {
  signingIn.value = true;
  const { data, error } = await fetchUserSignIn();
  signingIn.value = false;
  if (!error && data) {
    authStore.userInfo.hasSignedIn = true;
    authStore.userInfo.balance = data.balance;
    if (data.freeAdd !== undefined) {
      authStore.userInfo.freeAdd = data.freeAdd;
    }
  }
}
const summary = ref<Api.Dashboard.Summary>({
  orderTotal: 0,
  todayOrders: 0,
  runningOrders: 0,
  completedOrders: 0,
  userTotal: 0,
  balance: '0.00',
  announcement: ''
});

const cards = computed(() => [
  { label: '今日订单', value: summary.value.todayOrders, icon: 'ph:calendar-check', color: '#2563eb' },
  { label: '全部订单', value: summary.value.orderTotal, icon: 'ph:list-checks', color: '#7c3aed' },
  { label: '进行中', value: summary.value.runningOrders, icon: 'ph:spinner-gap', color: '#d97706' },
  { label: '已完成', value: summary.value.completedOrders, icon: 'ph:check-circle', color: '#059669' }
]);

async function loadDashboard() {
  loading.value = true;
  const { data, error } = await fetchDashboard();
  if (!error && data) summary.value = data;
  loading.value = false;
}

onMounted(loadDashboard);
</script>

<template>
  <NSpace vertical :size="16">
    <NCard :bordered="false" class="card-wrapper">
      <div class="flex flex-wrap items-center justify-between gap-16px">
        <div>
          <NText depth="3">{{ authStore.userInfo.siteName || '网课管理中心' }}</NText>
          <h1 class="mt-6px text-24px font-600">
            你好，{{ authStore.userInfo.displayName || authStore.userInfo.userName }}
          </h1>
          <NText depth="3">这里是原“个人综合”页面的新版工作台。</NText>
        </div>
        <NSpace>
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
          <NButton @click="router.push('/list')">订单汇总</NButton>
        </NSpace>
      </div>
    </NCard>

    <NAlert v-if="summary.announcement" type="info" title="平台公告">
      {{ summary.announcement }}
    </NAlert>

    <NSpin :show="loading">
      <NGrid cols="1 s:2 m:4" responsive="screen" :x-gap="16" :y-gap="16">
        <NGi v-for="card in cards" :key="card.label">
          <NCard :bordered="false" class="card-wrapper">
            <div class="flex items-center justify-between">
              <div>
                <NText depth="3">{{ card.label }}</NText>
                <div class="mt-8px text-28px font-700">{{ card.value }}</div>
              </div>
              <div class="size-48px flex-center rd-12px" :style="{ backgroundColor: `${card.color}18`, color: card.color }">
                <SvgIcon :icon="card.icon" class="text-28px" />
              </div>
            </div>
          </NCard>
        </NGi>
      </NGrid>
    </NSpin>

    <NGrid cols="1 m:2" responsive="screen" :x-gap="16" :y-gap="16">
      <NGi>
        <NCard title="账户概览" :bordered="false" class="card-wrapper">
          <NDescriptions label-placement="left" :column="1">
            <NDescriptionsItem label="账户余额">¥ {{ summary.balance }}</NDescriptionsItem>
            <NDescriptionsItem label="下级用户">{{ summary.userTotal }}</NDescriptionsItem>
            <NDescriptionsItem label="当前角色">
              <NTag :type="authStore.userInfo.roles.includes('R_SUPER') ? 'error' : 'info'">
                {{ authStore.userInfo.roles.includes('R_SUPER') ? '超级管理员' : '代理用户' }}
              </NTag>
            </NDescriptionsItem>
          </NDescriptions>
        </NCard>
      </NGi>
      <NGi>
        <NCard title="常用入口" :bordered="false" class="card-wrapper">
          <NGrid cols="2" :x-gap="12" :y-gap="12">
            <NGi><NButton block secondary type="primary" @click="router.push('/add')">课程下单</NButton></NGi>
            <NGi><NButton block secondary type="info" @click="router.push('/list')">订单管理</NButton></NGi>
            <NGi><NButton block secondary @click="router.push('/home')">实时公告</NButton></NGi>
            <NGi><NButton block secondary @click="router.push('/userinfo')">我的资料</NButton></NGi>
          </NGrid>
        </NCard>
      </NGi>
    </NGrid>
  </NSpace>
</template>

<style scoped></style>
