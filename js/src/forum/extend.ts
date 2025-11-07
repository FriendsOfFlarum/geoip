import Extend from 'flarum/common/extenders';
import User from 'flarum/common/models/User';

import { default as commonExtend } from '../common/extend';
import Post from 'flarum/common/models/Post';
import IPInfo from '../common/model/IPInfo';

export default [
  ...commonExtend,

  new Extend.Model(Post) //
    .hasOne<IPInfo>('ipInfo'),

  new Extend.Model(User) //
    .attribute('showIPCountry')
    .attribute('canSeeCountry'),
];
