<script setup lang="ts">
import { h, onMounted, reactive, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { NButton, NCard, NDataTable, NInput, NPagination, NSelect, NTag, NTooltip } from 'naive-ui';
import { fetchLogList } from '@/service/api';

defineOptions({ name: 'Log' });

const loading = ref(false);
const list = ref<Api.ProfileArea.LogItem[]>([]);
const total = ref(0);

const query = reactive({
  page: 1,
  pageSize: 20,
  type: '',
  keyword: ''
});

const typeOptions = [
  { label: '全部类型 (All Types)', value: '' },
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

function getTypeTagType(type: string): 'default' | 'info' | 'success' | 'warning' | 'error' {
  if (type.includes('充值') || type.includes('退款')) return 'success';
  if (type.includes('扣费')) return 'warning';
  if (type.includes('任务') || type.includes('提交')) return 'info';
  if (type.includes('登录')) return 'default';
  return 'info';
}

const columns: DataTableColumns<Api.ProfileArea.LogItem> = [
  {
    title: '流水ID',
    key: 'id',
    width: 80,
    render: row => h('span', { class: 'font-mono text-12px text-gray-400' }, `#${row.id}`)
  },
  {
    title: '用户 UID',
    key: 'uid',
    width: 95,
    render: row => h('span', { class: 'font-mono font-bold text-primary' }, row.uid)
  },
  {
    title: '操作类型',
    key: 'type',
    width: 125,
    render: row =>
      h(
        NTag,
        { size: 'small', type: getTypeTagType(row.type), round: true, class: 'font-medium' },
        { default: () => row.type }
      )
  },
  {
    title: '详情说明',
    key: 'text',
    minWidth: 280,
    render: row => h('span', { class: 'text-13px leading-relaxed' }, row.text)
  },
  {
    title: '资金变动',
    key: 'money',
    width: 125,
    render: row => {
      const num = Number(row.money);
      const isPositive = row.money.startsWith('+') || num > 0;
      const isZero = row.money === '0' || num === 0;
      return h(
        'span',
        {
          class: [
            'font-mono font-bold text-13px',
            isZero ? 'text-gray-400' : isPositive ? 'text-emerald-500' : 'text-rose-500'
          ]
        },
        isZero ? '0.00' : (isPositive && !row.money.startsWith('+') ? `+${row.money}` : row.money)
      );
    }
  },
  {
    title: '变动后余额',
    key: 'smoney',
    width: 120,
    render: row =>
      row.smoney
        ? h('span', { class: 'font-mono text-12px font-medium' }, `¥ ${row.smoney}`)
        : h('span', { class: 'text-gray-400' }, '-')
  },
  {
    title: '客户端 IP',
    key: 'ip',
    width: 135,
    render: row => h('span', { class: 'font-mono text-11px text-gray-500' }, row.ip || '-')
  },
  {
    title: '记录时间',
    key: 'addtime',
    width: 175,
    render: row => h('span', { class: 'font-mono text-12px text-gray-500' }, row.addtime)
  }
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

function handleReset() {
  query.type = '';
  query.keyword = '';
  query.page = 1;
  loadData();
}

function exportLogCsv() {
  if (!list.value.length) {
    window.$message?.warning('当前无可导出的日志数据');
    return;
  }
  const headers = ['流水ID', 'UID', '操作类型', '详情说明', '资金变动', '当前余额', 'IP', '记录时间'];
  const rows = list.value.map(i => [
    i.id,
    i.uid,
    i.type,
    `"${(i.text || '').replace(/"/g, '""')}"`,
    i.money,
    i.smoney || '',
    i.ip || '',
    i.addtime
  ]);
  const bom = String.fromCharCode(0xFEFF);
  const csv = bom + [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `operation_log_${new Date().toISOString().slice(0, 10)}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  window.$message?.success('操作日志报表已导出');
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="用户操作与资金变动流水" :bordered="false" class="rounded-12px shadow-sm">
      <template #header-extra>
        <div class="flex items-center gap-8px">
          <NButton size="small" secondary @click="exportLogCsv">
            📥 导出日志报表
          </NButton>
          <NButton size="small" :loading="loading" @click="loadData">
            刷新
          </NButton>
        </div>
      </template>

      <!-- 搜索筛选栏 -->
      <div class="mb-16px flex flex-wrap items-center justify-between gap-12px rounded-8px bg-gray-50/70 p-12px dark:bg-dark-600/50">
        <div class="flex flex-wrap items-center gap-10px">
          <NSelect
            v-model:value="query.type"
            :options="typeOptions"
            placeholder="日志操作类型"
            clearable
            class="w-180px"
          />
          <NInput
            v-model:value="query.keyword"
            placeholder="搜索详情内容 / 用户 UID"
            clearable
            class="w-240px"
            @keyup.enter="handleSearch"
          />
          <NButton type="primary" @click="handleSearch">
            <template #icon><span>🔍</span></template>
            查询
          </NButton>
          <NButton secondary @click="handleReset">重置</NButton>
        </div>
        <div class="text-12px text-gray-400">
          共计 <strong class="text-primary font-mono font-bold">{{ total }}</strong> 条资金与行为审计流水
        </div>
      </div>

      <NDataTable
        :loading="loading"
        :columns="columns"
        :data="list"
        :row-key="(row: Api.ProfileArea.LogItem) => row.id"
        :pagination="false"
        striped
        size="small"
        :scroll-x="1100"
      />

      <div class="mt-16px flex items-center justify-between border-t border-gray-100 dark:border-gray-800 pt-12px">
        <span class="text-12px text-gray-400">
          精确记录全站用户的登录态、费率调整、扣费充值与订单变动
        </span>
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
