import app from 'flarum/forum/app';
import extendBanIPModal from './extenders/extendBanIPModal';
import extendCommentPost from './extenders/extendCommentPost';
import extendUserPreferences from './extenders/extendUserPreferences';
import extendIpAddress from './extenders/extendIpAddress';

export { default as extend } from './extend';
export * from './components';
export * from './models';

app.initializers.add('fof/geoip', () => {
  extendBanIPModal();
  extendCommentPost();
  extendUserPreferences();
  extendIpAddress();
});
