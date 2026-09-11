import { request } from '../request';

export function fetchOrders(params: Api.Orders.Query) {
  return request<Api.Orders.Page>({
    url: 'admin-api/v1/index.php?action=orders',
    method: 'get',
    params
  });
}
