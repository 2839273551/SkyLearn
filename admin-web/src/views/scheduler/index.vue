<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
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
  runAllSchedulerTasks,
  runSchedulerTask,
  updateSchedulerTask
} from '@/service/api';
import { useAppStore } from '@/store/modules/app';

defineOptions({ name: 'Scheduler' });

const appStore = useAppStore();
const loading = ref(false);
const runningAll = ref(false);
const tasks = ref<Api.Scheduler.TaskItem[]>([]);
const summary = ref<Api.Scheduler.Summary>({
  total_tasks: 8,
  enabled_tasks: 8,
  total_runs_all: 0,
  total_success_all: 0
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

// 一键启动全部已开启任务
async function handleRunAll() {
  runningAll.value = true;
  const { data, error } = await runAllSchedulerTasks();
  runningAll.value = false;

  if (!error && data) {
    const totalSuccess = data.reports.reduce((acc, cur) => acc + cur.success_count, 0);
    window.$message?.success(`已完成全部 ${data.reports.length} 个任务的流式调度，处理订单 ${totalSuccess} 笔`);
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

onMounted(() => {
  loadTasks();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-10px sm:p-16px">
    <!-- 顶部总控台看板 (优雅现代质感) -->
    <NCard :bordered="false" class="rounded-12px shadow-sm border border-gray-100 dark:border-dark-600 bg-white dark:bg-dark-700">
      <div class="flex flex-wrap items-center justify-between gap-16px">
        <!-- 左侧：标题与全局状态 -->
        <div class="flex flex-wrap items-center gap-14px">
          <div class="flex h-48px w-48px items-center justify-center rounded-12px bg-primary/10 text-primary text-24px">
            ⚙️
          </div>
          <div>
            <div class="flex items-center gap-8px">
              <h1 class="text-18px font-bold text-gray-800 dark:text-gray-100">自动化任务调度与队列中心</h1>
              <NTag size="tiny" type="success" round class="font-medium">
                ● 调度引擎运行中
              </NTag>
            </div>
            <div class="mt-4px flex flex-wrap items-center gap-12px text-13px text-gray-500">
              <span>
                任务总数：<strong class="font-mono font-bold text-gray-800 dark:text-gray-200">{{ summary.total_tasks }}</strong> 个
              </span>
              <span>|</span>
              <span>
                已启用：<strong class="font-mono font-bold text-emerald-600">{{ summary.enabled_tasks }}</strong> 个
              </span>
              <span>|</span>
              <span>
                累计成功调度订单：<strong class="font-mono font-bold text-primary">{{ summary.total_success_all }}</strong> 笔
              </span>
              <span>|</span>
              <span>
                调度日志池存量：<strong class="font-mono font-bold text-gray-700 dark:text-gray-300">{{ summary.total_logs ?? 0 }}</strong> 笔
              </span>
            </div>
          </div>
        </div>

        <!-- 右侧：一键启动与自动化操作 -->
        <div class="flex flex-wrap items-center gap-10px">
          <NPopconfirm @positive-click="() => handleClearLogs()">
            <template #trigger>
              <NButton secondary size="medium" type="warning">
                🗑️ 清空所有日志
              </NButton>
            </template>
            确定清空全部调度引擎的执行日志吗？
          </NPopconfirm>

          <NTooltip>
            <template #trigger>
              <NButton secondary size="medium" @click="copyBtCommand">
                📋 复制宝塔定时命令
              </NButton>
            </template>
            可直接将此命令添加至宝塔面板的「计划任务」中（建议设置为每1分钟执行一次），实现全自动无人值守！
          </NTooltip>

          <NButton secondary size="medium" :loading="loading" @click="loadTasks">
            刷新状态
          </NButton>

          <!-- 一键启动全部按钮 -->
          <NButton
            type="primary"
            size="medium"
            :loading="runningAll"
            class="px-20px font-bold shadow-md"
            @click="handleRunAll"
          >
            🚀 一键启动全部任务
          </NButton>
        </div>
      </div>

      <div class="mt-14px flex flex-wrap items-center justify-between gap-10px rounded-8px bg-emerald-50/70 p-10px border border-emerald-200/80 dark:bg-emerald-950/20 dark:border-emerald-800/40 text-12px text-emerald-800 dark:text-emerald-300">
        <div class="flex items-center gap-6px">
          <span class="text-14px">🛡️</span>
          <span><strong>智能归档防刷已开启：</strong>所有标记为<strong>【已完成】</strong>、已退款/取消或进度达 100% 的订单，系统已自动隔离归档，<strong>绝不再参与下一轮同步轮询</strong>！</span>
        </div>
        <div class="flex items-center gap-6px">
          <span class="text-14px">🧹</span>
          <span><strong>自动瘦身清理已开启：</strong>系统每次调度后<strong>自动清理超过 3 天的历史过期日志</strong>，单任务保留上限 200 条，永久保护数据库轻盈！</span>
        </div>
      </div>

      <div class="mt-10px flex flex-wrap items-center justify-between gap-8px rounded-8px bg-blue-50/70 dark:bg-dark-600 p-10px border border-blue-200/80 dark:border-dark-500 text-12px text-blue-900 dark:text-blue-300">
        <div class="flex items-center gap-6px flex-wrap">
          <span class="font-bold">🖥️ 宝塔计划任务一键配置：</span>
          <span>类型选【Shell 脚本】，周期选【1 分钟】，命令填：</span>
          <code class="bg-white dark:bg-dark-700 px-6px py-2px rounded font-mono text-primary font-bold border border-blue-200 dark:border-dark-400">/www/server/php/74/bin/php /www/wwwroot/sk.yunxnet.cn/admin-api/v1/cron.php</code>
        </div>
        <NButton size="tiny" type="primary" secondary @click="copyBtCommand">
          📋 点击一键复制指令
        </NButton>
      </div>
    </NCard>

    <!-- 任务多窗口列表栅格 (每个任务一个宽敞大盘) -->
    <div class="grid grid-cols-1 gap-16px lg:grid-cols-3">
      <div
        v-for="task in tasks"
        :key="task.id"
        class="flex flex-col justify-between rounded-12px bg-white p-16px shadow-sm transition-all border dark:bg-dark-700"
        :class="task.enabled ? 'border-gray-200 hover:border-primary/50 dark:border-dark-500' : 'border-dashed border-gray-300 opacity-75 dark:border-dark-600'"
      >
        <div>
          <!-- 窗口头部：任务标识、开关、周期选择 -->
          <div class="flex items-start justify-between gap-8px border-b border-gray-100 pb-10px dark:border-dark-600">
            <div>
              <div class="flex items-center gap-6px">
                <span class="rounded bg-primary/10 px-6px py-1px font-mono text-11px font-bold text-primary">
                  {{ task.id.toUpperCase() }}
                </span>
                <span class="text-15px font-bold text-gray-800 dark:text-gray-100">
                  {{ task.name }}
                </span>
              </div>
              <p class="mt-4px text-12px text-gray-400 line-clamp-2" :title="task.description">
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
          <div class="mt-10px flex items-center justify-between gap-8px rounded-6px bg-gray-50/80 px-8px py-6px text-12px dark:bg-dark-600">
            <span class="text-gray-500">运行频次：</span>
            <NSelect
              :value="task.interval_mins"
              :options="intervalOptions"
              size="tiny"
              class="w-140px"
              @update:value="mins => handleIntervalChange(task, mins)"
            />
          </div>

          <!-- 核心指标摘要 -->
          <div class="mt-10px grid grid-cols-2 gap-8px text-12px">
            <div class="rounded-6px bg-slate-50 p-6px dark:bg-dark-600/50">
              <div class="text-11px text-gray-400">待处理积压</div>
              <div class="mt-2px font-mono text-14px font-bold" :class="task.pending_count > 0 ? 'text-rose-500' : 'text-emerald-500'">
                {{ task.pending_count }} 笔
              </div>
            </div>
            <div class="rounded-6px bg-slate-50 p-6px dark:bg-dark-600/50">
              <div class="text-11px text-gray-400">上次耗时</div>
              <div class="mt-2px font-mono text-14px font-bold text-gray-700 dark:text-gray-300">
                {{ task.last_cost_ms }} ms
              </div>
            </div>
          </div>

          <!-- 内置终端日志视窗 (每个窗口都能直接看日志) -->
          <div class="mt-10px flex flex-col gap-4px">
            <div class="flex items-center justify-between text-11px text-gray-400 font-mono">
              <span>终端输出 (Latest Log):</span>
              <span class="text-10px">{{ task.last_run_time || '未运行' }}</span>
            </div>
            <div class="h-100px overflow-y-auto rounded-6px bg-slate-950 p-8px font-mono text-11px text-emerald-400 border border-slate-800 leading-relaxed select-all">
              <pre class="whitespace-pre-wrap">{{ task.latest_log }}</pre>
            </div>
          </div>
        </div>

        <!-- 窗口底部按钮条 -->
        <div class="mt-12px flex items-center justify-between gap-8px border-t border-gray-100 pt-10px dark:border-dark-600">
          <NButton size="tiny" secondary @click="openLogs(task)">
            📜 历史日志
          </NButton>

          <NButton
            size="tiny"
            type="primary"
            :loading="runningTaskMap[task.id]"
            :disabled="!task.enabled"
            @click="handleRunSingle(task)"
          >
            ▶ 立即运行
          </NButton>
        </div>
      </div>
    </div>

    <!-- 历史日志查看抽屉 -->
    <NDrawer v-model:show="logDrawerVisible" :width="appStore.isMobile ? '100%' : 560" placement="right">
      <NDrawerContent :title="`调度日志流 - ${activeTask?.name || ''} [${activeTask?.id?.toUpperCase() || ''}]`" closable>
        <div class="mb-12px flex items-center justify-between border-b pb-8px">
          <span class="text-12px text-gray-500">最近 50 次执行记录</span>
          <NPopconfirm @positive-click="handleClearLogs(activeTask?.id)">
            <template #trigger>
              <NButton size="tiny" type="warning" secondary>清空该任务日志</NButton>
            </template>
            确定清空该任务的全部历史调度日志吗？
          </NPopconfirm>
        </div>

        <NSpin :show="logListLoading">
          <div v-if="!historyLogs.length" class="py-40px text-center">
            <NEmpty description="暂无历史执行日志记录" />
          </div>
          <div v-else class="flex flex-col gap-10px">
            <div
              v-for="log in historyLogs"
              :key="log.id"
              class="rounded-8px border border-slate-800 bg-slate-950 p-10px font-mono text-11px text-slate-200"
            >
              <div class="mb-6px flex items-center justify-between border-b border-slate-800 pb-4px text-10px text-slate-500">
                <span class="text-emerald-400 font-bold">#{{ log.id }} | 耗时: {{ log.cost_ms }}ms</span>
                <span>{{ log.created_at }}</span>
              </div>
              <pre class="whitespace-pre-wrap text-emerald-300">{{ log.content }}</pre>
            </div>
          </div>
        </NSpin>
      </NDrawerContent>
    </NDrawer>
  </div>
</template>
