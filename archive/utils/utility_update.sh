#!/bin/bash

cd /srv/clients
find *.vanillaforums.com -maxdepth 0 -exec echo -n -e "\033[35m{}:\033[30m " ';' -exec curl -L http://{}/utility/update.json ';' -exec echo '' ';'