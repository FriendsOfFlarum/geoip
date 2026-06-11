/// <reference types="mithril" />
import IPInfo from '../model/IPInfo';
export declare const getDescription: (ipInfo: IPInfo) => string;
export declare const getThreat: (ipInfo: IPInfo) => any;
/**
 * Render a flag image for a bare ISO 3166-1 alpha-2 country code. Shared by the
 * IP-derived flag (getFlagImage) and the user-selected custom flag.
 */
export declare const getFlagImageForCountry: (countryCode: string | null | undefined) => JSX.Element | null;
export declare const getFlagImage: (ipInfo: IPInfo | null | undefined) => JSX.Element | null;
export declare const getIPData: (ipInfo: IPInfo) => {
    description: string;
    threat: any;
    image: JSX.Element | null;
    zip: string | null;
    country: string | null;
};
