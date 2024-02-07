import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import PostMeta from 'flarum/common/components/PostMeta';
import Tooltip from 'flarum/common/components/Tooltip';
import { getIPData } from '../helpers/IPDataHelper';
import { handleCopyIP } from '../helpers/ClipboardHelper';
import ItemList from 'flarum/common/utils/ItemList';
import Button from 'flarum/common/components/Button';
import MapModal from '../components/MapModal';

export default function extendPostMeta() {
  extend(PostMeta.prototype, 'view', function (vdom) {
    const post = this.attrs.post;

    // Exit early if there's no post
    if (!post) return;

    const ipInformation = post.ip_info();

    // Exit early if there's no IP information for the post
    if (!ipInformation) return;

    // Extract dropdown from the VDOM
    const menuDropdown = vdom.children.find((e) => e.attrs?.className?.includes('dropdown-menu'));

    // Extract IP element for modification
    const ipElement = menuDropdown.children.find((e) => e.tag === 'span' && e.attrs?.className === 'PostMeta-ip');

    // Clear any default text from the IP element
    delete ipElement.text;

    // Construct the IP element with the tooltip and interactive behavior
    ipElement.children = [<div className="ip-container">{this.ipItems().toArray()}</div>];

    // If there's a threat level, add it as a data attribute for potential styling
    // TODO: move this to an Item?
    if (ipInformation.threatLevel) {
      ipElement.attrs['data-threat-level'] = ipInformation.threatLevel();
    }
  });

  PostMeta.prototype.ipItems = function () {
    const items = new ItemList();
    const post = this.attrs.post;
    const ipInformation = post.ip_info();
    const ipAddr = post.data.attributes.ipAddress;

    if (ipInformation && ipAddr) {
      const { description, threat, image } = getIPData(ipInformation);

      items.add(
        'ipInfo',
        <div className="ip-info">
          {image}
          <Tooltip text={`${description} ${threat ? `(${threat})` : ''}`}>
            <span onclick={handleCopyIP(ipAddr)}>{ipAddr}</span>
          </Tooltip>
        </div>,
        100
      );

      items.add(
        'mapButton',
        <Tooltip text={app.translator.trans('fof-geoip.forum.map_button_label')}>
          <Button
            icon="fas fa-map-marker-alt"
            className="Button Button--icon Button--link"
            onclick={() => app.modal.show(MapModal, { ipInfo: ipInformation, ipAddr: ipAddr })}
            aria-label={app.translator.trans('fof-geoip.forum.map_button_label')}
          />
        </Tooltip>,
        90
      );
    }

    return items;
  };
}
