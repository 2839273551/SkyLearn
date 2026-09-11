<script setup lang="ts">
import { h, onMounted, reactive, ref } from 'vue';
import type { DataTableRowKey, DataTableColumns } from 'naive-ui';
import { NButton, NInput, NPopconfirm, NSpace, NTag } from 'naive-ui';
import { deleteGuanx, fetchGuanxList, generateGuanx } from '@/service/api';

defineOptions({ name: 'Guanx' });

const loading = ref(false);
const submitting = ref(false);
const list = ref<Api.Guanx.Record[]>([]);
const total = ref(0);
const checkedRowKeys = ref<DataTableRowKey[]>([]);

const query = reactive({
  page: 1,
  pageSize: 20,
  keyword: '',
  status: '' as '' | '1' | '0',
  batchId: ''
});

const modalVisible = ref(false);
const exportModalVisible = ref(false);
const exportedText = ref('');

const formModel = reactive({
  num: 10,
  money: 10,
  batchId: undefined as number | undefined
});

const columns: DataTableColumns<Api.Guanx.Record> = [
  { type: 'selection', fixed: 'left' },
  { title: 'ID', key: 'id', width: 70, fixed: 'left' },
  {
    title: '卡密内容',
    key: 'content',
    minWidth: 180,
    render: row => h('span', { class: 'font-mono text-13px font-bold' }, row.content)
  },
  {
    title: '面值',
    key: 'money',
    width: 100,
    render: row => h('span', { class: 'font-bold text-success' }, `¥ ${row.money}`)
  },
  { title: '批次 ID', key: 'batchId', width: 110 },
  {
    title: '状态',
    key: 'status',
    width: 100,
    render: row =>
      h(
        NTag,
        {
          type: row.status === 1 ? 'default' : 'success',
          size: 'small'
        },
        { default: () => (row.status === 1 ? '已使用' : '未使用') }
      )
  },
  { title: '使用者 UID', key: 'uid', width: 110, render: row => row.uid || '-' },
  { title: '生成时间', key: 'addtime', width: 170 },
  { title: '使用时间', key: 'usedtime', width: 170, render: row => row.usedtime || '-' },
  {
    title: '操作',
    key: 'actions',
    width: 90,
    fixed: 'right',
    render: row =>
      h(
        NPopconfirm,
        { onPositiveClick: () => handleDelete([row.id]) },
        {
          default: () => `确定删除此卡密吗？`,
          trigger: () => h(NButton, { size: 'small', type: 'error', ghost: true }, { default: () => '删除' })
        }
      )
  }
];

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchGuanxList({
    page: query.page,
    pageSize: query.pageSize,
    keyword: query.keyword.trim() || undefined,
    status: query.status !== '' ? query.status : undefined,
    batchId: query.batchId.trim() || undefined
  });
  loading.value = false;

  if (!error && data) {
    list.value = data.records;
    total.value = data.total;
    checkedRowKeys.value = [];
  }
}

function handleSearch() {
  query.page = 1;
  loadData();
}

function openGenerateModal() {
  formModel.num = 10;
  formModel.money = 10;
  formModel.batchId = Number(new Date().toISOString().slice(5, 10).replace('-', '')) || 101;
  modalVisible.value = true;
}

async function handleGenerate() {
  submitting.value = true;
  const { data, error } = await generateGuanx({
    num: formModel.num,
    money: formModel.money,
    batchId: formModel.batchId
  });
  submitting.value = false;

  if (!error && data) {
    window.$message?.success(`成功生成 ${data.count} 张卡密！`);
    modalVisible.value = false;
    exportedText.value = data.cards.join('\n');
    exportModalVisible.value = true;
    loadData();
  }
}

function handleExportSelected() {
  const map = new Map(list.value.map(i => [i.id, i.content]));
  const selectedCards = (checkedRowKeys.value as string[]).map(id => map.get(id)).filter(Boolean);

  if (!selectedCards.length) {
    window.$message?.warning('请先勾选需要导出的卡密');
    return;
  }

  exportedText.value = selectedCards.join('\n');
  exportModalVisible.value = true;
}

async function handleDelete(ids: (string | number)[]) {
  if (!ids.length) {
    window.$message?.warning('请选择要删除的卡密');
    return;
  }
  const res = await deleteGuanx(ids);
  if (res !== null) {
    window.$message?.success('删除成功');
    loadData();
  }
}

function copyExported() {
  navigator.clipboard.writeText(exportedText.value);
  window.$message?.success('已复制到剪贴板');
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="充值卡密" :bordered="false" class="rounded-8px shadow-sm">
      <div class="mb-16px flex flex-wrap items-center justify-between gap-12px">
        <div class="flex flex-wrap items-center gap-10px">
          <NInput v-model:value="query.keyword" placeholder="卡密文本 / 使用者UID" clearable class="w-220px" @keyup.enter="handleSearch" />
          <NInput v-model:value="query.batchId" placeholder="批次 ID" clearable class="w-130px" @keyup.enter="handleSearch" />
          <NSelect
            v-model:value="query.status"
            :options="[
              { label: '全部状态', value: '' },
              { label: '未使用', value: '0' },
              { label: '已使用', value: '1' }
            ]"
            placeholder="使用状态"
            clearable
            class="w-130px"
          />
          <NButton type="primary" @click="handleSearch">查询</NButton>
        </div>

        <NSpace>
          <NButton type="primary" @click="openGenerateModal">批量生成卡密</NButton>
          <NButton type="info" ghost :disabled="!checkedRowKeys.length" @click="handleExportSelected">导出已勾选</NButton>
          <NPopconfirm :disabled="!checkedRowKeys.length" @positive-click="handleDelete(checkedRowKeys as string[])">
            <template #default>确定批量删除勾选的 {{ checkedRowKeys.length }} 张卡密吗？</template>
            <template #trigger>
              <NButton type="error" ghost :disabled="!checkedRowKeys.length">批量删除</NButton>
            </template>
          </NPopconfirm>
          <NButton :loading="loading" @click="loadData">刷新</NButton>
        </NSpace>
      </div>

      <NDataTable
        v-model:checked-row-keys="checkedRowKeys"
        :loading="loading"
        :columns="columns"
        :data="list"
        :row-key="(row: Api.Guanx.Record) => row.id"
        :pagination="false"
        striped
        :scroll-x="1200"
      />

      <div class="mt-16px flex justify-end">
        <NPagination
          v-model:page="query.page"
          v-model:page-size="query.pageSize"
          :item-count="total"
          :page-sizes="[20, 50, 100, 200]"
          show-size-picker
          show-quick-jumper
          @update:page="loadData"
          @update:page-size="handleSearch"
        />
      </div>
    </NCard>

    <!-- 批量生成弹窗 -->
    <NModal
      v-model:show="modalVisible"
      preset="card"
      title="批量生成卡密"
      class="max-w-460px"
      :mask-closable="false"
    >
      <NForm :model="formModel" label-placement="left" label-width="100">
        <NFormItem label="生成数量" required>
          <NInputNumber v-model:value="formModel.num" :min="1" :max="500" class="w-full" />
        </NFormItem>
        <NFormItem label="单张面值(元)" required>
          <NInputNumber v-model:value="formModel.money" :min="1" :max="10000" class="w-full" />
        </NFormItem>
        <NFormItem label="批次标识 ID">
          <NInputNumber v-model:value="formModel.batchId" :min="1" placeholder="留空自动生成" class="w-full" />
        </NFormItem>
      </NForm>
      <template #footer>
        <div class="flex justify-end gap-12px">
          <NButton @click="modalVisible = false">取消</NButton>
          <NButton type="primary" :loading="submitting" @click="handleGenerate">立即生成</NButton>
        </div>
      </template>
    </NModal>

    <!-- 导出卡密弹窗 -->
    <NModal
      v-model:show="exportModalVisible"
      preset="card"
      title="卡密导出清单"
      class="max-w-560px"
    >
      <div class="flex flex-col gap-10px">
        <div class="text-13px text-gray-500">已提取卡密文本，可直接复制或保存发卡：</div>
        <NInput v-model:value="exportedText" type="textarea" :autosize="{ minRows: 6, maxRows: 14 }" readonly />
      </div>
      <template #footer>
        <div class="flex justify-end gap-12px">
          <NButton @click="exportModalVisible = false">关闭</NButton>
          <NButton type="primary" @click="copyExported">一键复制全部</NButton>
        </div>
      </template>
    </NModal>
  </div>
</template>
