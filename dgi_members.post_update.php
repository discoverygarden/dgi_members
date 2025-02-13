<?php

/**
 * @file
 * Post-update hooks.
 */

/**
 * Populate dgi_members.settings:member_parameters.
 */
function dgi_members_post_update_add_params() : void {
  $config = \Drupal::configFactory()->getEditable('dgi_members.settings');
  $config->set('member_parameters', [
    'active_member',
  ]);
  $config->save(TRUE);
}

/**
 * Populate dgi_members.settings:display_compound_object_metadata_by_default.
 */
function dgi_members_post_update_add_metadata_display_flag() : void {
  $config = \Drupal::configFactory()->getEditable('dgi_members.settings');
  // If this config has not been set, default to false.
  $display_compound_object_metadata = $config->get('display_compound_object_metadata_by_default') ?? FALSE;
  $config->set('display_compound_object_metadata_by_default', $display_compound_object_metadata);
  $config->save(TRUE);
}

/**
 * Ensure original pair of values in dgi_members.settings is populated.
 *
 * Introduction of config did not include update hooks to populate it, meaning
 * there might be broken configuration kicking around.
 *
 * @see https://github.com/discoverygarden/dgi_members/pull/15
 */
function dgi_members_post_update_set_initial_configs() : void {
  $properties = [
    'allow_compound_display_for_non_compound' => FALSE,
    'display_non_compound_compound_as_first_member_of_itself' => FALSE,
  ];

  $config = \Drupal::configFactory()->getEditable('dgi_members.settings');
  $dirty = FALSE;

  foreach ($properties as $property => $default_value) {
    $property_value = $config->get($property);
    if ($property_value === NULL) {
      if (!$dirty) {
        $dirty = TRUE;
      }
      $config->set($property, $default_value);
    }
  }

  if ($dirty) {
    $config->save(TRUE);
  }
}
