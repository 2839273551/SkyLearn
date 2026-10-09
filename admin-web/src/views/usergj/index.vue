<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { NAlert, NButton, NCard, NForm, NFormItem, NInputNumber, NRadioGroup, NRadioButton, NSelect, NSpace, NSpin } from 'naive-ui';
import { batchUpdateRate, fetchUserlistList } from '@/service/api';

defineOptions({ name: 'Usergj' });

const router = useRouter();
const loading = ref(false);
const submitting = ref(false);

const mode = ref<'all' | 'custom'>('all');
const targetRate = ref(0.35);
const selectedUids = ref<number[]>([]);
const userOptions = ref<{ label: string; value: number }[]>([]);

async function loadUsers() {
  loading.value = true;
  const { data, error } = await fetchUserlistList({ page: 1, pageSize: 100 });
  loading.value = false;
  if (!error && data) {
    userOptions.value = data.records.map(u => ({
      label: `[UID: ${u.uid}] ${u.name || u.user} (当前: ${u.addprice}×)`,
      value: Number(u.uid)
    }));
  }
}

async function handleUpdate() {
  if (targetRate.value < 0.1 || targetRate.value > 5) {
    window.$message?.warning('费率系数范围必须在 0.10 ~ 5.00 之间');
    return;
  }
  if (mode.value === 'custom' && selectedUids.value.length === 0) {
    window.$message?.warning('请选择需要调价的目标代理');
    return;
  }

  submitting.value = true;
  const { error } = await batchUpdateRate({
    uids: mode.value === 'custom' ? selectedUids.value : undefined,
    rate: targetRate.value
  });
  submitting.value = false;

  if (!error) {
    window.$message?.success(`批量改价成功！新费率: ${targetRate.value}× 已生效`);
    router.push('/userlist');
  }
}

onMounted(() => {
  loadUsers();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-10px sm:p-16px max-w-720px mx-auto">
    <NCard :bordered="false" class="rounded-12px shadow-sm border border-gray-100 dark:border-dark-600">
      <div class="flex items-center gap-12px">
        <div class="flex h-44px w-44px items-center justify-center rounded-10px bg-primary/10 text-primary text-22px">
          💹
        </div>
        <div>
          <h1 class="text-17px font-bold text-gray-800 dark:text-gray-100">下级代理批量成本费率调整</h1>
          <p class="text-12px text-gray-400 mt-2px">为指定或全部下级代理统一调整网课下单的成本加价费率系数</p>
        </div>
      </div>
    </NCard>

    <NCard title="调价参数配置" :bordered="false" class="rounded-12px shadow-sm">
      <NSpin :show="loading">
        <div class="flex flex-col gap-16px">
          <NFormItem label="调价范围对象">
            <NRadioGroup v-model:value="mode">
              <NSpace>
                <NRadioButton value="all">全量下属代理 (全部统一生效)</NRadioButton>
                <NRadioButton value="custom">指定部分代理商户</NRadioButton>
              </NSpace>
            </NRadioGroup>
          </NFormItem>

          <NFormItem v-if="mode === 'custom'" label="选择目标代理商户">
            <NSelect
              v-model:value="selectedUids"
              multiple
              filterable
              :options="userOptions"
              placeholder="选择一个或多个目标商户"
            />
          </NFormItem>

          <NFormItem label="目标成本费率系数 (如 0.30 代表 3.0 折基础成本)">
            <NInputNumber
              v-model:value="targetRate"
              :step="0.05"
              :min="0.1"
              :max="5.0"
              class="w-full"
            >
              <template #suffix>×</template>
            </NInputNumber>
          </NFormItem>

          <div class="rounded-8px bg-slate-50 p-12px text-12px text-gray-500 dark:bg-dark-600 leading-relaxed border">
            💡 说明：费率修改即时生效，代理后续下单单价将根据新费率动态折算。请确保所设费率不低于您自身在平台的成本，以免产生费率倒挂。
          </div>

          <div class="flex justify-end gap-12px border-t pt-12px">
            <NButton secondary @click="router.push('/userlist')">取消并返回列表</NButton>
            <NButton type="primary" size="large" :loading="submitting" @click="handleUpdate">
              确认统一执行调价
            </NButton>
          </div>
        </div>
      </NSpin>
    </NCard>
  </div>
</template>
