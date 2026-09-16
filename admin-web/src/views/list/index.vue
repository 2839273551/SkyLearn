<script setup lang="ts">
import { computed, h, onMounted, reactive, ref } from 'vue';
import type { DataTableRowKey, DataTableColumns } from 'naive-ui';
import {
  NAlert,
  NButton,
  NCard,
  NDataTable,
  NDescriptions,
  NDescriptionsItem,
  NDivider,
  NDropdown,
  NInput,
  NModal,
  NPagination,
  NPopconfirm,
  NProgress,
  NSelect,
  NSpace,
  NTag
} from 'naive-ui';
import {
  batchDeleteOrders,
  batchRebrushOrders,
  batchRefundOrders,
  batchSyncOrders,
  batchUpdateOrderStatus,
  dockOrder,
  fetchOrders,
  rebrushOrder,
  syncOrderProgress
} from '@/service/api';
import { useAuthStore } from '@/store/modules/auth';
import { useAppStore } from '@/store/modules/app';

defineOptions({ name: 'List' });

const authStore = useAuthStore();
const appStore = useAppStore();
const isSuperAdmin = computed(() => authStore.userInfo.roles.includes('R_SUPER'));

const loading = ref(false);
const batchLoading = ref(false);
const records = ref<Api.Orders.Record[]>([]);
const total = ref(0);
const query = reactive<Api.Orders.Query>({ page: 1, pageSize: 20, keyword: '', status: '' });
const checkedRowKeys = ref<DataTableRowKey[]>([]);

const actionLoadingMap = reactive<Record<string, boolean>>({});

// 详情弹窗相关
const detailModalVisible = ref(false);
const currentDetail = ref<Api.Orders.Record | null>(null);

function openDetail(row: Api.Orders.Record) {
  currentDetail.value = row;
  detailModalVisible.value = true;
}

const statusOptions = [
  { label: '全部状态', value: '' },
  { label: '待处理', value: '待处理' },
  { label: '进行中', value: '进行中' },
  { label: '已完成', value: '已完成' },
  { label: '补刷中', value: '补刷中' },
  { label: '异常', value: '异常' },
  { label: '已取消', value: '已取消' }
];

function statusType(status: string): 'default' | 'info' | 'success' | 'warning' | 'error' {
  if (status === '已完成') return 'success';
  if (status === '进行中') return 'info';
  if (status === '补刷中') return 'warning';
  if (status === '异常') return 'error';
  if (status === '待处理') return 'default';
  return 'default';
}

function copyText(text: string, label = '内容') {
  if (!text) {
    window.$message?.warning('暂无可复制内容');
    return;
  }
  navigator.clipboard.writeText(text);
  window.$message?.success(`${label}已复制到剪贴板`);
}

// 复制全部（学校+账号+密码+课程）
function copyAll(row: Api.Orders.Record) {
  const lines = [
    `学校: ${row.school || '无'}`,
    `账号: ${row.account}`,
    `密码: ${row.password || '无'}`,
    `课程: ${row.courseName}`,
    `订单号: ${row.orderId}`
  ];
  copyText(lines.join('\n'), '学员全部信息');
}

// 复制详细模态框整套格式化文本
function copyFormattedDetail(row: Api.Orders.Record) {
  const text = [
    `课程类型：${row.platform || '无'}`,
    `1. 账号信息：${row.school ? row.school + ' ' : ''}${row.account} ${row.password || ''}`,
    `2. 课程名字：${row.courseName}`,
    `3. KCID：${row.kcid || '无'}`,
    `4. 站内反馈id：${row.orderId}`,
    `5. 上游返回YID：${row.yid || '无'}`,
    `6. 下单时间：${row.createdAt}`,
    `7. 上次同步时间：${row.finalupdate || '暂无'}`,
    `8. 订单状态：${row.status}`,
    `9. 进度：${row.progress || '0%'}`,
    `10. 备注：${row.remarks || '无'}`
  ].join('\n');
  copyText(text, '订单完整详情');
}

// 单单同步最新进度
async function handleSync(row: Api.Orders.Record) {
  const key = `sync_${row.orderId}`;
  actionLoadingMap[key] = true;
  const { data, error } = await syncOrderProgress(row.orderId);
  actionLoadingMap[key] = false;
  if (!error && data) {
    if (data.process) row.progress = data.process;
    if (data.status) row.status = data.status;
    if (data.remarks) row.remarks = data.remarks;
    if ((data as any).yid) row.yid = String((data as any).yid);
    if ((data as any).finalupdate) row.finalupdate = String((data as any).finalupdate);
    if (currentDetail.value && currentDetail.value.orderId === row.orderId) {
      if (data.process) currentDetail.value.progress = data.process;
      if (data.status) currentDetail.value.status = data.status;
      if (data.remarks) currentDetail.value.remarks = data.remarks;
      if ((data as any).yid) currentDetail.value.yid = String((data as any).yid);
      if ((data as any).finalupdate) currentDetail.value.finalupdate = String((data as any).finalupdate);
    }
    window.$message?.success(`订单 #${row.orderId} 进度同步成功: ${data.process || data.status}`);
  }
}

// 单单申请补刷
async function handleRebrush(row: Api.Orders.Record) {
  const key = `rebrush_${row.orderId}`;
  actionLoadingMap[key] = true;
  const { data, error } = await rebrushOrder(row.orderId);
  actionLoadingMap[key] = false;
  if (!error && data) {
    row.status = data.status || '补刷中';
    if (currentDetail.value && currentDetail.value.orderId === row.orderId) {
      currentDetail.value.status = data.status || '补刷中';
    }
    window.$message?.success(`订单 #${row.orderId} 已成功加入补刷排队！`);
  }
}

// 管理员重新向货源提交
async function handleDock(row: Api.Orders.Record) {
  const key = `dock_${row.orderId}`;
  actionLoadingMap[key] = true;
  const { data, error } = await dockOrder(row.orderId);
  actionLoadingMap[key] = false;
  if (!error && data) {
    row.dockStatus = String(data.dockstatus);
    row.status = data.status || '进行中';
    if (currentDetail.value && currentDetail.value.orderId === row.orderId) {
      currentDetail.value.dockStatus = String(data.dockstatus);
      currentDetail.value.status = data.status || '进行中';
    }
    window.$message?.success(`订单 #${row.orderId} 重新向上游货源提交成功！`);
  }
}

// ==========================================
// 批量操作处理函数
// ==========================================
function checkSelected(): boolean {
  if (!checkedRowKeys.value.length) {
    window.$message?.warning('请先勾选需要操作的订单！');
    return false;
  }
  return true;
}

// 批量修改显示状态 (type: 1)
async function handleBatchStatus(statusText: string) {
  if (!checkSelected()) return;
  batchLoading.value = true;
  const res = await batchUpdateOrderStatus(checkedRowKeys.value as string[], statusText, 1);
  batchLoading.value = false;
  if (res !== null) {
    window.$message?.success(`已批量将 ${checkedRowKeys.value.length} 笔订单状态变更为: ${statusText}`);
    checkedRowKeys.value = [];
    loadOrders();
  }
}

// 批量修改处理对接状态 (type: 2)
async function handleBatchDockStatus(dockStatusVal: string, label: string) {
  if (!checkSelected()) return;
  batchLoading.value = true;
  const res = await batchUpdateOrderStatus(checkedRowKeys.value as string[], dockStatusVal, 2);
  batchLoading.value = false;
  if (res !== null) {
    window.$message?.success(`已批量将 ${checkedRowKeys.value.length} 笔订单对接状态变更为: ${label}`);
    checkedRowKeys.value = [];
    loadOrders();
  }
}

// 批量退款
async function handleBatchRefund() {
  if (!checkSelected()) return;
  batchLoading.value = true;
  const res = await batchRefundOrders(checkedRowKeys.value as string[]);
  batchLoading.value = false;
  if (res !== null) {
    window.$message?.success('批量退款成功');
    checkedRowKeys.value = [];
    loadOrders();
  }
}

// 批量删除
async function handleBatchDelete() {
  if (!checkSelected()) return;
  batchLoading.value = true;
  const res = await batchDeleteOrders(checkedRowKeys.value as string[]);
  batchLoading.value = false;
  if (res !== null) {
    window.$message?.success('批量删除订单成功');
    checkedRowKeys.value = [];
    loadOrders();
  }
}

// 批量更新同步进度
async function handleBatchSync() {
  if (!checkSelected()) return;
  batchLoading.value = true;
  const res = await batchSyncOrders(checkedRowKeys.value as string[]);
  batchLoading.value = false;
  if (res !== null) {
    window.$message?.success('批量同步进度完成');
    checkedRowKeys.value = [];
    loadOrders();
  }
}

// 批量补单
async function handleBatchRebrush() {
  if (!checkSelected()) return;
  batchLoading.value = true;
  const res = await batchRebrushOrders(checkedRowKeys.value as string[]);
  batchLoading.value = false;
  if (res !== null) {
    window.$message?.success('批量补单加入排队成功');
    checkedRowKeys.value = [];
    loadOrders();
  }
}

const taskStatusDropdownOptions = [
  { label: '🕒 待处理', key: '待处理' },
  { label: '🟢 已完成', key: '已完成' },
  { label: '🔵 进行中', key: '进行中' },
  { label: '🔴 异常', key: '异常' },
  { label: '⚪ 已取消', key: '已取消' }
];

function handleSelectTaskStatus(key: string) {
  handleBatchStatus(key);
}

const dockStatusDropdownOptions = [
  { label: '⏳ 待处理', key: '0' },
  { label: '✅ 处理成功', key: '1' },
  { label: '❌ 提交失败', key: '2' },
  { label: '🔁 重复下单', key: '3' },
  { label: '🚫 已取消', key: '4' },
  { label: '🏬 自营订单', key: '99' },
  { type: 'divider', key: 'd1' },
  { label: '💰 批量退款 (原路退回余额)', key: 'refund' },
  { label: '🗑️ 批量彻底删除', key: 'delete' }
];

const dockStatusLabelMap: Record<string, string> = {
  '0': '待处理',
  '1': '处理成功',
  '2': '提交失败',
  '3': '重复下单',
  '4': '已取消',
  '99': '自营订单'
};

function handleSelectDockStatus(key: string) {
  if (key === 'refund') {
    window.$dialog?.warning({
      title: '批量退款确认',
      content: `确定为已勾选的 ${checkedRowKeys.value.length} 笔订单全额退款吗？资金将原路退回用户余额。`,
      positiveText: '确定退款',
      negativeText: '取消',
      onPositiveClick: handleBatchRefund
    });
    return;
  }
  if (key === 'delete') {
    window.$dialog?.error({
      title: '批量删除确认',
      content: `确定彻底删除已勾选的 ${checkedRowKeys.value.length} 笔订单吗？此操作不可恢复！`,
      positiveText: '确定彻底删除',
      negativeText: '取消',
      onPositiveClick: handleBatchDelete
    });
    return;
  }
  handleBatchDockStatus(key, dockStatusLabelMap[key] || key);
}

/**
 * 严格按照用户指定与经典排版顺序（状态与进度合二为一）：
 * [复选框] [操作] [详细] [订单所属平台] [账号] [备注] [任务名称] [状态与进度] [订单详细信息] [时间] [状态(对接状态)] [UID] [扣费]
 */
const columns = computed<DataTableColumns<Api.Orders.Record>>(() => {
  const cols: DataTableColumns<Api.Orders.Record> = [
    // 0. 多选小框 (复选框)
    {
      type: 'selection',
      width: 36
    },
    // 1. 操作
    {
      title: '操作',
      key: 'actions',
      width: 95,
      render: row =>
        h(NSpace, { size: 4, align: 'center' }, () => [
          h(
            NButton,
            {
              size: 'tiny',
              type: 'primary',
              secondary: true,
              loading: Boolean(actionLoadingMap[`sync_${row.orderId}`]),
              onClick: () => handleSync(row)
            },
            { default: () => '🔄 同步' }
          ),
          h(
            NPopconfirm,
            {
              onPositiveClick: () => handleRebrush(row)
            },
            {
              trigger: () =>
                h(
                  NButton,
                  {
                    size: 'tiny',
                    type: 'warning',
                    secondary: true,
                    loading: Boolean(actionLoadingMap[`rebrush_${row.orderId}`])
                  },
                  { default: () => '🚀 补刷' }
                ),
              default: () => `确定为订单 #${row.orderId} 申请补刷吗？`
            }
          )
        ])
    },
    // 2. 详细
    {
      title: '详细',
      key: 'detail',
      width: 40,
      align: 'center',
      render: row =>
        h(
          NButton,
          {
            size: 'tiny',
            type: 'info',
            round: true,
            title: '查看订单11项详细参数',
            onClick: () => openDetail(row)
          },
          { default: () => '🔍' }
        )
    },
    // 3. 订单所属平台
    {
      title: '订单所属平台',
      key: 'platform',
      minWidth: 125,
      render: row =>
        h(
          'div',
          { class: 'whitespace-normal break-words font-medium leading-relaxed text-gray-800 dark:text-gray-100' },
          row.platform || '无'
        )
    },
    // 4. 账号
    {
      title: '账号',
      key: 'account',
      minWidth: 160,
      render: row =>
        h('div', { class: 'flex flex-col gap-3px py-3px text-12px' }, [
          row.school
            ? h('div', { class: 'flex items-center gap-4px' }, [
                h(
                  NButton,
                  { size: 'tiny', tertiary: true, type: 'primary', class: 'px-4px h-18px text-10px', onClick: () => copyText(row.school, '学校') },
                  { default: () => '学校' }
                ),
                h('span', { class: 'text-gray-600 dark:text-gray-300 break-words whitespace-normal leading-normal font-medium' }, row.school)
              ])
            : null,
          h('div', { class: 'flex items-center gap-4px' }, [
            h(
              NButton,
              { size: 'tiny', tertiary: true, type: 'info', class: 'px-4px h-18px text-10px', onClick: () => copyText(row.account, '账号') },
              { default: () => '账号' }
            ),
            h('span', { class: 'font-mono font-bold text-gray-800 dark:text-gray-100' }, row.account)
          ]),
          row.password
            ? h('div', { class: 'flex items-center gap-4px' }, [
                h(
                  NButton,
                  { size: 'tiny', tertiary: true, class: 'px-4px h-18px text-10px text-gray-400', onClick: () => copyText(row.password || '', '密码') },
                  { default: () => '密码' }
                ),
                h('span', { class: 'font-mono text-11px text-gray-500' }, row.password)
              ])
            : null,
          h('div', { class: 'mt-2px' }, [
            h(
              NButton,
              { size: 'tiny', quaternary: true, type: 'success', class: 'px-4px h-18px text-10px', onClick: () => copyAll(row) },
              { default: () => '📋 全部复制' }
            )
          ])
        ])
    },
    // 5. 任务名称
    {
      title: '任务名称',
      key: 'courseName',
      minWidth: 135,
      render: row =>
        h(
          'div',
          { class: 'whitespace-normal break-words font-medium leading-relaxed text-gray-800 dark:text-gray-100' },
          row.courseName || '无'
        )
    },
    // 6. 任务状态与进度 (状态与进度二合一：高饱和清晰醒目款)
    {
      title: '状态与进度',
      key: 'statusProgress',
      minWidth: 145,
      render: row => {
        const pStr = row.progress || '0%';
        const numMatch = pStr.match(/(\d+(?:\.\d+)?)/);
        const percent = numMatch ? Math.min(100, Math.max(0, parseFloat(numMatch[1]))) : (row.status === '已完成' ? 100 : 0);
        const isComplete = percent >= 100 || row.status === '已完成';
        const isError = row.status === '异常';
        const isCancel = row.status === '已取消';

        // 状态标签属性与高饱和进度条渐变配置
        let tagType: 'success' | 'info' | 'warning' | 'error' | 'default' = 'info';
        let statusLabel = row.status || '进行中';
        let trackColor = 'linear-gradient(90deg, #3b82f6 0%, #6366f1 100%)';

        if (isComplete) {
          tagType = 'success';
          statusLabel = '已完成';
          trackColor = 'linear-gradient(90deg, #10b981 0%, #059669 100%)';
        } else if (isError) {
          tagType = 'error';
          statusLabel = '异常';
          trackColor = 'linear-gradient(90deg, #ef4444 0%, #dc2626 100%)';
        } else if (isCancel) {
          tagType = 'default';
          statusLabel = '已取消';
          trackColor = '#94a3b8';
        } else if (percent === 0 || row.status === '待处理') {
          tagType = 'warning';
          statusLabel = '待处理';
          trackColor = 'linear-gradient(90deg, #f59e0b 0%, #d97706 100%)';
        }

        return h('div', { class: 'flex flex-col gap-4px w-135px py-2px' }, [
          // 上排：高饱和彩色药丸状态标签 + 粗体百分比
          h('div', { class: 'flex items-center justify-between' }, [
            h(
              NTag,
              { type: tagType, size: 'tiny', round: true, class: 'font-bold px-6px' },
              { default: () => statusLabel }
            ),
            h(
              'span',
              {
                class: `font-mono text-12px font-extrabold ${
                  isComplete
                    ? 'text-emerald-600 dark:text-emerald-400'
                    : 'text-gray-800 dark:text-gray-100'
                }`
              },
              isComplete ? '100% ✔' : `${percent}%`
            )
          ]),
          // 下排：高清晰度实心双色渐变轨道条 (高度 6px，强对比度底槽)
          h(
            'div',
            { class: 'w-full h-6px rounded-full bg-gray-200 dark:bg-dark-500 overflow-hidden shadow-inner' },
            [
              h('div', {
                class: 'h-full rounded-full transition-all duration-300',
                style: {
                  width: `${percent}%`,
                  background: trackColor
                }
              })
            ]
          )
        ]);
      }
    },
    // 7. 订单详细信息 (分段排版，规整清晰)
    {
      title: '订单详细信息',
      key: 'detailInfo',
      minWidth: 190,
      render: row => {
        const text = row.remarks || (row.finalupdate ? `上次同步: ${row.finalupdate}` : '暂无详细上游记录');
        if (!row.remarks) {
          return h('span', { class: 'text-gray-400 text-12px' }, text);
        }
        // 如果包含 || 或 | 分隔符，做分行结构化展示
        const delimiter = text.includes('||') ? '||' : (text.includes('|') ? '|' : null);
        if (delimiter) {
          const parts = text.split(delimiter).map((s: string) => s.trim()).filter(Boolean);
          return h(
            'div',
            { class: 'flex flex-col gap-3px py-3px text-12px leading-snug' },
            parts.map((p: string, idx: number) =>
              h(
                'div',
                {
                  class: idx === 0
                    ? 'text-gray-800 dark:text-gray-200 font-medium'
                    : 'text-gray-600 dark:text-gray-400 text-11px font-mono'
                },
                (parts.length > 1 && idx > 0 ? '• ' : '') + p
              )
            )
          );
        }
        return h(
          'div',
          { class: 'whitespace-normal break-words text-13px sm:text-14px font-medium leading-relaxed text-gray-900 dark:text-gray-100 py-4px' },
          text
        );
      }
    },
    // 10. 时间 (双行紧凑展示)
    {
      title: '时间',
      key: 'createdAt',
      width: 95,
      render: row => {
        const parts = (row.createdAt || '').split(' ');
        if (parts.length === 2) {
          return h('div', { class: 'text-11px font-mono text-gray-500 dark:text-gray-400 leading-tight' }, [
            h('div', {}, parts[0]),
            h('div', {}, parts[1])
          ]);
        }
        return h('span', { class: 'text-11px font-mono' }, row.createdAt || '-');
      }
    }
  ];

  // 11. 状态 (对接状态: 严格根据用户指令，放到后面，且仅管理员可见)
  if (isSuperAdmin.value) {
    cols.push({
      title: '状态',
      key: 'dockStatus',
      width: 85,
      render: row => {
        const ds = String(row.dockStatus ?? '');
        if (ds === '1') {
          return h(NTag, { type: 'success', size: 'small', round: true }, { default: () => '处理成功' });
        }
        if (ds === '0') {
          return h(NTag, { type: 'info', size: 'small', round: true }, { default: () => '等待处理' });
        }
        if (ds === '2') {
          // 提交失败：管理员可点击重新对接提交
          return h(
            NPopconfirm,
            {
              onPositiveClick: () => handleDock(row)
            },
            {
              trigger: () =>
                h(
                  NButton,
                  {
                    size: 'tiny',
                    type: 'error',
                    dashed: true,
                    loading: Boolean(actionLoadingMap[`dock_${row.orderId}`])
                  },
                  { default: () => '❌ 提交失败 (重推)' }
                ),
              default: () => `确定重新向货源提交订单 #${row.orderId} 吗？`
            }
          );
        }
        if (ds === '3') {
          return h(NTag, { type: 'default', size: 'small', round: true }, { default: () => '重复下单' });
        }
        if (ds === '4') {
          return h(NTag, { type: 'default', size: 'small', round: true }, { default: () => '已取消' });
        }
        if (ds === '99') {
          return h(NTag, { type: 'warning', size: 'small', round: true }, { default: () => '自营订单' });
        }
        return h(NTag, { type: 'default', size: 'small', round: true }, { default: () => '未知状态' });
      }
    });
  }

  // 12. UID
  cols.push({
    title: 'UID',
    key: 'ownerId',
    width: 42,
    align: 'center',
    render: row => h('span', { class: 'font-mono text-12px font-bold text-gray-500' }, row.ownerId || '1')
  });

  // 13. 扣费
  cols.push({
    title: '扣费',
    key: 'fees',
    minWidth: 72,
    align: 'center',
    render: row =>
      h(
        'div',
        { class: 'inline-flex items-baseline justify-center font-mono font-bold text-rose-500 whitespace-nowrap' },
        [
          h('span', { class: 'text-11px mr-1px' }, '¥'),
          h('span', { class: 'text-12px' }, row.fees ?? '0.00')
        ]
      )
  });

  return cols;
});

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
  <NSpace vertical :size="16" class="p-10px sm:p-16px">
    <NAlert
      v-if="authStore.userInfo.ddggkg && authStore.userInfo.ddgg"
      type="warning"
      title="订单须知公告"
      class="card-wrapper whitespace-pre-wrap rounded-8px"
      closable
    >
      {{ authStore.userInfo.ddgg }}
    </NAlert>

    <NCard title="订单列表" :bordered="false" class="rounded-8px shadow-sm">
      <!-- 顶部查询栏 -->
      <div class="mb-14px flex flex-wrap items-center gap-12px">
        <NInput
          v-model:value="query.keyword"
          clearable
          class="w-320px lt-sm:w-full"
          placeholder="订单号、账号、课程、学校或备注"
          @keyup.enter="search"
        >
          <template #prefix><SvgIcon icon="ph:magnifying-glass" /></template>
        </NInput>
        <NSelect v-model:value="query.status" class="w-140px" :options="statusOptions" />
        <NButton type="primary" @click="search">查询</NButton>
        <NButton @click="reset">重置</NButton>
        <NButton quaternary :loading="loading" @click="loadOrders">
          <template #icon><SvgIcon icon="ph:arrow-clockwise" /></template>
          刷新
        </NButton>
      </div>

      <!-- 快捷批量动作与提示栏 (完全消除折叠空行，采用一体化下拉操作) -->
      <div class="mb-14px flex flex-wrap items-center justify-between gap-12px rounded-8px bg-blue-50/60 dark:bg-dark-600 p-10px border border-blue-100 dark:border-dark-500">
        <div class="flex flex-wrap items-center gap-8px">
          <span class="text-13px text-gray-700 dark:text-gray-200">
            已勾选 <strong class="text-primary font-mono text-14px font-bold">{{ checkedRowKeys.length }}</strong> 项
          </span>
          <NButton size="small" type="primary" :loading="batchLoading" :disabled="!checkedRowKeys.length" @click="handleBatchSync">
            ⬇️ 批量更新
          </NButton>
          <NButton size="small" type="warning" :loading="batchLoading" :disabled="!checkedRowKeys.length" @click="handleBatchRebrush">
            📝 批量补单
          </NButton>
          <NDropdown trigger="click" :options="taskStatusDropdownOptions" @select="handleSelectTaskStatus">
            <NButton size="small" type="info" secondary :loading="batchLoading" :disabled="!checkedRowKeys.length">
              ✏️ 修改任务状态 ▾
            </NButton>
          </NDropdown>
          <NDropdown v-if="isSuperAdmin" trigger="click" :options="dockStatusDropdownOptions" @select="handleSelectDockStatus">
            <NButton size="small" type="error" secondary :loading="batchLoading" :disabled="!checkedRowKeys.length">
              ⚙️ 处理状态与售后 ▾
            </NButton>
          </NDropdown>
          <NButton size="small" tertiary :disabled="!checkedRowKeys.length" @click="checkedRowKeys = []">
            清空勾选
          </NButton>
        </div>
        <div class="text-12px text-amber-600 dark:text-amber-400 font-medium">
          ⚠️ 订单如有问题请自行检查后反馈即可，请不要重复下相同订单
        </div>
      </div>

      <!-- 数据表格 (带首列多选框与移动端横向滑动保护) -->
      <NDataTable
        v-model:checked-row-keys="checkedRowKeys"
        :loading="loading"
        :columns="columns"
        :data="records"
        :row-key="(row: Api.Orders.Record) => row.orderId"
        :pagination="false"
        :scroll-x="1280"
        striped
      />

      <div class="mt-16px flex justify-end">
        <NPagination
          v-model:page="query.page"
          v-model:page-size="query.pageSize"
          :item-count="total"
          :page-sizes="[20, 50, 100]"
          show-size-picker
          show-quick-jumper
          @update:page="changePage"
          @update:page-size="changePageSize"
        />
      </div>
    </NCard>

    <!-- 订单详细参数弹窗（完全吻合小沐11项参数规范） -->
    <NModal
      v-model:show="detailModalVisible"
      preset="card"
      :title="`订单详细参数 [反向反馈ID: ${currentDetail?.orderId || ''}]`"
      :style="{ width: appStore.isMobile ? '92vw' : '620px' }"
    >
      <div v-if="currentDetail" class="flex flex-col gap-12px text-13px">
        <!-- 课程类型 / 平台标题 -->
        <div class="rounded-8px bg-slate-50 p-12px dark:bg-dark-600 border border-slate-200 dark:border-dark-500">
          <span class="text-gray-500">课程类型：</span>
          <strong class="text-primary text-15px">{{ currentDetail.platform || '无' }}</strong>
        </div>

        <div class="flex flex-col gap-10px rounded-8px border border-gray-100 dark:border-dark-500 p-14px bg-white dark:bg-dark-700 leading-relaxed">
          <!-- 1. 账号信息 -->
          <div class="flex flex-col gap-4px border-b pb-8px">
            <div class="flex items-center justify-between">
              <span class="font-bold text-gray-700 dark:text-gray-200">1. 账号信息：</span>
              <NButton size="tiny" secondary type="primary" @click="copyText(`${currentDetail.school ? currentDetail.school + ' ' : ''}${currentDetail.account} ${currentDetail.password || ''}`, '账号信息')">
                复制完整信息
              </NButton>
            </div>
            <div class="font-mono text-14px font-bold text-gray-800 dark:text-gray-100 flex flex-wrap gap-8px mt-2px">
              <span v-if="currentDetail.school" class="bg-blue-50 dark:bg-dark-500 px-6px py-2px rounded text-primary">{{ currentDetail.school }}</span>
              <span class="bg-slate-100 dark:bg-dark-500 px-6px py-2px rounded">{{ currentDetail.account }}</span>
              <span v-if="currentDetail.password" class="bg-slate-100 dark:bg-dark-500 px-6px py-2px rounded text-gray-600 dark:text-gray-300">{{ currentDetail.password }}</span>
            </div>
          </div>

          <!-- 2. 课程名字 -->
          <div class="flex items-start justify-between border-b pb-8px">
            <span class="font-bold text-gray-700 dark:text-gray-200 min-w-80px">2. 课程名字：</span>
            <span class="text-right text-gray-800 dark:text-gray-100 font-medium">{{ currentDetail.courseName }}</span>
          </div>

          <!-- 3. KCID -->
          <div class="flex items-center justify-between border-b pb-8px">
            <span class="font-bold text-gray-700 dark:text-gray-200">3. KCID：</span>
            <span class="font-mono text-gray-800 dark:text-gray-200">{{ currentDetail.kcid || '无' }}</span>
          </div>

          <!-- 4. 站内反馈id -->
          <div class="flex items-center justify-between border-b pb-8px">
            <span class="font-bold text-gray-700 dark:text-gray-200">4. 站内反馈id：</span>
            <strong class="font-mono text-primary font-bold">#{{ currentDetail.orderId }}</strong>
          </div>

          <!-- 5. 上游返回YID -->
          <div class="flex items-center justify-between border-b pb-8px">
            <span class="font-bold text-gray-700 dark:text-gray-200">5. 上游返回YID：</span>
            <div class="flex items-center gap-6px">
              <strong v-if="currentDetail.yid && currentDetail.yid !== '0'" class="font-mono text-emerald-600 font-bold text-14px">
                {{ currentDetail.yid }}
              </strong>
              <span v-else class="text-gray-400 text-12px">暂无YID (可点击下方同步拉取)</span>
              <NButton v-if="currentDetail.yid && currentDetail.yid !== '0'" size="tiny" quaternary type="primary" @click="copyText(currentDetail.yid, 'YID')">
                复制
              </NButton>
            </div>
          </div>

          <!-- 6. 下单时间 -->
          <div class="flex items-center justify-between border-b pb-8px">
            <span class="font-bold text-gray-700 dark:text-gray-200">6. 下单时间：</span>
            <span class="font-mono text-gray-600 dark:text-gray-300">{{ currentDetail.createdAt }}</span>
          </div>

          <!-- 7. 上次同步时间 -->
          <div class="flex items-center justify-between border-b pb-8px">
            <span class="font-bold text-gray-700 dark:text-gray-200">7. 上次同步时间：</span>
            <span class="font-mono text-gray-600 dark:text-gray-300">{{ currentDetail.finalupdate || '暂未同步' }}</span>
          </div>

          <!-- 8. 订单状态 -->
          <div class="flex items-center justify-between border-b pb-8px">
            <span class="font-bold text-gray-700 dark:text-gray-200">8. 订单状态：</span>
            <NTag :type="statusType(currentDetail.status)" size="small" round>{{ currentDetail.status }}</NTag>
          </div>

          <!-- 9. 操作快捷项 -->
          <div class="flex items-center justify-between border-b pb-8px">
            <span class="font-bold text-gray-700 dark:text-gray-200">9. 操作：</span>
            <NSpace :size="8">
              <NButton size="tiny" type="primary" ghost :loading="Boolean(actionLoadingMap[`sync_${currentDetail.orderId}`])" @click="handleSync(currentDetail)">
                🔄 立即同步最新进度
              </NButton>
              <NButton size="tiny" type="warning" ghost :loading="Boolean(actionLoadingMap[`rebrush_${currentDetail.orderId}`])" @click="handleRebrush(currentDetail)">
                🚀 发起排队补刷
              </NButton>
              <NButton v-if="isSuperAdmin && String(currentDetail.dockStatus) === '2'" size="tiny" type="error" dashed :loading="Boolean(actionLoadingMap[`dock_${currentDetail.orderId}`])" @click="handleDock(currentDetail)">
                重新向货源交单
              </NButton>
            </NSpace>
          </div>

          <!-- 10. 进度 -->
          <div class="flex flex-col gap-4px border-b pb-8px">
            <div class="flex items-center justify-between">
              <span class="font-bold text-gray-700 dark:text-gray-200">10. 进度：</span>
              <strong class="font-mono text-primary">{{ currentDetail.progress || '0%' }}</strong>
            </div>
            <NProgress
              type="line"
              :percentage="parseFloat((currentDetail.progress || '0').replace(/[^0-9.]/g, '')) || (currentDetail.status === '已完成' ? 100 : 0)"
              :height="8"
              :status="currentDetail.status === '已完成' ? 'success' : (currentDetail.status === '异常' ? 'error' : 'info')"
            />
          </div>

          <!-- 11. 备注 (高清大字号完全自适应) -->
          <div class="flex flex-col gap-4px">
            <span class="font-bold text-gray-700 dark:text-gray-200">11. 备注：</span>
            <div class="rounded-8px bg-slate-50 dark:bg-dark-600 p-10px text-gray-900 dark:text-gray-100 text-14px font-medium whitespace-pre-wrap leading-relaxed border border-slate-200 dark:border-dark-500">
              {{ currentDetail.remarks || '无特别备注' }}
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-between items-center">
          <NButton secondary type="primary" size="small" @click="currentDetail && copyFormattedDetail(currentDetail)">
            📋 复制11项详细文本
          </NButton>
          <NButton size="small" @click="detailModalVisible = false">关闭窗口</NButton>
        </div>
      </template>
    </NModal>
  </NSpace>
</template>

<style scoped>
:deep(.n-data-table-th) {
  white-space: nowrap !important;
}
:deep(.n-data-table-td) {
  vertical-align: middle;
}
</style>
