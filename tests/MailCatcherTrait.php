<?php

namespace Tests;

trait MailCatcherTrait
{
  /**
   * @var \GuzzleHttp\Client
   */
  private static $mailCatcher;

  private static function getMailCatcher(): \GuzzleHttp\Client
  {
    if (self::$mailCatcher === null) {
      self::$mailCatcher = new \GuzzleHttp\Client(['base_uri' => 'http://127.0.0.1:1080']);
    }
    return(self::$mailCatcher);
  }

  /**
   * Returns the meta data for all the emails caught by mailcatcher.
   * 
   * @return array The decoded json string with the meta data for the emails.
   */
  protected static function getEmails(): array
  {
    $jsonResponse = self::getMailCatcher()->get('/messages');
    return(json_decode($jsonResponse->getBody(), true));
  }

  /**
   * Retrieves the HTML body of the email with the provided ID.
   * 
   * @param int $id The desired email's ID in mailcatcher.
   * @return string
   */
  protected static function getEmailBody(int $id): string
  {
    return(self::getMailCatcher()->get("/messages/${id}.html")->getBody());
  }

  /**
   * Clears all the emails stored by mailcatcher.
   * 
   * @return void
   */
  protected static function clearEmails(): void
  {
    self::getMailCatcher()->delete('/messages');
  }
}