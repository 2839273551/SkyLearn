<script setup lang="ts">
import { h, onMounted, reactive, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { NButton, NPopconfirm, NSpace, NSwitch } from 'naive-ui';
import { deleteDengji, fetchDengjiList, saveDengji } from '@/service/api';

defineOptions({ name: 'Dengji' });

const loading = ref(false);
const submitting = ref(false);
const list = ref<Api.Dengji.Item[]>([]);
const modalVisible = ref(false);
const modalTitle = ref('添加等级');

const formModel = reactive<Partial<Api.Dengji.Item>>({
  id: '',
  name: '',
  sort: 10,
  rate: '0.80',
  money: '0.00',
  addkf: 1,
  gjkf: 1,
  status: 1
});

const columns: DataTableColumns<Api.Dengji.Item> = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '排序', key: 'sort', width: 80 },
  { title: '等级名称', key: 'name', minWidth: 140 },
  {
    title: '等级费率',
    key: 'rate',
    width: 110,
    render: row => h('span', { class: 'font-bold text-primary' }, `${row.rate}`)
  },
  {
    title: '开通价格',
    key: 'money',
    width: 110,
    render: row => h('span', {}, `¥ ${row.money}`)
  },
  {
    title: '添加下级扣费',
    key: 'addkf',
    width: 130,
    render: row =>
      h(
        NSwitch,
        {
          value: row.addkf === 1,
          onUpdateValue: async (val: boolean) => {
            const res = await saveDengji({ ...row, addkf: val ? 1 : 0 });
            if (res !== null) {
              row.addkf = val ? 1 : 0;
              window.$message?.success('设置已保存');
            }
          }
        },
        { checked: () => '开启', unchecked: () => '关闭' }
      )
  },
  {
    title: '修改费率扣费',
    key: 'gjkf',
    width: 130,
    render: row =>
      h(
        NSwitch,
        {
          value: row.gjkf === 1,
          onUpdateValue: async (val: boolean) => {
            const res = await saveDengji({ ...row, gjkf: val ? 1 : 0 });
            if (res !== null) {
              row.gjkf = val ? 1 : 0;
              window.$message?.success('设置已保存');
            }
          }
        },
        { checked: () => '开启', unchecked: () => '关闭' }
      )
  },
  {
    title: '状态',
    key: 'status',
    width: 100,
    render: row =>
      h(
        NSwitch,
        {
          value: row.status === 1,
          onUpdateValue: async (val: boolean) => {
            const res = await saveDengji({ ...row, status: val ? 1 : 0 });
            if (res !== null) {
              row.status = val ? 1 : 0;
              window.$message?.success(val ? '已启用' : '已停用');
            }
          }
        },
        { checked: () => '启用', unchecked: () => '停用' }
      )
  },
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
          { onPositiveClick: () => handleDelete(row.id) },
          {
            default: () => `确定删除等级【${row.name}】吗？`,
            trigger: () => h(NButton, { size: 'small', type: 'error', ghost: true }, { default: () => '删除' })
          }
        )
      ])
  }
];

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchDengjiList();
  loading.value = false;
  if (!error && data) {
    list.value = data.list;
  }
}

function openAddModal() {
  modalTitle.value = '添加等级';
  Object.assign(formModel, {
    id: '',
    name: '',
    sort: list.value.length ? Math.max(...list.value.map(i => i.sort)) + 1 : 1,
    rate: '0.80',
    money: '0.00',
    addkf: 1,
    gjkf: 1,
    status: 1
  });
  modalVisible.value = true;
}

function openEditModal(row: Api.Dengji.Item) {
  modalTitle.value = '编辑等级';
  Object.assign(formModel, row);
  modalVisible.value = true;
}

async function handleSubmit() {
  if (!formModel.name?.trim()) {
    window.$message?.warning('请输入等级名称');
    return;
  }

  submitting.value = true;
  const res = await saveDengji(formModel);
  submitting.value = false;

  if (res !== null) {
    window.$message?.success(formModel.id ? '修改成功' : '添加成功');
    modalVisible.value = false;
    loadData();
  }
}

async function handleDelete(id: string) {
  const res = await deleteDengji(id);
  if (res !== null) {
    window.$message?.success('删除成功');
    loadData();
  }
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="等级设置" :bordered="false" class="rounded-8px shadow-sm">
      <template #header-extra>
        <NSpace>
          <NButton type="primary" @click="openAddModal">添加等级</NButton>
          <NButton :loading="loading" @click="loadData">刷新</NButton>
        </NSpace>
      </template>

      <NDataTable
        :loading="loading"
        :columns="columns"
        :data="list"
        :row-key="(row: Api.Dengji.Item) => row.id"
        :pagination="false"
        striped
      />
    </NCard>

    <NModal
      v-model:show="modalVisible"
      preset="card"
      :title="modalTitle"
      class="max-w-560px"
      :mask-closable="false"
    >
      <NForm :model="formModel" label-placement="left" label-width="120">
        <NFormItem label="等级名称" required>
          <NInput v-model:value="formModel.name" placeholder="如：初级代理、核心合伙人" />
        </NFormItem>
        <NFormItem label="排序权重">
          <NInputNumber v-model:value="formModel.sort" :min="0" :max="999" class="w-full" />
        </NFormItem>
        <NFormItem label="等级费率" required>
          <NInput v-model:value="formModel.rate" placeholder="如：0.80 代表 8 折成本" />
        </NFormItem>
        <NFormItem label="开通价格(元)">
          <NInput v-model:value="formModel.money" placeholder="开通该等级所需费用" />
        </NFormItem>
        <NFormItem label="添加下级扣费">
          <NSwitch v-model:value="formModel.addkf" :checked-value="1" :unchecked-value="0" />
        </NFormItem>
        <NFormItem label="修改费率扣费">
          <NSwitch v-model:value="formModel.gjkf" :checked-value="1" :unchecked-value="0" />
        </NFormItem>
        <NFormItem label="状态">
          <NSwitch v-model:value="formModel.status" :checked-value="1" :unchecked-value="0">
            <template #checked>启用</template>
            <template #unchecked>停用</template>
          </NSwitch>
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
