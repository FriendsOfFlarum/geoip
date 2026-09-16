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
 * IP info lookups, keyed by IP address, holding the in-flight promise and
 * (once resolved) the record itself.
 *
 * We cannot look these up in `app.store` by address: records are keyed there
 * by their resource id, which the backend returns as a SHA-256 hash of the
 * address, and the plaintext `ip` attribute is only serialized for actors with
 * the `discussion.viewIpsPosts` permission. So `getBy('ip_info', 'ip', …)`
 * never matches for anyone else, and hashing the address here to use
 * `getById` would mean an async digest inside a synchronous `oninit`.
 *
 * Entries are kept after resolution rather than dropped in `.finally()`, so a
 * repeatedly re-mounted IPAddress (the audit log's "Load more", the post
 * stream) resolves from cache instead of re-requesting. That unbounded
 * re-requesting is what tripped Flarum's rate limiter — see issue #111.
 */
const ipInfoLookups = new Map<string, { promise: Promise<IPInfo | null>; record?: IPInfo | null }>();

/**
 * Failed lookups are cached too, so a 404/429 is not retried on every redraw.
 * Cleared on a timer so a transient failure is eventually retryable.
 */
const FAILED_LOOKUP_TTL = 60 * 1000;

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
        <Tooltip text={app.translator.trans('fof-geoip.lib.copy_ip_label')}>
          <Button
            icon="fas fa-copy"
            className="Button Button--icon Button--link"
            onclick={handleCopyIP(this.ip)}
            aria-label={app.translator.trans('fof-geoip.lib.copy_ip_label')}
          />
        </Tooltip>,
        95
      );

      items.add(
        'infoButton',
        <Tooltip text={app.translator.trans('fof-geoip.lib.map_button_label')}>
          <Button
            icon="fas fa-info-circle"
            className="Button Button--icon Button--link"
            onclick={(e: Event) => {
              e.stopPropagation();
              app.modal.show(() => import('../components/MapModal'), { ipInfo: this.ipInfo, ipAddr: this.ip });
            }}
            aria-label={app.translator.trans('fof-geoip.lib.map_button_label')}
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
    // Populate synchronously from an already-resolved lookup, so a re-mounted
    // component renders its flag immediately and never re-requests.
    this.ipInfo = ipInfoLookups.get(this.ip)?.record ?? undefined;

    this.loadIpInfo = async function () {
      if (this.ip.length === 0) return;

      const ip = this.ip;
      let lookup = ipInfoLookups.get(ip);

      if (!lookup) {
        const entry: { promise: Promise<IPInfo | null>; record?: IPInfo | null } = {
          promise: app.store
            .find<IPInfo>('ip_info', encodeURIComponent(ip))
            .then((record) => {
              entry.record = record;
              return record;
            })
            .catch((error) => {
              console.error('Failed to load IP info:', error);

              // Drop the entry after a delay so a transient failure (a 429 from
              // the rate limiter, say) can be retried, while an immediate redraw
              // storm still hits the cache rather than the API.
              setTimeout(() => ipInfoLookups.delete(ip), FAILED_LOOKUP_TTL);

              return null;
            }),
        };

        lookup = entry;
        ipInfoLookups.set(ip, entry);
      }

      const record = lookup.record !== undefined ? lookup.record : await lookup.promise;

      if (record === this.ipInfo) return;

      this.ipInfo = record ?? undefined;

      // Always redraw, including on a cache hit. Rows sharing an IP that an
      // earlier row already resolved reach this point with the record in hand
      // but with their own view already painted as the bare IP — several such
      // rows are the norm in an audit log. Returning early here leaves them
      // un-enriched for good (issue #111).
      m.redraw();
    };
  });
}
