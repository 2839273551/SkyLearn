<script setup lang="ts">
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { NCard, NSpin } from 'naive-ui';

defineOptions({ name: 'Home' });

const router = useRouter();

onMounted(() => {
  router.replace({ path: '/main' });
});
</script>

<template>
  <div class="flex h-300px items-center justify-center p-16px">
    <NCard :bordered="false" class="max-w-400px rounded-12px shadow-sm text-center">
      <NSpin size="medium" description="正在前往主页公告中心..." />
    </NCard>
  </div>
</template>
