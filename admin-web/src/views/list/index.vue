<script setup lang="ts">
import { computed, h, onMounted, reactive, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { NAlert, NButton, NCard, NDataTable, NInput, NPagination, NPopconfirm, NSelect, NSpace, NTag } from 'naive-ui';
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
  if (status === '进行中' || status === '补刷中') return 'info';
  if (status === '异常') return 'error';
  if (status === '待处理') return 'warning';
  return 'default';
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

const columns: DataTableColumns<Api.Orders.Record> = [
  { title: '订单号', key: 'orderId', width: 100, fixed: 'left' },
  { title: '平台', key: 'platform', width: 130, ellipsis: { tooltip: true } },
  { title: '账号', key: 'account', width: 140, ellipsis: { tooltip: true } },
  { title: '课程', key: 'courseName', minWidth: 180, ellipsis: { tooltip: true } },
  { title: '学校', key: 'school', width: 140, ellipsis: { tooltip: true } },
  { title: '进度', key: 'progress', width: 120, ellipsis: { tooltip: true } },
  {
    title: '课程状态',
    key: 'status',
    width: 100,
    render: row => h(NTag, { type: statusType(row.status), size: 'small', round: true }, { default: () => row.status || '待处理' })
  },
  {
    title: '对接状态',
    key: 'dockStatus',
    width: 150,
    render: row => {
      const ds = String(row.dockStatus ?? '');
      if (ds === '1') {
        return h(NTag, { type: 'success', size: 'small', round: true }, { default: () => '已提交成功' });
      }
      if (ds === '0') {
        return h(NTag, { type: 'info', size: 'small', round: true }, { default: () => '等待提交' });
      }
      if (ds === '2') {
        // 失败状态：超管可点击重新提交
        if (isSuperAdmin.value) {
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
                  { default: () => '❌ 提交失败 (点击重试)' }
                ),
              default: () => `确定重新向货源提交订单 #${row.orderId} 吗？`
            }
          );
        }
        return h(NTag, { type: 'error', size: 'small', round: true }, { default: () => '提交失败' });
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
  },
  { title: '备注', key: 'remarks', minWidth: 150, ellipsis: { tooltip: true } },
  { title: '下单时间', key: 'createdAt', width: 165 },
  {
    title: '操作',
    key: 'actions',
    width: 170,
    fixed: 'right',
    render: row =>
      h(NSpace, { size: 'small' }, () => [
        h(
          NButton,
          {
            size: 'small',
            type: 'primary',
            ghost: true,
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
                  size: 'small',
                  type: 'warning',
                  ghost: true,
                  loading: Boolean(actionLoadingMap[`rebrush_${row.orderId}`])
                },
                { default: () => '🚀 补刷' }
              ),
            default: () => `确定为订单 #${row.orderId} 申请补刷吗？`
          }
        )
      ])
  }
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

    <NCard title="订单汇总" :bordered="false" class="rounded-8px shadow-sm">
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
        <NSelect v-model:value="query.status" class="w-150px" :options="statusOptions" />
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
        :scroll-x="1550"
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
