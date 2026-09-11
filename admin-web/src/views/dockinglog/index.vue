<script setup lang="ts">
import { computed, h, onMounted, reactive, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { NButton, NCard, NDataTable, NDrawer, NDrawerContent, NInput, NPagination, NPopconfirm, NSelect, NSpace, NStatistic, NTabPane, NTabs, NTag, NTooltip } from 'naive-ui';
import { clearDockingLogs, fetchDockingLogList } from '@/service/api';

defineOptions({ name: 'DockingLog' });

const loading = ref(false);
const list = ref<Api.DockingLog.Item[]>([]);
const total = ref(0);
const metrics = ref<Api.DockingLog.Metrics>({
  today_total: 0,
  today_in: 0,
  today_out: 0,
  today_traffic: '0 B',
  today_traffic_bytes: 0,
  avg_cost_ms: 0,
  success_rate: '100%',
  is_admin: true
});

const query = reactive({
  page: 1,
  pageSize: 20,
  direction: '',
  action_filter: '',
  keyword: '',
  status: undefined as number | undefined
});

// 抽屉详情
const drawerVisible = ref(false);
const currentItem = ref<Api.DockingLog.Item | null>(null);

function viewDetail(item: Api.DockingLog.Item) {
  currentItem.value = item;
  drawerVisible.value = true;
}

function copyText(text: string, label = '内容') {
  if (!text) {
    window.$message?.warning(`暂无${label}可复制`);
    return;
  }
  navigator.clipboard.writeText(text);
  window.$message?.success(`${label}已复制到剪切板`);
}

function formatJson(raw: string) {
  if (!raw) return '暂无数据';
  try {
    const obj = JSON.parse(raw);
    return JSON.stringify(obj, null, 2);
  } catch {
    return raw;
  }
}

const statusOptions = [
  { label: '全部状态', value: undefined },
  { label: '调用成功 (200/OK)', value: 1 },
  { label: '调用失败/异常', value: 0 }
];

const columns: DataTableColumns<Api.DockingLog.Item> = [
  {
    title: '几点(调用时间)',
    key: 'created_at',
    width: 170,
    render: row => h('span', { class: 'font-mono text-13px text-gray-700 dark:text-gray-300' }, row.created_at)
  },
  {
    title: '方向',
    key: 'direction',
    width: 120,
    render: row => {
      const isIn = row.direction === 'in';
      return h(
        NTag,
        { size: 'small', type: isIn ? 'success' : 'info', round: true },
        { default: () => (isIn ? '📥 外部对接我' : '📤 我对接上游') }
      );
    }
  },
  {
    title: '谁走的(调用方)',
    key: 'caller',
    width: 160,
    render: row =>
      h('div', { class: 'flex flex-col gap-2px' }, [
        h('span', { class: 'font-bold text-13px text-primary' }, row.caller || '匿名调用'),
        h('span', { class: 'font-mono text-11px text-gray-400' }, `IP: ${row.ip || '-'}`)
      ])
  },
  {
    title: '走了什么接口',
    key: 'action',
    minWidth: 160,
    render: row =>
      h('div', { class: 'flex flex-col gap-2px' }, [
        h('span', { class: 'font-medium text-13px' }, row.action),
        h('span', { class: 'font-mono text-11px text-gray-500' }, `${row.method} ${row.target}`)
      ])
  },
  {
    title: '走了多少流量',
    key: 'traffic_total',
    width: 150,
    render: row => {
      const isLarge = row.traffic_total > 50 * 1024;
      return h(
        NTooltip,
        { trigger: 'hover' },
        {
          trigger: () =>
            h(
              'span',
              {
                class: [
                  'font-mono font-bold px-8px py-3px rounded-4px cursor-help inline-block text-12px',
                  isLarge
                    ? 'bg-amber-50 text-amber-600 border border-amber-200 dark:bg-amber-900/20'
                    : 'bg-teal-50 text-teal-600 border border-teal-200 dark:bg-teal-900/20'
                ]
              },
              `⚡ ${row.traffic_text}`
            ),
          default: () =>
            h('div', { class: 'text-12px p-4px leading-relaxed' }, [
              h('div', `总消耗流量: ${row.traffic_text} (${row.traffic_total} 字节)`),
              h('div', `请求发送(入): ${row.bytes_in} 字节`),
              h('div', `响应返回(出): ${row.bytes_out} 字节`)
            ])
        }
      );
    }
  },
  {
    title: '耗时',
    key: 'cost_ms',
    width: 95,
    render: row => {
      const isSlow = row.cost_ms > 1000;
      return h(
        'span',
        { class: ['font-mono text-12px', isSlow ? 'text-amber-500 font-bold' : 'text-gray-500'] },
        `${row.cost_ms} ms`
      );
    }
  },
  {
    title: '状态',
    key: 'status',
    width: 90,
    render: row =>
      h(
        NTag,
        { size: 'small', type: row.status === 1 ? 'success' : 'error' },
        { default: () => (row.status === 1 ? '成功' : '失败') }
      )
  },
  {
    title: '报文操作',
    key: 'actions',
    width: 100,
    fixed: 'right',
    render: row =>
      h(
        NButton,
        {
          size: 'tiny',
          type: 'primary',
          secondary: true,
          onClick: () => viewDetail(row)
        },
        { default: () => '报文详情' }
      )
  }
];

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchDockingLogList({
    page: query.page,
    pageSize: query.pageSize,
    direction: query.direction || undefined,
    action_filter: query.action_filter.trim() || undefined,
    keyword: query.keyword.trim() || undefined,
    status: query.status
  });
  loading.value = false;

  if (!error && data) {
    list.value = data.records;
    total.value = data.total;
    if (data.metrics) {
      metrics.value = data.metrics;
    }
  }
}

function handleTabChange(tab: string) {
  query.direction = tab;
  query.page = 1;
  loadData();
}

function handleSearch() {
  query.page = 1;
  loadData();
}

function handleReset() {
  query.action_filter = '';
  query.keyword = '';
  query.status = undefined;
  query.page = 1;
  loadData();
}

async function handleClearLogs(range: '7days' | '30days' | 'all') {
  const { error } = await clearDockingLogs({ range });
  if (!error) {
    window.$message?.success('日志清理成功');
    query.page = 1;
    loadData();
  }
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <!-- 顶部核心指标看板 -->
    <div class="grid grid-cols-2 gap-12px sm:grid-cols-3 lg:grid-cols-6">
      <NCard size="small" class="rounded-8px shadow-sm">
        <NStatistic label="今日请求总量" :value="metrics.today_total">
          <template #suffix><span class="text-12px text-gray-400">次</span></template>
        </NStatistic>
      </NCard>
      <NCard size="small" class="rounded-8px shadow-sm">
        <NStatistic label="外部对接我 (入站)" :value="metrics.today_in">
          <template #suffix><span class="text-12px text-emerald-500">次</span></template>
        </NStatistic>
      </NCard>
      <NCard size="small" class="rounded-8px shadow-sm">
        <NStatistic label="我对接外部 (出站)" :value="metrics.today_out">
          <template #suffix><span class="text-12px text-blue-500">次</span></template>
        </NStatistic>
      </NCard>
      <NCard size="small" class="rounded-8px shadow-sm border-primary/30">
        <NStatistic label="今日消耗总流量" :value="metrics.today_traffic">
          <template #prefix><span class="text-primary text-14px mr-4px">🌐</span></template>
        </NStatistic>
      </NCard>
      <NCard size="small" class="rounded-8px shadow-sm">
        <NStatistic label="平均响应耗时" :value="metrics.avg_cost_ms">
          <template #suffix><span class="text-12px text-gray-400">ms</span></template>
        </NStatistic>
      </NCard>
      <NCard size="small" class="rounded-8px shadow-sm">
        <NStatistic label="接口调用成功率" :value="metrics.success_rate" />
      </NCard>
    </div>

    <!-- 主卡片与操作表格 -->
    <NCard title="接口对接与串货监控" :bordered="false" class="rounded-8px shadow-sm">
      <template #header-extra>
        <div class="flex items-center gap-8px">
          <template v-if="metrics.is_admin">
            <NPopconfirm @positive-click="handleClearLogs('7days')">
              <template #trigger>
                <NButton size="small" type="warning" secondary>清理7天前日志</NButton>
              </template>
              确定清理7天以前的全部接口对接记录吗？
            </NPopconfirm>
            <NPopconfirm @positive-click="handleClearLogs('all')">
              <template #trigger>
                <NButton size="small" type="error" secondary>清空全部日志</NButton>
              </template>
              确定清空所有对接日志吗？此操作不可逆！
            </NPopconfirm>
          </template>
          <NButton size="small" :loading="loading" @click="loadData">刷新</NButton>
        </div>
      </template>

      <!-- 流向分类 Tab 切换 -->
      <NTabs v-model:value="query.direction" type="line" class="mb-14px" @update:value="handleTabChange">
        <NTabPane name="" tab="🔄 全部对接流水" />
        <NTabPane name="in" tab="📥 外部对接我 (Inbound API)" />
        <NTabPane name="out" tab="📤 我对接外部 (Outbound 货源)" />
      </NTabs>

      <!-- 搜索过滤条 -->
      <div class="mb-16px flex flex-wrap items-center justify-between gap-12px">
        <div class="flex flex-wrap items-center gap-10px">
          <NInput
            v-model:value="query.action_filter"
            placeholder="接口动作 (如: 查课 / 下单 / query)"
            clearable
            class="w-210px"
            @keyup.enter="handleSearch"
          />
          <NInput
            v-model:value="query.keyword"
            placeholder="调用方UID / 用户 / IP / 货源"
            clearable
            class="w-230px"
            @keyup.enter="handleSearch"
          />
          <NSelect
            v-model:value="query.status"
            :options="statusOptions"
            placeholder="执行状态"
            clearable
            class="w-140px"
          />
          <NButton type="primary" @click="handleSearch">查询</NButton>
          <NButton @click="handleReset">重置</NButton>
        </div>
      </div>

      <!-- 数据表格 -->
      <NDataTable
        :loading="loading"
        :columns="columns"
        :data="list"
        :row-key="(row: Api.DockingLog.Item) => row.id"
        :pagination="false"
        striped
        :scroll-x="1100"
      />

      <!-- 底部分页 -->
      <div class="mt-16px flex items-center justify-between">
        <span class="text-12px text-gray-500">
          共查询到 <strong class="text-primary">{{ total }}</strong> 条对接记录，单次请求网络流量与报文已全面记录
        </span>
        <NPagination
          v-model:page="query.page"
          :page-size="query.pageSize"
          :item-count="total"
          show-size-picker
          :page-sizes="[10, 20, 50, 100]"
          @update:page="loadData"
          @update:page-size="(s: number) => { query.pageSize = s; query.page = 1; loadData(); }"
        />
      </div>
    </NCard>

    <!-- 报文详情抽屉 -->
    <NDrawer v-model:show="drawerVisible" :width="560" placement="right">
      <NDrawerContent :title="`报文详情 #${currentItem?.id || ''}`" closable>
        <div v-if="currentItem" class="flex flex-col gap-16px">
          <!-- 基础信息卡 -->
          <div class="rounded-6px bg-gray-50 p-12px dark:bg-dark-600 text-13px flex flex-col gap-8px">
            <div class="flex justify-between">
              <span class="text-gray-500">接口方向：</span>
              <NTag size="small" :type="currentItem.direction === 'in' ? 'success' : 'info'">
                {{ currentItem.direction === 'in' ? '外部对接我 (入站)' : '我对接外部 (出站)' }}
              </NTag>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">几点走的：</span>
              <span class="font-mono font-bold">{{ currentItem.created_at }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">谁走的：</span>
              <span class="font-bold text-primary">{{ currentItem.caller }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">走的接口：</span>
              <span class="font-medium">{{ currentItem.action }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">目标/路由：</span>
              <span class="font-mono text-12px">{{ currentItem.target }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">来源/目标 IP：</span>
              <span class="font-mono">{{ currentItem.ip }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">响应耗时：</span>
              <span class="font-mono font-bold">{{ currentItem.cost_ms }} ms</span>
            </div>
            <div class="flex justify-between border-t border-gray-200 dark:border-gray-700 pt-6px">
              <span class="text-gray-500 font-bold">单次消耗总流量：</span>
              <span class="font-mono font-bold text-emerald-600 text-14px">
                ⚡ {{ currentItem.traffic_text }} ({{ currentItem.traffic_total }} B)
              </span>
            </div>
            <div class="flex justify-between text-11px text-gray-400">
              <span>请求入向: {{ currentItem.bytes_in }} 字节</span>
              <span>响应出向: {{ currentItem.bytes_out }} 字节</span>
            </div>
          </div>

          <!-- 请求入参 -->
          <div class="flex flex-col gap-6px">
            <div class="flex items-center justify-between">
              <span class="font-bold text-13px">请求入参 Params (已敏感脱敏)：</span>
              <NButton size="tiny" secondary @click="copyText(currentItem.params, '入参')">复制</NButton>
            </div>
            <pre class="max-h-240px overflow-auto rounded-6px bg-gray-900 p-10px font-mono text-12px text-emerald-400 select-all">{{ formatJson(currentItem.params) }}</pre>
          </div>

          <!-- 响应结果 -->
          <div class="flex flex-col gap-6px">
            <div class="flex items-center justify-between">
              <span class="font-bold text-13px">响应结果 Response：</span>
              <NButton size="tiny" secondary @click="copyText(currentItem.response, '响应')">复制</NButton>
            </div>
            <pre class="max-h-280px overflow-auto rounded-6px bg-gray-900 p-10px font-mono text-12px text-cyan-300 select-all">{{ formatJson(currentItem.response) }}</pre>
          </div>
        </div>
      </NDrawerContent>
    </NDrawer>
  </div>
</template>
