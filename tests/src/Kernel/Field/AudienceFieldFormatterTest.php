<?php

namespace Drupal\Tests\og\Kernel\Field;

use Drupal\KernelTests\KernelTestBase;
use Drupal\og\OgGroupAudienceHelperInterface;

/**
 * Test that formatters for entity reference can be applied to audience fields.
 *
 * @group og
 */
class AudienceFieldFormatterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field', 'og'];

  /**
   * Testing og_field_formatter_info_alter().
   */
  public function testFieldFormatterInfoAlter() {
    /** @var \Drupal\Core\Field\FormatterPluginManager $formatter_manager */
    $formatter_manager = \Drupal::getContainer()->get('plugin.manager.field.formatter');

    $expected = [
      'entity_reference_entity_id',
      'entity_reference_entity_view',
      'entity_reference_label',
    ];

    $actual = array_keys($formatter_manager->getOptions(OgGroupAudienceHelperInterface::GROUP_REFERENCE));
    sort($actual);
    $this->assertEquals($expected, $actual);
  }

}
