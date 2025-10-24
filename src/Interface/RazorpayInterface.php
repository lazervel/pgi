<?php

declare(strict_types=1);

namespace Lazervel\PGI\Interface;

/**
 * Seamless Razorpay gateway support for the PGI library.
 * @see https://github.com/lazervel/pgi
 * @package Lazervel\PGI
 */
interface RazorpayInterface
{
  /**
   * Creates a new Razorpay order
   * 
   * @param int         $amount   [required]
   * @param string|null $currency [optional]
   * @param array       $notes    [optional]
   * 
   * @return array|false Returns frontend-friendly order payload
   */
  public function order(int $amount, string $receipt = null, string $currency = null, array $notes = []);

  /**
   * Validates payment signature and status Uses both SDK and manual HMAC check
   * 
   * @param string $orderId   [required]
   * @param string $paymentId [required]
   * @param string $signature [required]
   * @param int    $amount    [required]
   * 
   * @return false| True if signature and capture status are valid
   */
  public function verifySignature(string $orderId, string $paymentId, string $signature, int $amount);
}
?>