<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useAuthStore } from '@/store/modules/auth';
import { fetchChargeInfo } from '@/service/api';

defineOptions({ name: 'Charge' });

const authStore = useAuthStore();
const loading = ref(false);
const rechargeAmount = ref(10);
const selectedType = ref('alipay');

const chargeInfo = ref<Api.ProfileArea.ChargeInfo>({
  user: '',
  balance: '0.00',
  isSuper: false,
  isDirect: false,
  onlineRechargeEnabled: true,
  minAmount: '10',
  isAlipay: true,
  isWxpay: true,
  isQqpay: true
});

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchChargeInfo();
  loading.value = false;
  if (!error && data) {
    chargeInfo.value = data;
    rechargeAmount.value = Number(data.minAmount) || 10;
  }
}

function handleSubmit() {
  const min = Number(chargeInfo.value.minAmount) || 1;
  if (rechargeAmount.value < min) {
    window.$message?.warning(`充值金额不得低于最低限制 ¥ ${min}`);
    return;
  }

  // 创建并提交表单到 epay
  const form = document.createElement('form');
  form.action = '/epay/epay.php';
  form.method = 'POST';
  form.target = '_blank';

  const moneyInput = document.createElement('input');
  moneyInput.type = 'hidden';
  moneyInput.name = 'money';
  moneyInput.value = String(rechargeAmount.value);
  form.appendChild(moneyInput);

  const typeInput = document.createElement('input');
  typeInput.type = 'hidden';
  typeInput.name = 'type';
  typeInput.value = selectedType.value;
  form.appendChild(typeInput);

  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="在线余额充值" :bordered="false" class="max-w-650px rounded-8px shadow-sm">
      <div v-if="!chargeInfo.onlineRechargeEnabled" class="py-20px">
        <NAlert type="warning" title="在线充值暂未开放">
          平台当前未开放个人在线充值接口，请直接联系上级代理或站长进行人工转账充值。
        </NAlert>
      </div>

      <div v-else class="flex flex-col gap-20px">
        <NAlert type="info">
          充值账号：<strong>{{ authStore.userInfo.userName }}</strong> | 当前可用余额：<strong class="text-success">¥ {{ authStore.userInfo.balance }}</strong>
        </NAlert>

        <div class="flex flex-col gap-8px">
          <label class="font-500">充值金额 (元)：</label>
          <NInputNumber v-model:value="rechargeAmount" :min="Number(chargeInfo.minAmount) || 1" :step="10" class="w-full">
            <template #prefix>¥</template>
          </NInputNumber>
          <span class="text-12px text-gray-500">最低充值金额限制：¥ {{ chargeInfo.minAmount }} 元</span>
        </div>

        <div class="flex flex-col gap-8px">
          <label class="font-500">选择支付渠道：</label>
          <NRadioGroup v-model:value="selectedType">
            <NSpace>
              <NRadio v-if="chargeInfo.isAlipay" value="alipay">支付宝</NRadio>
              <NRadio v-if="chargeInfo.isWxpay" value="wxpay">微信支付</NRadio>
              <NRadio v-if="chargeInfo.isQqpay" value="qqpay">QQ 钱包</NRadio>
            </NSpace>
          </NRadioGroup>
        </div>

        <NButton type="primary" size="large" class="mt-8px" @click="handleSubmit">
          前往收银台完成支付
        </NButton>

        <div class="rounded-6px bg-gray-50 p-12px text-13px text-gray-500 dark:bg-dark-600">
          <p class="m-0 font-600">充值提示：</p>
          <p class="m-0 mt-4px">1. 在线充值通过聚合支付网关结算，支付成功后系统将在 3 秒内自动充入余额；</p>
          <p class="m-0 mt-4px">2. 支付成功后请勿立即关闭跳转页面，等待系统返回或刷新本后台查看最新余额；</p>
          <p class="m-0 mt-4px">3. 如遇已扣款但余额未增加，请至【支付订单】复制商户单号联系站长核验补单。</p>
        </div>
      </div>
    </NCard>
  </div>
</template>
