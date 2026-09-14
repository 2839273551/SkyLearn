import { computed, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import { defineStore } from 'pinia';
import { useLoading } from '@sa/hooks';
import { fetchGetUserInfo, fetchLogin, fetchLogout, fetchSiteInfo } from '@/service/api';
import { useRouterPush } from '@/hooks/common/router';
import { localStg } from '@/utils/storage';
import { SetupStoreId } from '@/enum';
import { $t } from '@/locales';
import { useRouteStore } from '../route';
import { useTabStore } from '../tab';
import { clearAuthStorage, getToken } from './shared';

export const useAuthStore = defineStore(SetupStoreId.Auth, () => {
  const route = useRoute();
  const routeStore = useRouteStore();
  const tabStore = useTabStore();
  const { toLogin, redirectFromLogin } = useRouterPush(false);
  const { loading: loginLoading, startLoading, endLoading } = useLoading();

  const token = ref('');

  const userInfo: Api.Auth.UserInfo = reactive({
    userId: '',
    userName: '',
    displayName: '',
    avatar: '',
    siteName: '',
    balance: '0.00',
    csrfToken: '',
    roles: [],
    buttons: [],
    capabilities: [],
    sykg: false,
    ddggkg: false,
    ddgg: '',
    czph: false,
    qdkg: false,
    hasSignedIn: false
  });

  /** is super role in static route */
  const isStaticSuper = computed(() => {
    const { VITE_AUTH_ROUTE_MODE, VITE_STATIC_SUPER_ROLE } = import.meta.env;

    return VITE_AUTH_ROUTE_MODE === 'static' && userInfo.roles.includes(VITE_STATIC_SUPER_ROLE);
  });

  /** Is login */
  const isLogin = computed(() => Boolean(token.value));

  /** Reset auth store */
  async function resetStore() {
    recordUserId();

    clearAuthStorage();

    token.value = '';
    Object.assign(userInfo, {
      userId: '',
      userName: '',
      displayName: '',
      siteName: '',
      balance: '0.00',
      csrfToken: '',
      roles: [],
      buttons: [],
      capabilities: [],
      sykg: false,
      ddggkg: false,
      ddgg: '',
      czph: false,
      qdkg: false,
      hasSignedIn: false
    });

    if (!route.meta.constant) {
      await toLogin();
    }

    localStg.remove('globalTabs');
    tabStore.clearTabs();
    routeStore.resetStore();
  }

  /** Record the user ID of the previous login session Used to compare with the current user ID on next login */
  function recordUserId() {
    if (!userInfo.userId) {
      return;
    }

    // Store current user ID locally for next login comparison
    localStg.set('lastLoginUserId', userInfo.userId);
  }

  /**
   * Check if current login user is different from previous login user If different, clear all tabs
   *
   * @returns {boolean} Whether to clear all tabs
   */
  function checkTabClear(): boolean {
    localStg.remove('globalTabs');
    tabStore.clearTabs();
    localStg.remove('lastLoginUserId');
    return true;
  }

  /**
   * Login
   *
   * @param userName User name
   * @param password Password
   * @param [redirect=true] Whether to redirect after login. Default is `true`
   */
  async function login(userName: string, password: string, verification = '', redirect = true) {
    startLoading();

    const { data: loginToken, error } = await fetchLogin(userName, password, verification);

    if (!error) {
      const pass = await loginByToken(loginToken);

      if (pass) {
        // Check if the tab needs to be cleared
        const isClear = checkTabClear();
        let needRedirect = redirect;

        if (isClear) {
          // If the tab needs to be cleared,it means we don't need to redirect.
          needRedirect = false;
        }
        await redirectFromLogin(needRedirect);

        window.$notification?.success({
          title: $t('page.login.common.loginSuccess'),
          content: $t('page.login.common.welcomeBack', { userName: userInfo.userName }),
          duration: 4500
        });
      }

      endLoading();
      return { success: true };
    } else {
      resetStore();
    }

    endLoading();
    const code = Number(error.response?.data?.code);
    return {
      success: false,
      needVerification: code === 1002,
      code
    };
  }

  async function loginByToken(loginToken: Api.Auth.LoginToken) {
    // 1. stored in the localStorage, the later requests need it in headers
    localStg.set('token', loginToken.token);
    localStg.set('refreshToken', loginToken.refreshToken);

    // 2. get user info
    const pass = await getUserInfo();

    if (pass) {
      token.value = loginToken.token;

      return true;
    }

    return false;
  }

  async function getUserInfo() {
    const { data: info, error } = await fetchGetUserInfo();

    if (!error) {
      // update store
      Object.assign(userInfo, info);

      return true;
    }

    return false;
  }

  async function initSiteInfo() {
    const { data, error } = await fetchSiteInfo();
    if (!error && data) {
      if (data.siteName) userInfo.siteName = data.siteName;
      if (data.sykg !== undefined) userInfo.sykg = Boolean(data.sykg);
    }
  }

  async function initUserInfo() {
    initSiteInfo();
    const maybeToken = getToken();

    if (maybeToken) {
      token.value = maybeToken;
      const pass = await getUserInfo();

      if (!pass) {
        resetStore();
      }
    }
  }

  async function logout() {
    await fetchLogout();
    await resetStore();
  }

  return {
    token,
    userInfo,
    isStaticSuper,
    isLogin,
    loginLoading,
    resetStore,
    logout,
    login,
    initUserInfo,
    initSiteInfo
  };
});
