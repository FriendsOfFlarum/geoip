import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import PostListState from 'flarum/forum/states/PostListState';

/**
 * Add ipInfo to the include when loading posts (e.g. on user profile post stream).
 * PostListState explicitly sends include: ['user', 'discussion'], which overrides
 * the API default. Without this, ipInfo is never requested and country flags
 * don't appear on the user profile.
 */
export default function extendPostListState() {
  extend(PostListState.prototype, 'requestParams', function (params) {
    if (app.forum.attribute<boolean>('fof-geoip.showFlag')) {
      params.include = [...(params.include || []), 'ipInfo'];
    }
  });
}
