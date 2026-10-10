import { extend } from 'flarum/common/extend';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import { headerFlag } from '../../common/helpers/headerFlag';

/**
 * The sender's flag in a private message's header (flarum/messages), on the
 * same terms as a post's — see headerFlag().
 */
export default function extendMessage() {
  extend('ext:flarum/messages/forum/components/Message', 'headerItems', function (items: ItemList<Mithril.Children>) {
    const message = this.attrs.message;
    const flag = headerFlag(message.user(), message.ipInfo?.());

    if (flag) items.add('country', flag, 100);
  });
}
