<script setup lang="ts">
import { h, onMounted, reactive, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { NButton, NInput, NInputNumber, NSpace, NSwitch, NTag } from 'naive-ui';
import { fetchUserlistList, rechargeUserBalance, updateUserRate, updateUserStatus } from '@/service/api';

defineOptions({ name: 'Userlist' });

const loading = ref(false);
const list = ref<Api.ProfileArea.UserItem[]>([]);
const total = ref(0);

const query = reactive({
  page: 1,
  pageSize: 20,
  keyword: '',
  status: '' as '' | '1' | '0'
});

const rechargeModal = ref(false);
const rateModal = ref(false);
const currentUid = ref('');
const currentUserName = ref('');
const rechargeAmount = ref(100);
const targetRate = ref('0.30');

const columns: DataTableColumns<Api.ProfileArea.UserItem> = [
  { title: 'UID', key: 'uid', width: 70, fixed: 'left' },
  { title: '上级', key: 'uuid', width: 70 },
  { title: '账号', key: 'user', width: 140 },
  { title: '昵称', key: 'name', minWidth: 120 },
  {
    title: '成本费率',
    key: 'addprice',
    width: 100,
    render: row => h('span', { class: 'font-bold text-primary' }, `${row.addprice}×`)
  },
  {
    title: '当前余额',
    key: 'money',
    width: 110,
    render: row => h('span', { class: 'font-bold text-success' }, `¥ ${row.money}`)
  },
  { title: '累计充值', key: 'zcz', width: 100, render: row => `¥ ${row.zcz}` },
  {
    title: '状态',
    key: 'active',
    width: 100,
    render: row =>
      h(
        NSwitch,
        {
          value: row.active === 1,
          onUpdateValue: async (val: boolean) => {
            const res = await updateUserStatus(row.uid, val ? 1 : 0);
            if (res !== null) {
              row.active = val ? 1 : 0;
              window.$message?.success(val ? '已解封' : '已封禁');
            }
          }
        },
        { checked: () => '正常', unchecked: () => '封禁' }
      )
  },
  { title: '邀请码', key: 'yqm', width: 100 },
  { title: '注册时间', key: 'addtime', width: 160 },
  {
    title: '操作',
    key: 'actions',
    width: 150,
    fixed: 'right',
    render: row =>
      h(NSpace, { size: 'small' }, () => [
        h(
          NButton,
          {
            size: 'small',
            type: 'primary',
            ghost: true,
            onClick: () => {
              currentUid.value = row.uid;
              currentUserName.value = row.name || row.user;
              rechargeAmount.value = 50;
              rechargeModal.value = true;
            }
          },
          { default: () => '充值' }
        ),
        h(
          NButton,
          {
            size: 'small',
            type: 'info',
            ghost: true,
            onClick: () => {
              currentUid.value = row.uid;
              currentUserName.value = row.name || row.user;
              targetRate.value = row.addprice;
              rateModal.value = true;
            }
          },
          { default: () => '调费率' }
        )
      ])
  }
];

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchUserlistList({
    page: query.page,
    pageSize: query.pageSize,
    keyword: query.keyword.trim() || undefined,
    status: query.status !== '' ? query.status : undefined
  });
  loading.value = false;

  if (!error && data) {
    list.value = data.records;
    total.value = data.total;
  }
}

async function handleRecharge() {
  if (!rechargeAmount.value) {
    window.$message?.warning('请输入有效充值金额');
    return;
  }
  const res = await rechargeUserBalance(currentUid.value, rechargeAmount.value);
  if (res !== null) {
    window.$message?.success('充值成功');
    rechargeModal.value = false;
    loadData();
  }
}

async function handleUpdateRate() {
  if (!targetRate.value.trim()) {
    window.$message?.warning('请输入有效费率');
    return;
  }
  const res = await updateUserRate(currentUid.value, targetRate.value.trim());
  if (res !== null) {
    window.$message?.success('费率修改成功');
    rateModal.value = false;
    loadData();
  }
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="代理管理" :bordered="false" class="rounded-8px shadow-sm">
      <div class="mb-16px flex flex-wrap items-center justify-between gap-12px">
        <div class="flex flex-wrap items-center gap-10px">
          <NInput v-model:value="query.keyword" placeholder="UID / 账号 / 昵称 / 邀请码" clearable class="w-240px" @keyup.enter="loadData" />
          <NSelect
            v-model:value="query.status"
            :options="[
              { label: '全部状态', value: '' },
              { label: '正常', value: '1' },
              { label: '已封禁', value: '0' }
            ]"
            placeholder="账号状态"
            clearable
            class="w-130px"
          />
          <NButton type="primary" @click="loadData">查询</NButton>
        </div>
        <NButton :loading="loading" @click="loadData">刷新</NButton>
      </div>

      <NDataTable
        :loading="loading"
        :columns="columns"
        :data="list"
        :row-key="(row: Api.ProfileArea.UserItem) => row.uid"
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
          @update:page-size="loadData"
        />
      </div>
    </NCard>

    <!-- 充值弹窗 -->
    <NModal v-model:show="rechargeModal" preset="card" title="代理余额调整" class="max-w-450px">
      <div class="flex flex-col gap-12px">
        <div class="text-14px">目标代理：<strong>[UID: {{ currentUid }}] {{ currentUserName }}</strong></div>
        <NFormItem label="调整金额 (正数增加，负数扣除)">
          <NInputNumber v-model:value="rechargeAmount" :step="10" class="w-full">
            <template #prefix>¥</template>
          </NInputNumber>
        </NFormItem>
      </div>
      <template #footer>
        <div class="flex justify-end gap-12px">
          <NButton @click="rechargeModal = false">取消</NButton>
          <NButton type="primary" @click="handleRecharge">确认充值</NButton>
        </div>
      </template>
    </NModal>

    <!-- 调费率弹窗 -->
    <NModal v-model:show="rateModal" preset="card" title="修改代理费率" class="max-w-450px">
      <div class="flex flex-col gap-12px">
        <div class="text-14px">目标代理：<strong>[UID: {{ currentUid }}] {{ currentUserName }}</strong></div>
        <NFormItem label="新费率系数 (如 0.25 代表 2.5 折成本)">
          <NInput v-model:value="targetRate" placeholder="输入费率系数，如 0.30" />
        </NFormItem>
      </div>
      <template #footer>
        <div class="flex justify-end gap-12px">
          <NButton @click="rateModal = false">取消</NButton>
          <NButton type="primary" @click="handleUpdateRate">确认修改</NButton>
        </div>
      </template>
    </NModal>
  </div>
</template>
