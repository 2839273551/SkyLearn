import { request } from '../request';

export function fetchOrderCatalog(categoryId = '') {
  return request<Api.OrderEntry.Catalog>({
    url: 'admin-api/v1/index.php?action=order-catalog',
    method: 'get',
    params: { categoryId }
  });
}

export function fetchCourseQuery(productId: string, accounts: string[]) {
  return request<Api.OrderEntry.QueryResponse>({
    url: 'admin-api/v1/index.php?action=course-query',
    method: 'post',
    data: { productId, accounts }
  });
}

export function fetchOrderSubmit(productId: string, selections: Api.OrderEntry.Selection[]) {
  return request<Api.OrderEntry.SubmitResponse>({
    url: 'admin-api/v1/index.php?action=order-submit',
    method: 'post',
    data: { productId, selections }
  });
}
