<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { NAlert, NButton, NCard, NForm, NFormItem, NInput, NSpin } from 'naive-ui';
import { userMigrate } from '@/service/api';

defineOptions({ name: 'Sjqy' });

const router = useRouter();
const submitting = ref(false);

const form = reactive({
  target_uid: '',
  yqm: ''
});

async function handleMigrate() {
  if (!form.target_uid.trim() || !form.yqm.trim()) {
    window.$message?.warning('目标上级 UID 与专属邀请码均不能为空');
    return;
  }

  submitting.value = true;
  const { error } = await userMigrate({
    target_uid: Number(form.target_uid.trim()),
    yqm: form.yqm.trim()
  });
  submitting.value = false;

  if (!error) {
    window.$message?.success('团队上级迁移成功！');
    router.push('/userinfo');
  }
}
</script>

<template>
  <div class="flex flex-col gap-16px p-10px sm:p-16px max-w-640px mx-auto">
    <NCard :bordered="false" class="rounded-12px shadow-sm border border-gray-100 dark:border-dark-600">
      <div class="flex items-center gap-12px">
        <div class="flex h-44px w-44px items-center justify-center rounded-10px bg-primary/10 text-primary text-22px">
          🤝
        </div>
        <div>
          <h1 class="text-17px font-bold text-gray-800 dark:text-gray-100">团队归属与上级商户迁移</h1>
          <p class="text-12px text-gray-400 mt-2px">通过新上级的专属邀请码，将您的账户归属迁移至目标代理团队名下</p>
        </div>
      </div>
    </NCard>

    <NCard title="迁移信息填写" :bordered="false" class="rounded-12px shadow-sm">
      <div class="flex flex-col gap-14px">
        <NAlert type="info" class="text-12px">
          注意：迁移成功后，您将享受新上级团队的统一定价与技术指导，请务必核对目标商户 UID。
        </NAlert>

        <NForm label-placement="top">
          <NFormItem label="目标新上级 UID" required>
            <NInput v-model:value="form.target_uid" placeholder="输入目标上级的纯数字 UID（如: 1002）" />
          </NFormItem>

          <NFormItem label="目标上级专属邀请码 (8位字符)" required>
            <NInput v-model:value="form.yqm" placeholder="向您的新上级索取其专属推广邀请码" />
          </NFormItem>

          <div class="mt-8px flex justify-end gap-12px">
            <NButton secondary @click="router.push('/userinfo')">返回个人中心</NButton>
            <NButton type="primary" size="large" :loading="submitting" @click="handleMigrate">
              确认发起迁移绑定
            </NButton>
          </div>
        </NForm>
      </div>
    </NCard>
  </div>
</template>
