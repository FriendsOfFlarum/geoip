import GeoipSettingsPage from './components/ExtensionSettingsPage';

app.initializers.add('fof/geoip', () => {
    app.extensionData.for('fof-geoip').registerPage(GeoipSettingsPage);
});
