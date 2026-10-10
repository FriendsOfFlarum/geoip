import app from 'flarum/common/app';
import type Mithril from 'mithril';
import type User from 'flarum/common/models/User';
import type IPInfo from '../model/IPInfo';
import { getFlagImageForCountry, getIPData } from './IPDataHelper';

/** A user as this extension's model extenders leave them (see forum/extend.ts). */
type FlagAuthor = User & {
  showIPCountry?: () => boolean | undefined;
  customFlagCountry?: () => string | null | undefined;
  canSeeCountry?: () => boolean | undefined;
};

/**
 * The flag shown in the header of something a member wrote — a post, a
 * private message — or null when nothing should be shown. One decision for
 * every place a flag appears, so the rules can't drift apart:
 *
 *  - nothing unless the admin has turned flags on;
 *  - a flag the author chose themselves wins while that feature is enabled —
 *    it is self-disclosed, so everyone sees it;
 *  - otherwise the flag of the address they wrote from, to the audience the
 *    author opted into with `showIPCountry`, and to anyone allowed to see
 *    countries regardless.
 *
 * The backend applies the same rules to whether `ipInfo` ships at all; this
 * only decides what to draw with what arrived.
 */
export function headerFlag(author: FlagAuthor | null | false | undefined, ipInfo: IPInfo | null | false | undefined): Mithril.Children {
  if (!app.forum.attribute<boolean>('fof-geoip.showFlag')) return null;

  if (author && app.forum.attribute<boolean>('fof-geoip.allowCustomFlag')) {
    const custom = author.customFlagCountry?.();

    if (custom) return getFlagImageForCountry(custom) ?? null;
  }

  if (!ipInfo) return null;

  const optedIn = !!author && !!author.showIPCountry?.();
  const viewerMay = !!(app.session.user as FlagAuthor | null | undefined)?.canSeeCountry?.();

  if (!optedIn && !viewerMay) return null;

  return getIPData(ipInfo).image ?? null;
}
