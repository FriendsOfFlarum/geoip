export interface Country {
    code: string;
    name: string;
}
/**
 * Returns all selectable countries (code + localized name), sorted by name in
 * the current locale, cached per-locale so the list isn't rebuilt on every
 * keystroke of the picker.
 *
 * The candidate codes are filtered down to those the flag renderer
 * (getFlagEmojiUrl) can actually produce an image for, so the picker stays in
 * sync with what will actually be displayed on a post — the render pipeline is
 * the source of truth.
 */
export default function getCountries(): Country[];
