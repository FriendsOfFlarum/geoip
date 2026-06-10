import Component, { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import IPInfo from '../models/IPInfo';
import L from 'leaflet';
export interface ZipCodeMapAttrs extends ComponentAttrs {
    ipInfo: IPInfo;
}
export default class ZipCodeMap extends Component<ZipCodeMapAttrs> {
    ipInfo: IPInfo;
    loading: boolean;
    map: L.Map | null;
    data: any;
    oninit(vnode: Mithril.Vnode<ZipCodeMapAttrs, this>): void;
    view(): JSX.Element;
    searchLatLon(): Promise<void>;
    searchZip(): Promise<void>;
    configMap(vnode: Mithril.VnodeDOM): void;
    onremove(): void;
}
