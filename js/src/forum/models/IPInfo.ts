import Model from 'flarum/common/Model';

export default class IPInfo extends Model {
  id() {
    return Model.attribute<string>('id').call(this);
  }

  countryCode() {
    return Model.attribute<string | null>('countryCode').call(this);
  }

  zipCode() {
    return Model.attribute<string | null>('zipCode').call(this);
  }

  latitude() {
    return Model.attribute<number | null>('latitude').call(this);
  }

  longitude() {
    return Model.attribute<number | null>('longitude').call(this);
  }

  isp() {
    return Model.attribute<string | null>('isp').call(this);
  }

  organization() {
    return Model.attribute<string | null>('organization').call(this);
  }

  as() {
    return Model.attribute<string | null>('as').call(this);
  }

  mobile() {
    return Model.attribute<boolean | null>('mobile').call(this);
  }

  threatLevel() {
    return Model.attribute<string | null>('threatLevel').call(this);
  }

  threatTypes() {
    const raw = Model.attribute<string | null>('threatTypes').call(this);
    if (!raw) {
      return [];
    }
    try {
      return JSON.parse(raw);
    } catch (error) {
      return [];
    }
  }

  error() {
    return Model.attribute<string | null>('error').call(this);
  }

  dataProvider() {
    return Model.attribute<string | null>('dataProvider').call(this);
  }

  createdAt() {
    return Model.attribute('createdAt', Model.transformDate).call(this);
  }

  updatedAt() {
    return Model.attribute('updatedAt', Model.transformDate).call(this);
  }
}
