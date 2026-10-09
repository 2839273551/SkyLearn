<script setup lang="ts">
import { h, onMounted, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { fetchRankStats } from '@/service/api';

defineOptions({ name: 'Rd' });

const loading = ref(false);
const userRank = ref<Array<{ name: string; orderCount: number }>>([]);
const courseRank = ref<Array<{ platform: string; courseName: string; orderCount: number }>>([]);
const rechargeRank = ref<Array<{ name: string; money: string }>>([]);
const czEnabled = ref(false);

const userColumns: DataTableColumns<{ name: string; orderCount: number }> = [
  {
    title: '名次',
    key: 'rank',
    width: 80,
    render: (_, index) => h('span', { class: index < 3 ? 'font-bold text-warning text-16px' : '' }, index + 1)
  },
  { title: '代理昵称', key: 'name', minWidth: 160 },
  {
    title: '订单总量 (近90天)',
    key: 'orderCount',
    width: 160,
    render: row => h('span', { class: 'font-bold text-primary' }, `${row.orderCount} 单`)
  }
];

const rechargeColumns: DataTableColumns<{ name: string; money: string }> = [
  {
    title: '名次',
    key: 'rank',
    width: 80,
    render: (_, index) => h('span', { class: index < 3 ? 'font-bold text-warning text-16px' : '' }, index + 1)
  },
  { title: '代理昵称', key: 'name', minWidth: 160 },
  {
    title: '本周充值总额',
    key: 'money',
    width: 160,
    render: row => h('span', { class: 'font-bold text-success' }, `¥ ${row.money}`)
  }
];

const courseColumns: DataTableColumns<{ platform: string; courseName: string; orderCount: number }> = [
  {
    title: '名次',
    key: 'rank',
    width: 80,
    render: (_, index) => h('span', { class: index < 3 ? 'font-bold text-warning text-16px' : '' }, index + 1)
  },
  { title: '所属平台', key: 'platform', width: 140 },
  { title: '课程名称', key: 'courseName', minWidth: 200 },
  {
    title: '下单热度 (近30天)',
    key: 'orderCount',
    width: 160,
    render: row => h('span', { class: 'font-bold text-success' }, `${row.orderCount} 次`)
  }
];

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchRankStats();
  loading.value = false;
  if (!error && data) {
    userRank.value = data.userRank;
    courseRank.value = data.courseRank;
    rechargeRank.value = data.rechargeRank || [];
    czEnabled.value = Boolean(data.czEnabled);
  }
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="热度与出单排行榜" :bordered="false" class="rounded-8px shadow-sm">
      <template #header-extra>
        <NButton :loading="loading" type="primary" ghost @click="loadData">刷新</NButton>
      </template>

      <NTabs type="line" animated>
        <NTabPane name="users" tab="巅峰代理排行榜 (近 90 天)">
          <NDataTable
            :loading="loading"
            :columns="userColumns"
            :data="userRank"
            :row-key="(row: { name: string }) => row.name"
            :pagination="false"
            striped
          />
        </NTabPane>
        <NTabPane v-if="czEnabled" name="recharge" tab="本周充值排行榜">
          <NDataTable
            :loading="loading"
            :columns="rechargeColumns"
            :data="rechargeRank"
            :row-key="(row: { name: string }) => row.name"
            :pagination="false"
            striped
          />
        </NTabPane>
        <NTabPane name="courses" tab="热门课程榜单 (近 30 天)">
          <NDataTable
            :loading="loading"
            :columns="courseColumns"
            :data="courseRank"
            :row-key="(row: { courseName: string }) => row.courseName"
            :pagination="false"
            striped
          />
        </NTabPane>
      </NTabs>
    </NCard>
  </div>
</template>
