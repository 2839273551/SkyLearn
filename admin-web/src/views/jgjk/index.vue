<script setup lang="ts">
import { computed, h, onMounted, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { NButton, NCard, NDataTable, NInput, NSpace, NSpin, NTag, NTooltip } from 'naive-ui';
import { fetchClassList, fetchHuoyuanList } from '@/service/api';

defineOptions({ name: 'Jgjk' });

const loading = ref(false);
const classList = ref<any[]>([]);
const huoyuanList = ref<any[]>([]);
const keyword = ref('');

async function loadData() {
  loading.value = true;
  const [resClass, resHy] = await Promise.all([
    fetchClassList({ page: 1, pageSize: 200 }),
    fetchHuoyuanList()
  ]);
  loading.value = false;

  if (!resClass.error && resClass.data) {
    classList.value = resClass.data.records;
  }
  if (!resHy.error && resHy.data) {
    huoyuanList.value = resHy.data.list;
  }
}

const huoyuanMap = computed(() => {
  const map: Record<string, string> = {};
  huoyuanList.value.forEach(h => {
    map[h.hid] = h.name;
  });
  return map;
});

const filteredList = computed(() => {
  if (!keyword.value.trim()) return classList.value;
  const kw = keyword.value.trim().toLowerCase();
  return classList.value.filter(c => c.name.toLowerCase().includes(kw) || String(c.cid).includes(kw));
});

const columns: DataTableColumns<any> = [
  { title: 'CID', key: 'cid', width: 80 },
  { title: '网课平台名称', key: 'name', minWidth: 200 },
  {
    title: '所属货源',
    key: 'docking',
    width: 150,
    render: row => huoyuanMap.value[row.docking] || `货源HID: ${row.docking}`
  },
  {
    title: '基准标价',
    key: 'price',
    width: 110,
    render: row => h('strong', { class: 'font-mono text-primary' }, `¥ ${row.price}`)
  },
  {
    title: '计价模式',
    key: 'yunsuan',
    width: 110,
    render: row => (row.yunsuan === '+' ? '加法浮动 (+)' : '乘法倍率 (×)')
  },
  {
    title: '监控状态',
    key: 'status',
    width: 110,
    render: row =>
      h(
        NTag,
        { size: 'small', type: row.status === 1 ? 'success' : 'default', round: true },
        { default: () => (row.status === 1 ? '● 正常巡检中' : '已下架停用') }
      )
  }
];

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-10px sm:p-16px">
    <NCard :bordered="false" class="rounded-12px shadow-sm border border-gray-100 dark:border-dark-600">
      <div class="flex flex-wrap items-center justify-between gap-16px">
        <div class="flex items-center gap-12px">
          <div class="flex h-44px w-44px items-center justify-center rounded-10px bg-primary/10 text-primary text-22px">
            📊
          </div>
          <div>
            <h1 class="text-17px font-bold text-gray-800 dark:text-gray-100">全网商品价格监控与成本巡检</h1>
            <p class="text-12px text-gray-400 mt-2px">实时巡检各货源平台成本价变动，防范上游突发涨价导致的利润倒挂亏损风险</p>
          </div>
        </div>
        <div class="flex items-center gap-10px">
          <NInput v-model:value="keyword" placeholder="搜索网课名称 / CID" clearable class="w-220px" />
          <NButton :loading="loading" @click="loadData">刷新监控</NButton>
        </div>
      </div>
    </NCard>

    <NCard title="价格监控明细" :bordered="false" class="rounded-12px shadow-sm">
      <NSpin :show="loading">
        <NDataTable
          :columns="columns"
          :data="filteredList"
          :row-key="(row: any) => row.cid"
          :pagination="false"
          striped
          size="small"
          :scroll-x="900"
        />
      </NSpin>
    </NCard>
  </div>
</template>
