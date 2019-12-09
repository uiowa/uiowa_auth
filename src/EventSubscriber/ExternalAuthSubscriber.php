<?php

namespace Drupal\uiowa_auth\EventSubscriber;

use Drupal\externalauth\Authmap;
use Drupal\externalauth\Event\ExternalAuthEvents;
use Drupal\externalauth\Event\ExternalAuthLoginEvent;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\uiowa_auth\RoleMappings;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;
use Drupal\samlauth\SamlService;

/**
 * The uiowa event subscriber.
 */
class ExternalAuthSubscriber implements EventSubscriberInterface {

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
   * The externalauth authmap service.
   *
   * @var \Drupal\externalauth\Authmap
   */
  protected $authmap;

  protected $saml;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Configuration factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger interface.
   * @param \Drupal\externalauth\Authmap $authmap
   *   Authmap service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerInterface $logger, Authmap $authmap, SamlService $saml) {
    $this->config = $config_factory->get('uiowa_auth.settings');
    $this->logger = $logger;
    $this->authmap = $authmap;
    $this->saml = $saml;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ExternalAuthEvents::LOGIN][] = ['onUserLogin'];
    return $events;
  }

  /**
   * Authmap alter logic.
   *
   * @param \Drupal\externalauth\Event\ExternalAuthLoginEvent $event
   *   The ExternalAuthLoginEvent.
   */
  public function onUserLogin(ExternalAuthLoginEvent $event) {
    $account = $event->getAccount();
    $provider = $event->getProvider();
    $authname = $event->getAuthname();
    $attributes = $this->saml->getAttributes();
    $this->logger->debug('<pre><code>' . print_r($attributes, TRUE) . '</code></pre>');


    $mappings = $this->config->get('role_mappings');

    $data = [
      'uiowa_auth_mappings' => [],
    ];

    foreach (RoleMappings::generate($mappings) as $mapping) {
      if (in_array($mapping['value'], $attributes[$mapping['attr']])) {
        $this->logger->debug('<pre><code>' . print_r($attributes[$mapping['attr']], TRUE) . '</code></pre>');
        $data['uiowa_auth_mappings'][] = $mapping['rid'];
      }
    }

    $this->authmap->save($account, $provider, $authname, $data);
    $this->logger->notice('Saved mapped roles for @user to authmap table.', ['@user' => $authname]);
  }

}
