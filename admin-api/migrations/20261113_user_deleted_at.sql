-- 在发布包含 userlist-delete 的接口之前执行。
ALTER TABLE qingka_wangke_user
    ADD COLUMN deleted_at DATETIME DEFAULT NULL COMMENT '代理删除时间，保留关联订单与流水';
