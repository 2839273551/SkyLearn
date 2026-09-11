<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { fetchDashboard } from '@/service/api';

defineOptions({ name: 'Home' });

const loading = ref(false);
const announcement = ref('');

async function loadAnnouncement() {
  loading.value = true;
  const { data, error } = await fetchDashboard();
  if (!error && data) announcement.value = data.announcement;
  loading.value = false;
}

onMounted(loadAnnouncement);
</script>

<template>
  <NSpace vertical :size="16">
    <NCard :bordered="false" class="card-wrapper">
      <div class="flex items-center gap-12px">
        <div class="size-44px flex-center rd-12px bg-primary/12 text-primary">
          <SvgIcon icon="ph:megaphone" class="text-24px" />
        </div>
        <div>
          <h2 class="text-22px font-600">实时公告</h2>
          <NText depth="3">原 `/index/home` 页面已迁移为新版界面。</NText>
        </div>
      </div>
    </NCard>

    <NCard title="站长公告" :bordered="false" class="card-wrapper">
      <NSpin :show="loading">
        <NEmpty v-if="!announcement" description="暂无公告" />
        <NAlert v-else type="info" :show-icon="true">
          <div class="whitespace-pre-wrap text-15px leading-7">{{ announcement }}</div>
        </NAlert>
      </NSpin>
    </NCard>
  </NSpace>
</template>

<style scoped></style>
