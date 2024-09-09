import Extend from 'flarum/common/extenders';
import IPInfo from './models/IPInfo';
import Post from 'flarum/common/models/Post';
import User from 'flarum/common/models/User';

export default [
  new Extend.Store() //
    .add('ip_info', IPInfo),

  new Extend.Model(Post) //
    .hasOne('ip_info'),

  new Extend.Model(User) //
    .attribute('showIPCountry'),
];
