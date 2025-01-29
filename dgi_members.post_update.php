<?php

/**
 * @file
 * Post-update hooks.
 */

/**
 * Implements hook_post_update_NAME().
 */
function dgi_members_post_update_add_params(&$sandbox) {
  $config = \Drupal::configFactory()->getEditable('dgi_members.settings');
  $config->set('member_parameters', [
    'active_member',
  ]);
  $config->save(TRUE);
}

/**
 * Implements hook_post_update_NAME().
 */
function dgi_members_post_update_add_metadata_display_flag(&$sandbox) {
  $config = \Drupal::configFactory()->getEditable('dgi_members.settings');
  // If this config has not been set, default to false.
  $display_compound_object_metadata = $config->get('display_compound_object_metadata_by_default') ?? FALSE;
  $config->set('display_compound_object_metadata_by_default', $display_compound_object_metadata);
  $config->save(TRUE);
}
