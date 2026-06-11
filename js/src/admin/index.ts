import app from 'flarum/admin/app';
import extendIpAddress from '../common/extenders/extendIpAddress';

export { default as extend } from './extend';

app.initializers.add('fof/geoip', () => {
  extendIpAddress();
});
