# M7 intent: governed extension SDK and policy manifests

## Outcome

A WordPress developer can ship an independently versioned, locally installed Kiln extension that declares a small set of read-only abilities through a typed manifest. Kiln validates and registers only admitted declarations; it does not expose a generic route into WordPress, PHP, REST, SQL, files, or the network.

## Version-one boundary

M7 supports only administrator-gated read-only extension abilities. Every manifest has the logical identity kiln-ext/{extension-id}/{ability-id}; its registered WordPress ability name is kiln-ext/{extension-id}--{ability-id}, because the Abilities API permits exactly one namespace slash. IDs cannot contain a double hyphen, making the encoding collision-free. Every admitted ability has a closed input object schema, a closed output object schema, and one local execute callback supplied by the installed extension.

The core policy for every M7 extension ability is fixed to manage_options. Extensions cannot supply their own permission callback, bypass Kiln's policy decision, select a more permissive capability, or select a public or REST exposure policy. A future write-capable SDK requires a separately reviewed approval and effect-policy design.

## Trust and isolation

Extensions are ordinary local WordPress code and therefore are trusted at installation time in the same sense as other installed plugins. M7 limits the MCP-facing boundary: an extension must be named, schema-declared, versioned, bounded, and explicitly registered. Manifest failure, duplicate names, duplicate IDs, unsupported versions, or an extension exception must admit no part of that extension and must not prevent Kiln core abilities or other valid extensions from loading.

## Manifest contract

A manifest declares an extension ID, semantic version, and no more than ten ability descriptors. Each descriptor declares a normalized ability ID, a human-readable label/description, closed JSON object schemas, and a local executor. Identity and ability IDs are normalized before registration and collisions are rejected.

## Exit decision

M7 exits when unit and real-WordPress evidence proves valid registration, strict manifest refusal, collision isolation, administrator-only execution, subscriber denial, exact schema registration, bounded redacted audit receipts, and preservation of M1–M6 behavior.