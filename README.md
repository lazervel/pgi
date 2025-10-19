PGI - Payment Getway Integration
================================

PGI is a PHP library that provides ready-to-use integrations for multiple payment gateways.

<a href="https://github.com/indianmodassir"><img src="https://img.shields.io/badge/Author-Modassir-%2344cc11"/></a>
<a href="LICENSE"><img src="https://img.shields.io/github/license/lazervel/pgi"/></a>
<a href="https://packagist.org/packages/lazervel/pgi"><img src="https://img.shields.io/packagist/dt/lazervel/pgi.svg" alt="Total Downloads"></a>
<a href="https://github.com/lazervel/pgi/stargazers"><img src="https://img.shields.io/github/stars/lazervel/pgi"/></a>
<a href="https://github.com/lazervel/pgi/releases"><img src="https://img.shields.io/github/release/lazervel/pgi.svg" alt="Latest Version"></a>
<a href="https://github.com/lazervel/pgi/graphs/contributors"><img src="https://img.shields.io/github/contributors/lazervel/pgi" alt="Contributors"></a>
<a href="/"><img src="https://img.shields.io/github/repo-size/lazervel/pgi" alt="Repository Size"></a>


### Composer Installation

Installation is super-easy via [Composer](https://getcomposer.org/)

```bash
composer require lazervel/pgi
```

OR:

Click to [Browse package](https://packagist.org/packages/lazervel/pgi)

## Payment Integrations

- [Razorpay Integration](#razorpay-integration)

## Razorpay Integration

Start accepting domestic and international payments from customers on your website using the Razorpay Payment Gateway. Razorpay has developed the Standard Checkout method and manages it. You can configure payment methods, orders, company logo and also select custom colour based on your convenience. Razorpay supports these payment methods and international currencies.

### Configuration

```php
use Lazervel\PGI\Razorpay;

require 'vendor/autoload.php';
$rzp = new Razorpay;
```

### Create an Order in Server

In the sample app, the index.php file contains the code for order creation using Orders API.

```php
$rzp->order([
  'amount' => 50, // In Rupees        [required]
  'currency',     // default INR      [optional]
  'notes'         // default empty [] [optional]
]);

// Error Handling
$rzp->then(function($response) {
  print_r($response);
})->catch(function($err) {
  die($err);
});
```

### Verify Payment Signature

This is a mandatory step that allows you to confirm the authenticity of the details returned to the checkout for successful payments.

```php

$orderId   = $_SESSION['razorpay_order_id'];   // Where you stored
$paymentId = $_SESSION['razorpay_payment_id']; // Where you stored
$signature = $_SESSION['razorpay_signature'];  // Where you stored

// All parameter is required
$rzp->verifySignature($orderId, $paymentId, $signature);

// Error Handling
$rzp->then(function($payment) {
  // Success payment
  print_r($payment);
})->catch(function($err) {
  // Failed payment
  die($err);
});
```

License
-------

Licensed Under [MIT](LICENSE)

Copyright (c) 2025 [Indian Modassir](https://github.com/indianmodassir)

Contributing
------------

Pull requests are welcome! For major changes, please open an issue first to discuss what you would like to change.

## Resources
[Report issue](https://github.com/lazervel/path/issues) and [send Pull Request](https://github.com/lazervel/path/pulls) in the [main Lazervel repository](https://github.com/lazervel/path)