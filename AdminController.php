<?php

namespace Drupal\event_registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Controller for admin pages.
 */
class AdminController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new AdminController.
   */
  public function __construct(Connection $database, RendererInterface $renderer, DateFormatterInterface $date_formatter) {
    $this->database = $database;
    $this->renderer = $renderer;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('renderer'),
      $container->get('date.formatter')
    );
  }

  /**
   * List registrations.
   */
  public function listRegistrations() {
    // Get unique dates
    $dates = $this->database->select('event_registration_event_config', 'e')
      ->fields('e', ['event_date'])
      ->distinct()
      ->execute()
      ->fetchAllKeyed(0, 0);

    $date_options = [];
    foreach ($dates as $date) {
      $date_options[$date] = $this->dateFormatter->format(strtotime($date), 'custom', 'F j, Y');
    }

    $build = [];
    $build['filter_form'] = [
      '#type' => 'form',
      '#method' => 'get',
      '#prefix' => '<div class="event-registration-filter">',
      '#suffix' => '</div>',
    ];

    $build['filter_form']['event_date'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Date'),
      '#options' => $date_options,
      '#empty_option' => $this->t('- Select Date -'),
      '#attributes' => [
        'id' => 'event-date-filter',
      ],
    ];

    $build['filter_form']['event_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Event Name'),
      '#options' => [],
      '#empty_option' => $this->t('- Select Event -'),
      '#attributes' => [
        'id' => 'event-name-filter',
      ],
    ];

    $build['filter_form']['total_count'] = [
      '#markup' => '<div id="total-count"></div>',
    ];

    $build['registrations_table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Email'),
        $this->t('Event Date'),
        $this->t('College Name'),
        $this->t('Department'),
        $this->t('Submission Date'),
      ],
      '#empty' => $this->t('No registrations found.'),
      '#attributes' => ['id' => 'registrations-table'],
    ];

    $build['export_link'] = [
      '#markup' => '<div id="export-link"></div>',
    ];

    $build['#attached']['library'][] = 'event_registration/admin';

    return $build;
  }

  /**
   * Get registrations via AJAX.
   */
  public function getRegistrations($date, $event_name) {
    $query = $this->database->select('event_registration_registrations', 'r');
    $query->join('event_registration_event_config', 'e', 'r.event_config_id = e.id');
    $query->condition('e.event_date', $date);
    $query->condition('r.event_config_id', $event_name);
    $query->fields('r', ['full_name', 'email', 'college_name', 'department', 'created']);
    $query->fields('e', ['event_date']);
    $query->orderBy('r.created', 'DESC');
    $result = $query->execute();

    $rows = [];
    $total = 0;
    foreach ($result as $row) {
      $rows[] = [
        $row->full_name,
        $row->email,
        $this->dateFormatter->format(strtotime($row->event_date), 'custom', 'F j, Y'),
        $row->college_name,
        $row->department,
        $this->dateFormatter->format($row->created, 'long'),
      ];
      $total++;
    }

    $data = [
      'total' => $total,
      'rows' => $rows,
    ];

    return new JsonResponse($data);
  }

  /**
   * Export CSV.
   */
  public function exportCsv($date, $event_name) {
    $query = $this->database->select('event_registration_registrations', 'r');
    $query->join('event_registration_event_config', 'e', 'r.event_config_id = e.id');
    $query->condition('e.event_date', $date);
    $query->condition('r.event_config_id', $event_name);
    $query->fields('r', ['full_name', 'email', 'college_name', 'department', 'created']);
    $query->fields('e', ['event_name', 'event_date', 'category']);
    $query->orderBy('r.created', 'DESC');
    $result = $query->execute();

    $csv = [];
    $csv[] = ['Name', 'Email', 'Event Name', 'Event Date', 'Category', 'College Name', 'Department', 'Registration Date'];

    foreach ($result as $row) {
      $csv[] = [
        $row->full_name,
        $row->email,
        $row->event_name,
        $this->dateFormatter->format(strtotime($row->event_date), 'custom', 'F j, Y'),
        $row->category,
        $row->college_name,
        $row->department,
        $this->dateFormatter->format($row->created, 'long'),
      ];
    }

    $filename = "registrations_" . date('Y-m-d') . ".csv";
    $response = new Response();
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    ob_start();
    foreach ($csv as $row) {
      fputcsv($output, $row);
    }
    $content = ob_get_clean();
    fclose($output);

    $response->setContent($content);
    return $response;
  }

}