import { countryCodeEmoji } from 'country-code-emoji';

/**
 * Convert emoji to hex codepoints for Twemoji CDN filename
 * Example: '🇺🇸' -> '1f1fa-1f1f8'
 */
function emojiToCodepoint(emoji: string): string {
  return Array.from(emoji)
    .map((char) => char.codePointAt(0)?.toString(16))
    .filter(Boolean)
    .join('-');
}

export default function getFlagEmojiUrl(countryCode: string): string | null {
  if (!countryCode) return null;

  const flagEmoji = countryCodeEmoji(countryCode);
  if (!flagEmoji) return null;

  const basename = emojiToCodepoint(flagEmoji);
  return `https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/${basename}.png`;
}
