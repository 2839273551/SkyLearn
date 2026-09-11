import type { AxiosResponse } from 'axios';
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
    async onError(error) {
      if (error.response?.status === 401) {
        await useAuthStore().resetStore();
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
