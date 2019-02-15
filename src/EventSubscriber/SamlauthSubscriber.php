<?php

namespace Drupal\uiowa_auth\EventSubscriber;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManager;
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
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Configuration factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger interface.
   * @param \Drupal\externalauth\Authmap $authmap
   *   Authmap service.
   * @param \Drupal\Core\Entity\EntityTypeManager
   *   The EntityTypeManager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerInterface $logger, Authmap $authmap, EntityTypeManager $entityTypeManager) {
    $this->config = $config_factory;
    $this->logger = $logger;
    $this->authmap = $authmap;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SamlauthEvents::USER_SYNC][] = ['onUserSync'];
    $events[SamlauthEvents::USER_LINK][] = ['onUserLink'];
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
    $hawkid = $this->getHawkId($attributes);
    $account->setUsername($hawkid);

    // Revoke all previously-mapped roles for existing users.
    if ($account->isNew() === FALSE) {
      $row = $this->authmap->getAuthData($account->id(), 'samlauth');
      $data = unserialize($row['data']);

      if (!empty($data['uiowa_auth_mappings'])) {
        foreach ($data['uiowa_auth_mappings'] as $rid) {
          $account->removeRole($rid);
          $this->logger->notice('Revoked previously-mapped role @role for user @user so mapping is re-evaluated.', ['@role' => $rid, '@user' => $account->getUsername()]);
        }
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
    $sync = FALSE;
    $attributes = $event->getAttributes();

    /**
     * Prevent account creation for unlinked accounts that:
     * - do not already exist
     * - do not have a valid role mapping
     *
     * This prevents any HawkID user from creating an account with no role.
     */
    if (!$event->getLinkedAccount()) {
      $hawkid = $this->getHawkId($attributes);

       /** @var $search \Drupal\Core\Entity\EntityTypeInterface[] */
      $search = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $hawkid]);

      if (!empty($search)) {
        // Link account if HawkID user already exists.
        $account = reset($search);
        $event->setLinkedAccount($account);
        $sync = TRUE;
      }
      else {
        // Allow account creation if at least one role mapping is valid.
        $attr = $this->config->get('uiowa_auth.settings')->get('member_of_attribute');
        $member_of = $attributes[$attr];
        $mappings = $this->config->get('uiowa_auth.settings')->get('role_mappings');

        foreach ($mappings as $rid => $dn) {
          if (in_array($dn, $member_of)) {
            $sync = TRUE;

            $this->logger->notice('User @user has valid role mapping @rid => @dn. Allowing account creation.', [
              '@user' => $hawkid,
              '@rid' => $rid,
              '@dn' => $dn,
            ]);
          }
        }
      }

      if ($sync === FALSE) {
        $this->logger->error(t('HawkID @hawkid has no existing account or valid role mappings.', [
          '@hawkid' => $hawkid,
        ]));

        throw new ExternalAuthRegisterException();
      }
    }
  }

  /**
   * Transform the authname to a HawkID.
   *
   * @param $attributes
   *  SAML attributes to parse.
   *
   * @return string
   */
  public function getHawkId($attributes) {
    if ($attr = $this->config->get('samlauth.authentication')->get('user_name_attribute')) {
      $authname = $attributes[$attr][0];
      return stristr($authname, '@uiowa.edu', TRUE);
    }
    else {
      $this->logger->error(t('No user name attribute is set in SAML configuration. Unable to link account.'));
    }
  }
}
