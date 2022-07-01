import Model from 'flarum/common/Model';
import mixin from 'flarum/common/utils/mixin';

export default class IPInfo extends mixin(Model, {
  countryCode: Model.attribute('countryCode'),
  zipCode: Model.attribute('zipCode'),
  isp: Model.attribute('isp'),
  organization: Model.attribute('organization'),
  threatLevel: Model.attribute('threatLevel'),
  threatTypes: Model.attribute('threatTypes', (val) => (val && JSON.parse(val)) || Array.wrap),
  error: Model.attribute('error'),
}) {}
