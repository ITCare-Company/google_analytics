<?php

namespace Drupal\google_analytics\EventSubscriber\GoogleAnalyticsConfig;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Utility\Token;
use Drupal\google_analytics\Event\GoogleAnalyticsConfigEvent;
use Drupal\google_analytics\Event\GoogleAnalyticsEventsEvent;
use Drupal\google_analytics\GoogleAnalyticsEvents;
use Drupal\google_analytics\Helpers\GoogleAnalyticsAccounts;
use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adds custom Dimensions and Metrics to config and events.
 */
class CustomConfig implements EventSubscriberInterface {

  /**
   * Drupal Config Factory
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Google Analytics Accounts Service
   *
   * @var \Drupal\google_analytics\Helpers\GoogleAnalyticsAccounts
   */
  protected $gaAccounts;

  /**
   * Current Drupal User Account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentAccount;

  /**
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected $request;

  /**
   * Custom Mapping of Vars.
   *
   * @var array
   */
  protected $custom_map = [];

  /**
   * Custom Variables passed to GA.
   * @var array
   */
  protected $custom_vars = [];

  /**
   * DrupalMessage constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config Factory for Google Analytics Settings.
   */
  public function __construct(ConfigFactoryInterface $config_factory, GoogleAnalyticsAccounts $ga_accounts, AccountProxyInterface $account, RequestStack $request, Token $token) {    $this->config = $config_factory->get('google_analytics.settings');
    $this->gaAccounts = $ga_accounts;
    $this->currentAccount = $account;
    $this->request = $request->getCurrentRequest();
    $this->token = $token;

    // Populate custom map/vars
    $this->populateCustomConfig();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[GoogleAnalyticsEvents::ADD_CONFIG][] = ['onAddConfig'];
    $events[GoogleAnalyticsEvents::ADD_EVENT][] = ['onAddEvent'];
    return $events;
  }

  /**
   * Adds a new event to the Ga Javascript
   *
   * @param \Drupal\google_analytics\Event\GoogleAnalyticsConfigEvent $event
   *   The event being dispatched.
   *
   * @throws \Exception
   */
  public function onAddConfig(GoogleAnalyticsConfigEvent $event) {
    // Don't execute event if there is nothing in the mapping fields.
    if (empty($this->custom_map)) {
      return;
    }
    $event->addConfig('custom_map', $this->custom_map['custom_map']);
  }

  public function onAddEvent(GoogleAnalyticsEventsEvent $event) {
    // Don't execute event if there is nothing in the mapping fields.
    if (empty($this->custom_vars)) {
      return;
    }
    $event->addEvent('custom', $this->custom_vars);
  }

  protected function populateCustomConfig() {
    // Add custom dimensions and metrics.
    foreach (['dimension', 'metric'] as $google_analytics_custom_type) {
      $google_analytics_custom_vars = $this->config->get('custom.' . $google_analytics_custom_type);
      // Are there dimensions or metrics configured?
      if (!empty($google_analytics_custom_vars)) {
        // Add all the configured variables to the content.
        foreach ($google_analytics_custom_vars as $google_analytics_custom_var) {
          // Replace tokens in values.
          $types = [];
          if ($this->request->attributes->has('node')) {
            $node = $this->request->attributes->get('node');
            if ($node instanceof NodeInterface) {
              $types += ['node' => $node];
            }
          }
          $google_analytics_custom_var['value'] = $this->token->replace($google_analytics_custom_var['value'], $types, ['clear' => TRUE]);

          // Suppress empty values.
          if (!mb_strlen(trim($google_analytics_custom_var['name'])) || !mb_strlen(trim($google_analytics_custom_var['value']))) {
            continue;
          }

          // Per documentation the max length of a dimension is 150 bytes.
          // A metric has no length limitation. It's not documented if this
          // limit means 150 bytes after url encoding or before.
          // See https://developers.google.com/analytics/devguides/collection/analyticsjs/field-reference#customs
          if ($google_analytics_custom_type == 'dimension' && mb_strlen($google_analytics_custom_var['value']) > 150) {
            $google_analytics_custom_var['value'] = substr($google_analytics_custom_var['value'], 0, 150);
          }

          // Cast metric values for json_encode to data type numeric.
          if ($google_analytics_custom_type == 'metric') {
            settype($google_analytics_custom_var['value'], 'float');
          };

          // Build the arrays of values.
          $this->custom_map['custom_map'][$google_analytics_custom_type . $google_analytics_custom_var['index']] = $google_analytics_custom_var['name'];
          $this->custom_vars[$google_analytics_custom_var['name']] = $google_analytics_custom_var['value'];
        }
      }
    }
  }
}
