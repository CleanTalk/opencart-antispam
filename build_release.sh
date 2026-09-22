#!/bin/bash

MODULE_NAME="antispambycleantalk"
ARCHIVE="${MODULE_NAME}.ocmod.zip"

BUILD_DIR="build"
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR/upload"

for file in $(git ls-files | grep "$MODULE_NAME"); do
    dest="$BUILD_DIR/upload/$file"
    mkdir -p "$(dirname "$dest")"
    cp "$file" "$dest"
done

[ -f "install.xml" ] && cp "install.xml" "$BUILD_DIR/"

cd "$BUILD_DIR"
zip -r "../$ARCHIVE" .
cd ..
rm -rf "$BUILD_DIR"

echo "Ready: $ARCHIVE"
