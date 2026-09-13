import { request } from '../request';

// ==========================================
// 系统设置 (webset)
// ==========================================

export function fetchSystemSettings() {
  return request<Api.Settings.Payload>({ url: 'admin-api/v1/index.php?action=system-settings' });
}

export function saveSystemSettings(settings: Partial<Api.Settings.Values>) {
  return request<Api.Settings.Payload>({
    url: 'admin-api/v1/index.php?action=system-settings-save',
    method: 'post',
    data: { settings }
  });
}

// ==========================================
// 分类设置 (fenlei)
// ==========================================

export function fetchFenleiList() {
  return request<Api.Fenlei.ListResponse>({ url: 'admin-api/v1/index.php?action=fenlei-list' });
}

export function saveFenlei(data: { id?: string | number; name: string; sort: number; status: number }) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=fenlei-save',
    method: 'post',
    data
  });
}

export function deleteFenlei(id: string | number) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=fenlei-delete',
    method: 'post',
    data: { id }
  });
}

// ==========================================
// 接口配置 (huoyuan)
// ==========================================

export function fetchHuoyuanList() {
  return request<Api.Huoyuan.ListResponse>({ url: 'admin-api/v1/index.php?action=huoyuan-list' });
}

export function saveHuoyuan(data: Partial<Api.Huoyuan.Item> & { pass?: string; token?: string }) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=huoyuan-save',
    method: 'post',
    data
  });
}

export function deleteHuoyuan(hid: string | number) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=huoyuan-delete',
    method: 'post',
    data: { hid }
  });
}

export function fetchHuoyuanBalance(hid: string | number) {
  return request<Api.Huoyuan.BalanceResponse>({
    url: 'admin-api/v1/index.php?action=huoyuan-balance',
    method: 'post',
    data: { hid }
  });
}

// ==========================================
// 网课设置 (class)
// ==========================================

export function fetchClassList(params: {
  page: number;
  pageSize: number;
  keyword?: string;
  fenlei?: string;
  status?: number | string;
}) {
  return request<Api.Class.ListResponse>({
    url: 'admin-api/v1/index.php?action=class-list',
    method: 'get',
    params
  });
}

export function fetchClassOptions() {
  return request<Api.Class.OptionsResponse>({ url: 'admin-api/v1/index.php?action=class-options' });
}

export function saveClass(data: Partial<Api.Class.Item>) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=class-save',
    method: 'post',
    data
  });
}

export function deleteClass(cids: (string | number)[] | string | number) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=class-delete',
    method: 'post',
    data: { cids: Array.isArray(cids) ? cids : [cids] }
  });
}

export function batchUpdateClassStatus(cids: (string | number)[], status: number) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=class-batch-status',
    method: 'post',
    data: { cids, status }
  });
}

export function batchUpdateClassPriceSort(updates: Api.Class.PriceSortUpdate[]) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=class-batch-price-sort',
    method: 'post',
    data: { updates }
  });
}

// ==========================================
// 一键对接 (yjdj)
// ==========================================

export function fetchYjdjRemoteClasses(hid: string | number) {
  return request<Api.Yjdj.RemoteClassesResponse>({
    url: 'admin-api/v1/index.php?action=yjdj-remote-classes',
    method: 'post',
    data: { hid }
  });
}

export function copyYjdjFenlei(hid: string | number, copyMode: 'fenlei_only' | 'fenlei_and_class') {
  return request<Api.Yjdj.CopyFenleiResponse>({
    url: 'admin-api/v1/index.php?action=yjdj-copy-fenlei',
    method: 'post',
    data: { hid, copyMode }
  });
}

export function batchOnlineYjdjClasses(data: {
  hid: string | number;
  courses: Array<{
    cid: string;
    name: string;
    price: string;
    fenleiname?: string;
    content?: string;
  }>;
  categoryMode: 'default' | 'specified' | 'custom';
  categoryId?: string;
  customCategoryName?: string;
}) {
  return request<Api.Yjdj.BatchOnlineResponse>({
    url: 'admin-api/v1/index.php?action=yjdj-batch-online',
    method: 'post',
    data
  });
}

// ==========================================
// 等级设置 (dengji)
// ==========================================

export function fetchDengjiList() {
  return request<Api.Dengji.ListResponse>({ url: 'admin-api/v1/index.php?action=dengji-list' });
}

export function saveDengji(data: Partial<Api.Dengji.Item>) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=dengji-save',
    method: 'post',
    data
  });
}

export function deleteDengji(id: string | number) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=dengji-delete',
    method: 'post',
    data: { id }
  });
}

// ==========================================
// 密价设置 (mijia)
// ==========================================

export function fetchMijiaList(params?: { uid?: string; cid?: string }) {
  return request<Api.Mijia.ListResponse>({
    url: 'admin-api/v1/index.php?action=mijia-list',
    method: 'get',
    params
  });
}

export function saveMijia(data: Partial<Api.Mijia.Item>) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=mijia-save',
    method: 'post',
    data
  });
}

export function deleteMijia(mid: string | number) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=mijia-delete',
    method: 'post',
    data: { mid }
  });
}

// ==========================================
// 支付订单 (paylist)
// ==========================================

export function fetchPaylistList(params: {
  page: number;
  pageSize: number;
  keyword?: string;
  status?: number | string;
  type?: string;
}) {
  return request<Api.Paylist.ListResponse>({
    url: 'admin-api/v1/index.php?action=paylist-list',
    method: 'get',
    params
  });
}

// ==========================================
// 充值卡密 (guanx)
// ==========================================

export function fetchGuanxList(params: {
  page: number;
  pageSize: number;
  status?: number | string;
  batchId?: number | string;
  keyword?: string;
}) {
  return request<Api.Guanx.ListResponse>({
    url: 'admin-api/v1/index.php?action=guanx-list',
    method: 'get',
    params
  });
}

export function generateGuanx(data: { num: number; money: number; batchId?: number }) {
  return request<Api.Guanx.GenerateResponse>({
    url: 'admin-api/v1/index.php?action=guanx-generate',
    method: 'post',
    data
  });
}

export function deleteGuanx(ids: (string | number)[] | string | number) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=guanx-delete',
    method: 'post',
    data: { ids: Array.isArray(ids) ? ids : [ids] }
  });
}

// ==========================================
// 公告列表 (gglist)
// ==========================================

export function fetchGglistList() {
  return request<Api.Gglist.ListResponse>({ url: 'admin-api/v1/index.php?action=gglist-list' });
}

export function saveGglist(data: Partial<Api.Gglist.Item>) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=gglist-save',
    method: 'post',
    data
  });
}

export function deleteGglist(id: string | number) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=gglist-delete',
    method: 'post',
    data: { id }
  });
}

// ==========================================
// 今日数据 (data)
// ==========================================

export function fetchDataStats() {
  return request<Api.DataStats.Stats>({ url: 'admin-api/v1/index.php?action=data-stats' });
}

// ==========================================
// 货源统计 (ddtj)
// ==========================================

export function fetchDdtjStats() {
  return request<Api.DdtjStats.Stats>({ url: 'admin-api/v1/index.php?action=ddtj-stats' });
}

// ==========================================
// 站长帮助 (zzbz)
// ==========================================

export function fetchZzbzInfo() {
  return request<Api.Zzbz.Info>({ url: 'admin-api/v1/index.php?action=zzbz-info' });
}

// ==========================================
// 系统信息 (webmsg)
// ==========================================

export function fetchWebmsgInfo() {
  return request<Api.Webmsg.Info>({ url: 'admin-api/v1/index.php?action=webmsg-info' });
}

// ==========================================
// 我的信息与代理管理
// ==========================================

export function fetchUserlistList(params: { page: number; pageSize: number; keyword?: string; status?: number | string }) {
  return request<{ records: Api.ProfileArea.UserItem[]; total: number; current: number; size: number }>({
    url: 'admin-api/v1/index.php?action=userlist-list',
    method: 'get',
    params
  });
}

export function updateUserStatus(uid: string | number, active: number) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=userlist-status',
    method: 'post',
    data: { uid, active }
  });
}

export function rechargeUserBalance(uid: string | number, amount: number) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=userlist-recharge',
    method: 'post',
    data: { uid, amount }
  });
}

export function updateUserRate(uid: string | number, rate: string) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=userlist-rate',
    method: 'post',
    data: { uid, rate }
  });
}

export function fetchClassLatest() {
  return request<{ list: Api.ProfileArea.LatestClass[] }>({ url: 'admin-api/v1/index.php?action=class-latest' });
}

export function fetchClassOffline(params: { page: number }) {
  return request<{ records: Api.ProfileArea.OfflineClass[]; total: number; current: number; size: number }>({
    url: 'admin-api/v1/index.php?action=class-offline',
    method: 'get',
    params
  });
}

export function fetchRankStats() {
  return request<Api.ProfileArea.RankData>({ url: 'admin-api/v1/index.php?action=rank-stats' });
}

export function fetchKcidCompare(params: { page: number; keyword?: string }) {
  return request<{ records: Api.ProfileArea.KcidRecord[]; total: number; current: number; size: number }>({
    url: 'admin-api/v1/index.php?action=kcid-compare',
    method: 'get',
    params
  });
}

export function fetchOrderAvailable(params: { page: number; keyword?: string }) {
  return request<{ records: Api.ProfileArea.AvailableOrder[]; total: number; current: number; size: number }>({
    url: 'admin-api/v1/index.php?action=order-available',
    method: 'get',
    params
  });
}

export function fetchLogList(params: { page: number; type?: string; keyword?: string }) {
  return request<{ records: Api.ProfileArea.LogItem[]; total: number; current: number; size: number }>({
    url: 'admin-api/v1/index.php?action=log-list',
    method: 'get',
    params
  });
}

export function fetchHelpList() {
  return request<{ list: Api.ProfileArea.HelpItem[] }>({ url: 'admin-api/v1/index.php?action=help-list' });
}

export function fetchMyPriceList() {
  return request<Api.ProfileArea.MyPriceResponse>({ url: 'admin-api/v1/index.php?action=myprice-list' });
}

export function fetchDockingInfo() {
  return request<Api.ProfileArea.DockingInfo>({ url: 'admin-api/v1/index.php?action=docking-info' });
}

export function fetchPchangeList(params: { page: number; cid?: number | string }) {
  return request<Api.ProfileArea.PchangeResponse>({
    url: 'admin-api/v1/index.php?action=pchange-list',
    method: 'get',
    params
  });
}

export function submitPayCard(content: string) {
  return request<{ newBalance: string }>({
    url: 'admin-api/v1/index.php?action=pay-card',
    method: 'post',
    data: { content }
  });
}

export function fetchChargeInfo() {
  return request<Api.ProfileArea.ChargeInfo>({ url: 'admin-api/v1/index.php?action=charge-info' });
}

export function fetchUserProfile() {
  return request<Api.ProfileArea.UserProfile>({ url: 'admin-api/v1/index.php?action=user-profile' });
}

export function updateUserProfile(name: string) {
  return request<{ name: string }>({
    url: 'admin-api/v1/index.php?action=user-profile-save',
    method: 'post',
    data: { name }
  });
}

export function updateUserPassword(data: { oldPassword: string; newPassword: string; confirmPassword: string }) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=user-password-save',
    method: 'post',
    data
  });
}

export function updateUserYqprice(yqprice: string) {
  return request<{ yqprice: string; yqm: string; inviteUrl: string }>({
    url: 'admin-api/v1/index.php?action=user-yqprice-save',
    method: 'post',
    data: { yqprice }
  });
}

export function createUserApiKey() {
  return request<{ key: string; balance: string }>({
    url: 'admin-api/v1/index.php?action=user-api-key-create',
    method: 'post'
  });
}

export function refreshUserApiKey() {
  return request<{ key: string }>({
    url: 'admin-api/v1/index.php?action=user-api-key-refresh',
    method: 'post'
  });
}

export function updateUserPushToken(pushPlusToken: string) {
  return request<{ pushPlusToken: string }>({
    url: 'admin-api/v1/index.php?action=user-push-token-save',
    method: 'post',
    data: { pushPlusToken }
  });
}

export function fetchWorkorderList(params?: { page?: number; pageSize?: number; keyword?: string; status?: string }) {
  return request<Api.ProfileArea.WorkorderListResponse>({
    url: 'admin-api/v1/index.php?action=workorder-list',
    method: 'get',
    params
  });
}

export function createWorkorder(data: { type: 'order' | 'custom'; oid?: number; content: string }) {
  return request<{ gid: string }>({
    url: 'admin-api/v1/index.php?action=workorder-create',
    method: 'post',
    data
  });
}

export function replyWorkorder(data: { gid: number; reply: string }) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=workorder-reply',
    method: 'post',
    data
  });
}

export function finishWorkorder(data: { gid: number; remark?: string }) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=workorder-finish',
    method: 'post',
    data
  });
}

export function deleteWorkorder(gid: number) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=workorder-delete',
    method: 'post',
    data: { gid }
  });
}

// ==========================================
// 对接监控与调用流水 (dockinglog)
// ==========================================

export function fetchDockingLogList(params?: {
  page?: number;
  pageSize?: number;
  direction?: string;
  action_filter?: string;
  keyword?: string;
  status?: number;
  start_time?: string;
  end_time?: string;
}) {
  return request<Api.DockingLog.ListResponse>({
    url: 'admin-api/v1/index.php?action=docking-log-list',
    method: 'get',
    params
  });
}

export function clearDockingLogs(data: { range: 'all' | '7days' | '30days' }) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=docking-log-clear',
    method: 'post',
    data
  });
}

// ==========================================
// 无查提交 / 批量交单 (addtj)
// ==========================================

export function fetchNocheckOptions() {
  return request<Api.Addtj.OptionsResponse>({
    url: 'admin-api/v1/index.php?action=order-nocheck-options',
    method: 'get'
  });
}

export function submitOrderNocheck(data: { cid: number; content: string }) {
  return request<Api.Addtj.SubmitResponse>({
    url: 'admin-api/v1/index.php?action=order-submit-nocheck',
    method: 'post',
    data
  });
}

// ==========================================
// 添加代理 / 开通下级 (adduser)
// ==========================================

export function fetchGradeOptions() {
  return request<Api.Adduser.GradeOptionsResponse>({
    url: 'admin-api/v1/index.php?action=user-grade-options',
    method: 'get'
  });
}

export function createUser(data: { user: string; pass: string; name: string; grade_id: number }) {
  return request<Api.Adduser.CreateUserResponse>({
    url: 'admin-api/v1/index.php?action=user-create',
    method: 'post',
    data
  });
}

// ==========================================
// 自动化任务调度器 (scheduler)
// ==========================================

export function fetchSchedulerTasksList() {
  return request<Api.Scheduler.TasksListResponse>({
    url: 'admin-api/v1/index.php?action=scheduler-tasks-list',
    method: 'get'
  });
}

export function updateSchedulerTask(data: { task_id: string; enabled?: boolean; interval_mins?: number }) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=scheduler-task-update',
    method: 'post',
    data
  });
}

export function runSchedulerTask(task_id: string) {
  return request<Api.Scheduler.RunResult>({
    url: 'admin-api/v1/index.php?action=scheduler-task-run',
    method: 'post',
    data: { task_id }
  });
}

export function runAllSchedulerTasks() {
  return request<{ reports: Api.Scheduler.RunResult[] }>({
    url: 'admin-api/v1/index.php?action=scheduler-run-all',
    method: 'post'
  });
}

export function fetchSchedulerTaskLogs(task_id: string) {
  return request<Api.Scheduler.LogsResponse>({
    url: 'admin-api/v1/index.php?action=scheduler-task-logs',
    method: 'get',
    params: { task_id }
  });
}

export function clearSchedulerTaskLogs(task_id?: string) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=scheduler-task-clear-logs',
    method: 'post',
    data: { task_id }
  });
}
