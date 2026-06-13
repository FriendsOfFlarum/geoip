import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import { getIPData, getFlagImageForCountry } from '../../common/helpers/IPDataHelper';

export default function extendCommentPost() {
  extend('flarum/forum/components/CommentPost', 'headerItems', function (items: ItemList<Mithril.Children>) {
    if (!app.forum.attribute<boolean>('fof-geoip.showFlag')) return;

    const postUser = this.attrs.post.user();

    // A user-selected custom flag wins over the IP-based flag, but only while
    // the admin feature is enabled. If the admin later disables it, the stored
    // preference is not serialized (see the customFlagCountry field visibility
    // in extend.php), so we fall back to today's IP-based behaviour.
    if (postUser && app.forum.attribute<boolean>('fof-geoip.allowCustomFlag')) {
      const customFlag = postUser.customFlagCountry();
      if (customFlag) {
        // Custom flag is self-disclosed and therefore visible to everyone.
        const image = getFlagImageForCountry(customFlag);
        if (image) {
          items.add('country', image, 100);
        }
        return;
      }
    }

    // IP-based flag: only shown to the post author's opted-in audience and
    // actors with permission to see the country.
    const ipInfo = this.attrs.post.ipInfo?.();
    if (((ipInfo && postUser && postUser.showIPCountry()) || app.session.user?.canSeeCountry?.()) && ipInfo) {
      const { image } = getIPData(ipInfo);
      if (image) {
        items.add('country', image, 100);
      }
    }
  });
}
