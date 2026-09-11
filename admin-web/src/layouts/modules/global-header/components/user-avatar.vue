<script setup lang="ts">
import { computed } from 'vue';
import type { VNode } from 'vue';
import { useAuthStore } from '@/store/modules/auth';
import { useRouterPush } from '@/hooks/common/router';
import { useSvgIcon } from '@/hooks/common/icon';
import { $t } from '@/locales';

defineOptions({
  name: 'UserAvatar'
});

const authStore = useAuthStore();
const { routerPushByKey, toLogin } = useRouterPush();
const { SvgIconVNode } = useSvgIcon();

function loginOrRegister() {
  toLogin();
}

type DropdownKey = 'logout' | 'userinfo' | 'passwd' | 'sjqy';

type DropdownOption =
  | {
      key: DropdownKey;
      label: string;
      icon?: () => VNode;
    }
  | {
      type: 'divider';
      key: string;
    };

const avatarUrl = computed(() => {
  if (authStore.userInfo.avatar) {
    return authStore.userInfo.avatar;
  }
  const user = authStore.userInfo.userName || '';
  const digits = user.replace(/\D/g, '');
  if (digits.length >= 5 && digits.length <= 11) {
    return `https://q1.qlogo.cn/g?b=qq&nk=${digits}&s=100`;
  }
  return 'https://q1.qlogo.cn/g?b=qq&nk=10001&s=100';
});

const options = computed(() => {
  const opts: DropdownOption[] = [
    {
      label: '个人中心',
      key: 'userinfo',
      icon: SvgIconVNode({ icon: 'ph:user-circle', fontSize: 18 })
    },
    {
      label: '修改密码',
      key: 'passwd',
      icon: SvgIconVNode({ icon: 'ph:lock-key', fontSize: 18 })
    },
    {
      label: $t('common.logout'),
      key: 'logout',
      icon: SvgIconVNode({ icon: 'ph:sign-out', fontSize: 18 })
    }
  ];

  if (authStore.userInfo.canMigrateSuperior) {
    opts.unshift({
      label: '上级迁移',
      key: 'sjqy',
      icon: SvgIconVNode({ icon: 'ph:arrows-left-right', fontSize: 18 })
    });
  }

  return opts;
});

function logout() {
  window.$dialog?.info({
    title: $t('common.tip'),
    content: $t('common.logoutConfirm'),
    positiveText: $t('common.confirm'),
    negativeText: $t('common.cancel'),
    onPositiveClick: async () => {
      await authStore.logout();
    }
  });
}

function handleDropdown(key: DropdownKey) {
  if (key === 'logout') {
    logout();
  } else {
    // If your other options are jumps from other routes, they will be directly supported here
    routerPushByKey(key);
  }
}
</script>

<template>
  <NButton v-if="!authStore.isLogin" quaternary @click="loginOrRegister">
    {{ $t('page.login.common.loginOrRegister') }}
  </NButton>
  <NDropdown v-else placement="bottom" trigger="click" :options="options" @select="handleDropdown">
    <div>
      <ButtonIcon class="px-8px py-4px">
        <NAvatar
          round
          :size="28"
          :src="avatarUrl"
          fallback-src="https://q1.qlogo.cn/g?b=qq&nk=10001&s=100"
          class="mr-8px border border-primary/20 shadow-sm"
        />
        <span class="text-15px font-medium">{{ authStore.userInfo.displayName || authStore.userInfo.userName }}</span>
      </ButtonIcon>
    </div>
  </NDropdown>
</template>

<style scoped></style>
