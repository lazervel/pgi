<?php

declare(strict_types=1);

namespace Lazervel\PGI;

use Lazervel\PGI\Exception\InvalidAmountException;
use Lazervel\PGI\Interface\RazorpayInterface;
use Lazervel\PGI\Provider\Provider;
use Razorpay\Api\Api;

/**
 * Seamless Razorpay gateway support for the PGI library.
 */
class Razorpay extends Provider implements RazorpayInterface
{
  public string $receipt;
  private $Api;

  // Initializes Razorpay SDK with credentials Sets up API instance for transactions
  public function __construct()
  {
    parent::__construct('RZP_KEY_ID', 'RZP_KEY_SECRET');
    $this->Api = new Api($this->KEY_ID, $this->KEY_SECRET);
  }

  /**
   * Creates a new Razorpay order
   * 
   * @param int         $amount   [required]
   * @param string|null $currency [optional]
   * @param array       $notes    [optional]
   * 
   * @throws \Lazervel\PGI\Exception\InvalidAmountException
   * @return array Returns frontend-friendly order payload
   */
  public function order(int $amount, string $currency = null, array $notes = []) : array
  {
    $this->receipt = (string)($this->receipt ?? \time());
    $orderInfo = [
      'receipt'         => $this->receipt,
      'amount'          => $amount * 100,
      'currency'        => $currency ?? 'INR',
      'notes'           => $notes,
      'payment_capture' => 1
    ];

    // Validate Amount value
    if (!$amount || $amount <= 0) {
      throw new InvalidAmountException(\sprintf('Invalid Amount [%d].', $amount));
    }

    // Create RZP order
    $order = $this->Api->order->create($orderInfo);

    // Send order details to frontend
    return [
      'order_id' => $order->id,
      'reciept'  => $orderInfo['receipt'],
      'amount'   => $orderInfo['amount'],
      'currency' => $orderInfo['currency'],
      'key'      => $this->KEY_ID
    ];
  }

  public function resetAuthorization()
  {
    if (!$this->EnableSessionAuthorization) {
      throw new \Exception('Session Authorization is not enabled!');
    }

    if (!isset($_SESSION)) \session_start();

    unset(
      $_SESSION['razorpay_payment_id'], $_SESSION['razorpay_order_id'],
      $_SESSION['razorpay_signature'], $_SESSION['razorpay_amount']
    );

    return true;
  }

  /**
   * @return false| True if Authorized, Otherwise false
   */
  public function isAuthorized()
  {
    if (!$this->EnableSessionAuthorization) {
      throw new \Exception('Session Authorization is not enabled!');
    }

    try {
      $expect_session_id = $this->sessionId();
      if (!(
        isset($_SESSION['session_id']) &&
        isset($_SESSION['razorpay_payment_id']) &&
        isset($_SESSION['razorpay_order_id']) &&
        isset($_SESSION['razorpay_signature']) &&
        isset($_SESSION['razorpay_amount'])
      )) {
        return false;
      }

      $isVerifiedSession = $expect_session_id === $_SESSION['session_id'];

      $paymentId = $_SESSION['razorpay_payment_id'];
      $orderId   = $_SESSION['razorpay_order_id'];
      $signature = $_SESSION['razorpay_signature'];
      $amount    = (int)$_SESSION['amount'];

      $verifiedPayment = $this->verifySignature($orderId, $paymentId, $signature);
      $isMatchedAmount = (int)$verifiedPayment->amount == $amount;
      $verified = $isMatchedAmount && $isVerifiedSession && $verifiedPayment;

      return $verified ? $verifiedPayment : false; 
    } catch(\Exception $err) {
      return false;
    }
  }

  /**
   * Validates payment signature and status Uses both SDK and manual HMAC check
   * 
   * @param string $orderId   [required]
   * @param string $paymentId [required]
   * @param string $signature [required]
   * 
   * @return false| True if signature and capture status are valid
   */
  public function verifySignature(string $orderId, string $paymentId, string $signature)
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
      $isCaptured = $payment->status === 'captured';

      // Manually Verify
      $payload = \sprintf('%s|%s', $orderId, $paymentId);
      $generate_signature = \hash_hmac('sha256', $payload, $this->KEY_SECRET);
      $verified = $isCaptured && \hash_equals($generate_signature, $signature);

      if ($this->EnableSessionAuthorization && $verified) {
        $_SESSION['session_id']      = $this->sessionId();
        $_SESSION['razorpay_amount'] = $payment->amount / 100;

        $_SESSION['razorpay_payment_id'] = $paymentId;
        $_SESSION['razorpay_order_id']   = $orderId;
        $_SESSION['razorpay_signature']  = $signature;
      }
      return $verified ? $payment : false;
    } catch(\Exception $err) {
      return false;
    }
  }
}
?>