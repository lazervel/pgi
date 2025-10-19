<?php

declare(strict_types=1);

namespace Lazervel\PGI;

use Lazervel\PGI\Exception\InvalidAmountException;
use Lazervel\PGI\Provider\Provider;

use Razorpay\Api\Errors\SignatureVerificationError;
use Razorpay\Api\Api;

/**
 * Razorypay Payment Getway Integration PHP Library
 */
class Razorpay extends Provider
{
  /**
   * @var \Razorpay\Api\Api $Api
   */
  private $Api;

  public function __construct()
  {
    parent::__construct('RZP_KEY_SECRET', 'RZP_KEY_ID');
    try {
      $this->Api = new Api($this->KEY_ID, $this->KEY_SECRET);
    } catch(\Exception $err) {
      $this->rejectWith($err);
    }
  }

  /**
   * @param int $amount
   * @param string|null $currency
   * @param array $notes
   * 
   * @return string
   */
  public function order(int $amount, string $currency = null, array $notes = [])
  {
    $orderInfo = [
      'receipt'         => $this->uniqueId(),
      'amount'          => $amount * 100,
      'currency'        => $currency ?? 'INR',
      'notes'           => $notes,
      'payment_capture' => 1
    ];

    // Validate Amount value
    if (!$amount || $amount <= 0) {
      return $this->rejectWith(\sprintf('Invalid Amount [%d]', $amount));
    }

    // Create RZP order
    $order = $this->Api->order->create($orderInfo);
    $this->resolveWith($order);

    // Send order details to frontend
    return \json_encode([
      'order_id' => $order->id,
      'reciept'  => $this->uniqueId(),
      'amount'   => $amount * 100,
      'currency' => $currency ?? 'INR',
      'key'       => $this->KEY_ID
    ]);
  }

  /**
   * @param
   * @param
   * @param string $signature
   * 
   * @return bool
   */
  public function verifySignature($orderId, $paymentId, $signature) : bool
  {
    try {
      // Signature Verification using SDK
      $this->Api->utility->verifyPaymentSignature([
        'razorpay_order_id'   => $orderId,
        'razorpay_payment_id' => $paymentId,
        'razorpay_signature'  => $signature
      ]);

      // Fetch payment status
      $payment = $this->Api->payment->fetch($paymentId);
      $isCaptured = $payment->status === 'captured';

      // Signature Verification manually
      $payload = \sprintf('%s|%s', $orderId, $paymentId);
      $s = \hash_hmac('sha256', $payload, $this->KEY_SECRET);

      // Extra security Layer implementation
      // Paid
      if ($isCaptured && \hash_equals($s, $signature)) {
        $this->resolveWith($payment);
        return true;

      // Failed/Unpaid
      } else {
        \http_response_code(400);
        $this->rejectWith(['code' => 400, 'error' => 'Invalid Signature']);
        return false;
      }

    // Signature mismatch reject the payment / flag for review
    } catch(\Exception $err) {

      \http_response_code(400);
      $this->rejectWith($err);
      return false;
    }
  }
}
?>