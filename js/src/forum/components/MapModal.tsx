import app from 'flarum/forum/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import ZipCodeMap from './ZipCodeMap';
import IPInfo from '../models/IPInfo';
import { handleCopyIP } from '../helpers/ClipboardHelper';
import LabelValue from 'flarum/common/components/LabelValue';
import type Mithril from 'mithril';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import ItemList from 'flarum/common/utils/ItemList';

interface MapModalAttrs extends IInternalModalAttrs {
  ipInfo?: IPInfo;
  ipAddr: string;
}

export default class MapModal extends Modal<MapModalAttrs> {
  ipInfo: IPInfo | undefined;
  ipAddr!: string;

  oninit(vnode: Mithril.Vnode<MapModalAttrs, this>) {
    super.oninit(vnode);
    this.ipInfo = this.attrs.ipInfo;
    this.ipAddr = this.attrs.ipAddr;
  }

  className() {
    return 'MapModal Modal--medium';
  }

  title() {
    return app.translator.trans('fof-geoip.forum.map_modal.title');
  }

  content() {
    const ipInfo = this.ipInfo;

    if (!ipInfo) {
      return (
        <div className="Modal-body">
          <LoadingIndicator />
        </div>
      );
    }

    return (
      <div className="Modal-body">
        <div className="IPDetails">
          {this.dataItems().toArray()}

          {ipInfo.threatLevel() && <LabelValue label={app.translator.trans('fof-geoip.forum.map_modal.threat_level')} value={ipInfo.threatLevel()} />}
          {ipInfo.threatTypes().length > 0 && (
            <LabelValue label={app.translator.trans('fof-geoip.forum.map_modal.threat_types')} value={ipInfo.threatTypes().join(', ')} />
          )}
          {ipInfo.error() && <LabelValue label={app.translator.trans('fof-geoip.forum.map_modal.error')} value={ipInfo.error()} />}
        </div>
        <hr />
        <div className="IPDetails--map">{this.mapItems().toArray()}</div>
      </div>
    );
  }

  dataItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'ipAddress',
      <LabelValue
        label={app.translator.trans('fof-geoip.forum.map_modal.ip_address')}
        value={
          <span className="clickable-ip" onclick={handleCopyIP(this.ipAddr)}>
            {this.ipAddr}
          </span>
        }
      />,
      100
    );

    if (this.ipInfo) {
      this.ipInfo.countryCode?.() &&
        items.add(
          'countryCode',
          <LabelValue label={app.translator.trans('fof-geoip.forum.map_modal.country_code')} value={this.ipInfo.countryCode()} />,
          90
        );

      this.ipInfo.zipCode?.() &&
        items.add('zipCode', <LabelValue label={app.translator.trans('fof-geoip.forum.map_modal.zip_code')} value={this.ipInfo.zipCode()} />, 80);

      this.ipInfo.isp?.() &&
        items.add('isp', <LabelValue label={app.translator.trans('fof-geoip.forum.map_modal.isp')} value={this.ipInfo.isp()} />, 70);

      this.ipInfo.organization?.() &&
        items.add(
          'organization',
          <LabelValue label={app.translator.trans('fof-geoip.forum.map_modal.organization')} value={this.ipInfo.organization()} />,
          60
        );

      this.ipInfo.as?.() && items.add('as', <LabelValue label={app.translator.trans('fof-geoip.forum.map_modal.as')} value={this.ipInfo.as()} />, 50);

      items.add(
        'mobileNetwork',
        <LabelValue label={app.translator.trans('fof-geoip.forum.map_modal.mobile')} value={this.ipInfo.mobile() ? 'yes' : 'no'} />,
        40
      );
    }

    return items;
  }

  mapItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('mapContainer', <ZipCodeMap id="mapContainer" ipInfo={this.ipInfo} />, 100);

    return items;
  }
}
