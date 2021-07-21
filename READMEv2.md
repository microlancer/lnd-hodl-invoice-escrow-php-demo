# Lightning Trustless Escrow v2

The original version of HODL-invoice escrow has a key problem where the HTLC remains suspended over potentially several routing nodes and is not ideal for long-term holds. An escrow transaction can last days or even months. To solve this problem, we can set up a private channel between the merchant and the arbitration node. The funds in this channel will be suspended during a HODL-invoice but only affects the private channel.

Bob is an arbitrator node. He agrees to arbitrate the transaction between Alice and Charlie. Since Charlie will be receiving the funds from Alice, Charlie will first set up a security deposit private channel with Bob. This can be any amount at least as big as the transaction, but can be much larger in case there may be additional transactions in the future.

On the security deposit private channel, funds cannot be sent or received without Bob's consent.

Charlie must first send the transaction amount to Alice through the security deposit channel which is not transmitted to Alice unless there is a dispute. Once Bob has received the transaction which sends money to Alice as the destination, Bob tells Alice she is now safe to send direct payment to Charlie. 

Once Charlie receives payment from Alice, he ships the Widget.

Once Alice receives the Widget, she confirms with Charlie and Bob and the transaction is complete. Bob can disregard the security deposit transaction. 

If Charlie tries to close the channel without shipping the Widget, then Bob can issue a penalty transaction that takes all the funds in the channel. It's not a good idea for Charlie to try to cheat.

Bob himself does not have custody of the funds. He cannot steal Charlie's security deposit, since the payment is targeting Alice's node and not Bob. The worst he could do is collaborate with Alice, but that would harm his own reputation as a neutral 3rd party.

The arbitration happens with a HODL invoice with a route from Charlie -> Bob -> ... -> Alice. The connection between Charlie and Bob is direct (private). But any path can be used from Bob to Alice.

Alice must generate a refund HODL invoice that pays to her node. This invoice will be given to Charlie. Charlie will pay this invoice using the private channel to Bob. He will provide a preimage that will release the funds to Alice's node in the case of any dispute. Bob will not relay the preimage to Alice, but can verify that it would indeed release the funds properly to her node.

At this point, Bob tells Alice that he has received the security deposit transaction and it is safe to pay Charlie. The security deposit transaction stays in limbo on Bob's node which can be annoying for Bob but it's one of the costs of being an arbitrator. 

1. Charlie creates a secret preimage (for Bob) and public hash (for Alice).
1. Alice creates a safety deposit invoice using Charlie's public hash.
1. Charlie starts payment on the safety deposit invoice through the private channel with Bob.
1. Charlie sends Bob the secret preimage.
1. Bob doesn't relay the payment to Alice (yet) but verifies that the preimage does indeed release the funds and would pay Alice's node properly.
1. Bob tells Alice that the safety deposit invoice is validated and she is now safe to send payment directly to Charlie.
1. Alice pays Charlie's normal product invoice.
1. Charlie receives normal payment, and delivers the goods/services.
1. Alice receives the goods/services and the transaction is complete.
1. Bob discards or drops the safety deposit invoice and will allow it to expire.

This allows normal payments to flow over the LN and private channels to only be required between the merchant and the 3rd party arbitrator. Although HODL invoices can be used without private channels, some transactions can take days/weeks/months to complete, and it's less likely that there will be a chain of intermediate nodes willing to wait that long for the invoices to settle. It's not ideal to have such invoices sit in limbo over the network, but it's acceptable on a private channel dedicated to this exact purpose. The parties are willingly entering an agreement to participate in long-term limbo-suspended invoices privately. In most cases where the transaction is undisputed, there will be no significant impact and the transaction itself is disregarded causing no change in the private channel. The arbitrator node itself cannot steal funds, as the payment only releases funds to the destination node.

Issues:

* What happens if there are multiple transactions at the same time? 
* Will a private channel be able to handle multiple HODL invoices suspended over the same channel? 
* Will the ordering of settlement (if necessary) have an impact?
* How long can a private channel HODL invoice be in limbo? 
* How do penalties work in terms of ordering of channel balance settlements?
* What if a channel is force-closed during a transaction?

