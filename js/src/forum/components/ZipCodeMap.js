import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import load from 'external-load';

let addedResources = false;
const addResources = async () => {
  if (addedResources) return;

  await load.css('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
  await load.js('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js');

  addedResources = true;
};

export default class ZipCodeMap extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.ipInfo = this.attrs.ipInfo;

    this.data = null;

    // if (this.ipInfo.zipCode()) {
    //   this.searchZip();
    // } else {
    //   this.searchLatLon();
    // }
    this.searchLatLon();
  }

  view() {
    if (this.loading) {
      return <LoadingIndicator size="medium" />;
    } else if (!this.data) {
      return <div />;
    }

    return <div id="geoip-map" oncreate={this.configMap.bind(this)} />;
  }

  searchZip() {
    if (this.loading) return;

    this.loading = true;

    return addResources().then(
      app
        .request({
          url: `https://nominatim.openstreetmap.org/search`,
          method: 'GET',
          params: {
            q: this.inInfo.zipCode(),
            countrycodes: this.inInfo.country(),
            limit: 1,
            format: 'json',
          },
        })
        .then((data) => {
          this.data = data[0];
          this.loading = false;

          m.redraw();
        })
    );
  }

  searchLatLon() {
    if (this.loading) return;

    this.loading = true;

    return addResources().then(
      app
        .request({
          url: `https://nominatim.openstreetmap.org/reverse`,
          method: 'GET',
          params: {
            lat: this.ipInfo.latitude(),
            lon: this.ipInfo.longitude(),
            format: 'json',
          },
        })
        .then((data) => {
          this.data = data;
          this.loading = false;

          m.redraw();
        })
    );
  }

  configMap(vnode) {
    if (!this.data) return;

    const { boundingbox: bounding, display_name: displayName } = this.data;

    this.map = L.map(vnode.dom).setView([51.505, -0.09], 5);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    }).addTo(this.map);

    L.marker([bounding[0], bounding[2]]).addTo(this.map).bindPopup(displayName).openPopup();
  }
}
