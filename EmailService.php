<?php

namespace Drupal\event_registration\Service;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Email service for event registration.
 */
class EmailService {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new EmailService.
   */
  public function __construct(MailManagerInterface $mail_manager, ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager) {
    $this->mailManager = $mail_manager;
    $this->configFactory = $config_factory;
    $this->languageManager = $language_manager;
  }

  /**
   * Send confirmation email to user and admin.
   */
  public function sendConfirmationEmail($user_email, $user_name, $event_name, $event_date, $category) {
    $config = $this->configFactory->get('event_registration.settings');
    
    // Send to user
    $params = [
      'subject' => t('Event Registration Confirmation'),
      'body' => [
        '#theme' => 'registration_confirmation',
        '#user_name' => $user_name,
        '#event_name' => $event_name,
        '#event_date' => $event_date,
        '#category' => $category,
      ],
    ];

    $this->mailManager->mail('event_registration', 'registration_confirmation', $user_email, $this->languageManager->getDefaultLanguage()->getId(), $params);

    // Send to admin if enabled
    if ($config->get('enable_admin_notifications') && $admin_email = $config->get('admin_email')) {
      $params['body'] = [
        '#theme' => 'registration_confirmation_admin',
        '#user_name' => $user_name,
        '#user_email' => $user_email,
        '#event_name' => $event_name,
        '#event_date' => $event_date,
        '#category' => $category,
      ];

      $this->mailManager->mail('event_registration', 'admin_notification', $admin_email, $this->languageManager->getDefaultLanguage()->getId(), $params);
    }
  }

}