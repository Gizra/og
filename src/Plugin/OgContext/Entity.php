<?php

namespace Drupal\og\Plugin\OgContext;

use Drupal\og\Plugin\OgContextBase;

/**
 * @OgContext(
 *  id = "entity",
 *  title = "Entity",
 *  description = @Translation("Get the group from the current entity, by checking if it is a group or a group content entity.")
 * )
 */
class Entity extends OgContextBase {

}
