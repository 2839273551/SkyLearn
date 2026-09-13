<script setup lang="ts">
import { onMounted, ref } from 'vue';
import type { DataTableColumns } from 'naive-ui';
import { NButton, NCard, NDataTable, NSpin, NStatistic, NTag } from 'naive-ui';
import { fetchMyReferrals } from '@/service/api';

defineOptions({ name: 'Tjuser' });

const loading = ref(false);
const list = ref<any[]>([]);
const myYqm = ref('');
const totalReferrals = ref(0);
const siteUrl = ref('');

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchMyReferrals();
  loading.value = false;
  if (!error && data) {
    list.value = data.list;
    myYqm.value = data.my_yqm;
    totalReferrals.value = data.total_referrals;
    siteUrl.value = data.site_url;
  }
}

function copyText(text: string, label = '内容') {
  navigator.clipboard.writeText(text);
  window.$message?.success(`${label}已成功复制到剪贴板`);
}

const columns: DataTableColumns<any> = [
  { title: '商户 UID', key: 'uid', width: 90 },
  { title: '登录账号', key: 'user', width: 150 },
  { title: '商户昵称', key: 'name', minWidth: 140 },
  {
    title: '成本费率',
    key: 'rate',
    width: 100,
    render: row => `${row.rate}×`
  },
  {
    title: '累计完成订单',
    key: 'order_count',
    width: 130,
    render: row => `${row.order_count} 笔`
  },
  { title: '注册时间', key: 'addtime', width: 170 }
];

onMounted(() => {
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-10px sm:p-16px">
    <!-- 推广信息卡片 -->
    <div class="rounded-12px bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-16px text-white shadow-sm border border-slate-700/50">
      <div class="flex flex-wrap items-center justify-between gap-16px">
        <div>
          <h1 class="text-18px font-bold">我的专属分销推广中心</h1>
          <p class="text-12px text-slate-400 mt-4px">通过您的专属邀请链接或邀请码注册的用户，将永久绑定为您的下级商户并为您产生消费差价返佣</p>
        </div>
        <div class="flex flex-wrap items-center gap-10px">
          <div class="rounded-8px bg-slate-800/90 px-12px py-6px border border-slate-700 text-13px">
            专属邀请码：<strong class="font-mono text-emerald-400 font-bold tracking-wider">{{ myYqm || '未生成' }}</strong>
            <NButton size="tiny" secondary class="ml-8px" @click="copyText(myYqm, '邀请码')">复制</NButton>
          </div>
          <NButton type="primary" size="medium" class="font-bold" @click="copyText(`${siteUrl}/#/login?yqm=${myYqm}`, '推广链接')">
            🔗 复制专属注册链接
          </NButton>
        </div>
      </div>
    </div>

    <!-- 数据列表 -->
    <NCard title="我邀请的直属代理列表" :bordered="false" class="rounded-12px shadow-sm">
      <NSpin :show="loading">
        <NDataTable
          :columns="columns"
          :data="list"
          :row-key="(row: any) => row.uid"
          :pagination="false"
          striped
          size="small"
          :scroll-x="800"
        />
      </NSpin>
    </NCard>
  </div>
</template>
