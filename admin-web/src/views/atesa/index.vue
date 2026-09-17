<script setup lang="ts">
import { onMounted, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { useAppStore } from '@/store/modules/app';
import { fetchClassOffline } from '@/service/api';

defineOptions({ name: 'Atesa' });

const appStore = useAppStore();

const loading = ref(false);
const list = ref<Api.ProfileArea.OfflineClass[]>([]);
const total = ref(0);
const page = ref(1);

const columns: DataTableColumns<Api.ProfileArea.OfflineClass> = [
  { title: '课程 CID', key: 'cid', width: 90 },
  { title: '项目名称', key: 'courseName', minWidth: 200 },
  { title: '分类 ID', key: 'categoryId', width: 90 },
  { title: '分类名称', key: 'categoryName', width: 140 },
  { title: '下架前说明', key: 'content', minWidth: 200, ellipsis: { tooltip: true } }
];

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchClassOffline({ page: page.value });
  loading.value = false;
  if (!error && data) {
    list.value = data.records;
    total.value = data.total;
  }
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="下架专区【暂停对接】" :bordered="false" class="rounded-8px shadow-sm">
      <template #header-extra>
        <NButton :loading="loading" type="primary" ghost @click="loadData">刷新</NButton>
      </template>

      <NDataTable
        :loading="loading"
        :columns="columns"
        :data="list"
        :row-key="(row: Api.ProfileArea.OfflineClass) => row.cid"
        :pagination="false"
        striped
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
