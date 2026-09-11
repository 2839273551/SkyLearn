declare namespace Api {
  namespace Dashboard {
    interface Summary {
      orderTotal: number;
      todayOrders: number;
      runningOrders: number;
      completedOrders: number;
      userTotal: number;
      balance: string;
      announcement: string;
    }
  }
}
