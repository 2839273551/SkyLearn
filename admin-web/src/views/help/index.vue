<script setup lang="ts">
import { computed, h, onMounted, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { NTag } from 'naive-ui';
import { fetchHelpList } from '@/service/api';

defineOptions({ name: 'Help' });

const loading = ref(false);
const list = ref<Api.ProfileArea.HelpItem[]>([]);
const keyword = ref('');

const columns: DataTableColumns<Api.ProfileArea.HelpItem> = [
  { title: '课程 CID', key: 'cid', width: 90 },
  {
    title: '所属分类',
    key: 'fenleiName',
    width: 130,
    render: row => h(NTag, { size: 'small', type: 'info', round: true }, { default: () => row.fenleiName })
  },
  { title: '课程名称', key: 'name', width: 200 },
  { title: '课程说明与注意事项', key: 'content', minWidth: 320 }
];

const filteredList = computed(() => {
  const kw = keyword.value.trim().toLowerCase();
  if (!kw) return list.value;
  return list.value.filter(i => i.name.toLowerCase().includes(kw) || i.fenleiName.toLowerCase().includes(kw) || i.content.toLowerCase().includes(kw));
});

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchHelpList();
  loading.value = false;
  if (!error && data) {
    list.value = data.list;
  }
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="课程下单与查课说明手册" :bordered="false" class="rounded-8px shadow-sm">
      <div class="mb-16px flex items-center justify-between gap-12px">
        <NInput v-model:value="keyword" placeholder="搜索课程名称 / 分类 / 规则关键字" clearable class="w-280px" />
        <NButton :loading="loading" type="primary" ghost @click="loadData">刷新</NButton>
      </div>

      <NDataTable
        :loading="loading"
        :columns="columns"
        :data="filteredList"
        :row-key="(row: Api.ProfileArea.HelpItem) => row.cid"
        :pagination="{ pageSize: 20 }"
        striped
      />
    </NCard>
  </div>
</template>
