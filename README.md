# Elwin

Elwin owns the provider-neutral boundary between exact human input and evolving interpretation.

`UserInput` is immutable, byte-preserving evidence. `Intent` is a versioned interpretation and `Conversation` is a provider-independent deliberation identity. Superseding an Intent preserves the earlier version and links its replacement. None imply that executable work exists or may call a provider.

`Conversation` carries immutable input/intent references, provider-source message observations, question/response/intervention events, and portable decision/plan handoffs. It can pause or finish without creating a Run; provider session identifiers remain source observations rather than canonical identity.

## Clarification questions

`ClarificationQuestion` records both why input is needed and the exact `AllowedResponseShape` that can unblock the question. The provider-neutral shapes cover single and multiple selection, bounded text, confirmation, decision requests, and attachment/evidence requests. Responses use portable evidence references and are checked against the question identity and constraints.

Every shape also accepts an explicit refusal or cancellation. These are safe terminal responses to the question, not fabricated answers. Consumers such as Logres may use the result to drive paused-work transitions; Burdgeon or another client chooses how to render and collect it. Elwin contains no widget, layout, or provider SDK contract.

## Resumable handoffs

`ResumableHandoff` is Elwin's package-neutral pause/intervention boundary for Logres and Burdgeon. It identifies the paused work and question, carries an opaque resume token and consumer-owned checkpoint reference, exposes JSON-portable display context, and records an answer, cancellation, or expiry. Its allowed transitions and `HandoffQuery` semantics let adapters find unanswered or resumable handoffs without copying readiness rules. Elwin does not suspend work, replay handlers, or resume a Logres state machine; execution coordination remains downstream.

## Human input acceptance

`UserInputDraft` is mutable editor state and is not historical evidence. `SendPrimaryAskInput` is the explicit acceptance boundary: it stores one immutable `PrimaryAskUserInput` through the provider-neutral `UserInputStore` contract and deduplicates delivery by channel, submitting actor, and client submission ID. A Primary Ask requires a nonempty human-authored string and may include ordered attachment parts. Semantic author and submitting actor are separate; delegated human authorship requires an attestation reference.

Intent interpretation happens after input acceptance. `InferredIntent` exposes inference provenance and uncertainty; a human edit creates a new `UserEditedIntent` version and supersedes the earlier interpretation without changing its source input. Independent outcomes may use sibling intent families linked to the same source input. Elwin owns these contracts; a Funes adapter owns canonical durable persistence, and Burdgeon owns the Send and inspection UI.

`Twinkle` is a durable pre-commitment possibility, presented by Burdgeon as an Idea. Elwin owns its current lifecycle and its references to Quain-owned concepts; promotion retains a reference to Titan-owned work rather than changing the Twinkle into a plan.

The acceptance tests consume `sifrious/harness-contract-fixtures` for shared cross-package identities, including the pause → answer → resume handoff scenario; no package-local fixture copy is maintained.

Run `composer install && composer test` to verify the contracts.

## License

Copyright © 2026 Sifrious. All rights reserved. This is publicly viewable
proprietary software, not open-source software. See [LICENSE.md](LICENSE.md).
