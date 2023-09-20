import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import AccessTokensList from 'flarum/forum/components/AccessTokensList';
import ItemList from 'flarum/common/utils/ItemList';
import MapModal from '../components/MapModal';
import Button from 'flarum/common/components/Button';
import type Mithril from 'mithril';
import AccessToken from 'flarum/common/models/AccessToken';

export default function extendAccessTokensList() {
  extend(AccessTokensList.prototype, 'tokenActionItems', function (items: ItemList<Mithril.Children>, token: AccessToken) {
    const ipAddr = token.lastIpAddress();

    if (ipAddr) {
      items.add(
        'geoip-info',
        <Button
          className="Button"
          onclick={() => app.modal.show(MapModal, { ipAddr: ipAddr })}
          aria-label={app.translator.trans('fof-geoip.forum.map_button_label')}
        >
          {app.translator.trans('fof-geoip.forum.map_button_label')}
        </Button>,
        10
      );
    }
  });
}
