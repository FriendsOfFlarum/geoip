import app from 'flarum/common/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import type Mithril from 'mithril';
import IPInfo from '../model/IPInfo';
import L from 'leaflet';

// Fix default marker icon paths - Leaflet's default paths break when bundled.
// Use extension assets (published to assets/extensions/fof-geoip/).
let leafletIconsConfigured = false;
function configureLeafletMarkerIcons(): void {
  if (leafletIconsConfigured) return;
  const urls = (app.forum.attribute('fofGeoipLeafletMarkerUrls') as { iconUrl?: string; iconRetinaUrl?: string; shadowUrl?: string }) || {};
  if (urls.iconUrl) {
    delete (L.Icon.Default.prototype as any)._getIconUrl;
    L.Icon.Default.mergeOptions({
      iconUrl: urls.iconUrl,
      iconRetinaUrl: urls.iconRetinaUrl || urls.iconUrl,
      shadowUrl: urls.shadowUrl || '',
    });
    leafletIconsConfigured = true;
  }
}

export interface ZipCodeMapAttrs extends ComponentAttrs {
  ipInfo: IPInfo;
}

export default class ZipCodeMap extends Component<ZipCodeMapAttrs> {
  ipInfo!: IPInfo;
  loading = false;
  map: L.Map | null = null;
  data: NominatimResult | { unknown: true } | null = null;

  oninit(vnode: Mithril.Vnode<ZipCodeMapAttrs, this>) {
    super.oninit(vnode);

    this.ipInfo = this.attrs.ipInfo;
    this.data = null;

    if (this.ipInfo.latitude() && this.ipInfo.longitude()) {
      this.searchLatLon();
    } else if (this.ipInfo.zipCode()) {
      this.searchZip();
    } else {
      this.data = { unknown: true };
    }
  }

  view() {
    if (this.loading) {
      return <LoadingIndicator size="medium" />;
    } else if (this.data && 'unknown' in this.data) {
      return <div className="helpText">{app.translator.trans('fof-geoip.forum.map_modal.not_enough_data')}</div>;
    } else if (!this.data) {
      return <div />;
    }

    return <div id="geoip-map" oncreate={this.configMap.bind(this)} />;
  }

  async searchLatLon() {
    if (this.loading) return;

    this.loading = true;

    const data = await app.request<NominatimResult>({
      url: 'https://nominatim.openstreetmap.org/reverse',
      method: 'GET',
      params: {
        lat: this.ipInfo.latitude(),
        lon: this.ipInfo.longitude(),
        format: 'json',
      },
    });

    this.data = data;
    this.loading = false;

    m.redraw();
  }

  async searchZip() {
    if (this.loading) return;

    this.loading = true;

    const data = await app.request<NominatimResult[]>({
      url: 'https://nominatim.openstreetmap.org/search',
      method: 'GET',
      params: {
        q: this.ipInfo.zipCode(),
        countrycodes: this.ipInfo.countryCode(),
        limit: 1,
        format: 'json',
      },
    });

    this.data = data[0] ?? null;
    this.loading = false;

    m.redraw();
  }

  configMap(vnode: Mithril.VnodeDOM) {
    if (!this.data || 'unknown' in this.data) return;

    configureLeafletMarkerIcons();

    const { boundingbox: bounding, display_name: displayName } = this.data;

    const lat1 = parseFloat(bounding[0]);
    const lat2 = parseFloat(bounding[1]);
    const lon1 = parseFloat(bounding[2]);
    const lon2 = parseFloat(bounding[3]);

    const centerLat = (lat1 + lat2) / 2;
    const centerLon = (lon1 + lon2) / 2;
    const zoomLevel = 5;

    this.map = L.map(vnode.dom as HTMLElement).setView([centerLat, centerLon], zoomLevel);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(this.map);

    // Use textContent to prevent XSS from Nominatim's display_name
    const popupContent = document.createElement('span');
    popupContent.textContent = displayName;
    L.marker([centerLat, centerLon]).addTo(this.map).bindPopup(popupContent).openPopup();
  }

  onremove() {
    if (this.map) {
      this.map.remove();
      this.map = null;
    }
  }
}

interface NominatimResult {
  boundingbox: [string, string, string, string];
  display_name: string;
}
