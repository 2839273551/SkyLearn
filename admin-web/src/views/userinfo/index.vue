<script setup lang="ts">
import { computed } from 'vue';
import { useAuthStore } from '@/store/modules/auth';

defineOptions({ name: 'Userinfo' });

const authStore = useAuthStore();
const isSuper = computed(() => authStore.userInfo.roles.includes('R_SUPER'));
</script>

<template>
  <NGrid cols="1 m:3" responsive="screen" :x-gap="16" :y-gap="16">
    <NGi>
      <NCard :bordered="false" class="card-wrapper text-center">
        <div class="mx-auto size-88px flex-center rd-1/2 bg-primary/12 text-primary">
          <SvgIcon icon="ph:user-circle" class="text-52px" />
        </div>
        <h2 class="mt-16px text-22px font-600">
          {{ authStore.userInfo.displayName || authStore.userInfo.userName }}
        </h2>
        <NText depth="3">{{ authStore.userInfo.userName }}</NText>
        <div class="mt-12px">
          <NTag :type="isSuper ? 'error' : 'info'" round>{{ isSuper ? '超级管理员' : '代理用户' }}</NTag>
        </div>
      </NCard>
    </NGi>
    <NGi span="1 m:2">
      <NCard title="我的资料" :bordered="false" class="card-wrapper">
        <NDescriptions label-placement="left" :column="1" bordered>
          <NDescriptionsItem label="用户 UID">{{ authStore.userInfo.userId }}</NDescriptionsItem>
          <NDescriptionsItem label="登录账号">{{ authStore.userInfo.userName }}</NDescriptionsItem>
          <NDescriptionsItem label="显示名称">{{ authStore.userInfo.displayName || '-' }}</NDescriptionsItem>
          <NDescriptionsItem label="所属站点">{{ authStore.userInfo.siteName || '-' }}</NDescriptionsItem>
          <NDescriptionsItem label="账户余额">¥ {{ authStore.userInfo.balance }}</NDescriptionsItem>
          <NDescriptionsItem label="可用权限">
            <NSpace>
              <NTag v-for="item in authStore.userInfo.capabilities" :key="item" size="small">{{ item }}</NTag>
            </NSpace>
          </NDescriptionsItem>
        </NDescriptions>
        <NAlert class="mt-16px" type="info" :show-icon="true">
          资料修改和密码修改将在服务端权限校验完成后接入，当前页面只展示安全字段。
        </NAlert>
      </NCard>
    </NGi>
  </NGrid>
</template>

<style scoped></style>
