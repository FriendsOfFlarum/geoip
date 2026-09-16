/**
 * Whether a record's data came from a DB-IP database.
 *
 * DB-IP's Lite databases are licensed CC BY 4.0, which requires visible
 * attribution wherever results derived from them are shown. MaxMind's licences
 * do not impose the same public credit, and the hosted APIs carry no such
 * term, so the notice is shown only for DB-IP data.
 *
 * Matched on the leading vendor name rather than a substring: DB-IP's ASN
 * database declares its type as `DBIP-ASN-Lite (compat=GeoLite2-ASN)`, so
 * looking for "GeoLite2" anywhere in the string would credit MaxMind for a
 * DB-IP record.
 */
export default function isDbIpProvider(provider: string | null | undefined): boolean {
  if (!provider) {
    return false;
  }

  return provider.trim().toLowerCase().startsWith('dbip');
}
