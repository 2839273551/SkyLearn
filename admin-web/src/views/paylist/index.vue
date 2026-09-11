<script setup lang="ts">
import { h, onMounted, reactive, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { NTag } from 'naive-ui';
import { fetchPaylistList } from '@/service/api';

defineOptions({ name: 'Paylist' });

const loading = ref(false);
const list = ref<Api.Paylist.Record[]>([]);
const total = ref(0);

const query = reactive({
  page: 1,
  pageSize: 20,
  keyword: '',
  status: '' as '' | '1' | '0',
  type: ''
});

const typeOptions = [
  { label: '全部通道', value: '' },
  { label: '支付宝 (alipay)', value: 'alipay' },
  { label: '微信支付 (wxpay)', value: 'wxpay' },
  { label: 'QQ钱包 (qqpay)', value: 'qqpay' }
];

const columns: DataTableColumns<Api.Paylist.Record> = [
  { title: 'ID', key: 'oid', width: 70, fixed: 'left' },
  { title: '商户单号', key: 'outTradeNo', minWidth: 160, ellipsis: { tooltip: true } },
  { title: '接口交易号', key: 'tradeNo', minWidth: 160, ellipsis: { tooltip: true } },
  {
    title: '支付类型',
    key: 'type',
    width: 110,
    render: row => h(NTag, { size: 'small', type: 'info', round: true }, { default: () => row.type || '在线支付' })
  },
  { title: '充值 UID', key: 'uid', width: 100 },
  { title: '充值说明', key: 'name', minWidth: 140 },
  {
    title: '支付金额',
    key: 'money',
    width: 110,
    render: row => h('span', { class: 'font-bold text-success' }, `¥ ${row.money}`)
  },
  {
    title: '支付状态',
    key: 'status',
    width: 100,
    render: row =>
      h(
        NTag,
        {
          type: row.status === 1 ? 'success' : 'error',
          size: 'small'
        },
        { default: () => (row.status === 1 ? '已支付' : '未支付') }
      )
  },
  { title: '创建时间', key: 'addtime', width: 170 },
  { title: '支付完成时间', key: 'endtime', width: 170, render: row => row.endtime || '-' }
];

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchPaylistList({
    page: query.page,
    pageSize: query.pageSize,
    keyword: query.keyword.trim() || undefined,
    status: query.status !== '' ? query.status : undefined,
    type: query.type || undefined
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

function handleReset() {
  query.page = 1;
  query.keyword = '';
  query.status = '';
  query.type = '';
  loadData();
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="支付订单" :bordered="false" class="rounded-8px shadow-sm">
      <div class="mb-16px flex flex-wrap items-center gap-12px">
        <NInput
          v-model:value="query.keyword"
          placeholder="商户单号 / 交易号 / UID / 说明"
          clearable
          class="w-240px"
          @keyup.enter="handleSearch"
        />
        <NSelect
          v-model:value="query.status"
          :options="[
            { label: '全部状态', value: '' },
            { label: '已支付', value: '1' },
            { label: '未支付', value: '0' }
          ]"
          placeholder="状态筛选"
          clearable
          class="w-130px"
        />
        <NSelect
          v-model:value="query.type"
          :options="typeOptions"
          placeholder="通道筛选"
          clearable
          class="w-160px"
        />
        <NButton type="primary" @click="handleSearch">查询</NButton>
        <NButton @click="handleReset">重置</NButton>
        <div class="ml-auto">
          <NButton :loading="loading" @click="loadData">刷新</NButton>
        </div>
      </div>

      <NDataTable
        :loading="loading"
        :columns="columns"
        :data="list"
        :row-key="(row: Api.Paylist.Record) => row.oid"
        :pagination="false"
        striped
        :scroll-x="1300"
      />

      <div class="mt-16px flex justify-end">
        <NPagination
          v-model:page="query.page"
          v-model:page-size="query.pageSize"
          :item-count="total"
          :page-sizes="[20, 50, 100]"
          show-size-picker
          show-quick-jumper
          @update:page="loadData"
          @update:page-size="handleSearch"
        />
      </div>
    </NCard>
  </div>
</template>
