<?php

/**
 * @file
 * Token hooks.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function dgi_members_token_info() {
  $config = \Drupal::config('dgi_members.settings');

  $parameter_tokens = [];
  foreach ($config->get('member_parameters') as $parameter) {
    $parameter_tokens[_dgi_members_get_token_name($parameter)] = [
      'name' => t('Active member from @parameter (dgi_members/Compound)', [
        '@parameter' => $parameter,
      ]),
      'type' => 'node',
    ];
  }

  return [
    'tokens' => [
      'node' => $parameter_tokens,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function dgi_members_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  if ($type != 'node') {
    return;
  }

  $output = [];

  $config = \Drupal::config('dgi_members.settings');
  /** @var \Drupal\dgi_members\DgiMembersEntityOperationsInterface $member_service */
  $member_service = \Drupal::service('dgi_members.entity_service');
  foreach ($config->get('member_parameters') as $parameter) {
    $mapped = _dgi_members_get_token_name($parameter);
    $prefix = "{$mapped}:";
    $to_chain = [];
    foreach ($tokens as $key => $value) {
      if (str_starts_with($key, $prefix)) {
        $to_chain[substr($key, strlen($prefix))] = $value;
      }
    }

    if ($to_chain) {
      $bubbleable_metadata->addCacheContexts([
        "url.query_args:{$parameter}",
      ]);
      $output += \Drupal::token()->generate(
        $type,
        $to_chain,
        ['node' => $member_service->retrieveActiveMember($parameter)] + $data,
        $options,
        $bubbleable_metadata,
      );
    }
  }

  return $output;
}

function _dgi_members_get_token_name($parameter) {
  return "dgi_member__{$parameter}";
}
