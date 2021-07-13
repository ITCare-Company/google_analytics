<?php

namespace Drupal\google_analytics;

/**
 * Provides an interface.
 */
interface GoogleAnalitycsInterface {

  /**
   * Define the default file extension list that should be tracked as download.
   */
  const GOOGLE_ANALYTICS_TRACKFILES_EXTENSIONS = '7z|aac|arc|arj|asf|asx|avi|bin|csv|doc(x|m)?|dot(x|m)?|exe|flv|gif|gz|gzip|hqx|jar|jpe?g|js|mp(2|3|4|e?g)|mov(ie)?|msi|msp|pdf|phps|png|ppt(x|m)?|pot(x|m)?|pps(x|m)?|ppam|sld(x|m)?|thmx|qtm?|ra(m|r)?|sea|sit|tar|tgz|torrent|txt|wav|wma|wmv|wpd|xls(x|m|b)?|xlt(x|m)|xlam|xml|z|zip';

  /**
   * Define the Acceptable GA ID Patterns
   */
  const GOOGLE_ANALYTICS_GTAG_MATCH = '/(?:UA|G|AW|DC)-[0-9a-zA-Z]{5,}(?:-[0-9]{1,})?/';

  /**
   * Define the Acceptable tracking ID patterns
   */
  const GOOGLE_ANALYTICS_TRACKING_MATCH = '/(?:UA|G)-[0-9a-zA-Z]{5,}(?:-[0-9]{1,})?/';
}
