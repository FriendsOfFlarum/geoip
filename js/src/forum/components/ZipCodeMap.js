import Component from 'flarum/Component';
import LoadingIndicator from 'flarum/components/LoadingIndicator';
import load from 'load.js';

let addedResources = false;
const addResources = async () => {
    if (addedResources) return;

    await load.css('https://unpkg.com/leaflet@1.5.1/dist/leaflet.css');
    await load.js('https://unpkg.com/leaflet@1.5.1/dist/leaflet.js');

    addedResources = true;
};

export default class ZipCodeMap extends Component {
    constructor() {
        super(...arguments);

        this.data = null;

        this.search();
    }

    view() {
        if (this.loading) {
            return <LoadingIndicator size="medium" />;
        } else if (!this.data) {
            return <div />;
        }

        return <div id="geoip-map" config={this.configMap.bind(this)} />;
    }

    search() {
        if (this.loading) return;

        this.loading = true;

        return addResources().then(
            app
                .request({
                    url: `https://nominatim.openstreetmap.org/search`,
                    method: 'GET',
                    data: {
                        q: this.props.zip,
                        countrycodes: this.props.country,
                        limit: 1,
                        format: 'json',
                    },
                })
                .then(data => {
                    this.data = data[0];
                    this.loading = false;

                    m.redraw();
                })
        );
    }

    configMap(el, isInitialized) {
        if (isInitialized) return;
        if (!this.data) return;

        const { boundingbox: bounding, display_name: displayName } = this.data;

        this.map = L.map(el).setView([51.505, -0.09], 5);

        L.control.scale().addTo(this.map);

        L.tileLayer(`https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}{r}.png?lang=${app.data.locale}`, {
            attribution: '<a href="https://wikimediafoundation.org/wiki/Maps_Terms_of_Use">Wikimedia</a>',
            minZoom: 1,
            maxZoom: 19,
        }).addTo(this.map);

        L.marker([bounding[0], bounding[2]])
            .addTo(this.map)
            .bindPopup(displayName)
            .openPopup();
    }
}
