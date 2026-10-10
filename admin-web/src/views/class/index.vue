<script setup lang="ts">
import { computed, h, onMounted, reactive, ref } from 'vue';
import type { DataTableRowKey, DataTableColumns } from 'naive-ui';
import { useAppStore } from '@/store/modules/app';
import {
  NAlert,
  NButton,
  NCard,
  NDataTable,
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
  NSwitch,
  NTag,
  NTooltip
} from 'naive-ui';
import {
  batchUpdateClassPriceSort,
  batchUpdateClassStatus,
  batchUpdateClassVipPrice,
  deleteClass,
  quickSortClass,
  normalizeClassSort,
  fetchClassList,
  fetchClassOptions,
  saveClass
} from '@/service/api';

defineOptions({ name: 'Class' });

const appStore = useAppStore();

const loading = ref(false);
const submitting = ref(false);
const optionsLoading = ref(false);
const list = ref<Api.Class.Item[]>([]);
const total = ref(0);
const checkedRowKeys = ref<DataTableRowKey[]>([]);

// 暂存行内修改的价格与排序
const dirtyMap = reactive<Record<string, { price?: string; sort?: number }>>({});

const fenleiOptions = ref<Array<{ label: string; value: string }>>([]);
const huoyuanOptions = ref<Array<{ label: string; value: string }>>([]);

// 分类快捷切换与展开状态
const activeCategoryTab = ref('all');
const expandedCategories = ref<Set<string>>(new Set());

const query = reactive({
  keyword: '',
  status: '' as '' | '0' | '1'
});

const modalVisible = ref(false);
const modalTitle = ref('添加网课');

const formModel = reactive<Partial<Api.Class.Item>>({
  cid: '',
  name: '',
  sort: 0,
  price: '0.00',
  vipprice: '0.00',
  ckkf: '0',
  fenlei: '1',
  queryplat: '0',
  docking: '0',
  getnoun: '',
  noun: '',
  yunsuan: '*',
  status: 1,
  kcid: '0',
  content: ''
});

const dirtyCount = computed(() => Object.keys(dirtyMap).length);

// 分类分组接口定义
interface CategoryGroup {
  id: string;
  name: string;
  courses: Api.Class.Item[];
  totalCount: number;
  onlineCount: number;
  offlineCount: number;
}

// 分类分组构建
const categoryGroups = computed<CategoryGroup[]>(() => {
  const map: Record<string, CategoryGroup> = {};

  // 1. 初始化分类基础容器
  fenleiOptions.value.forEach(item => {
    map[item.value] = {
      id: item.value,
      name: item.label,
      courses: [],
      totalCount: 0,
      onlineCount: 0,
      offlineCount: 0
    };
  });

  // 2. 将加载的课程分别归入其分类
  list.value.forEach(course => {
    const flId = String(course.fenlei || '0');
    if (!map[flId]) {
      map[flId] = {
        id: flId,
        name: course.fenleiName || (flId === 'wck' ? '无查课' : '未分类'),
        courses: [],
        totalCount: 0,
        onlineCount: 0,
        offlineCount: 0
      };
    }
    map[flId].courses.push(course);
    map[flId].totalCount++;
    if (course.status === 1) {
      map[flId].onlineCount++;
    } else {
      map[flId].offlineCount++;
    }
  });

  // 3. 确保各分类内部严格按 sort ASC 进行序号排序
  Object.values(map).forEach(group => {
    group.courses.sort((a, b) => (Number(a.sort) || 0) - (Number(b.sort) || 0));
  });

  return Object.values(map);
});

// 当前展示的分类组列表（受分类快速切换标签与关键词影响）
const visibleCategoryGroups = computed(() => {
  let groups = categoryGroups.value;
  if (activeCategoryTab.value !== 'all') {
    groups = groups.filter(g => g.id === activeCategoryTab.value);
  }
  // 关键词过滤或全部模式下，只显示有课程的分类
  if (query.keyword) {
    groups = groups.filter(g => g.courses.length > 0);
  } else if (activeCategoryTab.value === 'all') {
    groups = groups.filter(g => g.courses.length > 0);
  }
  return groups;
});

// 折叠展开控制
function isExpanded(categoryId: string): boolean {
  return expandedCategories.value.has(categoryId);
}

function toggleCategoryExpand(categoryId: string) {
  if (expandedCategories.value.has(categoryId)) {
    expandedCategories.value.delete(categoryId);
  } else {
    expandedCategories.value.add(categoryId);
  }
}

const isAllExpanded = computed(() => {
  if (visibleCategoryGroups.value.length === 0) return false;
  return visibleCategoryGroups.value.every(g => expandedCategories.value.has(g.id));
});

function toggleExpandAll() {
  if (isAllExpanded.value) {
    expandedCategories.value.clear();
  } else {
    visibleCategoryGroups.value.forEach(g => expandedCategories.value.add(g.id));
  }
}

// 表格列定义 (包含原有全部快捷改价、改排序功能)
const columns: DataTableColumns<Api.Class.Item> = [
  { type: 'selection', fixed: 'left' },
  {
    title: '分类序号',
    key: 'sort',
    width: 145,
    fixed: 'left',
    render: row => {
      const currentSort = dirtyMap[row.cid]?.sort !== undefined ? dirtyMap[row.cid].sort! : row.sort;
      return h('div', { class: 'flex items-center gap-4px' }, [
        h(NInputNumber, {
          size: 'small',
          style: { width: '68px' },
          showButton: false,
          min: 0,
          value: currentSort,
          onUpdateValue: (val: number | null) => {
            if (!dirtyMap[row.cid]) dirtyMap[row.cid] = {};
            dirtyMap[row.cid].sort = val ?? 0;
          },
          onBlur: async () => {
            if (dirtyMap[row.cid]?.sort !== undefined && dirtyMap[row.cid].sort !== row.sort) {
              const res = await quickSortClass(row.cid, dirtyMap[row.cid].sort!);
              if (res !== null) {
                row.sort = dirtyMap[row.cid].sort!;
                delete dirtyMap[row.cid].sort;
                window.$message?.success(`【${row.name}】分类内序号更新为: ${row.sort}`);
                loadData();
              }
            }
          }
        }),
        h(
          NButton,
          {
            size: 'tiny',
            quaternary: true,
            title: '序号提前',
            onClick: async () => {
              const newSort = Math.max(0, (row.sort || 0) - 1);
              const res = await quickSortClass(row.cid, newSort);
              if (res !== null) {
                row.sort = newSort;
                if (dirtyMap[row.cid]) delete dirtyMap[row.cid].sort;
                window.$message?.success(`【${row.name}】序号提前为: ${newSort}`);
                loadData();
              }
            }
          },
          { default: () => '⬆' }
        ),
        h(
          NButton,
          {
            size: 'tiny',
            quaternary: true,
            title: '序号延后',
            onClick: async () => {
              const newSort = (row.sort || 0) + 1;
              const res = await quickSortClass(row.cid, newSort);
              if (res !== null) {
                row.sort = newSort;
                if (dirtyMap[row.cid]) delete dirtyMap[row.cid].sort;
                window.$message?.success(`【${row.name}】序号延后为: ${newSort}`);
                loadData();
              }
            }
          },
          { default: () => '⬇' }
        )
      ]);
    }
  },
  { title: 'ID', key: 'cid', width: 65 },
  {
    title: '课程名称',
    key: 'name',
    minWidth: 160,
    render: row =>
      h(
        NTooltip,
        {},
        {
          trigger: () => h('span', { class: 'font-500' }, row.name),
          default: () => row.content || '无特别说明'
        }
      )
  },
  {
    title: '定价(元)',
    key: 'price',
    width: 110,
    render: row =>
      h(NInput, {
        size: 'small',
        value: dirtyMap[row.cid]?.price !== undefined ? dirtyMap[row.cid].price : row.price,
        onUpdateValue: (val: string) => {
          if (!dirtyMap[row.cid]) dirtyMap[row.cid] = {};
          dirtyMap[row.cid].price = val;
        }
      })
  },
  {
    title: '全站密价',
    key: 'vipprice',
    width: 95,
    render: row => h('span', { class: 'text-gray-500' }, row.vipprice ? `¥${row.vipprice}` : '-')
  },
  {
    title: '查询接口',
    key: 'cxName',
    width: 120,
    render: row => h(NTag, { size: 'small', type: row.queryplat === '0' ? 'default' : 'success' }, { default: () => row.cxName })
  },
  {
    title: '交单接口',
    key: 'addName',
    width: 120,
    render: row => h(NTag, { size: 'small', type: row.docking === '0' ? 'default' : 'warning' }, { default: () => row.addName })
  },
  {
    title: '对接参数',
    key: 'noun',
    width: 110,
    ellipsis: { tooltip: true }
  },
  {
    title: '状态',
    key: 'status',
    width: 95,
    render: row =>
      h(
        NSwitch,
        {
          value: row.status === 1,
          onUpdateValue: async (val: boolean) => {
            const newStatus = val ? 1 : 0;
            const res = await batchUpdateClassStatus([row.cid], newStatus);
            if (res !== null) {
              row.status = newStatus;
              window.$message?.success(val ? '已上架' : '已下架');
            }
          }
        },
        { checked: () => '上架', unchecked: () => '下架' }
      )
  },
  {
    title: '操作',
    key: 'actions',
    width: 130,
    fixed: 'right',
    render: row =>
      h(NSpace, { size: 'small' }, () => [
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
            onPositiveClick: () => handleDelete([row.cid])
          },
          {
            default: () => `确定删除课程【${row.name}】吗？`,
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

async function loadOptions() {
  optionsLoading.value = true;
  const { data, error } = await fetchClassOptions();
  optionsLoading.value = false;
  if (!error && data) {
    fenleiOptions.value = data.fenleiList;
    huoyuanOptions.value = data.huoyuanList;
  }
}

async function loadData() {
  loading.value = true;
  const { data, error } = await fetchClassList({
    page: 1,
    pageSize: 500, // 加载全部课程进行分类分组管理
    keyword: query.keyword.trim() || undefined,
    status: query.status !== '' ? Number(query.status) : undefined
  });
  loading.value = false;

  if (!error && data) {
    list.value = data.records;
    total.value = data.total;
    // 重置已选和 dirty
    checkedRowKeys.value = [];
    for (const key of Object.keys(dirtyMap)) {
      delete dirtyMap[key];
    }
    // 首次加载或刷新默认展开所有含课程分类
    if (expandedCategories.value.size === 0) {
      categoryGroups.value.forEach(g => {
        if (g.courses.length > 0) {
          expandedCategories.value.add(g.id);
        }
      });
    }
  }
}

function handleSearch() {
  loadData();
}

function handleReset() {
  query.keyword = '';
  query.status = '';
  activeCategoryTab.value = 'all';
  loadData();
}

function openAddModal() {
  modalTitle.value = '添加网课';
  // 若当前定位在某分类，新增时默认预填该分类
  const defaultFenlei = activeCategoryTab.value !== 'all' ? activeCategoryTab.value : (fenleiOptions.value.length ? fenleiOptions.value[0].value : '1');
  const targetGroup = categoryGroups.value.find(g => g.id === defaultFenlei);
  const nextSort = targetGroup ? targetGroup.courses.length : 0;

  Object.assign(formModel, {
    cid: '',
    name: '',
    sort: nextSort,
    price: '0.00',
    vipprice: '0.00',
    ckkf: '0',
    fenlei: defaultFenlei,
    queryplat: '0',
    docking: '0',
    getnoun: '',
    noun: '',
    yunsuan: '*',
    status: 1,
    kcid: '0',
    content: ''
  });
  modalVisible.value = true;
}

function openEditModal(row: Api.Class.Item) {
  modalTitle.value = '编辑网课';
  Object.assign(formModel, {
    cid: row.cid,
    name: row.name,
    sort: row.sort,
    price: row.price,
    vipprice: row.vipprice,
    ckkf: row.ckkf,
    fenlei: row.fenlei,
    queryplat: row.queryplat,
    docking: row.docking,
    getnoun: row.getnoun,
    noun: row.noun,
    yunsuan: row.yunsuan || '*',
    status: row.status,
    kcid: row.kcid || '0',
    content: row.content
  });
  modalVisible.value = true;
}

async function handleSubmit() {
  if (!formModel.name?.trim()) {
    window.$message?.warning('请输入网课名称');
    return;
  }
  if (!formModel.price) {
    window.$message?.warning('请输入定价');
    return;
  }

  submitting.value = true;
  const res = await saveClass({
    cid: formModel.cid ? formModel.cid : undefined,
    name: formModel.name.trim(),
    sort: Number(formModel.sort) || 0,
    price: String(formModel.price),
    vipprice: String(formModel.vipprice || '0'),
    ckkf: String(formModel.ckkf || '0'),
    fenlei: formModel.fenlei,
    queryplat: formModel.queryplat,
    docking: formModel.docking,
    getnoun: formModel.getnoun,
    noun: formModel.noun,
    yunsuan: formModel.yunsuan,
    status: formModel.status,
    kcid: formModel.kcid,
    content: formModel.content
  });
  submitting.value = false;

  if (res !== null) {
    window.$message?.success(formModel.cid ? '修改网课成功' : '添加网课成功');
    modalVisible.value = false;
    loadData();
  }
}

async function handleDelete(cids: (string | number)[]) {
  if (!cids.length) {
    window.$message?.warning('请选择要删除的网课');
    return;
  }
  const res = await deleteClass(cids);
  if (res !== null) {
    window.$message?.success('删除成功');
    loadData();
  }
}

const batchVipPriceModal = ref(false);
const batchVipPriceValue = ref('');
const batchVipPriceLoading = ref(false);

function openBatchVipPriceModal() {
  if (!checkedRowKeys.value.length) {
    window.$message?.warning('请先勾选需要操作的网课');
    return;
  }
  batchVipPriceValue.value = '';
  batchVipPriceModal.value = true;
}

async function handleBatchVipPriceSubmit() {
  const val = batchVipPriceValue.value.trim();
  if (val === '' || isNaN(Number(val)) || Number(val) < 0) {
    window.$message?.warning('请输入合法的密价数值（如：0.20 或 0.35）');
    return;
  }

  batchVipPriceLoading.value = true;
  const res = await batchUpdateClassVipPrice(checkedRowKeys.value as string[], val);
  batchVipPriceLoading.value = false;

  if (res !== null) {
    window.$message?.success(`已成功将勾选的 ${checkedRowKeys.value.length} 门网课密价统一修改为 ¥${val}`);
    batchVipPriceModal.value = false;
    checkedRowKeys.value = [];
    loadData();
  }
}

async function handleBatchStatus(status: number) {
  if (!checkedRowKeys.value.length) {
    window.$message?.warning('请先勾选需要操作的网课');
    return;
  }
  const res = await batchUpdateClassStatus(checkedRowKeys.value as string[], status);
  if (res !== null) {
    window.$message?.success(status === 1 ? '批量上架成功' : '批量下架成功');
    loadData();
  }
}

async function handleSavePriceSort() {
  const updates: Api.Class.PriceSortUpdate[] = [];
  for (const [cidStr, change] of Object.entries(dirtyMap)) {
    updates.push({
      cid: Number(cidStr),
      price: change.price,
      sort: change.sort
    });
  }

  if (!updates.length) {
    window.$message?.info('暂无修改项');
    return;
  }

  const res = await batchUpdateClassPriceSort(updates);
  if (res !== null) {
    window.$message?.success('改价与排序保存成功');
    loadData();
  }
}

// 分类内序号一键规整为从 0 开始
async function handleNormalizeSort(fenleiId?: string) {
  const targetGroup = fenleiId ? categoryGroups.value.find(g => g.id === fenleiId) : null;
  const targetName = targetGroup ? targetGroup.name : '全部网课';
  const res = await normalizeClassSort(fenleiId);
  if (res !== null) {
    window.$message?.success(`【${targetName}】序号已成功规范化，所有网课已从 0 开始连续编号！`);
    loadData();
  }
}

onMounted(async () => {
  await loadOptions();
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-12px sm:p-16px">
    <NCard title="网课设置" :bordered="false" class="rounded-8px shadow-sm">
      <!-- 1. 顶部操作与检索栏 -->
      <div class="mb-14px flex flex-wrap items-center gap-12px">
        <NInput
          v-model:value="query.keyword"
          placeholder="搜索课程名 / 对接参数 / ID"
          clearable
          class="w-220px"
          @keyup.enter="handleSearch"
        />
        <NSelect
          v-model:value="query.status"
          :options="[
            { label: '全部状态', value: '' },
            { label: '上架中', value: '1' },
            { label: '已下架', value: '0' }
          ]"
          placeholder="状态筛选"
          clearable
          class="w-130px"
        />
        <NButton type="primary" @click="handleSearch">
          <template #icon>
            <icon-ic-round-search class="text-18px" />
          </template>
          查询
        </NButton>
        <NButton @click="handleReset">重置</NButton>

        <div class="ml-auto flex items-center gap-8px">
          <NButton type="primary" @click="openAddModal">
            <template #icon>
              <icon-ic-round-plus class="text-18px" />
            </template>
            添加网课
          </NButton>
          <NButton :loading="loading" @click="loadData">
            <template #icon>
              <icon-ic-round-refresh class="text-18px" />
            </template>
            刷新
          </NButton>
        </div>
      </div>

      <!-- 2. 分类快速切换胶囊栏 (带数量徽标，点击一键聚焦/筛选) -->
      <div class="mb-14px flex flex-wrap items-center gap-8px border-b border-gray-100 dark:border-dark-600 pb-12px">
        <button
          type="button"
          class="px-14px py-6px rounded-6px text-13px font-medium transition-all cursor-pointer border select-none flex items-center gap-6px"
          :class="
            activeCategoryTab === 'all'
              ? 'bg-blue-600 text-white border-blue-600 shadow-xs font-bold'
              : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-200 dark:bg-dark-600 dark:border-dark-500 dark:text-gray-200'
          "
          @click="activeCategoryTab = 'all'"
        >
          <span>📁 全部分类</span>
          <span
            class="px-6px py-1px rounded-full text-11px"
            :class="activeCategoryTab === 'all' ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-dark-500 text-gray-500'"
          >
            {{ total }}
          </span>
        </button>

        <button
          v-for="group in categoryGroups"
          :key="group.id"
          type="button"
          class="px-14px py-6px rounded-6px text-13px font-medium transition-all cursor-pointer border select-none flex items-center gap-6px"
          :class="
            activeCategoryTab === group.id
              ? 'bg-blue-600 text-white border-blue-600 shadow-xs font-bold'
              : 'bg-white hover:bg-gray-50 text-gray-700 border-gray-200 dark:bg-dark-600 dark:border-dark-500 dark:text-gray-200'
          "
          @click="activeCategoryTab = group.id"
        >
          <span>{{ group.name }}</span>
          <span
            class="px-6px py-1px rounded-full text-11px"
            :class="activeCategoryTab === group.id ? 'bg-white/20 text-white' : 'bg-gray-100 dark:bg-dark-500 text-gray-500'"
          >
            {{ group.totalCount }}
          </span>
        </button>
      </div>

      <!-- 3. 批量操作工具条 (100% 保持原快捷改价、批量上架下架、批量密价操作不变) -->
      <div class="mb-14px flex flex-wrap items-center gap-8px rounded-6px bg-gray-50 p-10px dark:bg-dark-600">
        <span class="text-13px text-gray-600 dark:text-gray-300">
          已勾选 <strong class="text-primary">{{ checkedRowKeys.length }}</strong> 项
        </span>
        <NButton size="small" type="primary" secondary :disabled="!checkedRowKeys.length" @click="openBatchVipPriceModal">
          🏷️ 批量修改密价
        </NButton>
        <NButton size="small" type="success" :disabled="!checkedRowKeys.length" @click="handleBatchStatus(1)">
          批量上架
        </NButton>
        <NButton size="small" type="warning" :disabled="!checkedRowKeys.length" @click="handleBatchStatus(0)">
          批量下架
        </NButton>
        <NPopconfirm :disabled="!checkedRowKeys.length" @positive-click="handleDelete(checkedRowKeys as string[])">
          <template #default>确定批量删除勾选的 {{ checkedRowKeys.length }} 门课程吗？</template>
          <template #trigger>
            <NButton size="small" type="error" :disabled="!checkedRowKeys.length">
              批量删除
            </NButton>
          </template>
        </NPopconfirm>

        <div class="ml-auto flex items-center gap-10px">
          <NButton size="small" quaternary @click="toggleExpandAll">
            {{ isAllExpanded ? '全部收起' : '全部展开' }}
          </NButton>
          <span v-if="dirtyCount > 0" class="text-12px text-amber-500 font-medium">
            有 {{ dirtyCount }} 项改价/排序未保存
          </span>
          <NButton size="small" type="info" :disabled="dirtyCount === 0" @click="handleSavePriceSort">
            应用修改 (改价/排序)
          </NButton>
        </div>
      </div>

      <!-- 4. 大列表：分类分组卡片流 (先显示分类，展开显示里面的课程，各分类内序号从0开始) -->
      <NSpin :show="loading">
        <div class="flex flex-col gap-14px">
          <div
            v-for="group in visibleCategoryGroups"
            :key="group.id"
            class="rounded-8px border border-gray-200 dark:border-dark-600 bg-white dark:bg-dark-700 shadow-xs overflow-hidden transition-shadow"
          >
            <!-- 分类一级标题栏 -->
            <div
              class="flex flex-wrap items-center justify-between gap-10px px-16px py-12px bg-gray-50/90 dark:bg-dark-600/60 border-b border-gray-200 dark:border-dark-600 cursor-pointer select-none hover:bg-gray-100/80 transition-colors"
              @click="toggleCategoryExpand(group.id)"
            >
              <div class="flex items-center gap-10px">
                <span class="text-13px text-gray-500 dark:text-gray-400 font-mono w-14px text-center">
                  {{ isExpanded(group.id) ? '▼' : '▶' }}
                </span>
                <span class="text-15px font-bold text-gray-900 dark:text-gray-100 flex items-center gap-6px">
                  <span>📁</span>
                  <span>{{ group.name }}</span>
                </span>
                <NTag size="small" type="primary" round class="font-mono">
                  共 {{ group.totalCount }} 门
                </NTag>
                <span class="text-12px text-gray-500 dark:text-gray-400">
                  ({{ group.onlineCount }} 门上架 / {{ group.offlineCount }} 门下架)
                </span>
              </div>

              <!-- 分类专属右侧操作 -->
              <div class="flex items-center gap-8px" @click.stop>
                <NPopconfirm @positive-click="handleNormalizeSort(group.id)">
                  <template #trigger>
                    <NButton size="tiny" secondary type="warning" title="将该分类下的课程序号按当前排列从0开始自动规范化为0,1,2...">
                      🔢 规范序号(0起)
                    </NButton>
                  </template>
                  确定将【{{ group.name }}】内部的所有网课序号按顺序规整为从 0 开始递增吗？
                </NPopconfirm>

                <NButton size="tiny" quaternary @click="toggleCategoryExpand(group.id)">
                  {{ isExpanded(group.id) ? '收起' : '展开' }}
                </NButton>
              </div>
            </div>

            <!-- 分类二级展开课程表格 -->
            <div v-show="isExpanded(group.id)" class="p-10px">
              <NDataTable
                v-if="group.courses.length > 0"
                v-model:checked-row-keys="checkedRowKeys"
                :columns="columns"
                :data="group.courses"
                :row-key="(row: Api.Class.Item) => row.cid"
                :pagination="false"
                striped
                :scroll-x="1400"
                size="small"
              />
              <div v-else class="py-24px text-center text-gray-400 text-13px">
                该分类下暂无网课项目
              </div>
            </div>
          </div>

          <!-- 空结果 -->
          <div v-if="visibleCategoryGroups.length === 0" class="py-48px text-center bg-white dark:bg-dark-700 rounded-8px">
            <NEmpty description="未找到符合条件的网课项目" />
          </div>
        </div>
      </NSpin>
    </NCard>

    <!-- 新增 / 编辑弹窗 (完全保持原有业务配置) -->
    <NModal
      v-model:show="modalVisible"
      preset="card"
      :title="modalTitle"
      class="max-w-720px"
      :mask-closable="false"
    >
      <NForm :model="formModel" label-placement="left" label-width="110">
        <NGrid cols="1 m:2" responsive="screen" :x-gap="16">
          <NGi span="1 m:2">
            <NFormItem label="课程名称" required>
              <NInput v-model:value="formModel.name" placeholder="请输入商品/课程完整标题" />
            </NFormItem>
          </NGi>
          <NGi>
            <NFormItem label="所在分类" required>
              <NSelect v-model:value="formModel.fenlei" :options="fenleiOptions" placeholder="选择分类" />
            </NFormItem>
          </NGi>
          <NGi>
            <NFormItem label="分类内序号">
              <NInputNumber v-model:value="formModel.sort" :min="0" :max="9999" placeholder="从0开始" class="w-full" />
            </NFormItem>
          </NGi>
          <NGi>
            <NFormItem label="销售定价(元)" required>
              <NInput v-model:value="formModel.price" placeholder="如：1.20" />
            </NFormItem>
          </NGi>
          <NGi>
            <NFormItem label="全站密价(元)">
              <NInput v-model:value="formModel.vipprice" placeholder="VIP或底价参考" />
            </NFormItem>
          </NGi>
          <NGi>
            <NFormItem label="查询平台">
              <NSelect v-model:value="formModel.queryplat" :options="huoyuanOptions" placeholder="选择查课上游" />
            </NFormItem>
          </NGi>
          <NGi>
            <NFormItem label="交单平台">
              <NSelect v-model:value="formModel.docking" :options="huoyuanOptions" placeholder="选择交单上游" />
            </NFormItem>
          </NGi>
          <NGi>
            <NFormItem label="查询参数">
              <NInput v-model:value="formModel.getnoun" placeholder="如上游课程ID" />
            </NFormItem>
          </NGi>
          <NGi>
            <NFormItem label="交单参数">
              <NInput v-model:value="formModel.noun" placeholder="上游交单标识" />
            </NFormItem>
          </NGi>
          <NGi>
            <NFormItem label="费率运算">
              <NRadioGroup v-model:value="formModel.yunsuan">
                <NSpace>
                  <NRadio value="*">乘法 (原价 × 费率)</NRadio>
                  <NRadio value="+">加法 (原价 + 加价)</NRadio>
                </NSpace>
              </NRadioGroup>
            </NFormItem>
          </NGi>
          <NGi>
            <NFormItem label="状态">
              <NSwitch v-model:value="formModel.status" :checked-value="1" :unchecked-value="0">
                <template #checked>上架中</template>
                <template #unchecked>已下架</template>
              </NSwitch>
            </NFormItem>
          </NGi>
          <NGi span="1 m:2">
            <NFormItem label="说明与规则">
              <NInput
                v-model:value="formModel.content"
                type="textarea"
                :autosize="{ minRows: 3, maxRows: 6 }"
                placeholder="填写前台展示给用户的注意事项、查课交单规则等"
              />
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

    <!-- 批量修改全站密价弹窗 -->
    <NModal
      v-model:show="batchVipPriceModal"
      preset="card"
      title="批量修改全站密价"
      class="max-w-460px"
      :mask-closable="false"
    >
      <div class="flex flex-col gap-16px">
        <NAlert type="info" :show-icon="true" class="rounded-6px text-12px">
          您当前已选择 <strong class="text-primary">{{ checkedRowKeys.length }}</strong> 门网课。在此输入统一的密价值，确认后所选网课的【全站密价】将全部批量变更为该数值。
        </NAlert>

        <div>
          <label class="mb-6px block text-13px font-medium text-gray-700 dark:text-gray-300">
            统一全站密价(元)：
          </label>
          <NInput
            v-model:value="batchVipPriceValue"
            placeholder="例如：0.20 或 0.35"
            size="medium"
            clearable
            @keydown.enter="handleBatchVipPriceSubmit"
          >
            <template #prefix>¥</template>
          </NInput>
        </div>

        <div>
          <div class="mb-6px text-12px text-gray-400">快捷填充常用底价：</div>
          <div class="flex flex-wrap gap-8px">
            <NTag
              v-for="p in ['0.10', '0.20', '0.30', '0.35', '0.40', '0.50', '1.00']"
              :key="p"
              size="small"
              class="cursor-pointer hover:border-primary hover:text-primary transition-all"
              checkable
              :checked="batchVipPriceValue === p"
              @update:checked="() => { batchVipPriceValue = p; }"
            >
              ¥{{ p }}
            </NTag>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end gap-12px">
          <NButton @click="batchVipPriceModal = false">取消</NButton>
          <NButton
            type="primary"
            :loading="batchVipPriceLoading"
            :disabled="!batchVipPriceValue.trim()"
            @click="handleBatchVipPriceSubmit"
          >
            确认批量修改 ({{ checkedRowKeys.length }} 门)
          </NButton>
        </div>
      </template>
    </NModal>
  </div>
</template>
