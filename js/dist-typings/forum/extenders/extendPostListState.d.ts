/**
 * Add ipInfo to the include when loading posts (e.g. on user profile post stream).
 * PostListState explicitly sends include: ['user', 'discussion'], which overrides
 * the API default. Without this, ipInfo is never requested and country flags
 * don't appear on the user profile.
 */
export default function extendPostListState(): void;
