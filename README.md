# Elwin

Elwin owns the provider-neutral boundary between exact human input and evolving interpretation.

`UserInput` is immutable, byte-preserving evidence. `Intent` is a versioned interpretation and `Conversation` is a provider-independent deliberation identity. Superseding an Intent preserves the earlier version and links its replacement. None imply that executable work exists or may call a provider.

## Human input acceptance

`UserInputDraft` is mutable editor state and is not historical evidence. `SendPrimaryAskInput` is the explicit acceptance boundary: it stores one immutable `PrimaryAskUserInput` through the provider-neutral `UserInputStore` contract and deduplicates delivery by channel, submitting actor, and client submission ID. A Primary Ask requires a nonempty human-authored string and may include ordered attachment parts. Semantic author and submitting actor are separate; delegated human authorship requires an attestation reference.

Intent interpretation happens after input acceptance. `InferredIntent` exposes inference provenance and uncertainty; a human edit creates a new `UserEditedIntent` version and supersedes the earlier interpretation without changing its source input. Independent outcomes may use sibling intent families linked to the same source input. Elwin owns these contracts; a Funes adapter owns canonical durable persistence, and Burdgeon owns the Send and inspection UI.

`Twinkle` is a durable pre-commitment possibility, presented by Burdgeon as an Idea. Elwin owns its current lifecycle and its references to Quain-owned concepts; promotion retains a reference to Titan-owned work rather than changing the Twinkle into a plan.

Run `composer install && composer test` to verify the contracts.

## License

Copyright © 2026 Sifrious. All rights reserved. This is publicly viewable
proprietary software, not open-source software. See [LICENSE.md](LICENSE.md).
