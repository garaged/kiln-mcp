#!/bin/sh
set -eu

root_dir=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
release_dir=${KILN_RELEASE_DIR:-"$root_dir/dist"}
composer_bin=${COMPOSER_BIN:-composer}

case "$release_dir" in
	/*) ;;
	*) release_dir="$root_dir/$release_dir" ;;
esac

version=$(sed -n 's/^[[:space:]]*\* Version: \(.*\)$/\1/p' "$root_dir/kiln-mcp.php" | head -n 1)
if [ -z "$version" ]; then
	echo "Cannot determine Kiln MCP version." >&2
	exit 1
fi

if ! command -v "$composer_bin" >/dev/null 2>&1 || ! command -v zip >/dev/null 2>&1; then
	echo "Release assembly requires Composer and zip." >&2
	exit 1
fi

mkdir -p "$release_dir"
archive="$release_dir/kiln-mcp-$version.zip"
checksum="$archive.sha256"
if [ -e "$archive" ] || [ -e "$checksum" ]; then
	echo "Refusing to overwrite an existing release artifact: $archive" >&2
	exit 1
fi

work_dir=$(mktemp -d "${TMPDIR:-/tmp}/kiln-mcp-release.XXXXXX")
trap 'rm -rf "$work_dir"' EXIT HUP INT TERM
stage_dir="$work_dir/kiln-mcp"

mkdir -p "$stage_dir"
cp "$root_dir/kiln-mcp.php" "$root_dir/LICENSE" "$root_dir/readme.txt" "$stage_dir/"
cp -R "$root_dir/src" "$stage_dir/src"

COMPOSER_VENDOR_DIR="$stage_dir/vendor" "$composer_bin" install \
	--no-dev \
	--no-interaction \
	--prefer-dist \
	--no-progress \
	--optimize-autoloader \
	--working-dir="$root_dir"

if [ ! -f "$stage_dir/vendor/autoload.php" ]; then
	echo "Production Composer autoloader was not generated." >&2
	exit 1
fi

(
	cd "$work_dir"
	zip -X -q -r "$archive" kiln-mcp
)

if command -v sha256sum >/dev/null 2>&1; then
	sha256sum "$archive" > "$checksum"
else
	shasum -a 256 "$archive" > "$checksum"
fi

echo "Release archive: $archive"
echo "SHA-256: $checksum"