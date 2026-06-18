import IPInfo from '../models/IPInfo';
export declare const getDescription: (ipInfo: IPInfo) => string;
export declare const getThreat: (ipInfo: IPInfo) => any;
export declare const getFlagImageForCountry: (countryCode?: string | null) => JSX.Element | null;
export declare const getFlagImage: (ipInfo: IPInfo) => JSX.Element | null;
export declare const getIPData: (ipInfo: IPInfo) => {
    description: string;
    threat: any;
    image: JSX.Element | null;
    zip: string | null;
    country: string | null;
};
