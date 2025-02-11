<?php

namespace Drupal\dgi_members\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\facets\Event\UrlCreated;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Facet event subscriber.
 */
class FacetEventSubscriber implements EventSubscriberInterface {

  use AutowireTrait;

  /**
   * This module's config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * Constructor.
   */
  public function __construct(
    #[Autowire(service: 'config.factory')]
    protected ConfigFactoryInterface $configFactory,
  ) {
    $this->config = $this->configFactory->get('dgi_members.settings');
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() : array {
    return [
      UrlCreated::class => 'urlCreated',
    ];
  }

  /**
   * Event handler; respond during facet URL generation to suppress parameters.
   *
   * @param \Drupal\facets\Event\UrlCreated $event
   *   The event to which we are responding.
   */
  public function urlCreated(UrlCreated $event) : void {
    $url = $event->getUrl();
    $query = $url->getOption('query');
    $changed = FALSE;
    foreach ($this->config->get('member_parameters') as $parameter) {
      if (array_key_exists($parameter, $query)) {
        if (!$changed) {
          $changed = TRUE;
        }
        unset($query[$parameter]);
      }
    }

    if ($changed) {
      $url->setOption('query', $query);
    }
  }

}
