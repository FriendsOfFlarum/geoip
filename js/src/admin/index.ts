import app from 'flarum/admin/app';
import GeoipSettingsPage from './components/GeoipSettingsPage';
import extendIpAddress from '../common/extenders/extendIpAddress';

export { default as extend } from './extend';

app.initializers.add('fof/geoip', () => {
  GeoipSettingsPage.register();
  extendIpAddress();
});
