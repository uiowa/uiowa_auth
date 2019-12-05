<?php

namespace Drupal\Tests\uiowa_auth\Kernel;

use Drupal\uiowa_auth\EventSubscriber\ExternalAuthSubscriber;
use Drupal\user\Entity\User;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Test description.
 *
 * @group kernel
 */
class ExternalauthSubscriberTest extends EntityKernelTestBase {
  /**
   * The config service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The user account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * The authmap service.
   *
   * @var \Drupal\externalauth\Authmap
   */
  protected $authmap;

  /**
   * The SamlauthUserSyncEvent.
   *
   * @var \Drupal\samlauth\Event\SamlauthUserSyncEvent
   */
  protected $event;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['uiowa_auth', 'externalauth', 'samlauth'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    file_put_contents($this->root . '/../vendor/onelogin/php-saml/certs/idp.crt', 'foo');
    $this->installConfig(['uiowa_auth', 'externalauth', 'samlauth']);
    $this->installSchema('externalauth', ['authmap']);

    $this->config = $this->container->get('config.factory');
    $this->config->getEditable('samlauth.authentication')->set('user_name_attribute', 'name')->save();

    $this->config->getEditable('uiowa_auth.settings')->set('role_mappings', [
      'webmaster|groups|DN=web',
      'webmaster|groups|DN=web2',
      'editor|groups|DN=edit',
    ])->save();

    $this->logger = $this->createMock('Psr\Log\LoggerInterface');
    $this->authmap = $this->container->get('externalauth.authmap');
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    $this->event = $this->createMock('Drupal\externalauth\Event\ExternalAuthLoginEvent');

    $this->event->expects($this->any())
      ->method('getProvider')
      ->will($this->returnValue('samlauth'));

    $this->event->expects($this->any())
      ->method('getAuthname')
      ->will($this->returnValue('foo'));
  }

  /**
   * Test that rids are stored only once in authmap data.
   */
  public function testAuthmapDataIsDeduped() {
    $account = User::create([
      'name' => $this->randomMachineName(),
      'status' => 1,
    ]);

    $account->addRole('webmaster');
    $account->addRole('editor');
    $account->save();

    $this->event->expects($this->any())
      ->method('getAccount')
      ->will($this->returnValue($account));

    $sut = new ExternalAuthSubscriber($this->config, $this->logger, $this->authmap);
    $sut->onUserLogin($this->event);
    $data = unserialize($this->authmap->getAuthData($account->id(), 'samlauth')['data']);
    $count = array_count_values($data['uiowa_auth_mappings']);
    $this->assertEquals(1, $count['webmaster']);
  }

}
