import Extend from 'flarum/common/extenders';
import User from 'flarum/common/models/User';

import { default as commonExtend } from '../common/extend';
import Post from 'flarum/common/models/Post';
import IPInfo from '../common/model/IPInfo';

export default [
  ...commonExtend,

  new Extend.Model(Post) //
    .hasOne<IPInfo>('ipInfo'),

  // Private messages record an address too. The model is taken from the
  // registry rather than imported, so nothing here depends on flarum/messages
  // being present, and the extender is only built when it is.
  ...('flarum-messages' in flarum.extensions
    ? [
        new Extend.Model(flarum.reg.get('flarum-messages', 'common/models/DialogMessage')) //
          .hasOne<IPInfo>('ipInfo'),
      ]
    : []),

  new Extend.Model(User) //
    .attribute('showIPCountry')
    .attribute('canSeeCountry')
    .attribute<string | null>('customFlagCountry'),
];
