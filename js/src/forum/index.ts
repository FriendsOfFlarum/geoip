import app from 'flarum/forum/app';
import extendBanIPModal from './extenders/extendBanIPModal';
import extendCommentPost from './extenders/extendCommentPost';
import extendUserPreferences from './extenders/extendUserPreferences';
import extendIpAddress from '../common/extenders/extendIpAddress';

export { default as extend } from './extend';

app.initializers.add('fof/geoip', () => {
  // @TODO - implement ban IP modal when extension is updated to Flarum 2.0
  //extendBanIPModal();
  extendCommentPost();
  extendUserPreferences();
  extendIpAddress();
});
