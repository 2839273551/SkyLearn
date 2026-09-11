<script setup lang="ts">
import { h, onMounted, reactive, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { NButton, NPopconfirm, NSpace, NSwitch, NTag } from 'naive-ui';
import { deleteFenlei, fetchFenleiList, saveFenlei } from '@/service/api';

defineOptions({ name: 'Fenlei' });

const loading = ref(false);
const submitting = ref(false);
const list = ref<Api.Fenlei.Item[]>([]);
const modalVisible = ref(false);
const modalTitle = ref('添加分类');

const formModel = reactive<{
  id: string;
  name: string;
  sort: number;
  status: number;
}>({
  id: '',
  name: '',
  sort: 10,
  status: 1
});

const columns: DataTableColumns<Api.Fenlei.Item> = [
  { title: 'ID', key: 'id', width: 80 },
  { title: '排序', key: 'sort', width: 90 },
  { title: '分类名称', key: 'name', minWidth: 160 },
  {
    title: '关联课程数',
    key: 'courseCount',
    width: 120,
    render: row => h(NTag, { type: row.courseCount > 0 ? 'info' : 'default', size: 'small', round: true }, { default: () => `${row.courseCount} 门` })
  },
  {
    title: '状态',
    key: 'status',
    width: 120,
    render: row =>
      h(
        NSwitch,
        {
          value: row.status === 1,
          onUpdateValue: async (val: boolean) => {
            const newStatus = val ? 1 : 0;
            const res = await saveFenlei({
              id: row.id,
              name: row.name,
              sort: row.sort,
              status: newStatus
            });
            if (res !== null) {
              row.status = newStatus;
              window.$message?.success(val ? '已启用该分类' : '已停用该分类');
            }
          }
        },
        { checked: () => '已启用', unchecked: () => '已停用' }
      )
  },
  { title: '添加时间', key: 'time', width: 180 },
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
            onClick: () => openEditModal(row)
          },
          { default: () => '编辑' }
        ),
        h(
          NPopconfirm,
          {
            onPositiveClick: () => handleDelete(row.id)
          },
          {
            default: () => `确定删除分类【${row.name}】吗？`,
            trigger: () =>
              h(
                NButton,
                {
                  size: 'small',
                  type: 'error',
                  ghost: true
                },
                { default: () => '删除' }
              )
          }
        )
      ])
  }
];

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchFenleiList();
  if (!error && data) {
    list.value = data.list;
  }
  loading.value = false;
}

function openAddModal() {
  modalTitle.value = '添加分类';
  formModel.id = '';
  formModel.name = '';
  formModel.sort = list.value.length ? Math.max(...list.value.map(i => i.sort)) + 1 : 10;
  formModel.status = 1;
  modalVisible.value = true;
}

function openEditModal(row: Api.Fenlei.Item) {
  modalTitle.value = '编辑分类';
  formModel.id = row.id;
  formModel.name = row.name;
  formModel.sort = row.sort;
  formModel.status = row.status;
  modalVisible.value = true;
}

async function handleSubmit() {
  if (!formModel.name.trim()) {
    window.$message?.warning('请输入分类名称');
    return;
  }

  submitting.value = true;
  const res = await saveFenlei({
    id: formModel.id ? formModel.id : undefined,
    name: formModel.name.trim(),
    sort: Number(formModel.sort) || 0,
    status: formModel.status
  });
  submitting.value = false;

  if (res !== null) {
    window.$message?.success(formModel.id ? '修改成功' : '添加成功');
    modalVisible.value = false;
    loadData();
  }
}

async function handleDelete(id: string) {
  const res = await deleteFenlei(id);
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
    <NCard title="分类设置" :bordered="false" class="rounded-8px shadow-sm">
      <template #header-extra>
        <NSpace>
          <NButton type="primary" @click="openAddModal">
            <template #icon>
              <icon-ic-round-plus class="text-18px" />
            </template>
            添加分类
          </NButton>
          <NButton :loading="loading" @click="loadData">
            <template #icon>
              <icon-ic-round-refresh class="text-18px" />
            </template>
            刷新
          </NButton>
        </NSpace>
      </template>

      <NDataTable
        :loading="loading"
        :columns="columns"
        :data="list"
        :row-key="(row: Api.Fenlei.Item) => row.id"
        :pagination="false"
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
      <NForm :model="formModel" label-placement="left" label-width="80">
        <NFormItem label="分类名称" required>
          <NInput v-model:value="formModel.name" placeholder="请输入分类名称，如：高校网课、专业课" />
        </NFormItem>
        <NFormItem label="排序权重">
          <NInputNumber v-model:value="formModel.sort" :min="0" :max="9999" class="w-full" />
        </NFormItem>
        <NFormItem label="启用状态">
          <NSwitch v-model:value="formModel.status" :checked-value="1" :unchecked-value="0">
            <template #checked>已启用</template>
            <template #unchecked>已停用</template>
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
