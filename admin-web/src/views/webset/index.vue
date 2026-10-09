<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { fetchSystemSettings, saveSystemSettings } from '@/service/api';
import { useAuthStore } from '@/store/modules/auth';

defineOptions({ name: 'Webset' });

const authStore = useAuthStore();
const loading = ref(false);
const saving = ref(false);
const hasEpayKey = ref(false);
const activeTab = ref('website');

const form = reactive<Api.Settings.Values>({
  sitename: '',
  keywords: '',
  description: '',
  logo: '',
  sykg: '0',
  ddggkg: '0',
  czph: '0',
  qdkg: '0',
  notice: '',
  ddgg: '',
  tcgonggao: '',
  zsgonggao: '',
  sjqykg: '0',
  user_yqzc: '0',
  user_htkh: '0',
  user_ktmoney: '',
  zxczkg: '0',
  zdpay: '',
  is_qqpay: '0',
  is_wxpay: '0',
  is_alipay: '0',
  epay_api: '',
  epay_pid: '',
  epay_key: '',
  yqjl: '',
  yqsq: '',
  yqsx: '',
  flkg: '0',
  fllx: '2',
  zddy: '',
  zdxd: '',
  ckkg: '0',
  xdkg: '0',
  zzqq: '',
  zzvx: ''
});

function syncStoreSwitches() {
  authStore.userInfo.siteName = form.sitename;
  authStore.userInfo.sykg = form.sykg === '1';
  authStore.userInfo.ddggkg = form.ddggkg === '1';
  authStore.userInfo.ddgg = form.ddgg;
  authStore.userInfo.czph = form.czph === '1';
  authStore.userInfo.qdkg = form.qdkg === '1';
}

async function loadSettings() {
  loading.value = true;
  const { data, error } = await fetchSystemSettings();
  if (!error && data) {
    Object.assign(form, data.settings, { epay_key: '' });
    hasEpayKey.value = data.hasEpayKey;
    syncStoreSwitches();
  }
  loading.value = false;
}

async function saveSettings() {
  saving.value = true;
  const { data, error } = await saveSystemSettings(form);
  if (!error && data) {
    Object.assign(form, data.settings, { epay_key: '' });
    hasEpayKey.value = data.hasEpayKey;
    syncStoreSwitches();
    window.$notification?.success({ title: '系统设置', content: '配置保存成功', duration: 3000 });
  }
  saving.value = false;
}

onMounted(loadSettings);
</script>

<template>
  <NSpace vertical :size="16">
    <NCard :bordered="false" class="card-wrapper">
      <div class="flex flex-wrap items-center justify-between gap-12px">
        <div>
          <h2 class="text-22px font-600">系统设置</h2>
          <NText depth="3">原 `/index/webset` 的全部配置已迁移到新版界面。</NText>
        </div>
        <NTag type="error" round>仅超级管理员可修改</NTag>
      </div>
    </NCard>

    <NAlert type="warning" :show-icon="true">
      支付配置、注册机制和课程开关会直接影响线上业务。商户 KEY 不会回显，留空保存表示保持原值。
    </NAlert>

    <NSpin :show="loading">
      <NCard :bordered="false" class="card-wrapper">
        <NForm :model="form" label-placement="top">
          <NTabs v-model:value="activeTab" type="line" animated pane-class="pt-18px">
            <NTabPane name="website" tab="网站配置">
              <NGrid cols="1 m:2" responsive="screen" :x-gap="16">
                <NGi>
                  <NFormItem label="站点名字">
                    <NInput v-model:value="form.sitename" placeholder="请输入站点名字" />
                  </NFormItem>
                </NGi>
                <NGi>
                  <NFormItem label="LOGO 地址">
                    <NInput v-model:value="form.logo" placeholder="请输入 LOGO 地址" />
                  </NFormItem>
                </NGi>
                <NGi>
                  <NFormItem label="SEO 关键词">
                    <NInput v-model:value="form.keywords" placeholder="多个关键词可用逗号分隔" />
                  </NFormItem>
                </NGi>
                <NGi>
                  <NFormItem label="SEO 介绍">
                    <NInput v-model:value="form.description" placeholder="请输入网站介绍" />
                  </NFormItem>
                </NGi>
                <NGi>
                  <NFormItem label="功能开关">
                    <NSpace vertical :size="12">
                      <div class="flex items-center gap-8px">
                        <NSwitch v-model:value="form.sykg" checked-value="1" unchecked-value="0" />
                        <span>防伪水印</span>
                      </div>
                      <div class="flex items-center gap-8px">
                        <NSwitch v-model:value="form.ddggkg" checked-value="1" unchecked-value="0" />
                        <span>订单公告</span>
                      </div>
                      <div class="flex items-center gap-8px">
                        <NSwitch v-model:value="form.czph" checked-value="1" unchecked-value="0" />
                        <span>充值排行榜</span>
                      </div>
                      <div class="flex items-center gap-8px">
                        <NSwitch v-model:value="form.qdkg" checked-value="1" unchecked-value="0" />
                        <span>每日签到</span>
                      </div>
                    </NSpace>
                  </NFormItem>
                </NGi>
                <NGi span="1 m:2">
                  <NFormItem label="公告">
                    <NInput v-model:value="form.notice" type="textarea" :autosize="{ minRows: 5, maxRows: 12 }" />
                  </NFormItem>
                </NGi>
                <NGi span="1 m:2">
                  <NFormItem label="订单公告">
                    <NInput v-model:value="form.ddgg" type="textarea" :autosize="{ minRows: 4, maxRows: 10 }" />
                  </NFormItem>
                </NGi>
                <NGi span="1 m:2">
                  <NFormItem label="弹窗公告">
                    <NInput v-model:value="form.tcgonggao" type="textarea" :autosize="{ minRows: 4, maxRows: 10 }" />
                  </NFormItem>
                </NGi>
                <NGi span="1 m:2">
                  <NFormItem label="直属公告">
                    <NInput v-model:value="form.zsgonggao" type="textarea" :autosize="{ minRows: 4, maxRows: 10 }" />
                  </NFormItem>
                </NGi>
              </NGrid>
            </NTabPane>

            <NTabPane name="agent" tab="代理配置">
              <NGrid cols="1 m:2" responsive="screen" :x-gap="16">
                <NGi>
                  <NFormItem label="上级迁移功能">
                    <NSwitch v-model:value="form.sjqykg" checked-value="1" unchecked-value="0" />
                  </NFormItem>
                </NGi>
                <NGi>
                  <NFormItem label="允许邀请码注册">
                    <NSwitch v-model:value="form.user_yqzc" checked-value="1" unchecked-value="0" />
                  </NFormItem>
                </NGi>
                <NGi>
                  <NFormItem label="允许后台开户">
                    <NSwitch v-model:value="form.user_htkh" checked-value="1" unchecked-value="0" />
                  </NFormItem>
                </NGi>
                <NGi>
                  <NFormItem label="代理开通价格">
                    <NInput v-model:value="form.user_ktmoney" placeholder="请输入金额" />
                  </NFormItem>
                </NGi>
              </NGrid>
            </NTabPane>

            <NTabPane name="payment" tab="支付配置">
              <NGrid cols="1 m:2" responsive="screen" :x-gap="16">
                <NGi>
                  <NFormItem label="开启在线充值">
                    <NSwitch v-model:value="form.zxczkg" checked-value="1" unchecked-value="0" />
                  </NFormItem>
                </NGi>
                <NGi>
                  <NFormItem label="最低充值">
                    <NInput v-model:value="form.zdpay" placeholder="请输入最低充值金额" />
                  </NFormItem>
                </NGi>
                <NGi span="1 m:2">
                  <NFormItem label="支付方式">
                    <NSpace :size="16">
                      <div class="flex items-center gap-8px">
                        <NSwitch v-model:value="form.is_alipay" checked-value="1" unchecked-value="0" />
                        <span>支付宝</span>
                      </div>
                      <div class="flex items-center gap-8px">
                        <NSwitch v-model:value="form.is_wxpay" checked-value="1" unchecked-value="0" />
                        <span>微信支付</span>
                      </div>
                      <div class="flex items-center gap-8px">
                        <NSwitch v-model:value="form.is_qqpay" checked-value="1" unchecked-value="0" />
                        <span>QQ 钱包</span>
                      </div>
                    </NSpace>
                  </NFormItem>
                </NGi>
                <NGi span="1 m:2">
                  <NFormItem label="易支付 API">
                    <NInput v-model:value="form.epay_api" placeholder="例如：https://pay.example.com/" />
                  </NFormItem>
                </NGi>
                <NGi>
                  <NFormItem label="商户 ID">
                    <NInput v-model:value="form.epay_pid" placeholder="请输入商户 ID" />
                  </NFormItem>
                </NGi>
                <NGi>
                  <NFormItem>
                    <template #label>
                      <NSpace align="center" :size="8">
                        <span>商户 KEY</span>
                        <NTag :type="hasEpayKey ? 'success' : 'default'" size="small">
                          {{ hasEpayKey ? '已配置' : '未配置' }}
                        </NTag>
                      </NSpace>
                    </template>
                    <NInput
                      v-model:value="form.epay_key"
                      type="password"
                      show-password-on="click"
                      autocomplete="new-password"
                      placeholder="留空保持原 KEY 不变"
                    />
                  </NFormItem>
                </NGi>
              </NGrid>
            </NTabPane>

            <NTabPane name="register" tab="注册机制">
              <NGrid cols="1 m:3" responsive="screen" :x-gap="16">
                <NGi>
                  <NFormItem label="邀请奖励">
                    <NInput v-model:value="form.yqjl" placeholder="上级获得的奖励金额" />
                  </NFormItem>
                </NGi>
                <NGi>
                  <NFormItem label="注册送奖励">
                    <NInput v-model:value="form.yqsq" placeholder="新用户获得的奖励金额" />
                  </NFormItem>
                </NGi>
                <NGi>
                  <NFormItem label="邀请限制">
                    <NInput v-model:value="form.yqsx" placeholder="每个用户的邀请上限" />
                  </NFormItem>
                </NGi>
              </NGrid>
            </NTabPane>

            <NTabPane name="category" tab="分类配置">
              <NGrid cols="1 m:2" responsive="screen" :x-gap="16">
                <NGi>
                  <NFormItem label="分类开关">
                    <NSwitch v-model:value="form.flkg" checked-value="1" unchecked-value="0" />
                  </NFormItem>
                </NGi>
                <NGi>
                  <NFormItem label="分类类型">
                    <NRadioGroup v-model:value="form.fllx">
                      <NRadioButton value="1">选择框分类</NRadioButton>
                      <NRadioButton value="2">单选框分类</NRadioButton>
                    </NRadioGroup>
                  </NFormItem>
                </NGi>
              </NGrid>
            </NTabPane>

            <NTabPane name="api" tab="查课配置">
              <NGrid cols="1 m:2" responsive="screen" :x-gap="16">
                <NGi>
                  <NFormItem label="最低调用 API 查课余额">
                    <NInput v-model:value="form.zddy" placeholder="请输入最低余额" />
                  </NFormItem>
                </NGi>
                <NGi>
                  <NFormItem label="最低调用 API 下单余额">
                    <NInput v-model:value="form.zdxd" placeholder="请输入最低余额" />
                  </NFormItem>
                </NGi>
                <NGi>
                  <NFormItem label="开启查课功能">
                    <NSwitch v-model:value="form.ckkg" checked-value="1" unchecked-value="0" />
                  </NFormItem>
                </NGi>
                <NGi>
                  <NFormItem label="开启下单功能">
                    <NSwitch v-model:value="form.xdkg" checked-value="1" unchecked-value="0" />
                  </NFormItem>
                </NGi>
              </NGrid>
            </NTabPane>

            <NTabPane name="contact" tab="联系配置">
              <NGrid cols="1 m:2" responsive="screen" :x-gap="16">
                <NGi>
                  <NFormItem label="QQ 联系方式">
                    <NInput v-model:value="form.zzqq" placeholder="请输入站长 QQ" />
                  </NFormItem>
                </NGi>
                <NGi>
                  <NFormItem label="微信联系方式">
                    <NInput v-model:value="form.zzvx" placeholder="请输入站长微信" />
                  </NFormItem>
                </NGi>
              </NGrid>
            </NTabPane>
          </NTabs>

          <NDivider />
          <div class="flex justify-end">
            <NPopconfirm positive-text="确认保存" negative-text="取消" @positive-click="saveSettings">
              <template #trigger>
                <NButton type="primary" size="large" :loading="saving">
                  <template #icon><SvgIcon icon="ph:floppy-disk" /></template>
                  保存全部设置
                </NButton>
              </template>
              系统设置会立即影响线上业务，确认保存当前配置吗？
            </NPopconfirm>
          </div>
        </NForm>
      </NCard>
    </NSpin>
  </NSpace>
</template>

<style scoped></style>
