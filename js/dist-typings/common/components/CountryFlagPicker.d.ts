import AutocompleteDropdown, { type AutocompleteDropdownAttrs } from 'flarum/common/components/AutocompleteDropdown';
import type Mithril from 'mithril';
export interface CountryFlagPickerAttrs extends AutocompleteDropdownAttrs {
    /** The currently-selected ISO 3166-1 alpha-2 code, or null/empty for none. */
    value?: string | null;
    /** Called with the chosen code, or null when the selection is cleared. */
    onchange: (code: string | null) => void;
    /** Disables input while a save is in flight. */
    disabled?: boolean;
}
/**
 * A searchable country picker. The user types to filter the list of countries
 * (by localized name or code) and selects one; the chosen flag is rendered
 * inline. Clearing the input clears the selection.
 */
export default class CountryFlagPicker extends AutocompleteDropdown<CountryFlagPickerAttrs> {
    /** Free-text query the user has typed into the input. */
    protected query: string;
    /** Whether the user has edited the input since the value was last applied. */
    protected dirty: boolean;
    oninit(vnode: Mithril.Vnode<CountryFlagPickerAttrs, this>): void;
    onupdate(vnode: Mithril.VnodeDOM<CountryFlagPickerAttrs, this>): void;
    private syncQueryToValue;
    private currentCountry;
    view(vnode: Mithril.Vnode<CountryFlagPickerAttrs, this>): Mithril.Children;
    suggestions(): JSX.Element[];
    private select;
}
