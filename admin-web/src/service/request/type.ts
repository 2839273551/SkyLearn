export interface RequestInstanceState {
  /** the promise of refreshing token */
  refreshTokenPromise: Promise<boolean> | null;
  /** the promise of refreshing csrf token */
  refreshCsrfPromise?: Promise<boolean> | null;
  /** the request error message stack */
  errMsgStack: string[];
  [key: string]: unknown;
}
