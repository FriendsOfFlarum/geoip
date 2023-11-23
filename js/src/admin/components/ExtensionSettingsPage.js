import app from 'flarum/admin/app';
import Alert from 'flarum/common/components/Alert';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import humanTime from 'flarum/common/helpers/humanTime';
import extractText from 'flarum/common/utils/extractText';
import linkify from 'linkify-lite';

export default class GeoipSettingsPage extends ExtensionPage {
  oninit(vnode) {
    super.oninit(vnode);
  }

  content() {
    const service = this.setting('fof-geoip.service')();
    const errorTime = Number(app.data.settings[`fof-geoip.services.${service}.last_error_time`]) * 1000;
    let error = app.data.settings[`fof-geoip.services.${service}.error`];

    if (error) error = linkify(error);

    return [
      <div className="container">
        <div className="geopage">
          <div className="Form-group">
            {this.buildSettingComponent({
              type: 'select',
              setting: 'fof-geoip.service',
              label: app.translator.trans('fof-geoip.admin.settings.service_label'),
              options: app.data['fof-geoip.services'].reduce((o, p) => {
                o[p] = app.translator.trans(`fof-geoip.admin.settings.service_${p}_label`);

                return o;
              }, {}),
              required: true,
              help: service && m.trust(linkify(extractText(app.translator.trans(`fof-geoip.admin.settings.service_${service}_description`)))),
            })}
          </div>
          {error
            ? Alert.component(
                {
                  className: 'Form-group',
                  dismissible: false,
                },
                [<b style={{ textTransform: 'uppercase', marginRight: '5px' }}>{humanTime(errorTime)}</b>, m.trust(error)]
              )
            : ''}

          {['ipdata', 'ipapi-pro'].includes(service)
            ? [
                this.buildSettingComponent({
                  type: 'string',
                  setting: `fof-geoip.services.${service}.access_key`,
                  label: app.translator.trans('fof-geoip.admin.settings.access_key_label'),
                  required: true,
                }),
              ]
            : []}
          {service === 'ipdata'
            ? this.buildSettingComponent({
                type: 'number',
                setting: 'fof-geoip.services.ipdata.quota',
                label: app.translator.trans('fof-geoip.admin.settings.quota_label'),
                min: 1500,
                placeholder: 1500,
              })
            : []}
          {this.submitButton()}
        </div>
      </div>,
    ];
  }
}
