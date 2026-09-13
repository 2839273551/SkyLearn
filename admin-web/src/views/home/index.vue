<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { NAlert, NCard, NEmpty, NList, NListItem, NSpin, NTag, NThing } from 'naive-ui';
import { fetchDashboard, fetchGglistList } from '@/service/api';

defineOptions({ name: 'Home' });

const loading = ref(false);
const announcement = ref('');
const noticeList = ref<any[]>([]);

async function loadData() {
  loading.value = true;
  const [dashRes, ggRes] = await Promise.all([
    fetchDashboard(),
    fetchGglistList()
  ]);
  loading.value = false;

  if (!dashRes.error && dashRes.data) {
    announcement.value = dashRes.data.announcement;
  }
  if (!ggRes.error && ggRes.data) {
    noticeList.value = ggRes.data.list;
  }
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-10px sm:p-16px max-w-1000px mx-auto">
    <!-- 顶部标题横幅 -->
    <NCard :bordered="false" class="rounded-12px shadow-sm border border-gray-100 dark:border-dark-600 bg-white dark:bg-dark-700">
      <div class="flex items-center gap-12px">
        <div class="flex h-44px w-44px items-center justify-center rounded-10px bg-primary/10 text-primary text-22px">
          📢
        </div>
        <div>
          <h1 class="text-17px font-bold text-gray-800 dark:text-gray-100">全站系统公告与通知中心</h1>
          <p class="text-12px text-gray-400 mt-2px">为全体代理商户提供最新的平台通告、通道维护、网课上新与服务政策</p>
        </div>
      </div>
    </NCard>

    <NSpin :show="loading">
      <div class="flex flex-col gap-16px">
        <!-- 站长置顶公告 -->
        <NCard v-if="announcement" title="📌 站长置顶公告" :bordered="false" class="rounded-12px shadow-sm border border-amber-200/80 bg-amber-50/40 dark:bg-dark-700 dark:border-dark-500">
          <div class="whitespace-pre-wrap text-14px sm:text-15px text-gray-700 dark:text-gray-200 leading-relaxed font-medium">
            {{ announcement }}
          </div>
        </NCard>

        <!-- 历史公告流水 -->
        <NCard title="📋 历史通告与更新日志" :bordered="false" class="rounded-12px shadow-sm">
          <div v-if="!noticeList.length" class="py-30px">
            <NEmpty description="暂无历史系统通告" />
          </div>
          <NList v-else hoverable clickable class="flex flex-col gap-10px">
            <NListItem v-for="item in noticeList" :key="item.id" class="rounded-8px p-12px border border-gray-100 dark:border-dark-600">
              <NThing :title="item.title" :description="`发布时间: ${item.time || item.addtime}`">
                <template #header-extra>
                  <NTag size="tiny" type="primary" round>官方发布</NTag>
                </template>
                <div class="mt-8px rounded-6px bg-gray-50/80 p-10px text-13px text-gray-600 dark:bg-dark-600 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">
                  {{ item.content }}
                </div>
              </NThing>
            </NListItem>
          </NList>
        </NCard>
      </div>
    </NSpin>
  </div>
</template>
