import Component, { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import IPInfo from '../model/IPInfo';
import L from 'leaflet';
export interface ZipCodeMapAttrs extends ComponentAttrs {
    ipInfo: IPInfo;
}
export default class ZipCodeMap extends Component<ZipCodeMapAttrs> {
    ipInfo: IPInfo;
    loading: boolean;
    map: L.Map | null;
    data: NominatimResult | {
        unknown: true;
    } | null;
    oninit(vnode: Mithril.Vnode<ZipCodeMapAttrs, this>): void;
    view(): JSX.Element;
    searchLatLon(): Promise<void>;
    searchZip(): Promise<void>;
    configMap(vnode: Mithril.VnodeDOM): void;
    onremove(): void;
}
interface NominatimResult {
    boundingbox: [string, string, string, string];
    display_name: string;
}
export {};
