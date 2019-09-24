# #!/bin/bash
#
# Clone the vanilla/internal repo for running tests against it.
set -e

# some git config
git config --global gc.auto 0 || true

TARGET_DIR="$HOME/workspace/internal"

## Clone the internal repo with the OAuth token according to github's doc.
## https://github.blog/2012-09-21-easier-builds-and-deployments-using-git-over-https-and-oauth/

# We do a pull instead of a clone
# to prevent our token from ever being written in cleartext to disk.
mkdir $TARGET_DIR
cd $TARGET_DIR
git init
git pull "https://$GITHUB_TOKEN@github.com/vanilla/internal.git"

cd $HOME/workspace/vanilla/plugins
ln -s $HOME/workspace/internal/plugins/* .