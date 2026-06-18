import app from 'flarum/common/app';
import leaflet from 'leaflet';
import 'leaflet/dist/leaflet.css';

leaflet.Icon.Default.imagePath = `${app.forum.attribute('baseUrl')}/assets/extensions/fof-geoip/`;

export default leaflet;
