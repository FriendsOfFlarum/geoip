import app from 'flarum/forum/app';
import extendPostMeta from './extenders/extendPostMeta';
import extendBanIPModal from './extenders/extendBanIPModal';

export { default as extend } from './extend';

app.initializers.add('fof/geoip', () => {
  extendPostMeta();
  extendBanIPModal();
});
