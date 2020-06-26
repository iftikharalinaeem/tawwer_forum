#!/usr/bin/env bash

circleci tests glob $1 > tmpTestsToRun
# Trim off the cloud directories. They don't run directly.
sed -i '/^cloud/d' tmpTestsToRun

# APIv0 tests can't be parallelised and are run separately.
sed -i '/APIv0/d' tmpTestsToRun

# Vendor tests should always be left off.
sed -i '/vendor/d' tmpTestsToRun

testFiles=$(cat tmpTestsToRun | circleci tests split --split-by=timings)
testFileNames=$(echo $testFiles)
filter=$(php ./.circleci/scripts/makePHPUnitFilter.php "$testFileNames")
echo $filter;