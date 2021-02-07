<?php

putenv('GRPC_SSL_CIPHER_SUITES=HIGH+ECDSA');

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

$preimage = openssl_random_pseudo_bytes(32, $crypto_strong);

echo "crypto_strong: " . ($crypto_strong ? "true" : " false") . "\n";
echo "preimage byte length: " . strlen($preimage)."\n";
echo "preimage base64: " . base64_encode($preimage)."\n";

$preimageHash = hash('sha256', $preimage, true);

echo "preimageHash byte length: " . strlen($preimageHash)."\n";
echo "preimageHash base64: " . base64_encode($preimageHash)."\n";

