<?php

namespace Drupal\uiowa_auth\EventSubscriber;

use Drupal\externalauth\Authmap;
use Drupal\externalauth\Exception\ExternalAuthRegisterException;
use Drupal\samlauth\Event\SamlauthEvents;
use Drupal\samlauth\Event\SamlauthUserSyncEvent;
use Drupal\samlauth\Event\SamlauthUserLinkEvent;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;

/**
 * The uiowa event subscriber.
 */
class SamlauthSubscriber implements EventSubscriberInterface {

  protected $config;
  protected $logger;
  protected $authmap;

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
  public function __construct(ConfigFactoryInterface $config_factory, LoggerInterface $logger, Authmap $authmap) {
    $this->config = $config_factory;
    $this->logger = $logger;
    $this->authmap = $authmap;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SamlauthEvents::USER_SYNC][] = ['onUserSync', 100];
    $events[SamlauthEvents::USER_LINK][] = ['onUserLink', 100];
    return $events;
  }

  /**
   * User synchronization logic.
   *
   * @param \Drupal\samlauth\Event\SamlauthUserSyncEvent $event
   *   The SamlauthUserSyncEvent.
   */
  public function onUserSync(SamlauthUserSyncEvent $event) {
    $account = $event->getAccount();
    $attributes = $event->getAttributes();

    // Transform the authname to a HawkID.
    $attr = $this->config->get('samlauth.authentication')->get('user_name_attribute');
    $authname = $attributes[$attr][0];
    $hawkid = stristr($authname, '@uiowa.edu', TRUE);
    $account->setUsername($hawkid);

    // Revoke all previously-mapped roles for existing users.
    if ($account->isNew() === FALSE) {
      $row = $this->authmap->getAuthData($account->id(), 'samlauth');
      $data = unserialize($row['data']);

      foreach ($data['mapped'] as $rid) {
        $account->removeRole($rid);
        $this->logger->notice('Revoked previously-mapped role @role for user @user so mapping is re-evaluated.', ['@role' => $rid, '@user' => $account->getUsername()]);
      }
    }

    $attr = $this->config->get('uiowa_auth.settings')->get('member_of_attribute');
    $member_of = $attributes[$attr];
    $mappings = $this->config->get('uiowa_auth.settings')->get('role_mappings');

    foreach ($mappings as $rid => $dn) {
      if (!$account->hasRole($rid) && in_array($dn, $member_of)) {
        $account->addRole($rid);
        $this->logger->notice('Assigned role @role for user @user based on mapping @dn.', [
          '@role' => $rid,
          '@user' => $account->getAccountName(),
          '@dn' => $dn,
        ]);
      }
    }

    // Mark the account as changed so it is saved.
    $event->markAccountChanged();
  }

  /**
   * User link logic.
   *
   * @param \Drupal\samlauth\Event\SamlauthUserLinkEvent $event
   *   The SamlauthUserLinkEvent.
   *
   * @throws ExternalAuthRegisterException
   */
  public function onUserLink(SamlauthUserLinkEvent $event) {
    // Prevent account creation for unlinked accounts.
    if ($event->getLinkedAccount() === FALSE) {
      // @todo: log a message here.
      throw new ExternalAuthRegisterException();
    }
  }

}
