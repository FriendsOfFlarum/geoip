import app from 'flarum/admin/app';
import GeoipSettingsPage from './components/GeoipSettingsPage';

export { default as extend } from './extend';

app.initializers.add('fof/geoip', () => {
  GeoipSettingsPage.register();
});
