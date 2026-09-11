<script setup lang="ts">
import { onMounted, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { fetchClassLatest } from '@/service/api';

defineOptions({ name: 'Atest' });

const loading = ref(false);
const list = ref<Api.ProfileArea.LatestClass[]>([]);

const columns: DataTableColumns<Api.ProfileArea.LatestClass> = [
  { title: '课程 CID', key: 'cid', width: 90 },
  { title: '课程名称', key: 'name', minWidth: 200 },
  { title: '分类', key: 'fenlei', width: 130 },
  { title: '基准定价', key: 'price', width: 110, render: row => `¥ ${row.price}` },
  { title: '上架时间', key: 'addtime', width: 180 }
];

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchClassLatest();
  loading.value = false;
  if (!error && data) {
    list.value = data.list;
  }
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="最新上架专区【最近 8 天】" :bordered="false" class="rounded-8px shadow-sm">
      <template #header-extra>
        <NButton :loading="loading" type="primary" ghost @click="loadData">刷新</NButton>
      </template>

      <NDataTable
        :loading="loading"
        :columns="columns"
        :data="list"
        :row-key="(row: Api.ProfileArea.LatestClass) => row.cid"
        :pagination="{ pageSize: 20 }"
        striped
      />
    </NCard>
  </div>
</template>
