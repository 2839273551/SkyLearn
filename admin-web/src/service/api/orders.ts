import { request } from '../request';

export function fetchOrders(params: Api.Orders.Query) {
  return request<Api.Orders.Page>({
    url: 'admin-api/v1/index.php?action=orders',
    method: 'get',
    params
  });
}

export function syncOrderProgress(oid: string | number) {
  return request<{ oid: number; process: string; status: string; remarks: string }>({
    url: 'admin-api/v1/index.php?action=order-sync',
    method: 'post',
    data: { oid: Number(oid) }
  });
}

export function rebrushOrder(oid: string | number) {
  return request<{ oid: number; status: string }>({
    url: 'admin-api/v1/index.php?action=order-rebrush',
    method: 'post',
    data: { oid: Number(oid) }
  });
}

export function dockOrder(oid: string | number) {
  return request<{ dockstatus: string; status: string }>({
    url: 'admin-api/v1/index.php?action=order-dock',
    method: 'post',
    data: { oid: Number(oid) }
  });
}
