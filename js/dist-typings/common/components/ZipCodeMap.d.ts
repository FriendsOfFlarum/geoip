import Component, { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import IPInfo from '../model/IPInfo';
export interface ZipCodeMapAttrs extends ComponentAttrs {
    ipInfo: IPInfo;
}
export default class ZipCodeMap extends Component<ZipCodeMapAttrs> {
    ipInfo: IPInfo;
    loading: boolean;
    map: any;
    data: any;
    addedResources: boolean;
    oninit(vnode: Mithril.Vnode<ZipCodeMapAttrs, this>): void;
    addResources(): Promise<void>;
    view(): JSX.Element;
    searchLatLon(): Promise<void>;
    searchZip(): Promise<void>;
    configMap(vnode: Mithril.VnodeDOM): void;
}
