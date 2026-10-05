#!/bin/bash
# Rakentaa asennettavan zip-paketin: bash build.sh  →  ../dist/nordic-chat-<versio>.zip
set -euo pipefail
cd "$(dirname "$0")"
VERSION=$(grep -m1 "Version:" nordic-chat.php | awk '{print $NF}')
TMP=$(mktemp -d)
mkdir -p "$TMP/nordic-chat"
cp -r nordic-chat.php composer.json composer.lock includes assets README.md TIETOSUOJA-CHAT.md "$TMP/nordic-chat/"
( cd "$TMP/nordic-chat" && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --quiet )
# Kirjastoista pois kehitystiedostot (testit, esimerkit, git-historia).
find "$TMP/nordic-chat/vendor" -mindepth 3 -maxdepth 3 \( -name tests -o -name test -o -name .git -o -name .github -o -name examples -o -name docs -o -name fixtures -o -name scripts \) -prune -exec rm -rf {} +
find "$TMP/nordic-chat/vendor" -maxdepth 3 -type f \( -name "*.md" ! -name "LICENSE*" -o -name "phpunit*" -o -name "phpstan*" -o -name ".php-cs-fixer*" \) -delete
mkdir -p ../dist
rm -f "../dist/nordic-chat-$VERSION.zip"
( cd "$TMP" && zip -qr "$OLDPWD/../dist/nordic-chat-$VERSION.zip" nordic-chat )
rm -rf "$TMP"
ls -la "../dist/nordic-chat-$VERSION.zip"
