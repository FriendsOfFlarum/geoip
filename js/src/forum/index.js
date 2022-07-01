import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import PostMeta from 'flarum/common/components/PostMeta';
import Model from 'flarum/common/Model';
import Tooltip from 'flarum/common/components/Tooltip';
import copyToClipboard from './util/copyToClipboard';
import getFlagEmojiUrl from './util/getFlagEmojiUrl';
import ZipCodeMap from './components/ZipCodeMap';
import IPInfo from './models/IPInfo';

const getIPData = (ipInfo) => {
    const data = {
        description: ipInfo.organization() || ipInfo.isp() || ipInfo.error() || '',
        threat: ipInfo.threatTypes() && ipInfo.threatTypes().join(', '),
    };

    if (ipInfo.countryCode()) {
        const url = getFlagEmojiUrl(ipInfo.countryCode());

        data.image = url && (
            <Tooltip text={ipInfo.countryCode()}>
                <img src={url} alt={ipInfo.countryCode()} height="16" loading="lazy" />
            </Tooltip>
        );
    }

    return data;
};

const copyIP = (ip) =>
    function () {
        copyToClipboard(ip);

        app.alerts.show({ type: 'success' }, app.translator.trans('fof-geoip.forum.alerts.ip_copied'));
    };

app.initializers.add('fof/geoip', () => {
    app.store.models.ip_info = IPInfo;
    app.store.models.posts.prototype.ipInfo = Model.hasOne('ip_info');

    extend(PostMeta.prototype, 'view', function (vdom) {
        if (!this.attrs.post) return;

        const ipInfo = this.attrs.post.ipInfo();
        const ipAddress = this.attrs.post.data.attributes.ipAddress;

        if (!ipInfo) return;

        const dropdownMenu = vdom.children.find((e) => e.attrs && e.attrs.className && e.attrs.className.includes('dropdown-menu'));
        const el = dropdownMenu.children.find((e) => e.tag === 'span' && e.attrs && e.attrs.className === 'PostMeta-ip');

        const { description, threat, image } = getIPData(ipInfo);

        delete el.text;

        el.children = [
            <Tooltip text={description + (!!threat ? ` (${threat})` : '')}>
                <span onclick={ipAddress && copyIP(ipAddress)}>{ipAddress}</span>
            </Tooltip>,
        ];

        if (image) {
            el.children.unshift(image);
        }

        if (ipInfo.threatLevel) {
            el.attrs['data-threat-level'] = ipInfo.threatLevel();
        }
    });

    const BanIPModal = flarum.core.compat['fof/ban-ips/components/BanIPModal'];

    if (BanIPModal) {
        extend(BanIPModal.prototype, 'content', function (vdom) {
            if (!this.post || !this.post.ipAddress()) return;

            const ipInfo = this.post.ipInfo();
            const formGroup = vdom.children.find(
                (e) =>
                    e &&
                    e.attrs &&
                    e.attrs.className &&
                    e.attrs.className.includes('Form-group') &&
                    e.children &&
                    Array.isArray(e.children) &&
                    e.children.find((e) => e.tag === 'div')
            );

            if (!ipInfo || !formGroup) return;

            for (const child of formGroup.children) {
                const label = child.children.find((e) => e && e.tag === 'label');
                const code = label && label.children.find((e) => e && e.tag === 'code');

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
});
