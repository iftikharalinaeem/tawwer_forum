# #!/bin/bash
#
# Clone the vanilla/internal repo for running tests against it.
set -e

TARGET_DIR="$HOME/workspace/internal"

# Clone the repo.
git clone git@github.com:vanilla/internal.git $TARGET_DIR
cd $TARGET_DIR

repo_target=${CUSTOM_TARGET_BRANCH: -$CIRCLE_BRANCH}

# When our target branch is a release branch
# We want to use the same target branch
if [[ $repo_target == "release/*" ]]
then
    git checkout $repo_target
fi

cd $HOME/workspace/vanilla/plugins
ln -s $HOME/workspace/internal/plugins/* .

cd $HOME/workspace/vanilla/applications
ln -s $HOME/workspace/internal/applications/* .