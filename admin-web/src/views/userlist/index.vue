<script setup lang="ts">
import { computed, h, onMounted, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import type { DataTableColumns } from 'naive-ui';
import {
  NAlert,
  NAvatar,
  NButton,
  NCard,
  NDataTable,
  NEmpty,
  NFormItem,
  NInput,
  NInputNumber,
  NModal,
  NPagination,
  NPopconfirm,
  NRadioGroup,
  NRadioButton,
  NSelect,
  NSpace,
  NSpin,
  NSwitch,
  NTag
} from 'naive-ui';
import { useAppStore } from '@/store/modules/app';
import { useAuthStore } from '@/store/modules/auth';
import {
  adjustUserBalance,
  createUser,
  deleteUser,
  fetchGradeOptions,
  fetchUserlistList,
  loginAsUser,
  rechargeUserBalance,
  resetUserPassword,
  toggleUserKey,
  updateUserRate,
  updateUserStatus
} from '@/service/api';

defineOptions({ name: 'Userlist' });

const appStore = useAppStore();
const authStore = useAuthStore();
const isSuperAdmin = computed(() => authStore.userInfo.roles.includes('R_SUPER'));
const route = useRoute();

const loading = ref(false);
const list = ref<Api.ProfileArea.UserItem[]>([]);
const total = ref(0);
const deleteModal = ref(false);
const deleteLoading = ref(false);
const deleteTarget = ref<Api.ProfileArea.UserItem | null>(null);

const query = reactive({
  page: 1,
  pageSize: 20,
  keyword: '',
  status: '' as '' | '1' | '0'
});


const rechargeModal = ref(false);
const rateModal = ref(false);
const currentUid = ref('');
const currentUserName = ref('');
const rechargeAmount = ref(100);
const targetRate = ref('0.30');
const pwdModal = ref(false);
const pwdLoading = ref(false);
const resetPwdForm = reactive({
  password: '12345678'
});

function openResetPwdModal(row: Api.ProfileArea.UserItem) {
  currentUid.value = row.uid;
  currentUserName.value = row.name || row.user;
  resetPwdForm.password = '12345678';
  pwdModal.value = true;
}

function openRateModal(row: Api.ProfileArea.UserItem) {
  currentUid.value = row.uid;
  currentUserName.value = row.name || row.user;
  targetRate.value = row.addprice;
  rateModal.value = true;
}

function openDeleteModal(row: Api.ProfileArea.UserItem) {
  deleteTarget.value = row;
  deleteModal.value = true;
}

async function handleDeleteUser() {
  if (!deleteTarget.value || deleteLoading.value) return;
  deleteLoading.value = true;
  try {
    const { error } = await deleteUser(deleteTarget.value.uid);
    if (error) return;
    window.$message?.success('代理已删除');
    deleteModal.value = false;
    if (list.value.length === 1 && query.page > 1) query.page -= 1;
    await loadData();
  } finally {
    deleteLoading.value = false;
  }
}

function getAvatarSrc(user: string) {
  const digits = (user || '').replace(/\D/g, '');
  const qq = digits.length >= 5 && digits.length <= 11 ? digits : '10001';
  return `https://q1.qlogo.cn/g?b=qq&nk=${qq}&s=100`;
}

async function handleUpdateStatus(row: Api.ProfileArea.UserItem, enabled: boolean) {
  const res = await updateUserStatus(row.uid, enabled ? 1 : 0);
  if (res !== null) {
    row.active = enabled ? 1 : 0;
    window.$message?.success(enabled ? '已解封' : '已封禁');
  }
}

async function handleResetPassword() {
  if (!resetPwdForm.password.trim()) {
    window.$message?.warning('请输入新密码');
    return;
  }
  pwdLoading.value = true;
  const res = await resetUserPassword(currentUid.value, resetPwdForm.password.trim());
  pwdLoading.value = false;
  if (res !== null) {
    window.$message?.success(`已成功重置代理 [${currentUserName.value}] 的登录密码！`);
    pwdModal.value = false;
  }
}

// 余额调账相关 (加钱 / 减钱)
const balanceModal = ref(false);
const balanceLoading = ref(false);
const currentTargetUser = ref<Api.ProfileArea.UserItem | null>(null);
const balanceForm = reactive({
  type: 'add' as 'add' | 'deduct',
  amount: 50,
  remark: ''
});

function openBalanceModal(row: Api.ProfileArea.UserItem) {
  currentTargetUser.value = row;
  currentUid.value = row.uid;
  currentUserName.value = row.name || row.user;
  balanceForm.type = 'add';
  balanceForm.amount = 50;
  balanceForm.remark = '';
  balanceModal.value = true;
}

async function handleAdjustBalance() {
  if (!balanceForm.amount || balanceForm.amount <= 0) {
    window.$message?.warning('请输入有效变动金额');
    return;
  }
  balanceLoading.value = true;
  const res = await adjustUserBalance(currentUid.value, balanceForm.amount, balanceForm.type, balanceForm.remark.trim());
  balanceLoading.value = false;
  if (res !== null) {
    window.$message?.success(balanceForm.type === 'add' ? '充值加款成功' : '扣减余额成功');
    balanceModal.value = false;
    loadData();
  }
}

// 对接密钥操作
async function handleToggleKey(row: Api.ProfileArea.UserItem, op: 'open' | 'close' | 'reset') {
  const opName = op === 'close' ? '关闭' : (op === 'reset' ? '重置' : '开通');
  const res = await toggleUserKey(row.uid, op);
  if (res !== null && res.data) {
    row.key = res.data.key || '';
    window.$message?.success(`已成功${opName}代理 [${row.name || row.user}] 的对接密钥！`);
  }
}

function copyKey(keyText: string) {
  if (!keyText) return;
  navigator.clipboard.writeText(keyText);
  window.$message?.success('已复制对接密钥到剪贴板！');
}

// 一键登录该代理后台
function handleLoginAs(row: Api.ProfileArea.UserItem) {
  window.$dialog?.warning({
    title: '一键免密登录确认',
    content: `确定以代理 [${row.name || row.user}]（UID: ${row.uid}）的身份进入管理后台吗？您将免密切换为该商户的视角与权限。`,
    positiveText: '确认登录',
    negativeText: '取消',
    onPositiveClick: async () => {
      loading.value = true;
      const res = await loginAsUser(row.uid);
      if (res !== null && res.data) {
        window.$message?.success(`已成功一键切换至 [${res.data.targetName}] 后台！正在前往工作台...`);
        setTimeout(() => {
          window.location.href = '/index/main';
        }, 600);
      } else {
        loading.value = false;
      }
    }
  });
}


// 开户相关
const createModal = ref(false);
const createLoading = ref(false);
const gradeList = ref<Api.Adduser.GradeItem[]>([]);
const openReg = ref('1');
const ktMoney = ref(0);
const currentUserRate = ref(1);

const createForm = reactive({
  user: '',
  pass: '',
  name: '',
  grade_id: null as string | null
});

async function openCreateModal() {
  const { data, error } = await fetchGradeOptions();
  if (!error && data) {
    gradeList.value = data.grades;
    openReg.value = data.user_htkh;
    ktMoney.value = data.user_ktmoney;
    currentUserRate.value = data.current_user_rate;
    if (data.grades.length > 0 && !createForm.grade_id) {
      const firstValid = data.grades.find(g => !g.disabled);
      if (firstValid) createForm.grade_id = firstValid.id;
    }
  }
  createModal.value = true;
}

const selectedGrade = computed(() => {
  if (!createForm.grade_id) return null;
  return gradeList.value.find(g => g.id === createForm.grade_id) || null;
});

const calculatedNeed = computed(() => {
  let fee = ktMoney.value;
  if (selectedGrade.value && selectedGrade.value.addkf === 1 && selectedGrade.value.rate > 0) {
    const rechargeCost = Number((selectedGrade.value.money * (currentUserRate.value / selectedGrade.value.rate)).toFixed(2));
    fee += rechargeCost;
  }
  return Number(fee.toFixed(2));
});

async function handleCreateUser() {
  if (!createForm.user.trim()) {
    window.$message?.warning('请输入代理 QQ 账号');
    return;
  }
  if (!createForm.pass.trim()) {
    window.$message?.warning('请输入代理登录密码');
    return;
  }
  if (!createForm.name.trim()) {
    window.$message?.warning('请输入代理昵称');
    return;
  }
  if (!createForm.grade_id) {
    window.$message?.warning('请选择代理等级');
    return;
  }

  createLoading.value = true;
  const { data, error } = await createUser({
    user: createForm.user.trim(),
    pass: createForm.pass.trim(),
    name: createForm.name.trim(),
    grade_id: Number(createForm.grade_id)
  });
  createLoading.value = false;

  if (!error && data) {
    window.$message?.success(`代理开通成功！账号: ${data.user} (UID: ${data.uid})`);
    createModal.value = false;
    createForm.user = '';
    createForm.pass = '';
    createForm.name = '';
    loadData();
  }
}


const columns: DataTableColumns<Api.ProfileArea.UserItem> = [
  { title: 'UID', key: 'uid', width: 70, fixed: 'left' },
  { title: '上级', key: 'uuid', width: 70 },
  {
    title: '账号',
    key: 'user',
    width: 170,
    render: row => {
      return h('div', { class: 'flex items-center gap-8px' }, [
        h(NAvatar, { round: true, size: 26, src: getAvatarSrc(row.user), fallbackSrc: getAvatarSrc('10001') }),
        h('span', { class: 'font-mono' }, row.user)
      ]);
    }
  },
  { title: '昵称', key: 'name', minWidth: 120 },
  {
    title: '成本费率',
    key: 'addprice',
    width: 100,
    render: row => h('span', { class: 'font-bold text-primary' }, `${row.addprice}×`)
  },
  {
    title: '当前余额',
    key: 'money',
    width: 110,
    render: row => h('span', { class: 'font-bold text-success' }, `¥ ${row.money}`)
  },
  { title: '累计充值', key: 'zcz', width: 100, render: row => `¥ ${row.zcz}` },
  {
    title: '状态',
    key: 'active',
    width: 100,
    render: row =>
      h(
        NSwitch,
        {
          value: row.active === 1,
          onUpdateValue: (val: boolean) => handleUpdateStatus(row, val)
        },
        { checked: () => '正常', unchecked: () => '封禁' }
      )
  },
  { title: '邀请码', key: 'yqm', width: 95 },
  {
    title: '对接密钥',
    key: 'key',
    width: 175,
    render: row => {
      if (row.key) {
        return h('div', { class: 'flex items-center gap-4px' }, [
          h(NTag, { type: 'success', size: 'tiny', round: true }, { default: () => '已开启' }),
          h(
            NButton,
            { size: 'tiny', tertiary: true, type: 'primary', onClick: () => copyKey(row.key) },
            { default: () => '复制' }
          ),
          h(
            NPopconfirm,
            {
              onPositiveClick: () => handleToggleKey(row, 'close')
            },
            {
              trigger: () =>
                h(
                  NButton,
                  { size: 'tiny', tertiary: true, type: 'error' },
                  { default: () => '关闭' }
                ),
              default: () => `确定关闭代理 [${row.name || row.user}] 的对接密钥吗？关闭后将无法对外对接交单。`
            }
          ),
          h(
            NPopconfirm,
            {
              onPositiveClick: () => handleToggleKey(row, 'reset')
            },
            {
              trigger: () =>
                h(
                  NButton,
                  { size: 'tiny', quaternary: true, title: '重新生成' },
                  { default: () => '🔄' }
                ),
              default: () => `确定重新生成代理 [${row.name || row.user}] 的对接密钥吗？`
            }
          )
        ]);
      }
      return h('div', { class: 'flex items-center gap-6px' }, [
        h(NTag, { type: 'default', size: 'tiny', round: true }, { default: () => '未开启' }),
        h(
          NButton,
          { size: 'tiny', type: 'success', dashed: true, onClick: () => handleToggleKey(row, 'open') },
          { default: () => '一键开通' }
        )
      ]);
    }
  },
  { title: '注册时间', key: 'addtime', width: 160 },
  {
    title: '操作',
    key: 'actions',
    width: 360,
    fixed: 'right',
    render: row =>
      h(NSpace, { size: 6, align: 'center' }, () => [
        h(
          NButton,
          {
            size: 'tiny',
            type: 'success',
            secondary: true,
            onClick: () => handleLoginAs(row)
          },
          { default: () => '🚀 登录后台' }
        ),
        h(
          NButton,
          {
            size: 'tiny',
            type: 'primary',
            secondary: true,
            onClick: () => openBalanceModal(row)
          },
          { default: () => '💰 调账' }
        ),
        h(
          NButton,
          {
            size: 'tiny',
            type: 'info',
            secondary: true,
            onClick: () => openRateModal(row)
          },
          { default: () => '⚙️ 调费率' }
        ),
        h(
          NButton,
          {
            size: 'tiny',
            type: 'warning',
            secondary: true,
            onClick: () => openResetPwdModal(row)
          },
          { default: () => '🔑 重置密码' }
        ),
        h(
          NButton,
          {
            size: 'tiny',
            type: 'error',
            secondary: true,
            onClick: () => openDeleteModal(row)
          },
          { default: () => '删除代理' }
        )
      ])
  }
];

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchUserlistList({
    page: query.page,
    pageSize: query.pageSize,
    keyword: query.keyword.trim() || undefined,
    status: query.status !== '' ? query.status : undefined
  });
  loading.value = false;

  if (!error && data) {
    list.value = data.records;
    total.value = data.total;
  }
}

async function handleRecharge() {
  if (!rechargeAmount.value) {
    window.$message?.warning('请输入有效充值金额');
    return;
  }
  const res = await rechargeUserBalance(currentUid.value, rechargeAmount.value);
  if (res !== null) {
    window.$message?.success('充值成功');
    rechargeModal.value = false;
    loadData();
  }
}

async function handleUpdateRate() {
  if (!targetRate.value.trim()) {
    window.$message?.warning('请输入有效费率');
    return;
  }
  const res = await updateUserRate(currentUid.value, targetRate.value.trim());
  if (res !== null) {
    window.$message?.success('费率修改成功');
    rateModal.value = false;
    loadData();
  }
}

onMounted(() => {
  loadData();
  if (route.query.openAdd === '1') {
    openCreateModal();
  }
});
</script>

<template>
  <div class="userlist-page flex flex-col gap-14px p-10px sm:p-16px">
    <NCard :title="isSuperAdmin ? '代理管理 (全站商户)' : '代理管理 (我的下级代理)'" :bordered="false" class="userlist-panel rounded-8px shadow-sm">
      <p v-if="!isSuperAdmin" class="text-12px text-gray-500 mb-12px bg-amber-50 dark:bg-dark-500 p-8px rounded-6px border border-amber-200 dark:border-dark-400">
        💡 提示：您当前查看的是直属于您名下的下级代理团队。您可点击“➕ 添加代理”开通直属下级，也可为直属下级充值或调整费率。
      </p>
      <div class="userlist-toolbar">
        <div class="userlist-filters">
          <NInput v-model:value="query.keyword" :input-props="{ 'aria-label': '搜索代理' }" placeholder="UID / 账号 / 昵称 / 邀请码" clearable class="userlist-search" @keyup.enter="loadData" />
          <NSelect
            v-model:value="query.status"
            :options="[
              { label: '全部状态', value: '' },
              { label: '正常', value: '1' },
              { label: '已封禁', value: '0' }
            ]"
            placeholder="账号状态"
            aria-label="账号状态"
            clearable
          />
          <NButton type="primary" @click="loadData">查询</NButton>
        </div>
        <div class="userlist-toolbar-actions">
          <NButton type="primary" @click="openCreateModal">➕ 添加代理</NButton>
          <NButton :loading="loading" @click="loadData">刷新</NButton>
        </div>
      </div>

      <template v-if="appStore.isMobile">
        <p class="mb-12px text-13px text-gray-500" aria-live="polite">共 {{ total }} 位代理</p>
        <NSpin :show="loading">
          <div v-if="list.length" class="agent-list">
            <article v-for="row in list" :key="row.uid" class="agent-card">
              <div class="agent-card-header">
                <NAvatar :size="36" round :src="getAvatarSrc(row.user)" :fallback-src="getAvatarSrc('10001')" />
                <div class="agent-identity">
                  <h2 class="m-0 text-15px font-semibold">{{ row.name || row.user }}</h2>
                  <div class="mt-2px text-13px text-gray-500 font-mono">{{ row.user }}</div>
                </div>
                <NSwitch
                  :value="row.active === 1"
                  :aria-label="`代理 ${row.user} 的账号状态`"
                  class="agent-status"
                  @update:value="handleUpdateStatus(row, $event)"
                >
                  <template #checked>正常</template>
                  <template #unchecked>封禁</template>
                </NSwitch>
              </div>
              <div class="agent-identifiers text-12px text-gray-500">
                <span>UID：{{ row.uid }}</span>
                <span>上级：{{ row.uuid }}</span>
              </div>
              <dl class="agent-metrics">
                <div class="agent-balance">
                  <dt>当前余额</dt>
                  <dd class="text-22px text-success font-semibold">¥ {{ row.money }}</dd>
                </div>
                <div>
                  <dt>成本费率</dt>
                  <dd class="text-primary font-semibold">{{ row.addprice }}×</dd>
                </div>
                <div>
                  <dt>累计充值</dt>
                  <dd>¥ {{ row.zcz }}</dd>
                </div>
              </dl>
              <dl class="agent-details">
                <div><dt>邀请码</dt><dd>{{ row.yqm || '—' }}</dd></div>
                <div><dt>注册时间</dt><dd>{{ row.addtime }}</dd></div>
              </dl>
              <div class="agent-key">
                <div class="flex flex-wrap items-center gap-8px">
                  <span class="text-13px text-gray-500">对接密钥</span>
                  <NTag :type="row.key ? 'success' : 'default'" size="small" round>{{ row.key ? '已开启' : '未开启' }}</NTag>
                </div>
                <div v-if="row.key" class="agent-key-actions">
                  <NButton secondary type="primary" @click="copyKey(row.key)">复制密钥</NButton>
                  <NPopconfirm
                    :style="{ maxWidth: 'calc(100vw - 32px)', overflowWrap: 'anywhere' }"
                    @positive-click="handleToggleKey(row, 'close')"
                  >
                    <template #trigger><NButton secondary type="error">关闭密钥</NButton></template>
                    确定关闭代理 [{{ row.name || row.user }}] 的对接密钥吗？关闭后将无法对外对接交单。
                  </NPopconfirm>
                  <NPopconfirm
                    :style="{ maxWidth: 'calc(100vw - 32px)', overflowWrap: 'anywhere' }"
                    @positive-click="handleToggleKey(row, 'reset')"
                  >
                    <template #trigger><NButton secondary>重置密钥</NButton></template>
                    确定重新生成代理 [{{ row.name || row.user }}] 的对接密钥吗？
                  </NPopconfirm>
                </div>
                <NButton v-else type="success" dashed class="mt-8px w-full" @click="handleToggleKey(row, 'open')">开通对接密钥</NButton>
              </div>
              <div class="agent-actions">
                <NButton type="success" secondary @click="handleLoginAs(row)">登录后台</NButton>
                <NButton type="primary" secondary @click="openBalanceModal(row)">余额调账</NButton>
                <NButton type="info" secondary @click="openRateModal(row)">调整费率</NButton>
                <NButton type="warning" secondary @click="openResetPwdModal(row)">重置密码</NButton>
                <NButton type="error" secondary class="agent-delete" @click="openDeleteModal(row)">删除代理</NButton>
              </div>
            </article>
          </div>
          <NEmpty v-else description="暂无代理数据" class="py-40px" />
        </NSpin>
      </template>
      <NDataTable
        v-else
        :loading="loading"
        :columns="columns"
        :data="list"
        :row-key="(row: Api.ProfileArea.UserItem) => row.uid"
        :pagination="false"
        striped
        :scroll-x="1300"
      />

      <div class="userlist-pagination">
        <NPagination
          v-model:page="query.page"
          v-model:page-size="query.pageSize"
          :item-count="total"
          :page-sizes="[20, 50, 100]"
          :page-slot="9"
          :simple="appStore.isMobile"
          :show-size-picker="!appStore.isMobile"
          :show-quick-jumper="!appStore.isMobile"
          @update:page="loadData"
          @update:page-size="loadData"
        />
      </div>
    </NCard>

    <NModal
      v-model:show="deleteModal"
      preset="card"
      title="删除代理"
      class="userlist-modal"
      style="width: 460px"
      :mask-closable="!deleteLoading"
      :close-on-esc="!deleteLoading"
      :closable="!deleteLoading"
    >
      <div v-if="deleteTarget" class="flex flex-col gap-14px">
        <NAlert type="warning">
          删除后，账号将停用并从代理列表移除，对接密钥和邀请码同时失效。历史订单与资金流水保留。
        </NAlert>
        <div class="text-14px">
          <div>代理名称：<strong>{{ deleteTarget.name || deleteTarget.user }}</strong></div>
          <div class="mt-6px">账号：{{ deleteTarget.user }}（UID：{{ deleteTarget.uid }}）</div>
          <div class="mt-6px">当前余额：¥ {{ deleteTarget.money }}</div>
        </div>
        <p class="m-0 text-13px text-gray-500">请先结清余额、处理直属下级及未完成的充值支付，再确认删除。</p>
      </div>
      <template #footer>
        <div class="userlist-modal-actions">
          <NButton :disabled="deleteLoading" @click="deleteModal = false">取消</NButton>
          <NButton type="error" :loading="deleteLoading" @click="handleDeleteUser">确认删除代理</NButton>
        </div>
      </template>
    </NModal>

    <!-- 余额调整弹窗 (加钱 / 减钱) -->
    <NModal v-model:show="balanceModal" preset="card" title="代理账户余额调整" class="userlist-modal" style="width: 480px">
      <div class="flex flex-col gap-14px">
        <div v-if="currentTargetUser" class="balance-summary rounded-8px bg-slate-50 p-10px dark:bg-dark-600 border text-13px">
          <div>
            目标代理：<strong>{{ currentTargetUser.name || currentTargetUser.user }}</strong>
            <span class="text-gray-400 text-12px ml-4px">[UID: {{ currentTargetUser.uid }}]</span>
          </div>
          <div>
            当前可用：<strong class="text-emerald-600 font-mono font-bold text-15px">¥ {{ currentTargetUser.money }}</strong>
          </div>
        </div>

        <NFormItem label="调整类型">
          <NRadioGroup v-model:value="balanceForm.type" class="balance-type">
            <NRadioButton value="add">➕ 增加余额 (充值加款)</NRadioButton>
            <NRadioButton value="deduct">➖ 扣减余额 (手动扣款)</NRadioButton>
          </NRadioGroup>
        </NFormItem>

        <NFormItem :label="balanceForm.type === 'add' ? (isSuperAdmin ? '充值增加金额 (¥)' : '为下级充值金额 (从您账户划扣 ¥)') : (isSuperAdmin ? '扣减金额 (¥)' : '从下级扣除金额 (返还给您 ¥)')" required>
          <NInputNumber v-model:value="balanceForm.amount" :min="0.01" :step="10" class="w-full">
            <template #prefix>¥</template>
          </NInputNumber>
        </NFormItem>

        <NFormItem label="调整说明 / 资金流水备注">
          <NInput v-model:value="balanceForm.remark" placeholder="如：线下转账加款、售后核销扣减等（选填）" />
        </NFormItem>
      </div>
      <template #footer>
        <div class="userlist-modal-actions">
          <NButton @click="balanceModal = false">取消</NButton>
          <NButton
            :type="balanceForm.type === 'add' ? 'primary' : 'error'"
            :loading="balanceLoading"
            @click="handleAdjustBalance"
          >
            {{ balanceForm.type === 'add' ? '确认充值加款' : '确认扣减余额' }}
          </NButton>
        </div>
      </template>
    </NModal>

    <!-- 重置密码弹窗 -->
    <NModal v-model:show="pwdModal" preset="card" title="重置代理登录密码" class="userlist-modal" style="width: 440px">
      <div class="flex flex-col gap-14px">
        <NAlert type="info">正在为代理商户 <strong>{{ currentUserName }}</strong>（UID: {{ currentUid }}）重置登录密码。</NAlert>
        <NFormItem label="设置新登录密码 (默认 12345678)" required>
          <NInput v-model:value="resetPwdForm.password" placeholder="输入新密码，至少6位" />
        </NFormItem>
      </div>
      <template #footer>
        <div class="userlist-modal-actions">
          <NButton @click="pwdModal = false">取消</NButton>
          <NButton type="primary" :loading="pwdLoading" @click="handleResetPassword">确认重置密码</NButton>
        </div>
      </template>
    </NModal>

    <!-- 调费率弹窗 -->
    <NModal v-model:show="rateModal" preset="card" title="修改代理费率" class="userlist-modal" style="width: 460px">
      <div class="flex flex-col gap-12px">
        <div class="text-14px">目标代理：<strong>[UID: {{ currentUid }}] {{ currentUserName }}</strong></div>
        <NFormItem :label="isSuperAdmin ? '新费率系数 (如 0.25 代表 2.5 折成本)' : '下级成本费率 (不可低于您自身成本费率)'">
          <NInput v-model:value="targetRate" placeholder="输入费率系数，如 0.30" />
        </NFormItem>
      </div>
      <template #footer>
        <div class="userlist-modal-actions">
          <NButton @click="rateModal = false">取消</NButton>
          <NButton type="primary" @click="handleUpdateRate">确认修改</NButton>
        </div>
      </template>
    </NModal>

    <!-- 开通代理弹窗 -->
    <NModal v-model:show="createModal" preset="card" :title="isSuperAdmin ? '开通代理账号' : '开通直属下级代理'" class="userlist-modal" style="width: 520px">
      <div class="flex flex-col gap-14px">
        <NAlert v-if="openReg === '0'" type="error">当前系统设置已暂停后台开户</NAlert>
        <div class="grid grid-cols-1 gap-12px sm:grid-cols-2">
          <NFormItem label="代理账号 (QQ号码)" required>
            <NInput v-model:value="createForm.user" placeholder="输入 5~11 位数字 QQ" />
          </NFormItem>
          <NFormItem label="初始登录密码" required>
            <NInput v-model:value="createForm.pass" type="password" show-password-on="click" placeholder="设置初始密码" />
          </NFormItem>
        </div>
        <div class="grid grid-cols-1 gap-12px sm:grid-cols-2">
          <NFormItem label="代理昵称 / 商户名称" required>
            <NInput v-model:value="createForm.name" placeholder="输入昵称" />
          </NFormItem>
          <NFormItem label="选择代理等级" required>
            <NSelect
              v-model:value="createForm.grade_id"
              :options="gradeList.map(g => ({
                label: `${g.name} (${g.rate}×费率)${g.disabled ? ' [费率倒挂不可选]' : ''}`,
                value: g.id,
                disabled: g.disabled
              }))"
              placeholder="选择等级"
            />
          </NFormItem>
        </div>

        <div v-if="selectedGrade" class="create-cost rounded-8px bg-slate-50 p-12px dark:bg-dark-600 text-13px leading-relaxed border">
          <div>
            <span class="text-gray-500">开户费率：</span>
            <strong class="text-primary font-mono font-bold">{{ selectedGrade.rate }}× 成本系数</strong>
          </div>
          <div>
            <span class="text-gray-500">基础开户手续费：</span>
            <span class="font-mono">¥ {{ ktMoney.toFixed(2) }}</span>
          </div>
          <div v-if="selectedGrade.addkf === 1" class="text-emerald-600">
            <span>该等级包含自动赠送初始余额：</span>
            <span class="font-mono font-bold">+¥ {{ selectedGrade.money }}</span>
          </div>
          <div class="border-t mt-6px pt-6px text-13px">
            <span class="font-bold text-gray-700 dark:text-gray-200">预计从您账户扣除：</span>
            <strong class="font-mono text-15px text-rose-500 font-bold">¥ {{ calculatedNeed }}</strong>
          </div>
        </div>
      </div>
      <template #footer>
        <div class="userlist-modal-actions">
          <NButton @click="createModal = false">取消</NButton>
          <NButton type="primary" :loading="createLoading" :disabled="openReg === '0'" @click="handleCreateUser">
            确认开通代理
          </NButton>
        </div>
      </template>
    </NModal>
  </div>
</template>

<style scoped>
.userlist-page,
.userlist-panel,
.userlist-filters > * {
  min-width: 0;
}

.userlist-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 16px;
}

.userlist-filters {
  display: grid;
  grid-template-columns: minmax(180px, 240px) 130px auto;
  gap: 10px;
}

.userlist-toolbar-actions {
  display: flex;
  gap: 8px;
}

.userlist-pagination {
  display: flex;
  justify-content: flex-end;
  margin-top: 16px;
  padding: 4px 0;
}

:global(.userlist-modal) {
  display: flex;
  max-width: calc(100vw - 24px);
  max-height: calc(100dvh - 32px);
  overflow-wrap: anywhere;
}

:global(.userlist-modal .n-card-content) {
  min-height: 0;
  overflow-y: auto;
}

.userlist-modal-actions,
.balance-summary,
.create-cost > div {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px 12px;
}

.userlist-modal-actions {
  justify-content: flex-end;
}

.balance-summary,
.create-cost > div {
  flex-wrap: wrap;
}

.create-cost > div > :last-child {
  margin-left: auto;
}

@media (max-width: 639px) {
  .userlist-panel :deep(.n-card-header) {
    padding: 16px 14px 12px;
  }

  .userlist-panel :deep(.n-card-header__main) {
    font-size: 17px;
  }

  .userlist-panel :deep(.n-card-content) {
    padding: 0 14px 16px;
  }

  .userlist-toolbar,
  .userlist-filters,
  .userlist-toolbar-actions {
    width: 100%;
  }

  .userlist-filters {
    grid-template-columns: minmax(0, 1fr) 80px;
  }

  .userlist-search {
    grid-column: 1 / -1;
  }

  .userlist-toolbar-actions {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 80px;
  }

  .userlist-page :deep(.n-button),
  .userlist-page :deep(.n-input),
  .userlist-page :deep(.n-base-selection-label),
  :global(.userlist-modal .n-button),
  :global(.userlist-modal .n-input),
  :global(.userlist-modal .n-base-selection-label) {
    min-height: 44px;
  }

  .userlist-page :deep(.n-input__input-el),
  :global(.userlist-modal .n-input__input-el) {
    height: 44px;
    line-height: 44px;
  }

  .agent-list {
    display: grid;
    gap: 12px;
  }

  .agent-card {
    min-width: 0;
    padding: 14px 12px 12px;
    border: 1px solid var(--n-border-color);
    border-radius: 8px;
    overflow-wrap: anywhere;
  }

  .agent-card-header {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .agent-card-header :deep(.n-avatar) {
    flex-shrink: 0;
  }

  .agent-identity {
    flex: 1;
    min-width: 0;
  }

  .agent-status {
    min-height: 44px;
    flex-shrink: 0;
  }

  .agent-identifiers {
    display: flex;
    flex-wrap: wrap;
    gap: 4px 12px;
    margin-top: 10px;
  }

  .agent-metrics {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
    margin: 14px 0;
    padding-bottom: 14px;
    border-bottom: 1px solid var(--n-border-color);
  }

  .agent-balance {
    grid-column: 1 / -1;
  }

  .agent-metrics dt,
  .agent-details dt {
    color: var(--n-title-text-color);
    opacity: 0.65;
    font-size: 13px;
  }

  .agent-metrics dd {
    margin: 4px 0 0;
    font-variant-numeric: tabular-nums;
  }

  .agent-details {
    display: grid;
    gap: 8px;
    margin: 0;
    font-size: 13px;
  }

  .agent-details > div {
    display: grid;
    grid-template-columns: 60px minmax(0, 1fr);
    gap: 8px;
  }

  .agent-details dd {
    margin: 0;
  }

  .agent-key {
    margin: 14px 0 12px;
  }

  .agent-key-actions,
  .agent-actions {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
  }

  .agent-key-actions {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 6px;
    margin-top: 8px;
  }

  .agent-delete {
    grid-column: 1 / -1;
  }

  .agent-key-actions :deep(.n-button) {
    padding-inline: 4px;
    font-size: 13px;
  }

  .userlist-pagination {
    justify-content: center;
  }

  .userlist-pagination :deep(.n-pagination-item),
  .userlist-pagination :deep(.n-pagination-quick-jumper .n-input) {
    min-width: 44px;
    min-height: 44px;
  }

  :global(.userlist-modal .n-card-header) {
    padding: 16px;
  }

  :global(.userlist-modal .n-card-header__main) {
    font-size: 17px;
  }

  :global(.userlist-modal .n-card-content) {
    padding: 0 16px;
  }

  :global(.userlist-modal .n-card__footer) {
    padding: 16px;
  }

  :global(.userlist-modal .n-card-header__close) {
    width: 44px;
    height: 44px;
  }

  :global(.userlist-modal .n-form-item-label) {
    height: auto;
    white-space: normal;
  }

  .userlist-modal-actions {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 2fr);
  }

  .balance-type {
    display: grid;
    width: 100%;
    gap: 8px;
  }

  .balance-type :deep(.n-radio-button) {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    border-radius: 4px;
  }

  .balance-type :deep(.n-radio-group__splitor) {
    display: none;
  }
}
</style>
