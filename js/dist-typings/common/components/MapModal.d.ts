/// <reference types="flarum/@types/translator-icu-rich" />
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import IPInfo from '../models/IPInfo';
import type Mithril from 'mithril';
import ItemList from 'flarum/common/utils/ItemList';
interface MapModalAttrs extends IInternalModalAttrs {
    ipInfo?: IPInfo;
    ipAddr: string;
}
export default class MapModal extends Modal<MapModalAttrs> {
    ipInfo: IPInfo | undefined;
    ipAddr: string;
    oninit(vnode: Mithril.Vnode<MapModalAttrs, this>): void;
    className(): string;
    title(): import("@askvortsov/rich-icu-message-formatter").NestedStringArray;
    content(): JSX.Element;
    dataItems(): ItemList<Mithril.Children>;
    mapItems(): ItemList<Mithril.Children>;
}
export {};
