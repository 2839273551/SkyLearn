<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import {
  NAlert,
  NBadge,
  NButton,
  NCard,
  NCode,
  NDrawer,
  NDrawerContent,
  NEmpty,
  NModal,
  NPopconfirm,
  NProgress,
  NSelect,
  NSpace,
  NSpin,
  NStatistic,
  NSwitch,
  NTag,
  NTooltip
} from 'naive-ui';
import {
  clearSchedulerTaskLogs,
  fetchSchedulerTaskLogs,
  fetchSchedulerTasksList,
  runSchedulerTask,
  updateSchedulerTask
} from '@/service/api';
import { useAppStore } from '@/store/modules/app';

defineOptions({ name: 'Scheduler' });

const appStore = useAppStore();
const loading = ref(false);
const tasks = ref<Api.Scheduler.TaskItem[]>([]);
const summary = ref<Api.Scheduler.Summary>({
  total_tasks: 8,
  enabled_tasks: 8,
  total_runs_all: 0,
  total_success_all: 0
});

const btCron = ref<Api.Scheduler.BtCronStatus>({
  is_active: false,
  last_heartbeat_time: '',
  elapsed_seconds: 999999
});

// 单任务运行状态映射
const runningTaskMap = ref<Record<string, boolean>>({});

// 周期选项（几分钟运行一次）
const intervalOptions = [
  { label: '每 1 分钟执行一次', value: 1 },
  { label: '每 2 分钟执行一次', value: 2 },
  { label: '每 3 分钟执行一次', value: 3 },
  { label: '每 5 分钟执行一次', value: 5 },
  { label: '每 10 分钟执行一次', value: 10 },
  { label: '每 15 分钟执行一次', value: 15 },
  { label: '每 20 分钟执行一次', value: 20 },
  { label: '每 30 分钟执行一次', value: 30 },
  { label: '每 60 分钟执行一次', value: 60 }
];

// 历史日志抽屉
const logDrawerVisible = ref(false);
const activeTask = ref<Api.Scheduler.TaskItem | null>(null);
const logListLoading = ref(false);
const historyLogs = ref<Api.Scheduler.LogItem[]>([]);

async function loadTasks() {
  loading.value = true;
  const { data, error } = await fetchSchedulerTasksList();
  loading.value = false;

  if (!error && data) {
    tasks.value = data.tasks;
    summary.value = data.summary;
    if (data.bt_cron) {
      btCron.value = data.bt_cron;
    }
  }
}

// 切换任务开启/关闭开关
async function handleToggleSwitch(task: Api.Scheduler.TaskItem, checked: boolean) {
  task.enabled = checked;
  const { error } = await updateSchedulerTask({
    task_id: task.id,
    enabled: checked
  });
  if (!error) {
    window.$message?.success(`任务 [${task.name}] 已${checked ? '启用' : '停用'}`);
    loadTasks();
  } else {
    task.enabled = !checked;
  }
}

// 修改运行间隔（几分钟一次）
async function handleIntervalChange(task: Api.Scheduler.TaskItem, mins: number) {
  task.interval_mins = mins;
  const { error } = await updateSchedulerTask({
    task_id: task.id,
    interval_mins: mins
  });
  if (!error) {
    window.$message?.success(`任务 [${task.name}] 周期已调整为每 ${mins} 分钟一次`);
  }
}

// 单独立即执行任务
async function handleRunSingle(task: Api.Scheduler.TaskItem) {
  runningTaskMap.value[task.id] = true;
  const { data, error } = await runSchedulerTask(task.id);
  runningTaskMap.value[task.id] = false;

  if (!error && data) {
    task.latest_log = data.logs;
    task.last_result = data.summary;
    task.last_cost_ms = data.cost_ms;
    task.last_run_time = '刚刚';
    window.$message?.success(`任务 [${task.name}] 执行完成，耗时 ${data.cost_ms}ms`);
    loadTasks();
  }
}

// 打开历史日志抽屉
async function openLogs(task: Api.Scheduler.TaskItem) {
  activeTask.value = task;
  logDrawerVisible.value = true;
  logListLoading.value = true;
  const { data, error } = await fetchSchedulerTaskLogs(task.id);
  logListLoading.value = false;
  if (!error && data) {
    historyLogs.value = data.logs;
  }
}

// 清理日志
async function handleClearLogs(taskId?: string) {
  const { error } = await clearSchedulerTaskLogs(taskId);
  if (!error) {
    window.$message?.success('日志清理成功');
    if (activeTask.value) {
      openLogs(activeTask.value);
    }
    loadTasks();
  }
}

// 复制宝塔定时任务指令
function copyBtCommand() {
  const cmd = `/www/server/php/74/bin/php /www/wwwroot/sk.yunxnet.cn/admin-api/v1/cron.php`;
  navigator.clipboard.writeText(cmd);
  window.$message?.success('宝塔计划任务指令已复制到剪贴板');
}

function formatHeartbeat(bt: Api.Scheduler.BtCronStatus) {
  if (!bt.last_heartbeat_time || bt.elapsed_seconds >= 86400) {
    return '暂无心跳记录';
  }
  if (bt.elapsed_seconds < 60) {
    return `${bt.elapsed_seconds} 秒前`;
  }
  const mins = Math.floor(bt.elapsed_seconds / 60);
  return `${mins} 分钟前`;
}

let timer: any = null;
onMounted(() => {
  loadTasks();
  timer = setInterval(() => {
    fetchSchedulerTasksList().then(({ data }) => {
      if (data?.bt_cron) {
        btCron.value = data.bt_cron;
      }
    });
  }, 10000);
});

onUnmounted(() => {
  if (timer) clearInterval(timer);
});
</script>

<template>
  <div class="flex flex-col gap-14px p-10px sm:p-16px max-w-1440px mx-auto font-sans">
    <!-- 顶部极简状态看板（彻底去除多余大块与无用一键启动） -->
    <NCard :bordered="false" class="rounded-8px shadow-sm bg-white dark:bg-dark-700">
      <div class="flex flex-wrap items-center justify-between gap-14px">
        <!-- 左侧：宝塔运行状态与指示灯 -->
        <div class="flex items-center gap-12px">
          <!-- 状态圆点指示灯 -->
          <div
            class="flex h-38px w-38px items-center justify-center rounded-full text-18px shrink-0"
            :class="
              btCron.is_active
                ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 animate-pulse'
                : 'bg-gray-100 text-gray-400 dark:bg-dark-500'
            "
          >
            {{ btCron.is_active ? '🟢' : '⚪' }}
          </div>
          <div>
            <div class="flex items-center gap-8px">
              <h1 class="text-16px font-bold text-gray-900 dark:text-gray-100">
                宝塔定时任务状态：
              </h1>
              <NTag
                :type="btCron.is_active ? 'success' : 'default'"
                size="small"
                round
                class="font-bold px-8px"
              >
                {{ btCron.is_active ? '已开启 · 自动运行中' : '未开启 / 已暂停' }}
              </NTag>
            </div>
            <p class="mt-4px text-12px text-gray-500 dark:text-gray-400">
              <template v-if="btCron.is_active">
                心跳正常（最近触发于: <span class="font-mono font-medium text-emerald-600 dark:text-emerald-400">{{ btCron.last_heartbeat_time }}</span>，{{ formatHeartbeat(btCron) }}）。各任务按设定周期自动调度。
              </template>
              <template v-else>
                最近未检测到宝塔定时心跳（系统已完全静默休眠，零资源占用）。只有在宝塔开启计划任务后才会自动运行。
              </template>
            </p>
          </div>
        </div>

        <!-- 右侧：精炼动作按钮组 (彻底去除一键启动全部) -->
        <div class="flex items-center gap-8px">
          <NButton size="small" type="primary" secondary @click="copyBtCommand">
            📋 复制宝塔命令
          </NButton>
          <NButton size="small" secondary :loading="loading" @click="loadTasks">
            🔄 检查状态
          </NButton>
          <NPopconfirm @positive-click="() => handleClearLogs()">
            <template #trigger>
              <NButton size="small" quaternary type="warning">
                🗑️ 清空日志
              </NButton>
            </template>
            确定清空全部调度引擎的执行日志吗？
          </NPopconfirm>
        </div>
      </div>
    </NCard>

    <!-- 任务多窗口列表栅格 (每个任务一个简洁干净的窗口) -->
    <div class="grid grid-cols-1 gap-14px lg:grid-cols-3">
      <div
        v-for="task in tasks"
        :key="task.id"
        class="flex flex-col justify-between rounded-8px bg-white p-14px shadow-sm transition-all border dark:bg-dark-700"
        :class="task.enabled ? 'border-gray-200 hover:border-primary/50 dark:border-dark-500' : 'border-dashed border-gray-300 opacity-75 dark:border-dark-600'"
      >
        <div>
          <!-- 窗口头部：任务标识、开关、周期选择 -->
          <div class="flex items-start justify-between gap-8px border-b border-gray-100 pb-8px dark:border-dark-600">
            <div>
              <div class="flex items-center gap-6px">
                <span class="rounded bg-primary/10 px-6px py-1px font-mono text-11px font-bold text-primary">
                  {{ task.id.toUpperCase() }}
                </span>
                <span class="text-14px font-bold text-gray-800 dark:text-gray-100">
                  {{ task.name }}
                </span>
              </div>
              <p class="mt-3px text-12px text-gray-400 line-clamp-1" :title="task.description">
                {{ task.description }}
              </p>
            </div>

            <!-- 任务开关 -->
            <div class="flex items-center gap-6px">
              <NSwitch
                :value="task.enabled"
                size="small"
                @update:value="checked => handleToggleSwitch(task, checked)"
              />
            </div>
          </div>

          <!-- 调度周期设置（几分钟运行一次） -->
          <div class="mt-8px flex items-center justify-between gap-8px rounded-6px bg-gray-50/80 px-8px py-4px text-12px dark:bg-dark-600">
            <span class="text-gray-500">运行频次：</span>
            <NSelect
              :value="task.interval_mins"
              :options="intervalOptions"
              size="tiny"
              class="w-140px"
              @update:value="mins => handleIntervalChange(task, mins)"
            />
          </div>

          <!-- 黑客流极客终端视窗 (实时日志) -->
          <div class="mt-8px overflow-hidden rounded-6px bg-[#1e1e1e] p-8px text-11px font-mono leading-relaxed text-gray-200 shadow-inner">
            <div class="flex items-center justify-between border-b border-gray-700 pb-4px text-10px text-gray-400">
              <span class="flex items-center gap-4px">
                <span class="inline-block h-6px w-6px rounded-full" :class="task.enabled ? 'bg-emerald-400 animate-pulse' : 'bg-gray-500'"></span>
                TERMINAL OUTPUT
              </span>
              <span>上次: {{ task.last_run_time || '未执行' }}</span>
            </div>
            <div class="mt-4px max-h-70px min-h-45px overflow-y-auto whitespace-pre-wrap select-text">
              {{ task.latest_log || '暂无运行输出，点击下方按钮立即执行' }}
            </div>
          </div>
        </div>

        <!-- 窗口底部动作栏 -->
        <div class="mt-10px flex items-center justify-between gap-8px pt-8px border-t border-gray-100 dark:border-dark-600">
          <div class="text-11px text-gray-400">
            成功: <strong class="text-primary font-mono">{{ task.total_success }}</strong> 笔
          </div>

          <div class="flex items-center gap-6px">
            <NButton size="tiny" secondary @click="openLogs(task)">
              📜 历史日志
            </NButton>
            <NButton
              size="tiny"
              type="primary"
              :loading="Boolean(runningTaskMap[task.id])"
              @click="handleRunSingle(task)"
            >
              ▶️ 立即运行
            </NButton>
          </div>
        </div>
      </div>
    </div>

    <!-- 历史日志抽屉 -->
    <NDrawer v-model:show="logDrawerVisible" :width="appStore.isMobile ? '92vw' : 580" placement="right">
      <NDrawerContent :title="`[${activeTask?.name || '任务'}] 历史调度日志`" closable>
        <template #footer>
          <NPopconfirm @positive-click="() => handleClearLogs(activeTask?.id)">
            <template #trigger>
              <NButton size="small" type="warning" secondary>清空此任务历史日志</NButton>
            </template>
            确定清空该任务的所有日志吗？
          </NPopconfirm>
        </template>

        <NSpin :show="logListLoading">
          <div v-if="historyLogs.length === 0" class="py-40px text-center">
            <NEmpty description="暂无历史执行日志" />
          </div>
          <div v-else class="flex flex-col gap-10px">
            <div
              v-for="log in historyLogs"
              :key="log.id"
              class="rounded-8px border border-gray-200 bg-gray-50 p-10px dark:border-dark-500 dark:bg-dark-600 text-12px"
            >
              <div class="flex items-center justify-between border-b border-gray-200 pb-6px dark:border-dark-500">
                <span class="font-mono text-gray-500">{{ log.created_at }}</span>
                <div class="flex items-center gap-6px">
                  <NTag size="tiny" :type="log.status === 1 ? 'success' : 'error'" round>
                    {{ log.status === 1 ? '成功' : '失败' }}
                  </NTag>
                  <span class="font-mono text-11px text-gray-400">耗时: {{ log.cost_ms }}ms</span>
                </div>
              </div>
              <div class="mt-6px whitespace-pre-wrap font-mono text-11px leading-relaxed text-gray-800 dark:text-gray-200">
                {{ log.content }}
              </div>
            </div>
          </div>
        </NSpin>
      </NDrawerContent>
    </NDrawer>
  </div>
</template>
