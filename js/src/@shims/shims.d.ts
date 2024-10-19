import IPInfo from '../forum/models/IPInfo';

declare module 'flarum/common/models/Post' {
  export default interface Post {
    ip_info: () => IPInfo;
  }
}

declare module 'flarum/common/models/User' {
  export default interface User {
    showIPCountry: () => boolean;
    canSeeCountry: () => boolean;
  }
}

declare module 'flarum/common/components/IPAddress' {
  export default interface IPAddress {
    ipInfo?: IPInfo;
    loadIpInfo: () => void;
  }
}
