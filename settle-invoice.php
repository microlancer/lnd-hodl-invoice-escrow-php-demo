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

$y = new \Invoicesrpc\InvoicesClient($host,['credentials'=>$credentials,'update_metadata'=>$callback]);

if (!isset($argv[1])) {
	echo "Usage: php settle-invoice.php <preimage-as-base64>\n";
	die;
}

$preimageBase64 = $argv[1];

$preimage = base64_decode($preimageBase64);

echo "preimage byte length: " . strlen($preimage)."\n";
echo "preimage base64: " . base64_encode($preimage)."\n";

$preimageHash = hash('sha256', $preimage, true);

echo "preimageHash byte length: " . strlen($preimageHash)."\n";
echo "preimageHash base64: " . base64_encode($preimageHash)."\n";

$msg = new \Invoicesrpc\SettleInvoiceMsg(['preimage' => $preimage]);
$result = $y->SettleInvoice($msg);

echo json_encode($result->wait(), JSON_PRETTY_PRINT) . "\n";

