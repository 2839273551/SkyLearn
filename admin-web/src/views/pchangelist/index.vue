<script setup lang="ts">
import { h, onMounted, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { fetchPchangeList } from '@/service/api';

defineOptions({ name: 'Pchangelist' });

const loading = ref(false);
const list = ref<Api.ProfileArea.PchangeRecord[]>([]);
const total = ref(0);
const page = ref(1);
const cidFilter = ref('');
const stats = ref({ total: 0, today: 0 });

const columns: DataTableColumns<Api.ProfileArea.PchangeRecord> = [
  { title: '商品 CID', key: 'cid', width: 90 },
  { title: '商品名称', key: 'kcname', minWidth: 200 },
  { title: '调整前价格', key: 'oldprice', width: 120, render: row => `¥ ${row.oldprice}` },
  {
    title: '调整后价格',
    key: 'newprice',
    width: 140,
    render: row => {
      const diff = Number(row.newprice) - Number(row.oldprice);
      const isUp = diff > 0;
      const isDown = diff < 0;
      return h(
        'span',
        { class: isUp ? 'font-bold text-error' : isDown ? 'font-bold text-success' : '' },
        `¥ ${row.newprice} (${isUp ? `+${diff.toFixed(2)}` : diff.toFixed(2)})`
      );
    }
  },
  { title: '变动时间', key: 'updatetime', width: 170 }
];

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchPchangeList({
    page: page.value,
    cid: cidFilter.value.trim() || undefined
  });
  loading.value = false;

  if (!error && data) {
    list.value = data.records;
    total.value = data.total;
    stats.value = data.stats;
  }
}

function handleSearch() {
  page.value = 1;
  loadData();
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NGrid cols="1 s:2" responsive="screen" :x-gap="16" :y-gap="16">
      <NGi>
        <NCard embedded :bordered="false" class="rounded-8px">
          <NStatistic label="历史累计调价次数" :value="stats.total">
            <template #suffix>次</template>
          </NStatistic>
        </NCard>
      </NGi>
      <NGi>
        <NCard embedded :bordered="false" class="rounded-8px">
          <NStatistic label="今日调价次数" :value="stats.today">
            <template #suffix>次</template>
          </NStatistic>
        </NCard>
      </NGi>
    </NGrid>

    <NCard title="商品价格变动历史记录" :bordered="false" class="rounded-8px shadow-sm">
      <div class="mb-16px flex items-center justify-between gap-12px">
        <div class="flex items-center gap-10px">
          <NInput v-model:value="cidFilter" placeholder="按商品 CID 搜索" clearable class="w-180px" @keyup.enter="handleSearch" />
          <NButton type="primary" @click="handleSearch">查询</NButton>
        </div>
        <NButton :loading="loading" @click="loadData">刷新</NButton>
      </div>

      <NDataTable
        :loading="loading"
        :columns="columns"
        :data="list"
        :row-key="(row: Api.ProfileArea.PchangeRecord) => `${row.cid}_${row.updatetime}`"
        :pagination="false"
        striped
      />

      <div class="mt-16px flex justify-end">
        <NPagination
          v-model:page="page"
          :page-size="20"
          :item-count="total"
          @update:page="loadData"
        />
      </div>
    </NCard>
  </div>
</template>
