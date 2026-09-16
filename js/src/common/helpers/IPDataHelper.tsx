import app from 'flarum/common/app';
import IPInfo from '../model/IPInfo';
import getFlagEmojiUrl from '../util/getFlagEmojiUrl';
import getCountryName from '../util/getCountryName';
import Tooltip from 'flarum/common/components/Tooltip';

export const getDescription = (ipInfo: IPInfo) => {
  return ipInfo.organization() || ipInfo.isp() || ipInfo.error() || '';
};

export const getThreat = (ipInfo: IPInfo) => {
  return ipInfo.threatTypes() && ipInfo.threatTypes().join(', ');
};

/**
 * Render a flag image for a bare ISO 3166-1 alpha-2 country code. Shared by the
 * IP-derived flag (getFlagImage) and the user-selected custom flag.
 */
export const getFlagImageForCountry = (countryCode: string | null | undefined) => {
  if (!countryCode) {
    return null;
  }

  const url = getFlagEmojiUrl(countryCode);

  if (!url) {
    return null;
  }

  const countryName = getCountryName(countryCode) ?? countryCode;

  return (
    <Tooltip text={countryName}>
      <img src={url} alt={countryName} height="16" loading="lazy" />
    </Tooltip>
  );
};

export const getFlagImage = (ipInfo: IPInfo | null | undefined) => {
  return getFlagImageForCountry(ipInfo?.countryCode?.());
};

export const getIPData = (ipInfo: IPInfo) => {
  const description = getDescription(ipInfo);
  const threat = getThreat(ipInfo);
  const image = getFlagImage(ipInfo);

  // Extracting zip and country from ipInfo
  const zip = ipInfo.zipCode();
  const country = ipInfo.countryCode(); // Assuming the country code is used as 'country'

  return { description, threat, image, zip, country };
};
