import app from 'flarum/common/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import ZipCodeMap from './ZipCodeMap';
import IPInfo from '../model/IPInfo';
import { handleCopyIP } from '../helpers/ClipboardHelper';
import type Mithril from 'mithril';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import ItemList from 'flarum/common/utils/ItemList';
import LabelValue from 'flarum/common/components/LabelValue';
import getCountryName from '../util/getCountryName';
import isDbIpProvider from '../util/isDbIpProvider';

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
    return app.translator.trans('fof-geoip.lib.map_modal.title');
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

          {ipInfo.threatLevel() && <LabelValue label={app.translator.trans('fof-geoip.lib.map_modal.threat_level')} value={ipInfo.threatLevel()} />}
          {ipInfo.threatTypes().length > 0 && (
            <LabelValue label={app.translator.trans('fof-geoip.lib.map_modal.threat_types')} value={ipInfo.threatTypes().join(', ')} />
          )}
          {ipInfo.error() && <LabelValue label={app.translator.trans('fof-geoip.lib.map_modal.error')} value={ipInfo.error()} />}
        </div>
        <hr />
        <div className="IPDetails--map">{this.mapItems().toArray()}</div>

        {/*
          DB-IP's databases are CC BY 4.0, which requires visible attribution
          wherever results derived from them are displayed. Keyed off the
          record's own dataProvider rather than the currently configured
          service, so a record looked up under a different provider is still
          credited correctly.
        */}
        {isDbIpProvider(ipInfo.dataProvider?.()) && (
          <p className="IPDetails-attribution">
            {app.translator.trans('fof-geoip.lib.map_modal.dbip_attribution', {
              a: <a href="https://db-ip.com" target="_blank" rel="noopener noreferrer" />,
            })}
          </p>
        )}
      </div>
    );
  }

  dataItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'ipAddress',
      <LabelValue
        label={app.translator.trans('fof-geoip.lib.map_modal.ip_address')}
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
          <LabelValue
            label={app.translator.trans('fof-geoip.lib.map_modal.country')}
            // Only the code is stored; the name is resolved in the viewer's
            // locale rather than showing a bare "DE".
            value={getCountryName(this.ipInfo.countryCode())}
          />,
          90
        );

      // Shown above the postal code: a place name is more useful at a glance
      // than a code, and not every service supplies both.
      this.ipInfo.city?.() &&
        items.add('city', <LabelValue label={app.translator.trans('fof-geoip.lib.map_modal.city')} value={this.ipInfo.city()} />, 85);

      this.ipInfo.region?.() &&
        items.add('region', <LabelValue label={app.translator.trans('fof-geoip.lib.map_modal.region')} value={this.ipInfo.region()} />, 84);

      this.ipInfo.zipCode?.() &&
        items.add('zipCode', <LabelValue label={app.translator.trans('fof-geoip.lib.map_modal.zip_code')} value={this.ipInfo.zipCode()} />, 80);

      this.ipInfo.isp?.() &&
        items.add('isp', <LabelValue label={app.translator.trans('fof-geoip.lib.map_modal.isp')} value={this.ipInfo.isp()} />, 70);

      this.ipInfo.organization?.() &&
        items.add(
          'organization',
          <LabelValue label={app.translator.trans('fof-geoip.lib.map_modal.organization')} value={this.ipInfo.organization()} />,
          60
        );

      this.ipInfo.as?.() && items.add('as', <LabelValue label={app.translator.trans('fof-geoip.lib.map_modal.as')} value={this.ipInfo.as()} />, 50);

      // Only shown when the service actually knows. Offline databases carry
      // no connection-type data, and rendering "no" for them would assert the
      // address is definitely not mobile rather than that it is unknown.
      this.ipInfo.mobile?.() !== null &&
        this.ipInfo.mobile?.() !== undefined &&
        items.add(
          'mobileNetwork',
          <LabelValue label={app.translator.trans('fof-geoip.lib.map_modal.mobile')} value={this.ipInfo.mobile() ? 'yes' : 'no'} />,
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
