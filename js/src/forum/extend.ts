import Extend from 'flarum/common/extenders';
import User from 'flarum/common/models/User';

import { default as commonExtend } from '../common/extend';

export default [
  ...commonExtend,

  new Extend.Model(User) //
    .attribute('showIPCountry')
    .attribute('canSeeCountry')
    .attribute('customFlagCountry'),
];
