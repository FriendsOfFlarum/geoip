import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Switch from 'flarum/common/components/Switch';
import CountryFlagPicker from '../../common/components/CountryFlagPicker';

export default function extendUserPreferences() {
  extend('flarum/forum/components/SettingsPage', 'privacyItems', function (items) {
    if (app.forum.attribute<boolean>('fof-geoip.showFlag')) {
      items.add(
        'ip-country',
        <Switch
          state={this.user.preferences().showIPCountry}
          onchange={(checked: boolean) => {
            this.showIPCountryLoading = true;

            this.user.savePreferences({ showIPCountry: checked }).then(() => {
              this.showIPCountryLoading = false;
              m.redraw();
            });
          }}
          loading={this.showIPCountryLoading}
        >
          {app.translator.trans('fof-geoip.forum.user.settings.ip_country')}
        </Switch>
      );
    }

    if (app.forum.attribute<boolean>('fof-geoip.allowCustomFlag')) {
      items.add(
        'custom-flag',
        <div className="Form-group">
          <label>{app.translator.trans('fof-geoip.forum.user.settings.custom_flag_label')}</label>
          <p className="helpText">{app.translator.trans('fof-geoip.forum.user.settings.custom_flag_help')}</p>
          <CountryFlagPicker
            value={this.user.preferences().customFlagCountry}
            disabled={this.customFlagLoading}
            onchange={(code: string | null) => {
              this.customFlagLoading = true;

              this.user.savePreferences({ customFlagCountry: code }).then(() => {
                this.customFlagLoading = false;
                m.redraw();
              });
            }}
          />
        </div>
      );
    }
  });
}
