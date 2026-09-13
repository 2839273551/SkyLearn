<script setup lang="ts">
import { computed, h, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import {
  NAlert,
  NBadge,
  NButton,
  NButtonGroup,
  NCard,
  NCode,
  NDataTable,
  NDivider,
  NDrawer,
  NDrawerContent,
  NInput,
  NPagination,
  NPopconfirm,
  NProgress,
  NSelect,
  NSpace,
  NStatistic,
  NSwitch,
  NTabPane,
  NTabs,
  NTag,
  NTooltip
} from 'naive-ui';
import { clearDockingLogs, fetchDockingLogList } from '@/service/api';
import { useAppStore } from '@/store/modules/app';

defineOptions({ name: 'DockingLog' });

const appStore = useAppStore();

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

// 视图模式: 'table' (表格视图) | 'stream' (运维日志终端)
const viewMode = ref<'table' | 'stream'>('table');

// 自动刷新
const autoRefreshInterval = ref<number | null>(null);
const refreshSeconds = ref<number>(0);
const refreshOptions = [
  { label: '手动刷新', value: 0 },
  { label: '5 秒轮询', value: 5 },
  { label: '15 秒轮询', value: 15 },
  { label: '30 秒轮询', value: 30 }
];

const query = reactive({
  page: 1,
  pageSize: 20,
  direction: '',
  action_filter: '',
  keyword: '',
  status: undefined as number | undefined
});

// 抽屉报文查看
const drawerVisible = ref(false);
const currentItem = ref<Api.DockingLog.Item | null>(null);
const detailActiveTab = ref('overview');

function viewDetail(item: Api.DockingLog.Item) {
  currentItem.value = item;
  detailActiveTab.value = 'overview';
  drawerVisible.value = true;
}

function copyText(text: string, label = '内容') {
  if (!text) {
    window.$message?.warning(`暂无${label}可复制`);
    return;
  }
  navigator.clipboard.writeText(text);
  window.$message?.success(`${label}已成功复制到剪贴板`);
}

function formatJson(raw: string) {
  if (!raw) return '{}';
  try {
    const obj = JSON.parse(raw);
    return JSON.stringify(obj, null, 2);
  } catch {
    return raw;
  }
}

// 快速过滤指定关键词
function quickFilter(val: string) {
  query.keyword = val;
  query.page = 1;
  loadData();
}

// 导出 CSV
function exportCsv() {
  if (!list.value.length) {
    window.$message?.warning('当前无可导出的流水数据');
    return;
  }
  const headers = ['记录ID', '请求时间', '通信方向', '调用主体', '来源IP', '请求方式', '接口服务', '端点路径', '单次网络吞吐(Bytes)', '吞吐格式化', '往返延迟(ms)', '响应状态'];
  const rows = list.value.map(item => [
    item.id,
    item.created_at,
    item.direction === 'in' ? '入站调用' : '出站转发',
    `"${item.caller.replace(/"/g, '""')}"`,
    item.ip,
    item.method,
    `"${item.action.replace(/"/g, '""')}"`,
    `"${item.target.replace(/"/g, '""')}"`,
    item.traffic_total,
    item.traffic_text,
    item.cost_ms,
    item.status === 1 ? '成功' : '失败'
  ]);
  const bom = String.fromCharCode(0xFEFF);
  const csvContent = bom + [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.setAttribute('href', url);
  link.setAttribute('download', `gateway_audit_log_${new Date().toISOString().slice(0, 10)}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  window.$message?.success('审计流水报表已导出');
}

const statusOptions = [
  { label: '全部状态', value: undefined },
  { label: '请求成功 (200)', value: 1 },
  { label: '异常拦截', value: 0 }
];

const columns: DataTableColumns<Api.DockingLog.Item> = [
  {
    title: '请求时间',
    key: 'created_at',
    width: 175,
    render: row =>
      h(
        NTooltip,
        { trigger: 'hover' },
        {
          trigger: () =>
            h(
              'span',
              { class: 'font-mono text-12px font-medium text-gray-700 dark:text-gray-300 tracking-tight' },
              row.created_at
            ),
          default: () => `审计流水 ID: #${row.id}`
        }
      )
  },
  {
    title: '通信方向',
    key: 'direction',
    width: 125,
    render: row => {
      const isIn = row.direction === 'in';
      return h(
        NTag,
        {
          size: 'small',
          type: isIn ? 'success' : 'info',
          bordered: false,
          round: true,
          class: 'font-medium font-mono text-11px'
        },
        {
          icon: () => (isIn ? '📥' : '📤'),
          default: () => (isIn ? '入站调用' : '出站转发')
        }
      );
    }
  },
  {
    title: '调用主体',
    key: 'caller',
    width: 175,
    render: row =>
      h('div', { class: 'flex flex-col gap-2px' }, [
        h(
          'span',
          {
            class: 'font-bold text-12px text-primary cursor-pointer hover:underline',
            onClick: () => row.uid > 0 && quickFilter(String(row.uid))
          },
          row.caller || '匿名调用'
        ),
        h(
          'span',
          {
            class: 'font-mono text-11px text-gray-400 hover:text-gray-600 cursor-pointer flex items-center gap-4px',
            onClick: () => quickFilter(row.ip)
          },
          [row.ip, h('span', { class: 'text-9px text-gray-300' }, '🔍')]
        )
      ])
  },
  {
    title: '接口服务与端点',
    key: 'action',
    minWidth: 200,
    render: row =>
      h('div', { class: 'flex flex-col gap-2px' }, [
        h('div', { class: 'flex items-center gap-6px' }, [
          h(
            NTag,
            {
              size: 'tiny',
              type: row.method === 'POST' ? 'success' : 'info',
              class: 'font-mono font-bold text-10px px-4px'
            },
            { default: () => row.method }
          ),
          h('span', { class: 'font-bold text-13px text-gray-800 dark:text-gray-200' }, row.action)
        ]),
        h(
          'span',
          { class: 'font-mono text-11px text-gray-400 truncate max-w-280px' },
          row.target || '/api.php'
        )
      ])
  },
  {
    title: '单次网络吞吐',
    key: 'traffic_total',
    width: 165,
    render: row => {
      const isHigh = row.traffic_total > 50 * 1024;
      const isMedium = row.traffic_total > 2 * 1024;
      const badgeClass = isHigh
        ? 'bg-rose-50 text-rose-600 border-rose-200 dark:bg-rose-900/20 dark:border-rose-800'
        : isMedium
        ? 'bg-amber-50 text-amber-600 border-amber-200 dark:bg-amber-900/20 dark:border-amber-800'
        : 'bg-emerald-50 text-emerald-600 border-emerald-200 dark:bg-emerald-900/20 dark:border-emerald-800';

      return h(
        NTooltip,
        { trigger: 'hover' },
        {
          trigger: () =>
            h(
              'div',
              {
                class: [
                  'font-mono font-bold px-8px py-4px rounded-6px border cursor-help inline-flex items-center gap-6px text-12px transition-all hover:scale-102',
                  badgeClass
                ]
              },
              [
                h('span', { class: 'text-11px' }, '⚡'),
                h('span', row.traffic_text),
                h(
                  'span',
                  { class: 'text-10px opacity-70 font-normal' },
                  `(${row.traffic_total >= 1024 ? Math.round(row.traffic_total / 1024) + 'K' : row.traffic_total + 'B'})`
                )
              ]
            ),
          default: () =>
            h('div', { class: 'text-12px p-4px leading-relaxed' }, [
              h('div', { class: 'font-bold text-emerald-400 mb-4px' }, '⚡ 单次网络 I/O 负载明细:'),
              h('div', `总计传输: ${row.traffic_text} (${row.traffic_total} 字节)`),
              h('div', `上行请求: ${row.bytes_in} 字节`),
              h('div', `下行响应: ${row.bytes_out} 字节`)
            ])
        }
      );
    }
  },
  {
    title: '往返延迟',
    key: 'cost_ms',
    width: 115,
    render: row => {
      const isSlow = row.cost_ms > 1000;
      const isWarn = row.cost_ms > 400;
      const colorClass = isSlow
        ? 'text-rose-500 font-bold'
        : isWarn
        ? 'text-amber-500 font-bold'
        : 'text-emerald-500 font-medium';
      return h('div', { class: 'flex items-center gap-4px' }, [
        h('span', { class: ['font-mono text-12px', colorClass] }, `${row.cost_ms} ms`),
        h(NProgress, {
          type: 'line',
          status: isSlow ? 'error' : isWarn ? 'warning' : 'success',
          percentage: Math.min(100, Math.max(10, Math.round((row.cost_ms / 2000) * 100))),
          showIndicator: false,
          style: { width: '36px' }
        })
      ]);
    }
  },
  {
    title: '响应状态',
    key: 'status',
    width: 105,
    render: row =>
      h(
        NTag,
        {
          size: 'small',
          type: row.status === 1 ? 'success' : 'error',
          round: true,
          class: 'font-mono text-11px font-bold'
        },
        { default: () => (row.status === 1 ? '200 OK' : 'FAILED') }
      )
  },
  {
    title: '报文详情',
    key: 'actions',
    width: 95,
    fixed: 'right',
    render: row =>
      h(
        NButton,
        {
          size: 'tiny',
          type: 'primary',
          secondary: true,
          class: 'font-medium',
          onClick: () => viewDetail(row)
        },
        { default: () => '查看报文' }
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
    window.$message?.success('审计流水归档清理完成');
    query.page = 1;
    loadData();
  }
}

// 自动轮询处理
function handleRefreshChange(seconds: number) {
  refreshSeconds.value = seconds;
  if (autoRefreshInterval.value) {
    clearInterval(autoRefreshInterval.value);
    autoRefreshInterval.value = null;
  }
  if (seconds > 0) {
    autoRefreshInterval.value = window.setInterval(() => {
      loadData();
    }, seconds * 1000);
    window.$message?.info(`已启用每 ${seconds} 秒自动轮询拉取`);
  } else {
    window.$message?.info('已切换为手动拉取');
  }
}

onMounted(() => {
  loadData();
});

onBeforeUnmount(() => {
  if (autoRefreshInterval.value) {
    clearInterval(autoRefreshInterval.value);
  }
});
</script>

<template>
  <div class="flex flex-col gap-14px p-10px sm:p-16px">
    <!-- 顶部极客 APM 实时全链路监控大盘 -->
    <div class="rounded-12px bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 p-16px text-white shadow-md border border-slate-700/50">
      <div class="mb-14px flex flex-wrap items-center justify-between gap-12px border-b border-slate-700/60 pb-12px">
        <div class="flex items-center gap-10px">
          <span class="inline-flex h-10px w-10px animate-ping rounded-full bg-emerald-400 opacity-75"></span>
          <span class="inline-flex h-8px w-8px -ml-13px rounded-full bg-emerald-500"></span>
          <h2 class="text-16px font-bold tracking-wide">接口对接与网关链路监控大盘</h2>
          <NTag size="tiny" type="success" round class="font-mono text-10px bg-emerald-500/20 text-emerald-300 border-none">
            实时监控中
          </NTag>
        </div>
        <div class="flex items-center gap-12px text-12px text-slate-300">
          <span class="hidden sm:inline font-mono">网桥探针活跃中</span>
          <NSelect
            v-model:value="refreshSeconds"
            :options="refreshOptions"
            size="tiny"
            class="w-130px"
            @update:value="handleRefreshChange"
          />
          <NButton size="tiny" type="primary" secondary class="text-white" :loading="loading" @click="loadData">
            立即刷新
          </NButton>
        </div>
      </div>

      <!-- 核心指标栅格 -->
      <div class="grid grid-cols-2 gap-12px sm:grid-cols-3 lg:grid-cols-6">
        <div class="rounded-8px bg-slate-800/80 p-12px border border-slate-700">
          <div class="text-11px font-mono text-slate-400">今日吞吐总量</div>
          <div class="mt-4px flex items-baseline gap-4px">
            <span class="text-22px font-bold font-mono text-white">{{ metrics.today_total }}</span>
            <span class="text-11px text-slate-400">次</span>
          </div>
          <div class="mt-4px text-10px text-emerald-400">● 实时链路捕获</div>
        </div>

        <div class="rounded-8px bg-slate-800/80 p-12px border border-slate-700">
          <div class="text-11px font-mono text-slate-400">入站调用</div>
          <div class="mt-4px flex items-baseline gap-4px">
            <span class="text-22px font-bold font-mono text-emerald-400">{{ metrics.today_in }}</span>
            <span class="text-11px text-slate-400">次</span>
          </div>
          <div class="mt-4px text-10px text-slate-400">外部对接网关</div>
        </div>

        <div class="rounded-8px bg-slate-800/80 p-12px border border-slate-700">
          <div class="text-11px font-mono text-slate-400">出站请求</div>
          <div class="mt-4px flex items-baseline gap-4px">
            <span class="text-22px font-bold font-mono text-sky-400">{{ metrics.today_out }}</span>
            <span class="text-11px text-slate-400">次</span>
          </div>
          <div class="mt-4px text-10px text-slate-400">上游货源通信转发</div>
        </div>

        <div class="rounded-8px bg-slate-800/80 p-12px border border-emerald-500/40 relative overflow-hidden">
          <div class="text-11px font-mono text-emerald-300 font-bold flex items-center gap-4px">
            <span>⚡ 网络吞吐消耗</span>
          </div>
          <div class="mt-4px flex items-baseline gap-4px">
            <span class="text-22px font-bold font-mono text-emerald-400">{{ metrics.today_traffic }}</span>
          </div>
          <div class="mt-4px text-10px text-slate-400">单次请求高精计流</div>
        </div>

        <div class="rounded-8px bg-slate-800/80 p-12px border border-slate-700">
          <div class="text-11px font-mono text-slate-400">平均响应延迟</div>
          <div class="mt-4px flex items-baseline gap-4px">
            <span class="text-22px font-bold font-mono" :class="metrics.avg_cost_ms < 300 ? 'text-emerald-400' : 'text-amber-400'">
              {{ metrics.avg_cost_ms }}
            </span>
            <span class="text-11px text-slate-400">ms</span>
          </div>
          <div class="mt-4px text-10px" :class="metrics.avg_cost_ms < 300 ? 'text-emerald-400' : 'text-amber-400'">
            {{ metrics.avg_cost_ms < 300 ? '极速响应' : '一般延迟' }}
          </div>
        </div>

        <div class="rounded-8px bg-slate-800/80 p-12px border border-slate-700">
          <div class="text-11px font-mono text-slate-400">服务可用率</div>
          <div class="mt-4px flex items-baseline gap-4px">
            <span class="text-22px font-bold font-mono text-teal-400">{{ metrics.success_rate }}</span>
          </div>
          <div class="mt-4px text-10px text-teal-300">无异常宕机</div>
        </div>
      </div>
    </div>

    <!-- 主卡片与操作表格 -->
    <NCard :bordered="false" class="rounded-12px shadow-sm">
      <template #header>
        <div class="flex items-center gap-12px">
          <span class="text-16px font-bold">API 网关流向与上游对接审计日志</span>
          <NButtonGroup size="small">
            <NButton :type="viewMode === 'table' ? 'primary' : 'default'" @click="viewMode = 'table'">
              📊 表格视图
            </NButton>
            <NButton :type="viewMode === 'stream' ? 'primary' : 'default'" @click="viewMode = 'stream'">
              💻 运维终端流
            </NButton>
          </NButtonGroup>
        </div>
      </template>

      <template #header-extra>
        <div class="flex flex-wrap items-center gap-8px">
          <NButton size="small" secondary @click="exportCsv">
            📥 导出审计报表
          </NButton>
          <template v-if="metrics.is_admin">
            <NPopconfirm @positive-click="handleClearLogs('7days')">
              <template #trigger>
                <NButton size="small" type="warning" secondary>清理 7 天前</NButton>
              </template>
              确定清理 7 天以前的历史审计流水吗？
            </NPopconfirm>
            <NPopconfirm @positive-click="handleClearLogs('all')">
              <template #trigger>
                <NButton size="small" type="error" secondary>清空全部流水</NButton>
              </template>
              确定清空所有接口流水吗？此操作不可逆！
            </NPopconfirm>
          </template>
        </div>
      </template>

      <!-- 流向分类 Tab 切换 -->
      <NTabs v-model:value="query.direction" type="line" class="mb-14px" @update:value="handleTabChange">
        <NTabPane name="" tab="🔄 全部请求" />
        <NTabPane name="in" tab="📥 入站调用" />
        <NTabPane name="out" tab="📤 出站转发" />
      </NTabs>

      <!-- 搜索过滤条 -->
      <div class="mb-16px flex flex-wrap items-center justify-between gap-12px rounded-8px bg-gray-50/70 p-12px dark:bg-dark-600/50">
        <div class="flex flex-wrap items-center gap-10px">
          <NInput
            v-model:value="query.action_filter"
            placeholder="动作指令 (如: query / order / balance)"
            clearable
            class="w-full sm:w-230px"
            @keyup.enter="handleSearch"
          />
          <NInput
            v-model:value="query.keyword"
            placeholder="检索 UID / 账号 / 来源 IP / 目标服务"
            clearable
            class="w-full sm:w-270px"
            @keyup.enter="handleSearch"
          />
          <NSelect
            v-model:value="query.status"
            :options="statusOptions"
            placeholder="响应状态"
            clearable
            class="w-full sm:w-170px"
          />
          <NButton type="primary" @click="handleSearch">
            <template #icon><span>🔍</span></template>
            过滤检索
          </NButton>
          <NButton secondary @click="handleReset">重置</NButton>
        </div>
        <div class="text-12px text-gray-400 font-mono">
          当前共匹配 <strong class="text-primary font-bold">{{ total }}</strong> 条访问审计记录
        </div>
      </div>

      <!-- 模式一：专业数据表格 -->
      <div v-if="viewMode === 'table'">
        <NDataTable
          :loading="loading"
          :columns="columns"
          :data="list"
          :row-key="(row: Api.DockingLog.Item) => row.id"
          :pagination="false"
          striped
          size="small"
          :scroll-x="1160"
        />
      </div>

      <!-- 模式二：运维流控制台终端 (Terminal Console View) -->
      <div v-else class="rounded-8px bg-slate-950 p-14px font-mono text-12px shadow-inner border border-slate-800">
        <div class="mb-10px flex items-center justify-between border-b border-slate-800 pb-8px text-slate-400">
          <span class="flex items-center gap-6px">
            <span class="h-3 w-3 rounded-full bg-rose-500 inline-block"></span>
            <span class="h-3 w-3 rounded-full bg-amber-500 inline-block"></span>
            <span class="h-3 w-3 rounded-full bg-emerald-500 inline-block"></span>
            <span class="ml-8px font-bold text-slate-300">Terminal Log Stream (/var/log/docking_gateway.log)</span>
          </span>
          <span class="text-emerald-400 text-11px">● 实时链路捕获已启用</span>
        </div>

        <div v-if="!list.length" class="py-30px text-center text-slate-600">
          ~ [EMPTY] 暂无流水记录 ~
        </div>

        <div v-else class="flex flex-col gap-6px max-h-560px overflow-y-auto pr-6px">
          <div
            v-for="item in list"
            :key="item.id"
            class="group flex flex-wrap items-center justify-between rounded-4px px-8px py-6px hover:bg-slate-900 transition-colors border-l-2"
            :class="item.status === 1 ? 'border-emerald-500 bg-slate-900/30' : 'border-rose-500 bg-rose-950/20'"
          >
            <div class="flex flex-wrap items-center gap-10px">
              <span class="text-slate-500 text-11px select-all">{{ item.created_at }}</span>
              <span
                class="rounded-2px px-4px py-1px text-10px font-bold"
                :class="item.direction === 'in' ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-sky-950 text-sky-400 border border-sky-800'"
              >
                {{ item.direction === 'in' ? 'INBOUND' : 'OUTBOUND' }}
              </span>
              <span
                class="rounded-2px px-4px py-1px text-10px font-bold"
                :class="item.method === 'POST' ? 'bg-emerald-900/60 text-emerald-300' : 'bg-blue-900/60 text-blue-300'"
              >
                {{ item.method }}
              </span>
              <span class="text-slate-200 font-bold hover:text-primary cursor-pointer" @click="viewDetail(item)">
                {{ item.action }}
              </span>
              <span class="text-slate-500 text-11px truncate max-w-220px">{{ item.target }}</span>
              <span class="text-slate-400 text-11px">by {{ item.caller }}</span>
              <span class="text-slate-600 text-11px">[{{ item.ip }}]</span>
            </div>

            <div class="flex items-center gap-12px">
              <span class="text-emerald-400 font-bold">⚡ {{ item.traffic_text }}</span>
              <span :class="item.cost_ms < 300 ? 'text-slate-400' : 'text-amber-400'">{{ item.cost_ms }}ms</span>
              <span :class="item.status === 1 ? 'text-emerald-400 font-bold' : 'text-rose-400 font-bold'">
                {{ item.status === 1 ? '200 OK' : 'FAILED' }}
              </span>
              <NButton size="tiny" secondary type="primary" @click="viewDetail(item)">
                检视报文
              </NButton>
            </div>
          </div>
        </div>
      </div>

      <!-- 底部分页 -->
      <div class="mt-16px flex flex-wrap items-center justify-between gap-12px border-t border-gray-100 dark:border-gray-800 pt-12px">
        <span class="text-12px text-gray-400">
          全链路入向与出向网络字节负载均已被微秒级捕获并生成审计留痕
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

    <!-- 深度报文检视器 (Payload Inspector Drawer) -->
    <NDrawer v-model:show="drawerVisible" :width="appStore.isMobile ? '100%' : 620" placement="right">
      <NDrawerContent :title="`全链路报文检查器 #${currentItem?.id || ''}`" closable>
        <div v-if="currentItem" class="flex flex-col gap-16px">
          <!-- 核心元数据看板 -->
          <div class="rounded-8px border border-gray-200 bg-gray-50/80 p-14px dark:border-dark-600 dark:bg-dark-600/50">
            <div class="mb-10px flex items-center justify-between border-b border-gray-200 pb-8px dark:border-dark-500">
              <div class="flex items-center gap-8px">
                <NTag size="small" :type="currentItem.direction === 'in' ? 'success' : 'info'" round>
                  {{ currentItem.direction === 'in' ? '📥 入站调用' : '📤 出站转发' }}
                </NTag>
                <NTag size="small" :type="currentItem.status === 1 ? 'success' : 'error'">
                  {{ currentItem.status === 1 ? '200 OK 响应正常' : '异常 / 拦截' }}
                </NTag>
              </div>
              <span class="font-mono text-12px text-gray-500">{{ currentItem.created_at }}</span>
            </div>

            <div class="grid grid-cols-2 gap-y-8px text-13px">
              <div>
                <span class="text-gray-400">调用凭据主体：</span>
                <strong class="text-primary font-mono">{{ currentItem.caller }}</strong>
              </div>
              <div>
                <span class="text-gray-400">来源/目标 IP：</span>
                <span class="font-mono font-medium">{{ currentItem.ip }}</span>
              </div>
              <div>
                <span class="text-gray-400">请求服务动作：</span>
                <span class="font-medium">{{ currentItem.action }}</span>
              </div>
              <div>
                <span class="text-gray-400">请求方式：</span>
                <span class="font-mono font-bold">{{ currentItem.method }}</span>
              </div>
              <div class="col-span-2">
                <span class="text-gray-400">访问端点 URI：</span>
                <span class="font-mono text-12px">{{ currentItem.target }}</span>
              </div>
            </div>

            <!-- 网络流量与性能指示 -->
            <div class="mt-12px rounded-6px bg-emerald-50/70 p-10px border border-emerald-200/80 dark:bg-emerald-950/20 dark:border-emerald-800">
              <div class="flex items-center justify-between text-13px">
                <span class="font-bold text-emerald-800 dark:text-emerald-300">⚡ 单次网络 I/O 负载总计：</span>
                <strong class="font-mono text-16px text-emerald-600 dark:text-emerald-400">
                  {{ currentItem.traffic_text }} ({{ currentItem.traffic_total }} Bytes)
                </strong>
              </div>
              <div class="mt-6px flex items-center justify-between text-11px text-emerald-700 dark:text-emerald-400/80 border-t border-emerald-200/40 pt-6px">
                <span>上行负载: {{ currentItem.bytes_in }} 字节</span>
                <span>下行负载: {{ currentItem.bytes_out }} 字节</span>
                <span>往返延迟: {{ currentItem.cost_ms }} ms</span>
              </div>
            </div>
          </div>

          <!-- 报文选项卡 -->
          <NTabs v-model:value="detailActiveTab" type="segment">
            <NTabPane name="overview" tab="📋 综合全览" />
            <NTabPane name="request" tab="📤 请求入参" />
            <NTabPane name="response" tab="📥 响应结果" />
          </NTabs>

          <!-- 选项卡 1：综合视图 -->
          <div v-if="detailActiveTab === 'overview'" class="flex flex-col gap-14px">
            <div>
              <div class="mb-6px flex items-center justify-between">
                <span class="font-bold text-13px">请求入参摘要 (敏感凭据已自动脱敏)：</span>
                <NButton size="tiny" secondary @click="copyText(currentItem.params, '请求入参')">一键复制</NButton>
              </div>
              <div class="rounded-8px border border-slate-800 overflow-hidden">
                <NCode :code="formatJson(currentItem.params)" language="json" show-line-numbers />
              </div>
            </div>

            <div>
              <div class="mb-6px flex items-center justify-between">
                <span class="font-bold text-13px">返回响应报文：</span>
                <NButton size="tiny" secondary @click="copyText(currentItem.response, '响应结果')">一键复制</NButton>
              </div>
              <div class="rounded-8px border border-slate-800 overflow-hidden">
                <NCode :code="formatJson(currentItem.response)" language="json" show-line-numbers />
              </div>
            </div>
          </div>

          <!-- 选项卡 2：纯请求入参 -->
          <div v-else-if="detailActiveTab === 'request'" class="flex flex-col gap-8px">
            <div class="flex items-center justify-between">
              <span class="text-12px text-gray-400">已自动执行密码与 API Key 脱敏掩码保护</span>
              <NButton size="small" type="primary" secondary @click="copyText(currentItem.params, '完整请求入参')">
                复制全部入参
              </NButton>
            </div>
            <div class="rounded-8px border border-slate-800 overflow-hidden">
              <NCode :code="formatJson(currentItem.params)" language="json" show-line-numbers />
            </div>
          </div>

          <!-- 选项卡 3：纯响应结果 -->
          <div v-else-if="detailActiveTab === 'response'" class="flex flex-col gap-8px">
            <div class="flex items-center justify-between">
              <span class="text-12px text-gray-400">服务端输出的完整原始 JSON 报文</span>
              <NButton size="small" type="primary" secondary @click="copyText(currentItem.response, '完整响应报文')">
                复制全部响应
              </NButton>
            </div>
            <div class="rounded-8px border border-slate-800 overflow-hidden">
              <NCode :code="formatJson(currentItem.response)" language="json" show-line-numbers />
            </div>
          </div>
        </div>
      </NDrawerContent>
    </NDrawer>
  </div>
</template>
