import app from 'flarum/common/app';
import copyToClipboard from '../util/copyToClipboard';

export const handleCopyIP = (ip: string) => {
  return () => {
    copyToClipboard(ip);
    app.alerts.show({ type: 'success' }, app.translator.trans('fof-geoip.lib.alerts.ip_copied'));
  };
};
