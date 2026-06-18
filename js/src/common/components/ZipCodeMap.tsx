import app from 'flarum/common/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import type Mithril from 'mithril';
import IPInfo from '../models/IPInfo';
import loadLeaflet from '../loadLeaflet';

type LeafletMap = import('leaflet').Map;

export interface ZipCodeMapAttrs extends ComponentAttrs {
  ipInfo: IPInfo;
}

export default class ZipCodeMap extends Component<ZipCodeMapAttrs> {
  ipInfo!: IPInfo;
  loading = false;
  map: LeafletMap | null = null;
  data: any;

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
    } else if (this.data && this.data.unknown) {
      return <div className="helpText">{app.translator.trans('fof-geoip.lib.map_modal.not_enough_data')}</div>;
    } else if (!this.data) {
      return <div />;
    }

    return <div id="geoip-map" oncreate={this.configMap.bind(this)} />;
  }

  async searchLatLon() {
    if (this.loading) return;

    this.loading = true;

    const data = await app.request<any>({
      url: `https://nominatim.openstreetmap.org/reverse`,
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

    const data = await app.request<any>({
      url: `https://nominatim.openstreetmap.org/search`,
      method: 'GET',
      params: {
        q: this.ipInfo.zipCode(),
        countrycodes: this.ipInfo.countryCode(),
        limit: 1,
        format: 'json',
      },
    });

    this.data = data[0];
    this.loading = false;

    m.redraw();
  }

  async configMap(vnode: Mithril.VnodeDOM) {
    if (!this.data || this.map) return;

    const L = await loadLeaflet();

    const { boundingbox: bounding, display_name: displayName } = this.data;

    // Extract the latitude and longitude from the bounding box
    const lat1 = parseFloat(bounding[0]); // South bound latitude
    const lat2 = parseFloat(bounding[1]); // North bound latitude
    const lon1 = parseFloat(bounding[2]); // West bound longitude
    const lon2 = parseFloat(bounding[3]); // East bound longitude

    // Calculate the center of the bounding box
    const centerLat = (lat1 + lat2) / 2;
    const centerLon = (lon1 + lon2) / 2;

    const zoomLevel = 5; // Set your preferred zoom level here

    this.map = L.map(vnode.dom as HTMLElement).setView([centerLat, centerLon], zoomLevel);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(this.map);

    // Set a marker at the center of the bounding box
    L.marker([centerLat, centerLon]).addTo(this.map).bindPopup(displayName).openPopup();
  }

  onremove() {
    if (this.map) {
      this.map.remove();
      this.map = null;
    }
  }
}
