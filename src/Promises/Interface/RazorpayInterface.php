<?php

declare(strict_types=1);

namespace Lazervel\PGI\Promises\Interface;

interface RazorpayInterface
{
  /**
   * Creates a new Razorpay order
   * 
   * @param int         $amount   [required]
   * @param string|null $currency [optional]
   * @param array       $notes    [optional]
   * 
   * @throws \Lazervel\PGI\Exception\InvalidAmountException
   * @return \Lazervel\PGI\Promises\Promise
   */
  public function order(int $amount, string $currency = null, array $notes = []) : self;

  public function isAuthorized() : self;

  /**
   * Validates payment signature and status Uses both SDK and manual HMAC check
   * 
   * @param string $orderId   [required]
   * @param string $paymentId [required]
   * @param string $signature [required]
   * 
   * @return \Lazervel\PGI\Promises\Promise
   */
  public function verifySignature(string $orderId, string $paymentId, string $signature) : self;
}
?>