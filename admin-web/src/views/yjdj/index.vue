<script setup lang="ts">
import { computed, h, onMounted, reactive, ref } from 'vue';
import type { DataTableRowKey, DataTableColumns } from 'naive-ui';
import { NButton, NInput, NInputNumber, NRadio, NRadioGroup, NSpace, NTag } from 'naive-ui';
import {
  batchOnlineYjdjClasses,
  copyYjdjFenlei,
  fetchClassOptions,
  fetchHuoyuanList,
  fetchYjdjRemoteClasses
} from '@/service/api';

defineOptions({ name: 'Yjdj' });

const loading = ref(false);
const huoyuanLoading = ref(false);
const actionLoading = ref(false);

const selectedHid = ref<string>('');
const huoyuanOptions = ref<Array<{ label: string; value: string }>>([]);
const localCategoryOptions = ref<Array<{ label: string; value: string }>>([]);

// 上游拉取的全部数据
const rawClasses = ref<Api.Yjdj.RemoteClassItem[]>([]);
const remoteCategories = ref<string[]>([]);

// 搜索与过滤
const filterKeyword = ref('');
const filterStatus = ref<'all' | 'online' | 'offline'>('all');

// 选中的行（上游 cid）
const checkedRowKeys = ref<DataTableRowKey[]>([]);

// 本地价格覆盖（批量改价后的价格映射，cid -> price）
const priceOverrideMap = reactive<Record<string, string>>({});

// 弹窗状态
const copyModalVisible = ref(false);
const copyMode = ref<'fenlei_only' | 'fenlei_and_class'>('fenlei_only');

const priceModalVisible = ref(false);
const priceAdjustType = ref<'fixed' | 'multiply' | 'add'>('multiply');
const priceFixedValue = ref<number>(1.5);
const priceMultiplyFactor = ref<number>(1.2);
const priceAddAmount = ref<number>(0.3);

const onlineModalVisible = ref(false);
const categoryMode = ref<'default' | 'specified' | 'custom'>('default');
const specifiedCategoryId = ref<string>('');
const customCategoryName = ref<string>('');

// 过滤后的显示列表
const filteredList = computed(() => {
  const kw = filterKeyword.value.trim().toLowerCase();
  return rawClasses.value.filter(item => {
    if (filterStatus.value === 'online' && !item.isOnline) return false;
    if (filterStatus.value === 'offline' && item.isOnline) return false;
    if (kw) {
      const matchName = item.name.toLowerCase().includes(kw);
      const matchCid = item.cid.toLowerCase().includes(kw);
      const matchFenlei = item.fenleiname.toLowerCase().includes(kw);
      if (!matchName && !matchCid && !matchFenlei) return false;
    }
    return true;
  });
});

const onlineCount = computed(() => rawClasses.value.filter(i => i.isOnline).length);
const offlineCount = computed(() => rawClasses.value.filter(i => !i.isOnline).length);

const columns: DataTableColumns<Api.Yjdj.RemoteClassItem> = [
  { type: 'selection', fixed: 'left' },
  { title: '上游CID', key: 'cid', width: 90, fixed: 'left' },
  {
    title: '课程名称',
    key: 'name',
    minWidth: 180,
    render: row => h('span', { class: 'font-500' }, row.name)
  },
  {
    title: '原分类',
    key: 'fenleiname',
    width: 120,
    render: row => h(NTag, { size: 'small', type: 'info', round: true }, { default: () => row.fenleiname || '未分类' })
  },
  {
    title: '上架定价',
    key: 'price',
    width: 120,
    render: row => {
      const currentPrice = priceOverrideMap[row.cid] !== undefined ? priceOverrideMap[row.cid] : row.price;
      const isModified = priceOverrideMap[row.cid] !== undefined;
      return h(
        'span',
        { class: isModified ? 'font-bold text-success' : 'text-gray-700 dark:text-gray-300' },
        `¥ ${Number(currentPrice).toFixed(2)}`
      );
    }
  },
  {
    title: '上架状态',
    key: 'isOnline',
    width: 110,
    render: row =>
      h(
        NTag,
        {
          type: row.isOnline ? 'success' : 'default',
          size: 'small'
        },
        { default: () => (row.isOnline ? '已在售' : '未上架') }
      )
  },
  {
    title: '操作',
    key: 'actions',
    width: 100,
    fixed: 'right',
    render: row =>
      h(
        NButton,
        {
          size: 'small',
          type: 'primary',
          ghost: true,
          onClick: () => handleQuickOnlineSingle(row)
        },
        { default: () => (row.isOnline ? '更新上架' : '上架') }
      )
  }
];

async function loadHuoyuanList() {
  huoyuanLoading.value = true;
  const { data } = await fetchHuoyuanList();
  huoyuanLoading.value = false;
  if (data?.list) {
    huoyuanOptions.value = data.list
      .filter(hItem => hItem.status === 1)
      .map(hItem => ({
        label: `${hItem.name} [${hItem.pt}] (${hItem.url || '无URL'})`,
        value: hItem.hid
      }));
    if (huoyuanOptions.value.length && !selectedHid.value) {
      selectedHid.value = huoyuanOptions.value[0].value;
    }
  }

  // 加载本地分类选项供指定分类使用
  const optRes = await fetchClassOptions();
  if (optRes.data?.fenleiList) {
    localCategoryOptions.value = optRes.data.fenleiList;
    if (localCategoryOptions.value.length) {
      specifiedCategoryId.value = localCategoryOptions.value[0].value;
    }
  }
}

async function handleFetchRemote() {
  if (!selectedHid.value) {
    window.$message?.warning('请先选择上游货源平台');
    return;
  }

  loading.value = true;
  const { data, error } = await fetchYjdjRemoteClasses(selectedHid.value);
  loading.value = false;

  if (!error && data) {
    rawClasses.value = data.classes;
    remoteCategories.value = data.categories;
    checkedRowKeys.value = [];
    for (const k of Object.keys(priceOverrideMap)) {
      delete priceOverrideMap[k];
    }
    window.$message?.success(`成功拉取到 ${data.total} 门课程数据`);
  }
}

function openCopyModal() {
  if (!selectedHid.value) {
    window.$message?.warning('请先选择货源');
    return;
  }
  copyModalVisible.value = true;
}

async function handleConfirmCopy() {
  actionLoading.value = true;
  const { data, error } = await copyYjdjFenlei(selectedHid.value, copyMode.value);
  actionLoading.value = false;

  if (!error && data) {
    window.$message?.success('复制分类操作完成');
    copyModalVisible.value = false;
    // 重新拉取以更新在售标记
    handleFetchRemote();
  }
}

function openPriceModal() {
  if (!checkedRowKeys.value.length) {
    window.$message?.warning('请先勾选需要改价的课程');
    return;
  }
  priceModalVisible.value = true;
}

function handleConfirmPriceAdjust() {
  const selectedCids = checkedRowKeys.value as string[];
  const map = new Map(rawClasses.value.map(i => [i.cid, i]));

  for (const cid of selectedCids) {
    const item = map.get(cid);
    if (!item) continue;
    const basePrice = Number(item.price) || 0;
    let newPrice = basePrice;

    if (priceAdjustType.value === 'fixed') {
      newPrice = Number(priceFixedValue.value) || 0;
    } else if (priceAdjustType.value === 'multiply') {
      newPrice = basePrice * (Number(priceMultiplyFactor.value) || 1);
    } else if (priceAdjustType.value === 'add') {
      newPrice = basePrice + (Number(priceAddAmount.value) || 0);
    }

    if (newPrice < 0) newPrice = 0;
    priceOverrideMap[cid] = newPrice.toFixed(2);
  }

  window.$message?.success(`已为选中的 ${selectedCids.length} 门课程完成价格调整`);
  priceModalVisible.value = false;
}

function openOnlineModal() {
  if (!checkedRowKeys.value.length) {
    window.$message?.warning('请先勾选需要上架的课程');
    return;
  }
  onlineModalVisible.value = true;
}

async function handleConfirmOnline() {
  const selectedCids = checkedRowKeys.value as string[];
  const map = new Map(rawClasses.value.map(i => [i.cid, i]));

  const coursesPayload = selectedCids.map(cid => {
    const item = map.get(cid)!;
    const finalPrice = priceOverrideMap[cid] !== undefined ? priceOverrideMap[cid] : item.price;
    return {
      cid: item.cid,
      name: item.name,
      price: finalPrice,
      fenleiname: item.fenleiname,
      content: item.content
    };
  });

  actionLoading.value = true;
  const { data, error } = await batchOnlineYjdjClasses({
    hid: selectedHid.value,
    courses: coursesPayload,
    categoryMode: categoryMode.value,
    categoryId: categoryMode.value === 'specified' ? specifiedCategoryId.value : undefined,
    customCategoryName: categoryMode.value === 'custom' ? customCategoryName.value.trim() : undefined
  });
  actionLoading.value = false;

  if (!error && data) {
    window.$message?.success(`批量上架成功：新增 ${data.createdCount} 门，更新 ${data.updatedCount} 门`);
    onlineModalVisible.value = false;
    // 刷新数据
    handleFetchRemote();
  }
}

async function handleQuickOnlineSingle(row: Api.Yjdj.RemoteClassItem) {
  const finalPrice = priceOverrideMap[row.cid] !== undefined ? priceOverrideMap[row.cid] : row.price;
  actionLoading.value = true;
  const { error } = await batchOnlineYjdjClasses({
    hid: selectedHid.value,
    courses: [
      {
        cid: row.cid,
        name: row.name,
        price: finalPrice,
        fenleiname: row.fenleiname,
        content: row.content
      }
    ],
    categoryMode: 'default'
  });
  actionLoading.value = false;

  if (!error) {
    window.$message?.success(`课程【${row.name}】上架成功`);
    row.isOnline = true;
  }
}

onMounted(() => {
  loadHuoyuanList();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="一键对接" :bordered="false" class="rounded-8px shadow-sm">
      <template #header-extra>
        <span class="text-13px text-gray-500">
          支持从上游货源一键拉取课程、克隆分类、公式改价与批量上架入库
        </span>
      </template>

      <!-- 货源选择与顶层操作栏 -->
      <div class="mb-16px flex flex-wrap items-center gap-12px rounded-8px border border-dashed border-gray-200 p-14px dark:border-dark-400">
        <span class="font-500 text-gray-700 dark:text-gray-200">选择目标货源：</span>
        <NSelect
          v-model:value="selectedHid"
          :options="huoyuanOptions"
          :loading="huoyuanLoading"
          placeholder="请选择货源接口"
          class="min-w-280px max-w-400px"
        />
        <NButton type="primary" :loading="loading" @click="handleFetchRemote">
          <template #icon>
            <icon-ic-round-cloud-download class="text-18px" />
          </template>
          拉取课程数据
        </NButton>
        <NButton type="info" ghost :disabled="!selectedHid" @click="openCopyModal">
          <template #icon>
            <icon-ic-round-content-copy class="text-18px" />
          </template>
          复制上游分类
        </NButton>
      </div>

      <!-- 检索与统计区（有数据时展示） -->
      <div v-if="rawClasses.length > 0" class="mb-16px flex flex-wrap items-center justify-between gap-12px">
        <div class="flex flex-wrap items-center gap-10px">
          <NInput
            v-model:value="filterKeyword"
            placeholder="搜索课程名称 / 上游CID / 分类..."
            clearable
            class="w-260px"
          />
          <NRadioGroup v-model:value="filterStatus">
            <NRadio value="all">全部 ({{ rawClasses.length }})</NRadio>
            <NRadio value="online">已在售 ({{ onlineCount }})</NRadio>
            <NRadio value="offline">未上架 ({{ offlineCount }})</NRadio>
          </NRadioGroup>
        </div>

        <div class="flex items-center gap-8px">
          <span class="text-13px text-gray-600 dark:text-gray-300">
            已选中 <strong class="text-primary">{{ checkedRowKeys.length }}</strong> 门
          </span>
          <NButton
            size="small"
            type="warning"
            :disabled="!checkedRowKeys.length"
            @click="openPriceModal"
          >
            批量公式改价
          </NButton>
          <NButton
            size="small"
            type="success"
            :disabled="!checkedRowKeys.length"
            @click="openOnlineModal"
          >
            批量上架到数据库
          </NButton>
        </div>
      </div>

      <!-- 数据表格 -->
      <NDataTable
        v-if="rawClasses.length > 0"
        v-model:checked-row-keys="checkedRowKeys"
        :loading="loading"
        :columns="columns"
        :data="filteredList"
        :row-key="(row: Api.Yjdj.RemoteClassItem) => row.cid"
        :pagination="{ pageSize: 50, showSizePicker: true, pageSizes: [20, 50, 100, 200] }"
        striped
        :max-height="620"
        :scroll-x="900"
      />

      <div v-else-if="!loading" class="flex-center py-60px text-gray-400">
        <div class="text-center">
          <icon-ic-round-inbox class="text-48px text-gray-300" />
          <p class="mt-8px">请选择上方货源接口并点击“拉取课程数据”</p>
        </div>
      </div>
    </NCard>

    <!-- 复制分类模式弹窗 -->
    <NModal
      v-model:show="copyModalVisible"
      preset="card"
      title="复制分类模式选择"
      class="max-w-480px"
      :mask-closable="false"
    >
      <div class="flex flex-col gap-12px">
        <p class="text-14px text-gray-600 dark:text-gray-300">请选择您需要将上游货源克隆到本地的方式：</p>
        <NRadioGroup v-model:value="copyMode">
          <div class="flex flex-col gap-12px">
            <div class="cursor-pointer rounded-6px border p-12px" :class="copyMode === 'fenlei_only' ? 'border-primary bg-primary-50 dark:bg-dark-600' : 'border-gray-200'">
              <NRadio value="fenlei_only">
                <span class="font-600">仅复制分类名称</span>
              </NRadio>
              <p class="mt-4px text-12px text-gray-500">仅将上游所有分类名称自动录入本地分类表中，不导入课程。</p>
            </div>
            <div class="cursor-pointer rounded-6px border p-12px" :class="copyMode === 'fenlei_and_class' ? 'border-primary bg-primary-50 dark:bg-dark-600' : 'border-gray-200'">
              <NRadio value="fenlei_and_class">
                <span class="font-600">复制分类及全部课程数据</span>
              </NRadio>
              <p class="mt-4px text-12px text-gray-500">将分类名称与上游的所有网课课程全部自动克隆上架到数据库中。</p>
            </div>
          </div>
        </NRadioGroup>
      </div>
      <template #footer>
        <div class="flex justify-end gap-12px">
          <NButton @click="copyModalVisible = false">取消</NButton>
          <NButton type="primary" :loading="actionLoading" @click="handleConfirmCopy">确认复制</NButton>
        </div>
      </template>
    </NModal>

    <!-- 批量改价弹窗 -->
    <NModal
      v-model:show="priceModalVisible"
      preset="card"
      title="批量改价"
      class="max-w-500px"
      :mask-closable="false"
    >
      <div class="flex flex-col gap-16px">
        <div class="text-13px text-gray-600 dark:text-gray-300">
          已选中 <strong class="text-primary">{{ checkedRowKeys.length }}</strong> 门课程，请选择定价公式：
        </div>
        <NRadioGroup v-model:value="priceAdjustType">
          <NSpace vertical>
            <NRadio value="multiply">
              按倍率浮动加价（推荐）
              <div v-if="priceAdjustType === 'multiply'" class="mt-8px flex items-center gap-6px">
                <span class="text-12px text-gray-500">上游价 ×</span>
                <NInputNumber v-model:value="priceMultiplyFactor" :min="0.1" :max="10" :step="0.05" size="small" class="w-120px" />
                <span class="text-12px text-gray-500">倍（例：1.2 代表利润率 20%）</span>
              </div>
            </NRadio>

            <NRadio value="add">
              固定金额加价
              <div v-if="priceAdjustType === 'add'" class="mt-8px flex items-center gap-6px">
                <span class="text-12px text-gray-500">上游价 +</span>
                <NInputNumber v-model:value="priceAddAmount" :step="0.1" size="small" class="w-120px" />
                <span class="text-12px text-gray-500">元</span>
              </div>
            </NRadio>

            <NRadio value="fixed">
              统一固定价格
              <div v-if="priceAdjustType === 'fixed'" class="mt-8px flex items-center gap-6px">
                <span class="text-12px text-gray-500">固定为 ¥</span>
                <NInputNumber v-model:value="priceFixedValue" :min="0" :step="0.1" size="small" class="w-120px" />
                <span class="text-12px text-gray-500">元</span>
              </div>
            </NRadio>
          </NSpace>
        </NRadioGroup>
      </div>
      <template #footer>
        <div class="flex justify-end gap-12px">
          <NButton @click="priceModalVisible = false">取消</NButton>
          <NButton type="primary" @click="handleConfirmPriceAdjust">应用到勾选课程</NButton>
        </div>
      </template>
    </NModal>

    <!-- 批量上架设置弹窗 -->
    <NModal
      v-model:show="onlineModalVisible"
      preset="card"
      title="批量上架到数据库"
      class="max-w-520px"
      :mask-closable="false"
    >
      <div class="flex flex-col gap-16px">
        <div class="rounded-6px bg-blue-50 p-10px text-13px text-blue-700 dark:bg-dark-600 dark:text-blue-300">
          准备将勾选的 <strong>{{ checkedRowKeys.length }}</strong> 门课程上架到平台商品库中。
        </div>

        <div>
          <div class="mb-8px font-500 text-gray-700 dark:text-gray-200">目标分类归属方式：</div>
          <NRadioGroup v-model:value="categoryMode">
            <NSpace vertical>
              <NRadio value="default">
                自动归入上游对应分类（本地不存在将自动新建同名分类）
              </NRadio>
              <NRadio value="specified">
                指定归入本地已有分类
                <div v-if="categoryMode === 'specified'" class="mt-8px">
                  <NSelect
                    v-model:value="specifiedCategoryId"
                    :options="localCategoryOptions"
                    placeholder="选择已有分类"
                    class="w-240px"
                  />
                </div>
              </NRadio>
              <NRadio value="custom">
                新建一个自定义分类全部归入
                <div v-if="categoryMode === 'custom'" class="mt-8px">
                  <NInput
                    v-model:value="customCategoryName"
                    placeholder="输入新分类名称，如：推荐精选"
                    class="w-240px"
                  />
                </div>
              </NRadio>
            </NSpace>
          </NRadioGroup>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-12px">
          <NButton @click="onlineModalVisible = false">取消</NButton>
          <NButton type="primary" :loading="actionLoading" @click="handleConfirmOnline">确认上架入库</NButton>
        </div>
      </template>
    </NModal>
  </div>
</template>
