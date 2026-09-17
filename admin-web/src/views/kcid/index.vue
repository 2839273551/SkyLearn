<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { useAppStore } from '@/store/modules/app';
import { fetchKcidCompare } from '@/service/api';

defineOptions({ name: 'Kcid' });

const appStore = useAppStore();

const loading = ref(false);
const list = ref<Api.ProfileArea.KcidRecord[]>([]);
const total = ref(0);
const keyword = ref('');
const page = ref(1);

const columns: DataTableColumns<Api.ProfileArea.KcidRecord> = [
  { title: '订单 ID', key: 'oid', width: 90 },
  { title: '下单账号', key: 'user', width: 140 },
  { title: '平台', key: 'platform', width: 130 },
  { title: '课程名称', key: 'courseName', minWidth: 180 },
  {
    title: '上游 KCID',
    key: 'kcid',
    width: 130,
    render: row => `[KCID] ${row.kcid || '-'}`
  },
  { title: '进度', key: 'progress', width: 110 },
  { title: '状态', key: 'status', width: 110 },
  { title: '下单时间', key: 'addtime', width: 170 }
];

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchKcidCompare({ page: page.value, keyword: keyword.value.trim() || undefined });
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
    <NCard title="课程 ID (KCID) 对比核验" :bordered="false" class="rounded-8px shadow-sm">
      <div class="mb-16px flex items-center justify-between gap-12px">
        <div class="flex items-center gap-10px">
          <NInput v-model:value="keyword" placeholder="搜索订单 ID / 账号 / 课程 / KCID" clearable class="w-280px" @keyup.enter="handleSearch" />
          <NButton type="primary" @click="handleSearch">查询</NButton>
        </div>
        <NButton :loading="loading" @click="loadData">刷新</NButton>
      </div>

      <NDataTable
        :loading="loading"
        :columns="columns"
        :data="list"
        :row-key="(row: Api.ProfileArea.KcidRecord) => row.oid"
        :pagination="false"
        striped
        :scroll-x="1100"
      />

      <div class="mt-16px flex w-full items-center justify-center sm:justify-end overflow-x-auto py-4px">
        <NPagination
          v-model:page="page"
          :page-size="20"
          :item-count="total"
          :page-slot="appStore.isMobile ? 5 : 9"
          :size="appStore.isMobile ? 'small' : 'medium'"
          @update:page="loadData"
        />
      </div>
    </NCard>
  </div>
</template>
