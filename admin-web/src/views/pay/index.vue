<script setup lang="ts">
import { ref } from 'vue';
import { useAuthStore } from '@/store/modules/auth';
import { submitPayCard } from '@/service/api';

defineOptions({ name: 'Pay' });

const authStore = useAuthStore();
const cardCode = ref('');
const submitting = ref(false);

async function handleRecharge() {
  if (!cardCode.value.trim()) {
    window.$message?.warning('请输入卡密代码');
    return;
  }

  submitting.value = true;
  const { data, error } = await submitPayCard(cardCode.value.trim());
  submitting.value = false;

  if (!error && data) {
    window.$message?.success('卡密充值成功！');
    authStore.userInfo.balance = data.newBalance;
    cardCode.value = '';
  }
}
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="卡密快速充值" :bordered="false" class="max-w-650px rounded-8px shadow-sm">
      <div class="flex flex-col gap-20px">
        <NAlert type="info">
          当前账户：<strong>{{ authStore.userInfo.userName }}</strong> | 可用余额：<strong class="text-success">¥ {{ authStore.userInfo.balance }}</strong>
        </NAlert>

        <div class="flex flex-col gap-8px">
          <label class="font-500">充值卡密代码：</label>
          <div class="flex gap-12px">
            <NInput v-model:value="cardCode" placeholder="请输入向站长或上级购买的充值卡密" class="flex-1" @keyup.enter="handleRecharge" />
            <NButton type="primary" :loading="submitting" @click="handleRecharge">立即充值</NButton>
          </div>
        </div>

        <div class="rounded-6px bg-gray-50 p-12px text-13px text-gray-500 dark:bg-dark-600">
          <p class="m-0 font-600">使用须知：</p>
          <p class="m-0 mt-4px">1. 卡密仅限充值一次，核销后自动将对应面值充入您的账户可用余额；</p>
          <p class="m-0 mt-4px">2. 请妥善保管未使用的卡密，切勿泄露给第三方；</p>
          <p class="m-0 mt-4px">3. 如遇卡密无效或已被使用，请及时联系向您发卡的上级或平台站长。</p>
        </div>
      </div>
    </NCard>
  </div>
</template>
