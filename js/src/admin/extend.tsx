import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';
import Alert from 'flarum/common/components/Alert';
import Component from 'flarum/common/Component';
import LinkButton from 'flarum/common/components/LinkButton';
import humanTime from 'flarum/common/helpers/humanTime';
import extractText from 'flarum/common/utils/extractText';
import type AdminPage from 'flarum/admin/components/AdminPage';
import type Mithril from 'mithril';

import { default as commonExtend } from '../common/extend';
import GeoipTestComponent from './components/GeoipTestComponent';

const KEYED_SERVICES = ['ipdata', 'ipapi-pro', 'ipsevenex', 'ipinfo-lite'];

/**
 * Renders the `<b>…</b>` placeholder in a service description as an external
 * link to the URL it wraps. The URL is the tag's own text content (injected as
 * children by the translator), so we read it from the children rather than
 * threading it through separately.
 */
class ServiceLink extends Component {
  view(vnode: Mithril.Vnode) {
    const href = extractText(vnode.children);

    return (
      <LinkButton href={href} external={true} target="_blank" rel="noopener noreferrer" className="">
        {vnode.children}
      </LinkButton>
    );
  }
}

export default [
  ...commonExtend,

  new Extend.Admin() //
    // An error from the currently-selected service, surfaced above the settings.
    .customSetting(function (this: AdminPage) {
      const service = this.setting('fof-geoip.service')();
      const error = app.data.settings[`fof-geoip.services.${service}.error`] as string | undefined;

      if (!error) return null;

      const errorTime = Number(app.data.settings[`fof-geoip.services.${service}.last_error_time`]) * 1000;

      return (
        <Alert className="Form-group" dismissable={false}>
          <b style={{ textTransform: 'uppercase', marginRight: '5px' }}>{humanTime(new Date(errorTime))}</b>
          {error}
        </Alert>
      );
    }, 100)

    // General feature toggles.
    .setting(
      () => ({
        setting: 'fof-geoip.showFlag',
        type: 'boolean',
        label: app.translator.trans('fof-geoip.admin.settings.show_flag_label'),
        help: app.translator.trans('fof-geoip.admin.settings.show_flag_help'),
      }),
      90
    )
    .setting(
      () => ({
        setting: 'fof-geoip.allowCustomFlag',
        type: 'boolean',
        label: app.translator.trans('fof-geoip.admin.settings.allow_custom_flag_label'),
        help: app.translator.trans('fof-geoip.admin.settings.allow_custom_flag_help'),
      }),
      80
    )

    // IP lookup service selector; the per-service description renders its URL as an external link.
    .customSetting(function (this: AdminPage) {
      const service = this.setting('fof-geoip.service')();

      return this.buildSettingComponent({
        type: 'select',
        setting: 'fof-geoip.service',
        label: app.translator.trans('fof-geoip.admin.settings.service_label'),
        options: (app.data['fof-geoip.services'] as string[]).reduce((o: Record<string, string>, p: string) => {
          o[p] = extractText(app.translator.trans(`fof-geoip.admin.settings.service_${p}_label`));
          return o;
        }, {}),
        required: true,
        help: service && app.translator.trans(`fof-geoip.admin.settings.service_${service}_description`, { b: <ServiceLink /> }),
      });
    }, 70)

    // Access key — only for services that require one.
    .customSetting(function (this: AdminPage) {
      const service = this.setting('fof-geoip.service')();

      if (!KEYED_SERVICES.includes(service)) return null;

      return this.buildSettingComponent({
        type: 'string',
        setting: `fof-geoip.services.${service}.access_key`,
        label: app.translator.trans('fof-geoip.admin.settings.access_key_label'),
        required: true,
      });
    }, 60)

    // Lookup quota — only meaningful for the ipdata service.
    .customSetting(function (this: AdminPage) {
      const service = this.setting('fof-geoip.service')();

      if (service !== 'ipdata') return null;

      return this.buildSettingComponent({
        type: 'number',
        setting: 'fof-geoip.services.ipdata.quota',
        label: app.translator.trans('fof-geoip.admin.settings.quota_label'),
        min: 1500,
        placeholder: 1500,
      });
    }, 50)

    // Service configuration tester.
    .customSetting(() => <GeoipTestComponent />, 40)

    .permission(
      () => ({
        icon: 'fas fa-globe',
        permission: 'fof-geoip.canSeeCountry',
        label: app.translator.trans('fof-geoip.admin.permissions.see_country'),
      }),
      'moderate',
      50
    ),
];
