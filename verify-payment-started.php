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
	echo "Usage: php verify-payment-started.php <preimage-hash-as-base64>\n";
	die;
}

$preimageHashBase64 = $argv[1];

$preimageHash = base64_decode($preimageHashBase64);

$preimageHashString = hash('sha256', $preimageHash);

echo "preimageHash byte length: " . strlen($preimageHash)."\n";
echo "preimageHash string: " . $preimageHashString ."\n";
echo "preimageHash base64: " . base64_encode($preimageHash)."\n";

$req = new \Lnrpc\PaymentHash([
    'r_hash' => $preimageHash
]);

$inv = new \Lnrpc\Invoice();
$inv->getState();

$result = $x->LookupInvoice($req)->wait();

$state = $result[0]->getState();

echo "invoice state: " . $state . "\n";

switch ($state) {
    case 0: echo "Invoice OPEN and waiting for payment to be initiated.\n";
        break;
    case 1: echo "Invoice SETTLED.\n";
        break;
    case 2: echo "Invoice CANCELLED.\n";
        break;
    case 3: echo "Invoice ACCEPTED and waiting for preimage settlement.\n";
        break;
}
    
