/**
 * Add ipInfo to the include when loading posts (e.g. on user profile post stream).
 * PostListState explicitly sends include: ['user', 'discussion'], which overrides
 * the API default. Without this, ipInfo is never requested for users who can see it.
 */
export default function extendPostListState(): void;
