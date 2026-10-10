import { extend } from 'flarum/common/extend';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import { headerFlag } from '../../common/helpers/headerFlag';

/**
 * The author's flag in a post's header. The decision lives in headerFlag(),
 * shared with private messages so both follow the same rules.
 */
export default function extendCommentPost() {
  extend('flarum/forum/components/CommentPost', 'headerItems', function (items: ItemList<Mithril.Children>) {
    const post = this.attrs.post;
    const flag = headerFlag(post.user(), post.ipInfo?.());

    if (flag) items.add('country', flag, 100);
  });
}
