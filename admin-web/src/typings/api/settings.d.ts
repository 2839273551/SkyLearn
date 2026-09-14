declare namespace Api {
  namespace Settings {
    interface Values {
      sitename: string;
      avatar?: string;
      keywords: string;
      description: string;
      logo: string;
      sykg: string;
      ddggkg: string;
      czph: string;
      qdkg: string;
      notice: string;
      ddgg: string;
      tcgonggao: string;
      zsgonggao: string;
      sjqykg: string;
      user_yqzc: string;
      user_htkh: string;
      user_ktmoney: string;
      zxczkg: string;
      zdpay: string;
      is_qqpay: string;
      is_wxpay: string;
      is_alipay: string;
      epay_api: string;
      epay_pid: string;
      epay_key: string;
      yqjl: string;
      yqsq: string;
      yqsx: string;
      mfxdkg: string;
      mfxd: string;
      flkg: string;
      fllx: string;
      zddy: string;
      zdxd: string;
      ckkg: string;
      xdkg: string;
      zzqq: string;
      zzvx: string;
    }

    interface Payload {
      settings: Values;
      hasEpayKey: boolean;
    }
  }

  namespace Fenlei {
    interface Item {
      id: string;
      sort: number;
      name: string;
      status: number;
      time: string;
      courseCount: number;
    }

    interface ListResponse {
      list: Item[];
    }
  }

  namespace Huoyuan {
    interface Item {
      hid: string;
      pt: string;
      ptName: string;
      name: string;
      url: string;
      user: string;
      ip: string;
      cookie: string;
      status: number;
      hasPass: boolean;
      hasToken: boolean;
      addtime: string;
      endtime: string;
    }

    interface PlatformOption {
      value: string;
      label: string;
    }

    interface ListResponse {
      list: Item[];
      platformOptions: PlatformOption[];
    }

    interface BalanceResponse {
      hid: string;
      name: string;
      balance: string;
      raw?: any;
    }
  }

  namespace Class {
    interface Item {
      cid: string;
      sort: number;
      name: string;
      getnoun: string;
      noun: string;
      price: string;
      vipprice: string;
      ckkf: string;
      queryplat: string;
      docking: string;
      yunsuan: string;
      content: string;
      status: number;
      fenlei: string;
      kcid: string;
      addtime: string;
      fenleiName: string;
      cxName: string;
      addName: string;
    }

    interface ListResponse {
      records: Item[];
      current: number;
      size: number;
      total: number;
    }

    interface SelectOption {
      value: string;
      label: string;
    }

    interface OptionsResponse {
      fenleiList: SelectOption[];
      huoyuanList: SelectOption[];
      platformOptions: SelectOption[];
    }

    interface PriceSortUpdate {
      cid: number;
      price?: string;
      sort?: number;
    }
  }

  namespace Yjdj {
    interface RemoteClassItem {
      cid: string;
      name: string;
      price: string;
      fenleiname: string;
      content: string;
      isOnline: boolean;
    }

    interface RemoteClassesResponse {
      hid: string;
      categories: string[];
      classes: RemoteClassItem[];
      total: number;
    }

    interface CopyFenleiResponse {
      createdFenlei: number;
      skippedFenlei: number;
      createdClass?: number;
      updatedClass?: number;
    }

    interface BatchOnlineResponse {
      createdCount: number;
      updatedCount: number;
    }
  }

  namespace Dengji {
    interface Item {
      id: string;
      sort: number;
      name: string;
      rate: string;
      money: string;
      addkf: number;
      gjkf: number;
      status: number;
      time: string;
    }

    interface ListResponse {
      list: Item[];
    }
  }

  namespace Mijia {
    interface Item {
      mid: string;
      uid: string;
      userName: string;
      cid: string;
      className: string;
      mode: number;
      price: string;
      addtime: string;
    }

    interface ListResponse {
      list: Item[];
    }
  }

  namespace Paylist {
    interface Record {
      oid: string;
      outTradeNo: string;
      tradeNo: string;
      type: string;
      uid: string;
      name: string;
      money: string;
      status: number;
      addtime: string;
      endtime: string;
    }

    interface ListResponse {
      records: Record[];
      current: number;
      size: number;
      total: number;
    }
  }

  namespace Guanx {
    interface Record {
      id: string;
      content: string;
      money: number;
      status: number;
      uid: string;
      batchId: number;
      addtime: string;
      usedtime: string;
    }

    interface ListResponse {
      records: Record[];
      current: number;
      size: number;
      total: number;
    }

    interface GenerateResponse {
      count: number;
      batchId: number;
      cards: string[];
    }
  }

  namespace Gglist {
    interface Item {
      id: string;
      title: string;
      content: string;
      time: string;
      uid: string;
      status: number;
      zhiding: number;
    }

    interface ListResponse {
      list: Item[];
    }
  }

  namespace DataStats {
    interface Stats {
      totalUsers: number;
      todayUsers: number;
      totalOrders: number;
      todayOrders: number;
      yesterdayOrders: number;
      sevenDaysOrders: number;
      todaySales: string;
      yesterdaySales: string;
      todayRecharge: string;
    }
  }

  namespace DdtjStats {
    interface RankItem {
      name: string;
      today: number;
      yesterday: number;
      week: number;
      month: number;
      total: number;
      latest: string;
    }

    interface Stats {
      huoyuanRank: RankItem[];
      platformRank: RankItem[];
    }
  }

  namespace Zzbz {
    interface CronItem {
      title: string;
      cycle: string;
      url: string;
    }

    interface DaemonItem {
      name: string;
      count: number;
      cmd: string;
      dir: string;
    }

    interface Info {
      crons: CronItem[];
      daemons: DaemonItem[];
    }
  }

  namespace Webmsg {
    interface SystemInfo {
      appName: string;
      author: string;
      version: string;
      domain: string;
      serverIp: string;
      phpVersion: string;
      os: string;
    }

    interface TimelineItem {
      version: string;
      time: string;
      desc: string;
    }

    interface Info {
      systemInfo: SystemInfo;
      timeline: TimelineItem[];
    }
  }

  namespace ProfileArea {
    interface UserItem {
      uid: string;
      uuid: string;
      user: string;
      name: string;
      addprice: string;
      money: string;
      zcz: string;
      yqm: string;
      active: number;
      key: string;
      addtime: string;
      endtime: string;
    }

    interface LatestClass {
      cid: string;
      name: string;
      price: string;
      fenlei: string;
      addtime: string;
    }

    interface OfflineClass {
      cid: string;
      courseName: string;
      categoryId: string;
      categoryName: string;
      content: string;
    }

    interface RankData {
      userRank: Array<{ name: string; orderCount: number }>;
      courseRank: Array<{ platform: string; courseName: string; orderCount: number }>;
      rechargeRank?: Array<{ name: string; money: string }>;
      czEnabled?: boolean;
    }

    interface KcidRecord {
      oid: string;
      user: string;
      platform: string;
      courseName: string;
      kcid: string;
      progress: string;
      status: string;
      addtime: string;
    }

    interface AvailableOrder {
      oid: string;
      platform: string;
      courseName: string;
      status: string;
      progress: string;
      remarks: string;
      addtime: string;
    }

    interface LogItem {
      id: string;
      uid: string;
      type: string;
      text: string;
      money: string;
      smoney: string;
      ip: string;
      addtime: string;
    }

    interface HelpItem {
      cid: string;
      name: string;
      content: string;
      fenleiName: string;
    }

    interface MyPriceItem {
      cid: string;
      name: string;
      basePrice: string;
      userPrice: string;
      ckkf: string;
      fenleiName: string;
      content: string;
    }

    interface MyPriceResponse {
      userRate: string;
      totalProducts: number;
      totalCategories: number;
      list: MyPriceItem[];
    }

    interface DockingInfo {
      uid: string;
      key: string;
      apiBaseUrl: string;
      apiBalanceUrl: string;
      apiGoodsUrl: string;
      apiQueryUrl: string;
      apiAddUrl: string;
      apiAutoAddUrl: string;
      apiStatusUrl: string;
      apiBudanUrl: string;
    }

    interface PchangeRecord {
      cid: string;
      kcname: string;
      oldprice: string;
      newprice: string;
      updatetime: string;
    }

    interface PchangeResponse {
      records: PchangeRecord[];
      current: number;
      size: number;
      total: number;
      stats: { total: number; today: number };
    }

    interface ChargeInfo {
      user: string;
      balance: string;
      isSuper: boolean;
      isDirect: boolean;
      onlineRechargeEnabled: boolean;
      minAmount: string;
      isAlipay: boolean;
      isWxpay: boolean;
      isQqpay: boolean;
    }

    interface UserProfile {
      uid: string;
      user: string;
      name: string;
      avatar?: string;
      money: string;
      zcz: string;
      addprice: string;
      vip: number;
      freeAdd: number;
      yqm: string;
      yqprice: string;
      inviteUrl: string;
      superiorUser: string;
      key: string;
      hasKey: boolean;
      pushPlusToken: string;
      totalOrders: number;
      stats: {
        agentTotal: number;
        agentRegToday: number;
        agentLoginToday: number;
        orderToday: number;
      };
      siteNotice: string;
      superiorNotice: string;
      siteName: string;
    }
    interface WorkorderItem {
      gid: string;
      uid: string;
      userName: string;
      displayName: string;
      region: string;
      title: string;
      content: string;
      state: string;
      addtime: string;
    }

    interface WorkorderListResponse {
      records: WorkorderItem[];
      total: number;
      page: number;
      pageSize: number;
      stats: {
        pending: number;
        answered: number;
        finished: number;
      };
      isSuper: boolean;
    }
  }

  namespace DockingLog {
    interface Item {
      id: string;
      direction: 'in' | 'out';
      action: string;
      caller: string;
      uid: number;
      target: string;
      method: string;
      ip: string;
      params: string;
      response: string;
      status: number;
      cost_ms: number;
      bytes_in: number;
      bytes_out: number;
      traffic_total: number;
      traffic_text: string;
      traffic_detail: string;
      created_at: string;
    }

    interface Metrics {
      today_total: number;
      today_in: number;
      today_out: number;
      today_traffic: string;
      today_traffic_bytes: number;
      avg_cost_ms: number;
      success_rate: string;
      is_admin: boolean;
    }

    interface ListResponse {
      records: Item[];
      current: number;
      size: number;
      total: number;
      metrics: Metrics;
    }
  }

  namespace Addtj {
    interface ClassOption {
      cid: string;
      name: string;
      price: number;
      base_price: number;
      fenlei_id: string;
      fenlei_name: string;
      content: string;
    }

    interface OptionsResponse {
      classes: ClassOption[];
      user_money: number;
      user_rate: number;
    }

    interface SubmitResponse {
      success_count: number;
      total_count: number;
      deducted_money: number;
      remain_money: number;
    }
  }

  namespace Adduser {
    interface GradeItem {
      id: string;
      name: string;
      rate: number;
      money: number;
      addkf: number;
      disabled: boolean;
    }

    interface GradeOptionsResponse {
      grades: GradeItem[];
      user_htkh: string;
      user_ktmoney: number;
      current_user_rate: number;
      is_admin: boolean;
    }

    interface CreateUserResponse {
      uid: number;
      user: string;
      name: string;
      rate: number;
      first_recharge: number;
      deducted: number;
    }
  }

  namespace Scheduler {
    interface TaskItem {
      id: string;
      name: string;
      description: string;
      enabled: boolean;
      interval_mins: number;
      last_run_time: string;
      last_cost_ms: number;
      last_status: number;
      last_result: string;
      total_runs: number;
      total_success: number;
      total_failed: number;
      pending_count: number;
      latest_log: string;
    }

    interface Summary {
      total_tasks: number;
      enabled_tasks: number;
      total_runs_all: number;
      total_success_all: number;
      total_logs?: number;
    }

    interface TasksListResponse {
      tasks: TaskItem[];
      summary: Summary;
    }

    interface RunResult {
      task_id: string;
      cost_ms: number;
      success_count: number;
      failed_count: number;
      summary: string;
      logs: string;
    }

    interface LogItem {
      id: string;
      task_id: string;
      content: string;
      status: number;
      cost_ms: number;
      processed_count: number;
      created_at: string;
    }

    interface LogsResponse {
      logs: LogItem[];
    }
  }
}
