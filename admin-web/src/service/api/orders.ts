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

export function batchUpdateOrderStatus(oids: (string | number)[], status: string, type: 1 | 2 = 1) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=order-batch-status',
    method: 'post',
    data: { oids: oids.map(Number), status, type }
  });
}

export function batchRefundOrders(oids: (string | number)[]) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=order-batch-refund',
    method: 'post',
    data: { oids: oids.map(Number) }
  });
}

export function batchDeleteOrders(oids: (string | number)[]) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=order-batch-delete',
    method: 'post',
    data: { oids: oids.map(Number) }
  });
}

export function batchSyncOrders(oids: (string | number)[]) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=order-batch-sync',
    method: 'post',
    data: { oids: oids.map(Number) }
  });
}

export function batchRebrushOrders(oids: (string | number)[]) {
  return request<null>({
    url: 'admin-api/v1/index.php?action=order-batch-rebrush',
    method: 'post',
    data: { oids: oids.map(Number) }
  });
}
