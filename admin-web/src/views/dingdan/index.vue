<script setup lang="ts">
import { h, onMounted, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { NTag } from 'naive-ui';
import { fetchOrderAvailable } from '@/service/api';

defineOptions({ name: 'Dingdan' });

const loading = ref(false);
const list = ref<Api.ProfileArea.AvailableOrder[]>([]);
const total = ref(0);
const keyword = ref('');
const page = ref(1);

const columns: DataTableColumns<Api.ProfileArea.AvailableOrder> = [
  { title: '平台', key: 'platform', width: 140 },
  { title: '课程名称', key: 'courseName', minWidth: 180 },
  {
    title: '任务状态',
    key: 'status',
    width: 110,
    render: row =>
      h(
        NTag,
        {
          type: row.status === '已完成' ? 'success' : row.status === '进行中' ? 'info' : 'warning',
          size: 'small'
        },
        { default: () => row.status || '待处理' }
      )
  },
  { title: '实时进度', key: 'progress', width: 110 },
  { title: '说明与备注', key: 'remarks', minWidth: 260, ellipsis: { tooltip: true } },
  { title: '下单时间', key: 'addtime', width: 170 }
];

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchOrderAvailable({ page: page.value, keyword: keyword.value.trim() || undefined });
  loading.value = false;
  if (!error && data) {
    list.value = data.records;
    total.value = data.total;
  }
}

function handleSearch() {
  page.value = 1;
  loadData();
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="可用项目任务公示" :bordered="false" class="rounded-8px shadow-sm">
      <div class="mb-16px flex items-center justify-between gap-12px">
        <div class="flex items-center gap-10px">
          <NInput v-model:value="keyword" placeholder="搜索平台 / 课程名 / 备注" clearable class="w-260px" @keyup.enter="handleSearch" />
          <NButton type="primary" @click="handleSearch">查询</NButton>
        </div>
        <NButton :loading="loading" @click="loadData">刷新</NButton>
      </div>

      <NDataTable
        :loading="loading"
        :columns="columns"
        :data="list"
        :row-key="(row: Api.ProfileArea.AvailableOrder) => row.oid"
        :pagination="false"
        striped
      />

      <div class="mt-16px flex justify-end">
        <NPagination
          v-model:page="page"
          :page-size="20"
          :item-count="total"
          @update:page="loadData"
        />
      </div>
    </NCard>
  </div>
</template>
