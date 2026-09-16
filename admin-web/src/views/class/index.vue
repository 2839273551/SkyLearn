<script setup lang="ts">
import { computed, h, onMounted, reactive, ref } from 'vue';
import type { DataTableRowKey, DataTableColumns } from 'naive-ui';
import { NAlert, NButton, NInput, NInputNumber, NPopconfirm, NSpace, NSwitch, NTag, NTooltip } from 'naive-ui';
import {
  batchUpdateClassPriceSort,
  batchUpdateClassStatus,
  batchUpdateClassVipPrice,
  deleteClass,
  quickSortClass,
  fetchClassList,
  fetchClassOptions,
  saveClass
} from '@/service/api';

defineOptions({ name: 'Class' });

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

const query = reactive({
  page: 1,
  pageSize: 20,
  keyword: '',
  fenlei: '',
  status: '' as '' | '0' | '1'
});

const modalVisible = ref(false);
const modalTitle = ref('添加网课');

const formModel = reactive<Partial<Api.Class.Item>>({
  cid: '',
  name: '',
  sort: 10,
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

const columns: DataTableColumns<Api.Class.Item> = [
  { type: 'selection', fixed: 'left' },
  { title: 'ID', key: 'cid', width: 70, fixed: 'left' },
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
    title: '快捷排序',
    key: 'sort',
    width: 155,
    render: row => {
      const currentSort = dirtyMap[row.cid]?.sort !== undefined ? dirtyMap[row.cid].sort! : row.sort;
      return h('div', { class: 'flex items-center gap-4px' }, [
        h(NInputNumber, {
          size: 'small',
          style: { width: '80px' },
          showButton: false,
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
                window.$message?.success(`【${row.name}】排序已更新为: ${row.sort}`);
              }
            }
          }
        }),
        h(
          NButton,
          {
            size: 'tiny',
            quaternary: true,
            title: '顺序提前',
            onClick: async () => {
              const newSort = Math.max(0, (row.sort || 0) - 1);
              const res = await quickSortClass(row.cid, newSort);
              if (res !== null) {
                row.sort = newSort;
                if (dirtyMap[row.cid]) delete dirtyMap[row.cid].sort;
                window.$message?.success(`【${row.name}】排序提前为: ${newSort}`);
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
            title: '顺序延后',
            onClick: async () => {
              const newSort = (row.sort || 0) + 1;
              const res = await quickSortClass(row.cid, newSort);
              if (res !== null) {
                row.sort = newSort;
                if (dirtyMap[row.cid]) delete dirtyMap[row.cid].sort;
                window.$message?.success(`【${row.name}】排序延后为: ${newSort}`);
              }
            }
          },
          { default: () => '⬇' }
        )
      ]);
    }
  },
  {
    title: '定价(元)',
    key: 'price',
    width: 120,
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
    width: 100,
    render: row => h('span', { class: 'text-gray-500' }, row.vipprice ? `¥${row.vipprice}` : '-')
  },
  {
    title: '分类',
    key: 'fenleiName',
    width: 110,
    render: row => h(NTag, { size: 'small', type: 'info', round: true }, { default: () => row.fenleiName || '未分类' })
  },
  {
    title: '查询接口',
    key: 'cxName',
    width: 130,
    render: row => h(NTag, { size: 'small', type: row.queryplat === '0' ? 'default' : 'success' }, { default: () => row.cxName })
  },
  {
    title: '交单接口',
    key: 'addName',
    width: 130,
    render: row => h(NTag, { size: 'small', type: row.docking === '0' ? 'default' : 'warning' }, { default: () => row.addName })
  },
  {
    title: '对接参数',
    key: 'noun',
    width: 120,
    ellipsis: { tooltip: true }
  },
  {
    title: '状态',
    key: 'status',
    width: 100,
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
    width: 140,
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
    page: query.page,
    pageSize: query.pageSize,
    keyword: query.keyword.trim() || undefined,
    fenlei: query.fenlei || undefined,
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
  }
}

function handleSearch() {
  query.page = 1;
  loadData();
}

function handleReset() {
  query.page = 1;
  query.keyword = '';
  query.fenlei = '';
  query.status = '';
  loadData();
}

function openAddModal() {
  modalTitle.value = '添加网课';
  Object.assign(formModel, {
    cid: '',
    name: '',
    sort: 10,
    price: '0.00',
    vipprice: '0.00',
    ckkf: '0',
    fenlei: fenleiOptions.value.length ? fenleiOptions.value[0].value : '1',
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
    sort: Number(formModel.sort) || 10,
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

onMounted(async () => {
  await loadOptions();
  loadData();
});
</script>

<template>
  <div class="flex flex-col gap-16px p-16px">
    <NCard title="网课设置" :bordered="false" class="rounded-8px shadow-sm">
      <!-- 过滤表单 -->
      <div class="mb-16px flex flex-wrap items-center gap-12px">
        <NInput
          v-model:value="query.keyword"
          placeholder="课程名 / 对接参数 / ID"
          clearable
          class="w-220px"
          @keyup.enter="handleSearch"
        />
        <NSelect
          v-model:value="query.fenlei"
          :options="[{ label: '全部分类', value: '' }, ...fenleiOptions]"
          placeholder="分类筛选"
          clearable
          class="w-160px"
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

      <!-- 批量操作栏 -->
      <div class="mb-12px flex flex-wrap items-center gap-8px rounded-6px bg-gray-50 p-10px dark:bg-dark-600">
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

        <div class="ml-auto flex items-center gap-8px">
          <span v-if="dirtyCount > 0" class="text-12px text-warning">
            有 {{ dirtyCount }} 项改价/排序未保存
          </span>
          <NButton size="small" type="info" :disabled="dirtyCount === 0" @click="handleSavePriceSort">
            应用修改 (改价/排序)
          </NButton>
        </div>
      </div>

      <!-- 数据表格 -->
      <NDataTable
        v-model:checked-row-keys="checkedRowKeys"
        :loading="loading"
        :columns="columns"
        :data="list"
        :row-key="(row: Api.Class.Item) => row.cid"
        :pagination="false"
        striped
        :scroll-x="1400"
      />

      <!-- 分页栏 -->
      <div class="mt-16px flex justify-end">
        <NPagination
          v-model:page="query.page"
          v-model:page-size="query.pageSize"
          :item-count="total"
          :page-sizes="[20, 50, 100, 200]"
          show-size-picker
          show-quick-jumper
          @update:page="loadData"
          @update:page-size="handleSearch"
        />
      </div>
    </NCard>

    <!-- 新增 / 编辑弹窗 -->
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
            <NFormItem label="排序权重">
              <NInputNumber v-model:value="formModel.sort" :min="0" :max="9999" class="w-full" />
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
