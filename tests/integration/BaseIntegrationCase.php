<?php

namespace Tests\integration;

use PHPUnit\DbUnit\TestCaseTrait;
use PHPUnit\Framework\TestCase;
use Tests\CreatesApplication;
use Tests\MailCatcherTrait;

abstract class BaseIntegrationCase extends TestCase
{
  use TestCaseTrait {
    setUp as public setUpTestCase;
  }
  use MailCatcherTrait;
  use CreatesApplication;

  private static $pdo;
  private static $app;
  private $connection;

  protected static function getPdo()
  {
    return(self::$pdo);
  }

  protected function getApp()
  {
    if (self::$app === null) {
      self::$app = $this->createApplication();
    }
    return(self::$app);
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
}