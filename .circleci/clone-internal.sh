# #!/bin/bash
#
# Clone the vanilla/internal repo for running tests against it.
set -e

#TARGET_DIR="$HOME/workspace/internal"
#
## Clone the repo.
#git clone git@github.com:vanilla/internal.git $TARGET_DIR
#cd $TARGET_DIR
#
#repo_target=${CUSTOM_TARGET_BRANCH: -$CIRCLE_BRANCH}
#
## When our target branch is a release branch
## We want to use the same target branch
#if [[ $repo_target == "release/*" ]]
#then
#    git checkout $repo_target
#fi
#
#cd $HOME/workspace/vanilla/plugins
#ln -s $HOME/workspace/internal/plugins/* .
#
#cd $HOME/workspace/vanilla/applications
#ln -s $HOME/workspace/internal/applications/* .

mkdir -p ~/.ssh

echo 'github.com ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEAq2A7hRGmdnm9tUDbO9IDSwBK6TbQa+PXYPCPy6rbTrTtw7PHkccKrpp0yVhp5HdEIcKr6pLlVDBfOLX9QUsyCOV0wzfjIJNlGEYsdlLJizHhbn2mUjvSAHQqZETYP81eFzLQNnPHt4EVVUh7VfDESU84KezmD5QlWpXLmvU31/yMf+Se8xhHTvKSCZIFImWwoG6mbUoWf9nzpIoaSjB+weqqUUmpaaasXVal72J+UX2B+2RPW3RcT0eOzQgqlJL3RKrTJvdsjE3JEAvGq3lGHSZXy28G3skua2SmVi/w4yCE6gbODqnTWlg7+wC604ydGXA8VJiS5ap43JXiUFFAaQ==' >> ~/.ssh/known_hosts

(umask 077; touch ~/.ssh/id_rsa)
chmod 0600 ~/.ssh/id_rsa
(cat <<EOF > ~/.ssh/id_rsa
$CHECKOUT_KEY
EOF
)

# use git+ssh instead of https
git config --global url."ssh://git@github.com".insteadOf "https://github.com" || true
git config --global gc.auto 0 || true

TARGET_DIR="$HOME/workspace/internal"

# Clone the repo.
if [ -e $TARGET_DIR/.git ]
then
    cd $TARGET_DIR
    git remote set-url origin git@github.com:vanilla/internal.git || true
else
    mkdir -p $TARGET_DIR
    cd $TARGET_DIR
    git clone "git@github.com:vanilla/internal.git" .
fi

cd $HOME/workspace/vanilla/plugins
ln -s $HOME/workspace/internal/plugins/* .

cd $HOME/workspace/vanilla/applications
ln -s $HOME/workspace/internal/applications/* .