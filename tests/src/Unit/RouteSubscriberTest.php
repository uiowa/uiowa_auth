<?php

namespace Drupal\Tests\uiowa_auth\Unit;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\uiowa_auth\EventSubscriber\RouteSubscriber;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests for the route subscriber.
 */
class RouteSubscriberTest extends UnitTestCase {

  /**
   * Run the core user routes through the route subscriber.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The altered route collection.
   */
  protected function alterUserRoutes() {
    $collection = new RouteCollection();
    $collection->add('user.login', new Route('/user/login', [], ['_user_is_logged_in' => 'FALSE']));
    $collection->add('user.pass', new Route('/user/password', [], ['_access' => 'TRUE']));
    $collection->add('user.login.http', new Route('/user/login', [], [
      '_user_is_logged_in' => 'FALSE',
      '_format' => 'json',
    ], [], '', [], ['POST']));
    $collection->add('user.pass.http', new Route('/user/password', [], [
      '_access' => 'TRUE',
      '_format' => 'json',
    ], [], '', [], ['POST']));
    $collection->add('user.reset.login', new Route('/user/reset/{uid}/{timestamp}/{hash}/login', [], [
      '_user_is_logged_in' => 'FALSE',
    ]));

    $subscriber = new RouteSubscriber($this->createMock(AccountInterface::class));
    $subscriber->onAlterRoutes(new RouteBuildEvent($collection));

    return $collection;
  }

  /**
   * Test local password login and reset routes are blocked.
   *
   * @dataProvider blockedRoutes
   */
  public function testPasswordRoutesBlocked($name) {
    $route = $this->alterUserRoutes()->get($name);
    $this->assertEquals('FALSE', $route->getRequirement('_access'));
  }

  /**
   * Data provider for blocked routes.
   *
   * @see testPasswordRoutesBlocked()
   */
  public static function blockedRoutes() {
    return [
      ['user.pass'],
      ['user.pass.http'],
      ['user.login.http'],
    ];
  }

  /**
   * Test one-time login links still work, e.g. for drush uli.
   */
  public function testResetLoginNotBlocked() {
    $route = $this->alterUserRoutes()->get('user.reset.login');
    $this->assertNull($route->getRequirement('_access'));
  }

  /**
   * Test the HTML login route uses the legacy login controller.
   */
  public function testLoginRouteController() {
    $route = $this->alterUserRoutes()->get('user.login');
    $this->assertEquals('Drupal\uiowa_auth\Controller\LegacyLoginController::build', $route->getDefault('_controller'));
  }

}
