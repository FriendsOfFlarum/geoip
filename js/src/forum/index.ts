import app from 'flarum/forum/app';
import extendPostMeta from './extenders/extendPostMeta';
import extendBanIPModal from './extenders/extendBanIPModal';
import extendAccessTokensList from './extenders/extendAccessTokensList';
import extendCommentPost from './extenders/extendCommentPost';
import extendUserPreferences from './extenders/extendUserPreferences';

export { default as extend } from './extend';

app.initializers.add('fof/geoip', () => {
  extendPostMeta();
  extendBanIPModal();
  extendAccessTokensList();
  extendCommentPost();
  extendUserPreferences();
});
