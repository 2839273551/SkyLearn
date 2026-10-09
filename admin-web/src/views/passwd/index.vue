<script setup lang="ts">
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/store/modules/auth';
import { updateUserPassword } from '@/service/api';

defineOptions({ name: 'Passwd' });

const router = useRouter();
const authStore = useAuthStore();
const submitting = ref(false);

const form = reactive({
  oldPassword: '',
  newPassword: '',
  confirmPassword: ''
});

async function handleSubmit() {
  if (!form.oldPassword) {
    window.$message?.warning('请输入原登录密码');
    return;
  }
  if (!form.newPassword || form.newPassword.length < 6) {
    window.$message?.warning('新密码长度不能少于 6 位');
    return;
  }
  if (form.newPassword !== form.confirmPassword) {
    window.$message?.warning('两次输入的新密码不一致');
    return;
  }

  submitting.value = true;
  const { error } = await updateUserPassword({
    oldPassword: form.oldPassword,
    newPassword: form.newPassword,
    confirmPassword: form.confirmPassword
  });
  submitting.value = false;

  if (!error) {
    window.$message?.success('密码修改成功，请牢记新密码！');
    form.oldPassword = '';
    form.newPassword = '';
    form.confirmPassword = '';
  }
}
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="修改登录密码" :bordered="false" class="max-w-600px rounded-8px shadow-sm">
      <div class="flex flex-col gap-18px">
        <NAlert type="info">
          当前登录账号：<strong>{{ authStore.userInfo.userName }}</strong>（显示名称：{{ authStore.userInfo.displayName || authStore.userInfo.userName }}）
        </NAlert>

        <div class="flex flex-col gap-14px">
          <div>
            <label class="mb-6px block text-13px font-medium">当前原密码：</label>
            <NInput
              v-model:value="form.oldPassword"
              type="password"
              show-password-on="click"
              placeholder="请输入当前正在使用的旧密码"
            />
          </div>

          <div>
            <label class="mb-6px block text-13px font-medium">设置新密码：</label>
            <NInput
              v-model:value="form.newPassword"
              type="password"
              show-password-on="click"
              placeholder="请输入长度不低于 6 位的新密码"
            />
          </div>

          <div>
            <label class="mb-6px block text-13px font-medium">再次确认新密码：</label>
            <NInput
              v-model:value="form.confirmPassword"
              type="password"
              show-password-on="click"
              placeholder="请再次输入上方新密码"
              @keyup.enter="handleSubmit"
            />
          </div>
        </div>

        <div class="flex items-center gap-12px pt-6px">
          <NButton type="primary" size="large" :loading="submitting" @click="handleSubmit">
            确认修改密码
          </NButton>
          <NButton size="large" @click="router.push('/userinfo')">
            返回个人资料
          </NButton>
        </div>

        <div class="rounded-6px border border-gray-100 bg-gray-50 p-12px text-12px text-gray-500 dark:border-dark-400 dark:bg-dark-600">
          <p class="m-0 font-bold text-gray-700 dark:text-gray-300">安全提示：</p>
          <p class="m-0 mt-4px">1. 密码修改成功后，系统已平滑续签当前登录会话凭证，您无需重新登录；</p>
          <p class="m-0 mt-4px">2. 请避免使用纯数字、连续字符或与其他网站相同的弱口令密码；</p>
          <p class="m-0 mt-4px">3. 站长与管理员绝不会向您索要登录密码，请注意防范钓鱼及假冒客服欺诈。</p>
        </div>
      </div>
    </NCard>
  </div>
</template>

