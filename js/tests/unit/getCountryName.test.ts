import { describe, it, expect, beforeAll } from '@jest/globals';

/**
 * Country codes are stored, country names are displayed.
 *
 * The flag tooltip already resolved a code to a localized name via
 * Intl.DisplayNames, but the logic was inline and the map modal showed the
 * bare code instead. This is that logic extracted so both use it, and so the
 * name follows the viewer's locale rather than being hardcoded English.
 */
describe('getCountryName', () => {
  let getCountryName: (code: string | null | undefined, locale?: string) => string | null;

  beforeAll(async () => {
    ({ default: getCountryName } = await import('../../src/common/util/getCountryName'));
  });

  it('resolves a code to a name in the given locale', () => {
    expect(getCountryName('DE', 'en')).toBe('Germany');
    expect(getCountryName('GB', 'en')).toBe('United Kingdom');
  });

  it('localizes the name', () => {
    expect(getCountryName('DE', 'de')).toBe('Deutschland');
    expect(getCountryName('DE', 'fr')).toBe('Allemagne');
  });

  it('accepts lower-case codes', () => {
    // Stored values are upper-cased, but nothing guarantees a caller passes
    // them that way.
    expect(getCountryName('de', 'en')).toBe('Germany');
  });

  it('returns null for no code', () => {
    expect(getCountryName(null)).toBeNull();
    expect(getCountryName(undefined)).toBeNull();
    expect(getCountryName('')).toBeNull();
  });

  /**
   * Intl.DisplayNames throws a RangeError on a malformed code. A bad value in
   * the database must not take out the modal, so the code itself is shown.
   */
  it('falls back to the code when it cannot be resolved', () => {
    expect(getCountryName('ZZZZ', 'en')).toBe('ZZZZ');
    expect(getCountryName('!!', 'en')).toBe('!!');
  });
});
