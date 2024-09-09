import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import SettingsPage from 'flarum/forum/components/SettingsPage';
import Switch from 'flarum/common/components/Switch';

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
  });
}
