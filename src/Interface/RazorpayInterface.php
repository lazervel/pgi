<?php

declare(strict_types=1);

namespace Lazervel\PGI\Interface;

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
   * @return string Returns frontend-friendly JSON payload
   */
  public function order(int $amount, string $currency = null, array $notes = []) : array;

  public function isAuthorized() : bool;

  /**
   * Validates payment signature and status Uses both SDK and manual HMAC check
   * 
   * @param string $orderId   [required]
   * @param string $paymentId [required]
   * @param string $signature [required]
   * 
   * @return bool True if signature and capture status are valid
   */
  public function verifySignature(string $orderId, string $paymentId, string $signature) : bool;
}
?>