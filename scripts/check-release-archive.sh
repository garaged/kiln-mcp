#!/bin/sh
set -eu

archive=${1:?Usage: check-release-archive.sh path/to/kiln-mcp.zip}
checksum="$archive.sha256

if [ ! -f "$archive" ] || [ ! -f "$checksum" ]; then
	echo "Release archive and SHA-256 sidecar are required." >&2
	exit 1
fi

if command -v sha256sum >/dev/null 2>&1; then
	sha256sum -c "$checksum"
else
	expected=$(awk '{print $1}' "$checksum")
	actual=$(shasum -a 256 "$archive" | awk '{print $1}')
	test "$expected" = "$actual"
fi

entries=$(unzip -Z1 "$archive")
for required in kiln-mcp/kiln-mcp.php kiln-mcp/LICENSE kiln-mcp/readme.txt kiln-mcp/vendor/autoload.php; do
	if ! printf '%s\n' "$entries" | grep -Fx "$required" >/dev/null; then
		echo "Release archive is missing $required." >&2
		exit 1
	fi
done

if ! printf '%s\n' "$entries" | grep -Eq '^kiln-mcp/src/.+\.php$'; then
	echo "Release archive has no production source." >&2
	exit 1
fi

if printf '%s\n' "$entries" | grep -Eq '(^|/)(tests|testing|docs|scripts|\.github|\.git|vendor/bin)(/|$)|(^|/)(composer\.json|composer\.lock|phpunit\.xml|phpstan\.neon\.dist)$'; then
	echo "Release archive contains development-only material." >&2
	exit 1
fi

echo "Release archive checks passed."