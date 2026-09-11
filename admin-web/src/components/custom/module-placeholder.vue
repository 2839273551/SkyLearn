<script setup lang="ts">
interface Props {
  title: string;
  description: string;
  features: string[];
  risk?: 'normal' | 'high';
}

withDefaults(defineProps<Props>(), { risk: 'normal' });
</script>

<template>
  <NSpace vertical :size="16">
    <NCard :bordered="false" class="card-wrapper">
      <NSpace vertical :size="12">
        <div class="flex flex-wrap items-center gap-10px">
          <h2 class="text-22px font-600">{{ title }}</h2>
          <NTag :type="risk === 'high' ? 'warning' : 'info'" round>
            {{ risk === 'high' ? '高风险模块，最后接入' : '新版页面接入中' }}
          </NTag>
        </div>
        <NText depth="3">{{ description }}</NText>
      </NSpace>
    </NCard>
    <NCard title="计划功能" :bordered="false" class="card-wrapper">
      <NList>
        <NListItem v-for="feature in features" :key="feature">
          <template #prefix>
            <SvgIcon icon="ph:check-circle" class="text-18px text-primary" />
          </template>
          {{ feature }}
        </NListItem>
      </NList>
    </NCard>
  </NSpace>
</template>

<style scoped></style>
