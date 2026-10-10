import type Mithril from 'mithril';
import type User from 'flarum/common/models/User';
import type IPInfo from '../model/IPInfo';
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
export declare function headerFlag(author: FlagAuthor | null | false | undefined, ipInfo: IPInfo | null | false | undefined): Mithril.Children;
export {};
