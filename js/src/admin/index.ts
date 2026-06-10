import app from 'flarum/admin/app';
import GeoipSettingsPage from './components/GeoipSettingsPage';
import extendIpAddress from '../common/extenders/extendIpAddress';

export * from './components';
export * from '../common/models';
export { default as extend } from './extend';

app.initializers.add('fof/geoip', () => {
  extendIpAddress();

  app.extensionData
    .for('fof-geoip')
    .registerPage(GeoipSettingsPage)
    .registerPermission(
      {
        icon: 'fas fa-globe',
        permission: 'fof-geoip.canSeeCountry',
        label: app.translator.trans('fof-geoip.admin.permissions.see_country'),
      },
      'moderate',
      50
    );
});
