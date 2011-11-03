#!/bin/bash

cd /srv/clients
find *.vanillaforums.com -maxdepth 0 -exec echo -n -e "{}: " ';' -exec curl -L http://{}/utility/resendemails.json ';' -exec echo '' ';'