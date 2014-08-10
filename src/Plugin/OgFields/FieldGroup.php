<?php

namespace Drupal\og\Plugin\OgFields;

/**
 * Redirects to a message deletion form.
 *
 * @OgFields(
 *  id = "Email",
 *  label = @Translation("Email"),
 *  view_modes = {
 *    "email_subject" = @Translation("Notify - Email subject"),
 *    "email_body" = @Translation("Notify - Email body"),
 *  },
 * )
 */
class FieldGroup {

  function __construct() {
    dpm('a');
  }

}