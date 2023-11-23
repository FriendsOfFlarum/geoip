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

  latitude() {
    return Model.attribute<number>('latitude').call(this);
  }

  longitude() {
    return Model.attribute<number>('longitude').call(this);
  }

  isp() {
    return Model.attribute<string>('isp').call(this);
  }

  organization() {
    return Model.attribute<string>('organization').call(this);
  }

  as() {
    return Model.attribute<string>('as').call(this);
  }

  mobile() {
    return Model.attribute<boolean>('mobile').call(this);
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

  dataProvider() {
    return Model.attribute<string>('dataProvider').call(this);
  }

  createdAt() {
    return Model.attribute('createdAt', Model.transformDate).call(this);
  }

  updatedAt() {
    return Model.attribute('updatedAt', Model.transformDate).call(this);
  }
}
