
# Lightning Escrow using LND Hodl Invoices

This is a set of PHP scripts that demonstrates the use of Hold (or Hodl) Invoices in the process of escrow. It allows for a 3rd party to help mediate the transaction, while not being able to steal and run away with the funds during the transaction.

For background and reference, see: https://wiki.ion.radar.tech/tech/research/hodl-invoice

## Install

1. Copy `config.php-example` to `config.php` and edit the values.
2. Run `composer update` to install the php-lnd-grpc dependency. Note that the version of LND will make a difference, so be sure to match the library's LND API version with the version of LND that you will be connecting to.

## Demo

In this walkthrough, Alice is the customer who wants to buy an item from Bob. Alice and Bob have agreed to a neutral 3rd party escrow named Charlie who will help be intermediate party in the transaction. Charlie will make sure that:

* Before Bob ships the item, Alice has actually paid.
* Before Bob gets paid, that Alice has actually received the item.

At a highlevel, the whole process looks like this:

1. Alice creates a secret preimage (for Charlie) and hash (for Bob).
2. Bob creates an invoice using alice's hash.
3. Alice starts payment on the invoice (it is now in a limbo) on her wallet.
4. Charlie matches the invoice with the secret preimage.
5. Charlie tells Bob her payment is good.
6. Bob ships the product to Alice.
7. Alice confirms she received the product.
8. Charlie gives Bob the secret preimage.
9. Bob settles the payment on the invoice using the secret preimage and sees the funds in his wallet. 
10. The transaction is complete.

Here is the detailed process along with the scripts and output used to demonstrate a complete working example. 

### 1. Alice (customer) creates a preimage and hashes it. 

```cli
[alice] $ php generate-preimage-and-hash.php
crypto_strong: true
preimage byte length: 32
preimage base64: 6X6n7r2dQKGOteWh1fFEBhl7nSMVGDHI4Mwdtb8KtOc=
preimageHash byte length: 32
preimageHash base64: OqNT0brwbMijomrk9FEnoh5Y4uMDSWLGnXuRG80g8W4=
```

### 2. Alice (customer) transmits the hashed preimage to Bob (merchant) and actual preimage to Charlie (intermediary)


> \<**alice**> Hey Bob. Here's my preimageHash that I encoded base64 so I can paste it in chat without weird symbols: OqNT0brwbMijomrk9FEnoh5Y4uMDSWLGnXuRG80g8W4= 
>
> \<**bob**> Great, thanks!

> \<**alice**> (private chat) Hey Charlie, here's the secret preimage: 6X6n7r2dQKGOteWh1fFEBhl7nSMVGDHI4Mwdtb8KtOc=
>
> \<**charlie**> (private chat) Got it, I'll keep it safe until you give me the green light to give it to bob.

### 3. Bob (merchant) creates a Hold Invoice based on that preimage hash.

```cli
[bob] $ php create-hold-invoice.php OqNT0brwbMijomrk9FEnoh5Y4uMDSWLGnXuRG80g8W4=
HoldInvoice: lnbc10n1psplqlspp5823485d67pkv3gazdtj0g5f85g093chrqdyk935a0wg3hnfq79hqdqqcqzpgsp5t0zp56hfdu5fyaammj4scn0x44myhg4frtvn38jgyh389sw0nfms9qy9qsqm7gztpyapwjmch0vjkvm00wwzr9kwz54s6tedgztk0azkfnlqzt3tujqsrlxvj4ku2gxr6rsqmfdssv7fku83x0qemh952m272jajugpqr6w5g
When you pay this invoice with your wallet software, the transaction will hang until the invoice is settled.
[bob] $
```

### 4. Bob (merchant) gives the generated invoice string to Alice (customer).

> \<**bob**> Here's your invoice Alice (CC: Charlie): lnbc10n1psplqlspp5823485d67pkv3gazdtj0g5f85g093chrqdyk935a0wg3hnfq79hqdqqcqzpgsp5t0zp56hfdu5fyaammj4scn0x44myhg4frtvn38jgyh389sw0nfms9qy9qsqm7gztpyapwjmch0vjkvm00wwzr9kwz54s6tedgztk0azkfnlqzt3tujqsrlxvj4ku2gxr6rsqmfdssv7fku83x0qemh952m272jajugpqr6w5g
>
> \<**alice**> Thanks! I'll start payment ASAP.
>
> \<**charlie**> Thanks!


### 5. Bob (merchant) checks the status of his invoice to see if she has initiated payment. He should not ship the item if it's not ACCEPTED yet.

```cli
[bob] $ php verify-payment-started.php OqNT0brwbMijomrk9FEnoh5Y4uMDSWLGnXuRG80g8W4=
preimageHash byte length: 32
preimageHash string: f297aa95551486368ae8781108a4bfda6c51dba5bbe16b93b13eceecebfd5f72
preimageHash base64: OqNT0brwbMijomrk9FEnoh5Y4uMDSWLGnXuRG80g8W4=
invoice state: 0
Invoice OPEN and waiting for payment to be initiated.
[bob] $
```

Here, the invoice is still in the `OPEN` state which means that Alice hasn't started yet, so he should NOT ship the item.

### 6. Alice (customer) opens her Lightning wallet and pays the invoice. The transaction will now start, but will be in a limbo "hold" state on her wallet (payment-in-progress spinner may be displayed).

```cli
[alice] $ lncli payinvoice lnbc10n1psplqlspp5823485d67pkv3gazdtj0g5f85g093chrqdyk935a0wg3hnfq79hqdqqcqzpgsp5t0zp56hfdu5fyaammj4scn0x44myhg4frtvn38jgyh389sw0nfms9qy9qsqm7gztpyapwjmch0vjkvm00wwzr9kwz54s6tedgztk0azkfnlqzt3tujqsrlxvj4ku2gxr6rsqmfdssv7fku83x0qemh952m272jajugpqr6w5g --allow_self_payment
Payment hash: 3aa353d1baf06cc8a3a26ae4f45127a21e58e2e3034962c69d7b911bcd20f16e
Description:
Amount (in satoshis): 1
Fee limit (in satoshis): 1
Destination: 02bb10aaa77a95a358cebb2d112c4de00e47c08f56e89b1acb4487ddd44cc98d6d
Confirm payment (yes/no): yes
Amount + fee:   0 + 0 sat
Payment hash:   3aa353d1baf06cc8a3a26ae4f45127a21e58e2e3034962c69d7b911bcd20f16e
Payment status: IN_FLIGHT
.................................................
```

### 7. Bob (merchant) waits a bit and checks the status of the invoice again.

```cli
[bob] $ php verify-payment-started.php OqNT0brwbMijomrk9FEnoh5Y4uMDSWLGnXuRG80g8W4=
preimageHash byte length: 32
preimageHash string: f297aa95551486368ae8781108a4bfda6c51dba5bbe16b93b13eceecebfd5f72
preimageHash base64: OqNT0brwbMijomrk9FEnoh5Y4uMDSWLGnXuRG80g8W4=
invoice state: 3
Invoice ACCEPTED and waiting for preimage settlement.
[bob] $
```

Everything looks good now, and the invoice is in the `ACCEPTED` state which means Alice has started payment but he still needs to get the green light from Charlie.

> \<**bob**> I see the payment started Alice, thanks! Charlie, did you get her preimage?
>
> \<**charlie**> Checking it now...

### 8. In the meantime, Charlie (intermediary) verifies that the preimage he received from Alice (customer) matches the invoice he received from Bob (merchant). He should verify the description, amount, expiry, and destination node.

```cli
[charlie] $ php verify-preimage.php 6X6n7r2dQKGOteWh1fFEBhl7nSMVGDHI4Mwdtb8KtOc= lnbc10n1psplqlspp5823485d67pkv3gazdtj0g5f85g093chrqdyk935a0wg3hnfq79hqdqqcqzpgsp5t0zp56hfdu5fyaammj4scn0x44myhg4frtvn38jgyh389sw0nfms9qy9qsqm7gztpyapwjmch0vjkvm00wwzr9kwz54s6tedgztk0azkfnlqzt3tujqsrlxvj4ku2gxr6rsqmfdssv7fku83x0qemh952m272jajugpqr6w5g
preimage byte length: 32
preimage base64: 6X6n7r2dQKGOteWh1fFEBhl7nSMVGDHI4Mwdtb8KtOc=
preimageHash byte length: 32
preimageHash string: 3aa353d1baf06cc8a3a26ae4f45127a21e58e2e3034962c69d7b911bcd20f16e
preimageHash base64: OqNT0brwbMijomrk9FEnoh5Y4uMDSWLGnXuRG80g8W4=
invoice paymentHash: 3aa353d1baf06cc8a3a26ae4f45127a21e58e2e3034962c69d7b911bcd20f16e
VERIFICATION PASSED. The given preimage will successfully unlock the given invoice!
[charlie] $
```

Everything looks good on Charlie's end as he sees the `VERIFICATION PASSED` meaning the invoice matched the actual preimage he got from Alice.


### 9. Charlie (intermediary) lets Bob (merchant) know that he got the preimage from Alice for the invoice and it is valid, so he can now safely ship the item.

> \<**charlie**> Hey bob, the preimage is solid. It matches the invoice, so you're safe to ship.
>
> \<**bob**> Great! Let me send the item to alice.

### 10. Bob (merchant) ships the item to Alice (customer).

> \<**bob**> Hey alice! I sent you the item you purchased.
>
> \<**alice**> Great, let me check my inbox...
>
> \<**alice**> Perfect, it has arrived safe and sound!

### 11. Alice (customer) receives the purchased item successfully. Satisfied, she tells Charlie that everything is all good to complete the payment without any dispute on her end.

> \<**alice**> charlie, you can transmit my preimage if you want. I'll also just give it to you directly bob, in case charlie isn't awake yet. 6X6n7r2dQKGOteWh1fFEBhl7nSMVGDHI4Mwdtb8KtOc=
>
> \<**bob**> Thanks Alice!


### 12. Charlie (intermediary) now gives the actual preimage to Bob (merchant).

> \<**charlie**> bob, here is the preimage in case you didn't get it from Alice. 6X6n7r2dQKGOteWh1fFEBhl7nSMVGDHI4Mwdtb8KtOc=
>
> \<**bob**> Thanks charlie!

### 13. Bob (merchant) settles the invoice.

```cli
[bob] $ php settle-invoice.php 6X6n7r2dQKGOteWh1fFEBhl7nSMVGDHI4Mwdtb8KtOc=
preimage byte length: 32
preimage base64: 6X6n7r2dQKGOteWh1fFEBhl7nSMVGDHI4Mwdtb8KtOc=
preimageHash byte length: 32
preimageHash base64: OqNT0brwbMijomrk9FEnoh5Y4uMDSWLGnXuRG80g8W4=
[
    {},
    {
        "metadata": [],
        "code": 0,
        "details": ""
    }
]
```

Here, there were no errors, so the funds are now in Bob's (merchant) account. He can also verify the invoice state.

```cli
[bob] $ php verify-payment-started.php OqNT0brwbMijomrk9FEnoh5Y4uMDSWLGnXuRG80g8W4=
preimageHash byte length: 32
preimageHash string: f297aa95551486368ae8781108a4bfda6c51dba5bbe16b93b13eceecebfd5f72
preimageHash base64: OqNT0brwbMijomrk9FEnoh5Y4uMDSWLGnXuRG80g8W4=
invoice state: 1
Invoice SETTLED.
```

The invoice is now `SETTLED` and the transaction is complete.

### 14. Alice (customer) sees in her Lightning wallet, that the transaction has actually completed.

```cli
Amount + fee:   1 + 0.002 sat
Payment hash:   3aa353d1baf06cc8a3a26ae4f45127a21e58e2e3034962c69d7b911bcd20f16e
Payment status: SUCCEEDED, preimage: e97ea7eebd9d40a18eb5e5a1d5f14406197b9d23151831c8e0cc1db5bf0ab4e7
```
> \<**alice**> Thanks everyone!
> 
> \<**bob**> Thanks!
>
> \<**charlie**> Anytime!


