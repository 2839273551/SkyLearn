import type { Router } from 'vue-router';
import { useTitle } from '@vueuse/core';
import { $t } from '@/locales';
import { useAuthStore } from '@/store/modules/auth';

export function createDocumentTitleGuard(router: Router) {
  router.afterEach(to => {
    const { i18nKey, title } = to.meta;

    const documentTitle = i18nKey ? $t(i18nKey) : title;
    const authStore = useAuthStore();
    const siteTitle = authStore.userInfo.siteName || '网课管理中心';

    useTitle(documentTitle ? `${documentTitle} - ${siteTitle}` : siteTitle);
  });
}
