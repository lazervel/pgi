<?php

declare(strict_types=1);

namespace Lazervel\PGI;

use Lazervel\PGI\Interface\RazorpayInterface;
use Lazervel\Cryptor\Cryptor;
use Razorpay\Api\Api;

/**
 * Seamless Razorpay gateway support for the PGI library.
 * @see https://github.com/lazervel/pgi
 * @package Lazervel\PGI
 */
class Razorpay extends Cryptor implements RazorpayInterface
{
  private $Api;
  private string $KEY_SECRET;
  private string $KEY_ID;
  public bool $isDebug = false;
  

  public function __construct(?string $KEY_ID = null, ?string $KEY_SECRET = null)
  {
    $KEY_SECRET = $KEY_SECRET ?? ($_ENV['RZP_KEY_SECRET'] ?? $_ENV['KEY_SECRET'] ?? '');
    $KEY_ID = $KEY_ID ?? ($_ENV['RZP_KEY_ID'] ?? $_ENV['KEY_ID'] ?? '');

    if (!($KEY_ID && $KEY_SECRET)) {
      throw new \Exception(\sprintf('Key ID or SECRET Not Found!'));
    }

    parent::__construct();
    $this->Api = new Api($KEY_ID, $KEY_SECRET);
    $this->KEY_SECRET = $this->encrypt($KEY_SECRET, null, $KEY_ID);
    $this->KEY_ID = $KEY_ID;
  }

  /**
   * Creates a new Razorpay order
   * 
   * @param int         $amount   [required]
   * @param string|null $currency [optional]
   * @param array       $notes    [optional]
   * 
   * @return array|false Returns frontend-friendly order payload
   */
  public function order(int $amount, string $receipt = null, string $currency = null, array $notes = []) : array
  {
    $receipt = $receipt ?? \sprintf('%s', \time());
    $orderInfo = [
      'receipt'         => $receipt,
      'amount'          => $amount * 100,
      'currency'        => $currency ?? 'INR',
      'notes'           => $notes,
      'payment_capture' => 1
    ];

    if ($amount <= 0) {
      if ($this->isDebug) {
        throw new \InvalidArgumentException(\sprintf('Invalid Amount %d', $amount));
      }
      return false;
    };

    try {
      // Create RZP order
      $order = $this->Api->order->create($orderInfo);

      // Send order details to frontend
      return [
        'order_id' => $order->id,
        'receipt'  => $orderInfo['receipt'],
        'amount'   => $orderInfo['amount'],
        'currency' => $orderInfo['currency'],
        'key'      => $this->KEY_ID
      ];
    } catch(\Exception $err) {
      if ($this->isDebug) {
        throw $err;
      }
      return false;
    }
  }

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
  public function verifySignature(string $orderId, string $paymentId, string $signature, int $amount)
  {
    try {
      // Signature Verification paid/failed using SDK
      $this->Api->utility->verifyPaymentSignature([
        'razorpay_order_id'   => $orderId,
        'razorpay_payment_id' => $paymentId,
        'razorpay_signature'  => $signature
      ]);

      // Fetch payment status
      $payment = $this->Api->payment->fetch($paymentId);
      $isOrderMatch = $payment->order_id === $orderId;
      $isCaptured = $isOrderMatch && $payment->status === 'captured';

      // Handle mismatch amount!
      if (!($amount && $payment->amount === $amount * 100)) {
        return false;
      }

      // Manually Verify
      $payload = \sprintf('%s|%s', $orderId, $paymentId);
      $KEY_SECRET = $this->decrypt($this->KEY_SECRET, null, $this->KEY_ID);
      $generate_signature = \hash_hmac('sha256', $payload, $KEY_SECRET);

      // Final Verification
      $verified = $isCaptured && \hash_equals($generate_signature, $signature);

      return $verified ? $payment : false;
    } catch(\Exception $err) {
      if ($this->isDebug) {
        throw $err;
      }
      return false;
    }
  }

  /**
   * Prevents serialization of sensitive data.
   *
   * @return array An empty array to avoid exposing sensitive information.
   */
  public function __sleep() : array
  {
    return [];
  }

  /**
   * Controls what is displayed during debugging (e.g., var_dump()).
   *
   * @return array An array hiding the encryption key from output.
   */
  public function __debugInfo(): array
  {
    return [];
  }

  /**
   * Destructor to securely erase the encryption key from memory.
   */
  public function __destruct()
  {
    $this->KEY_ID = $this->KEY_SECRET = '';
    $this->Api = null;
  }
}
?>