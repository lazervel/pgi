<?php

declare(strict_types=1);

namespace Lazervel\PGI\Promises;

use Lazervel\PGI\Promises\Promise;
use Lazervel\PGI\Razorpay as RZP;

class Razorpay extends Promise
{
  public string $receipt;
  private $razorpay;

  // Initializes Razorpay SDK with credentials Sets up API instance for transactions
  public function __construct()
  {
    try {
      $this->razorpay = new RZP;
    } catch(\Exception $err) {
      $this->rejectWith(400, 'Connection Failed');
    }
  }

  /**
   * Creates a new Razorpay order
   * 
   * @param int         $amount   [required]
   * @param string|null $currency [optional]
   * @param array       $notes    [optional]
   * 
   * @return \Lazervel\PGI\Promises\Promise
   */
  public function order(int $amount, string $currency = null, array $notes = []) : self
  {
    try {
      $order = $this->razorpay->order($amount, $currency, $notes);
      $this->resolveWith(200, $order);
    } catch(\Exception $err) {
      $this->rejectWith(400, 'Order Failed');
    }
    return $this;
  }

  public function isAuthorized() : self
  {
    $this->razorpay->isAuthorized() ? $this->resolveWith(200, 'Authorized') : $this->rejectWith(401, 'Unauthorized');
    return $this;
  }

  /**
   * Validates payment signature and status Uses both SDK and manual HMAC check
   * 
   * @param string $orderId   [required]
   * @param string $paymentId [required]
   * @param string $signature [required]
   * 
   * @return \Lazervel\PGI\Promises\Promise
   */
  public function verifySignature(string $orderId, string $paymentId, string $signature) : self
  {
    $status = $this->razorpay->verifySignature($orderId, $paymentId, $signature);
    $status ? $this->resolveWith(200, $status) : $this->rejectWith(400, 'Verification Failed');
    return $this;
  }
}
?>