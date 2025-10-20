<?php

declare(strict_types=1);

namespace Lazervel\PGI\Promises;

abstract class Promise
{
  private $rejected = false;
  private $finallyHandler;
  private $rejectHandler;
  private $memory = [];
  private $event = [];
  private $fired = false;

  /**
   * @param int   $status   [required]
   * @param mixed $argument [required]
   */
  protected function resolveWith(int $status, $argument) : void
  {
    if ($this->rejected) return;
    $this->fired = true;

    $this->event['responseJSON'] = \json_encode($argument);
    $this->event['response']     = $argument;
    $this->event['status']       = $status;
    $this->event['timestamp']    = time();
    $this->event['cradential']   = true;
    $this->event['target']       = $this;
  }

  /**
   * @param int   $status [required]
   * @param mixed $err    [required]
   */
  protected function rejectWith(int $status, $err) : void
  {
    $this->fired    = false;
    $this->rejected = true;
    
    $this->event['status']     = $status;
    $this->event['error']      = $err;
    $this->event['timestamp']  = time();
    $this->event['cradential'] = false;
    $this->event['target']     = $this;
  }

  /**
   * @param callable|null $onRejectHandler [required]
   * @return
   */
  public function catch(?callable $onRejectHandler) : self
  {
    $this->rejectHandler = $onRejectHandler;
    return $this;
  }

  /**
   * @param callable $callback [required]
   * @return
   */
  public function finally(callable $callback) : self
  {
    $this->finallyHandler = $callback;
    return $this->done($callback);
  }

  /**
   * @param callable|null $onResolveHandler [required]
   * @return
   */
  public function done(?callable $onResolveHandler) : self
  {
    $this->memory[] = $onResolveHandler;
    return $this;
  }

  /**
   * @param callable      $onResolveHandler [required]
   * @param callable|null $onRejectHandler  [optional]
   * @return
   */
  public function then(callable $onResolveHandler, ?callable $onRejectHandler = null) : self
  {
    return $this->done($onResolveHandler)->catch($onRejectHandler);
  }

  public function __destruct()
  {
    $finallyHandler   = $this->finallyHandler;
    $event            = (object) $this->event;
    $rejectHandler    = $this->rejectHandler;

    if ($this->rejected && \is_callable($rejectHandler)) {
      $rejectHandler($event, $this->event['error']);
      $finallyHandler && $finallyHandler($event, $this->event['error']);
    } else if ($this->fired) {
      foreach($this->memory as $callback) {
        $callback($event, $this->event['response']);
      }
    }
  }
}
?>