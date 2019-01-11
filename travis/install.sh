#!/usr/bin/env bash
## Clone vanilla/vanilla so we can test against it

cd $TRAVIS_BUILD_DIR

# This directory might already exist (from the cache) but its not a big deal.
mkdir "$TRAVIS_BUILD_DIR/vanilla"
cd "$TRAVIS_BUILD_DIR/vanilla"

# Because cached files are already possibly here we can't do a clone.
printf "\nCloning the main vanilla repository..."
git init
git remote add origin https://github.com/vanilla/vanilla
git pull --depth 50 origin fix/mysql-throw

# Symlink in the knowledge plugin
printf "\nSymlinking plugins from the knowledge repo..."
cd "$TRAVIS_BUILD_DIR/vanilla/plugins"
ln -s ../../plugins/* ./

cd "$TRAVIS_BUILD_DIR"
