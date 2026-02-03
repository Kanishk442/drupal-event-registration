<?php

namespace Drupal\event_registration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Event configuration form.
 */
class EventConfigForm extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new EventConfigForm.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_registration_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['event_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Name'),
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Category'),
      '#required' => TRUE,
      '#options' => [
        'Online Workshop' => $this->t('Online Workshop'),
        'Hackathon' => $this->t('Hackathon'),
        'Conference' => $this->t('Conference'),
        'One-day Workshop' => $this->t('One-day Workshop'),
      ],
    ];

    $form['event_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Event Date'),
      '#required' => TRUE,
      '#date_date_format' => 'Y-m-d',
    ];

    $form['registration_start'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Registration Start Date'),
      '#required' => TRUE,
      '#date_date_format' => 'Y-m-d',
      '#date_time_format' => 'H:i',
    ];

    $form['registration_end'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Registration End Date'),
      '#required' => TRUE,
      '#date_date_format' => 'Y-m-d',
      '#date_time_format' => 'H:i',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Event'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $event_date = new DrupalDateTime($form_state->getValue('event_date'));
    $reg_start = $form_state->getValue('registration_start');
    $reg_end = $form_state->getValue('registration_end');

    if ($reg_start >= $reg_end) {
      $form_state->setErrorByName('registration_end', $this->t('Registration end date must be after start date.'));
    }

    if ($reg_end >= $event_date) {
      $form_state->setErrorByName('event_date', $this->t('Event date must be after registration end date.'));
    }

    // Check for duplicate event name
    $query = $this->database->select('event_registration_event_config', 'e')
      ->condition('e.event_name', $form_state->getValue('event_name'))
      ->condition('e.event_date', $form_state->getValue('event_date'))
      ->countQuery()
      ->execute()
      ->fetchField();

    if ($query > 0) {
      $form_state->setErrorByName('event_name', $this->t('An event with this name already exists on the selected date.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->database->insert('event_registration_event_config')
      ->fields([
        'event_name' => $form_state->getValue('event_name'),
        'category' => $form_state->getValue('category'),
        'event_date' => $form_state->getValue('event_date'),
        'registration_start' => $form_state->getValue('registration_start')->format('Y-m-d H:i:s'),
        'registration_end' => $form_state->getValue('registration_end')->format('Y-m-d H:i:s'),
        'created' => time(),
      ])
      ->execute();

    $this->messenger()->addStatus($this->t('Event configuration saved successfully.'));
  }

}