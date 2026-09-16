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

/** A setting declared by the active service in PHP. */
interface ServiceSetting {
  type?: 'text' | 'number' | 'boolean';
  label: string;
  help?: string;
  placeholder?: string;
  required?: boolean;
  /** Supplied by an extender, so the stored setting is ignored. */
  pinned?: boolean;
}

/** Per-database diagnostics reported by an offline service. */
interface DatabaseStatus {
  /** Whether a path is set at all, as opposed to set but unusable. */
  configured: boolean;
  available: boolean;
  path: string | null;
  type: string | null;
  built: number | null;
  error: string | null;
}

/**
 * Flag a database as stale once it is older than this.
 *
 * Both MaxMind and DB-IP publish monthly, so 90 days means roughly three
 * missed editions — late enough not to nag about a database that is a month or
 * two old, early enough to catch one that is no longer being updated. A stale
 * database still works; its allocations are just increasingly likely to be
 * wrong.
 */
const STALE_AFTER_DAYS = 90;

/** The database a `…_path` setting configures, or '' for other settings. */
function databaseKind(settingKey: string): string {
  return settingKey.match(/\.(\w+)_path$/)?.[1] ?? '';
}

/**
 * A one-line verdict per database, read from the file's own metadata: what it
 * is, when it was built, and whether it is too old to trust. Without this a
 * wrong path or a long-stale database degrades silently.
 */
function databaseStatusLine(status: DatabaseStatus | undefined): Mithril.Children {
  if (!status) return null;

  // No path set. Say so explicitly: every database is optional, so silence
  // here reads as "something went wrong" rather than "you have not set this".
  if (!status.configured) {
    return <p className="GeoipDatabases-status">{app.translator.trans('fof-geoip.admin.settings.database_not_configured')}</p>;
  }

  if (!status.available) {
    return <p className="GeoipDatabases-status GeoipDatabases-status--error">{status.error}</p>;
  }

  const built = status.built ? new Date(status.built * 1000) : null;
  const stale = built ? (Date.now() - built.getTime()) / 86_400_000 > STALE_AFTER_DAYS : false;

  return (
    <p className={`GeoipDatabases-status${stale ? ' GeoipDatabases-status--stale' : ''}`}>
      <strong>{status.type}</strong>
      {built && [' — ', app.translator.trans('fof-geoip.admin.settings.database_built', { time: humanTime(built) })]}
      {stale && ` (${extractText(app.translator.trans('fof-geoip.admin.settings.database_stale'))})`}
    </p>
  );
}

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
    // The selected service cannot answer lookups at all — an offline driver
    // with no readable database, say. Shown above everything else because the
    // failure is otherwise invisible: the forum carries on and posts simply
    // never get flags.
    .customSetting(function (this: AdminPage) {
      if (!app.data['fof-geoip.serviceUnavailable']) return null;

      return (
        <Alert type="error" className="Form-group" dismissible={false}>
          {app.translator.trans('fof-geoip.admin.settings.service_unavailable')}
        </Alert>
      );
    }, 110)

    // An error from the currently-selected service, surfaced above the settings.
    .customSetting(function (this: AdminPage) {
      const service = this.setting('fof-geoip.service')();
      const error = app.data.settings[`fof-geoip.services.${service}.error`] as string | undefined;

      if (!error) return null;

      const errorTime = Number(app.data.settings[`fof-geoip.services.${service}.last_error_time`]) * 1000;

      return (
        <Alert className="Form-group" dismissible={false}>
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
      const forced = app.data['fof-geoip.forcedService'] as string | null;

      // An extender has pinned the driver, so this setting is ignored. Show
      // what is actually in effect rather than a control whose value would
      // silently have no consequence.
      if (forced) {
        return (
          <div className="Form-group">
            <label>{app.translator.trans('fof-geoip.admin.settings.service_label')}</label>
            <Alert type="info" dismissible={false}>
              {app.translator.trans('fof-geoip.admin.settings.service_forced', {
                service: extractText(app.translator.trans(`fof-geoip.admin.settings.service_${forced}_label`)),
              })}
            </Alert>
          </div>
        );
      }

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

    // Settings for the active service, rendered from what the service itself
    // declares in PHP (see ServiceInterface::settings()). Deliberately generic:
    // it knows nothing about which services exist or what fields they need, so
    // adding a service is a backend-only change and the frontend cannot drift
    // out of step with it.
    .customSetting(function (this: AdminPage) {
      const declared = (app.data['fof-geoip.serviceSettings'] as Record<string, ServiceSetting>) ?? {};
      const status = (app.data['fof-geoip.databaseStatus'] as Record<string, DatabaseStatus>) ?? {};

      const entries = Object.entries(declared);

      if (entries.length === 0) return null;

      return (
        <div className="GeoipServiceSettings">
          {entries.map(([key, definition]) => {
            // A path supplied by an extender takes precedence over the stored
            // setting, so the input would have no effect. Show the value as
            // read-only rather than implying it can be changed here.
            if (definition.pinned) {
              return (
                <div className="Form-group" key={key}>
                  <label>{app.translator.trans(definition.label)}</label>
                  <p className="helpText">{app.translator.trans('fof-geoip.admin.settings.setting_pinned')}</p>
                  {databaseStatusLine(status[databaseKind(key)])}
                </div>
              );
            }

            return (
              <div key={key}>
                {this.buildSettingComponent({
                  type: definition.type === 'number' ? 'number' : definition.type === 'boolean' ? 'boolean' : 'string',
                  setting: key,
                  label: app.translator.trans(definition.label),
                  help: definition.help ? app.translator.trans(definition.help) : undefined,
                  placeholder: definition.placeholder,
                  required: definition.required ?? false,
                })}
                {databaseStatusLine(status[databaseKind(key)])}
              </div>
            );
          })}
        </div>
      );
    }, 60)

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
