import GeoipSettingsModal from './components/ExtensionSettingsModal';

app.initializers.add('fof/geoip', () => {
    app.extensionSettings['fof-geoip'] = () => app.modal.show(GeoipSettingsModal);
});
