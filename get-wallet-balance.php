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
$gir = new \Lnrpc\WalletBalanceRequest();
$result = $x->WalletBalance($gir);
echo "Balance:".$result->wait()[0]->getTotalBalance()."\n";

