<script setup lang="ts">
import { computed, h, onMounted, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import type { DataTableColumns } from 'naive-ui';
import {
  NAvatar,
  NButton,
  NCard,
  NDataTable,
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
  NSwitch,
  NTag
} from 'naive-ui';
import { useAppStore } from '@/store/modules/app';
import { useAuthStore } from '@/store/modules/auth';
import {
  adjustUserBalance,
  createUser,
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
      const digits = (row.user || '').replace(/\D/g, '');
      const avatarSrc = (digits.length >= 5 && digits.length <= 11)
        ? `https://q1.qlogo.cn/g?b=qq&nk=${digits}&s=100`
        : 'https://q1.qlogo.cn/g?b=qq&nk=10001&s=100';
      return h('div', { class: 'flex items-center gap-8px' }, [
        h(NAvatar, { round: true, size: 26, src: avatarSrc, fallbackSrc: 'https://q1.qlogo.cn/g?b=qq&nk=10001&s=100' }),
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
          onUpdateValue: async (val: boolean) => {
            const res = await updateUserStatus(row.uid, val ? 1 : 0);
            if (res !== null) {
              row.active = val ? 1 : 0;
              window.$message?.success(val ? '已解封' : '已封禁');
            }
          }
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
    width: 300,
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
            onClick: () => {
              currentUid.value = row.uid;
              currentUserName.value = row.name || row.user;
              targetRate.value = row.addprice;
              rateModal.value = true;
            }
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
  <div class="flex flex-col gap-14px p-10px sm:p-16px">
    <NCard :title="isSuperAdmin ? '代理管理 (全站商户)' : '代理管理 (我的下级代理)'" :bordered="false" class="rounded-8px shadow-sm">
      <p v-if="!isSuperAdmin" class="text-12px text-gray-500 mb-12px bg-amber-50 dark:bg-dark-500 p-8px rounded-6px border border-amber-200 dark:border-dark-400">
        💡 提示：您当前查看的是直属于您名下的下级代理团队。您可点击右上角“➕ 添加代理”开通直属下级，也可在操作列为直属下级充值或调整费率。
      </p>
      <div class="mb-16px flex flex-wrap items-center justify-between gap-12px">
        <div class="flex flex-wrap items-center gap-10px">
          <NInput v-model:value="query.keyword" placeholder="UID / 账号 / 昵称 / 邀请码" clearable class="w-full sm:w-240px" @keyup.enter="loadData" />
          <NSelect
            v-model:value="query.status"
            :options="[
              { label: '全部状态', value: '' },
              { label: '正常', value: '1' },
              { label: '已封禁', value: '0' }
            ]"
            placeholder="账号状态"
            clearable
            class="w-full sm:w-130px"
          />
          <NButton type="primary" @click="loadData">查询</NButton>
        </div>
        <div class="flex items-center gap-8px">
          <NButton type="primary" @click="openCreateModal">➕ 添加代理</NButton>
          <NButton :loading="loading" @click="loadData">刷新</NButton>
        </div>
      </div>

      <NDataTable
        :loading="loading"
        :columns="columns"
        :data="list"
        :row-key="(row: Api.ProfileArea.UserItem) => row.uid"
        :pagination="false"
        striped
        :scroll-x="1300"
      />

      <div class="mt-16px flex justify-end">
        <NPagination
          v-model:page="query.page"
          v-model:page-size="query.pageSize"
          :item-count="total"
          :page-sizes="[20, 50, 100]"
          show-size-picker
          show-quick-jumper
          @update:page="loadData"
          @update:page-size="loadData"
        />
      </div>
    </NCard>

    <!-- 余额调整弹窗 (加钱 / 减钱) -->
    <NModal v-model:show="balanceModal" preset="card" title="代理账户余额调整" :style="{ width: appStore.isMobile ? '92vw' : '480px' }">
      <div class="flex flex-col gap-14px">
        <div v-if="currentTargetUser" class="rounded-8px bg-slate-50 p-10px dark:bg-dark-600 border text-13px flex justify-between items-center">
          <div>
            目标代理：<strong>{{ currentTargetUser.name || currentTargetUser.user }}</strong>
            <span class="text-gray-400 text-12px ml-4px">[UID: {{ currentTargetUser.uid }}]</span>
          </div>
          <div>
            当前可用：<strong class="text-emerald-600 font-mono font-bold text-15px">¥ {{ currentTargetUser.money }}</strong>
          </div>
        </div>

        <NFormItem label="调整类型">
          <NRadioGroup v-model:value="balanceForm.type">
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
        <div class="flex justify-end gap-12px">
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
    <NModal v-model:show="pwdModal" preset="card" title="重置代理登录密码" :style="{ width: appStore.isMobile ? '92vw' : '440px' }">
      <div class="flex flex-col gap-14px">
        <NAlert type="info">正在为代理商户 <strong>{{ currentUserName }}</strong>（UID: {{ currentUid }}）重置登录密码。</NAlert>
        <NFormItem label="设置新登录密码 (默认 12345678)" required>
          <NInput v-model:value="resetPwdForm.password" placeholder="输入新密码，至少6位" />
        </NFormItem>
      </div>
      <template #footer>
        <div class="flex justify-end gap-12px">
          <NButton @click="pwdModal = false">取消</NButton>
          <NButton type="primary" :loading="pwdLoading" @click="handleResetPassword">确认重置密码</NButton>
        </div>
      </template>
    </NModal>

    <!-- 调费率弹窗 -->
    <NModal v-model:show="rateModal" preset="card" title="修改代理费率" :style="{ width: appStore.isMobile ? '92vw' : '460px' }">
      <div class="flex flex-col gap-12px">
        <div class="text-14px">目标代理：<strong>[UID: {{ currentUid }}] {{ currentUserName }}</strong></div>
        <NFormItem :label="isSuperAdmin ? '新费率系数 (如 0.25 代表 2.5 折成本)' : '下级成本费率 (不可低于您自身成本费率)'">
          <NInput v-model:value="targetRate" placeholder="输入费率系数，如 0.30" />
        </NFormItem>
      </div>
      <template #footer>
        <div class="flex justify-end gap-12px">
          <NButton @click="rateModal = false">取消</NButton>
          <NButton type="primary" @click="handleUpdateRate">确认修改</NButton>
        </div>
      </template>
    </NModal>

    <!-- 开通代理弹窗 -->
    <NModal v-model:show="createModal" preset="card" :title="isSuperAdmin ? '开通代理账号' : '开通直属下级代理'" :style="{ width: appStore.isMobile ? '92vw' : '520px' }">
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

        <div v-if="selectedGrade" class="rounded-8px bg-slate-50 p-12px dark:bg-dark-600 text-12px leading-relaxed border">
          <div class="flex justify-between">
            <span class="text-gray-500">开户费率：</span>
            <strong class="text-primary font-mono font-bold">{{ selectedGrade.rate }}× 成本系数</strong>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-500">基础开户手续费：</span>
            <span class="font-mono">¥ {{ ktMoney.toFixed(2) }}</span>
          </div>
          <div v-if="selectedGrade.addkf === 1" class="flex justify-between text-emerald-600">
            <span>该等级包含自动赠送初始余额：</span>
            <span class="font-mono font-bold">+¥ {{ selectedGrade.money }}</span>
          </div>
          <div class="flex justify-between border-t mt-6px pt-6px text-13px">
            <span class="font-bold text-gray-700 dark:text-gray-200">预计从您账户扣除：</span>
            <strong class="font-mono text-15px text-rose-500 font-bold">¥ {{ calculatedNeed }}</strong>
          </div>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-12px">
          <NButton @click="createModal = false">取消</NButton>
          <NButton type="primary" :loading="createLoading" :disabled="openReg === '0'" @click="handleCreateUser">
            确认开通代理
          </NButton>
        </div>
      </template>
    </NModal>
  </div>
</template>
