<?php

namespace Drupal\og\Plugin\OgDeleteOrphans;

use Drupal\Core\Form\FormStateInterface;
use Drupal\og\OgDeleteOrphansBase;

/**
 * Performs a batch deletion of orphans.
 *
 * @OgDeleteOrphans(
 *  id = "batch",
 *  label = @Translation("Batch", context = "OgDeleteOrphans"),
 *  description = @Translation("The deletion is done in a batch operation. Good for large websites with a lot of content."),
 * )
 */
class Batch extends OgDeleteOrphansBase {

  /**
   * {@inheritdoc}
   */
  public function process() {
    $queue = $this->getQueue();
    $item = $queue->claimItem(0);
    $data = $item->data;
    $this->deleteOrphan($data['type'], $data['id']);
    $queue->deleteItem($item);
  }

  /**
   * {@inheritdoc}
   */
  public function configurationForm(array $form, FormStateInterface $form_state) {
    $count = $this->getQueue()->numberOfItems();
    return [
      '#type' => 'fieldset',
      '#title' => $this->t('Batch options'),
      'info' => [
        '#markup' => '<p>' . $this->t('There are :count orphans waiting to be deleted.', [
          ':count' => $count,
        ]) . '</p>',
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Start batch deletion'),
        '#submit' => ['\Drupal\og\Plugin\OgDeleteOrphans\Batch::batchSubmit'],
        '#disabled' => !(bool) $count,
      ],
    ];
  }

  /**
   * Submit handler for ::configurationForm().
   */
  public static function batchSubmit($form, FormStateInterface $form_state) {
    $batch = [];
    $steps = \Drupal::queue('og_orphaned_group_content')->numberOfItems();
    for ($i = 0; $i < $steps; $i++) {
      $batch['operations'][] = ['\Drupal\og\Plugin\OgDeleteOrphans\Batch::step', []];
    }
    batch_set($batch);
  }

  /**
   * Batch step definition callback to process one queue item.
   */
  public static function step($context) {
    if (!empty($context['interrupted'])) {
      return;
    }
    \Drupal::getContainer()->get('plugin.manager.og.delete_orphans')->createInstance('batch', [])->process();
  }

}
