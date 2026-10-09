<script setup lang="ts">
import { computed, h, onMounted, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { NTag } from 'naive-ui';
import { fetchMyPriceList } from '@/service/api';

defineOptions({ name: 'Myprice' });

const loading = ref(false);
const list = ref<Api.ProfileArea.MyPriceItem[]>([]);
const userRate = ref('1.00');
const totalProducts = ref(0);
const totalCategories = ref(0);
const keyword = ref('');

const columns: DataTableColumns<Api.ProfileArea.MyPriceItem> = [
  { title: 'CID', key: 'cid', width: 70 },
  {
    title: '分类',
    key: 'fenleiName',
    width: 120,
    render: row => h(NTag, { size: 'small', type: 'info', round: true }, { default: () => row.fenleiName })
  },
  { title: '课程名称', key: 'name', minWidth: 180 },
  { title: '全站基准价', key: 'basePrice', width: 110, render: row => `¥ ${row.basePrice}` },
  {
    title: '我的实际价格',
    key: 'userPrice',
    width: 130,
    render: row => h('span', { class: 'font-bold text-15px text-primary' }, `¥ ${row.userPrice}`)
  },
  { title: '查课扣费', key: 'ckkf', width: 100, render: row => (Number(row.ckkf) > 0 ? `¥ ${row.ckkf}` : '免费') },
  { title: '特别说明', key: 'content', minWidth: 200, ellipsis: { tooltip: true } }
];

const filteredList = computed(() => {
  const kw = keyword.value.trim().toLowerCase();
  if (!kw) return list.value;
  return list.value.filter(i => i.name.toLowerCase().includes(kw) || i.fenleiName.toLowerCase().includes(kw));
});

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchMyPriceList();
  loading.value = false;
  if (!error && data) {
    list.value = data.list;
    userRate.value = data.userRate;
    totalProducts.value = data.totalProducts;
    totalCategories.value = data.totalCategories;
  }
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NGrid cols="1 s:3" responsive="screen" :x-gap="16" :y-gap="16">
      <NGi>
        <NCard embedded :bordered="false" class="rounded-8px">
          <NStatistic label="我的代理价格系数" :value="`${userRate} ×`" />
        </NCard>
      </NGi>
      <NGi>
        <NCard embedded :bordered="false" class="rounded-8px">
          <NStatistic label="在售商品总数" :value="totalProducts">
            <template #suffix>门</template>
          </NStatistic>
        </NCard>
      </NGi>
      <NGi>
        <NCard embedded :bordered="false" class="rounded-8px">
          <NStatistic label="在售分类总数" :value="totalCategories">
            <template #suffix>个</template>
          </NStatistic>
        </NCard>
      </NGi>
    </NGrid>

    <NCard title="商品学习价格表" :bordered="false" class="rounded-8px shadow-sm">
      <div class="mb-16px flex items-center justify-between gap-12px">
        <NInput v-model:value="keyword" placeholder="搜索课程名称 / 分类" clearable class="w-260px" />
        <NButton :loading="loading" type="primary" ghost @click="loadData">刷新</NButton>
      </div>

      <NDataTable
        :loading="loading"
        :columns="columns"
        :data="filteredList"
        :row-key="(row: Api.ProfileArea.MyPriceItem) => row.cid"
        :pagination="{ pageSize: 20 }"
        striped
      />
    </NCard>
  </div>
</template>
