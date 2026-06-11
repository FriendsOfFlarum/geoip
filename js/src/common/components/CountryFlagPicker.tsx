import app from 'flarum/common/app';
import AutocompleteDropdown, { type AutocompleteDropdownAttrs } from 'flarum/common/components/AutocompleteDropdown';
import extractText from 'flarum/common/utils/extractText';
import type Mithril from 'mithril';
import getCountries, { type Country } from '../util/getCountries';
import getFlagEmojiUrl from '../util/getFlagEmojiUrl';

export interface CountryFlagPickerAttrs extends AutocompleteDropdownAttrs {
  /** The currently-selected ISO 3166-1 alpha-2 code, or null/empty for none. */
  value?: string | null;
  /** Called with the chosen code, or null when the selection is cleared. */
  onchange: (code: string | null) => void;
  /** Disables input while a save is in flight. */
  disabled?: boolean;
}

const MAX_RESULTS = 50;

/**
 * A searchable country picker. The user types to filter the list of countries
 * (by localized name or code) and selects one; the chosen flag is rendered
 * inline. Clearing the input clears the selection.
 */
export default class CountryFlagPicker extends AutocompleteDropdown<CountryFlagPickerAttrs> {
  /** Free-text query the user has typed into the input. */
  protected query = '';
  /** Whether the user has edited the input since the value was last applied. */
  protected dirty = false;

  oninit(vnode: Mithril.Vnode<CountryFlagPickerAttrs, this>) {
    super.oninit(vnode);
    this.syncQueryToValue();
  }

  onupdate(vnode: Mithril.VnodeDOM<CountryFlagPickerAttrs, this>) {
    super.onupdate(vnode);
    // Reflect external value changes (e.g. after a save) back into the input,
    // unless the user is actively editing it.
    if (!this.dirty && !this.hasFocus) {
      this.syncQueryToValue();
    }
  }

  private syncQueryToValue() {
    const current = this.currentCountry();
    this.query = current ? current.name : '';
  }

  private currentCountry(): Country | undefined {
    const code = (this.attrs.value || '').toUpperCase();
    if (!code) return undefined;
    return getCountries().find((c) => c.code === code);
  }

  view(vnode: Mithril.Vnode<CountryFlagPickerAttrs, this>) {
    const code = this.attrs.value || '';
    const flagUrl = code ? getFlagEmojiUrl(code) : null;
    const placeholder = extractText(app.translator.trans('fof-geoip.lib.custom_flag.placeholder'));

    vnode.children = [
      <div className="CountryFlagPicker-control">
        {flagUrl && <img className="CountryFlagPicker-flag" src={flagUrl} alt="" height="16" />}
        <input
          className="FormControl"
          autocomplete="off"
          placeholder={placeholder}
          disabled={this.attrs.disabled}
          value={this.query}
          oninput={(e: InputEvent) => {
            this.query = (e.target as HTMLInputElement).value;
            this.dirty = true;
            // Clearing the field clears the selection.
            if (this.query.trim() === '' && this.attrs.value) {
              this.attrs.onchange(null);
            }
          }}
        />
      </div>,
    ];

    return super.view(vnode);
  }

  suggestions(): JSX.Element[] {
    if (!this.dirty) return [];

    const q = this.query.trim().toLowerCase();
    if (q === '') return [];

    const matches = getCountries()
      .filter((c) => c.name.toLowerCase().includes(q) || c.code.toLowerCase() === q)
      .slice(0, MAX_RESULTS);

    return matches.map((country) => {
      const flagUrl = getFlagEmojiUrl(country.code);

      return (
        <li data-index={country.code}>
          <button type="button" className="CountryFlagPicker-option" onclick={() => this.select(country)}>
            {flagUrl && <img className="CountryFlagPicker-optionFlag" src={flagUrl} alt="" height="16" />}
            <span className="CountryFlagPicker-optionName">{country.name}</span>
          </button>
        </li>
      );
    });
  }

  private select(country: Country) {
    this.query = country.name;
    this.dirty = false;
    this.hasFocus = false;
    this.inputElement().blur();
    this.attrs.onchange(country.code);
  }
}
