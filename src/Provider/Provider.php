<?php

declare(strict_types=1);

namespace Lazervel\PGI\Provider;

use Lazervel\PGI\Exception\KeySecretNotFoundException;
use Lazervel\Dotenv\Dotenv;

abstract class Provider
{
  public bool $EnableSessionAuthorization = false;
  protected $KEY_SECRET;
  protected $KEY_ID;

  public function __construct(string $KEY_ID, string $KEY_SECRET)
  {
    Dotenv::process(getcwd())->load();

    $this->KEY_SECRET = $_ENV[$KEY_SECRET] ?? null;
    $this->KEY_ID = $_ENV[$KEY_ID] ?? null;
    
    if (!($this->KEY_ID && $this->KEY_SECRET)) {
      throw new KeySecretNotFoundException(\sprintf('Environment variable [%s] or [%s] Not Found!', $KEY_ID, $KEY_SECRET));
    }
  }

  public function sessionId()
  {
    // Start session if not started
    if (!isset($_SESSION)) \session_start();
    \session_regenerate_id(true); // Stop for Session fixation

    $session_id = \session_id(); // Generating session id
    return $session_id;
  }
}
?>