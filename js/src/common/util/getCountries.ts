import app from 'flarum/common/app';
import getFlagEmojiUrl from './getFlagEmojiUrl';

/**
 * Candidate ISO 3166-1 alpha-2 country codes for the custom-flag picker. Kept
 * as a static list so we don't depend on a heavyweight country-data package;
 * names are resolved per-locale at runtime via Intl.DisplayNames (the same
 * mechanism IPDataHelper uses for IP flags).
 */
// prettier-ignore
const COUNTRY_CODES = [
  'AD','AE','AF','AG','AI','AL','AM','AO','AQ','AR','AS','AT','AU','AW','AX','AZ',
  'BA','BB','BD','BE','BF','BG','BH','BI','BJ','BL','BM','BN','BO','BQ','BR','BS',
  'BT','BV','BW','BY','BZ','CA','CC','CD','CF','CG','CH','CI','CK','CL','CM','CN',
  'CO','CR','CU','CV','CW','CX','CY','CZ','DE','DJ','DK','DM','DO','DZ','EC','EE',
  'EG','EH','ER','ES','ET','FI','FJ','FK','FM','FO','FR','GA','GB','GD','GE','GF',
  'GG','GH','GI','GL','GM','GN','GP','GQ','GR','GS','GT','GU','GW','GY','HK','HM',
  'HN','HR','HT','HU','ID','IE','IL','IM','IN','IO','IQ','IR','IS','IT','JE','JM',
  'JO','JP','KE','KG','KH','KI','KM','KN','KP','KR','KW','KY','KZ','LA','LB','LC',
  'LI','LK','LR','LS','LT','LU','LV','LY','MA','MC','MD','ME','MF','MG','MH','MK',
  'ML','MM','MN','MO','MP','MQ','MR','MS','MT','MU','MV','MW','MX','MY','MZ','NA',
  'NC','NE','NF','NG','NI','NL','NO','NP','NR','NU','NZ','OM','PA','PE','PF','PG',
  'PH','PK','PL','PM','PN','PR','PS','PT','PW','PY','QA','RE','RO','RS','RU','RW',
  'SA','SB','SC','SD','SE','SG','SH','SI','SJ','SK','SL','SM','SN','SO','SR','SS',
  'ST','SV','SX','SY','SZ','TC','TD','TF','TG','TH','TJ','TK','TL','TM','TN','TO',
  'TR','TT','TV','TW','TZ','UA','UG','UM','US','UY','UZ','VA','VC','VE','VG','VI',
  'VN','VU','WF','WS','YE','YT','ZA','ZM','ZW',
];

export interface Country {
  code: string;
  name: string;
}

let cache: { locale: string; countries: Country[] } | null = null;

/**
 * Returns all selectable countries (code + localized name), sorted by name in
 * the current locale, cached per-locale so the list isn't rebuilt on every
 * keystroke of the picker.
 *
 * The candidate codes are filtered down to those the flag renderer
 * (getFlagEmojiUrl) can actually produce an image for, so the picker stays in
 * sync with what will actually be displayed on a post — the render pipeline is
 * the source of truth.
 */
export default function getCountries(): Country[] {
  const locale = app.translator.getLocale() as string;

  if (cache && cache.locale === locale) {
    return cache.countries;
  }

  const displayNames = new Intl.DisplayNames([locale], { type: 'region' });

  const countries = COUNTRY_CODES.filter((code) => getFlagEmojiUrl(code) !== null)
    .map((code) => {
      let name = code;
      try {
        name = displayNames.of(code) || code;
      } catch (e) {
        // Intl.DisplayNames throws on codes it doesn't recognise; fall back to the code.
      }
      return { code, name };
    })
    .sort((a, b) => a.name.localeCompare(b.name, locale));

  cache = { locale, countries };

  return countries;
}
