<?php

namespace Drupal\Tests\google_analytics\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\Traits\Core\CronRunTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Test search functionality of Google Analytics module.
 *
 * @group Google Analytics
 */
class GoogleAnalyticsSearchTest extends BrowserTestBase {

  use StringTranslationTrait;
  use CronRunTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['google_analytics', 'search', 'node'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\Entity\User|bool
   */
  protected $adminUser;

  /**
   * Default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $permissions = [
      'access administration pages',
      'administer google analytics',
      'search content',
      'create page content',
      'edit own page content',
    ];

    // User to set up google_analytics.
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests if search tracking is properly added to the page.
   */
  public function testGoogleAnalyticsSearchTracking() {
    $ua_code = 'UA-123456-1';
    $this->config('google_analytics.settings')
      ->set('account', $ua_code)
      ->set('privacy.anonymizeip', 0)
      ->set('track.displayfeatures', 1)
      ->save();

    // Check tracking code visibility.
    $this->drupalGet('');
    $this->assertRaw($ua_code);

    $this->drupalGet('search/node');
    $this->assertNoRaw('gtag("config", ' . Json::encode($ua_code) . ', {"groups":"default","page_path":"');

    // Enable site search support.
    $this->config('google_analytics.settings')->set('track.site_search', 1)->save();

    // Search for random string.
    $search = [];
    $search['keys'] = $this->randomMachineName(8);

    // Create a node to search for.
    $edit = [];
    $edit['title[0][value]'] = 'This is a test title';
    $edit['body[0][value]'] = 'This test content contains ' . $search['keys'] . ' string.';

    // Fire a search, it's expected to get 0 results.
    $this->drupalPostForm('search/node', $search, $this->t('Search'));
    $this->assertRaw('gtag("config", ' . Json::encode($ua_code) . ', {"groups":"default","page_path":(window.google_analytics_search_results) ?');
    $this->assertRaw('window.google_analytics_search_results = 0;');

    // Save the node.
    $this->drupalPostForm('node/add/page', $edit, $this->t('Save'));
    $this->assertText($this->t('@type @title has been created.', ['@type' => 'Basic page', '@title' => $edit['title[0][value]']]));

    // Index the node or it cannot found.
    $this->cronRun();

    $this->drupalPostForm('search/node', $search, $this->t('Search'));
    $this->assertRaw('gtag("config", ' . Json::encode($ua_code) . ', {"groups":"default","page_path":(window.google_analytics_search_results) ?');
    $this->assertRaw('window.google_analytics_search_results = 1;');

    $this->drupalPostForm('node/add/page', $edit, $this->t('Save'));
    $this->assertText($this->t('@type @title has been created.', ['@type' => 'Basic page', '@title' => $edit['title[0][value]']]));

    // Index the node or it cannot found.
    $this->cronRun();

    $this->drupalPostForm('search/node', $search, $this->t('Search'));
    $this->assertRaw('gtag("config", ' . Json::encode($ua_code) . ', {"groups":"default","page_path":(window.google_analytics_search_results) ?');
    $this->assertRaw('window.google_analytics_search_results = 2;');
  }

}
