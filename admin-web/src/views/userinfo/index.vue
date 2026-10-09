<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/store/modules/auth';
import {
  createUserApiKey,
  fetchUserProfile,
  refreshUserApiKey,
  updateUserPassword,
  updateUserProfile,
  updateUserPushToken,
  updateUserYqprice
} from '@/service/api';

defineOptions({ name: 'Userinfo' });

const router = useRouter();
const authStore = useAuthStore();
const isSuper = computed(() => authStore.userInfo.roles.includes('R_SUPER'));

const loading = ref(false);
const submitting = ref(false);
const hideApiKey = ref(true);

const profile = ref<Api.ProfileArea.UserProfile>({
  uid: '',
  user: '',
  name: '',
  avatar: '',
  money: '0.00',
  zcz: '0',
  addprice: '1.00',
  vip: 0,
  yqm: '',
  yqprice: '',
  inviteUrl: '',
  superiorUser: '无',
  key: '',
  hasKey: false,
  pushPlusToken: '',
  totalOrders: 0,
  stats: {
    agentTotal: 0,
    agentRegToday: 0,
    agentLoginToday: 0,
    orderToday: 0
  },
  siteNotice: '',
  superiorNotice: '',
  siteName: ''
});

const avatarUrl = computed(() => {
  if (profile.value.avatar) {
    return profile.value.avatar;
  }
  const user = profile.value.user || '';
  const digits = user.replace(/\D/g, '');
  if (digits.length >= 5 && digits.length <= 11) {
    return 'https://q1.qlogo.cn/g?b=qq&nk=' + digits + '&s=640';
  }
  return 'https://q1.qlogo.cn/g?b=qq&nk=10001&s=640';
});

// Modals
const nameModal = ref(false);
const editName = ref('');

const yqpriceModal = ref(false);
const editYqprice = ref('');

const pushTokenModal = ref(false);
const editPushToken = ref('');

const pwdModal = ref(false);
const pwdForm = reactive({
  oldPassword: '',
  newPassword: '',
  confirmPassword: ''
});

async function loadProfile() {
  loading.value = true;
  const { data, error } = await fetchUserProfile();
  loading.value = false;

  if (!error && data) {
    profile.value = data;
    authStore.userInfo.displayName = data.name;
    authStore.userInfo.balance = data.money;
  }
}

function copyText(text: string, label = '内容') {
  if (!text) {
    window.$message?.warning('暂无' + label + '可复制');
    return;
  }
  navigator.clipboard.writeText(text);
  window.$message?.success(label + '已成功复制到剪贴板');
}

// 1. 修改昵称
function openNameModal() {
  editName.value = profile.value.name;
  nameModal.value = true;
}

async function handleSaveName() {
  const name = editName.value.trim();
  if (!name) {
    window.$message?.warning('用户昵称不能为空');
    return;
  }
  submitting.value = true;
  const { error } = await updateUserProfile(name);
  submitting.value = false;
  if (!error) {
    window.$message?.success('昵称修改成功');
    profile.value.name = name;
    authStore.userInfo.displayName = name;
    nameModal.value = false;
  }
}

// 2. 设置下级费率
function openYqpriceModal() {
  editYqprice.value = profile.value.yqprice || profile.value.addprice;
  yqpriceModal.value = true;
}

async function handleSaveYqprice() {
  const rate = editYqprice.value.trim();
  if (!rate || isNaN(Number(rate))) {
    window.$message?.warning('请输入合法的费率数字');
    return;
  }
  if (Number(rate) < Number(profile.value.addprice)) {
    window.$message?.warning('下级默认费率不能低于您自身的成本费率 (' + profile.value.addprice + ')');
    return;
  }
  if (Number(rate) < 0.2) {
    window.$message?.warning('邀请费率最低不能低于 0.20');
    return;
  }

  submitting.value = true;
  const { data, error } = await updateUserYqprice(rate);
  submitting.value = false;
  if (!error && data) {
    window.$message?.success('下级默认费率设置成功');
    profile.value.yqprice = data.yqprice;
    profile.value.yqm = data.yqm;
    profile.value.inviteUrl = data.inviteUrl;
    yqpriceModal.value = false;
  }
}

// 3. 开通 API 密钥
function handleCreateKey() {
  const balance = Number(profile.value.money);
  const isFree = balance >= 50;
  const tipText = isFree
    ? '您当前余额满 50 元，享受免费开通特权。确认立即开通 API 密钥吗？'
    : ('开通 API 接口需扣除 5 元手续费，您当前可用余额为 ¥ ' + profile.value.money + '。确认立即开通吗？');

  window.$dialog?.info({
    title: '开通 API 接口对接',
    content: tipText,
    positiveText: '确认开通',
    negativeText: '取消',
    onPositiveClick: async () => {
      submitting.value = true;
      const { data, error } = await createUserApiKey();
      submitting.value = false;
      if (!error && data) {
        window.$message?.success('API 接口开通成功！');
        profile.value.key = data.key;
        profile.value.hasKey = true;
        profile.value.money = data.balance;
        authStore.userInfo.balance = data.balance;
        hideApiKey.value = false;
      }
    }
  });
}

// 4. 更换 API 密钥
function handleRefreshKey() {
  window.$dialog?.warning({
    title: '更换 API 密钥确认',
    content: '更换后旧密钥将即时失效，使用旧密钥对接的自动化程序、发卡网或脚本将无法继续调用。确认生成新密钥吗？',
    positiveText: '确认更换',
    negativeText: '取消',
    onPositiveClick: async () => {
      submitting.value = true;
      const { data, error } = await refreshUserApiKey();
      submitting.value = false;
      if (!error && data) {
        window.$message?.success('API 密钥已更新，旧密钥已失效');
        profile.value.key = data.key;
        hideApiKey.value = false;
      }
    }
  });
}

// 5. 设置 PushPlus Token
function openPushTokenModal() {
  editPushToken.value = profile.value.pushPlusToken || '';
  pushTokenModal.value = true;
}

async function handleSavePushToken() {
  const token = editPushToken.value.trim();
  submitting.value = true;
  const { data, error } = await updateUserPushToken(token);
  submitting.value = false;
  if (!error && data) {
    window.$message?.success(token ? '微信推送 Token 设置成功' : '微信推送 Token 已清除解绑');
    profile.value.pushPlusToken = data.pushPlusToken;
    pushTokenModal.value = false;
  }
}

// 6. 修改密码
function openPwdModal() {
  pwdForm.oldPassword = '';
  pwdForm.newPassword = '';
  pwdForm.confirmPassword = '';
  pwdModal.value = true;
}

async function handleSavePassword() {
  if (!pwdForm.oldPassword) {
    window.$message?.warning('请输入原登录密码');
    return;
  }
  if (!pwdForm.newPassword || pwdForm.newPassword.length < 6) {
    window.$message?.warning('新密码长度不能少于 6 位');
    return;
  }
  if (pwdForm.newPassword !== pwdForm.confirmPassword) {
    window.$message?.warning('两次输入的新密码不一致');
    return;
  }

  submitting.value = true;
  const { error } = await updateUserPassword({
    oldPassword: pwdForm.oldPassword,
    newPassword: pwdForm.newPassword,
    confirmPassword: pwdForm.confirmPassword
  });
  submitting.value = false;
  if (!error) {
    window.$message?.success('密码修改成功，请牢记新密码');
    pwdModal.value = false;
  }
}

onMounted(() => {
  loadProfile();
});
</script>

<template>
  <NSpin :show="loading">
    <div class="flex flex-col gap-16px p-16px">
      <!-- 站长/上级公告横幅 -->
      <div v-if="profile.superiorNotice || profile.siteNotice" class="flex flex-col gap-10px">
        <NAlert v-if="profile.superiorNotice" type="warning" title="上级代理通知" :show-icon="true" closable>
          <div class="whitespace-pre-wrap leading-relaxed">{{ profile.superiorNotice }}</div>
        </NAlert>
        <NAlert v-if="profile.siteNotice" type="info" title="全站公告" :show-icon="true" closable>
          <div class="whitespace-pre-wrap leading-relaxed">{{ profile.siteNotice }}</div>
        </NAlert>
      </div>

      <!-- 第一行：基础资料与经营数据概览 -->
      <NGrid cols="1 m:3" responsive="screen" :x-gap="16" :y-gap="16">
        <!-- 左侧个人卡片 -->
        <NGi>
          <NCard :bordered="false" class="h-full rounded-8px shadow-sm">
            <div class="flex flex-col items-center text-center">
              <NAvatar
                round
                :size="84"
                :src="avatarUrl"
                fallback-src="https://q1.qlogo.cn/g?b=qq&nk=10001&s=640"
                class="border-2 border-primary/30 shadow-md"
              />

              <div class="mt-14px flex items-center justify-center gap-8px">
                <h2 class="text-20px font-bold text-gray-800 dark:text-gray-100">
                  {{ profile.name || profile.user }}
                </h2>
                <NTooltip trigger="hover">
                  <template #trigger>
                    <NButton size="tiny" quaternary circle @click="openNameModal">
                      <template #icon>
                        <SvgIcon icon="ph:pencil-simple" class="text-16px text-primary" />
                      </template>
                    </NButton>
                  </template>
                  修改显示昵称
                </NTooltip>
              </div>

              <div class="mt-4px text-13px text-gray-500">登录账号：{{ profile.user }}</div>

              <div class="mt-12px flex flex-wrap items-center justify-center gap-8px">
                <NTag :type="isSuper ? 'error' : 'primary'" size="small" round>
                  {{ isSuper ? '超级管理员' : '代理用户' }}
                </NTag>
                <NTag type="info" size="small" round>UID: {{ profile.uid }}</NTag>
                <NTag v-if="profile.vip === 1" type="warning" size="small" round>VIP 会员</NTag>
              </div>

              <div class="mt-16px w-full rounded-6px bg-gray-50 p-12px text-left text-13px dark:bg-dark-600">
                <div class="flex justify-between py-4px">
                  <span class="text-gray-500">上级代理归属：</span>
                  <span class="font-medium text-primary">{{ profile.superiorUser || '系统直属' }}</span>
                </div>
                <div class="flex justify-between py-4px">
                  <span class="text-gray-500">所属管理站点：</span>
                  <span class="font-medium text-gray-700 dark:text-gray-300">{{ profile.siteName }}</span>
                </div>
              </div>

              <NGrid :cols="2" :x-gap="10" class="mt-16px w-full">
                <NGi>
                  <NButton secondary block type="primary" size="small" @click="openNameModal">
                    修改昵称
                  </NButton>
                </NGi>
                <NGi>
                  <NButton secondary block type="warning" size="small" @click="openPwdModal">
                    修改密码
                  </NButton>
                </NGi>
              </NGrid>
            </div>
          </NCard>
        </NGi>

        <!-- 右侧经营核心资产卡片 -->
        <NGi span="1 m:2">
          <NCard title="经营核心数据与资产概览" :bordered="false" class="h-full rounded-8px shadow-sm">
            <template #header-extra>
              <NButton size="small" type="primary" ghost @click="loadProfile">
                <template #icon><SvgIcon icon="ph:arrows-clockwise" /></template>
                刷新数据
              </NButton>
            </template>

            <NGrid cols="2 s:3" responsive="screen" :x-gap="16" :y-gap="16">
              <NGi>
                <NCard embedded :bordered="false" class="rounded-8px">
                  <NStatistic label="账户可用余额" :value="'¥ ' + profile.money">
                    <template #prefix>
                      <SvgIcon icon="ph:wallet" class="mr-6px text-22px text-success" />
                    </template>
                  </NStatistic>
                  <div class="mt-8px flex items-center justify-between">
                    <span class="text-12px text-gray-400">实时计算余额</span>
                    <NButton size="tiny" type="primary" @click="router.push('/charge')">立即充值</NButton>
                  </div>
                </NCard>
              </NGi>

              <NGi>
                <NCard embedded :bordered="false" class="rounded-8px">
                  <NStatistic label="累计充值总额" :value="'¥ ' + profile.zcz">
                    <template #prefix>
                      <SvgIcon icon="ph:coins" class="mr-6px text-22px text-warning" />
                    </template>
                  </NStatistic>
                  <div class="mt-8px text-12px text-gray-400">历史全部充值沉淀</div>
                </NCard>
              </NGi>

              <NGi>
                <NCard embedded :bordered="false" class="rounded-8px">
                  <NStatistic label="我的成本费率" :value="profile.addprice + ' ×'">
                    <template #prefix>
                      <SvgIcon icon="ph:trend-up" class="mr-6px text-22px text-primary" />
                    </template>
                  </NStatistic>
                  <div class="mt-8px text-12px text-gray-400">自身拿货基准成本折扣</div>
                </NCard>
              </NGi>

              <NGi>
                <NCard embedded :bordered="false" class="rounded-8px">
                  <NStatistic label="历史接单总量" :value="profile.totalOrders">
                    <template #prefix>
                      <SvgIcon icon="ph:receipt" class="mr-6px text-22px text-info" />
                    </template>
                    <template #suffix>单</template>
                  </NStatistic>
                  <div class="mt-8px text-12px text-gray-400">个人全部已提交订单</div>
                </NCard>
              </NGi>

              <NGi>
                <NCard embedded :bordered="false" class="rounded-8px">
                  <NStatistic label="团队下级代理" :value="profile.stats.agentTotal">
                    <template #prefix>
                      <SvgIcon icon="ph:users-three" class="mr-6px text-22px text-purple-500" />
                    </template>
                    <template #suffix>人</template>
                  </NStatistic>
                  <div class="mt-8px text-12px text-gray-400">今日新注册: +{{ profile.stats.agentRegToday }} 人</div>
                </NCard>
              </NGi>

              <NGi>
                <NCard embedded :bordered="false" class="rounded-8px">
                  <NStatistic label="团队今日接单" :value="profile.stats.orderToday">
                    <template #prefix>
                      <SvgIcon icon="ph:chart-bar" class="mr-6px text-cyan-600" />
                    </template>
                    <template #suffix>单</template>
                  </NStatistic>
                  <div class="mt-8px text-12px text-gray-400">今日活跃代理: {{ profile.stats.agentLoginToday }} 人</div>
                </NCard>
              </NGi>
            </NGrid>
          </NCard>
        </NGi>
      </NGrid>

      <!-- 第二行：代理分销推广体系 & API 接口对接管理 -->
      <NGrid cols="1 m:2" responsive="screen" :x-gap="16" :y-gap="16">
        <!-- 代理分销与推广体系 -->
        <NGi>
          <NCard title="代理分销与推广中心" :bordered="false" class="h-full rounded-8px shadow-sm">
            <template #header-extra>
              <NButton size="small" type="primary" secondary @click="openYqpriceModal">
                设置下级费率
              </NButton>
            </template>

            <div class="flex flex-col gap-14px">
              <div class="flex items-center justify-between rounded-8px bg-primary/8 p-12px">
                <div class="flex items-center gap-12px">
                  <div class="size-42px flex-center rd-10px bg-primary text-white">
                    <SvgIcon icon="ph:share-network" class="text-22px" />
                  </div>
                  <div>
                    <div class="text-12px text-gray-500">我的专属推广邀请码</div>
                    <div class="text-18px font-bold text-primary">{{ profile.yqm || '设置费率后生成' }}</div>
                  </div>
                </div>
                <div class="text-right">
                  <div class="text-12px text-gray-500">下级默认成本费率</div>
                  <div class="text-16px font-bold text-gray-800 dark:text-gray-100">
                    {{ profile.yqprice ? (profile.yqprice + ' ×') : '暂未设置' }}
                  </div>
                </div>
              </div>

              <div>
                <label class="mb-6px block text-13px font-medium text-gray-600 dark:text-gray-300">
                  专属推广注册链接：
                </label>
                <div class="flex gap-8px">
                  <NInput
                    :value="profile.inviteUrl || '请先设置下级费率生成专属邀请链接'"
                    readonly
                    placeholder="专属邀请链接"
                    class="min-w-0 flex-1 font-mono text-13px"
                  />
                  <NButton
                    type="primary"
                    :disabled="!profile.inviteUrl"
                    @click="copyText(profile.inviteUrl, '推广链接')"
                  >
                    复制链接
                  </NButton>
                </div>
              </div>

              <div class="rounded-6px border border-gray-100 bg-gray-50 p-12px text-12px text-gray-500 dark:border-dark-400 dark:bg-dark-600">
                <p class="m-0 font-bold text-gray-700 dark:text-gray-300">推广与分销机制：</p>
                <p class="m-0 mt-4px">1. 用户通过您的专属邀请链接注册后，将永久自动绑定为您团队的直属下级代理；</p>
                <p class="m-0 mt-4px">2. 下级代理初始成本费率为您设定的邀请费率，下级每笔下单差价将自动结算至您的账户余额；</p>
                <p class="m-0 mt-4px">3. 邀请费率支持随时调整，最低不可低于您自身的成本费率（{{ profile.addprice }} ×）。</p>
              </div>
            </div>
          </NCard>
        </NGi>

        <!-- API 接口对接中心 -->
        <NGi>
          <NCard title="开放平台与 API 对接密钥" :bordered="false" class="h-full rounded-8px shadow-sm">
            <template #header-extra>
              <NButton size="small" type="info" ghost @click="router.push('/docking')">
                查看对接文档
              </NButton>
            </template>

            <div v-if="!profile.hasKey" class="flex flex-col items-center justify-center py-20px text-center">
              <div class="size-60px flex-center rd-1/2 bg-warning/12 text-warning">
                <SvgIcon icon="ph:key" class="text-32px" />
              </div>
              <h3 class="mt-14px text-16px font-bold">API 接口尚未开通</h3>
              <p class="mt-4px max-w-360px text-13px text-gray-500">
                开通后可获得独立的开放接口 Key，支持与第三方系统、发卡平台或自动化脚本免登录串联对接。
              </p>
              <div class="mt-12px text-12px text-gray-400">
                开通规则：账户余额满 50 元享受<strong>免费开通</strong>，未满 50 元仅扣除 5 元手续费
              </div>
              <NButton type="primary" class="mt-16px px-24px" :loading="submitting" @click="handleCreateKey">
                立即开通 API 密钥
              </NButton>
            </div>

            <div v-else class="flex flex-col gap-14px">
              <div class="flex items-center justify-between rounded-8px bg-success/8 p-12px">
                <div class="flex items-center gap-12px">
                  <div class="size-42px flex-center rd-10px bg-success text-white">
                    <SvgIcon icon="ph:shield-check" class="text-22px" />
                  </div>
                  <div>
                    <div class="text-12px text-gray-500">API 接口状态</div>
                    <div class="text-15px font-bold text-success">已开通正常运行中</div>
                  </div>
                </div>
                <NButton size="small" type="warning" ghost @click="handleRefreshKey">
                  更换/重置 Key
                </NButton>
              </div>

              <div>
                <label class="mb-6px block text-13px font-medium text-gray-600 dark:text-gray-300">
                  我的 API 访问密钥 (Key)：
                </label>
                <div class="flex gap-8px">
                  <NInput
                    :value="hideApiKey ? '••••••••••••••••' : profile.key"
                    readonly
                    placeholder="API Key"
                    class="min-w-0 flex-1 font-mono text-14px font-bold tracking-wider"
                  >
                    <template #suffix>
                      <NButton text size="tiny" class="mr-4px" @click="hideApiKey = !hideApiKey">
                        <SvgIcon :icon="hideApiKey ? 'ph:eye' : 'ph:eye-slash'" class="text-18px text-gray-400 hover:text-primary" />
                      </NButton>
                    </template>
                  </NInput>
                  <NButton type="primary" secondary @click="copyText(profile.key, 'API 密钥')">
                    复制 Key
                  </NButton>
                </div>
              </div>

              <div class="rounded-6px border border-gray-100 bg-gray-50 p-12px text-12px text-gray-500 dark:border-dark-400 dark:bg-dark-600">
                <p class="m-0 font-bold text-gray-700 dark:text-gray-300">密钥保管与对接提示：</p>
                <p class="m-0 mt-4px">1. 密钥具备本账号最高下单与查单权限，请勿将密钥分享或提交到公开仓库；</p>
                <p class="m-0 mt-4px">2. 如疑似密钥泄漏，请立即点击右上角【更换/重置 Key】，旧密钥将即时失效；</p>
                <p class="m-0 mt-4px">3. 接口参数规范、加密验证及回执规范请查阅【项目与对接 - 对接中心】。</p>
              </div>
            </div>
          </NCard>
        </NGi>
      </NGrid>

      <!-- 第三行：微信通知推送 & 安全中心修改密码 -->
      <NGrid cols="1 m:2" responsive="screen" :x-gap="16" :y-gap="16">
        <!-- 微信消息通知推送配置 -->
        <NGi>
          <NCard title="微信消息推送通知" :bordered="false" class="h-full rounded-8px shadow-sm">
            <template #header-extra>
              <NButton size="small" type="primary" secondary @click="openPushTokenModal">
                {{ profile.pushPlusToken ? '修改 Token' : '设置 Token' }}
              </NButton>
            </template>

            <div class="flex flex-col gap-14px">
              <div class="flex items-center gap-12px rounded-8px bg-cyan-500/8 p-12px">
                <div class="size-42px flex-center rd-10px bg-cyan-600 text-white">
                  <SvgIcon icon="ph:bell-ringing" class="text-22px" />
                </div>
                <div class="flex-1">
                  <div class="text-12px text-gray-500">微信 PushPlus / PushShowDoc 推送</div>
                  <div class="text-14px font-bold text-cyan-800 dark:text-cyan-200">
                    {{ profile.pushPlusToken ? '已配置推送接收渠道' : '尚未配置推送 Token' }}
                  </div>
                </div>
              </div>

              <div>
                <label class="mb-6px block text-13px font-medium text-gray-600 dark:text-gray-300">
                  当前推送 Token 标识：
                </label>
                <div class="flex gap-8px">
                  <NInput
                    :value="profile.pushPlusToken || '未绑定（无法接收微信端实时通知）'"
                    readonly
                    class="min-w-0 flex-1 font-mono text-13px"
                  />
                  <NButton
                    v-if="profile.pushPlusToken"
                    type="error"
                    ghost
                    @click="openPushTokenModal"
                  >
                    解绑 / 更换
                  </NButton>
                </div>
              </div>

              <div class="rounded-6px border border-gray-100 bg-gray-50 p-12px text-12px text-gray-500 dark:border-dark-400 dark:bg-dark-600">
                <p class="m-0 font-bold text-gray-700 dark:text-gray-300">通知开通指引：</p>
                <p class="m-0 mt-4px">1. 关注【PushPlus】公众号，在公众号菜单或官网获取个人专属 Token；</p>
                <p class="m-0 mt-4px">2. 将 Token 填入本系统后，订单开课、进度更新、退款或异常提醒将秒级微信推送到手机；</p>
                <p class="m-0 mt-4px">3. 无需常驻后台挂机，随时随地掌握订单实时进展。</p>
              </div>
            </div>
          </NCard>
        </NGi>

        <!-- 账户安全与密码修改 -->
        <NGi>
          <NCard title="安全中心与密码管理" :bordered="false" class="h-full rounded-8px shadow-sm">
            <template #header-extra>
              <NButton size="small" type="warning" secondary @click="openPwdModal">
                修改登录密码
              </NButton>
            </template>

            <div class="flex flex-col gap-14px">
              <div class="flex items-center gap-12px rounded-8px bg-emerald-500/8 p-12px">
                <div class="size-42px flex-center rd-10px bg-emerald-600 text-white">
                  <SvgIcon icon="ph:lock-key" class="text-22px" />
                </div>
                <div>
                  <div class="text-12px text-gray-500">账号安全防护状态</div>
                  <div class="text-14px font-bold text-emerald-800 dark:text-emerald-200">
                    密码鉴权与 CSRF 防跨站双重校验中
                  </div>
                </div>
              </div>

              <NDescriptions label-placement="left" :column="1" bordered size="small">
                <NDescriptionsItem label="当前账号">{{ profile.user }}</NDescriptionsItem>
                <NDescriptionsItem label="登录保护">Cookie 安全加密会话</NDescriptionsItem>
                <NDescriptionsItem label="密码状态">已设置（建议每 30 天更换一次）</NDescriptionsItem>
              </NDescriptions>

              <div class="rounded-6px border border-gray-100 bg-gray-50 p-12px text-12px text-gray-500 dark:border-dark-400 dark:bg-dark-600">
                <p class="m-0 font-bold text-gray-700 dark:text-gray-300">安全防护须知：</p>
                <p class="m-0 mt-4px">1. 严禁将密码告知任何人，站长与客服绝不会以任何名义索要您的登录密码；</p>
                <p class="m-0 mt-4px">2. 修改密码成功后系统将自动更新授权凭证，无需重新登录即可继续安全操作。</p>
              </div>
            </div>
          </NCard>
        </NGi>
      </NGrid>
    </div>

    <!-- 弹窗1：修改显示昵称 -->
    <NModal v-model:show="nameModal" preset="card" title="修改用户显示昵称" class="max-w-440px">
      <div class="flex flex-col gap-12px">
        <label class="text-13px text-gray-500">输入用于后台前台展示的个性昵称：</label>
        <NInput v-model:value="editName" placeholder="请输入新昵称（限30字以内）" maxlength="30" show-count />
      </div>
      <template #footer>
        <div class="flex justify-end gap-10px">
          <NButton @click="nameModal = false">取消</NButton>
          <NButton type="primary" :loading="submitting" @click="handleSaveName">保存昵称</NButton>
        </div>
      </template>
    </NModal>

    <!-- 弹窗2：设置下级费率 -->
    <NModal v-model:show="yqpriceModal" preset="card" title="设置下级默认邀请费率" class="max-w-460px">
      <div class="flex flex-col gap-14px">
        <NAlert type="info">
          您的自身成本费率为 <strong>{{ profile.addprice }} ×</strong>。设置的下级费率不能低于此数值，且最低不可低于 0.20。
        </NAlert>
        <div>
          <label class="mb-6px block text-13px font-medium">下级初始费率系数：</label>
          <NInput v-model:value="editYqprice" placeholder="如 0.30 代表 3 折价格供货">
            <template #suffix>×</template>
          </NInput>
        </div>
        <div class="text-12px text-gray-400">
          如您之前尚未分配专属邀请码，保存时系统将自动生成 5~6 位专属邀请码。
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-10px">
          <NButton @click="yqpriceModal = false">取消</NButton>
          <NButton type="primary" :loading="submitting" @click="handleSaveYqprice">确认保存</NButton>
        </div>
      </template>
    </NModal>

    <!-- 弹窗3：设置微信推送 Token -->
    <NModal v-model:show="pushTokenModal" preset="card" title="设置 PushPlus 微信推送 Token" class="max-w-460px">
      <div class="flex flex-col gap-14px">
        <p class="m-0 text-13px text-gray-500">
          请输入 PushPlus (pushplus.plus) 分配给您的用户 Token。若需解绑，请清空后直接点击保存即可。
        </p>
        <NInput v-model:value="editPushToken" placeholder="留空保存即可解绑清空 Token" clearable />
      </div>
      <template #footer>
        <div class="flex justify-end gap-10px">
          <NButton @click="pushTokenModal = false">取消</NButton>
          <NButton type="primary" :loading="submitting" @click="handleSavePushToken">确认提交</NButton>
        </div>
      </template>
    </NModal>

    <!-- 弹窗4：修改登录密码 -->
    <NModal v-model:show="pwdModal" preset="card" title="修改登录密码" class="max-w-460px">
      <div class="flex flex-col gap-14px">
        <div>
          <label class="mb-4px block text-13px font-medium">原登录密码：</label>
          <NInput v-model:value="pwdForm.oldPassword" type="password" show-password-on="click" placeholder="请输入当前旧密码" />
        </div>
        <div>
          <label class="mb-4px block text-13px font-medium">新登录密码：</label>
          <NInput v-model:value="pwdForm.newPassword" type="password" show-password-on="click" placeholder="不少于 6 位的新密码" />
        </div>
        <div>
          <label class="mb-4px block text-13px font-medium">确认新密码：</label>
          <NInput v-model:value="pwdForm.confirmPassword" type="password" show-password-on="click" placeholder="请再次输入新密码" />
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-10px">
          <NButton @click="pwdModal = false">取消</NButton>
          <NButton type="primary" :loading="submitting" @click="handleSavePassword">确认修改密码</NButton>
        </div>
      </template>
    </NModal>
  </NSpin>
</template>

<style scoped></style>
