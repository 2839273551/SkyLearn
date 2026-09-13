<script setup lang="ts">
import { ref } from 'vue';
import { NAlert, NButton, NCard, NDivider, NEmpty, NInput, NSelect, NSpace, NTag } from 'naive-ui';

defineOptions({ name: 'Chati' });

const question = ref('');
const questionType = ref('all');
const searching = ref(false);

const typeOptions = [
  { label: '全部题型 (自动识别)', value: 'all' },
  { label: '单选 / 多选题', value: 'choice' },
  { label: '判断题', value: 'judge' },
  { label: '填空 / 简答题', value: 'text' }
];

interface SearchResult {
  question: string;
  answer: string;
  options?: string[];
  type: string;
  source: string;
}

const searchResults = ref<SearchResult[]>([]);

function handleSearch() {
  if (!question.value.trim()) {
    window.$message?.warning('请输入题目关键词或完整题干');
    return;
  }
  searching.value = true;
  // 模拟题库索引或调用查题接口
  setTimeout(() => {
    searching.value = false;
    searchResults.value = [
      {
        question: question.value.trim(),
        answer: '正确答案请以题目官方知识点解析为准',
        type: '单选题',
        source: '全网高校题库聚合引擎'
      }
    ];
  }, 400);
}

function copyAnswer(ans: string) {
  navigator.clipboard.writeText(ans);
  window.$message?.success('答案已复制到剪贴板');
}

function clearQuery() {
  question.value = '';
  searchResults.value = [];
}
</script>

<template>
  <div class="flex flex-col gap-16px p-10px sm:p-16px max-w-960px mx-auto">
    <!-- 顶部卡片 -->
    <NCard :bordered="false" class="rounded-12px shadow-sm border border-gray-100 dark:border-dark-600">
      <div class="flex items-center gap-12px">
        <div class="flex h-44px w-44px items-center justify-center rounded-10px bg-primary/10 text-primary text-22px">
          📖
        </div>
        <div>
          <h1 class="text-17px font-bold text-gray-800 dark:text-gray-100">在线题库快捷检索中心</h1>
          <p class="text-12px text-gray-400 mt-2px">聚合高校主流网课考点、课后习题、期末作业与在线考试答案</p>
        </div>
      </div>
    </NCard>

    <!-- 检索面板 -->
    <NCard title="题目搜索" :bordered="false" class="rounded-12px shadow-sm">
      <div class="flex flex-col gap-12px">
        <div class="flex flex-wrap items-center gap-10px">
          <NSelect v-model:value="questionType" :options="typeOptions" class="w-full sm:w-200px" />
          <NInput
            v-model:value="question"
            placeholder="请输入题目完整题干或核心关键词..."
            clearable
            class="flex-1"
            @keyup.enter="handleSearch"
          />
          <NButton type="primary" :loading="searching" class="w-full sm:w-auto px-20px font-bold" @click="handleSearch">
            🔍 立即搜题
          </NButton>
        </div>

        <div class="flex items-center justify-between text-12px text-gray-400">
          <span>支持将题干、选项或者带有下划线的挖空原题直接粘贴搜索</span>
          <NButton size="tiny" secondary @click="clearQuery">清空重置</NButton>
        </div>
      </div>
    </NCard>

    <!-- 结果展示 -->
    <NCard title="检索结果" :bordered="false" class="rounded-12px shadow-sm">
      <div v-if="!searchResults.length" class="py-24px">
        <NEmpty description="请输入题干内容开始检索" />
      </div>
      <div v-else class="flex flex-col gap-12px">
        <div
          v-for="(res, idx) in searchResults"
          :key="idx"
          class="rounded-10px bg-slate-50 p-14px border border-gray-200 dark:bg-dark-600 dark:border-dark-500 flex flex-col gap-10px"
        >
          <div class="flex items-start justify-between gap-10px">
            <span class="font-bold text-14px text-gray-800 dark:text-gray-100 leading-relaxed">{{ res.question }}</span>
            <NTag size="tiny" type="info">{{ res.type }}</NTag>
          </div>
          <div class="rounded-6px bg-white p-10px border dark:bg-dark-500 flex items-center justify-between">
            <div>
              <span class="text-12px text-gray-400">参考答案：</span>
              <strong class="text-14px font-bold text-emerald-600">{{ res.answer }}</strong>
            </div>
            <NButton size="tiny" type="primary" secondary @click="copyAnswer(res.answer)">复制答案</NButton>
          </div>
          <div class="text-11px text-gray-400 text-right">来源：{{ res.source }}</div>
        </div>
      </div>
    </NCard>
  </div>
</template>
