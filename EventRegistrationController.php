<?php

namespace Drupal\event_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for event registration AJAX callbacks.
 */
class EventRegistrationController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new EventRegistrationController.
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
   * Get dates for a category.
   */
  public function getDates($category) {
    $now = date('Y-m-d H:i:s');
    $query = $this->database->select('event_registration_event_config', 'e');
    $query->condition('e.category', $category);
    $query->condition('e.registration_start', $now, '<=');
    $query->condition('e.registration_end', $now, '>=');
    $query->fields('e', ['event_date']);
    $query->distinct();
    $result = $query->execute();

    $dates = [];
    foreach ($result as $row) {
      $dates[] = [
        'value' => $row->event_date,
        'label' => date('F j, Y', strtotime($row->event_date)),
      ];
    }

    return new JsonResponse($dates);
  }

  /**
   * Get events for a category and date.
   */
  public function getEvents($category, $date) {
    $now = date('Y-m-d H:i:s');
    $query = $this->database->select('event_registration_event_config', 'e');
    $query->condition('e.category', $category);
    $query->condition('e.event_date', $date);
    $query->condition('e.registration_start', $now, '<=');
    $query->condition('e.registration_end', $now, '>=');
    $query->fields('e', ['id', 'event_name']);
    $result = $query->execute();

    $events = [];
    foreach ($result as $row) {
      $events[] = [
        'value' => $row->id,
        'label' => $row->event_name,
      ];
    }

    return new JsonResponse($events);
  }

}