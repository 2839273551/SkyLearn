declare namespace Api {
  namespace Orders {
    interface Query {
      page: number;
      pageSize: number;
      keyword?: string;
      status?: string;
    }

    interface Record {
      orderId: string;
      ownerId: string;
      courseId: string;
      account: string;
      password?: string;
      fees?: string;
      platform: string;
      courseName: string;
      school: string;
      progress: string;
      remarks: string;
      status: string;
      dockStatus: string;
      createdAt: string;
    }

    interface Page {
      records: Record[];
      current: number;
      size: number;
      total: number;
    }
  }
}
