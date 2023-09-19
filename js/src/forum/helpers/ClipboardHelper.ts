import app from 'flarum/forum/app';
import copyToClipboard from '../util/copyToClipboard';

export const handleCopyIP = (ip: string) => {
  return () => {
    copyToClipboard(ip);
    app.alerts.show({ type: 'success' }, app.translator.trans('fof-geoip.forum.alerts.ip_copied'));
  };
};
