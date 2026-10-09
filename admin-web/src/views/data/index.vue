<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { fetchDataStats } from '@/service/api';

defineOptions({ name: 'Data' });

const loading = ref(false);
const stats = ref<Api.DataStats.Stats>({
  totalUsers: 0,
  todayUsers: 0,
  totalOrders: 0,
  todayOrders: 0,
  yesterdayOrders: 0,
  sevenDaysOrders: 0,
  todaySales: '0.00',
  yesterdaySales: '0.00',
  todayRecharge: '0.00'
});

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchDataStats();
  loading.value = false;
  if (!error && data) {
    stats.value = data;
  }
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="今日数据运营看板" :bordered="false" class="rounded-8px shadow-sm">
      <template #header-extra>
        <NButton :loading="loading" type="primary" ghost @click="loadData">
          刷新数据
        </NButton>
      </template>

      <!-- 核心指标统计网格 -->
      <NGrid cols="1 s:2 m:3 l:4" responsive="screen" :x-gap="16" :y-gap="16">
        <NGi>
          <NCard embedded :bordered="false" class="rounded-8px">
            <NStatistic label="今日销售额" :value="stats.todaySales">
              <template #prefix>¥</template>
            </NStatistic>
          </NCard>
        </NGi>
        <NGi>
          <NCard embedded :bordered="false" class="rounded-8px">
            <NStatistic label="今日总充值" :value="stats.todayRecharge">
              <template #prefix>¥</template>
            </NStatistic>
          </NCard>
        </NGi>
        <NGi>
          <NCard embedded :bordered="false" class="rounded-8px">
            <NStatistic label="今日订单数" :value="stats.todayOrders">
              <template #suffix>单</template>
            </NStatistic>
          </NCard>
        </NGi>
        <NGi>
          <NCard embedded :bordered="false" class="rounded-8px">
            <NStatistic label="今日新增用户" :value="stats.todayUsers">
              <template #suffix>人</template>
            </NStatistic>
          </NCard>
        </NGi>

        <NGi>
          <NCard embedded :bordered="false" class="rounded-8px">
            <NStatistic label="昨日销售额" :value="stats.yesterdaySales">
              <template #prefix>¥</template>
            </NStatistic>
          </NCard>
        </NGi>
        <NGi>
          <NCard embedded :bordered="false" class="rounded-8px">
            <NStatistic label="昨日订单数" :value="stats.yesterdayOrders">
              <template #suffix>单</template>
            </NStatistic>
          </NCard>
        </NGi>
        <NGi>
          <NCard embedded :bordered="false" class="rounded-8px">
            <NStatistic label="近 7 天总订单" :value="stats.sevenDaysOrders">
              <template #suffix>单</template>
            </NStatistic>
          </NCard>
        </NGi>
        <NGi>
          <NCard embedded :bordered="false" class="rounded-8px">
            <NStatistic label="全站总注册用户" :value="stats.totalUsers">
              <template #suffix>人</template>
            </NStatistic>
          </NCard>
        </NGi>

        <NGi span="1 s:2 m:3 l:4">
          <NCard embedded :bordered="false" class="rounded-8px">
            <NStatistic label="历史累计总订单" :value="stats.totalOrders">
              <template #suffix>条</template>
            </NStatistic>
          </NCard>
        </NGi>
      </NGrid>
    </NCard>
  </div>
</template>
