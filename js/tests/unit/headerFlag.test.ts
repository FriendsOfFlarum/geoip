import { describe, it, expect, beforeEach } from '@jest/globals';
import app from 'flarum/common/app';
import { headerFlag } from '../../src/common/helpers/headerFlag';

/**
 * One decision for the flag in a post's header and a private message's.
 * The rules are the post rules; these pin them so the two can't drift.
 */

type Settings = { showFlag: boolean; allowCustomFlag: boolean };

function forum(settings: Settings) {
  (app as any).forum = {
    attribute: (key: string) => ({ 'fof-geoip.showFlag': settings.showFlag, 'fof-geoip.allowCustomFlag': settings.allowCustomFlag })[key],
  };
}

function viewer(canSeeCountry: boolean) {
  (app as any).session = { user: { canSeeCountry: () => canSeeCountry } };
}

const author = (showIPCountry: boolean, customFlagCountry: string | null = null) => ({
  showIPCountry: () => showIPCountry,
  customFlagCountry: () => customFlagCountry,
});

const ipInfo = (countryCode: string) =>
  ({
    countryCode: () => countryCode,
    zipCode: () => null,
    city: () => null,
    region: () => null,
    isp: () => null,
    organization: () => null,
    as: () => null,
    threat: () => null,
    threatTypes: () => [],
    error: () => null,
  }) as any;

beforeEach(() => {
  forum({ showFlag: true, allowCustomFlag: false });
  viewer(false);
  // The flag's tooltip names the country in the viewer's locale.
  (app as any).translator = { getLocale: () => 'en' };
});

describe('headerFlag', () => {
  it('shows nothing while flags are turned off, whatever else is true', () => {
    forum({ showFlag: false, allowCustomFlag: true });
    viewer(true);

    expect(headerFlag(author(true, 'GB') as any, ipInfo('US'))).toBeNull();
  });

  it('shows the flag the author chose, to anyone, when that is allowed', () => {
    forum({ showFlag: true, allowCustomFlag: true });

    expect(headerFlag(author(false, 'GB') as any, null)).not.toBeNull();
  });

  it('ignores a chosen flag when the admin has turned that off', () => {
    forum({ showFlag: true, allowCustomFlag: false });

    expect(headerFlag(author(false, 'GB') as any, null)).toBeNull();
  });

  it('shows where the author wrote from when they opted in', () => {
    expect(headerFlag(author(true) as any, ipInfo('US'))).not.toBeNull();
  });

  it('shows it to a viewer allowed to see countries even when the author did not opt in', () => {
    viewer(true);

    expect(headerFlag(author(false) as any, ipInfo('US'))).not.toBeNull();
  });

  it('shows nothing otherwise, or when there is no address data', () => {
    expect(headerFlag(author(false) as any, ipInfo('US'))).toBeNull();
    expect(headerFlag(author(true) as any, null)).toBeNull();
    expect(headerFlag(null, ipInfo('US'))).toBeNull();
  });
});
