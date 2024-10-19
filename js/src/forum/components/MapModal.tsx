import app from 'flarum/forum/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import ZipCodeMap from './ZipCodeMap';
import IPInfo from '../models/IPInfo';
import { handleCopyIP } from '../helpers/ClipboardHelper';
import LabelValue from 'flarum/common/components/LabelValue';
import type Mithril from 'mithril';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';

interface MapModalAttrs extends IInternalModalAttrs {
  ipInfo?: IPInfo;
  ipAddr: string;
}

export default class MapModal extends Modal<MapModalAttrs> {
  ipInfo: IPInfo | undefined;

  oninit(vnode: Mithril.Vnode<MapModalAttrs, this>) {
    super.oninit(vnode);
    this.ipInfo = this.attrs.ipInfo;
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
          <LabelValue
            label={app.translator.trans('fof-geoip.forum.map_modal.ip_address')}
            value={
              <span className="clickable-ip" onclick={handleCopyIP(this.attrs.ipAddr)}>
                {this.attrs.ipAddr}
              </span>
            }
          />
          {ipInfo.countryCode() && <LabelValue label={app.translator.trans('fof-geoip.forum.map_modal.country_code')} value={ipInfo.countryCode()} />}
          {ipInfo.zipCode() && <LabelValue label={app.translator.trans('fof-geoip.forum.map_modal.zip_code')} value={ipInfo.zipCode()} />}
          {ipInfo.isp() && <LabelValue label={app.translator.trans('fof-geoip.forum.map_modal.isp')} value={ipInfo.isp()} />}
          {ipInfo.organization() && (
            <LabelValue label={app.translator.trans('fof-geoip.forum.map_modal.organization')} value={ipInfo.organization()} />
          )}
          {ipInfo.as() && <LabelValue label={app.translator.trans('fof-geoip.forum.map_modal.as')} value={ipInfo.as()} />}
          {<LabelValue label={app.translator.trans('fof-geoip.forum.map_modal.mobile')} value={ipInfo.mobile() ? 'yes' : 'no'} />}
          {ipInfo.threatLevel() && <LabelValue label={app.translator.trans('fof-geoip.forum.map_modal.threat_level')} value={ipInfo.threatLevel()} />}
          {ipInfo.threatTypes().length > 0 && (
            <LabelValue label={app.translator.trans('fof-geoip.forum.map_modal.threat_types')} value={ipInfo.threatTypes().join(', ')} />
          )}
          {ipInfo.error() && <LabelValue label={app.translator.trans('fof-geoip.forum.map_modal.error')} value={ipInfo.error()} />}
        </div>
        <hr />
        <div id="mapContainer">
          <ZipCodeMap ipInfo={ipInfo} />
        </div>
      </div>
    );
  }
}
