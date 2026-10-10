import{E as e,H as t,O as n,S as ee,T as r,W as te,ft as i,tt as a,wt as o,y as s}from"./vendor-icons-B7yid5VR.js";import{R as ne,et as re,l as ie,lt as ae,nt as oe,s as c,z as se}from"./vendor-naive-CA3wMCJz.js";import{G as l,p as u}from"./index-CMysEq8P.js";var d={class:`flex flex-col gap-16px p-16px`},f={class:`flex flex-col gap-10px`},p={class:`flex flex-wrap items-center gap-24px text-14px`},m={class:`text-primary`},h={class:`flex items-center gap-8px`},g={class:`font-mono font-bold tracking-wider`},_={class:`flex items-center gap-8px`},v={class:`font-mono text-12px`},y={class:`flex flex-col gap-14px`},b={class:`flex items-center justify-between rounded-6px bg-gray-50 p-10px dark:bg-dark-600`},x={class:`font-mono text-13px`},S={class:`m-0 font-mono text-12px text-gray-700 dark:text-gray-300`},C={class:`flex flex-col gap-14px`},w={class:`flex items-center justify-between rounded-6px bg-gray-50 p-10px dark:bg-dark-600`},T={class:`font-mono text-13px`},E={class:`flex flex-col gap-14px`},D={class:`flex items-center justify-between rounded-6px bg-gray-50 p-10px dark:bg-dark-600`},O={class:`font-mono text-13px`},k={class:`flex flex-col gap-14px`},ce={class:`flex items-center justify-between rounded-6px bg-gray-50 p-10px dark:bg-dark-600`},A={class:`font-mono text-13px`},j={class:`flex flex-col gap-14px`},M={class:`flex items-center justify-between rounded-6px bg-gray-50 p-10px dark:bg-dark-600`},N={class:`font-mono text-13px`},P={class:`m-0 font-mono text-12px`},F={class:`flex flex-col gap-14px`},I={class:`flex items-center justify-between rounded-6px bg-gray-50 p-10px dark:bg-dark-600`},L={class:`font-mono text-13px`},R={class:`font-mono text-12px rounded-6px bg-gray-50 p-10px dark:bg-dark-600`},z={class:`flex flex-col gap-14px`},B={class:`flex items-center justify-between rounded-6px bg-gray-50 p-10px dark:bg-dark-600`},V={class:`font-mono text-13px`},H={class:`m-0 font-mono text-12px`},U={class:`mt-20px rounded-8px border border-primary/20 bg-primary/4 p-16px`},W={class:`mt-10px grid grid-cols-1 s:2 gap-12px text-12px text-gray-600 dark:text-gray-300 leading-relaxed`},G={class:`m-0`},K=n({name:`docking`,__name:`index`,setup(n){let K=i(!1),q=i(!0),J=i({uid:``,key:``,apiBaseUrl:``,apiBalanceUrl:``,apiGoodsUrl:``,apiQueryUrl:``,apiAddUrl:``,apiAutoAddUrl:``,apiStatusUrl:``,apiBudanUrl:``});async function Y(){K.value=!0;let{data:e,error:t}=await l();K.value=!1,!t&&e&&(J.value=e)}function X(e,t=`内容`){if(!e){window.$message?.warning(`暂无${t}可复制`);return}navigator.clipboard.writeText(e),window.$message?.success(`${t}已成功复制到剪贴板`)}return t(()=>{Y()}),(t,n)=>{let i=u,l=oe,K=ae,Y=ne,Z=se,Q=re,$=ie,le=c;return te(),ee(`div`,d,[e(Q,{title:`平台串货与 API 开放对接中心`,bordered:!1,class:`rounded-8px shadow-sm`},{default:a(()=>[e(K,{type:`info`,title:`我的对接凭据与网关协议`,class:`mb-16px`},{default:a(()=>[s(`div`,f,[s(`div`,p,[s(`span`,null,[n[10]||=r(`对接商户 UID：`,-1),s(`strong`,m,o(J.value.uid),1)]),s(`div`,h,[n[12]||=s(`span`,null,`对接密钥 KEY：`,-1),s(`code`,g,o(q.value?`••••••••••••••••`:J.value.key||`未生成`),1),e(l,{size:`tiny`,quaternary:``,circle:``,onClick:n[0]||=e=>q.value=!q.value},{icon:a(()=>[e(i,{icon:q.value?`ph:eye`:`ph:eye-slash`},null,8,[`icon`])]),_:1}),e(l,{size:`tiny`,type:`primary`,secondary:``,onClick:n[1]||=e=>X(J.value.key,`对接 KEY`)},{default:a(()=>[...n[11]||=[r(` 复制 KEY `,-1)]]),_:1})]),s(`div`,_,[n[14]||=s(`span`,null,`接口基础网关：`,-1),s(`code`,v,o(J.value.apiBaseUrl),1),e(l,{size:`tiny`,quaternary:``,onClick:n[2]||=e=>X(J.value.apiBaseUrl,`网关地址`)},{default:a(()=>[...n[13]||=[r(`复制`,-1)]]),_:1})])]),n[15]||=s(`div`,{class:`text-12px text-gray-500 leading-normal`},[r(` 协议支持：所有接口全面支持 `),s(`code`,null,`POST (Content-Type: application/json)`),r(` 及 `),s(`code`,null,`POST (x-www-form-urlencoded)`),r(` 双协议自动适配；支持小储系统、卡易信、彩虹发卡网及自建脚本直接串联对接。 `)],-1)])]),_:1}),e(le,{type:`line`,animated:``},{default:a(()=>[e($,{name:`balance`,tab:`1. 查询余额 (POST)`},{default:a(()=>[s(`div`,y,[s(`div`,b,[s(`span`,x,[n[16]||=r(`接口地址：`,-1),s(`strong`,null,o(J.value.apiBalanceUrl),1)]),e(l,{size:`small`,ghost:``,type:`primary`,onClick:n[3]||=e=>X(J.value.apiBalanceUrl,`查询余额接口`)},{default:a(()=>[...n[17]||=[r(`复制接口`,-1)]]),_:1})]),n[19]||=s(`p`,{class:`m-0 text-13px text-gray-500`},[r(` 请求方式：`),s(`code`,null,`POST`),r(` | 参数格式：`),s(`code`,null,`JSON`),r(` 或 `),s(`code`,null,`表单 POST`)],-1),e(Q,{embedded:``,size:`small`,title:`请求参数规范`},{default:a(()=>[e(Z,{"label-placement":`left`,column:1,bordered:``,size:`small`},{default:a(()=>[e(Y,{label:`uid (必填)`},{default:a(()=>[r(`平台分配的商户 UID（如：`+o(J.value.uid)+`）`,1)]),_:1}),e(Y,{label:`key (必填)`},{default:a(()=>[...n[18]||=[r(`您的商户对接密钥 KEY`,-1)]]),_:1})]),_:1})]),_:1}),e(Q,{embedded:``,size:`small`,title:`JSON 请求示例与成功回执`},{default:a(()=>[s(`pre`,S,`// 请求 JSON
{
  "uid": `+o(J.value.uid)+`,
  "key": "`+o(J.value.key||`YOUR_API_KEY`)+`"
}

// 成功返回示例
{
  "code": 1,
  "msg": "查询成功",
  "money": 88359.49
}`,1)]),_:1})])]),_:1}),e($,{name:`goods`,tab:`2. 获取商品与平台 (POST)`},{default:a(()=>[s(`div`,C,[s(`div`,w,[s(`span`,T,[n[20]||=r(`接口地址：`,-1),s(`strong`,null,o(J.value.apiGoodsUrl),1)]),e(l,{size:`small`,ghost:``,type:`primary`,onClick:n[4]||=e=>X(J.value.apiGoodsUrl,`获取商品接口`)},{default:a(()=>[...n[21]||=[r(`复制接口`,-1)]]),_:1})]),n[26]||=s(`p`,{class:`m-0 text-13px text-gray-500`},[r(` 请求方式：`),s(`code`,null,`POST`),r(` | 说明：用于外部商城（如小储系统）自动拉取所有网课平台、获取对应 `),s(`code`,null,`cid`),r(`（即 platform 编号）与代理实时成本单价。 `)],-1),e(Q,{embedded:``,size:`small`,title:`请求参数规范`},{default:a(()=>[e(Z,{"label-placement":`left`,column:1,bordered:``,size:`small`},{default:a(()=>[e(Y,{label:`uid (必填)`},{default:a(()=>[...n[22]||=[r(`商户 UID`,-1)]]),_:1}),e(Y,{label:`key (必填)`},{default:a(()=>[...n[23]||=[r(`商户对接密钥`,-1)]]),_:1}),e(Y,{label:`fenlei (选填)`},{default:a(()=>[...n[24]||=[r(`按分类 ID 筛选，留空获取全部分类`,-1)]]),_:1})]),_:1})]),_:1}),e(Q,{embedded:``,size:`small`,title:`成功返回示例`},{default:a(()=>[...n[25]||=[s(`pre`,{class:`m-0 font-mono text-12px text-gray-700 dark:text-gray-300`},`{
  "code": 1,
  "data": [
    {
      "cid": "12",
      "name": "超星学习通[日常作业+视频]",
      "price": 0.85,
      "content": "支持自动换课，无视人脸",
      "noun": "xxt"
    }
  ]
}`,-1)]]),_:1})])]),_:1}),e($,{name:`query`,tab:`3. 在线查课 (POST)`},{default:a(()=>[s(`div`,E,[s(`div`,D,[s(`span`,O,[n[27]||=r(`接口地址：`,-1),s(`strong`,null,o(J.value.apiQueryUrl),1)]),e(l,{size:`small`,ghost:``,type:`primary`,onClick:n[5]||=e=>X(J.value.apiQueryUrl,`在线查课接口`)},{default:a(()=>[...n[28]||=[r(`复制接口`,-1)]]),_:1})]),n[36]||=s(`p`,{class:`m-0 text-13px text-gray-500`},[r(` 请求方式：`),s(`code`,null,`POST`),r(` | 说明：根据平台编号和学生账号密码在线查询当前名下修读的课程清单。 `)],-1),e(Q,{embedded:``,size:`small`,title:`请求参数规范`},{default:a(()=>[e(Z,{"label-placement":`left`,column:1,bordered:``,size:`small`},{default:a(()=>[e(Y,{label:`uid (必填)`},{default:a(()=>[...n[29]||=[r(`商户 UID`,-1)]]),_:1}),e(Y,{label:`key (必填)`},{default:a(()=>[...n[30]||=[r(`商户对接密钥`,-1)]]),_:1}),e(Y,{label:`platform (必填)`},{default:a(()=>[...n[31]||=[r(`课程平台编号（即商品获取接口中的 cid）`,-1)]]),_:1}),e(Y,{label:`school (必填)`},{default:a(()=>[...n[32]||=[r(`学校名称（无学校可传“自动识别”）`,-1)]]),_:1}),e(Y,{label:`user (必填)`},{default:a(()=>[...n[33]||=[r(`学生学习账号 / 手机号`,-1)]]),_:1}),e(Y,{label:`pass (必填)`},{default:a(()=>[...n[34]||=[r(`学生学习登录密码`,-1)]]),_:1})]),_:1})]),_:1}),e(Q,{embedded:``,size:`small`,title:`成功返回示例`},{default:a(()=>[...n[35]||=[s(`pre`,{class:`m-0 font-mono text-12px text-gray-700 dark:text-gray-300`},`{
  "code": 1,
  "msg": "查询成功",
  "userName": "张三",
  "data": [
    {
      "id": "2087412",
      "name": "大学英语进阶与听说训练",
      "teacher": "李老师",
      "state": "未完成"
    }
  ]
}`,-1)]]),_:1})])]),_:1}),e($,{name:`add`,tab:`4. 课程下单 (POST)`},{default:a(()=>[s(`div`,k,[s(`div`,ce,[s(`span`,A,[n[37]||=r(`接口地址：`,-1),s(`strong`,null,o(J.value.apiAddUrl),1)]),e(l,{size:`small`,ghost:``,type:`primary`,onClick:n[6]||=e=>X(J.value.apiAddUrl,`课程下单接口`)},{default:a(()=>[...n[38]||=[r(`复制接口`,-1)]]),_:1})]),n[48]||=s(`p`,{class:`m-0 text-13px text-gray-500`},[r(` 请求方式：`),s(`code`,null,`POST`),r(` | 说明：查课完毕后，将选定的课程提交平台进入自动上号挂机排队链路。 `)],-1),e(Q,{embedded:``,size:`small`,title:`请求参数规范`},{default:a(()=>[e(Z,{"label-placement":`left`,column:1,bordered:``,size:`small`},{default:a(()=>[e(Y,{label:`uid (必填)`},{default:a(()=>[...n[39]||=[r(`商户 UID`,-1)]]),_:1}),e(Y,{label:`key (必填)`},{default:a(()=>[...n[40]||=[r(`商户对接密钥`,-1)]]),_:1}),e(Y,{label:`platform (必填)`},{default:a(()=>[...n[41]||=[r(`商品平台编号 cid`,-1)]]),_:1}),e(Y,{label:`school (必填)`},{default:a(()=>[...n[42]||=[r(`学校名称`,-1)]]),_:1}),e(Y,{label:`user (必填)`},{default:a(()=>[...n[43]||=[r(`学习账号`,-1)]]),_:1}),e(Y,{label:`pass (必填)`},{default:a(()=>[...n[44]||=[r(`学习密码`,-1)]]),_:1}),e(Y,{label:`kcname (必填)`},{default:a(()=>[...n[45]||=[r(`需要修读的完整课程名称`,-1)]]),_:1}),e(Y,{label:`kcid (选填)`},{default:a(()=>[...n[46]||=[r(`上游课程 ID（建议携带，避免同名课程误判）`,-1)]]),_:1})]),_:1})]),_:1}),e(Q,{embedded:``,size:`small`,title:`成功返回示例`},{default:a(()=>[...n[47]||=[s(`pre`,{class:`m-0 font-mono text-12px text-gray-700 dark:text-gray-300`},`{
  "code": 1,
  "msg": "下单成功",
  "oid": 10582,
  "money": 0.85
}`,-1)]]),_:1})])]),_:1}),e($,{name:`autoAdd`,tab:`5. 查课并下单[一步到位] (POST)`},{default:a(()=>[s(`div`,j,[s(`div`,M,[s(`span`,N,[n[49]||=r(`接口地址：`,-1),s(`strong`,null,o(J.value.apiAutoAddUrl),1)]),e(l,{size:`small`,ghost:``,type:`primary`,onClick:n[7]||=e=>X(J.value.apiAutoAddUrl,`一步查课下单接口`)},{default:a(()=>[...n[50]||=[r(`复制接口`,-1)]]),_:1})]),e(K,{type:`success`},{default:a(()=>[...n[51]||=[r(` 特别推荐：专为小储商城、第三方发卡系统或自动化爬虫定制的一步提单接口。平台将自动校验并完成查课和扣费下单，一步返回订单结果！ `,-1)]]),_:1}),e(Q,{embedded:``,size:`small`,title:`请求 JSON 参数示例`},{default:a(()=>[s(`pre`,P,`{
  "uid": `+o(J.value.uid)+`,
  "key": "`+o(J.value.key||`YOUR_API_KEY`)+`",
  "platform": "课程CID",
  "school": "学校名称",
  "user": "学习账号",
  "pass": "学习密码",
  "kcname": "完整课程名称"
}`,1)]),_:1})])]),_:1}),e($,{name:`status`,tab:`6. 订单进度与状态 (GET / POST)`},{default:a(()=>[s(`div`,F,[s(`div`,I,[s(`span`,L,[n[52]||=r(`接口地址：`,-1),s(`strong`,null,o(J.value.apiStatusUrl),1)]),e(l,{size:`small`,ghost:``,type:`primary`,onClick:n[8]||=e=>X(J.value.apiStatusUrl,`进度查询接口`)},{default:a(()=>[...n[53]||=[r(`复制接口`,-1)]]),_:1})]),n[55]||=s(`p`,{class:`m-0 text-13px text-gray-500`},[r(` 请求方式：`),s(`code`,null,`GET / POST`),r(` | 说明：可随时通过订单号查询任务当前实时挂机进度、状态与备注信息。 `)],-1),e(Q,{embedded:``,size:`small`,title:`GET 请求调用示例`},{default:a(()=>[s(`div`,R,o(J.value.apiStatusUrl)+`?oid=10582 `,1)]),_:1}),e(Q,{embedded:``,size:`small`,title:`成功返回示例`},{default:a(()=>[...n[54]||=[s(`pre`,{class:`m-0 font-mono text-12px text-gray-700 dark:text-gray-300`},`[
  {
    "id": 10582,
    "ptname": "超星学习通",
    "school": "北京大学",
    "name": "张三",
    "user": "13800138000",
    "kcname": "大学英语",
    "status": "已完成",
    "progress": "100%",
    "addtime": "2026-09-11 14:20:00"
  }
]`,-1)]]),_:1})])]),_:1}),e($,{name:`budan`,tab:`7. 补单与重跑 (POST)`},{default:a(()=>[s(`div`,z,[s(`div`,B,[s(`span`,V,[n[56]||=r(`接口地址：`,-1),s(`strong`,null,o(J.value.apiBudanUrl),1)]),e(l,{size:`small`,ghost:``,type:`primary`,onClick:n[9]||=e=>X(J.value.apiBudanUrl,`补单接口`)},{default:a(()=>[...n[57]||=[r(`复制接口`,-1)]]),_:1})]),n[58]||=s(`p`,{class:`m-0 text-13px text-gray-500`},[r(` 请求方式：`),s(`code`,null,`POST`),r(` | 参数：`),s(`code`,null,`uid`),r(`, `),s(`code`,null,`key`),r(`, `),s(`code`,null,`oid`),r(` (订单号) `)],-1),e(Q,{embedded:``,size:`small`,title:`请求 JSON 参数示例`},{default:a(()=>[s(`pre`,H,`{
  "uid": `+o(J.value.uid)+`,
  "key": "`+o(J.value.key||`YOUR_API_KEY`)+`",
  "oid": 10582
}`,1)]),_:1})])]),_:1})]),_:1}),s(`div`,U,[n[68]||=s(`h4`,{class:`m-0 text-14px font-bold text-primary`},`小储商城 / 卡易信 / 外部发卡系统串货对接指南：`,-1),s(`div`,W,[s(`div`,null,[n[64]||=s(`p`,{class:`m-0 font-semibold`},`1. 站点类型选择：`,-1),n[65]||=s(`p`,{class:`m-0`},`在小储商城后台添加货源时，选择【小储系统】或【API通用对接】；`,-1),n[66]||=s(`p`,{class:`m-0 mt-6px font-semibold`},`2. 网站域名与密钥：`,-1),s(`p`,G,[n[59]||=r(`网站地址填 `,-1),s(`code`,null,o(J.value.apiBaseUrl?J.value.apiBaseUrl.replace(`/api.php`,``):`https://sk.yunxnet.cn`),1),n[60]||=r(`，商户ID填 `,-1),s(`code`,null,o(J.value.uid),1),n[61]||=r(`，密钥填您的 `,-1),n[62]||=s(`code`,null,`KEY`,-1),n[63]||=r(`；`,-1)])]),n[67]||=s(`div`,null,[s(`p`,{class:`m-0 font-semibold`},`3. 商品绑定对应：`),s(`p`,{class:`m-0`},[r(`小储端【商品编号】直接填写我方的 `),s(`code`,null,`cid`),r(`（在第2个Tab商品列表中获取）；`)]),s(`p`,{class:`m-0 mt-6px font-semibold`},`4. 自动化运行：`),s(`p`,{class:`m-0`},`客户在您的小储前台下单付款后，系统将自动发起 API 调用扣费秒级流转到我方服务器！`)],-1)])])]),_:1})])}}});export{K as default};