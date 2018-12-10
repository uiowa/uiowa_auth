<?php

namespace Drupal\uiowa_auth\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('user.pass')) {
      $route->setRequirement('_access', 'FALSE');
    }

    if ($route = $collection->get('user.login')) {
      $route->setRequirement('_access', 'FALSE');
    }
  }

}
