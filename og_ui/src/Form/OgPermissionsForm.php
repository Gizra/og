<?php

declare(strict_types = 1);

namespace Drupal\og_ui\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\og\GroupPermission;
use Drupal\og\GroupTypeManager;
use Drupal\og\Og;
use Drupal\og\OgRoleManagerInterface;
use Drupal\og\PermissionManagerInterface;
use Drupal\user\RoleInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provide the group permissions form.
 *
 * @see \Drupal\user\Form\UserPermissionsForm
 */
class OgPermissionsForm extends FormBase {

  /**
   * The permission manager.
   *
   * @var \Drupal\og\PermissionManagerInterface
   */
  protected $permissionManager;

  /**
   * The role manager.
   *
   * @var \Drupal\og\OgRoleManagerInterface
   */
  protected $roleManager;

  /**
   * The group type manager.
   *
   * @var \Drupal\og\GroupTypeManager
   */
  protected $groupTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The group roles.
   *
   * @var \Drupal\og\OgRoleInterface[]|\Drupal\user\RoleInterface[]
   */
  protected $roles;

  /**
   * Constructs a new OgPermissionsForm.
   *
   * @param \Drupal\og\PermissionManagerInterface $permission_manager
   *   The OG permission handler.
   * @param \Drupal\og\OgRoleManagerInterface $role_manager
   *   The OG role manager.
   * @param \Drupal\og\GroupTypeManager $group_type_manager
   *   The group type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(PermissionManagerInterface $permission_manager, OgRoleManagerInterface $role_manager, GroupTypeManager $group_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    $this->permissionManager = $permission_manager;
    $this->roleManager = $role_manager;
    $this->groupTypeManager = $group_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.permission_manager'),
      $container->get('og.role_manager'),
      $container->get('og.group_type_manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_permissions';
  }

  /**
   * Title callback for the group permissions page.
   *
   * @param string $entity_type_id
   *   The group entity type id.
   * @param string $bundle_id
   *   The group bundle id.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The group permission title.
   */
  public function titleCallback($entity_type_id, $bundle_id) {
    return $this->t('@bundle permissions', [
      '@bundle' => $this->entityTypeBundleInfo->getBundleInfo($entity_type_id)[$bundle_id]['label'],
    ]);
  }

  /**
   * Returns the group roles to display in the form.
   *
   * @param string $entity_type_id
   *   The group entity type id.
   * @param string $bundle_id
   *   The group entity bundle id.
   *
   * @return array
   *   The group roles.
   */
  protected function getGroupRoles($entity_type_id, $bundle_id) {
    if (empty($this->roles)) {
      $this->roles = $this->roleManager->getRolesByBundle($entity_type_id, $bundle_id);
    }

    return $this->roles;
  }

  /**
   * The group permissions form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $entity_type_id
   *   The group entity type id.
   * @param string $bundle_id
   *   The group bundle id.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = '', $bundle_id = '') {
    // Return a 404 error when this is not a group.
    if (!Og::isGroup($entity_type_id, $bundle_id)) {
      throw new NotFoundHttpException();
    }

    // Render the link for hiding descriptions.
    $form['system_compact_link'] = [
      '#id' => FALSE,
      '#type' => 'system_compact_link',
    ];

    $hide_descriptions = system_admin_compact_mode();

    $form['permissions'] = [
      '#type' => 'table',
      '#header' => [$this->t('Permission')],
      '#id' => 'permissions',
      '#attributes' => ['class' => ['permissions', 'js-permissions']],
      '#sticky' => TRUE,
    ];

    $roles = $this->getGroupRoles($entity_type_id, $bundle_id);

    uasort($roles, function (RoleInterface $a, RoleInterface $b) {
      if ($a->getWeight() == $b->getWeight()) {
        return 0;
      }
      return ($a->getWeight() < $b->getWeight()) ? -1 : 1;
    });

    /** @var \Drupal\og\Entity\OgRole $role */
    foreach ($roles as $role) {
      $form['permissions']['#header'][] = [
        'data' => $role->getLabel(),
        'class' => ['checkbox'],
      ];
    }

    $bundles = $this->groupTypeManager->getGroupContentBundleIdsByGroupBundle($entity_type_id, $bundle_id);
    $group_permissions = $this->permissionManager->getDefaultGroupPermissions($entity_type_id, $bundle_id);
    $group_content_permissions = $this->permissionManager->getDefaultEntityOperationPermissions($entity_type_id, $bundle_id, $bundles);

    $permissions_by_provider = [
      'Group' => [],
      'Group content' => [],
    ];

    foreach ($group_permissions as $permission) {
      if (!empty($permission->getProvider())) {
        $permissions_by_provider[$permission->getProvider()][$permission->getName()] = $permission;
      }
      else {
        $permissions_by_provider['Group'][$permission->getName()] = $permission;
      }
    }

    foreach ($group_content_permissions as $permission) {
      if (!empty($permission->getProvider())) {
        $permissions_by_provider[$permission->getProvider()][$permission->getName()] = $permission;
      }
      else {
        $permissions_by_provider['Group content'][$permission->getName()] = $permission;
      }
    }

    foreach ($permissions_by_provider as $provider => $permissions) {
      // Skip empty permission provider groups.
      if (empty($permissions)) {
        continue;
      }

      $form['permissions'][$provider] = [
        [
          '#wrapper_attributes' => [
            'colspan' => count($roles) + 1,
            'class' => ['module'],
            'id' => 'provider-' . Html::getId($provider),
          ],
          '#markup' => $provider,
        ],
      ];

      /** @var \Drupal\og\Permission $permission */
      foreach ($permissions as $permission_name => $permission) {
        // Fill in default values for the permission.
        $perm_item = [
          'title' => $permission->getTitle(),
          'description' => $permission->getDescription(),
          'restrict access' => $permission->getRestrictAccess(),
          'warning' => $permission->getRestrictAccess() ? $this->t('Warning: Give to trusted roles only; this permission has security implications.') : '',
        ];

        $form['permissions'][$permission_name]['description'] = [
          '#type' => 'inline_template',
          '#template' => '<div class="permission"><span class="title">{{ title }}</span>{% if description or warning %}<div class="description">{% if warning %}<em class="permission-warning">{{ warning }}</em> {% endif %}{{ description }}</div>{% endif %}</div>',
          '#context' => [
            'title' => $perm_item['title'],
          ],
        ];

        // Show the permission description.
        if (!$hide_descriptions) {
          $form['permissions'][$permission_name]['description']['#context']['description'] = $perm_item['description'];
          $form['permissions'][$permission_name]['description']['#context']['warning'] = $perm_item['warning'];
        }

        foreach ($roles as $rid => $role) {
          $rid_simple = $role->getName();

          // The roles property indicates which roles the permission applies to.
          $permission_applies = TRUE;
          if ($permission instanceof GroupPermission) {
            $target_roles = $permission->getApplicableRoles();
            $permission_applies = empty($target_roles) || in_array($rid_simple, $target_roles);
          }

          if ($permission_applies) {
            $form['permissions'][$permission_name][$rid] = [
              '#title' => $role->getName() . ': ' . $perm_item['title'],
              '#title_display' => 'invisible',
              '#wrapper_attributes' => [
                'class' => ['checkbox'],
              ],
              '#type' => 'checkbox',
              '#default_value' => $role->hasPermission($permission_name) ? 1 : 0,
              '#attributes' => ['class' => ['rid-' . $rid, 'js-rid-' . $rid]],
              '#parents' => [$rid, $permission_name],
            ];

            // Show a column of disabled but checked checkboxes.
            // Only applies to admins or default roles.
            if ($roles[$rid]->get('is_admin') ||
                in_array($rid_simple, $permission->getDefaultRoles())) {
              $form['permissions'][$permission_name][$rid]['#disabled'] = TRUE;
              $form['permissions'][$permission_name][$rid]['#default_value'] = TRUE;
            }
          }
          else {
            $form['permissions'][$permission_name][$rid] = [
              '#title' => $role->getName() . ': ' . $perm_item['title'],
              '#title_display' => 'invisible',
              '#wrapper_attributes' => [
                'class' => ['checkbox'],
              ],
              '#markup' => '-',
            ];
          }
        }
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save permissions'),
      '#button_type' => 'primary',
    ];

    $form['#attached']['library'][] = 'user/drupal.user.permissions';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\og\Entity\OgRole $roles */
    foreach ($this->roles as $rid => $role) {
      if (!$form_state->hasValue($rid)) {
        continue;
      }

      $permissions = $form_state->getValue($rid);
      foreach ($permissions as $permission => $grant) {
        if ($grant) {
          $role->grantPermission($permission);
        }
        else {
          $role->revokePermission($permission);
        }
      }

      $role->save();
    }

    $this->messenger()->addMessage($this->t('The changes have been saved.'));
  }

}
