import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import ZipCodeMap from '../components/ZipCodeMap';
import Tooltip from 'flarum/common/components/Tooltip';
import { getIPData } from '../helpers/IPDataHelper';
import { handleCopyIP } from '../helpers/ClipboardHelper';

export default function extendBanIPModal() {
  const BanIPModal = flarum.core.compat['fof/ban-ips/components/BanIPModal'];

  if (BanIPModal) {
    extend(BanIPModal.prototype, 'content', function (vdom) {
      if (!this.post || !this.post.ipAddress()) return;

      const ipInfo = this.post.ip_info();
      const formGroup = vdom.children.find((e) => e?.attrs?.className?.includes('Form-group') && e.children?.find?.((e) => e.tag === 'div'));

      if (!ipInfo || !formGroup) return;

      for (const child of formGroup.children) {
        const label = child.children.find((e) => e?.tag === 'label');
        const code = label && label.children.find((e) => e?.tag === 'code');

        const codeIndex = code && label.children.indexOf(code);
        if (!code) continue;

        const { description, threat, image } = getIPData(ipInfo);

        if (!code.attrs) code.attrs = {};
        code.attrs['data-threat-level'] = ipInfo.threatLevel();

        code.children[1] = (
          <Tooltip text={description + (!!threat ? ` (${threat})` : '')}>
            <span>{code.children[1]}</span>
          </Tooltip>
        );

        if (image) {
          label.children.splice(codeIndex, 0, image);
        }
      }

      if (ipInfo.zipCode() && ipInfo.countryCode()) {
        vdom.children.splice(
          2,
          0,
          <div className="Form-group">
            <ZipCodeMap zip={ipInfo.zipCode()} country={ipInfo.countryCode()} />
          </div>
        );
      }
    });
  }
}
