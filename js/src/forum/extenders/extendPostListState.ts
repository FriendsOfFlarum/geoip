import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import PostListState from 'flarum/forum/states/PostListState';

/**
 * Add ipInfo to the include when loading posts (e.g. on user profile post stream).
 * PostListState explicitly sends include: ['user', 'discussion'], which overrides
 * the API default. Without this, ipInfo is never requested for users who can see it.
 */
export default function extendPostListState() {
  extend(PostListState.prototype, 'requestParams', function (params) {
    if (app.forum.attribute<boolean>('fofGeoipCanSeeIpInfo')) {
      params.include = [...(params.include || []), 'ipInfo'];
    }
  });
}
