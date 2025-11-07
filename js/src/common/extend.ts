import Extend from 'flarum/common/extenders';
import IPInfo from './model/IPInfo';

export default [
  new Extend.Store() //
    .add('ip_info', IPInfo),
];
