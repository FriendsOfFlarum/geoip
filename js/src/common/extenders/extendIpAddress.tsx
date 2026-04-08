import app from 'flarum/common/app';
import { extend, override } from 'flarum/common/extend';
import IPInfo from '../model/IPInfo';
import { getIPData } from '../helpers/IPDataHelper';
import Tooltip from 'flarum/common/components/Tooltip';
import Button from 'flarum/common/components/Button';
import { handleCopyIP } from '../helpers/ClipboardHelper';
import type Mithril from 'mithril';
import ItemList from 'flarum/common/utils/ItemList';

/**
 * In-flight IP info requests, keyed by IP address.
 * Prevents duplicate API calls when multiple components request the same IP.
 */
const ipInfoRequests = new Map<string, Promise<IPInfo>>();

export default function extendIpAddress() {
  extend('flarum/common/components/IPAddress', 'viewItems', function (items: ItemList<Mithril.Children>) {
    // loadIpInfo is triggered by Intersection Observer when component enters viewport
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
    return (
      <span
        className="IPAddress IPAddress--enhanced ip-container"
        oncreate={(vnode: Mithril.VnodeDOM) => {
          if (this.ip.length === 0 || this.ipInfo) return;
          const observer = new IntersectionObserver(
            (entries) => {
              for (const entry of entries) {
                if (entry.isIntersecting) {
                  this.loadIpInfo();
                  observer.disconnect();
                }
              }
            },
            { rootMargin: '100px' }
          );
          observer.observe(vnode.dom);
          this._ipObserver = observer;
        }}
        onremove={() => {
          if (this._ipObserver) {
            this._ipObserver.disconnect();
            this._ipObserver = null;
          }
        }}
      >
        {this.viewItems().toArray()}
      </span>
    );
  });

  extend('flarum/common/components/IPAddress', 'oninit', function () {
    // Populate synchronously if already in store (e.g. included with post response).
    this.ipInfo = app.store.getBy<IPInfo>('ip_info', 'ip', this.ip) ?? undefined;

    this.loadIpInfo = async function () {
      if (this.ip.length === 0) return;

      try {
        // Deduplicate: reuse in-flight request if another component already requested this IP
        let request = ipInfoRequests.get(this.ip);
        if (!request) {
          request = app.store.find<IPInfo>('ip_info', encodeURIComponent(this.ip));
          ipInfoRequests.set(this.ip, request);
          request.finally(() => ipInfoRequests.delete(this.ip));
        }
        this.ipInfo = await request;
        m.redraw();
      } catch (error) {
        console.error('Failed to load IP info:', error);
      }
    };
  });
}
