<?php
// $Id$

/**
 * Menu callback to load settings form.
 */
function og_admin_settings() {
  // content types
  $is_configured = count(og_get_types('group')) && count(og_get_types('group_post'));
  if (!$is_configured) {
    form_set_error('content_types_table', t('You must designate at least one content type to act as a Group node and another as a Group post. <a href="!create">Create new content type</a> if needed.', array('!create' => url('admin/content/types/add'))));
  }
  $form['og_settings']['content_types'] = array(
    '#type' => 'fieldset', 
    '#title' => t('Content types'), 
    '#collapsible' => TRUE, 
    '#collapsed' => $is_configured,
  );
  $map = og_types_map();
  $header = array(t('Type'), t('Usage'), t('Operations'));
  foreach (node_get_types() as $type) {
    $type_url_str = str_replace('_', '-', $type->type);
    $usage = variable_get('og_content_type_usage_'. $type->type, 'omitted');
    $rows[] = array(
      $type->name,
      $map[$usage],
      l(t('Edit'), "admin/content/types/$type_url_str", array('query' => drupal_get_destination())),      
    );
  }
  $form['og_settings']['content_types']['content_types_table'] = array('#value' => theme('table', $header, $rows));

  
  $form['og_settings']['group_details'] = array(
    '#type' => 'fieldset', 
    '#title' => t('Group details'), 
    '#collapsible' => TRUE, 
    '#collapsed' => TRUE
  );
  // groups directory visibility
  $options = array(t('New groups don\'t appear in the groups directory. Administrators control the directory exclusively.'),
              t('New groups always appear in the groups directory.'),
              t('Group creator chooses whether her group appears in the directory. Defaults to %in.', array('%in' => t('in directory'))),
              t('Group creator chooses whether her group appears in the directory. Defaults to %out.', array('%out' => t('not in directory'))),
            );
  $form['og_settings']['group_details']['og_visibility_directory'] = array(
    '#type' => 'radios', 
    '#title' => t('Groups directory control'), 
    '#default_value' => variable_get('og_visibility_directory', OG_DIRECTORY_CHOOSE_TRUE), 
    '#description' =>t('OG admins always see the checkbox for adding a group to the %dir. Note that changing this setting has no effect on existing posts. Re-save those posts to acquire this new setting.', array('%dir' => t('groups directory'))), 
    '#options' => $options
  );
  
  // groups registration visibility
  $options = array(
    t('New groups don\'t appear on the registration form. Administrators control the form exclusively.'),
    t('New groups always appear on the registration form.'),
    t('Group creator chooses whether her group appears on the registration form. Defaults to %in.', array('%in' => t('on form'))),
    t('Group creator chooses whether her group appears on the registration form. Defaults to %out.', array('%out' => t('not on form'))),
  );
  $form['og_settings']['group_details']['og_visibility_registration'] = array(
    '#type' => 'radios', 
    '#title' => t('Registration form control'), 
    '#default_value' => variable_get('og_visibility_registration', OG_REGISTRATION_CHOOSE_FALSE), 
    '#description' =>t('OG admins always see the checkbox for adding a group to the %dir. Note that changing this setting has no effect on existing posts. Re-save those posts to acquire this new setting.', array('%dir' => t('registration form'))), 
    '#options' => $options
  );

  // email notifications default
  $options = array(
    OG_NOTIFICATION_SELECTIVE => t('New members are not subscribed to group email notifications by default. A member may choose to enable this from her profile page or her My membership page.'),
    OG_NOTIFICATION_ALWAYS => t('New members are subscribed to group email notifications by default. A member may choose to disable this from her profile page.'),
  );
  $form['og_settings']['group_details']['og_notification'] = array(
    '#type' => 'radios', 
    '#title' => t('Group email notifications'), 
    '#default_value' => variable_get('og_notification', OG_NOTIFICATION_ALWAYS), 
    '#description' =>t('Should new members automatically be notified via email when new content is posted to their group? Note that changing this setting has no effect on existing members.'), 
    '#options' => $options
  );
  
  // audience checkboxes
  $form['og_settings']['group_details']['og_audience_checkboxes'] = array(
    '#type' => 'checkbox', 
    '#title' => t('Audience checkboxes'), 
    '#default_value' => variable_get('og_audience_checkboxes', TRUE), 
    '#description' => t('Show each group that the user is a member of as a checkbox in the Audience section. This enables the member to place her post into multiple groups. If unchecked, simplify the user interface by omitting the checkboxes and assuming user wants to post into the current group. This simplification only applies to new nodes, and not to edits of existing nodes. Group administrators always see checkboxes.')
  );
  
  // audience required
  $options = array(t('optional'), t('required'));
  $form['og_settings']['group_details']['og_audience_required'] = array(
    '#type' => 'radios', 
    '#title' => t('Audience required'), 
    '#default_value' => variable_get('og_audience_required', 0), 
    '#options' => $options, 
    '#description' => t('Do you require that all (non administrator) posts be affiliated with a group? Note that changing this setting will affect existing posts when they are edited.')
  );
  unset($options);

  // audience home page - Views
  $options = og_get_available_views();
  $form['og_settings']['group_details']['og_home_page_view'] = array(
    '#type' => 'radios', 
    '#title' => t('Home page presentation'), 
    '#options' => $options, 
    '#default_value' => variable_get('og_home_page_view', 'og_ghp_ron'), 
    '#description' => t('Pick a View for your group home page. Only Views whose names start with <strong>og_ghp_</strong> are eligible. The View determines the layout of your group home page. You may alter the presentation using !theme. Also see the Theme section of the !README. Also note that group admins can override this presentation using the included <em>Organic Groups Panels</em> module.', array('!README' => og_readme(), '!theme' => l(t('typical Views themeing techniques'), 'http://drupal.org/node/42597'))),
  );
  
  // member pictures
  $form['og_settings']['group_details']['og_member_pics'] = array(
    '#type' => 'checkbox', 
    '#title' => t('Member pictures'), 
    '#default_value' => variable_get('og_member_pics', TRUE), 
    '#description' => t('Should member pictures be shown on the members page, the group members block, and group details block? You must also enable pictures in !user.', array('!user' => l(t('User configuration'), 'admin/user/settings')))
  );

  $form['og_settings']['email'] = array(
    '#type' => 'fieldset', 
    '#title' => t('Email settings'), 
    '#collapsible' => TRUE, 
    '#collapsed' => TRUE
  );

  $form['og_settings']['email']['og_email_notification_pattern'] = array(
    '#type' => 'textfield',
    '#title' => t('Format of From: field'),
    '#default_value' => variable_get("og_email_notification_pattern", '@user_name  <@site_mail>'),
    '#description' => t('Specify the format of the "From:" field on outgoing notifications. Available variables: @user_mail, @user_name, @site_mail, @site_name. Note that the @user_mail token reveals the author\'s email address. If the admin email examples above appear blank, you need to set your site email in the Site Configuration panel.'),
  );
  $form['og_settings']['email']['og_new_node_subject'] = array(
    '#type' => 'textfield', 
    '#title' => t('New content subject'), 
    '#description' => 'Subject of email for new content. Available variables: @group, !group_url, @type, @site, !content_url, !reply_url, @title, @subject, @node_full, @node_teaser, @username. %subject contains the comment title in the case of a comment but the node title in the case of a new post. @title is always the node title.', '#default_value' => _og_user_mail_text('og_new_node_subject')
  );
  $form['og_settings']['email']['og_new_node_body'] = array('#type' => 'textarea', '#title' => t('New content body'), '#rows' => 10, '#description' => 'Body of email for new content. Available variables: @group, !group_url, @type, @site, !content_url, !reply_url, @title, @subject, @node_full, @node_teaser, @username. @subject contains the comment title in the case of a comment but the node title in the case of a new post. %title is always the node title.', '#default_value' => _og_user_mail_text('og_new_node_body'));
  $form['og_settings']['email']['og_admin_email_body'] = array('#type' => 'textarea', '#title' => t('Group admin email body'), '#rows' => 10, '#description' => 'The body of the email sent to users from the group admin. Available variables: @group, @body, @site, !url_group, !url_unsubscribe', '#default_value' => _og_user_mail_text('og_admin_email_body'));
  $form['og_settings']['email']['og_approve_user_subject'] = array('#type' => 'textfield', '#title' => t('User approved email subject'), '#description' => 'The subject of the email sent to new approved members. Available variables: !group_url, @title', '#default_value' => _og_user_mail_text('og_approve_user_subject'));
  $form['og_settings']['email']['og_approve_user_body'] = array('#type' => 'textarea', '#title' => t('User approved email body'), '#rows' => 10, '#description' => 'The body of the email sent to new approved members. Available variables: !group_url, @title', '#default_value' => _og_user_mail_text('og_approve_user_body'));   
  $form['og_settings']['email']['og_deny_user_subject'] = array('#type' => 'textfield', '#title' => t('User denied email subject'), '#description' => 'The subject of the email sent to denied users. Available variables: !group_url, @title', '#default_value' => _og_user_mail_text('og_deny_user_subject'));
  $form['og_settings']['email']['og_deny_user_body'] = array('#type' => 'textarea', '#title' => t('User denied email body'), '#rows' => 10, '#description' => 'The body of the email sent to denied users. Available variables: !group_url, @title', '#default_value' => _og_user_mail_text('og_deny_user_body'));   
  $form['og_settings']['email']['og_invite_user_subject'] = array('#type' => 'textfield', '#title' => t('Invite user email subject'), '#description' => 'The subject of the email sent to users invited to join a group. Available variables: @group, @site, @description, !group_url, @body', '#default_value' => _og_user_mail_text('og_invite_user_subject'));
  $form['og_settings']['email']['og_invite_user_body'] = array('#type' => 'textarea', '#title' => t('Invite user email body'), '#rows' => 10, '#description' => 'The body of the email sent to users invited to join a group. Available variables: @group, @site, @description, !group_url, @body', '#default_value' => _og_user_mail_text('og_invite_user_body'));   
  $form['og_settings']['email']['og_request_user_subject'] = array('#type' => 'textfield', '#title' => t('Request user email subject'), '#description' => 'The subject of the email sent to a user\'s request to join a group. Available variables: @group, @username, !approve_url, !group_url', '#default_value' => _og_user_mail_text('og_request_user_subject'));
  $form['og_settings']['email']['og_request_user_body'] = array('#type' => 'textarea', '#title' => t('Request user email body'), '#rows' => 10, '#description' => 'The body of the email sent to a user\'s request to join a group. Available variables: @group, @username, !approve_url, !group_url', '#default_value' => _og_user_mail_text('og_request_user_body'));
  $form['og_settings']['email']['og_new_admin_subject'] = array('#type' => 'textfield', '#title' => t('New admin user email subject'), '#description' => 'The subject of the email sent to a new admin for a group. Available variables: @group, @username, !group_url', '#default_value' => _og_user_mail_text('og_new_admin_subject'));
  $form['og_settings']['email']['og_new_admin_body'] = array('#type' => 'textarea', '#title' => t('New admin user email body'), '#rows' => 10, '#description' => 'The body of the email sent to a new admin for a group. Available variables: @group, @username, !group_url, !invite_url', '#default_value' => _og_user_mail_text('og_new_admin_body'));

  return system_settings_form($form);
}

