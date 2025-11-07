import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import { getIPData } from '../../common/helpers/IPDataHelper';

export default function extendCommentPost() {
  extend('flarum/forum/components/CommentPost', 'headerItems', function (items: ItemList<Mithril.Children>) {
    if (app.forum.attribute<boolean>('fof-geoip.showFlag')) {
      const ipInfo = this.attrs.post.ipInfo?.();
      const postUser = this.attrs.post.user();
      if (((ipInfo && postUser && postUser.showIPCountry()) || app.session.user?.canSeeCountry?.()) && ipInfo) {
        const { image } = getIPData(ipInfo);
        if (image) {
          items.add('country', image, 100);
        }
      }
    }
  });
}
