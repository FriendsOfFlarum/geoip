import Extend from 'flarum/common/extenders';
import User from 'flarum/common/models/User';

import { default as commonExtend } from '../common/extend';
import Post from 'flarum/common/models/Post';
import IPInfo from './models/IPInfo';

export default [
  ...commonExtend,

  new Extend.Store() //
    .add('ip_info', IPInfo),

  new Extend.Model(Post) //
    .hasOne<IPInfo>('ip_info'),

  new Extend.Model(User) //
    .attribute('showIPCountry')
    .attribute('canSeeCountry'),
];
