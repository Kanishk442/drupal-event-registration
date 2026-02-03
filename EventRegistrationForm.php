<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\event_registration\Service\EmailService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Event registration form.
 */
class EventRegistrationForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The email service.
   *
   * @var \Drupal\event_registration\Service\EmailService
   */
  protected $emailService;

  /**
   * Constructs a new EventRegistrationForm.
   */
  public function __construct(Connection $database, ConfigFactoryInterface $config_factory, EmailService $email_service) {
    $this->database = $database;
    $this->configFactory = $config_factory;
    $this->emailService = $email_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('config.factory'),
      $container->get('event_registration.email_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_registration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Check if any event is open for registration
    $now = new DrupalDateTime();
    $query = $this->database->select('event_registration_event_config', 'e');
    $query->condition('e.registration_start', $now->format('Y-m-d H:i:s'), '<=');
    $query->condition('e.registration_end', $now->format('Y-m-d H:i:s'), '>=');
    $query->fields('e', ['id']);
    $available = $query->countQuery()->execute()->fetchField();

    if ($available == 0) {
      $form['message'] = [
        '#markup' => '<div class="messages messages--warning">' . $this->t('No events are currently open for registration.') . '</div>',
      ];
      return $form;
    }

    // Get unique categories
    $categories = $this->database->select('event_registration_event_config', 'e')
      ->fields('e', ['category'])
      ->condition('e.registration_start', $now->format('Y-m-d H:i:s'), '<=')
      ->condition('e.registration_end', $now->format('Y-m-d H:i:s'), '>=')
      ->distinct()
      ->execute()
      ->fetchAllKeyed(0, 0);

    $form['full_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full Name'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#attributes' => [
        'pattern' => '^[a-zA-Z\s]*$',
        'title' => $this->t('Only letters and spaces allowed'),
      ],
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['college_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('College Name'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#attributes' => [
        'pattern' => '^[a-zA-Z0-9\s\.\-]*$',
        'title' => $this->t('Only alphanumeric characters, spaces, dots, and hyphens allowed'),
      ],
    ];

    $form['department'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Department'),
      '#required' => TRUE,
      '#maxlength' => 255,
      '#attributes' => [
        'pattern' => '^[a-zA-Z\s]*$',
        'title' => $this->t('Only letters and spaces allowed'),
      ],
    ];

    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#required' => TRUE,
      '#options' => $categories,
      '#empty_option' => $this->t('- Select -'),
      '#ajax' => [
        'callback' => '::dateCallback',
        'wrapper' => 'date-wrapper',
        'event' => 'change',
      ],
    ];

    $form['date_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'date-wrapper'],
    ];

    $selected_category = $form_state->getValue('category');
    if ($selected_category) {
      $dates = $this->getAvailableDates($selected_category);
      $form['date_wrapper']['event_date'] = [
        '#type' => 'select',
        '#title' => $this->t('Event Date'),
        '#required' => TRUE,
        '#options' => $dates,
        '#empty_option' => $this->t('- Select -'),
        '#ajax' => [
          'callback' => '::eventCallback',
          'wrapper' => 'event-wrapper',
          'event' => 'change',
        ],
      ];
    }

    $form['event_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'event-wrapper'],
    ];

    $selected_date = $form_state->getValue('event_date');
    if ($selected_category && $selected_date) {
      $events = $this->getAvailableEvents($selected_category, $selected_date);
      $form['event_wrapper']['event_name'] = [
        '#type' => 'select',
        '#title' => $this->t('Event Name'),
        '#required' => TRUE,
        '#options' => $events,
        '#empty_option' => $this->t('- Select -'),
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Register'),
    ];

    // Add JS for validation
    $form['#attached']['library'][] = 'event_registration/registration';

    return $form;
  }

  /**
   * AJAX callback for date field.
   */
  public function dateCallback(array &$form, FormStateInterface $form_state) {
    return $form['date_wrapper'];
  }

  /**
   * AJAX callback for event field.
   */
  public function eventCallback(array &$form, FormStateInterface $form_state) {
    return $form['event_wrapper'];
  }

  /**
   * Get available dates for a category.
   */
  private function getAvailableDates($category) {
    $now = new DrupalDateTime();
    $query = $this->database->select('event_registration_event_config', 'e');
    $query->condition('e.category', $category);
    $query->condition('e.registration_start', $now->format('Y-m-d H:i:s'), '<=');
    $query->condition('e.registration_end', $now->format('Y-m-d H:i:s'), '>=');
    $query->fields('e', ['event_date']);
    $query->distinct();
    $result = $query->execute();

    $dates = [];
    foreach ($result as $row) {
      $date = new DrupalDateTime($row->event_date);
      $dates[$row->event_date] = $date->format('F j, Y');
    }

    return $dates;
  }

  /**
   * Get available events for a category and date.
   */
  private function getAvailableEvents($category, $date) {
    $now = new DrupalDateTime();
    $query = $this->database->select('event_registration_event_config', 'e');
    $query->condition('e.category', $category);
    $query->condition('e.event_date', $date);
    $query->condition('e.registration_start', $now->format('Y-m-d H:i:s'), '<=');
    $query->condition('e.registration_end', $now->format('Y-m-d H:i:s'), '>=');
    $query->fields('e', ['id', 'event_name']);
    $result = $query->execute();

    $events = [];
    foreach ($result as $row) {
      $events[$row->id] = $row->event_name;
    }

    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate email format
    $email = $form_state->getValue('email');
    if (!\Drupal::service('email.validator')->isValid($email)) {
      $form_state->setErrorByName('email', $this->t('Please enter a valid email address.'));
    }

    // Validate special characters in text fields
    $fields = ['full_name', 'college_name', 'department'];
    foreach ($fields as $field) {
      $value = $form_state->getValue($field);
      if (preg_match('/[<>"\']/', $value)) {
        $form_state->setErrorByName($field, $this->t('Special characters are not allowed in @field.', ['@field' => $field]));
      }
    }

    // Check for duplicate registration
    $event_config_id = $form_state->getValue('event_name');
    if ($event_config_id) {
      $query = $this->database->select('event_registration_registrations', 'r')
        ->condition('r.email', $form_state->getValue('email'))
        ->condition('r.event_config_id', $event_config_id)
        ->countQuery()
        ->execute()
        ->fetchField();

      if ($query > 0) {
        $form_state->setErrorByName('email', $this->t('You have already registered for this event.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $event_config_id = $form_state->getValue('event_name');
    
    // Get event details
    $event = $this->database->select('event_registration_event_config', 'e')
      ->fields('e', ['event_name', 'category', 'event_date'])
      ->condition('e.id', $event_config_id)
      ->execute()
      ->fetchAssoc();

    // Insert registration
    $this->database->insert('event_registration_registrations')
      ->fields([
        'event_config_id' => $event_config_id,
        'full_name' => $form_state->getValue('full_name'),
        'email' => $form_state->getValue('email'),
        'college_name' => $form_state->getValue('college_name'),
        'department' => $form_state->getValue('department'),
        'created' => time(),
      ])
      ->execute();

    // Send email notifications
    $this->emailService->sendConfirmationEmail(
      $form_state->getValue('email'),
      $form_state->getValue('full_name'),
      $event['event_name'],
      $event['event_date'],
      $event['category']
    );

    $this->messenger()->addStatus($this->t('Thank you for registering! A confirmation email has been sent.'));
  }

}