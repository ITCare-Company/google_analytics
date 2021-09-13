<?php

namespace Drupal\google_analytics\EventSubscriber\GoogleAnalyticsEvents;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\google_analytics\Event\GoogleAnalyticsEventsEvent;
use Drupal\google_analytics\GoogleAnalyticsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds Drupal Messages to GA Javascript.
 */
class DrupalMessage implements EventSubscriberInterface {

  /**
   * Drupal Config Factory
   *
   * @var \Drupal\acquia_contenthub\Client\ProjectVersionClient
   */
  protected $config;

  /**
   * Drupal Messenger Service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * DrupalMessage constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory for Google Analytics Settings.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MessengerInterface $messenger) {
    $this->config = $config_factory->get('google_analytics.settings');
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[GoogleAnalyticsEvents::ADD_EVENT][] = ['onAddEvent'];
    return $events;
  }

  /**
   * Adds a new event to the Ga Javascript
   *
   * @param \Drupal\google_analytics\Event\GoogleAnalyticsEventsEvent $event
   *   The event being dispatched.
   *
   * @throws \Exception
   */
  public function onAddEvent(GoogleAnalyticsEventsEvent $event) {
    if ($message_types = $this->config->get('track.messages')) {
      // Add messages tracking.
      $event_name = 'drupal_message';
      $legacy_mode = $this->config->get('ua_legacy') ?? FALSE;

      $message_types = array_values(array_filter($message_types));
      $status_heading = [
        'status' => t('Status message'),
        'warning' => t('Warning message'),
        'error' => t('Error message'),
      ];

      foreach ($this->messenger->all() as $type => $messages) {
        // Track only the selected message types.
        if (in_array($type, $message_types)) {
          foreach ($messages as $message) {
            // Compatibility with 3.x and UA format.
            if ($legacy_mode) {
              $event->addEvent(Json::encode($status_heading[$type]),
                ['event_category' => t('Messages'),
                 'event_label'    => strip_tags((string) $message)
                ]);
            }
            else {
              $event->addEvent($event_name, [
                'message_type' => $status_heading[$type],
                'value' => strip_tags((string) $message),
              ]);
            }
          }
        }
      }
    }
  }
}
