import Model from 'flarum/common/Model';
export default class IPInfo extends Model {
    id(): string;
    ip(): string | undefined;
    countryCode(): string | null;
    zipCode(): string | null;
    latitude(): number | null;
    longitude(): number | null;
    isp(): string | null;
    organization(): string | null;
    as(): string | null;
    mobile(): boolean | null;
    threatLevel(): string | null;
    threatTypes(): any;
    error(): string | null;
    dataProvider(): string | null;
    createdAt(): Date | null | undefined;
    updatedAt(): Date | null | undefined;
}
