# RFC: Security Bond Channels for Escrow

## Problem

Let's say Alice wants to Pay Carol for merchandise, and to ensure the successful delivery, have Bob be an escrow intermediary.  That way, if the product isn't delivered properly by Carol, Alice isn't stuck having paid for nothing. Bob can assist in recovering funds. But, we don't want to Bob to simply be a "traditional" escrow who has custody of the funds, since he might simply run-off with the funds.

The non-Lightning on-chain solution would be to create a 2-of-3 multisig transaction. However, we want to take advantage of Lightning and it's transaction speed and not clutter the main chain.

One Lightning-based solution is to create a type of HTLC-based escrow can be created using **Hold** invoices in Lightning where the payment doesn't settle until the buyer Alice acknowledges receipt of the item. But the downside to these types of payments is that they remain in-limbo on the network and disrupt the flow of publicly routed payments and channels locking them up. This is especially not ideal in the case long-term shipping times. Nodes do not want HTLCs that sit in limbo. For long-term escrow (ranging from weeks to months), we need a better solution.

## The Proposed Solution

We propose a special **private** lightning channel, let us call it the "security bond channel" between a payment recipient (e.g. Carol the merchant) and escrow (Bob) that will act as an insurance policy to protect the customer in case of long-term failure-to-deliver. 

These special channels act as a security bond which are used to ensure that the escrowed transactions between Alice and Carol are financially secure. In case of a breech-of-contract during a transaction, the funds in this private channel will be used to penalize any bad actors. These private channels are not used for generalized routing or even for common payments. They are used only in the worst-case scenario of a transaction dispute.

Only users who will be on the receiving-end will need to create security bond channels. Users  who are paying do not need such channels, as there is no "flight risk" for their end of the transactions. 

## Transaction Flow With Escrow

Alice wants to buy a book for 15,000 sats from Carol.

**Pre-transaction setup**

1. Carol creates 1m sats private channel with Bob. This can be recycled for many future receipts.
1. Alice tells Bob that she plans to pay Carol 15,000 sats for merchandise.
1. Bob asks Alice to generate a preimage P and H(P), and sends Bob the H(P).
1. Bob creates an insurance policy invoice paid through the private channel that Carol must pay. It will be for 15,000 sats using a very long-term **Hold** invoice. This is created using H(P) of Alice. Bob can only take custody of these funds if Alice permits it by revealing P to Bob.
1. Carol can verify that she is not simply paying Bob directly by comparing the H(P) associated to the invoice with the H(P) that Alice announced.

**Case 1: Successful transaction**

1. Alice pays Carol 15,000 sats directly. 
1. Carol receives the payment, and ships the book.
1. Alice receives the book.
1. Bob never receives P of Alice since everything went smoothly. He simply cancels the private bonded HTLC with Carol.

**Case 2: Dispute**

1. Alice pays Carol 15,000 sats directly. 
1. Alice never receives the book after paying Carol.
1. Carol seems to be unresponsive even after 2 weeks.
1. Alice asks Bob for help to recover the funds.
1. Bob agrees that Carol seems to be failing her obligation.
1. Alice gives Bob her preimage P used to secure the funds in the LN bond payment from Carol to Bob which originally was made using Alice's H(P).
1. Bob uses Alice's preimage, to retrieve the 15,000 sats from the channel with Carol.
1. Bob reimburses Alice the 15,000 sats in good faith.

**Case 3: Carol attempts to defraud via channel force-close attempt**

1. Alice pays Carol 15,000 sats directly. 
1. Carol seems to be unresponsive even after 2 weeks.
1. Alice asks Bob for help to recover the funds.
1. Carol tries to be sneaky and begins a force-close on the private channel.
1. The channel cannot be closed until the insurance payment HTLC is resolved.
1. Alice gives Bob her preimage as in Case 2, and Bob can settle the insurance invoice before the channel is closed.
1. Bob reimburses Alice the 15,0000 sats in good faith.

In all scenarios, Bob acts as a "Bonded Escrow Node" acting as an intermediary for lightning transactions. The only requirement is for the payment receivers to create the security bond private channels.

## Key attributes

- Because the security bond channels are private, the HTLCs can remain "in limbo" for quite some time without disrupting the LN network as a whole. Public routing paths are unaffected.
- The actual transaction (lightning payment for the merchandise) from Alice to Carol is settled immediately. This public invoice does NOT remain "in limbo" at all.
- The security bond LN transaction is direct between Carol and Bob, so there is no risk of an intermediary node disappearing during the transaction before it is settled, cancelled, or expired.
- This does not prevent Alice and Bob colluding together against Carol. If they collude, Bob can easily take the funds committed by Carol. Bob, however, will be incentivized to be a trustworthy bonded escrow node and maintain a good reputation in order to gain business.
- If Bob becomes unresponsive, but Carol is cooperative, there is no problem. However, if Bob is unresponsive and Carol is cheating, Alice won't have a way to recover funds.
- It's always in the best interest of Alice and Carol to resolve their dispute together and not involve Bob, since once the H(P) is revealed to Bob, he can send the money fully or partially to anyone or himself at his own discretion.
- Bob can charge a fee for this escrow service.
- One downside is the receiver of funds must have some liquidity to pay for the bonded channel in advance of receiving payment.

## Improvements/Questions

Part of the security of this process is that Alice generates P and H(P) while Bob prevents Alice from cheating Carol. In this way, neither can cheat each other. However, is there a way to exchange keys and set up a private channel between Alice and Carol in such a way that Bob would not be needed as an escrow/intermediary?

A private bonded channel can only have up to ~400 in-flight HTLCs for purposes of bonding. This limitation can become an issue for high-volume merchants. Multiple channels to the same merchant might need to be used. Is there a way to avoid this issue?
