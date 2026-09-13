<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import {
  NAlert,
  NButton,
  NCard,
  NDivider,
  NFormItem,
  NInput,
  NSelect,
  NSpin
} from 'naive-ui';
import { createUser, fetchGradeOptions } from '@/service/api';

defineOptions({ name: 'Adduser' });

const router = useRouter();
const loading = ref(false);
const submitting = ref(false);

const gradeList = ref<Api.Adduser.GradeItem[]>([]);
const openReg = ref('1');
const ktMoney = ref(0);
const currentUserRate = ref(1);

const form = reactive({
  user: '',
  pass: '',
  name: '',
  grade_id: null as string | null
});

async function loadGrades() {
  loading.value = true;
  const { data, error } = await fetchGradeOptions();
  loading.value = false;

  if (!error && data) {
    gradeList.value = data.grades;
    openReg.value = data.user_htkh;
    ktMoney.value = data.user_ktmoney;
    currentUserRate.value = data.current_user_rate;
    if (data.grades.length > 0 && !form.grade_id) {
      const firstValid = data.grades.find(g => !g.disabled);
      if (firstValid) form.grade_id = firstValid.id;
    }
  }
}

const selectedGrade = computed(() => {
  if (!form.grade_id) return null;
  return gradeList.value.find(g => g.id === form.grade_id) || null;
});

const calculatedNeed = computed(() => {
  let fee = ktMoney.value;
  if (selectedGrade.value && selectedGrade.value.addkf === 1 && selectedGrade.value.rate > 0) {
    const rechargeCost = Number((selectedGrade.value.money * (currentUserRate.value / selectedGrade.value.rate)).toFixed(2));
    fee += rechargeCost;
  }
  return Number(fee.toFixed(2));
});

async function handleCreate() {
  if (!form.user.trim()) {
    window.$message?.warning('请输入代理 QQ 号码');
    return;
  }
  if (!form.pass.trim()) {
    window.$message?.warning('请输入初始密码');
    return;
  }
  if (!form.name.trim()) {
    window.$message?.warning('请输入代理昵称');
    return;
  }
  if (!form.grade_id) {
    window.$message?.warning('请选择代理等级');
    return;
  }

  submitting.value = true;
  const { data, error } = await createUser({
    user: form.user.trim(),
    pass: form.pass.trim(),
    name: form.name.trim(),
    grade_id: Number(form.grade_id)
  });
  submitting.value = false;

  if (!error && data) {
    window.$message?.success(`代理开通成功！UID: ${data.uid}，账号: ${data.user}`);
    router.push('/userlist');
  }
}

onMounted(() => {
  loadGrades();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px max-w-720px mx-auto">
    <NAlert v-if="openReg === '0'" type="error" title="开户暂未开放" class="rounded-8px">
      当前系统设置已暂停后台手动开户，具体开放时间请留意系统公告或联系管理员。
    </NAlert>

    <NCard title="开通下级代理账号" :bordered="false" class="rounded-12px shadow-sm">
      <NSpin :show="loading">
        <div class="flex flex-col gap-16px">
          <p class="text-13px text-gray-500">
            开通后新代理将绑定为您的一级下属，其下单赚取的差价将自动计入您的邀请与团队分销返佣收益。
          </p>

          <NFormItem label="代理登录账号 (QQ 号码)" required>
            <NInput v-model:value="form.user" placeholder="输入 5~11 位数字 QQ 号码（自动绑定 QQ 头像）" />
          </NFormItem>

          <NFormItem label="初始登录密码" required>
            <NInput v-model:value="form.pass" type="password" show-password-on="click" placeholder="为下级代理设置初始登录密码" />
          </NFormItem>

          <NFormItem label="代理昵称 / 商户名称" required>
            <NInput v-model:value="form.name" placeholder="输入代理专属昵称" />
          </NFormItem>

          <NFormItem label="选择代理等级与成本费率" required>
            <NSelect
              v-model:value="form.grade_id"
              :options="gradeList.map(g => ({
                label: `${g.name} (${g.rate}×成本系数)${g.disabled ? ' [费率倒挂不可开]' : ''}`,
                value: g.id,
                disabled: g.disabled
              }))"
              placeholder="选择等级"
            />
          </NFormItem>

          <!-- 资费预算卡片 -->
          <div v-if="selectedGrade" class="rounded-8px bg-slate-50 p-14px dark:bg-dark-600 text-13px flex flex-col gap-8px border border-slate-200 dark:border-dark-500">
            <div class="flex justify-between">
              <span class="text-gray-500">下级成本费率：</span>
              <strong class="text-primary font-mono">{{ selectedGrade.rate }}×</strong>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">开户基础手续费：</span>
              <span class="font-mono">¥ {{ ktMoney.toFixed(2) }}</span>
            </div>
            <div v-if="selectedGrade.addkf === 1" class="flex justify-between text-emerald-600 font-medium">
              <span>自动充入下级初始余额：</span>
              <span class="font-mono">+¥ {{ selectedGrade.money }}</span>
            </div>
            <div class="flex justify-between border-t border-gray-200 dark:border-dark-500 pt-8px text-14px">
              <span class="font-bold text-gray-700 dark:text-gray-200">本次开户将从您余额扣除：</span>
              <strong class="font-mono text-18px text-rose-500 font-bold">¥ {{ calculatedNeed.toFixed(2) }}</strong>
            </div>
          </div>

          <NDivider style="margin: 4px 0" />

          <div class="flex justify-end gap-12px">
            <NButton secondary @click="router.push('/userlist')">返回代理列表</NButton>
            <NButton type="primary" size="large" :loading="submitting" :disabled="openReg === '0'" @click="handleCreate">
              确认开通下级代理
            </NButton>
          </div>
        </div>
      </NSpin>
    </NCard>
  </div>
</template>
