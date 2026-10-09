<script setup lang="ts">
import { useAuthStore } from '@/store/modules/auth';

defineOptions({
  name: 'GlobalFooter'
});

const authStore = useAuthStore();
</script>

<template>
  <DarkModeContainer class="h-full flex-center">
    <span>{{ authStore.userInfo.siteName || '网课管理中心' }} · 统一后台</span>
  </DarkModeContainer>
</template>

<style scoped></style>
