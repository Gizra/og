<?php

declare(strict_types = 1);

namespace Drupal\og_access;

/**
 * Helper class for constants.
 */
class OgAccess {

  /**
   * The access realm of group member.
   */
  const OG_ACCESS_REALM = 'og_access';

  /**
   * Group public access field.
   */
  const OG_ACCESS_FIELD = 'group_access';

  /**
   * Group public access field.
   */
  const OG_ACCESS_CONTENT_FIELD = 'group_content_access';

  /**
   * Public group/group content access.
   */
  const OG_ACCESS_PUBLIC = 0;

  /**
   * Private group/group content access.
   */
  const OG_ACCESS_PRIVATE = 1;

}
