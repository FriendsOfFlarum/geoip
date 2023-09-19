import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import PostMeta from 'flarum/common/components/PostMeta';
import Tooltip from 'flarum/common/components/Tooltip';
import { getIPData } from '../helpers/IPDataHelper';
import { handleCopyIP } from '../helpers/ClipboardHelper';

export default function extendPostMeta() {
  extend(PostMeta.prototype, 'view', function (vdom) {
    const post = this.attrs.post;
    if (!post) return;

    const ipInformation = post.ip_info();
    const ipAddr = post.data.attributes.ipAddress;

    if (!ipInformation) return;

    const menuDropdown = vdom.children.find((e) => e.attrs?.className?.includes('dropdown-menu'));
    const ipElement = menuDropdown.children.find((e) => e.tag === 'span' && e.attrs?.className === 'PostMeta-ip');

    const { description, threat, image } = getIPData(ipInformation);

    delete ipElement.text;

    ipElement.children = [
      <Tooltip text={description + (!!threat ? ` (${threat})` : '')}>
        <span onclick={ipAddr && handleCopyIP(ipAddr)}>{ipAddr}</span>
      </Tooltip>,
    ];

    if (image) {
      ipElement.children.unshift(image);
    }

    if (ipInformation.threatLevel) {
      ipElement.attrs['data-threat-level'] = ipInformation.threatLevel();
    }
  });
}
