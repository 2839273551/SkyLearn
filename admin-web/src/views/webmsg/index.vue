<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { fetchWebmsgInfo } from '@/service/api';

defineOptions({ name: 'Webmsg' });

const loading = ref(false);
const systemInfo = ref<Api.Webmsg.SystemInfo>({
  appName: '网课管理中心',
  author: 'SkyLearn',
  version: '8.3.8',
  domain: '',
  serverIp: '',
  phpVersion: '',
  os: ''
});

const timeline = ref<Api.Webmsg.TimelineItem[]>([]);

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchWebmsgInfo();
  loading.value = false;
  if (!error && data) {
    systemInfo.value = data.systemInfo;
    timeline.value = data.timeline;
  }
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NGrid cols="1 m:2" responsive="screen" :x-gap="16" :y-gap="16">
      <!-- 系统参数 -->
      <NGi>
        <NCard title="系统基础信息" :bordered="false" class="h-full rounded-8px shadow-sm">
          <template #header-extra>
            <NButton :loading="loading" size="small" type="primary" ghost @click="loadData">刷新</NButton>
          </template>

          <NDescriptions :columns="1" bordered label-placement="left" label-width="120">
            <NDescriptionsItem label="系统名称">{{ systemInfo.appName }}</NDescriptionsItem>
            <NDescriptionsItem label="系统作者">{{ systemInfo.author }}</NDescriptionsItem>
            <NDescriptionsItem label="当前版本">
              <NTag type="primary" size="small" round>{{ systemInfo.version }}</NTag>
            </NDescriptionsItem>
            <NDescriptionsItem label="当前绑定域名">{{ systemInfo.domain || '-' }}</NDescriptionsItem>
            <NDescriptionsItem label="服务器 IP">{{ systemInfo.serverIp || '-' }}</NDescriptionsItem>
            <NDescriptionsItem label="PHP 运行环境">{{ systemInfo.phpVersion || '-' }}</NDescriptionsItem>
            <NDescriptionsItem label="服务器操作系统">{{ systemInfo.os || '-' }}</NDescriptionsItem>
          </NDescriptions>
        </NCard>
      </NGi>

      <!-- 版本历史时间线 -->
      <NGi>
        <NCard title="版本迭代历史" :bordered="false" class="h-full rounded-8px shadow-sm">
          <NTimeline>
            <NTimelineItem
              v-for="(item, index) in timeline"
              :key="index"
              :type="index === 0 ? 'success' : 'info'"
            >
              <template #header>
                <div class="flex flex-wrap items-center gap-8px mb-4px">
                  <strong class="font-mono text-14px font-bold text-gray-800 dark:text-gray-100">{{ item.version }}</strong>
                  <span class="text-12px text-gray-400 font-mono">{{ item.time }}</span>
                  <NTag v-if="index === 0" type="success" size="tiny" round>当前线上最新版</NTag>
                </div>
              </template>
              <div class="text-13px text-gray-700 dark:text-gray-300 leading-relaxed rounded-6px bg-gray-50/80 dark:bg-dark-600/50 p-8px border border-gray-100 dark:border-dark-500">
                {{ item.desc }}
              </div>
            </NTimelineItem>
          </NTimeline>
        </NCard>
      </NGi>
    </NGrid>
  </div>
</template>
