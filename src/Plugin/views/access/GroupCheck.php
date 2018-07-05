<?php

namespace Drupal\og\Plugin\views\access;

use Drupal\views\Plugin\views\access\AccessPluginBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
* Access plugin that provides group-audience based access control.
*
* @ingroup views_access_plugins
*
* @ViewsAccess(
*   id = "group_check",
*   title = @Translation("Group Check"),
*   help = @Translation("Access will be granted to users with memberships to the audience for this view. The audience will be determine by the route.")
* )
*/
class GroupAudience extends AccessPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = FALSE;

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {

  }

  /**
  * {@inheritdoc}
  */
   public function summaryTitle() {
     return $this
       ->t('Organic Group Access');
   }

   /**
   * {@inheritdoc}
   */
   protected function defineOptions() {
     $options = parent::defineOptions();
     $options['entity_id'] = '';
     $options['entity_type'] = '';
     $options['permissions'] = '';
     return $options;
   }

   /**
   * {@inheritdoc}
   */
   public function buildOptionsForm(&$form, FormStateInterface $form_state) {
     parent::buildOptionsForm($form, $form_state);
     $values = $form_state->getValues();

     $form['entity_id'] = [
       '#type' => 'textfield',
       '#title' => t('Group ID'),
       '#description' => t('You may enter data from this view as per the "Replacement patterns".'),
       '#default_value' => $this->options['entity_id'],
     ];

     $entityTypes = [
       -1 => t('Select an Group Type')
     ];
     $entities = entity_get_bundles();
     foreach($entities as $id => $entityType) {
       foreach ($entityType as $bundle => $data) {
         if (Og::isGroup($id, $bundle)) {
             $entityTypes["{$id}|{$bundle}"] = "$entityType['label'] ({$data['label']})";
         }
       }
     }
     $form['entity_type_id'] = [
       '#type' => 'select',
       '#title' => t('Entity Type / Bundle'),
       '#description' => t('Select the entity type of the group.'),
       '#default_value' => isset($values['access_options']) ? $values['access_options']['entity_type_id'] : $this->options['entity_type'],
       '#options' => $entityTypes,
     ];

     $permissionOptions = [ -1 => t('Select a permission below.')];
     $event = new PermissionEvent($groupEntitySelect[0], $groupEntitySelect[1], []);
     $this->dispatcher->dispatch(PermissionEventInterface::EVENT_NAME, $event);
     $permissions = $event->getPermissions();
     $permissionOptions = [];
     foreach ($permissions as $id => $permission) {
       $permissionOptions[$id] = $permission->getName();
     }

     $form['permission'] = [
       '#type' => 'select',
       '#title' => t('Permission'),
       '#description' => t('Permission required for access.'),
       '#default_value' => $this->options['permission'],
       '#options' => $permissionOptions,
       '#attributes' => [
         'id' => 'edit-options-wrapper'
       ]
     ];
   }
}
