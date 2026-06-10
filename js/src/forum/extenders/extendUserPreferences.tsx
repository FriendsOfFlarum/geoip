import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import SettingsPage from 'flarum/forum/components/SettingsPage';
import Switch from 'flarum/common/components/Switch';
import Select from 'flarum/common/components/Select';
import extractText from 'flarum/common/utils/extractText';
import countryOptions from '../../common/helpers/countryOptions';

export default function extendUserPreferences() {
  extend(SettingsPage.prototype, 'privacyItems', function (items) {
    if (app.forum.attribute<boolean>('fof-geoip.showFlag')) {
      items.add(
        'ip-country',
        Switch.component(
          {
            state: this.user.preferences().showIPCountry,
            onchange: (value) => {
              this.showIPCountryLoading = true;

              this.user.savePreferences({ showIPCountry: value }).then(() => {
                this.showIPCountryLoading = false;
                m.redraw();
              });
            },
            loading: this.showIPCountryLoading,
          },
          app.translator.trans('fof-geoip.forum.user.settings.ip_country')
        )
      );
    }

    if (app.forum.attribute<boolean>('fof-geoip.allowCustomFlag')) {
      const none = extractText(app.translator.trans('fof-geoip.lib.custom_flag.none'));

      items.add(
        'custom-flag',
        <div className="Form-group">
          <label>{app.translator.trans('fof-geoip.forum.user.settings.custom_flag_label')}</label>
          <p className="helpText">{app.translator.trans('fof-geoip.forum.user.settings.custom_flag_help')}</p>
          {Select.component({
            value: this.user.preferences().customFlagCountry || '',
            disabled: this.customFlagLoading,
            options: { '': none, ...countryOptions() },
            onchange: (value: string) => {
              this.customFlagLoading = true;

              this.user.savePreferences({ customFlagCountry: value || null }).then(() => {
                this.customFlagLoading = false;
                m.redraw();
              });
            },
          })}
        </div>
      );
    }
  });
}
