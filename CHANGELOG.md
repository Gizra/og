# Changelog

All notable changes to the Organic Groups project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0-alpha3]

Third alpha release for Organic Groups. This release brings many small improvements and adds support for Drupal 8.7.

Starting with this release PHP 7.1 or higher is required,

## Improvements

* #311 Throw an exception when calling MembershipManager::getGroupIds() with an entity which is not group content.
* #433 Remove obsolete "Use queue" setting.
* #450 Use Cache API to store the group relation map.
* #455 Early exit if there are no memberships to process.
* #458 Show UID in LogicException for existing membership.
* #462 Add default access check.
* #467 Use full pager on member overview.
* #468 Small optimization for adding/removing roles to/from memberships.
* #472 Provide a list cache tag for group memberships.
* #473 Add support for Drupal 8.7.
* #475 Provide a method to retrieve the membership IDs of a given group.
* #482 Allow to retrieve groups and roles by permissions.
* #483 Provide a list cache tag for group content.
* #489 Declare PHP 7.1 as the minimum supported version.
* #491 Update URL of repository now that Drupal has moved to Gitlab.
* #492 Add Slack to support channels.
* #493 Introduce strict typing in the OgMembership entity.
* #495 Use better way to check if an entity represents a user.
* #496 Adopt new module dependency format.
* #498 Improve documentation.
* #514 Fix coding standards violations.
* #516 Do not require to pass full OgRole objects when we only need the ID.

## Bug fixes

* #443 OgRole actions config throws error on import.
* #461 Fix minor typo in subscribe form.
* #465 Fix broken/missing handler for roles in OG membership view.
* #471 Fix unnecessarily gendered language in comments.
* #476 Only allow users with "access user profiles" permission to access the members overview.
* #479 MembershipManager returns memberships of the wrong group.
* #480 MembershipManager still returns memberships after a user is deleted.
* #503 Fix random failure when time service is instantiated one second too late
* #509 Do not invalidate group content list cache tags when the group itself changes.
## [1.0-alpha2]

Second alpha release of the Drupal 8 port of Organic Groups. This release adds support for Drupal 8.6 and PHP 7.3.

### New features

* #451 Provide a method on the `MembershipManager` to retrieve all group memberships filtered by role.

### Improvements

* #447 Add support for Drupal 8.6 and PHP 7.3. Start preliminary testing on Drupal 8.7.
* #446 Avoid double caching of `OgMembership` entities. This reduces memory consumption.
* #449 Clarify how to work with membership states by providing a new constant and improving documentation.
* #437 Streamline the Travis CI installation procedure.


## [1.0-alpha1]

Initial alpha release of the Drupal 8 port of Organic Groups.


[Unreleased]: https://github.com/Gizra/og/compare/8.x-1.0-alpha1...8.x-1.x
[1.0-alpha3]: https://github.com/Gizra/og/compare/8.x-1.0-alpha2...8.x-1.0-alpha3
[1.0-alpha2]: https://github.com/Gizra/og/compare/8.x-1.0-alpha1...8.x-1.0-alpha2
[1.0-alpha1]: https://github.com/Gizra/og/releases/tag/8.x-1.0-alpha1
