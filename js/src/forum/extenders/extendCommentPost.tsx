import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import ItemList from 'flarum/common/utils/ItemList';
import CommentPost from 'flarum/forum/components/CommentPost';
import type Mithril from 'mithril';
import { getIPData } from '../helpers/IPDataHelper';

export default function extendCommentPost() {
  extend(CommentPost.prototype, 'headerItems', function (items: ItemList<Mithril.Children>) {
    if (app.forum.attribute<boolean>('fof-geoip.showFlag')) {
      const ipInfo = this.attrs.post.ip_info?.();
      if (ipInfo) {
        const { image } = getIPData(ipInfo);
        items.add('country', image, 100);
      }
    }
  });
}