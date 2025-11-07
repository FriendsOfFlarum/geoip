import app from 'flarum/common/app';
import IPInfo from '../model/IPInfo';
import getFlagEmojiUrl from '../util/getFlagEmojiUrl';
import Tooltip from 'flarum/common/components/Tooltip';

export const getDescription = (ipInfo: IPInfo) => {
  return ipInfo.organization() || ipInfo.isp() || ipInfo.error() || '';
};

export const getThreat = (ipInfo: IPInfo) => {
  return ipInfo.threatTypes() && ipInfo.threatTypes().join(', ');
};

export const getFlagImage = (ipInfo: IPInfo | null | undefined) => {
  if (!ipInfo) return null;

  const countryCode = ipInfo.countryCode?.();

  if (!countryCode) {
    return null;
  }

  const url = getFlagEmojiUrl(countryCode);

  if (!url) {
    return null;
  }

  const currentLocale = app.translator.getLocale() as string;

  // Create an instance of Intl.DisplayNames for displaying full country names
  const displayNames = new Intl.DisplayNames([currentLocale], { type: 'region' });

  // Get the full country name using the country code
  const countryName = displayNames.of(countryCode) || countryCode;

  return (
    <Tooltip text={countryName}>
      <img src={url} alt={countryName} height="16" loading="lazy" />
    </Tooltip>
  );
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
