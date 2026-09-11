<script setup lang="ts">
import { computed, reactive } from 'vue';
import { useAuthStore } from '@/store/modules/auth';
import { useFormRules, useNaiveForm } from '@/hooks/common/form';

defineOptions({ name: 'PwdLogin' });

const authStore = useAuthStore();
const { formRef, validate } = useNaiveForm();

interface FormModel {
  userName: string;
  password: string;
  verification: string;
}

const model: FormModel = reactive({
  userName: '',
  password: '',
  verification: ''
});

const rules = computed<Record<keyof FormModel, App.Global.FormRule[]>>(() => {
  const { formRules } = useFormRules();

  return {
    userName: formRules.userName,
    password: formRules.pwd,
    verification: []
  };
});

async function handleSubmit() {
  await validate();
  await authStore.login(model.userName, model.password, model.verification);
}
</script>

<template>
  <NForm ref="formRef" :model="model" :rules="rules" size="large" :show-label="false" @keyup.enter="handleSubmit">
    <NFormItem path="userName">
      <NInput v-model:value="model.userName" autocomplete="username" placeholder="请输入平台账号" />
    </NFormItem>
    <NFormItem path="password">
      <NInput
        v-model:value="model.password"
        type="password"
        show-password-on="click"
        autocomplete="current-password"
        placeholder="请输入登录密码"
      />
    </NFormItem>
    <NFormItem path="verification">
      <NInput
        v-model:value="model.verification"
        type="password"
        show-password-on="click"
        autocomplete="one-time-code"
        placeholder="管理员二次验证（仅超级管理员填写）"
      />
    </NFormItem>
    <NSpace vertical :size="20">
      <NButton type="primary" size="large" round block :loading="authStore.loginLoading" @click="handleSubmit">
        登录管理后台
      </NButton>
      <NText depth="3" class="text-center text-13px">
        使用现有平台账号登录，身份由服务端安全 Cookie 维护。
      </NText>
    </NSpace>
  </NForm>
</template>

<style scoped></style>
