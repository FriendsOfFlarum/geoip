import app from 'flarum/forum/app';
import { extend, override } from 'flarum/common/extend';
import IPAddress from 'flarum/common/components/IPAddress';
import IPInfo from '../models/IPInfo';
import { getIPData } from '../helpers/IPDataHelper';
import Tooltip from 'flarum/common/components/Tooltip';
import Button from 'flarum/common/components/Button';
import { handleCopyIP } from '../helpers/ClipboardHelper';
import MapModal from '../components/MapModal';

export default function extendIpAddress() {
  extend(IPAddress.prototype, 'viewItems', function (items) {
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
              app.modal.show(MapModal, { ipInfo: this.ipInfo, ipAddr: this.ip });
            }}
            aria-label={app.translator.trans('fof-geoip.forum.map_button_label')}
          />
        </Tooltip>,
        90
      );
    }
  });

  override(IPAddress.prototype, 'view', function () {
    return <span className="IPAddress IPAddress--enhanced ip-container">{this.viewItems().toArray()}</span>;
  });

  IPAddress.prototype.loadIpInfo = async function () {
    if (this.ip.length === 0) return;
    const ipInfo = app.store.getBy<IPInfo>('ip_info', 'ip', this.ip) || (await app.store.find<IPInfo>('ip_info', encodeURIComponent(this.ip)));
    this.ipInfo = ipInfo;
    m.redraw();
  };
}
