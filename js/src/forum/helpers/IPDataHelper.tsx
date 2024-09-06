import IPInfo from '../models/IPInfo';
import getFlagEmojiUrl from '../util/getFlagEmojiUrl';
import Tooltip from 'flarum/common/components/Tooltip';

export const getDescription = (ipInfo: IPInfo) => {
  return ipInfo.organization() || ipInfo.isp() || ipInfo.error() || '';
};

export const getThreat = (ipInfo: IPInfo) => {
  return ipInfo.threatTypes() && ipInfo.threatTypes().join(', ');
};

export const getFlagImage = (ipInfo: IPInfo) => {
  if (ipInfo.countryCode() && ipInfo.countryCode().length > 1) {
    const url = getFlagEmojiUrl(ipInfo.countryCode());
    if (url) {
      return (
        <Tooltip text={ipInfo.countryCode()}>
          <img src={url} alt={ipInfo.countryCode()} height="16" loading="lazy" />
        </Tooltip>
      );
    }
  }
  return null;
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
