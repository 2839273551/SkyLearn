import type { RequestInstanceState } from './type';

export function showErrorMsg(state: RequestInstanceState, message: string) {
  if (!state.errMsgStack?.length) {
    state.errMsgStack = [];
  }

  if (state.errMsgStack.includes(message)) return;

  state.errMsgStack.push(message);
  window.$message?.error(message, {
    onLeave: () => {
      state.errMsgStack = state.errMsgStack.filter(item => item !== message);
    }
  });
}
