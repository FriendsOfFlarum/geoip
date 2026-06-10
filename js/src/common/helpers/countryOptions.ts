import app from 'flarum/common/app';
import COUNTRY_CODES from '../util/countryCodes';
import getFlagEmojiUrl from '../util/getFlagEmojiUrl';

/**
 * Build a `{ code: localizedName }` map of ISO 3166-1 alpha-2 countries,
 * sorted alphabetically by name in the current locale. Suitable for passing
 * to a Flarum <Select> options prop.
 *
 * The candidate codes come from COUNTRY_CODES, but the list is filtered down
 * to codes that the flag renderer (getFlagEmojiUrl) can actually produce a
 * flag image for. That keeps the picker in sync with what will actually be
 * displayed on a post — the render pipeline is the source of truth.
 */
export default function countryOptions(): Record<string, string> {
  const locale = app.translator.getLocale() as string;
  const displayNames = new Intl.DisplayNames([locale], { type: 'region' });

  return COUNTRY_CODES.filter((code) => getFlagEmojiUrl(code) !== null)
    .map((code) => {
      let name = code;
      try {
        name = displayNames.of(code) || code;
      } catch (e) {
        // Intl.DisplayNames throws on codes it doesn't recognise; fall back to the code.
      }
      return [code, name] as [string, string];
    })
    .sort((a, b) => a[1].localeCompare(b[1], locale))
    .reduce((options: Record<string, string>, [code, name]) => {
      options[code] = name;
      return options;
    }, {});
}
