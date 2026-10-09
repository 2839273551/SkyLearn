<script setup lang="ts">
import { h, onMounted, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { fetchDdtjStats } from '@/service/api';

defineOptions({ name: 'Ddtj' });

const loading = ref(false);
const huoyuanRank = ref<Api.DdtjStats.RankItem[]>([]);
const platformRank = ref<Api.DdtjStats.RankItem[]>([]);

const columns: DataTableColumns<Api.DdtjStats.RankItem> = [
  { title: '名称', key: 'name', minWidth: 160 },
  {
    title: '今日销量',
    key: 'today',
    width: 100,
    render: row => h('span', { class: row.today > 0 ? 'font-bold text-primary' : 'text-gray-400' }, `${row.today}`)
  },
  { title: '昨日销量', key: 'yesterday', width: 100 },
  { title: '本周销量', key: 'week', width: 100 },
  { title: '本月销量', key: 'month', width: 100 },
  {
    title: '历史总销量',
    key: 'total',
    width: 120,
    render: row => h('span', { class: 'font-bold text-success' }, `${row.total}`)
  },
  { title: '最后下单时间', key: 'latest', width: 180 }
];

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchDdtjStats();
  loading.value = false;
  if (!error && data) {
    huoyuanRank.value = data.huoyuanRank;
    platformRank.value = data.platformRank;
  }
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="货源与平台销量统计报表" :bordered="false" class="rounded-8px shadow-sm">
      <template #header-extra>
        <NButton :loading="loading" type="primary" ghost @click="loadData">刷新数据</NButton>
      </template>

      <NTabs type="line" animated>
        <NTabPane name="huoyuan" tab="各接口货源销量排行">
          <NDataTable
            :loading="loading"
            :columns="columns"
            :data="huoyuanRank"
            :row-key="(row: Api.DdtjStats.RankItem) => row.name"
            :pagination="false"
            striped
          />
        </NTabPane>
        <NTabPane name="platform" tab="各网课平台销量排行">
          <NDataTable
            :loading="loading"
            :columns="columns"
            :data="platformRank"
            :row-key="(row: Api.DdtjStats.RankItem) => row.name"
            :pagination="false"
            striped
          />
        </NTabPane>
      </NTabs>
    </NCard>
  </div>
</template>
