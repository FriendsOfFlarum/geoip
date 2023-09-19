import flag from 'country-code-emoji';
import convert from 'twemoji-basename';

export default (countryCode) => {
  const codepoint = flag(countryCode);
  if (!codepoint) return null;

  const basename = convert(codepoint);
  return basename ? `https://cdn.jsdelivr.net/gh/twitter/twemoji@14/assets/72x72/${basename}.png` : null;
};
