<?php

namespace Tests\e2e;

use Behat\Mink\{Mink, Session};
use Behat\Mink\Driver\GoutteDriver;
use Behat\Mink\Driver\Goutte\Client as GoutteClient;
use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit\Framework\TestCase;
use Tests\MailCatcherTrait;
 
abstract class MinkTestCase extends TestCase {
  use TestCaseTrait {
    setUp as public setUpTestCase;
  }
  use MailCatcherTrait;

  private static $mink;
  private static $baseUrl;
  private static $pdo;
  private $connection;

  protected static function getMink()
  {
    return(self::$mink);
  }

  protected static function getPdo()
  {
    return(self::$pdo);
  }

  protected static function getBaseUrl()
  {
    return(self::$baseUrl);
  }

  public function __construct()
  {
    parent::__construct();
    self::$baseUrl = "http://{$GLOBALS['DOMAIN_NAME']}:{$GLOBALS['DOMAIN_PORT']}";
    if (self::$mink === null) {
      self::$mink = new Mink([
        'goutte' => self::createGoutteSession(),
      ]);
      self::$mink->setDefaultSessionName('goutte');
    }
  }

  protected function setUp()
  {
    parent::setUp();
    $this->setUpTestCase();
    self::getMink()->resetSessions();
  }

  final public function getConnection()
  {
    if ($this->connection === null) {
      if (self::$pdo === null) {
        try {
          self::$pdo = new \PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']);
        } catch (\PDOException $e) {
          echo("\n[ERROR] Unable to connect to the DB server.\n");
          throw $e;
        }
      }

      $this->connection = $this->createDefaultDBConnection(self::$pdo, $GLOBALS['DB_DBNAME']);
    }

    return($this->connection);
  }

  protected function getDataSet()
  {
    return($this->createArrayDataSet($this->fixtures));
  }

  protected static function getSession(string $name = null): Session
  {
    return(self::getMink()->getSession($name));
  }

  protected static function createGoutteSession(): Session
  {
    return(new Session(new GoutteDriver(new GoutteClient())));
  }

  protected static function getUrlFromUri(string $uri): string
  {
    if (!empty($uri) && strpos($uri, '/') !== 0) {
      $uri = "/${uri}";
    }

    return(self::$baseUrl.$uri);
  }

  protected static function visitUri(Session $session, string $uri): void
  {
    $session->visit(self::getUrlFromUri($uri));
  }

  protected static function authenticateUser(
    Session $session, string $identifier, string $password
  ): void {
    self::visitUri($session, '/login');
    $page = $session->getPage();
    $form = $page->find('named', ['id_or_name', 'login-form']);
    $form->fillField('identifier', $identifier);
    $form->fillField('password', $password);
    $form->submit();
  }

  private static $htmlCleanerPatterns = [
    '/>(?:[\n\t\r]| {2,})+</' => '><',
    '/^(?:[\n\t\r]| {2,})+</' => '<',
    '/>(?:[\n\t\r]| {2,})+$/' => '>',
  ];

  protected static function oneLineHtml(string $html): string
  {
    foreach (self::$htmlCleanerPatterns as $pattern => $replacement) {
      $html = preg_replace($pattern, $replacement, $html);
    }

    return($html);
  }
}