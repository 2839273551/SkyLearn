<script setup lang="ts">
import { h, onMounted, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import type { DataTableColumns } from 'naive-ui';
import { NAvatar, NButton, NPopconfirm, NSpace, NTag, NTooltip } from 'naive-ui';
import {
  createWorkorder,
  deleteWorkorder,
  fetchWorkorderList,
  finishWorkorder,
  replyWorkorder
} from '@/service/api';

defineOptions({ name: 'Workorder' });

const loading = ref(false);
const submitting = ref(false);
const list = ref<Api.ProfileArea.WorkorderItem[]>([]);
const total = ref(0);
const isSuper = ref(false);

const query = reactive({
  page: 1,
  pageSize: 15,
  keyword: '',
  status: ''
});

const stats = reactive({
  pending: 0,
  answered: 0,
  finished: 0
});

// 详情弹窗 / 对话抽屉
const detailModal = ref(false);
const activeTicket = ref<Api.ProfileArea.WorkorderItem | null>(null);
const quickReplyText = ref('');

// 新建工单弹窗
const createModal = ref(false);
const createForm = reactive({
  type: 'order' as 'order' | 'custom',
  oid: undefined as number | undefined,
  content: ''
});

// 管理员回复 / 结单弹窗
const replyModal = ref(false);
const replyTargetGid = ref(0);
const replyContent = ref('');
const isFinishAction = ref(false);

const statusOptions = [
  { label: '全部状态', value: '' },
  { label: '待回复', value: '待回复' },
  { label: '已回复', value: '已回复' },
  { label: '已完成', value: '已完成' }
];

const columns: DataTableColumns<Api.ProfileArea.WorkorderItem> = [
  { title: '工单 ID', key: 'gid', width: 85, fixed: 'left' },
  {
    title: '工单状态',
    key: 'state',
    width: 100,
    render: row => {
      const type = row.state === '待回复' ? 'warning' : row.state === '已回复' ? 'info' : 'success';
      return h(NTag, { type, size: 'small', round: true }, { default: () => row.state || '待处理' });
    }
  },
  {
    title: '关联来源 / 订单',
    key: 'region',
    width: 120,
    render: row => {
      const isOid = /^\d+$/.test(row.region);
      if (isOid) {
        return h(NTag, { type: 'primary', size: 'small', quaternary: true }, { default: () => `订单 #${row.region}` });
      }
      return h(NTag, { size: 'small', quaternary: true }, { default: () => row.region || '其它问题' });
    }
  },
  {
    title: '提交用户',
    key: 'userName',
    width: 150,
    render: row => {
      const digits = (row.userName || '').replace(/\D/g, '');
      const avatarSrc = (digits.length >= 5 && digits.length <= 11)
        ? `https://q1.qlogo.cn/g?b=qq&nk=${digits}&s=100`
        : 'https://q1.qlogo.cn/g?b=qq&nk=10001&s=100';
      return h('div', { class: 'flex items-center gap-6px' }, [
        h(NAvatar, { round: true, size: 24, src: avatarSrc }),
        h('span', { class: 'text-13px font-medium' }, row.displayName || row.userName || `UID:${row.uid}`)
      ]);
    }
  },
  {
    title: '工单主题与商品信息',
    key: 'title',
    minWidth: 220,
    ellipsis: { tooltip: true }
  },
  {
    title: '最新问答内容摘要',
    key: 'content',
    minWidth: 260,
    ellipsis: { tooltip: true }
  },
  { title: '提交时间', key: 'addtime', width: 170 },
  {
    title: '操作',
    key: 'actions',
    width: 220,
    fixed: 'right',
    render: row => {
      const btns = [
        h(
          NButton,
          {
            size: 'small',
            type: 'primary',
            ghost: true,
            onClick: () => openDetail(row)
          },
          { default: () => '查看详情' }
        )
      ];

      // 管理员支持回复与结单
      if (isSuper.value) {
        if (row.state !== '已完成') {
          btns.push(
            h(
              NButton,
              {
                size: 'small',
                type: 'info',
                ghost: true,
                onClick: () => openReplyModal(Number(row.gid), false)
              },
              { default: () => '回复' }
            ),
            h(
              NButton,
              {
                size: 'small',
                type: 'success',
                ghost: true,
                onClick: () => openReplyModal(Number(row.gid), true)
              },
              { default: () => '结单' }
            )
          );
        }
      } else {
        // 用户支持追加反馈
        if (row.state !== '已完成') {
          btns.push(
            h(
              NButton,
              {
                size: 'small',
                type: 'warning',
                ghost: true,
                onClick: () => openReplyModal(Number(row.gid), false)
              },
              { default: () => '追加反馈' }
            )
          );
        }
      }

      // 删除按钮
      btns.push(
        h(
          NPopconfirm,
          {
            onPositiveClick: () => handleDelete(Number(row.gid))
          },
          {
            trigger: () =>
              h(
                NButton,
                { size: 'small', type: 'error', ghost: true },
                { default: () => '删除' }
              ),
            default: () => '确定删除此工单吗？'
          }
        )
      );

      return h(NSpace, { size: 'small' }, () => btns);
    }
  }
];

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchWorkorderList({
    page: query.page,
    pageSize: query.pageSize,
    keyword: query.keyword.trim() || undefined,
    status: query.status || undefined
  });
  loading.value = false;

  if (!error && data) {
    list.value = data.records;
    total.value = data.total;
    isSuper.value = Boolean(data.isSuper);
    Object.assign(stats, data.stats);
  }
}

function handleSearch() {
  query.page = 1;
  loadData();
}

function openDetail(row: Api.ProfileArea.WorkorderItem) {
  activeTicket.value = row;
  quickReplyText.value = '';
  detailModal.value = true;
}

function openReplyModal(gid: number, finish: boolean) {
  replyTargetGid.value = gid;
  isFinishAction.value = finish;
  replyContent.value = finish ? '核查无误，处理完成，特此结单' : '';
  replyModal.value = true;
}

async function handleConfirmReply() {
  const content = replyContent.value.trim();
  if (!content) {
    window.$message?.warning('请输入回复说明内容');
    return;
  }
  submitting.value = true;
  if (isFinishAction.value) {
    const { error } = await finishWorkorder({ gid: replyTargetGid.value, remark: content });
    if (!error) {
      window.$message?.success('工单已成功结单！');
      replyModal.value = false;
      detailModal.value = false;
      loadData();
    }
  } else {
    const { error } = await replyWorkorder({ gid: replyTargetGid.value, reply: content });
    if (!error) {
      window.$message?.success(isSuper.value ? '已成功回复工单！' : '已成功追加反馈！');
      replyModal.value = false;
      detailModal.value = false;
      loadData();
    }
  }
  submitting.value = false;
}

async function handleQuickReply() {
  if (!activeTicket.value) return;
  const text = quickReplyText.value.trim();
  if (!text) {
    window.$message?.warning('请输入要发送的内容');
    return;
  }
  submitting.value = true;
  const { error } = await replyWorkorder({
    gid: Number(activeTicket.value.gid),
    reply: text
  });
  submitting.value = false;
  if (!error) {
    window.$message?.success('发送成功');
    quickReplyText.value = '';
    detailModal.value = false;
    loadData();
  }
}

async function handleCreateTicket() {
  if (!createForm.content.trim()) {
    window.$message?.warning('请输入问题描述');
    return;
  }
  if (createForm.type === 'order' && !createForm.oid) {
    window.$message?.warning('请填写关联的订单 ID');
    return;
  }

  submitting.value = true;
  const { error } = await createWorkorder({
    type: createForm.type,
    oid: createForm.oid,
    content: createForm.content.trim()
  });
  submitting.value = false;

  if (!error) {
    window.$message?.success('工单创建成功，站长将尽快核验处理！');
    createModal.value = false;
    createForm.content = '';
    createForm.oid = undefined;
    loadData();
  }
}

async function handleDelete(gid: number) {
  const { error } = await deleteWorkorder(gid);
  if (!error) {
    window.$message?.success('工单已删除');
    loadData();
  }
}

const route = useRoute();
onMounted(() => {
  loadData();
  if (route.query.oid) {
    createForm.type = 'order';
    createForm.oid = Number(route.query.oid);
    createModal.value = true;
  }
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <!-- 顶部状态与统计面板 -->
    <NGrid cols="2 s:4" responsive="screen" :x-gap="16" :y-gap="16">
      <NGi>
        <NCard embedded :bordered="false" class="rounded-8px">
          <NStatistic label="待回复工单" :value="stats.pending">
            <template #prefix>
              <SvgIcon icon="ph:hourglass-medium" class="mr-6px text-22px text-warning" />
            </template>
            <template #suffix>条</template>
          </NStatistic>
        </NCard>
      </NGi>
      <NGi>
        <NCard embedded :bordered="false" class="rounded-8px">
          <NStatistic label="已回复工单" :value="stats.answered">
            <template #prefix>
              <SvgIcon icon="ph:chat-circle-dots" class="mr-6px text-22px text-info" />
            </template>
            <template #suffix>条</template>
          </NStatistic>
        </NCard>
      </NGi>
      <NGi>
        <NCard embedded :bordered="false" class="rounded-8px">
          <NStatistic label="已结单完成" :value="stats.finished">
            <template #prefix>
              <SvgIcon icon="ph:check-circle" class="mr-6px text-22px text-success" />
            </template>
            <template #suffix>条</template>
          </NStatistic>
        </NCard>
      </NGi>
      <NGi>
        <NCard embedded :bordered="false" class="rounded-8px">
          <NStatistic label="工单总数" :value="total">
            <template #prefix>
              <SvgIcon icon="ph:chats-circle" class="mr-6px text-22px text-primary" />
            </template>
            <template #suffix>条</template>
          </NStatistic>
        </NCard>
      </NGi>
    </NGrid>

    <!-- 主表格卡片 -->
    <NCard title="工单与售后服务中心" :bordered="false" class="rounded-8px shadow-sm">
      <div class="mb-16px flex flex-wrap items-center justify-between gap-12px">
        <div class="flex flex-wrap items-center gap-10px">
          <NSelect
            v-model:value="query.status"
            :options="statusOptions"
            placeholder="筛选状态"
            clearable
            class="w-130px"
          />
          <NInput
            v-model:value="query.keyword"
            placeholder="搜索订单 ID / 问题内容 / 关键词"
            clearable
            class="w-260px"
            @keyup.enter="handleSearch"
          />
          <NButton type="primary" @click="handleSearch">查询</NButton>
          <NButton :loading="loading" @click="loadData">刷新</NButton>
        </div>

        <NButton type="primary" secondary @click="createModal = true">
          <template #icon><SvgIcon icon="ph:plus" /></template>
          新建售后工单
        </NButton>
      </div>

      <NDataTable
        :loading="loading"
        :columns="columns"
        :data="list"
        :row-key="(row: Api.ProfileArea.WorkorderItem) => row.gid"
        :pagination="false"
        striped
        :scroll-x="1200"
      />

      <div class="mt-16px flex justify-end">
        <NPagination
          v-model:page="query.page"
          v-model:page-size="query.pageSize"
          :item-count="total"
          :page-sizes="[15, 30, 50]"
          show-size-picker
          show-quick-jumper
          @update:page="loadData"
          @update:page-size="loadData"
        />
      </div>
    </NCard>

    <!-- 弹窗1：新建售后工单 -->
    <NModal v-model:show="createModal" preset="card" title="提交售后工单" class="max-w-520px">
      <div class="flex flex-col gap-14px">
        <NAlert type="info">
          订单未走进度、课程锁死或退款异常，请提交工单并填写准确订单 ID，站长将在 24 小时内完成核查与回复。
        </NAlert>

        <div>
          <label class="mb-6px block text-13px font-medium">工单类型：</label>
          <NRadioGroup v-model:value="createForm.type">
            <NSpace>
              <NRadio value="order">已有订单售后处理</NRadio>
              <NRadio value="custom">平台其他问题咨询</NRadio>
            </NSpace>
          </NRadioGroup>
        </div>

        <div v-if="createForm.type === 'order'">
          <label class="mb-6px block text-13px font-medium">关联订单号 (OID)：</label>
          <NInputNumber
            v-model:value="createForm.oid"
            placeholder="请输入订单列表中的订单 ID (数字)"
            :show-button="false"
            class="w-full"
          />
        </div>

        <div>
          <label class="mb-6px block text-13px font-medium">问题详细说明：</label>
          <NInput
            v-model:value="createForm.content"
            type="textarea"
            :rows="5"
            placeholder="请详细描述您遇到的问题（如：订单号已过48小时无进度、密码修改后如何补跑等）"
            maxlength="500"
            show-count
          />
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-10px">
          <NButton @click="createModal = false">取消</NButton>
          <NButton type="primary" :loading="submitting" @click="handleCreateTicket">确认提交工单</NButton>
        </div>
      </template>
    </NModal>

    <!-- 弹窗2：工单对话历史详情 -->
    <NModal
      v-model:show="detailModal"
      preset="card"
      :title="`工单详情 [#${activeTicket?.gid || ''}] ${activeTicket?.state || ''}`"
      class="max-w-680px"
    >
      <div v-if="activeTicket" class="flex flex-col gap-16px">
        <!-- 头部摘要 -->
        <div class="rounded-8px border border-gray-100 bg-gray-50 p-12px text-13px dark:border-dark-400 dark:bg-dark-600">
          <div class="flex justify-between py-2px">
            <span class="text-gray-500">关联来源：</span>
            <span class="font-bold text-primary">{{ activeTicket.region }}</span>
          </div>
          <div class="flex justify-between py-2px">
            <span class="text-gray-500">提交用户：</span>
            <span>{{ activeTicket.displayName || activeTicket.userName }} (UID: {{ activeTicket.uid }})</span>
          </div>
          <div class="flex justify-between py-2px">
            <span class="text-gray-500">创建时间：</span>
            <span class="font-mono text-gray-400">{{ activeTicket.addtime }}</span>
          </div>
          <div class="mt-4px border-t border-gray-200 pt-6px text-gray-600 dark:border-dark-400 dark:text-gray-300">
            <strong>工单主题：</strong>{{ activeTicket.title }}
          </div>
        </div>

        <!-- 历史对话记录 -->
        <div>
          <label class="mb-8px block font-bold text-14px">完整处理与对话记录：</label>
          <div class="max-h-340px overflow-y-auto whitespace-pre-wrap rounded-8px border border-gray-200 bg-white p-14px font-mono text-13px leading-relaxed dark:border-dark-400 dark:bg-dark-700">
            {{ activeTicket.content }}
          </div>
        </div>

        <!-- 快速回复输入框 (未完成状态可用) -->
        <div v-if="activeTicket.state !== '已完成'" class="flex flex-col gap-8px">
          <label class="text-13px font-medium text-gray-700 dark:text-gray-300">
            {{ isSuper ? '管理员直接回复：' : '追加补充反馈：' }}
          </label>
          <div class="flex gap-8px">
            <NInput
              v-model:value="quickReplyText"
              type="textarea"
              :rows="2"
              :placeholder="isSuper ? '输入回复内容...' : '继续向站长补充问题...'"
              class="flex-1"
            />
            <NButton type="primary" :loading="submitting" @click="handleQuickReply">
              发送
            </NButton>
          </div>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-10px">
          <NButton @click="detailModal = false">关闭窗口</NButton>
        </div>
      </template>
    </NModal>

    <!-- 弹窗3：管理员回复 / 结单弹窗 -->
    <NModal
      v-model:show="replyModal"
      preset="card"
      :title="isFinishAction ? '管理员结单确认' : (isSuper ? '管理员回复工单' : '追加提问')"
      class="max-w-480px"
    >
      <div class="flex flex-col gap-12px">
        <label class="text-13px text-gray-600 dark:text-gray-300">
          {{ isFinishAction ? '结单说明与处理结论：' : '请输入要回复的内容：' }}
        </label>
        <NInput
          v-model:value="replyContent"
          type="textarea"
          :rows="4"
          :placeholder="isFinishAction ? '输入结单结论...' : '输入回复内容...'"
        />
      </div>
      <template #footer>
        <div class="flex justify-end gap-10px">
          <NButton @click="replyModal = false">取消</NButton>
          <NButton type="primary" :loading="submitting" @click="handleConfirmReply">
            {{ isFinishAction ? '确认结单' : '确认回复' }}
          </NButton>
        </div>
      </template>
    </NModal>
  </div>
</template>

<style scoped></style>
