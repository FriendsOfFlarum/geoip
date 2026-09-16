import app from 'flarum/common/app';

/**
 * Resolve an ISO 3166-1 alpha-2 code to a country name in the viewer's locale.
 *
 * Only the code is stored, so the displayed name has to be derived. Using
 * Intl.DisplayNames means the name follows whichever language the viewer is
 * reading the forum in, rather than being fixed to the language of whoever
 * happened to run the lookup.
 *
 * Returns null when there is no code, and falls back to the code itself when
 * it cannot be resolved — Intl.DisplayNames throws a RangeError on a malformed
 * value, and a bad row must not take the modal down with it.
 */
export default function getCountryName(countryCode: string | null | undefined, locale?: string): string | null {
  if (!countryCode) {
    return null;
  }

  const code = countryCode.toUpperCase();
  const currentLocale = locale ?? (app.translator.getLocale() as string);

  try {
    return new Intl.DisplayNames([currentLocale], { type: 'region' }).of(code) || code;
  } catch (e) {
    return code;
  }
}
