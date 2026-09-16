import { jest, describe, it, expect, beforeEach } from '@jest/globals';
import m from 'mithril';

/**
 * Regression tests for issue #109: the flag beside the country picker was
 * rendered above the input instead of on the same line.
 *
 * The cause is structural rather than cosmetic, which is why it can be tested
 * without a real stylesheet. Core's AutocompleteDropdown builds its wrapper
 * with a hardcoded class list and never merges `attrs.className`, so the
 * `CountryFlagPicker` class never reached the DOM — and every rule in
 * forum.less is nested under `.CountryFlagPicker`, so none of them applied.
 *
 * These tests assert on the rendered class names: if the component's own class
 * is missing from the wrapper, its styles cannot apply, whatever the LESS says.
 */

async function boot() {
  document.body.innerHTML = '<div id="app"><div id="content"></div></div>';

  const { default: bootstrapForum } = await import('@flarum/jest-config/src/bootstrap/forum');

  bootstrapForum({
    resources: [
      { type: 'forums', id: '1', attributes: { 'fof-geoip.allowCustomFlag': true } },
      { type: 'users', id: '1', attributes: { id: 1, username: 'admin', displayName: 'Admin' } },
    ],
  });

  app.boot();
}

async function mountPicker(attrs: Record<string, unknown> = {}) {
  const { default: CountryFlagPicker } = await import('../../src/common/components/CountryFlagPicker');

  const root = document.createElement('div');
  document.body.appendChild(root);

  m.mount(root, {
    view: () => m(CountryFlagPicker as any, { value: 'GB', onchange: () => {}, ...attrs }),
  });

  await new Promise((resolve) => setTimeout(resolve, 0));
  m.redraw.sync();

  return root;
}

describe('CountryFlagPicker', () => {
  beforeEach(async () => {
    jest.resetModules();
    document.body.innerHTML = '';
    await boot();
  });

  it('puts its own class on the wrapper so its styles can apply', async () => {
    const root = await mountPicker();

    const wrapper = root.querySelector('.AutocompleteDropdown');

    expect(wrapper).not.toBeNull();
    expect(wrapper!.classList.contains('CountryFlagPicker')).toBe(true);
  });

  it('keeps the core AutocompleteDropdown class and its state classes', async () => {
    const root = await mountPicker();

    const wrapper = root.querySelector('.CountryFlagPicker')!;

    // The component must not replace core's class — core's own styles
    // (position: relative, the suggestions menu) hang off it.
    expect(wrapper.classList.contains('AutocompleteDropdown')).toBe(true);
  });

  it('renders the flag and the input as siblings inside the control', async () => {
    const root = await mountPicker({ value: 'GB' });

    const control = root.querySelector('.CountryFlagPicker-control')!;
    const slot = control.querySelector('.CountryFlagPicker-flagSlot');
    const flag = control.querySelector('img.CountryFlagPicker-flag');
    const input = control.querySelector('input.FormControl');

    expect(flag).not.toBeNull();
    expect(input).not.toBeNull();
    // The flag lives inside the fixed-width slot; the slot and the input are
    // the control's direct children.
    expect(flag!.parentElement).toBe(slot);
    expect(slot!.parentElement).toBe(control);
    expect(input!.parentElement).toBe(control);
  });

  it('renders the flag before the input', async () => {
    const root = await mountPicker({ value: 'GB' });

    const control = root.querySelector('.CountryFlagPicker-control')!;
    const children = [...control.children];
    const slotIndex = children.findIndex((c) => c.matches('.CountryFlagPicker-flagSlot'));
    const inputIndex = children.findIndex((c) => c.matches('input.FormControl'));

    expect(slotIndex).toBeGreaterThanOrEqual(0);
    expect(inputIndex).toBeGreaterThan(slotIndex);
  });

  /**
   * The slot ahead of the input is always present, so the field keeps its
   * position whether or not a country is selected. Without it, choosing or
   * clearing a country adds or removes an element before the input and the
   * field jumps sideways.
   */
  it('keeps the flag slot in place when no country is selected', async () => {
    const withFlag = await mountPicker({ value: 'GB' });
    expect(withFlag.querySelector('.CountryFlagPicker-flagSlot')).not.toBeNull();
    expect(withFlag.querySelector('.CountryFlagPicker-flagSlot img')).not.toBeNull();

    document.body.innerHTML = '<div id="app"><div id="content"></div></div>';

    const without = await mountPicker({ value: null });
    const slot = without.querySelector('.CountryFlagPicker-flagSlot');

    // Slot still rendered, just empty.
    expect(slot).not.toBeNull();
    expect(slot!.querySelector('img')).toBeNull();
  });

  it('renders no flag when no country is selected', async () => {
    const root = await mountPicker({ value: null });

    expect(root.querySelector('img.CountryFlagPicker-flag')).toBeNull();
    expect(root.querySelector('input.FormControl')).not.toBeNull();
  });
});
