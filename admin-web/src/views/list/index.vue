<script setup lang="ts">
import { h, onMounted, reactive, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { NAlert, NTag } from 'naive-ui';
import { fetchOrders } from '@/service/api';
import { useAuthStore } from '@/store/modules/auth';

defineOptions({ name: 'List' });

const authStore = useAuthStore();
const loading = ref(false);
const records = ref<Api.Orders.Record[]>([]);
const total = ref(0);
const query = reactive<Api.Orders.Query>({ page: 1, pageSize: 20, keyword: '', status: '' });

const statusOptions = [
  { label: '全部状态', value: '' },
  { label: '待处理', value: '待处理' },
  { label: '进行中', value: '进行中' },
  { label: '已完成', value: '已完成' },
  { label: '异常', value: '异常' },
  { label: '已取消', value: '已取消' }
];

function statusType(status: string): 'default' | 'info' | 'success' | 'warning' | 'error' {
  if (status === '已完成') return 'success';
  if (status === '进行中') return 'info';
  if (status === '异常') return 'error';
  if (status === '待处理') return 'warning';
  return 'default';
}

const columns: DataTableColumns<Api.Orders.Record> = [
  { title: '订单号', key: 'orderId', width: 110, fixed: 'left' },
  { title: '平台', key: 'platform', width: 130, ellipsis: { tooltip: true } },
  { title: '账号', key: 'account', width: 140, ellipsis: { tooltip: true } },
  { title: '课程', key: 'courseName', minWidth: 200, ellipsis: { tooltip: true } },
  { title: '学校', key: 'school', width: 150, ellipsis: { tooltip: true } },
  { title: '进度', key: 'progress', width: 120, ellipsis: { tooltip: true } },
  {
    title: '状态',
    key: 'status',
    width: 100,
    render: row => h(NTag, { type: statusType(row.status), size: 'small', round: true }, { default: () => row.status || '未知' })
  },
  { title: '备注', key: 'remarks', minWidth: 160, ellipsis: { tooltip: true } },
  { title: '创建时间', key: 'createdAt', width: 170 }
];

async function loadOrders() {
  loading.value = true;
  const { data, error } = await fetchOrders(query);
  if (!error && data) {
    records.value = data.records;
    total.value = data.total;
  }
  loading.value = false;
}

function search() {
  query.page = 1;
  loadOrders();
}

function reset() {
  query.keyword = '';
  query.status = '';
  query.page = 1;
  loadOrders();
}

function changePage(page: number) {
  query.page = page;
  loadOrders();
}

function changePageSize(pageSize: number) {
  query.pageSize = pageSize;
  query.page = 1;
  loadOrders();
}

onMounted(loadOrders);
</script>

<template>
  <NSpace vertical :size="16">
    <NAlert
      v-if="authStore.userInfo.ddggkg && authStore.userInfo.ddgg"
      type="warning"
      title="订单须知公告"
      class="card-wrapper whitespace-pre-wrap"
      closable
    >
      {{ authStore.userInfo.ddgg }}
    </NAlert>

    <NCard :bordered="false" class="card-wrapper">
      <div class="flex flex-wrap items-center gap-12px">
        <NInput
          v-model:value="query.keyword"
          clearable
          class="w-320px lt-sm:w-full"
          placeholder="订单号、账号、课程、学校或备注"
          @keyup.enter="search"
        >
          <template #prefix><SvgIcon icon="ph:magnifying-glass" /></template>
        </NInput>
        <NSelect v-model:value="query.status" class="w-160px" :options="statusOptions" />
        <NButton type="primary" @click="search">查询</NButton>
        <NButton @click="reset">重置</NButton>
        <NButton quaternary :loading="loading" @click="loadOrders">
          <template #icon><SvgIcon icon="ph:arrow-clockwise" /></template>
          刷新
        </NButton>
      </div>
    </NCard>

    <NCard title="订单汇总" :bordered="false" class="card-wrapper">
      <NDataTable
        remote
        :columns="columns"
        :data="records"
        :loading="loading"
        :row-key="row => row.orderId"
        :scroll-x="1400"
      />
      <div class="mt-16px flex justify-end">
        <NPagination
          :page="query.page"
          :page-size="query.pageSize"
          :item-count="total"
          show-size-picker
          :page-sizes="[10, 20, 50, 100]"
          @update:page="changePage"
          @update:page-size="changePageSize"
        />
      </div>
    </NCard>
  </NSpace>
</template>

<style scoped></style>
