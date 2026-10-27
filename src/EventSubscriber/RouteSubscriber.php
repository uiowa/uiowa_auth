<?php

namespace Drupal\uiowa_auth\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {
  /**
   * The current user.
   *
   * @var Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Route subscriber constructor.
   *
   * @param Drupal\Core\Sessions\AccountInterface $user
   *   The current user.
   */
  public function __construct(AccountInterface $user) {
    $this->user = $user;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Block local password login and reset on both the HTML and JSON routes,
    // so HawkID is the only way to log in.
    foreach (['user.pass', 'user.pass.http', 'user.login.http'] as $name) {
      if ($route = $collection->get($name)) {
        $route->setRequirement('_access', 'FALSE');
      }
    }

    if ($route = $collection->get('user.login')) {
      $route->setDefaults([
        '_controller' => 'Drupal\uiowa_auth\Controller\LegacyLoginController::build',
      ]);
    }
  }

}
