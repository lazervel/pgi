<?php

declare(strict_types=1);

namespace Lazervel\PGI\Provider;

use Lazervel\PGI\Exception\PGIException;
use Dotenv\Dotenv;

abstract class Provider
{
  private $resolveHandlers = [];
  private $rejected = false;
  private $resolved = false;
  protected $rejectHandler;
  private $argument;

  protected $KEY_SECRET;
  protected $KEY_ID;

  public function __construct(string $KEY_SECRET, string $KEY_ID)
  {
    Dotenv::process(getcwd())->safeLoad();
    $this->KEY_SECRET = $_ENV[$KEY_SECRET];
    $this->KEY_ID = $_ENV[$KEY_ID];
    $this->self = $this;
  }

  public function session()
  {
    // Start session if not started
    if (!isset($_SESSION)) \session_start();

    // Stop for Session fixation
    \session_regenerate_id(true);

    // Generating session id
    $session_id = \session_id();

    return [
      'session_hash_id' => \password_hash($session_id, PASSWORD_DEFAULT),
      'session_id' => $session_id
    ];
  }

  /**
   * @param mixed $arg
   */
  protected function resolveWith($arg) : void
  {
    if (!$this->rejected) {
      $this->resolved = true;
      $this->argument = $arg;
    }
  }

  /**
   * @param mixed $error
   */
  protected function rejectWith($err) : void
  {
    $this->rejected = true;
    $this->argument = $err;
  }

  /**
   * 
   */
  protected function uniqueId($suffix = null) : string
  {
    \date_default_timezone_set('Asia/Kolkata');

    $suffix = $suffix ?? rand(11, 99);
    $unique = time();
    
    return \sprintf('%d%d', $unique, $suffix);
  }

  /**
   * 
   */
  public function catch(callable $onReject) : self
  {
    $this->rejectHandler = $onReject;
    return $this;
  }

  /**
   * 
   */
  public function then(callable $onResolve, ?callable $onReject = null) : self
  {
    $this->resolveHandlers[] = $onResolve;
    if ($onReject) {
      $this->catch($onReject);
    }
    return $this;
  }

  public function __destruct()
  {
    $fn = $this->rejectHandler;
    
    if ($this->rejected) {
      if (!$fn) {
        throw new PGIException($this->argument);
      }

      $fn($this->argument);
    }

    if ($this->resolved) {
      foreach($this->resolveHandlers as $fn) {
        $fn($this->argument);
      }
    }
  }
}
?>