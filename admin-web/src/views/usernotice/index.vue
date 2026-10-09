<script setup lang="ts">
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';

defineOptions({ name: 'Usernotice' });
const router = useRouter();

onMounted(() => {
  router.replace('/home');
});
</script>

<template>
  <div></div>
</template>
