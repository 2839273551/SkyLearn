<script setup lang="ts">
import { computed, h, onMounted, reactive, ref } from 'vue';
import type { DataTableRowKey, DataTableColumns } from 'naive-ui';
import {
  NAlert,
  NBadge,
  NButton,
  NCard,
  NCheckbox,
  NCollapse,
  NCollapseItem,
  NDataTable,
  NDivider,
  NEmpty,
  NForm,
  NFormItem,
  NGi,
  NGrid,
  NInput,
  NInputNumber,
  NModal,
  NPopconfirm,
  NRadio,
  NRadioGroup,
  NSelect,
  NSpace,
  NSpin,
  NTabPane,
  NTabs,
  NTag,
  NTooltip
} from 'naive-ui';
import {
  batchOnlineYjdjClasses,
  checkYjdjBalance,
  checkYjdjDeployedCount,
  copyYjdjFenlei,
  executeYjdjAdvancedTool,
  fetchClassOptions,
  fetchHuoyuanList,
  fetchYjdjRemoteClasses
} from '@/service/api';

defineOptions({ name: 'Yjdj' });

const loading = ref(false);
const huoyuanLoading = ref(false);
const actionLoading = ref(false);
const balanceLoading = ref(false);
const deployedLoading = ref(false);

const selectedHid = ref<string>('');
const huoyuanOptions = ref<Array<{ label: string; value: string }>>([]);
const localCategoryOptions = ref<Array<{ label: string; value: string }>>([]);

// 货源探测状态
const huoyuanBalance = ref<string | null>(null);
const huoyuanDeployedCount = ref<number | null>(null);

// 上游拉取的全部数据与分组
const rawClasses = ref<Api.Yjdj.RemoteClassItem[]>([]);
const upstreamCategories = ref<Array<{ value: string; label: string; count: number }>>([]);

// 上架配置与定价引擎状态 (从 yjdj.php 移植融合)
const createNewCategory = ref<'0' | '1'>('0');
const localCategoryId = ref<string>('');
const newCategoryName = ref<string>('');
const markupMultiplier = ref<number>(1.0);
const multiplyByFive = ref<number>(2); // 2: 乘5(29系统), 1: 不乘5(暗网), 0: 直接加价
const skipExisting = ref<number>(1); // 1: 跳过已有, 0: 不跳过覆盖

// 分类上架专用选择
const upstreamCategoryForOnline = ref<string>('all');

// 选中的行（上游 cid 映射表）
const selectedCidSet = ref<Set<string>>(new Set());

// 搜索过滤
const filterKeyword = ref('');
const filterUpstreamFenlei = ref('all');
const activeCategoryPanels = ref<string[]>([]);
const categorySearchTerms = reactive<Record<string, string>>({});

// 高阶批量运维工具箱表单
const advancedActive = ref(false);
const advKeywordOld = ref('');
const advKeywordNew = ref('');
const advKeywordScope = ref<'all' | 'category' | 'docking'>('all');
const advKeywordScopeId = ref('');

const advPrefixText = ref('');
const advPrefixScope = ref<'category' | 'docking'>('category');
const advPrefixScopeId = ref('');

const advDupScope = ref<'all' | 'category' | 'docking'>('all');
const advDupScopeId = ref('');
const advDupStrategy = ref<'keep_larger' | 'keep_smaller' | 'delall'>('keep_larger');

// 复制分类弹窗
const copyModalVisible = ref(false);
const copyMode = ref<'fenlei_only' | 'fenlei_and_class'>('fenlei_only');

// 按分类分组后的数据结构
interface ProductGroup {
  fenlei: string;
  fenleiname: string;
  products: Api.Yjdj.RemoteClassItem[];
}

const groupedProducts = computed<ProductGroup[]>(() => {
  if (!rawClasses.value.length) return [];
  const map: Record<string, ProductGroup> = {};

  for (const item of rawClasses.value) {
    const fn = item.fenleiname || '未分类';
    if (!map[fn]) {
      map[fn] = {
        fenlei: fn,
        fenleiname: fn,
        products: []
      };
    }
    map[fn].products.push(item);
  }

  return Object.values(map);
});

// 计算加价后的预计单价
function calculatePrice(rawPriceStr: string) {
  const base = Number(rawPriceStr) || 0;
  const mult = Number(markupMultiplier.value) || 1;
  let finalP = base;
  if (multiplyByFive.value === 2) {
    finalP = base * mult * 5;
  } else if (multiplyByFive.value === 1) {
    finalP = base * mult;
  } else if (multiplyByFive.value === 0) {
    finalP = base + mult;
  }
  return Math.max(0, finalP).toFixed(2);
}

// 过滤后的分组数据
const filteredGroups = computed(() => {
  const kw = filterKeyword.value.trim().toLowerCase();
  const upFl = filterUpstreamFenlei.value;

  return groupedProducts.value
    .filter(g => {
      if (upFl !== 'all' && g.fenlei !== upFl) return false;
      if (!kw) return true;
      const matchGroup = g.fenleiname.toLowerCase().includes(kw);
      const matchProduct = g.products.some(
        p => p.name.toLowerCase().includes(kw) || p.cid.toLowerCase().includes(kw)
      );
      return matchGroup || matchProduct;
    })
    .map(g => {
      const catKw = (categorySearchTerms[g.fenlei] || '').trim().toLowerCase();
      let list = g.products;
      if (catKw) {
        list = list.filter(p => p.name.toLowerCase().includes(catKw) || p.cid.toLowerCase().includes(catKw));
      } else if (kw) {
        list = list.filter(p => p.name.toLowerCase().includes(kw) || p.cid.toLowerCase().includes(kw));
      }
      return {
        ...g,
        products: list
      };
    })
    .filter(g => g.products.length > 0);
});

const totalSelectedCount = computed(() => selectedCidSet.value.size);

function isCategoryAllSelected(group: ProductGroup) {
  if (!group.products.length) return false;
  return group.products.every(p => selectedCidSet.value.has(p.cid));
}

function isCategoryIndeterminate(group: ProductGroup) {
  const selectedInGroup = group.products.filter(p => selectedCidSet.value.has(p.cid)).length;
  return selectedInGroup > 0 && selectedInGroup < group.products.length;
}

function toggleCategorySelect(group: ProductGroup, checked: boolean) {
  const next = new Set(selectedCidSet.value);
  for (const p of group.products) {
    if (checked) next.add(p.cid);
    else next.delete(p.cid);
  }
  selectedCidSet.value = next;
}

function toggleProductSelect(cid: string, checked: boolean) {
  const next = new Set(selectedCidSet.value);
  if (checked) next.add(cid);
  else next.delete(cid);
  selectedCidSet.value = next;
}

function selectAllProducts() {
  const next = new Set<string>();
  for (const g of filteredGroups.value) {
    for (const p of g.products) next.add(p.cid);
  }
  selectedCidSet.value = next;
  window.$message?.info(`已全选当前显示的 ${next.size} 门课程`);
}

function clearAllSelections() {
  selectedCidSet.value = new Set();
}

function expandAllPanels() {
  activeCategoryPanels.value = filteredGroups.value.map(g => g.fenlei);
}
function collapseAllPanels() {
  activeCategoryPanels.value = [];
}

async function loadHuoyuanList() {
  huoyuanLoading.value = true;
  const { data } = await fetchHuoyuanList();
  huoyuanLoading.value = false;
  if (data?.list) {
    huoyuanOptions.value = data.list
      .filter(hItem => hItem.status === 1)
      .map(hItem => ({
        label: `${hItem.name} [${hItem.pt}] (${hItem.url || '无URL'})`,
        value: String(hItem.hid)
      }));
    if (huoyuanOptions.value.length && !selectedHid.value) {
      selectedHid.value = huoyuanOptions.value[0].value;
    }
  }

  const optRes = await fetchClassOptions();
  if (optRes.data?.fenleiList) {
    localCategoryOptions.value = optRes.data.fenleiList.filter(f => f.value !== 'wck');
    if (localCategoryOptions.value.length && !localCategoryId.value) {
      localCategoryId.value = localCategoryOptions.value[0].value;
    }
  }
}

async function handleCheckBalance() {
  if (!selectedHid.value) {
    window.$message?.warning('请先选择货源接口');
    return;
  }
  balanceLoading.value = true;
  const { data, error } = await checkYjdjBalance(selectedHid.value);
  balanceLoading.value = false;

  if (!error && data) {
    huoyuanBalance.value = data.balance;
    window.$dialog?.info({
      title: '上游货源余额探测',
      content: `接口【${data.name}】当前账户可用余额为：¥ ${data.balance} 元`,
      positiveText: '确定'
    });
  }
}

async function handleCheckDeployedCount() {
  if (!selectedHid.value) {
    window.$message?.warning('请先选择货源接口');
    return;
  }
  deployedLoading.value = true;
  const { data, error } = await checkYjdjDeployedCount(selectedHid.value);
  deployedLoading.value = false;

  if (!error && data) {
    huoyuanDeployedCount.value = data.count;
    window.$dialog?.info({
      title: '货源已上架统计',
      content: `接口【${data.name}】在当前本站数据库中已上架商品：${data.count} 门`,
      positiveText: '确定'
    });
  }
}

async function handleFetchRemote() {
  if (!selectedHid.value) {
    window.$message?.warning('请先选择上游货源平台');
    return;
  }

  loading.value = true;
  huoyuanBalance.value = null;
  huoyuanDeployedCount.value = null;
  const { data, error } = await fetchYjdjRemoteClasses(selectedHid.value);
  loading.value = false;

  if (!error && data) {
    rawClasses.value = data.classes;
    selectedCidSet.value = new Set();

    const upOptions: Array<{ value: string; label: string; count: number }> = [
      { value: 'all', label: `全部分类 (共 ${data.total} 门)`, count: data.total }
    ];
    for (const g of groupedProducts.value) {
      upOptions.push({
        value: g.fenlei,
        label: `分类: ${g.fenleiname} (共 ${g.products.length} 门)`,
        count: g.products.length
      });
    }
    upstreamCategories.value = upOptions;
    activeCategoryPanels.value = groupedProducts.value.map(g => g.fenlei);

    window.$message?.success(`成功拉取到 ${data.total} 门商品，已按 ${groupedProducts.value.length} 个上游分类归类整理`);
  }
}

async function handleStartIntegrationSelected() {
  if (totalSelectedCount.value === 0) {
    window.$message?.warning('请先在下方勾选需要上架的商品');
    return;
  }
  if (createNewCategory.value === '1' && !newCategoryName.value.trim()) {
    window.$message?.warning('请输入新建分类名称');
    return;
  }
  if (createNewCategory.value === '0' && !localCategoryId.value) {
    window.$message?.warning('请选择上架分类');
    return;
  }

  const selectedCids = Array.from(selectedCidSet.value);
  const map = new Map(rawClasses.value.map(i => [i.cid, i]));
  const coursesPayload = selectedCids
    .map(cid => map.get(cid))
    .filter(Boolean)
    .map(item => ({
      cid: item!.cid,
      name: item!.name,
      price: item!.price,
      fenleiname: item!.fenleiname,
      content: item!.content
    }));

  actionLoading.value = true;
  const { data, error } = await batchOnlineYjdjClasses({
    hid: selectedHid.value,
    courses: coursesPayload,
    createNewCategory: createNewCategory.value,
    newCategoryName: newCategoryName.value.trim(),
    localCategoryId: localCategoryId.value,
    markupMultiplier: Number(markupMultiplier.value) || 1.0,
    multiplyByFive: Number(multiplyByFive.value),
    skipExisting: Number(skipExisting.value)
  });
  actionLoading.value = false;

  if (!error && data) {
    window.$dialog?.success({
      title: '上架成功',
      content: `批量上架完成！新增上架 ${data.createdCount} 门，更新原有 ${data.updatedCount} 门${
        data.skippedCount ? `，跳过已有 ${data.skippedCount} 门` : ''
      }。`,
      positiveText: '完成'
    });
    handleFetchRemote();
    loadHuoyuanList();
  }
}

async function handleStartIntegrationByCategory() {
  if (createNewCategory.value === '1' && !newCategoryName.value.trim()) {
    window.$message?.warning('请输入新建分类名称');
    return;
  }
  if (createNewCategory.value === '0' && !localCategoryId.value) {
    window.$message?.warning('请选择上架分类');
    return;
  }

  const targetCategory = upstreamCategoryForOnline.value;
  let targetCourses = rawClasses.value;
  if (targetCategory !== 'all') {
    targetCourses = rawClasses.value.filter(p => (p.fenleiname || '未分类') === targetCategory);
  }

  if (!targetCourses.length) {
    window.$message?.warning('所选分类下无可上架商品');
    return;
  }

  const coursesPayload = targetCourses.map(item => ({
    cid: item.cid,
    name: item.name,
    price: item.price,
    fenleiname: item.fenleiname,
    content: item.content
  }));

  actionLoading.value = true;
  const { data, error } = await batchOnlineYjdjClasses({
    hid: selectedHid.value,
    courses: coursesPayload,
    createNewCategory: createNewCategory.value,
    newCategoryName: newCategoryName.value.trim(),
    localCategoryId: localCategoryId.value,
    markupMultiplier: Number(markupMultiplier.value) || 1.0,
    multiplyByFive: Number(multiplyByFive.value),
    skipExisting: Number(skipExisting.value)
  });
  actionLoading.value = false;

  if (!error && data) {
    window.$dialog?.success({
      title: '分类直上一键完成',
      content: `分类直上完成！新增上架 ${data.createdCount} 门，更新原有 ${data.updatedCount} 门${
        data.skippedCount ? `，跳过已有 ${data.skippedCount} 门` : ''
      }。`,
      positiveText: '完成'
    });
    handleFetchRemote();
    loadHuoyuanList();
  }
}

async function handleExecuteKeywordReplace() {
  if (!advKeywordOld.value.trim()) {
    window.$message?.warning('请输入要替换的原关键词');
    return;
  }
  actionLoading.value = true;
  const { error } = await executeYjdjAdvancedTool({
    tool: 'update_keywords',
    oldKeyword: advKeywordOld.value.trim(),
    newKeyword: advKeywordNew.value.trim(),
    scope: advKeywordScope.value,
    scopeId: advKeywordScopeId.value.trim()
  });
  actionLoading.value = false;
  if (!error) {
    window.$message?.success('关键词批量替换成功');
    advKeywordOld.value = '';
    advKeywordNew.value = '';
  }
}

async function handleExecuteAddPrefix() {
  if (!advPrefixText.value.trim()) {
    window.$message?.warning('请输入要新增的商品前缀');
    return;
  }
  if (!advPrefixScopeId.value.trim()) {
    window.$message?.warning('请输入分类ID或对接平台ID');
    return;
  }
  actionLoading.value = true;
  const { error } = await executeYjdjAdvancedTool({
    tool: 'add_prefix',
    prefix: advPrefixText.value.trim(),
    scope: advPrefixScope.value,
    scopeId: advPrefixScopeId.value.trim()
  });
  actionLoading.value = false;
  if (!error) {
    window.$message?.success('商品前缀批量添加成功');
    advPrefixText.value = '';
  }
}

async function handleExecuteDeduplicate() {
  if (advDupScope.value !== 'all' && !advDupScopeId.value.trim()) {
    window.$message?.warning('请输入分类ID或对接平台ID');
    return;
  }
  actionLoading.value = true;
  const { error } = await executeYjdjAdvancedTool({
    tool: 'delete_duplicates',
    scope: advDupScope.value,
    scopeId: advDupScopeId.value.trim(),
    strategy: advDupStrategy.value
  });
  actionLoading.value = false;
  if (!error) {
    window.$message?.success('批量去重清理执行完毕');
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
  const { error } = await copyYjdjFenlei(selectedHid.value, copyMode.value);
  actionLoading.value = false;

  if (!error) {
    window.$message?.success('克隆分类操作完成');
    copyModalVisible.value = false;
    handleFetchRemote();
    loadHuoyuanList();
  }
}

onMounted(() => {
  loadHuoyuanList();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <!-- 头部卡片：货源选择、信息探测与拉取 -->
    <NCard title="综合一键对接" :bordered="false" class="rounded-8px shadow-sm">
      <template #header-extra>
        <span class="text-13px text-gray-500">
          支持从上游货源实时拉取、分类手风琴聚合、多算法加价上架、余额探测与高阶运维工具箱
        </span>
      </template>

      <div class="flex flex-wrap items-center gap-12px rounded-8px border border-gray-100 bg-gray-50/80 p-14px dark:border-dark-400 dark:bg-dark-600">
        <div class="flex items-center gap-8px">
          <span class="font-500 text-gray-700 dark:text-gray-200 shrink-0">选择货源：</span>
          <NSelect
            v-model:value="selectedHid"
            :options="huoyuanOptions"
            :loading="huoyuanLoading"
            placeholder="搜索并选择货源接口"
            class="min-w-260px max-w-360px"
          />
        </div>

        <NButton type="info" secondary :loading="balanceLoading" :disabled="!selectedHid" @click="handleCheckBalance">
          <template #icon>
            <icon-ph-wallet class="text-16px" />
          </template>
          查询货源余额
        </NButton>

        <NButton type="warning" secondary :loading="deployedLoading" :disabled="!selectedHid" @click="handleCheckDeployedCount">
          <template #icon>
            <icon-ph-package class="text-16px" />
          </template>
          查询已上架数
        </NButton>

        <NButton type="primary" :loading="loading" :disabled="!selectedHid" @click="handleFetchRemote">
          <template #icon>
            <icon-ic-round-cloud-download class="text-18px" />
          </template>
          获取商品 (拉取全部)
        </NButton>

        <NButton type="info" ghost :disabled="!selectedHid" @click="openCopyModal">
          <template #icon>
            <icon-ic-round-content-copy class="text-16px" />
          </template>
          克隆上游分类
        </NButton>

        <!-- 探测结果胶囊 -->
        <div v-if="huoyuanBalance !== null" class="flex items-center gap-6px rounded-6px bg-success/12 px-10px py-4px text-12px text-success font-bold">
          💰 货源可用余额：¥ {{ huoyuanBalance }}
        </div>
        <div v-if="huoyuanDeployedCount !== null" class="flex items-center gap-6px rounded-6px bg-primary/12 px-10px py-4px text-12px text-primary font-bold">
          📦 本地在售商品：{{ huoyuanDeployedCount }} 门
        </div>
      </div>
    </NCard>

    <!-- 上架配置与定价引擎控制台 -->
    <NCard title="上架配置与定价引擎" :bordered="false" class="rounded-8px shadow-sm">
      <div class="flex flex-col gap-14px">
        <NGrid cols="1 s:2 m:4" responsive="screen" :x-gap="16" :y-gap="12">
          <!-- 是否新建分类 -->
          <NGi>
            <div class="flex flex-col gap-6px">
              <label class="text-13px font-medium text-gray-700 dark:text-gray-300">是否新建分类：</label>
              <NRadioGroup v-model:value="createNewCategory" size="small">
                <NRadioButton value="0">否 (使用已有)</NRadioButton>
                <NRadioButton value="1">是 (新建分类)</NRadioButton>
              </NRadioGroup>
            </div>
          </NGi>

          <!-- 选择上架分类 / 新建分类名 -->
          <NGi>
            <div class="flex flex-col gap-6px">
              <label class="text-13px font-medium text-gray-700 dark:text-gray-300">
                {{ createNewCategory === '1' ? '新建分类名称：' : '选择上架分类：' }}
              </label>
              <NInput
                v-if="createNewCategory === '1'"
                v-model:value="newCategoryName"
                placeholder="请输入新分类名称"
                size="small"
                clearable
              />
              <NSelect
                v-else
                v-model:value="localCategoryId"
                :options="localCategoryOptions"
                placeholder="选择本地分类"
                size="small"
                filterable
              />
            </div>
          </NGi>

          <!-- 设置上架加价 -->
          <NGi>
            <div class="flex flex-col gap-6px">
              <label class="text-13px font-medium text-gray-700 dark:text-gray-300">设置加价数值：</label>
              <NInputNumber
                v-model:value="markupMultiplier"
                :min="0"
                :precision="2"
                size="small"
                placeholder="加价数值"
              />
            </div>
          </NGi>

          <!-- 加价计算方式 -->
          <NGi>
            <div class="flex flex-col gap-6px">
              <label class="text-13px font-medium text-gray-700 dark:text-gray-300">加价计算方式：</label>
              <NSelect
                v-model:value="multiplyByFive"
                :options="[
                  { label: '乘法且乘5 (29系统: 底价 * 加价 * 5)', value: 2 },
                  { label: '乘法且不乘5 (暗网: 底价 * 加价)', value: 1 },
                  { label: '加法直接加价 (底价 + 加价)', value: 0 }
                ]"
                size="small"
              />
            </div>
          </NGi>

          <!-- 处理已有商品 -->
          <NGi>
            <div class="flex flex-col gap-6px">
              <label class="text-13px font-medium text-gray-700 dark:text-gray-300">处理已有商品：</label>
              <NSelect
                v-model:value="skipExisting"
                :options="[
                  { label: '跳过已有商品 (安全防重)', value: 1 },
                  { label: '覆盖更新已有商品', value: 0 }
                ]"
                size="small"
              />
            </div>
          </NGi>

          <!-- 按分类整类直上选择 -->
          <NGi>
            <div class="flex flex-col gap-6px">
              <label class="text-13px font-medium text-gray-700 dark:text-gray-300">指定上游分类直上：</label>
              <NSelect
                v-model:value="upstreamCategoryForOnline"
                :options="upstreamCategories"
                placeholder="选择上游分类"
                size="small"
                :disabled="!rawClasses.length"
              />
            </div>
          </NGi>

          <!-- 操作按钮组 -->
          <NGi span="1 s:2">
            <div class="flex h-full items-end gap-10px">
              <NButton
                type="success"
                size="medium"
                class="flex-1 font-bold shadow-sm"
                :loading="actionLoading"
                :disabled="totalSelectedCount === 0"
                @click="handleStartIntegrationSelected"
              >
                🚀 上架选中商品 ({{ totalSelectedCount }} 门)
              </NButton>

              <NButton
                type="primary"
                size="medium"
                secondary
                class="flex-1 font-bold"
                :loading="actionLoading"
                :disabled="!rawClasses.length"
                @click="handleStartIntegrationByCategory"
              >
                ⚡ 按上游分类整类直上
              </NButton>
            </div>
          </NGi>
        </NGrid>
      </div>
    </NCard>

    <!-- 分类手风琴聚合商品列表 -->
    <NCard v-if="rawClasses.length > 0" :bordered="false" class="rounded-8px shadow-sm">
      <template #header>
        <div class="flex flex-wrap items-center gap-12px">
          <span class="font-bold text-16px">商品分类大盘</span>
          <NBadge :value="rawClasses.length" type="info" />
          <span class="text-13px text-gray-500">
            共 {{ groupedProducts.length }} 个分类 | 已勾选 <strong class="text-primary">{{ totalSelectedCount }}</strong> 门
          </span>
        </div>
      </template>

      <template #header-extra>
        <div class="flex flex-wrap items-center gap-8px">
          <NInput
            v-model:value="filterKeyword"
            placeholder="全局搜索商品名或CID..."
            size="small"
            clearable
            class="w-200px"
          />
          <NButton size="tiny" quaternary @click="selectAllProducts">全选当前</NButton>
          <NButton size="tiny" quaternary @click="clearAllSelections">清空选择</NButton>
          <NButton size="tiny" quaternary @click="expandAllPanels">展开全部</NButton>
          <NButton size="tiny" quaternary @click="collapseAllPanels">收起全部</NButton>
        </div>
      </template>

      <!-- 手风琴流 -->
      <NCollapse v-model:expanded-names="activeCategoryPanels">
        <NCollapseItem
          v-for="group in filteredGroups"
          :key="group.fenlei"
          :name="group.fenlei"
          class="border-b border-gray-100 dark:border-dark-500 py-4px"
        >
          <template #header>
            <div class="flex flex-1 items-center justify-between pr-12px" @click.stop>
              <div class="flex items-center gap-10px">
                <NCheckbox
                  :checked="isCategoryAllSelected(group)"
                  :indeterminate="isCategoryIndeterminate(group)"
                  @update:checked="(val: boolean) => toggleCategorySelect(group, val)"
                />
                <span class="font-bold text-14px text-gray-800 dark:text-gray-100">
                  {{ group.fenleiname }}
                </span>
                <span class="text-12px text-gray-400">
                  (共 {{ group.products.length }} 门)
                </span>
                <NTag
                  v-if="group.products.filter(p => selectedCidSet.has(p.cid)).length > 0"
                  size="tiny"
                  type="primary"
                  round
                >
                  已选 {{ group.products.filter(p => selectedCidSet.has(p.cid)).length }} 门
                </NTag>
              </div>

              <div class="flex items-center gap-8px" @click.stop>
                <NInput
                  v-model:value="categorySearchTerms[group.fenlei]"
                  :placeholder="`在 ${group.fenleiname} 内检索...`"
                  size="tiny"
                  clearable
                  class="w-160px"
                />
              </div>
            </div>
          </template>

          <!-- 内部商品轻量表格 -->
          <div class="p-8px bg-gray-50/50 dark:bg-dark-700/50 rounded-6px">
            <NDataTable
              :columns="[
                {
                  key: 'selection',
                  width: 48,
                  render: (row: Api.Yjdj.RemoteClassItem) =>
                    h(NCheckbox, {
                      checked: selectedCidSet.has(row.cid),
                      'onUpdate:checked': (val: boolean) => toggleProductSelect(row.cid, val)
                    })
                },
                { title: '上游CID', key: 'cid', width: 90 },
                { title: '商品名称', key: 'name', minWidth: 220 },
                {
                  title: '上游底价',
                  key: 'price',
                  width: 100,
                  render: (row: Api.Yjdj.RemoteClassItem) => `¥ ${Number(row.price).toFixed(2)}`
                },
                {
                  title: '预计上架售价',
                  key: 'calcPrice',
                  width: 120,
                  render: (row: Api.Yjdj.RemoteClassItem) =>
                    h('span', { class: 'font-bold text-emerald-600 dark:text-emerald-400' }, `¥ ${calculatePrice(row.price)}`)
                },
                {
                  title: '在售状态',
                  key: 'isOnline',
                  width: 100,
                  render: (row: Api.Yjdj.RemoteClassItem) =>
                    h(
                      NTag,
                      { size: 'tiny', type: row.isOnline ? 'success' : 'default', round: true },
                      { default: () => (row.isOnline ? '本地在售' : '未上架') }
                    )
                }
              ]"
              :data="group.products"
              :row-key="(row: Api.Yjdj.RemoteClassItem) => row.cid"
              :pagination="false"
              size="small"
              striped
            />
          </div>
        </NCollapseItem>
      </NCollapse>
    </NCard>

    <!-- 高阶批量操作模块 (从 yjdj.php 完整复刻升级) -->
    <NCard title="高阶批量操作工具箱" :bordered="false" class="rounded-8px shadow-sm">
      <template #header-extra>
        <NButton size="small" secondary type="primary" @click="advancedActive = !advancedActive">
          {{ advancedActive ? '收起工具箱' : '展开高阶工具箱' }}
        </NButton>
      </template>

      <NCollapse :expanded-names="advancedActive ? ['1'] : []">
        <NCollapseItem title="点击展开常用批量运维功能 (批量关键词替换、前缀追加、智能去重)" name="1">
          <NTabs type="line" animated>
            <!-- 工具 1：批量替换关键词 -->
            <NTabPane name="keywords" tab="🔤 批量替换关键词">
              <div class="flex flex-col gap-12px py-8px">
                <NAlert type="info" :show-icon="true" class="rounded-6px text-12px">
                  批量将课程名称中的敏感词或指定字词替换为新关键词（若留空“替换为”，则直接删除该关键词）。
                </NAlert>
                <div class="flex flex-wrap items-end gap-12px">
                  <div class="flex-1 min-w-180px">
                    <label class="mb-4px block text-12px font-medium">原关键词：</label>
                    <NInput v-model:value="advKeywordOld" placeholder="请输入要替换的词" />
                  </div>
                  <div class="flex-1 min-w-180px">
                    <label class="mb-4px block text-12px font-medium">替换为：</label>
                    <NInput v-model:value="advKeywordNew" placeholder="替换后的关键词(留空删除)" />
                  </div>
                  <div class="w-140px">
                    <label class="mb-4px block text-12px font-medium">生效范围：</label>
                    <NSelect
                      v-model:value="advKeywordScope"
                      :options="[
                        { label: '全站所有课程', value: 'all' },
                        { label: '指定分类ID', value: 'category' },
                        { label: '指定货源ID', value: 'docking' }
                      ]"
                    />
                  </div>
                  <div v-if="advKeywordScope !== 'all'" class="w-140px">
                    <label class="mb-4px block text-12px font-medium">范围ID：</label>
                    <NInput v-model:value="advKeywordScopeId" placeholder="输入对应ID" />
                  </div>
                  <NButton type="primary" :loading="actionLoading" @click="handleExecuteKeywordReplace">
                    立即更新关键词
                  </NButton>
                </div>
              </div>
            </NTabPane>

            <!-- 工具 2：批量添加前缀 -->
            <NTabPane name="prefix" tab="🏷️ 批量添加课程前缀">
              <div class="flex flex-col gap-12px py-8px">
                <NAlert type="info" :show-icon="true" class="rounded-6px text-12px">
                  批量在指定分类或货源的课程名称前追加统一前缀（例如：【高质量】、【秒刷全包】等）。
                </NAlert>
                <div class="flex flex-wrap items-end gap-12px">
                  <div class="flex-1 min-w-200px">
                    <label class="mb-4px block text-12px font-medium">要新增的前缀：</label>
                    <NInput v-model:value="advPrefixText" placeholder="如：【高质量】" />
                  </div>
                  <div class="w-140px">
                    <label class="mb-4px block text-12px font-medium">生效范围：</label>
                    <NSelect
                      v-model:value="advPrefixScope"
                      :options="[
                        { label: '指定分类ID', value: 'category' },
                        { label: '指定货源ID', value: 'docking' }
                      ]"
                    />
                  </div>
                  <div class="w-160px">
                    <label class="mb-4px block text-12px font-medium">分类ID / 货源ID：</label>
                    <NInput v-model:value="advPrefixScopeId" placeholder="输入对应ID" />
                  </div>
                  <NButton type="warning" :loading="actionLoading" @click="handleExecuteAddPrefix">
                    立即追加前缀
                  </NButton>
                </div>
              </div>
            </NTabPane>

            <!-- 工具 3：批量去重与清理 -->
            <NTabPane name="duplicates" tab="🧹 批量商品去重清理">
              <div class="flex flex-col gap-12px py-8px">
                <NAlert type="warning" :show-icon="true" class="rounded-6px text-12px">
                  自动比对相同货源、相同对接参数的重复商品，并根据保留策略安全清理多余商品。
                </NAlert>
                <div class="flex flex-wrap items-end gap-12px">
                  <div class="w-140px">
                    <label class="mb-4px block text-12px font-medium">检查范围：</label>
                    <NSelect
                      v-model:value="advDupScope"
                      :options="[
                        { label: '所有范围', value: 'all' },
                        { label: '指定分类ID', value: 'category' },
                        { label: '指定货源ID', value: 'docking' }
                      ]"
                    />
                  </div>
                  <div v-if="advDupScope !== 'all'" class="w-140px">
                    <label class="mb-4px block text-12px font-medium">范围ID：</label>
                    <NInput v-model:value="advDupScopeId" placeholder="输入对应ID" />
                  </div>
                  <div class="w-200px">
                    <label class="mb-4px block text-12px font-medium">保留策略：</label>
                    <NSelect
                      v-model:value="advDupStrategy"
                      :options="[
                        { label: '保留 CID 更大的商品', value: 'keep_larger' },
                        { label: '保留 CID 更小的商品', value: 'keep_smaller' },
                        { label: '直接删除全部重复项', value: 'delall' }
                      ]"
                    />
                  </div>
                  <NPopconfirm @positive-click="handleExecuteDeduplicate">
                    <template #default>确认执行商品去重清理吗？该操作不可撤销。</template>
                    <template #trigger>
                      <NButton type="error" :loading="actionLoading">
                        执行去重清理
                      </NButton>
                    </template>
                  </NPopconfirm>
                </div>
              </div>
            </NTabPane>
          </NTabs>
        </NCollapseItem>
      </NCollapse>
    </NCard>

    <!-- 克隆分类弹窗 -->
    <NModal
      v-model:show="copyModalVisible"
      preset="card"
      title="一键克隆上游分类"
      class="max-w-500px"
      :mask-closable="false"
    >
      <div class="flex flex-col gap-16px">
        <NAlert type="info" :show-icon="true" class="rounded-6px text-12px">
          系统将自动分析该货源返回的所有商品分类，并一键无损同步到本站分类库中。
        </NAlert>
        <div>
          <label class="mb-8px block text-13px font-medium">选择克隆模式：</label>
          <NRadioGroup v-model:value="copyMode">
            <NSpace vertical>
              <NRadio value="fenlei_only">
                仅克隆上游分类（推荐：仅同步建立分类结构，不导入课程）
              </NRadio>
              <NRadio value="fenlei_and_class">
                同时克隆分类并全量导入课程（自动按分类入库）
              </NRadio>
            </NSpace>
          </NRadioGroup>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-12px">
          <NButton @click="copyModalVisible = false">取消</NButton>
          <NButton type="primary" :loading="actionLoading" @click="handleConfirmCopy">
            确认开始克隆
          </NButton>
        </div>
      </template>
    </NModal>
  </div>
</template>

<style scoped></style>
