<?php

namespace Drupal\og\Plugin\Field\FieldType;

use Drupal\options\Plugin\Field\FieldType\ListStringItem;

/**
 * Plugin implementation of the 'list_string' field type.
 *
 * @FieldType(
 *   id = "og_list_string",
 *   label = @Translation("List (text)"),
 *   description = @Translation("This field stores text values from a list of allowed 'value => label' pairs, i.e. 'US States': IL => Illinois, IA => Iowa, IN => Indiana."),
 *   category = @Translation("Text"),
 *   default_widget = "options_select",
 *   default_formatter = "list_default",
 * )
 */
class OgListString extends ListStringItem {

}
