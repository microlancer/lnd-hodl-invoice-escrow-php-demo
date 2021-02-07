<?php

putenv('GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

$certPath = \LnEscrow\Config::CERT_PATH;
$macaroonPath = \LnEscrow\Config::MACAROON_PATH;
$host = \LnEscrow\Config::HOST;

$cert = file_get_contents($certPath);
$macaroon = file_get_contents($macaroonPath);
$callback = function ($metadata) use ($macaroon) {
        return ['macaroon' => [bin2hex($macaroon)]];
    };

$credentials = \Grpc\ChannelCredentials::createSsl($cert);

$x = new \Lnrpc\LightningClient($host,['credentials'=>$credentials,'update_metadata'=>$callback]);

$y = new \Invoicesrpc\InvoicesClient($host,['credentials'=>$credentials,'update_metadata'=>$callback]);


if (!isset($argv[1])) { 
	echo "Usage: php create-hold-invoice.php <preimage-hash-as-base64>\n";
	die;
}

$preimageHash = base64_decode($argv[1]);

$s = new \Invoicesrpc\AddHoldInvoiceRequest(['hash' => $preimageHash, 'value' => 1]);

$result = $y->AddHoldInvoice($s);

$invoice = $result->wait()[0]->getPaymentRequest();

echo "HoldInvoice: " . $invoice ."\n";

echo "When you pay this invoice with your wallet software, the transaction will hang until the invoice is settled.\n";

