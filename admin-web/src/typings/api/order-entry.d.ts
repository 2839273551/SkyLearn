declare namespace Api {
  namespace OrderEntry {
    interface Category {
      id: string;
      name: string;
      sort: number;
    }

    interface Product {
      id: string;
      categoryId: string;
      name: string;
      price: string;
      queryFee: string;
      content: string;
      noun: string;
      sort: number;
      noCheck: boolean;
    }

    interface Catalog {
      categories: Category[];
      products: Product[];
      balance: string;
      freeAdd: number;
      freeOrderEnabled: boolean;
      queryEnabled: boolean;
      orderEnabled: boolean;
      notice: string;
    }

    interface Course {
      id: string;
      name: string;
      teacher: string;
      state: string;
      kcjs: string;
    }

    interface QueryResult {
      code: number;
      msg: string;
      userinfo: string;
      userName: string;
      courses: Course[];
    }

    interface QueryResponse {
      results: QueryResult[];
      balance: string;
      queryFee: string;
    }

    interface Selection {
      userinfo: string;
      userName: string;
      course: Course;
    }

    interface SubmitResponse {
      submitted: number;
      charged: string;
      balance: string;
      freeAdd: number;
    }
  }
}
