#!/bin/bash

a = 0
for ((i = 0; i < 200; i++))
do
	php reindex.php vanillaforumsorg 200
done
