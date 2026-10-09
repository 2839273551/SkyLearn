<script setup lang="ts">
import { h, onMounted, reactive, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { NButton, NPopconfirm, NSpace, NSwitch, NTag } from 'naive-ui';
import { deleteHuoyuan, fetchHuoyuanBalance, fetchHuoyuanList, saveHuoyuan } from '@/service/api';

defineOptions({ name: 'Huoyuan' });

const loading = ref(false);
const submitting = ref(false);
const balanceLoadingHid = ref<string | null>(null);
const list = ref<Api.Huoyuan.Item[]>([]);
const platformOptions = ref<Api.Huoyuan.PlatformOption[]>([]);
const modalVisible = ref(false);
const modalTitle = ref('添加接口配置');

const formModel = reactive<{
  hid: string;
  name: string;
  pt: string;
  url: string;
  user: string;
  pass: string;
  token: string;
  ip: string;
  cookie: string;
  status: number;
}>({
  hid: '',
  name: '',
  pt: '2xx',
  url: '',
  user: '',
  pass: '',
  token: '',
  ip: '',
  cookie: '',
  status: 1
});

const columns: DataTableColumns<Api.Huoyuan.Item> = [
  { title: 'ID', key: 'hid', width: 80 },
  { title: '接口名称', key: 'name', minWidth: 140 },
  {
    title: '平台类型',
    key: 'ptName',
    width: 130,
    render: row => h(NTag, { type: 'primary', size: 'small', round: true }, { default: () => row.ptName || row.pt })
  },
  { title: '接口域名/网址', key: 'url', minWidth: 160, ellipsis: { tooltip: true } },
  { title: '账号 / UID', key: 'user', width: 130, ellipsis: { tooltip: true } },
  {
    title: '凭据状态',
    key: 'credentials',
    width: 140,
    render: row =>
      h(NSpace, { size: 'small' }, () => [
        h(NTag, { type: row.hasPass ? 'success' : 'warning', size: 'small' }, { default: () => (row.hasPass ? '密码已设' : '无密码') }),
        h(NTag, { type: row.hasToken ? 'info' : 'default', size: 'small' }, { default: () => (row.hasToken ? 'Token已设' : '无Token') })
      ])
  },
  {
    title: '状态',
    key: 'status',
    width: 110,
    render: row =>
      h(
        NSwitch,
        {
          value: row.status === 1,
          onUpdateValue: async (val: boolean) => {
            const newStatus = val ? 1 : 0;
            const res = await saveHuoyuan({
              hid: row.hid,
              name: row.name,
              pt: row.pt,
              url: row.url,
              user: row.user,
              ip: row.ip,
              cookie: row.cookie,
              status: newStatus
            });
            if (res !== null) {
              row.status = newStatus;
              window.$message?.success(val ? '已启用接口' : '已停用接口');
            }
          }
        },
        { checked: () => '启用', unchecked: () => '停用' }
      )
  },
  { title: '添加时间', key: 'addtime', width: 170, ellipsis: { tooltip: true } },
  {
    title: '操作',
    key: 'actions',
    width: 220,
    fixed: 'right',
    render: row =>
      h(NSpace, { size: 'small' }, () => [
        h(
          NButton,
          {
            size: 'small',
            type: 'info',
            ghost: true,
            loading: balanceLoadingHid.value === row.hid,
            onClick: () => handleCheckBalance(row)
          },
          { default: () => '查余额' }
        ),
        h(
          NButton,
          {
            size: 'small',
            type: 'primary',
            ghost: true,
            onClick: () => openEditModal(row)
          },
          { default: () => '编辑' }
        ),
        h(
          NPopconfirm,
          {
            onPositiveClick: () => handleDelete(row.hid)
          },
          {
            default: () => `确定删除接口【${row.name}】吗？若有网课正使用此接口将无法删除。`,
            trigger: () =>
              h(
                NButton,
                {
                  size: 'small',
                  type: 'error',
                  ghost: true
                },
                { default: () => '删除' }
              )
          }
        )
      ])
  }
];

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchHuoyuanList();
  if (!error && data) {
    list.value = data.list;
    platformOptions.value = data.platformOptions;
  }
  loading.value = false;
}

function openAddModal() {
  modalTitle.value = '添加接口配置';
  formModel.hid = '';
  formModel.name = '';
  formModel.pt = platformOptions.value.length ? platformOptions.value[0].value : '2xx';
  formModel.url = '';
  formModel.user = '';
  formModel.pass = '';
  formModel.token = '';
  formModel.ip = '';
  formModel.cookie = '';
  formModel.status = 1;
  modalVisible.value = true;
}

function openEditModal(row: Api.Huoyuan.Item) {
  modalTitle.value = '编辑接口配置';
  formModel.hid = row.hid;
  formModel.name = row.name;
  formModel.pt = row.pt;
  formModel.url = row.url;
  formModel.user = row.user;
  formModel.pass = '';
  formModel.token = '';
  formModel.ip = row.ip;
  formModel.cookie = row.cookie;
  formModel.status = row.status;
  modalVisible.value = true;
}

async function handleCheckBalance(row: Api.Huoyuan.Item) {
  balanceLoadingHid.value = row.hid;
  const { data, error } = await fetchHuoyuanBalance(row.hid);
  balanceLoadingHid.value = null;

  if (!error && data) {
    window.$dialog?.info({
      title: '上游接口余额',
      content: `接口【${data.name}】当前实时可用余额为：¥ ${data.balance} 元`,
      positiveText: '知道了'
    });
  }
}

async function handleSubmit() {
  if (!formModel.name.trim()) {
    window.$message?.warning('请输入接口自定义名称');
    return;
  }
  if (!formModel.pt) {
    window.$message?.warning('请选择平台类型');
    return;
  }

  submitting.value = true;
  const res = await saveHuoyuan({
    hid: formModel.hid ? formModel.hid : undefined,
    name: formModel.name.trim(),
    pt: formModel.pt,
    url: formModel.url.trim(),
    user: formModel.user.trim(),
    pass: formModel.pass.trim() ? formModel.pass.trim() : undefined,
    token: formModel.token.trim() ? formModel.token.trim() : undefined,
    ip: formModel.ip.trim(),
    cookie: formModel.cookie.trim(),
    status: formModel.status
  });
  submitting.value = false;

  if (res !== null) {
    window.$message?.success(formModel.hid ? '接口修改成功' : '接口添加成功');
    modalVisible.value = false;
    loadData();
  }
}

async function handleDelete(hid: string) {
  const res = await deleteHuoyuan(hid);
  if (res !== null) {
    window.$message?.success('删除成功');
    loadData();
  }
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="接口配置" :bordered="false" class="rounded-8px shadow-sm">
      <template #header-extra>
        <NSpace>
          <NButton type="primary" @click="openAddModal">
            <template #icon>
              <icon-ic-round-plus class="text-18px" />
            </template>
            添加接口
          </NButton>
          <NButton :loading="loading" @click="loadData">
            <template #icon>
              <icon-ic-round-refresh class="text-18px" />
            </template>
            刷新
          </NButton>
        </NSpace>
      </template>

      <NDataTable
        :loading="loading"
        :columns="columns"
        :data="list"
        :row-key="(row: Api.Huoyuan.Item) => row.hid"
        :pagination="false"
        striped
      />
    </NCard>

    <NModal
      v-model:show="modalVisible"
      preset="card"
      :title="modalTitle"
      class="max-w-650px"
      :mask-closable="false"
    >
      <NForm :model="formModel" label-placement="left" label-width="110">
        <NGrid cols="1 m:2" responsive="screen" :x-gap="16">
          <NGi>
            <NFormItem label="自定义名称" required>
              <NInput v-model:value="formModel.name" placeholder="如：主线爱学习、备用27" />
            </NFormItem>
          </NGi>
          <NGi>
            <NFormItem label="平台类型" required>
              <NSelect v-model:value="formModel.pt" :options="platformOptions" placeholder="选择上游系统标识" />
            </NFormItem>
          </NGi>
          <NGi span="1 m:2">
            <NFormItem label="域名 / 网址">
              <NInput v-model:value="formModel.url" placeholder="如：http://api.upstream.com/ (不带多余子路径)" />
            </NFormItem>
          </NGi>
          <NGi>
            <NFormItem label="账号 / UID">
              <NInput v-model:value="formModel.user" placeholder="输入账号或UID (27系统填UID)" />
            </NFormItem>
          </NGi>
          <NGi>
            <NFormItem label="密码 / KEY">
              <NInput
                v-model:value="formModel.pass"
                type="password"
                show-password-on="click"
                :placeholder="formModel.hid ? '留空表示保持原有密码不变' : '输入密码或KEY'"
              />
            </NFormItem>
          </NGi>
          <NGi span="1 m:2">
            <NFormItem label="密钥 / Token">
              <NInput
                v-model:value="formModel.token"
                type="password"
                show-password-on="click"
                :placeholder="formModel.hid ? '留空表示保持原有Token不变' : '输入上游Token凭据（若有）'"
              />
            </NFormItem>
          </NGi>
          <NGi>
            <NFormItem label="指定IP">
              <NInput v-model:value="formModel.ip" placeholder="留空默认服务器IP" />
            </NFormItem>
          </NGi>
          <NGi>
            <NFormItem label="状态">
              <NSwitch v-model:value="formModel.status" :checked-value="1" :unchecked-value="0">
                <template #checked>启用</template>
                <template #unchecked>停用</template>
              </NSwitch>
            </NFormItem>
          </NGi>
          <NGi span="1 m:2">
            <NFormItem label="Cookie">
              <NInput v-model:value="formModel.cookie" type="textarea" :autosize="{ minRows: 2, maxRows: 4 }" placeholder="特殊平台需要的附加Cookie信息（普通对接可留空）" />
            </NFormItem>
          </NGi>
        </NGrid>
      </NForm>

      <template #footer>
        <div class="flex justify-end gap-12px">
          <NButton @click="modalVisible = false">取消</NButton>
          <NButton type="primary" :loading="submitting" @click="handleSubmit">保存</NButton>
        </div>
      </template>
    </NModal>
  </div>
</template>
