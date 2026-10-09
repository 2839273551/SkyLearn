<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { fetchDockingInfo } from '@/service/api';

defineOptions({ name: 'Docking' });

const loading = ref(false);
const hideKey = ref(true);

const docking = ref<Api.ProfileArea.DockingInfo>({
  uid: '',
  key: '',
  apiBaseUrl: '',
  apiBalanceUrl: '',
  apiGoodsUrl: '',
  apiQueryUrl: '',
  apiAddUrl: '',
  apiAutoAddUrl: '',
  apiStatusUrl: '',
  apiBudanUrl: ''
});

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchDockingInfo();
  loading.value = false;
  if (!error && data) {
    docking.value = data;
  }
}

function copyText(text: string, label = '内容') {
  if (!text) {
    window.$message?.warning(`暂无${label}可复制`);
    return;
  }
  navigator.clipboard.writeText(text);
  window.$message?.success(`${label}已成功复制到剪贴板`);
}

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="平台串货与 API 开放对接中心" :bordered="false" class="rounded-8px shadow-sm">
      <!-- 对接凭据卡片 -->
      <NAlert type="info" title="我的对接凭据与网关协议" class="mb-16px">
        <div class="flex flex-col gap-10px">
          <div class="flex flex-wrap items-center gap-24px text-14px">
            <span>对接商户 UID：<strong class="text-primary">{{ docking.uid }}</strong></span>
            <div class="flex items-center gap-8px">
              <span>对接密钥 KEY：</span>
              <code class="font-mono font-bold tracking-wider">{{ hideKey ? '••••••••••••••••' : (docking.key || '未生成') }}</code>
              <NButton size="tiny" quaternary circle @click="hideKey = !hideKey">
                <template #icon><SvgIcon :icon="hideKey ? 'ph:eye' : 'ph:eye-slash'" /></template>
              </NButton>
              <NButton size="tiny" type="primary" secondary @click="copyText(docking.key, '对接 KEY')">
                复制 KEY
              </NButton>
            </div>
            <div class="flex items-center gap-8px">
              <span>接口基础网关：</span>
              <code class="font-mono text-12px">{{ docking.apiBaseUrl }}</code>
              <NButton size="tiny" quaternary @click="copyText(docking.apiBaseUrl, '网关地址')">复制</NButton>
            </div>
          </div>
          <div class="text-12px text-gray-500 leading-normal">
            协议支持：所有接口全面支持 <code>POST (Content-Type: application/json)</code> 及 <code>POST (x-www-form-urlencoded)</code> 双协议自动适配；支持小储系统、卡易信、彩虹发卡网及自建脚本直接串联对接。
          </div>
        </div>
      </NAlert>

      <!-- 接口文档选项卡 -->
      <NTabs type="line" animated>
        <!-- 1. 查询余额接口 -->
        <NTabPane name="balance" tab="1. 查询余额 (POST)">
          <div class="flex flex-col gap-14px">
            <div class="flex items-center justify-between rounded-6px bg-gray-50 p-10px dark:bg-dark-600">
              <span class="font-mono text-13px">接口地址：<strong>{{ docking.apiBalanceUrl }}</strong></span>
              <NButton size="small" ghost type="primary" @click="copyText(docking.apiBalanceUrl, '查询余额接口')">复制接口</NButton>
            </div>
            <p class="m-0 text-13px text-gray-500">
              请求方式：<code>POST</code> | 参数格式：<code>JSON</code> 或 <code>表单 POST</code>
            </p>

            <NCard embedded size="small" title="请求参数规范">
              <NDescriptions label-placement="left" :column="1" bordered size="small">
                <NDescriptionsItem label="uid (必填)">平台分配的商户 UID（如：{{ docking.uid }}）</NDescriptionsItem>
                <NDescriptionsItem label="key (必填)">您的商户对接密钥 KEY</NDescriptionsItem>
              </NDescriptions>
            </NCard>

            <NCard embedded size="small" title="JSON 请求示例与成功回执">
              <pre class="m-0 font-mono text-12px text-gray-700 dark:text-gray-300">// 请求 JSON
{
  "uid": {{ docking.uid }},
  "key": "{{ docking.key || 'YOUR_API_KEY' }}"
}

// 成功返回示例
{
  "code": 1,
  "msg": "查询成功",
  "money": 88359.49
}</pre>
            </NCard>
          </div>
        </NTabPane>

        <!-- 2. 获取商品列表 -->
        <NTabPane name="goods" tab="2. 获取商品与平台 (POST)">
          <div class="flex flex-col gap-14px">
            <div class="flex items-center justify-between rounded-6px bg-gray-50 p-10px dark:bg-dark-600">
              <span class="font-mono text-13px">接口地址：<strong>{{ docking.apiGoodsUrl }}</strong></span>
              <NButton size="small" ghost type="primary" @click="copyText(docking.apiGoodsUrl, '获取商品接口')">复制接口</NButton>
            </div>
            <p class="m-0 text-13px text-gray-500">
              请求方式：<code>POST</code> | 说明：用于外部商城（如小储系统）自动拉取所有网课平台、获取对应 <code>cid</code>（即 platform 编号）与代理实时成本单价。
            </p>

            <NCard embedded size="small" title="请求参数规范">
              <NDescriptions label-placement="left" :column="1" bordered size="small">
                <NDescriptionsItem label="uid (必填)">商户 UID</NDescriptionsItem>
                <NDescriptionsItem label="key (必填)">商户对接密钥</NDescriptionsItem>
                <NDescriptionsItem label="fenlei (选填)">按分类 ID 筛选，留空获取全部分类</NDescriptionsItem>
              </NDescriptions>
            </NCard>

            <NCard embedded size="small" title="成功返回示例">
              <pre class="m-0 font-mono text-12px text-gray-700 dark:text-gray-300">{
  "code": 1,
  "data": [
    {
      "cid": "12",
      "name": "超星学习通[日常作业+视频]",
      "price": 0.85,
      "content": "支持自动换课，无视人脸",
      "noun": "xxt"
    }
  ]
}</pre>
            </NCard>
          </div>
        </NTabPane>

        <!-- 3. 在线查课接口 -->
        <NTabPane name="query" tab="3. 在线查课 (POST)">
          <div class="flex flex-col gap-14px">
            <div class="flex items-center justify-between rounded-6px bg-gray-50 p-10px dark:bg-dark-600">
              <span class="font-mono text-13px">接口地址：<strong>{{ docking.apiQueryUrl }}</strong></span>
              <NButton size="small" ghost type="primary" @click="copyText(docking.apiQueryUrl, '在线查课接口')">复制接口</NButton>
            </div>
            <p class="m-0 text-13px text-gray-500">
              请求方式：<code>POST</code> | 说明：根据平台编号和学生账号密码在线查询当前名下修读的课程清单。
            </p>

            <NCard embedded size="small" title="请求参数规范">
              <NDescriptions label-placement="left" :column="1" bordered size="small">
                <NDescriptionsItem label="uid (必填)">商户 UID</NDescriptionsItem>
                <NDescriptionsItem label="key (必填)">商户对接密钥</NDescriptionsItem>
                <NDescriptionsItem label="platform (必填)">课程平台编号（即商品获取接口中的 cid）</NDescriptionsItem>
                <NDescriptionsItem label="school (必填)">学校名称（无学校可传“自动识别”）</NDescriptionsItem>
                <NDescriptionsItem label="user (必填)">学生学习账号 / 手机号</NDescriptionsItem>
                <NDescriptionsItem label="pass (必填)">学生学习登录密码</NDescriptionsItem>
              </NDescriptions>
            </NCard>

            <NCard embedded size="small" title="成功返回示例">
              <pre class="m-0 font-mono text-12px text-gray-700 dark:text-gray-300">{
  "code": 1,
  "msg": "查询成功",
  "userName": "张三",
  "data": [
    {
      "id": "2087412",
      "name": "大学英语进阶与听说训练",
      "teacher": "李老师",
      "state": "未完成"
    }
  ]
}</pre>
            </NCard>
          </div>
        </NTabPane>

        <!-- 4. 单课程下单接口 -->
        <NTabPane name="add" tab="4. 课程下单 (POST)">
          <div class="flex flex-col gap-14px">
            <div class="flex items-center justify-between rounded-6px bg-gray-50 p-10px dark:bg-dark-600">
              <span class="font-mono text-13px">接口地址：<strong>{{ docking.apiAddUrl }}</strong></span>
              <NButton size="small" ghost type="primary" @click="copyText(docking.apiAddUrl, '课程下单接口')">复制接口</NButton>
            </div>
            <p class="m-0 text-13px text-gray-500">
              请求方式：<code>POST</code> | 说明：查课完毕后，将选定的课程提交平台进入自动上号挂机排队链路。
            </p>

            <NCard embedded size="small" title="请求参数规范">
              <NDescriptions label-placement="left" :column="1" bordered size="small">
                <NDescriptionsItem label="uid (必填)">商户 UID</NDescriptionsItem>
                <NDescriptionsItem label="key (必填)">商户对接密钥</NDescriptionsItem>
                <NDescriptionsItem label="platform (必填)">商品平台编号 cid</NDescriptionsItem>
                <NDescriptionsItem label="school (必填)">学校名称</NDescriptionsItem>
                <NDescriptionsItem label="user (必填)">学习账号</NDescriptionsItem>
                <NDescriptionsItem label="pass (必填)">学习密码</NDescriptionsItem>
                <NDescriptionsItem label="kcname (必填)">需要修读的完整课程名称</NDescriptionsItem>
                <NDescriptionsItem label="kcid (选填)">上游课程 ID（建议携带，避免同名课程误判）</NDescriptionsItem>
              </NDescriptions>
            </NCard>

            <NCard embedded size="small" title="成功返回示例">
              <pre class="m-0 font-mono text-12px text-gray-700 dark:text-gray-300">{
  "code": 1,
  "msg": "下单成功",
  "oid": 10582,
  "money": 0.85
}</pre>
            </NCard>
          </div>
        </NTabPane>

        <!-- 5. 查课并自动下单 -->
        <NTabPane name="autoAdd" tab="5. 查课并下单[一步到位] (POST)">
          <div class="flex flex-col gap-14px">
            <div class="flex items-center justify-between rounded-6px bg-gray-50 p-10px dark:bg-dark-600">
              <span class="font-mono text-13px">接口地址：<strong>{{ docking.apiAutoAddUrl }}</strong></span>
              <NButton size="small" ghost type="primary" @click="copyText(docking.apiAutoAddUrl, '一步查课下单接口')">复制接口</NButton>
            </div>
            <NAlert type="success">
              特别推荐：专为小储商城、第三方发卡系统或自动化爬虫定制的一步提单接口。平台将自动校验并完成查课和扣费下单，一步返回订单结果！
            </NAlert>

            <NCard embedded size="small" title="请求 JSON 参数示例">
              <pre class="m-0 font-mono text-12px">{
  "uid": {{ docking.uid }},
  "key": "{{ docking.key || 'YOUR_API_KEY' }}",
  "platform": "课程CID",
  "school": "学校名称",
  "user": "学习账号",
  "pass": "学习密码",
  "kcname": "完整课程名称"
}</pre>
            </NCard>
          </div>
        </NTabPane>

        <!-- 6. 订单状态与进度查询 -->
        <NTabPane name="status" tab="6. 订单进度与状态 (GET / POST)">
          <div class="flex flex-col gap-14px">
            <div class="flex items-center justify-between rounded-6px bg-gray-50 p-10px dark:bg-dark-600">
              <span class="font-mono text-13px">接口地址：<strong>{{ docking.apiStatusUrl }}</strong></span>
              <NButton size="small" ghost type="primary" @click="copyText(docking.apiStatusUrl, '进度查询接口')">复制接口</NButton>
            </div>
            <p class="m-0 text-13px text-gray-500">
              请求方式：<code>GET / POST</code> | 说明：可随时通过订单号查询任务当前实时挂机进度、状态与备注信息。
            </p>

            <NCard embedded size="small" title="GET 请求调用示例">
              <div class="font-mono text-12px rounded-6px bg-gray-50 p-10px dark:bg-dark-600">
                {{ docking.apiStatusUrl }}?oid=10582
              </div>
            </NCard>

            <NCard embedded size="small" title="成功返回示例">
              <pre class="m-0 font-mono text-12px text-gray-700 dark:text-gray-300">[
  {
    "id": 10582,
    "ptname": "超星学习通",
    "school": "北京大学",
    "name": "张三",
    "user": "13800138000",
    "kcname": "大学英语",
    "status": "已完成",
    "progress": "100%",
    "addtime": "2026-09-11 14:20:00"
  }
]</pre>
            </NCard>
          </div>
        </NTabPane>

        <!-- 7. 补单重刷接口 -->
        <NTabPane name="budan" tab="7. 补单与重跑 (POST)">
          <div class="flex flex-col gap-14px">
            <div class="flex items-center justify-between rounded-6px bg-gray-50 p-10px dark:bg-dark-600">
              <span class="font-mono text-13px">接口地址：<strong>{{ docking.apiBudanUrl }}</strong></span>
              <NButton size="small" ghost type="primary" @click="copyText(docking.apiBudanUrl, '补单接口')">复制接口</NButton>
            </div>
            <p class="m-0 text-13px text-gray-500">
              请求方式：<code>POST</code> | 参数：<code>uid</code>, <code>key</code>, <code>oid</code> (订单号)
            </p>
            <NCard embedded size="small" title="请求 JSON 参数示例">
              <pre class="m-0 font-mono text-12px">{
  "uid": {{ docking.uid }},
  "key": "{{ docking.key || 'YOUR_API_KEY' }}",
  "oid": 10582
}</pre>
            </NCard>
          </div>
        </NTabPane>
      </NTabs>

      <!-- 小储 / 发卡系统对接对照指南 -->
      <div class="mt-20px rounded-8px border border-primary/20 bg-primary/4 p-16px">
        <h4 class="m-0 text-14px font-bold text-primary">小储商城 / 卡易信 / 外部发卡系统串货对接指南：</h4>
        <div class="mt-10px grid grid-cols-1 s:2 gap-12px text-12px text-gray-600 dark:text-gray-300 leading-relaxed">
          <div>
            <p class="m-0 font-semibold">1. 站点类型选择：</p>
            <p class="m-0">在小储商城后台添加货源时，选择【小储系统】或【API通用对接】；</p>
            <p class="m-0 mt-6px font-semibold">2. 网站域名与密钥：</p>
            <p class="m-0">网站地址填 <code>{{ docking.apiBaseUrl ? docking.apiBaseUrl.replace('/api.php', '') : 'https://sk.yunxnet.cn' }}</code>，商户ID填 <code>{{ docking.uid }}</code>，密钥填您的 <code>KEY</code>；</p>
          </div>
          <div>
            <p class="m-0 font-semibold">3. 商品绑定对应：</p>
            <p class="m-0">小储端【商品编号】直接填写我方的 <code>cid</code>（在第2个Tab商品列表中获取）；</p>
            <p class="m-0 mt-6px font-semibold">4. 自动化运行：</p>
            <p class="m-0">客户在您的小储前台下单付款后，系统将自动发起 API 调用扣费秒级流转到我方服务器！</p>
          </div>
        </div>
      </div>
    </NCard>
  </div>
</template>

<style scoped></style>
