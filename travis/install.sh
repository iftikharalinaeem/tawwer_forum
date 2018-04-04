#!/usr/bin/env bash

cd $TRAVIS_BUILD_DIR

git clone --depth=50 --branch=master https://github.com/vanilla/vanilla vanilla

cd "$TRAVIS_BUILD_DIR/vanilla/applications"
ln -s ../../applications/* ./
cd "$TRAVIS_BUILD_DIR/vanilla/plugins"
ln -s ../../plugins/* ./

cd "$TRAVIS_BUILD_DIR/vanilla"
ls -lah ./applications
ls -lah ./plugins

composer self-update
composer install --optimize-autoloader
composer require phpunit/phpunit ~5
