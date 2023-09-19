export default (str: string): void => {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(str).then(() => {});
    return;
  }

  // Fallback method:
  const el: HTMLTextAreaElement = document.createElement('textarea');
  el.value = str;
  el.setAttribute('readonly', '');
  el.style.position = 'absolute';
  el.style.left = '-9999px';
  document.body.appendChild(el);

  const selection = document.getSelection();
  const selected: Range | null = selection && selection.rangeCount > 0 ? selection.getRangeAt(0) : null;

  el.select();
  document.execCommand('copy');
  document.body.removeChild(el);

  if (selected && selection) {
    selection.removeAllRanges();
    selection.addRange(selected);
  }
};
