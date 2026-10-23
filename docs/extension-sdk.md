# M7 extension SDK

M7 lets a locally installed WordPress plugin declare a small, read-only Kiln extension. It has no relay, remote registry, telemetry, network capability, filesystem capability, SQL interface, generic WordPress interface, or write effect.

## Registration

Register one provider during normal WordPress plugin load. The callback receives a private staging registry. Kiln invokes providers independently at ability-registration time, so a thrown exception or rejected batch cannot expose partial abilities or prevent a later valid provider from loading.

    <?php
    use KilnMcp\Extension\ExtensionAbility;
    use KilnMcp\Extension\ExtensionManifest;
    use KilnMcp\Extension\ExtensionRegistry;

    add_action(
        'kiln_mcp_register_extensions',
        static function ( ExtensionRegistry $registry ): void {
            $registry->register(
                new ExtensionManifest(
                    'example',
                    '1.0.0',
                    array(
                        new ExtensionAbility(
                            'site-facts',
                            'Site facts',
                            'Returns bounded local facts.',
                            array(
                                'type'                 => 'object',
                                'additionalProperties' => false,
                                'properties'           => array(),
                                'required'             => array(),
                            ),
                            array(
                                'type'                 => 'object',
                                'additionalProperties' => false,
                                'properties'           => array(
                                    'status' => array( 'type' => 'string' ),
                                ),
                                'required'             => array( 'status' ),
                            ),
                            static fn( array $input ): array => array( 'status' => 'available' )
                        ),
                    )
                )
            );
        }
    );

## Admission contract

- Extension and ability IDs are lowercase, normalized identifiers; an extension has 3–32 characters.
- Versions use semantic-version syntax.
- Each manifest declares at most ten abilities.
- Input and output schemas are closed JSON object schemas: `type: object`, `additionalProperties: false`, plus `properties` and `required`.
- Kiln derives every public name as `kiln-ext/{extension-id}/{ability-id}`. Duplicate extension IDs and public names are refused.
- A provider's manifests commit as one batch only after the provider returns successfully.

## Non-negotiable policy

Kiln owns all MCP-facing policy. Every admitted ability is administrator-gated with `manage_options`, marked read-only and idempotent, and hidden from REST. An extension cannot set a permission callback, transport, public flag, effect type, or category.

Executors receive the schema-validated input array and must return an array matching their declared output schema. Exceptions and non-array results become a fixed failure without exposing implementation details.

Installed PHP remains an administrator trust decision. M7 constrains the MCP surface; it is not a sandbox for malicious WordPress code.

## Audit and test expectations

Each allowed or denied execution appends a bounded local receipt under the generic ability class `kiln/extension`. The receipt never retains extension input, output, extension ID, callback, filesystem path, or exception detail.

Run the complete real-WordPress conformance suite with:

    make container-test

It activates a disposable valid fixture and a fixture that registers then throws. The test proves valid registration, atomic refusal of the throwing fixture, administrator execution, subscriber denial, generic receipt redaction, and M1–M6 regression coverage.