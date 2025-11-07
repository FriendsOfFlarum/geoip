import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import type Mithril from 'mithril';
import ItemList from 'flarum/common/utils/ItemList';
export default class GeoipSettingsPage extends ExtensionPage {
    content(): JSX.Element;
    settingsItems(): ItemList<Mithril.Children>;
    generalItems(): ItemList<Mithril.Children>;
    providerItems(): ItemList<Mithril.Children>;
}
