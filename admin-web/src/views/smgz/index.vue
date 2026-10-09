<script setup lang="ts">
import { NAlert, NCard, NCollapse, NCollapseItem, NDivider, NTag } from 'naive-ui';

defineOptions({ name: 'Smgz' });
</script>

<template>
  <div class="flex flex-col gap-16px p-10px sm:p-16px max-w-960px mx-auto">
    <NCard :bordered="false" class="rounded-12px shadow-sm border border-gray-100 dark:border-dark-600">
      <div class="flex items-center gap-12px">
        <div class="flex h-44px w-44px items-center justify-center rounded-10px bg-primary/10 text-primary text-22px">
          ⚖️
        </div>
        <div>
          <h1 class="text-17px font-bold text-gray-800 dark:text-gray-100">商户服务规则与接单规范协议</h1>
          <p class="text-12px text-gray-400 mt-2px">为保障全体用户与各级代理的合法权益，请在开展查课、接单、串货业务前仔细阅读本规则</p>
        </div>
      </div>
    </NCard>

    <NCard :bordered="false" class="rounded-12px shadow-sm">
      <NCollapse :default-expanded-names="['1', '2', '3']" class="flex flex-col gap-10px">
        <NCollapseItem title="一、 查课与下单合规条款" name="1">
          <div class="text-13px text-gray-600 dark:text-gray-300 leading-relaxed flex flex-col gap-8px">
            <p>1. 用户在提交查课或下单前，必须确保输入的学校名、账号及密码真实无误。</p>
            <p>2. 如因学生本人修改密码、人脸识别验证或学校教务系统风控导致学习中断，请在订单汇总中申请重刷或提交售后工单协助处理。</p>
            <p>3. 严禁使用本平台接口从事任何违反国家法律法规、侵犯他人隐私或非法爬取教务数据的行为。</p>
          </div>
        </NCollapseItem>

        <NCollapseItem title="二、 掉进度与挂科自动补刷保障" name="2">
          <div class="text-13px text-gray-600 dark:text-gray-300 leading-relaxed flex flex-col gap-8px">
            <p>1. 平台支持全生命周期的免费补刷保障。凡在开课期内因上游队列波动导致进度未达标的课程，均可在【订单汇总】中一键发起“申请补刷”。</p>
            <p>2. 补单请求将由全自动调度引擎在 5 分钟内推向上游队列重新开跑，无需反复催单。</p>
          </div>
        </NCollapseItem>

        <NCollapseItem title="三、 代理分销与成本结算说明" name="3">
          <div class="text-13px text-gray-600 dark:text-gray-300 leading-relaxed flex flex-col gap-8px">
            <p>1. 各级代理成本单价严格根据所开通的等级费率系数计算（单价 = 基础价 × 成本系数）。</p>
            <p>2. 下级代理产生的消费差价将实时折算为推广佣金返还至上级商户账户，可直接用于继续消费或批量交单。</p>
          </div>
        </NCollapseItem>

        <NCollapseItem title="四、 免责与不可抗力声明" name="4">
          <div class="text-13px text-gray-600 dark:text-gray-300 leading-relaxed flex flex-col gap-8px">
            <p>若因高校教务系统重大改版、官方接口关闭、不可抗力网络故障或第三方货源下架导致的暂时服务不可用，平台将协助商户向货源申请退款并全额原路返还消费积分。</p>
          </div>
        </NCollapseItem>
      </NCollapse>
    </NCard>
  </div>
</template>
