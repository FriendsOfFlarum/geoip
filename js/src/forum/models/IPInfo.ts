import Model from 'flarum/common/Model';

export default class IPInfo extends Model {
  id() {
    return Model.attribute<string>('id').call(this);
  }

  countryCode() {
    return Model.attribute<string>('countryCode').call(this);
  }

  zipCode() {
    return Model.attribute<string>('zipCode').call(this);
  }

  isp() {
    return Model.attribute<string>('isp').call(this);
  }

  organization() {
    return Model.attribute<string>('organization').call(this);
  }

  threatLevel() {
    return Model.attribute<string>('threatLevel').call(this);
  }

  threatTypes() {
    const raw = Model.attribute<string>('threatTypes').call(this);
    try {
      return JSON.parse(raw);
    } catch (error) {
      return [];
    }
  }

  error() {
    return Model.attribute<string>('error').call(this);
  }

  createdAt() {
    return Model.attribute('createdAt', Model.transformDate).call(this);
  }

  updatedAt() {
    return Model.attribute('updatedAt', Model.transformDate).call(this);
  }
}
