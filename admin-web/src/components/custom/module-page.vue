<script setup lang="ts">
defineProps<{ title: string }>();
</script>

<template>
  <section class="min-h-320px flex flex-col gap-24px p-16px">
    <h1 class="m-0 text-22px font-600">{{ title }}</h1>
    <div class="flex-1 flex-center py-48px">
      <NEmpty description="暂无内容" />
    </div>
  </section>
</template>
