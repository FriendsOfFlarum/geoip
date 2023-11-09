import app from 'flarum/forum/app';
import extendPostMeta from './extenders/extendPostMeta';
import extendBanIPModal from './extenders/extendBanIPModal';
import extendAccessTokensList from './extenders/extendAccessTokensList';

export { default as extend } from './extend';

app.initializers.add('fof/geoip', () => {
  extendPostMeta();
  extendBanIPModal();
  extendAccessTokensList();
});
