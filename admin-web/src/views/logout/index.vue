<script setup lang="ts">
import { onMounted } from 'vue';
import { useAuthStore } from '@/store/modules/auth';
onMounted(() => useAuthStore().logout());
</script>
<template><NResult status="success" title="正在退出" description="登录状态即将结束" /></template>
