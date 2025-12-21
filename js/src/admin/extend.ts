import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';

import { default as commonExtend } from '../common/extend';
import GeoipSettingsPage from './components/GeoipSettingsPage';

export default [
  ...commonExtend,

  new Extend.Admin() //
    .page(GeoipSettingsPage)
    .permission(
      () => ({
        icon: 'fas fa-globe',
        permission: 'fof-geoip.canSeeCountry',
        label: app.translator.trans('fof-geoip.admin.permissions.see_country'),
      }),
      'moderate',
      50
    ),
];
