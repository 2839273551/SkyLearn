declare namespace Api {
  /**
   * namespace Auth
   *
   * backend api module: "auth"
   */
  namespace Auth {
    interface LoginToken {
      token: string;
      refreshToken: string;
    }

    interface UserInfo {
      userId: string;
      userName: string;
      displayName: string;
      avatar?: string;
      siteName: string;
      balance: string;
      csrfToken: string;
      roles: string[];
      buttons: string[];
      capabilities: string[];
      canMigrateSuperior?: boolean;
      sykg?: boolean;
      ddggkg?: boolean;
      ddgg?: string;
      czph?: boolean;
      qdkg?: boolean;
      hasSignedIn?: boolean;
    }
  }
}
