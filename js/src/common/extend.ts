import Extend from 'flarum/common/extenders';
import Post from 'flarum/common/models/Post';

import IPInfo from './models/IPInfo';

export default [
  new Extend.Store() //
    .add('ip_info', IPInfo),

  new Extend.Model(Post) //
    .hasOne<IPInfo>('ip_info'),
];
