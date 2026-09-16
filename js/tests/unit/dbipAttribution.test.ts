import { describe, it, expect, beforeAll } from '@jest/globals';

/**
 * DB-IP's Lite databases are licensed CC BY 4.0, which requires visible
 * attribution wherever results derived from them are displayed. MaxMind's
 * licences do not impose the same public credit, and the hosted providers
 * carry no such term, so the notice is shown only when the data actually came
 * from DB-IP.
 *
 * The decision is driven by the `dataProvider` recorded on each record — the
 * database type the lookup came from — rather than by whatever provider
 * happens to be configured now, so a record looked up months ago is still
 * credited correctly.
 */
describe('isDbIpProvider', () => {
  let isDbIpProvider: (provider: string | null | undefined) => boolean;

  beforeAll(async () => {
    ({ default: isDbIpProvider } = await import('../../src/common/util/isDbIpProvider'));
  });

  it('recognises the DB-IP database types', () => {
    expect(isDbIpProvider('DBIP-Country-Lite')).toBe(true);
    expect(isDbIpProvider('DBIP-City-Lite')).toBe(true);
    expect(isDbIpProvider('DBIP-ASN-Lite (compat=GeoLite2-ASN)')).toBe(true);
  });

  it('recognises paid DB-IP editions', () => {
    expect(isDbIpProvider('DBIP-City')).toBe(true);
    expect(isDbIpProvider('DBIP-Location-ISP')).toBe(true);
  });

  it('is case insensitive', () => {
    // Nothing guarantees the vendor keeps capitalising it the same way.
    expect(isDbIpProvider('dbip-city-lite')).toBe(true);
  });

  /**
   * The ASN database declares `compat=GeoLite2-ASN`, so a naive "contains
   * GeoLite2" check would misattribute a DB-IP record to MaxMind. Matching on
   * the leading vendor name avoids that.
   */
  it('does not confuse MaxMind databases for DB-IP', () => {
    expect(isDbIpProvider('GeoLite2-City')).toBe(false);
    expect(isDbIpProvider('GeoLite2-ASN')).toBe(false);
    expect(isDbIpProvider('GeoIP2-City')).toBe(false);
    expect(isDbIpProvider('GeoIP2-ISP')).toBe(false);
  });

  it('does not credit the hosted providers', () => {
    expect(isDbIpProvider('http://ip-api.com')).toBe(false);
    expect(isDbIpProvider('https://api.ipdata.co')).toBe(false);
    expect(isDbIpProvider('https://api.iplocation.net')).toBe(false);
  });

  it('handles a missing provider', () => {
    expect(isDbIpProvider(null)).toBe(false);
    expect(isDbIpProvider(undefined)).toBe(false);
    expect(isDbIpProvider('')).toBe(false);
  });
});
