import { request } from '../request';

/**
 * Login
 *
 * @param userName User name
 * @param password Password
 */
export function fetchLogin(userName: string, password: string, verification = '') {
  return request<Api.Auth.LoginToken>({
    url: 'admin-api/v1/index.php?action=login',
    method: 'post',
    data: {
      userName,
      password,
      verification
    }
  });
}

/** Get user info */
export function fetchGetUserInfo() {
  return request<Api.Auth.UserInfo>({ url: 'admin-api/v1/index.php?action=session' });
}

/** End the current cookie session. */
export function fetchLogout() {
  return request<null>({ url: 'admin-api/v1/index.php?action=logout', method: 'post' });
}

/** Get public site info */
export function fetchSiteInfo() {
  return request<{ siteName: string; logo?: string; keywords?: string; description?: string; sykg?: boolean }>({
    url: 'admin-api/v1/index.php?action=site-info'
  });
}

/** User daily sign in */
export function fetchUserSignIn() {
  return request<{ balance: string; freeAdd: number; hasSignedIn: boolean }>({
    url: 'admin-api/v1/index.php?action=user-signin',
    method: 'post'
  });
}

/**
 * return custom backend error
 *
 * @param code error code
 * @param msg error message
 */
export function fetchCustomBackendError(code: string, msg: string) {
  return request({ url: 'admin-api/v1/index.php?action=error', params: { code, msg } });
}
