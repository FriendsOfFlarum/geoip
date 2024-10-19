import app from 'flarum/admin/app';
import GeoipSettingsPage from './components/GeoipSettingsPage';

export * from './components';
export { default as extend } from './extend';

app.initializers.add('fof/geoip', () => {
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
