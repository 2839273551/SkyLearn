<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { useAuthStore } from '@/store/modules/auth';
import { useFormRules, useNaiveForm } from '@/hooks/common/form';

defineOptions({ name: 'PwdLogin' });

const authStore = useAuthStore();
const { formRef, validate } = useNaiveForm();

interface FormModel {
  userName: string;
  password: string;
}

const model: FormModel = reactive({
  userName: '',
  password: ''
});

const verificationModal = ref(false);
const verificationCode = ref('');

const rules = computed<Record<keyof FormModel, App.Global.FormRule[]>>(() => {
  const { formRules } = useFormRules();

  return {
    userName: formRules.userName,
    password: formRules.pwd
  };
});

async function handleSubmit() {
  await validate();
  const res = await authStore.login(model.userName, model.password);
  if (res?.needVerification) {
    verificationCode.value = '';
    verificationModal.value = true;
  }
}

async function handleConfirmVerification() {
  if (!verificationCode.value.trim()) {
    window.$message?.warning('请输入管理员二次验证码');
    return;
  }
  const res = await authStore.login(model.userName, model.password, verificationCode.value.trim());
  if (res?.success) {
    verificationModal.value = false;
  }
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
    <NSpace vertical :size="20">
      <NButton type="primary" size="large" round block :loading="authStore.loginLoading" @click="handleSubmit">
        登录管理后台
      </NButton>
      <NText depth="3" class="text-center text-13px">
        使用现有平台账号登录，身份由服务端安全 Cookie 维护。
      </NText>
    </NSpace>
  </NForm>

  <!-- 管理员安全二次验证弹窗 -->
  <NModal
    v-model:show="verificationModal"
    preset="card"
    title="管理员安全二次验证"
    class="max-w-440px"
    :mask-closable="false"
  >
    <div class="flex flex-col gap-14px">
      <NAlert type="warning" :show-icon="true">
        系统检测到您正在登录超级管理员账号，请输入管理员专属二次验证码以完成身份核验。
      </NAlert>
      <div>
        <label class="mb-6px block text-13px font-medium text-gray-700 dark:text-gray-200">
          管理员二次验证码：
        </label>
        <NInput
          v-model:value="verificationCode"
          type="password"
          show-password-on="click"
          placeholder="请输入超级管理员二次验证码"
          autofocus
          @keyup.enter="handleConfirmVerification"
        />
      </div>
    </div>
    <template #footer>
      <div class="flex justify-end gap-10px">
        <NButton @click="verificationModal = false">取消</NButton>
        <NButton type="primary" :loading="authStore.loginLoading" @click="handleConfirmVerification">
          确认登录
        </NButton>
      </div>
    </template>
  </NModal>
</template>

<style scoped></style>
