<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { fetchZzbzInfo } from '@/service/api';

defineOptions({ name: 'Zzbz' });

const loading = ref(false);
const crons = ref<Api.Zzbz.CronItem[]>([]);
const daemons = ref<Api.Zzbz.DaemonItem[]>([]);

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchZzbzInfo();
  loading.value = false;
  if (!error && data) {
    crons.value = data.crons;
    daemons.value = data.daemons;
  }
}

function copyText(text: string) {
  navigator.clipboard.writeText(text);
  window.$message?.success('已复制到剪贴板');
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="站长部署与自动化运维指南" :bordered="false" class="rounded-8px shadow-sm">
      <NAlert type="info" title="基础环境要求" class="mb-16px">
        本网课系统后端运行在 PHP 7.4 + Nginx + MySQL 环境下，异步队列依托 Redis 支持，请确保服务器已安装并启动 Redis 服务。
      </NAlert>

      <NTabs type="line" animated>
        <!-- 计划任务 -->
        <NTabPane name="crons" tab="宝塔计划任务 (定时监控)">
          <div class="flex flex-col gap-12px">
            <p class="text-13px text-gray-500">
              请在 <strong>宝塔面板 ➔ 计划任务</strong> 中添加任务，任务类型选择 <code>访问 URL</code>，并按以下建议频率监控：
            </p>

            <div
              v-for="(item, index) in crons"
              :key="index"
              class="flex flex-wrap items-center justify-between gap-12px rounded-6px border border-gray-100 bg-gray-50 p-12px dark:border-dark-400 dark:bg-dark-600"
            >
              <div>
                <div class="font-600 text-gray-800 dark:text-gray-100">
                  {{ item.title }}
                  <span class="ml-8px text-12px font-normal text-primary">推荐频率：{{ item.cycle }}</span>
                </div>
                <div class="mt-4px font-mono text-12px text-gray-600 dark:text-gray-300">{{ item.url }}</div>
              </div>
              <NButton size="small" type="primary" ghost @click="copyText(item.url)">复制 URL</NButton>
            </div>
          </div>
        </NTabPane>

        <!-- 进程守护 -->
        <NTabPane name="daemons" tab="Redis 队列进程守护">
          <div class="flex flex-col gap-12px">
            <p class="text-13px text-gray-500">
              请在 <strong>宝塔面板 ➔ 软件商店 ➔ 进程守护管理器 (Supervisor)</strong> 中添加以下出队常驻进程：
            </p>

            <div
              v-for="(item, index) in daemons"
              :key="index"
              class="flex flex-wrap items-center justify-between gap-12px rounded-6px border border-gray-100 bg-gray-50 p-12px dark:border-dark-400 dark:bg-dark-600"
            >
              <div>
                <div class="font-600 text-gray-800 dark:text-gray-100">
                  {{ item.name }}
                  <span class="ml-8px text-12px font-normal text-success">进程数量：{{ item.count }}</span>
                </div>
                <div class="mt-4px font-mono text-12px text-gray-600 dark:text-gray-300">
                  启动命令：<code>{{ item.cmd }}</code> | 运行目录：<code>{{ item.dir }}</code>
                </div>
              </div>
              <NButton size="small" type="primary" ghost @click="copyText(item.cmd)">复制命令</NButton>
            </div>
          </div>
        </NTabPane>
      </NTabs>
    </NCard>
  </div>
</template>
