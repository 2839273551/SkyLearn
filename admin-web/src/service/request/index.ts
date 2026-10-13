import type { AxiosResponse, InternalAxiosRequestConfig } from 'axios';
import { BACKEND_ERROR_CODE, createFlatRequest } from '@sa/axios';
import { useAuthStore } from '@/store/modules/auth';
import { getServiceBaseURL } from '@/utils/service';
import { showErrorMsg } from './shared';
import type { RequestInstanceState } from './type';

const isHttpProxy = import.meta.env.DEV && import.meta.env.VITE_HTTP_PROXY === 'Y';
const { baseURL } = getServiceBaseURL(import.meta.env, isHttpProxy);

export const request = createFlatRequest(
  {
    baseURL,
    timeout: 60 * 1000,
    withCredentials: true,
    headers: { Accept: 'application/json' }
  },
  {
    defaultState: {
      errMsgStack: [],
      refreshTokenPromise: null
    } as RequestInstanceState,
    transform(response: AxiosResponse<App.Service.Response<any>>) {
      return response.data.data;
    },
    async onRequest(config) {
      config.headers.set('X-Requested-With', 'XMLHttpRequest');
      const csrfToken = useAuthStore().userInfo.csrfToken;
      if (csrfToken) config.headers.set('X-CSRF-Token', csrfToken);
      return config;
    },
    isBackendSuccess(response) {
      return String(response.data.code) === import.meta.env.VITE_SERVICE_SUCCESS_CODE;
    },
    async onBackendFail(response) {
      const responseCode = String(response.data.code);
      const logoutCodes = import.meta.env.VITE_SERVICE_LOGOUT_CODES?.split(',').filter(Boolean) || [];

      if (logoutCodes.includes(responseCode)) {
        await useAuthStore().resetStore();
      }

      return null;
    },
    async onHttpError(error, instance) {
      const status = error.response?.status;
      const config = error.config as (InternalAxiosRequestConfig & { _isCsrfRetry?: boolean }) | undefined;

      // 419 CSRF 页面验证失效容错：静默拉取当前会话最新 CSRF Token 并自动重试请求
      if (status === 419 && config && !config._isCsrfRetry) {
        config._isCsrfRetry = true;

        if (!request.state.refreshCsrfPromise) {
          request.state.refreshCsrfPromise = (async () => {
            try {
              const res = await instance.get<App.Service.Response<Api.Auth.UserInfo>>(
                'admin-api/v1/index.php?action=session',
                { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
              );
              const info = res?.data?.data;
              if (info?.csrfToken) {
                const authStore = useAuthStore();
                Object.assign(authStore.userInfo, info);
                return true;
              }
              return false;
            } catch (err: any) {
              if (err?.response?.status === 401) {
                await useAuthStore().resetStore();
              }
              return false;
            } finally {
              request.state.refreshCsrfPromise = null;
            }
          })();
        }

        const success = await request.state.refreshCsrfPromise;
        if (success) {
          const newCsrf = useAuthStore().userInfo.csrfToken;
          if (newCsrf && config.headers) {
            config.headers.set('X-CSRF-Token', newCsrf);
          }
          return instance(config);
        }
      }

      return null;
    },
    async onError(error) {
      if (error.response?.status === 401) {
        await useAuthStore().resetStore();
        return;
      }

      if (Number(error.response?.data?.code) === 1002) {
        return;
      }

      let message = error.message;
      if (error.code === BACKEND_ERROR_CODE) {
        message = error.response?.data?.msg || message;
      } else if (error.response?.data?.msg) {
        message = error.response.data.msg;
      }

      showErrorMsg(request.state, message);
    }
  }
);
