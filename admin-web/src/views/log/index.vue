<script setup lang="ts">
import { h, onMounted, reactive, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { NTag } from 'naive-ui';
import { fetchLogList } from '@/service/api';

defineOptions({ name: 'Log' });

const loading = ref(false);
const list = ref<Api.ProfileArea.LogItem[]>([]);
const total = ref(0);

const query = reactive({
  page: 1,
  type: '',
  keyword: ''
});

const typeOptions = [
  { label: '全部类型', value: '' },
  { label: '登录', value: '登录' },
  { label: '添加任务', value: '添加任务' },
  { label: '批量提交', value: '批量提交' },
  { label: '上级充值', value: '上级充值' },
  { label: '代理充值', value: '代理充值' },
  { label: '修改费率', value: '修改费率' },
  { label: '查课扣费', value: '查课扣费' },
  { label: '卡密充值', value: '卡密充值' },
  { label: '订单退款', value: '订单退款' }
];

const columns: DataTableColumns<Api.ProfileArea.LogItem> = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '用户 UID', key: 'uid', width: 90 },
  {
    title: '操作类型',
    key: 'type',
    width: 120,
    render: row => h(NTag, { size: 'small', type: 'info', round: true }, { default: () => row.type })
  },
  { title: '详情说明', key: 'text', minWidth: 260 },
  {
    title: '资金变动',
    key: 'money',
    width: 120,
    render: row => {
      const isPositive = row.money.startsWith('+') || Number(row.money) > 0;
      const isZero = row.money === '0' || Number(row.money) === 0;
      return h(
        'span',
        { class: isZero ? 'text-gray-400' : isPositive ? 'font-bold text-success' : 'font-bold text-error' },
        row.money
      );
    }
  },
  { title: '当前余额', key: 'smoney', width: 110, render: row => (row.smoney ? `¥ ${row.smoney}` : '-') },
  { title: '操作 IP', key: 'ip', width: 130 },
  { title: '记录时间', key: 'addtime', width: 170 }
];

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchLogList({
    page: query.page,
    type: query.type || undefined,
    keyword: query.keyword.trim() || undefined
  });
  loading.value = false;

  if (!error && data) {
    list.value = data.records;
    total.value = data.total;
  }
}

function handleSearch() {
  query.page = 1;
  loadData();
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="操作与资金日志" :bordered="false" class="rounded-8px shadow-sm">
      <div class="mb-16px flex flex-wrap items-center justify-between gap-12px">
        <div class="flex flex-wrap items-center gap-10px">
          <NSelect v-model:value="query.type" :options="typeOptions" placeholder="日志类型" clearable class="w-150px" />
          <NInput v-model:value="query.keyword" placeholder="详情内容 / UID 搜索" clearable class="w-220px" @keyup.enter="handleSearch" />
          <NButton type="primary" @click="handleSearch">查询</NButton>
        </div>
        <NButton :loading="loading" @click="loadData">刷新</NButton>
      </div>

      <NDataTable
        :loading="loading"
        :columns="columns"
        :data="list"
        :row-key="(row: Api.ProfileArea.LogItem) => row.id"
        :pagination="false"
        striped
        :scroll-x="1100"
      />

      <div class="mt-16px flex justify-end">
        <NPagination
          v-model:page="query.page"
          :page-size="20"
          :item-count="total"
          @update:page="loadData"
        />
      </div>
    </NCard>
  </div>
</template>
