<script setup lang="ts">
import { computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useTitle } from '@vueuse/core';
import { NConfigProvider, darkTheme } from 'naive-ui';
import type { WatermarkProps } from 'naive-ui';
import { useAppStore } from './store/modules/app';
import { useThemeStore } from './store/modules/theme';
import { useAuthStore } from './store/modules/auth';
import { naiveDateLocales, naiveLocales } from './locales/naive';
import { $t } from './locales';

defineOptions({
  name: 'App'
});

const route = useRoute();
const appStore = useAppStore();
const themeStore = useThemeStore();
const authStore = useAuthStore();

onMounted(() => {
  authStore.initSiteInfo();
});

watch(
  () => authStore.userInfo.siteName,
  newSiteName => {
    if (newSiteName) {
      const { i18nKey, title } = route.meta;
      const pageTitle = i18nKey ? $t(i18nKey) : title;
      useTitle(pageTitle ? `${pageTitle} - ${newSiteName}` : newSiteName);
    }
  }
);

const naiveDarkTheme = computed(() => (themeStore.darkMode ? darkTheme : undefined));

const naiveLocale = computed(() => {
  return naiveLocales[appStore.locale];
});

const naiveDateLocale = computed(() => {
  return naiveDateLocales[appStore.locale];
});

const showWatermark = computed(() => {
  return Boolean(authStore.userInfo.sykg);
});

const watermarkProps = computed<WatermarkProps>(() => {
  const text = `${authStore.userInfo.userName || ''} ${authStore.userInfo.siteName || ''}`.trim() || '网课管理中心';
  return {
    content: text,
    cross: true,
    fullscreen: true,
    fontSize: 15,
    lineHeight: 16,
    width: 320,
    height: 240,
    xOffset: 12,
    yOffset: 60,
    rotate: -15,
    zIndex: 9999
  };
});
</script>

<template>
  <NConfigProvider
    :theme="naiveDarkTheme"
    :theme-overrides="themeStore.naiveTheme"
    :locale="naiveLocale"
    :date-locale="naiveDateLocale"
    class="h-full"
  >
    <AppProvider>
      <RouterView class="bg-layout" />
      <NWatermark v-if="showWatermark" v-bind="watermarkProps" />
    </AppProvider>
  </NConfigProvider>
</template>

<style scoped></style>
