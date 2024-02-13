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
