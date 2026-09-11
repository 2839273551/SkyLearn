<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { fetchDockingInfo } from '@/service/api';

defineOptions({ name: 'Docking' });

const loading = ref(false);
const docking = ref<Api.ProfileArea.DockingInfo>({
  uid: '',
  key: '',
  apiAddUrl: '',
  apiQueryUrl: '',
  apiStatusUrl: '',
  apiRefreshUrl: ''
});

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchDockingInfo();
  loading.value = false;
  if (!error && data) {
    docking.value = data;
  }
}

function copyText(text: string) {
  navigator.clipboard.writeText(text);
  window.$message?.success('已复制到剪贴板');
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="平台串货与 API 对接中心" :bordered="false" class="rounded-8px shadow-sm">
      <NAlert type="info" title="我的对接凭据" class="mb-16px">
        <div class="flex flex-wrap items-center gap-24px">
          <span>对接 UID：<strong>{{ docking.uid }}</strong></span>
          <span>对接 KEY：<code>{{ docking.key || '未生成' }}</code></span>
          <NButton size="small" type="primary" ghost @click="copyText(docking.key)">复制 KEY</NButton>
        </div>
      </NAlert>

      <NTabs type="line" animated>
        <NTabPane name="add" tab="下单接口 (POST)">
          <div class="flex flex-col gap-12px">
            <div class="flex items-center justify-between rounded-6px bg-gray-50 p-10px dark:bg-dark-600">
              <span class="font-mono text-13px">接口地址：{{ docking.apiAddUrl }}</span>
              <NButton size="small" ghost type="primary" @click="copyText(docking.apiAddUrl)">复制接口</NButton>
            </div>
            <p class="text-13px text-gray-500">请求方式：<code>POST</code> | Header：<code>Content-Type: application/json</code></p>
            <NCard embedded size="small" title="请求 JSON 参数示例">
              <pre class="m-0 font-mono text-12px">{
  "uid": {{ docking.uid }},
  "key": "{{ docking.key }}",
  "platform": "课程CID",
  "school": "学校名称",
  "user": "学习账号",
  "pass": "学习密码",
  "kcname": "课程完整名称"
}</pre>
            </NCard>
          </div>
        </NTabPane>

        <NTabPane name="query" tab="查课接口 (POST)">
          <div class="flex flex-col gap-12px">
            <div class="flex items-center justify-between rounded-6px bg-gray-50 p-10px dark:bg-dark-600">
              <span class="font-mono text-13px">接口地址：{{ docking.apiQueryUrl }}</span>
              <NButton size="small" ghost type="primary" @click="copyText(docking.apiQueryUrl)">复制接口</NButton>
            </div>
            <NCard embedded size="small" title="请求 JSON 参数示例">
              <pre class="m-0 font-mono text-12px">{
  "uid": {{ docking.uid }},
  "key": "{{ docking.key }}",
  "platform": "课程CID",
  "school": "学校名称",
  "user": "学习账号",
  "pass": "学习密码"
}</pre>
            </NCard>
          </div>
        </NTabPane>

        <NTabPane name="status" tab="进度查询 (GET / POST)">
          <div class="flex flex-col gap-12px">
            <div class="flex items-center justify-between rounded-6px bg-gray-50 p-10px dark:bg-dark-600">
              <span class="font-mono text-13px">接口地址：{{ docking.apiStatusUrl }}?oid=订单号</span>
              <NButton size="small" ghost type="primary" @click="copyText(docking.apiStatusUrl)">复制接口</NButton>
            </div>
          </div>
        </NTabPane>
      </NTabs>
    </NCard>
  </div>
</template>
