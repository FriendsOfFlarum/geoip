import Component from 'flarum/common/Component';
import ItemList from 'flarum/common/utils/ItemList';
import Mithril from 'mithril';
interface GeoipTestComponentAttrs {
}
export default class GeoipTestComponent extends Component<GeoipTestComponentAttrs> {
    private testIP;
    private testResult;
    private testLoading;
    private testError;
    view(): JSX.Element;
    testItems(): ItemList<Mithril.Children>;
    testService(): Promise<void>;
}
export {};
