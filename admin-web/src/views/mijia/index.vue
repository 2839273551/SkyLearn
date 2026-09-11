<script setup lang="ts">
import { h, onMounted, reactive, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { NButton, NPopconfirm, NSpace, NTag } from 'naive-ui';
import { deleteMijia, fetchClassOptions, fetchMijiaList, saveMijia } from '@/service/api';

defineOptions({ name: 'Mijia' });

const loading = ref(false);
const submitting = ref(false);
const list = ref<Api.Mijia.Item[]>([]);
const classOptions = ref<Array<{ label: string; value: string }>>([]);

const filterUid = ref('');
const modalVisible = ref(false);
const modalTitle = ref('添加密价');

const formModel = reactive<{
  mid: string;
  uid: string;
  cid: string;
  mode: number;
  price: string;
}>({
  mid: '',
  uid: '',
  cid: '',
  mode: 0,
  price: '0.10'
});

const modeMap: Record<number, { text: string; type: 'default' | 'info' | 'success' }> = {
  0: { text: '固定立减 (元)', type: 'info' },
  1: { text: '倍率立减 (倍)', type: 'default' },
  2: { text: '一口底价 (元)', type: 'success' }
};

const columns: DataTableColumns<Api.Mijia.Item> = [
  { title: 'ID', key: 'mid', width: 70 },
  {
    title: '用户',
    key: 'uid',
    width: 140,
    render: row => h('span', { class: 'font-500' }, `[UID: ${row.uid}] ${row.userName}`)
  },
  {
    title: '指定课程',
    key: 'className',
    minWidth: 160,
    render: row => h('span', {}, `[ID: ${row.cid}] ${row.className}`)
  },
  {
    title: '优惠类型',
    key: 'mode',
    width: 130,
    render: row => h(NTag, { type: modeMap[row.mode]?.type || 'default', size: 'small', round: true }, { default: () => modeMap[row.mode]?.text })
  },
  {
    title: '数值 (金额/倍数)',
    key: 'price',
    width: 140,
    render: row => h('span', { class: 'font-bold text-primary' }, row.mode === 1 ? `× ${row.price}` : `¥ ${row.price}`)
  },
  { title: '添加时间', key: 'addtime', width: 170 },
  {
    title: '操作',
    key: 'actions',
    width: 140,
    fixed: 'right',
    render: row =>
      h(NSpace, { size: 'small' }, () => [
        h(NButton, { size: 'small', type: 'primary', ghost: true, onClick: () => openEditModal(row) }, { default: () => '编辑' }),
        h(
          NPopconfirm,
          { onPositiveClick: () => handleDelete(row.mid) },
          {
            default: () => `确定删除此密价记录吗？`,
            trigger: () => h(NButton, { size: 'small', type: 'error', ghost: true }, { default: () => '删除' })
          }
        )
      ])
  }
];

async function loadOptions() {
  const { data } = await fetchClassOptions();
  if (data?.fenleiList) {
    // 拉取全部商品用于选择
    const res = await fetchClassOptions();
    if (res.data) {
      // 从 class list 里选
    }
  }
}

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchMijiaList({ uid: filterUid.value.trim() || undefined });
  loading.value = false;
  if (!error && data) {
    list.value = data.list;
  }
}

async function initClasses() {
  const { data } = await fetchClassOptions();
  if (data) {
    // fetch classes
  }
}

function openAddModal() {
  modalTitle.value = '添加密价';
  Object.assign(formModel, {
    mid: '',
    uid: filterUid.value.trim() || '',
    cid: classOptions.value.length ? classOptions.value[0].value : '',
    mode: 0,
    price: '0.10'
  });
  modalVisible.value = true;
}

function openEditModal(row: Api.Mijia.Item) {
  modalTitle.value = '编辑密价';
  Object.assign(formModel, {
    mid: row.mid,
    uid: row.uid,
    cid: row.cid,
    mode: row.mode,
    price: row.price
  });
  modalVisible.value = true;
}

async function handleSubmit() {
  if (!formModel.uid?.trim()) {
    window.$message?.warning('请输入目标代理用户的 UID');
    return;
  }
  if (!formModel.cid) {
    window.$message?.warning('请输入或选择网课课程 ID');
    return;
  }

  submitting.value = true;
  const res = await saveMijia({
    mid: formModel.mid ? formModel.mid : undefined,
    uid: formModel.uid.trim(),
    cid: formModel.cid,
    mode: formModel.mode,
    price: formModel.price
  });
  submitting.value = false;

  if (res !== null) {
    window.$message?.success(formModel.mid ? '密价修改成功' : '密价添加成功');
    modalVisible.value = false;
    loadData();
  }
}

async function handleDelete(mid: string) {
  const res = await deleteMijia(mid);
  if (res !== null) {
    window.$message?.success('删除成功');
    loadData();
  }
}

onMounted(() => {
  loadData();
  initClasses();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="密价设置" :bordered="false" class="rounded-8px shadow-sm">
      <div class="mb-16px flex flex-wrap items-center justify-between gap-12px">
        <div class="flex items-center gap-10px">
          <NInput v-model:value="filterUid" placeholder="输入用户 UID 筛选" clearable class="w-200px" @keyup.enter="loadData" />
          <NButton type="primary" @click="loadData">查询</NButton>
        </div>
        <NSpace>
          <NButton type="primary" @click="openAddModal">添加密价</NButton>
          <NButton :loading="loading" @click="loadData">刷新</NButton>
        </NSpace>
      </div>

      <NDataTable
        :loading="loading"
        :columns="columns"
        :data="list"
        :row-key="(row: Api.Mijia.Item) => row.mid"
        :pagination="{ pageSize: 20 }"
        striped
      />
    </NCard>

    <NModal
      v-model:show="modalVisible"
      preset="card"
      :title="modalTitle"
      class="max-w-500px"
      :mask-closable="false"
    >
      <NForm :model="formModel" label-placement="left" label-width="110">
        <NFormItem label="用户 UID" required>
          <NInput v-model:value="formModel.uid" placeholder="输入享受密价的代理 UID (纯数字)" />
        </NFormItem>
        <NFormItem label="课程 CID" required>
          <NInput v-model:value="formModel.cid" placeholder="输入网课课程 ID (CID)" />
        </NFormItem>
        <NFormItem label="定价类型">
          <NRadioGroup v-model:value="formModel.mode">
            <NSpace vertical>
              <NRadio :value="0">在成本价基础上立减固定金额 (元)</NRadio>
              <NRadio :value="1">在倍数基础上扣除 (倍率折扣)</NRadio>
              <NRadio :value="2">一口底价 (直接指定该用户售价)</NRadio>
            </NSpace>
          </NRadioGroup>
        </NFormItem>
        <NFormItem :label="formModel.mode === 1 ? '扣减倍数' : '金额 (元)'" required>
          <NInput v-model:value="formModel.price" placeholder="如 0.10 或 1.20" />
        </NFormItem>
      </NForm>

      <template #footer>
        <div class="flex justify-end gap-12px">
          <NButton @click="modalVisible = false">取消</NButton>
          <NButton type="primary" :loading="submitting" @click="handleSubmit">保存</NButton>
        </div>
      </template>
    </NModal>
  </div>
</template>
