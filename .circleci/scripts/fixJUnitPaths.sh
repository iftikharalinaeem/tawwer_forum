#!/usr/bin/env bash

# Trim off the starts of the path to make them relative to our app.
sed -i 's/workspace\/repo\/cloud/workspace\/repo/g' $1
sed -i 's/\/home\/circleci\/workspace\/repo\///g' $1
cat $1