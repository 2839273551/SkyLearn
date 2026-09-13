<script setup lang="ts">
import { computed, h, onMounted, reactive, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { NAlert, NButton, NCard, NDataTable, NInput, NPagination, NPopconfirm, NProgress, NSelect, NSpace, NTag } from 'naive-ui';
import { dockOrder, fetchOrders, rebrushOrder, syncOrderProgress } from '@/service/api';
import { useAuthStore } from '@/store/modules/auth';

defineOptions({ name: 'List' });

const authStore = useAuthStore();
const isSuperAdmin = computed(() => authStore.userInfo.roles.includes('R_SUPER'));

const loading = ref(false);
const records = ref<Api.Orders.Record[]>([]);
const total = ref(0);
const query = reactive<Api.Orders.Query>({ page: 1, pageSize: 20, keyword: '', status: '' });

const actionLoadingMap = reactive<Record<string, boolean>>({});

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

// 同步最新进度
async function handleSync(row: Api.Orders.Record) {
  const key = `sync_${row.orderId}`;
  actionLoadingMap[key] = true;
  const { data, error } = await syncOrderProgress(row.orderId);
  actionLoadingMap[key] = false;
  if (!error && data) {
    if (data.process) row.progress = data.process;
    if (data.status) row.status = data.status;
    if (data.remarks) row.remarks = data.remarks;
    window.$message?.success(`订单 #${row.orderId} 进度同步成功: ${data.process || data.status}`);
  }
}

// 申请补刷
async function handleRebrush(row: Api.Orders.Record) {
  const key = `rebrush_${row.orderId}`;
  actionLoadingMap[key] = true;
  const { data, error } = await rebrushOrder(row.orderId);
  actionLoadingMap[key] = false;
  if (!error && data) {
    row.status = data.status || '补刷中';
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
    window.$message?.success(`订单 #${row.orderId} 重新向上游货源提交成功！`);
  }
}

const columns = computed<DataTableColumns<Api.Orders.Record>>(() => {
  const cols: DataTableColumns<Api.Orders.Record> = [
    {
      title: '操作',
      key: 'actions',
      width: 140,
      fixed: 'left',
      render: row =>
        h(NSpace, { size: 6, align: 'center' }, () => [
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
    { title: '平台', key: 'platform', width: 140, ellipsis: { tooltip: true } },
    {
      title: '账号信息',
      key: 'account',
      minWidth: 210,
      render: row =>
        h('div', { class: 'flex flex-col gap-3px py-3px text-12px' }, [
          row.school
            ? h('div', { class: 'flex items-center gap-4px' }, [
                h(
                  NButton,
                  { size: 'tiny', tertiary: true, type: 'primary', class: 'px-4px h-18px text-10px', onClick: () => copyText(row.school, '学校') },
                  { default: () => '学校' }
                ),
                h('span', { class: 'text-gray-600 dark:text-gray-300 truncate max-w-150px', title: row.school }, row.school)
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
    { title: '任务名称', key: 'courseName', minWidth: 180, ellipsis: { tooltip: true } },
    {
      title: '状态',
      key: 'status',
      width: 90,
      render: row => h(NTag, { type: statusType(row.status), size: 'small', round: true }, { default: () => row.status || '待处理' })
    },
    {
      title: '%',
      key: 'progress',
      width: 120,
      render: row => {
        const pStr = row.progress || '0%';
        const numMatch = pStr.match(/(\d+(?:\.\d+)?)/);
        const percent = numMatch ? Math.min(100, Math.max(0, parseFloat(numMatch[1]))) : (row.status === '已完成' ? 100 : 0);
        return h('div', { class: 'flex flex-col gap-3px w-100px' }, [
          h('div', { class: 'flex justify-between items-center text-11px font-mono font-bold text-gray-600 dark:text-gray-300' }, [
            h('span', {}, `${percent}%`),
            percent === 100 ? h('span', { class: 'text-emerald-500' }, '✔') : null
          ]),
          h(NProgress, {
            type: 'line',
            percentage: percent,
            showIndicator: false,
            height: 5,
            status: percent === 100 ? 'success' : (row.status === '异常' ? 'error' : 'info')
          })
        ]);
      }
    },
    { title: '订单详细信息', key: 'remarks', minWidth: 160, ellipsis: { tooltip: true } },
    { title: '时间', key: 'createdAt', width: 155 }
  ];

  // 严格根据用户指令：对接状态放到最后，且仅超级管理员可见，代理完全不展示该列！
  if (isSuperAdmin.value) {
    cols.push({
      title: '对接状态',
      key: 'dockStatus',
      width: 140,
      fixed: 'right',
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
                  { default: () => '❌ 处理失败 (点击重推)' }
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

      <NDataTable
        :loading="loading"
        :columns="columns"
        :data="records"
        :row-key="(row: Api.Orders.Record) => row.orderId"
        :pagination="false"
        striped
        :scroll-x="isSuperAdmin ? 1520 : 1380"
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
  </NSpace>
</template>

<style scoped></style>
