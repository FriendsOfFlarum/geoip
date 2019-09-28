import SettingsModal from 'flarum/components/SettingsModal';
import SelectItem from '@fof/components/admin/settings/items/SelectItem';
import StringItem from '@fof/components/admin/settings/items/StringItem';
import BooleanItem from '@fof/components/admin/settings/items/BooleanItem';

export default class GeoipSettingsModal extends SettingsModal {
    className() {
        return 'Modal--medium';
    }

    title() {
        return app.translator.trans('fof-geoip.admin.settings.title');
    }

    form() {
        const service = this.setting('fof-geoip.service')();

        return [
            <div className="Form-group">
                <label>{app.translator.trans('fof-geoip.admin.settings.service_label')}</label>

                {SelectItem.component({
                    options: app.data['fof-geoip.services'].reduce((o, p) => {
                        o[p] = app.translator.trans(`fof-geoip.admin.settings.service_${p}_label`);

                        return o;
                    }, {}),
                    key: 'fof-geoip.service',
                    required: true,
                })}

                <br />
                <br />
                <p className="helpText">{app.translator.trans(`fof-geoip.admin.settings.service_${service}_description`)}</p>
            </div>,

            ['ipdata', 'ipstack'].includes(service)
                ? [
                      <StringItem key={`fof-geoip.services.${service}.access_key`} required>
                          {app.translator.trans('fof-geoip.admin.settings.access_key_label')}
                      </StringItem>,
                  ]
                : [],

            service === 'ipstack'
                ? <div className="Form-group">
                      <BooleanItem key={`fof-geoip.services.ipstack.security`}>
                          {app.translator.trans('fof-geoip.admin.settings.security_label')}
                      </BooleanItem>
                  </div>
                : [],
        ];
    }
}
