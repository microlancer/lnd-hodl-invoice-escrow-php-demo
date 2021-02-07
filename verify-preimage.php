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

if (!isset($argv[1]) || !isset($argv[2])) {
	echo "Usage: php verify-preimage.php <preimage-as-base64> <invoice>\n";
	die;
}

$preimageBase64 = $argv[1];
$invoice = $argv[2];

$preimage = base64_decode($preimageBase64);

echo "preimage byte length: " . strlen($preimage)."\n";
echo "preimage base64: " . base64_encode($preimage)."\n";

$preimageHash = hash('sha256', $preimage, true);

$preimageHashString = hash('sha256', $preimage);

echo "preimageHash byte length: " . strlen($preimageHash)."\n";
echo "preimageHash string: " . $preimageHashString ."\n";
echo "preimageHash base64: " . base64_encode($preimageHash)."\n";

$req = new \Lnrpc\PayReqString([
    'pay_req' => $invoice,
]);

$result = $x->DecodePayReq($req)->wait();

$paymentHash = $result[0]->getPaymentHash();

echo "invoice paymentHash: " . $paymentHash . "\n";

if ($paymentHash === $preimageHashString) {
    echo "VERIFICATION PASSED. The given preimage will successfully unlock the given invoice!\n";
} else {
    echo "VERIFICATION FAILED. Wrong or bad preimage for invoice.\n";
}
