import app from 'flarum/common/app';
import { extend, override } from 'flarum/common/extend';
import IPInfo from '../model/IPInfo';
import { getIPData } from '../helpers/IPDataHelper';
import Tooltip from 'flarum/common/components/Tooltip';
import Button from 'flarum/common/components/Button';
import { handleCopyIP } from '../helpers/ClipboardHelper';
import type Mithril from 'mithril';
import ItemList from 'flarum/common/utils/ItemList';

export default function extendIpAddress() {
  extend('flarum/common/components/IPAddress', 'viewItems', function (items: ItemList<Mithril.Children>) {
    if (!this.ipInfo) {
      this.loadIpInfo();
    }

    if (this.ipInfo && items.has('ip')) {
      items.remove('ip');

      const { description, threat, image } = getIPData(this.ipInfo);

      items.add(
        'ipInfo',
        <span className="ip-info">
          {image}
          <Tooltip text={`${description} ${threat ? `(${threat})` : ''}`}>
            <code>{this.ip}</code>
          </Tooltip>
        </span>,
        100
      );

      items.add(
        'copyButton',
        <Tooltip text={app.translator.trans('fof-geoip.forum.copy_ip_label')}>
          <Button
            icon="fas fa-copy"
            className="Button Button--icon Button--link"
            onclick={handleCopyIP(this.ip)}
            aria-label={app.translator.trans('fof-geoip.forum.copy_ip_label')}
          />
        </Tooltip>,
        95
      );

      items.add(
        'infoButton',
        <Tooltip text={app.translator.trans('fof-geoip.forum.map_button_label')}>
          <Button
            icon="fas fa-info-circle"
            className="Button Button--icon Button--link"
            onclick={(e: Event) => {
              e.stopPropagation();
              app.modal.show(() => import('../components/MapModal'), { ipInfo: this.ipInfo, ipAddr: this.ip });
            }}
            aria-label={app.translator.trans('fof-geoip.forum.map_button_label')}
          />
        </Tooltip>,
        90
      );
    }
  });

  override('flarum/common/components/IPAddress', 'view', function () {
    return <span className="IPAddress IPAddress--enhanced ip-container">{this.viewItems().toArray()}</span>;
  });

  extend('flarum/common/components/IPAddress', 'oninit', function () {
    this.loadIpInfo = async function () {
      if (this.ip.length === 0) return;
      try {
        // Try to get from store first
        let ipInfo = app.store.getBy<IPInfo>('ip_info', 'ip', this.ip);

        // If not in store, fetch from API
        if (!ipInfo) {
          ipInfo = await app.store.find<IPInfo>('ip_info', encodeURIComponent(this.ip));
        }

        this.ipInfo = ipInfo;
        m.redraw();
      } catch (error) {
        console.error('Failed to load IP info:', error);
      }
    };
  });
}
