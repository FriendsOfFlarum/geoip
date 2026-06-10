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
export default function countryOptions(): Record<string, string>;
