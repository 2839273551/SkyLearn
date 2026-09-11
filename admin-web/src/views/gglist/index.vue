<script setup lang="ts">
import { h, onMounted, reactive, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { NButton, NPopconfirm, NSpace, NSwitch, NTag, NTooltip } from 'naive-ui';
import { deleteGglist, fetchGglistList, saveGglist } from '@/service/api';

defineOptions({ name: 'Gglist' });

const loading = ref(false);
const submitting = ref(false);
const list = ref<Api.Gglist.Item[]>([]);

const modalVisible = ref(false);
const modalTitle = ref('发布公告');

const formModel = reactive<{
  id: string;
  title: string;
  content: string;
  status: number;
  zhiding: number;
}>({
  id: '',
  title: '',
  content: '',
  status: 1,
  zhiding: 0
});

const columns: DataTableColumns<Api.Gglist.Item> = [
  { title: 'ID', key: 'id', width: 70 },
  { title: '公告标题', key: 'title', width: 180 },
  {
    title: '内容详情',
    key: 'content',
    minWidth: 260,
    render: row =>
      h(
        NTooltip,
        {},
        {
          trigger: () => h('div', { class: 'truncate cursor-pointer max-w-450px text-gray-700 dark:text-gray-300' }, row.content),
          default: () => h('div', { class: 'max-w-400px whitespace-pre-wrap' }, row.content)
        }
      )
  },
  {
    title: '置顶状态',
    key: 'zhiding',
    width: 110,
    render: row =>
      h(
        NSwitch,
        {
          value: row.zhiding === 1,
          onUpdateValue: async (val: boolean) => {
            const res = await saveGglist({ ...row, zhiding: val ? 1 : 0 });
            if (res !== null) {
              row.zhiding = val ? 1 : 0;
              window.$message?.success(val ? '已置顶' : '已取消置顶');
            }
          }
        },
        { checked: () => '置顶', unchecked: () => '普通' }
      )
  },
  {
    title: '可见状态',
    key: 'status',
    width: 110,
    render: row =>
      h(
        NSwitch,
        {
          value: row.status === 1,
          onUpdateValue: async (val: boolean) => {
            const res = await saveGglist({ ...row, status: val ? 1 : 0 });
            if (res !== null) {
              row.status = val ? 1 : 0;
              window.$message?.success(val ? '已设为可见' : '已设为隐藏');
            }
          }
        },
        { checked: () => '可见', unchecked: () => '隐藏' }
      )
  },
  { title: '发布时间', key: 'time', width: 170 },
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
            default: () => `确定删除此公告吗？`,
            trigger: () => h(NButton, { size: 'small', type: 'error', ghost: true }, { default: () => '删除' })
          }
        )
      ])
  }
];

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchGglistList();
  loading.value = false;
  if (!error && data) {
    list.value = data.list;
  }
}

function openAddModal() {
  modalTitle.value = '发布公告';
  Object.assign(formModel, {
    id: '',
    title: '',
    content: '',
    status: 1,
    zhiding: 0
  });
  modalVisible.value = true;
}

function openEditModal(row: Api.Gglist.Item) {
  modalTitle.value = '编辑公告';
  Object.assign(formModel, {
    id: row.id,
    title: row.title,
    content: row.content,
    status: row.status,
    zhiding: row.zhiding
  });
  modalVisible.value = true;
}

async function handleSubmit() {
  if (!formModel.title.trim()) {
    window.$message?.warning('请输入公告标题');
    return;
  }
  if (!formModel.content.trim()) {
    window.$message?.warning('请输入公告内容');
    return;
  }

  submitting.value = true;
  const res = await saveGglist(formModel);
  submitting.value = false;

  if (res !== null) {
    window.$message?.success(formModel.id ? '修改成功' : '发布成功');
    modalVisible.value = false;
    loadData();
  }
}

async function handleDelete(id: string) {
  const res = await deleteGglist(id);
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
    <NCard title="公告列表" :bordered="false" class="rounded-8px shadow-sm">
      <template #header-extra>
        <NSpace>
          <NButton type="primary" @click="openAddModal">发布公告</NButton>
          <NButton :loading="loading" @click="loadData">刷新</NButton>
        </NSpace>
      </template>

      <NDataTable
        :loading="loading"
        :columns="columns"
        :data="list"
        :row-key="(row: Api.Gglist.Item) => row.id"
        :pagination="{ pageSize: 15 }"
        striped
      />
    </NCard>

    <NModal
      v-model:show="modalVisible"
      preset="card"
      :title="modalTitle"
      class="max-w-600px"
      :mask-closable="false"
    >
      <NForm :model="formModel" label-placement="left" label-width="90">
        <NFormItem label="公告标题" required>
          <NInput v-model:value="formModel.title" placeholder="请输入公告标题" />
        </NFormItem>
        <NFormItem label="公告内容" required>
          <NInput v-model:value="formModel.content" type="textarea" :autosize="{ minRows: 4, maxRows: 10 }" placeholder="支持纯文本或 HTML 标签" />
        </NFormItem>
        <NFormItem label="置顶显示">
          <NSwitch v-model:value="formModel.zhiding" :checked-value="1" :unchecked-value="0">
            <template #checked>置顶</template>
            <template #unchecked>普通</template>
          </NSwitch>
        </NFormItem>
        <NFormItem label="前台可见">
          <NSwitch v-model:value="formModel.status" :checked-value="1" :unchecked-value="0">
            <template #checked>可见</template>
            <template #unchecked>隐藏</template>
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
