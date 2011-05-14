<?php
/**
 * @file
 * og.rules_defaults.inc
 */

/**
 * Implementation of hook_default_rules_configuration().
 */
function og_default_rules_configuration() {
  $items = array();
  $items['rules_og_member_active'] = entity_import('rules_config', '{ "rules_og_member_active" : {
      "LABEL" : "OG member subscribe (Active)",
      "PLUGIN" : "reaction rule",
      "REQUIRES" : [ "rules", "og" ],
      "ON" : [ "og_membership_insert" ],
      "IF" : [
        { "data_is" : { "data" : [ "og-membership:entity-type" ], "value" : "user" } },
        { "data_is" : { "data" : [ "og-membership:state" ], "value" : "1" } }
      ],
      "DO" : [
        { "entity_fetch" : {
            "USING" : { "type" : "user", "id" : [ "og-membership:etid" ] },
            "PROVIDE" : { "entity_fetched" : { "subscribed_user" : "Subscribed user" } }
          }
        },
        { "mail" : {
            "to" : [ "subscribed-user:mail" ],
            "subject" : "Your membership request was approved for \'[og_membership:group:label]\'\\r\\n",
            "message" : "[subscribed_user:name],\\r\\n\\r\\nYou are now a member in the group \'[og_membership:group:label]\' located at [og_membership:group:url]",
            "from" : ""
          }
        },
        { "og_get_managers_from_group" : {
            "USING" : { "group" : [ "og-membership:group" ] },
            "PROVIDE" : { "group_manager" : { "group_manager" : "Group manager" } }
          }
        },
        { "mail" : {
            "to" : [ "group-manager:mail" ],
            "subject" : "[subscribed_user:name] joined [og_membership:group:label]",
            "message" : "[group_manager:name],\\r\\n\\r\\n[subscribed_user:name] joined [og_membership:group:label] in [og_membership:group:url]",
            "from" : ""
          }
        }
      ]
    }
  }');
  $items['rules_og_member_pending'] = entity_import('rules_config', '{ "rules_og_member_pending" : {
      "LABEL" : "OG member subscribe (Pending)",
      "PLUGIN" : "reaction rule",
      "REQUIRES" : [ "rules", "og" ],
      "ON" : [ "og_membership_insert" ],
      "IF" : [
        { "data_is" : { "data" : [ "og-membership:entity-type" ], "value" : "user" } },
        { "data_is" : { "data" : [ "og-membership:state" ], "value" : "2" } }
      ],
      "DO" : [
        { "entity_fetch" : {
            "USING" : { "type" : "user", "id" : [ "og-membership:etid" ] },
            "PROVIDE" : { "entity_fetched" : { "subscribed_user" : "Subscribed user" } }
          }
        },
        { "mail" : {
            "to" : [ "subscribed-user:mail" ],
            "subject" : "Your membership request for \'[og_membership:group:label]\'\\r\\n",
            "message" : "[subscribed_user:name],\\r\\n\\r\\nYour membership to group \'[og_membership:group:label]\' located at [og_membership:group:url] is pending approval.",
            "from" : ""
          }
        },
        { "og_get_managers_from_group" : {
            "USING" : { "group" : [ "og-membership:group" ] },
            "PROVIDE" : { "group_manager" : { "group_manager" : "Group manager" } }
          }
        },
        { "mail" : {
            "to" : [ "group-manager:mail" ],
            "subject" : "[subscribed_user:name] membership request [og_membership:group:label]",
            "message" : "[group_manager:name],\\r\\n\\r\\n[subscribed_user:name] requests membership for group \'[og_membership:group:label]\' in [og_membership:group:url].\\r\\n\\r\\n[og_membership:og-membership-request]",
            "from" : [ "" ]
          }
        }
      ]
    }
  }');
  return $items;
}
