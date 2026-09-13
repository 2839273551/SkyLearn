<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { NCard, NEmpty, NList, NListItem, NSpin, NTag, NThing } from 'naive-ui';
import { fetchGglistList } from '@/service/api';

defineOptions({ name: 'Usernotice' });

const loading = ref(false);
const notices = ref<any[]>([]);

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchGglistList();
  loading.value = false;
  if (!error && data) {
    notices.value = data.list;
  }
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-10px sm:p-16px max-w-960px mx-auto">
    <NCard :bordered="false" class="rounded-12px shadow-sm border border-gray-100 dark:border-dark-600">
      <div class="flex items-center gap-12px">
        <div class="flex h-44px w-44px items-center justify-center rounded-10px bg-primary/10 text-primary text-22px">
          📢
        </div>
        <div>
          <h1 class="text-17px font-bold text-gray-800 dark:text-gray-100">站内消息与系统公告中心</h1>
          <p class="text-12px text-gray-400 mt-2px">实时发布平台服务升级、网课上新、通道状态维护与重要紧急通知</p>
        </div>
      </div>
    </NCard>

    <NCard title="通知公告流" :bordered="false" class="rounded-12px shadow-sm">
      <NSpin :show="loading">
        <div v-if="!notices.length" class="py-30px">
          <NEmpty description="当前暂无系统通知公告" />
        </div>
        <NList v-else hoverable clickable>
          <NListItem v-for="item in notices" :key="item.id">
            <NThing :title="item.title" :description="`发布时间: ${item.time || item.addtime}`">
              <template #header-extra>
                <NTag size="tiny" type="success" round>系统通知</NTag>
              </template>
              <div class="mt-8px rounded-6px bg-gray-50 p-10px text-13px text-gray-600 dark:bg-dark-600 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">
                {{ item.content }}
              </div>
            </NThing>
          </NListItem>
        </NList>
      </NSpin>
    </NCard>
  </div>
</template>
