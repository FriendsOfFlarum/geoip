import Alert from 'flarum/components/Alert';
import SettingsModal from 'flarum/components/SettingsModal';
import humanTime from 'flarum/helpers/humanTime';
import extractText from 'flarum/utils/extractText';

import SelectItem from '@fof/components/admin/settings/items/SelectItem';
import StringItem from '@fof/components/admin/settings/items/StringItem';
import BooleanItem from '@fof/components/admin/settings/items/BooleanItem';

import linkify from 'linkify-lite';

export default class GeoipSettingsModal extends SettingsModal {
    className() {
        return 'Modal--medium';
    }

    title() {
        return app.translator.trans('fof-geoip.admin.settings.title');
    }

    form() {
        const service = this.setting('fof-geoip.service')();
        const errorTime = Number(app.data.settings[`fof-geoip.services.${service}.last_error_time`]) * 1000;
        let error = app.data.settings[`fof-geoip.services.${service}.error`];

        if (error) error = linkify(error);

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
                <p className="helpText">
                    {service && m.trust(linkify(extractText(app.translator.trans(`fof-geoip.admin.settings.service_${service}_description`))))}
                </p>
            </div>,

            error
                ? Alert.component({
                      children: [<b style={{ textTransform: 'uppercase', marginRight: '5px' }}>{humanTime(errorTime)}</b>, m.trust(error)],
                      className: 'Form-group',
                      dismissible: false,
                  })
                : '',

            ['ipdata', 'ipstack'].includes(service)
                ? [
                      <StringItem key={`fof-geoip.services.${service}.access_key`} required>
                          {app.translator.trans('fof-geoip.admin.settings.access_key_label')}
                      </StringItem>,
                  ]
                : [],

            service === 'ipstack' ? (
                <div className="Form-group">
                    <BooleanItem key={`fof-geoip.services.ipstack.security`}>
                        {app.translator.trans('fof-geoip.admin.settings.security_label')}
                    </BooleanItem>
                </div>
            ) : (
                []
            ),
        ];
    }
}
