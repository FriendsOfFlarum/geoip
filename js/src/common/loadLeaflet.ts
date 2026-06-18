import app from 'flarum/common/app';

export default async function loadLeaflet() {
  __webpack_public_path__ = `${app.forum.attribute('baseUrl')}/assets/extensions/fof-geoip/`;

  const [, L] = await Promise.all([import('leaflet/dist/leaflet.css'), import('leaflet')]);

  L.Icon.Default.imagePath = `${app.forum.attribute('baseUrl')}/assets/extensions/fof-geoip/`;
  return L;
}
