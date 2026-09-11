import { request } from '../request';

/** Load the summary displayed on the management home page. */
export function fetchDashboard() {
  return request<Api.Dashboard.Summary>({ url: 'admin-api/v1/index.php?action=dashboard' });
}
