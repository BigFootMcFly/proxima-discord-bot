#!/bin/bash

# ---------------------------------------------------------------------------
# setting constants
PACKAGE_REPOSITORY=proxima.goliath.hu/proxima/discord-bot
BUILD_TAG=default
UID=$(id -u)
GID=$(id -g)

# ---------------------------------------------------------------------------

clear

echo This is a sample build script, if the workflow cannot be used.
echo Please customize the repository address/name for your own.
echo

# ---------------------------------------------------------------------------
echo Building "${BUILD_TAG}" package ..
echo

# ---------------------------------------------------------------------------
echo Changing to project root directory...

pushd ../.. > /dev/null

# ---------------------------------------------------------------------------
echo Determining tag name...

branch=$(git branch --show-current)
tag=temp

[[ $branch == dev ]] && tag=testing
[[ $branch == main ]] && tag=latest
[[ $branch == master ]] && tag=latest

# ---------------------------------------------------------------------------
echo Building image...


docker buildx build . \
    --tag ${PACKAGE_REPOSITORY}:$tag \
    --build-arg GROUP_ID=${GID} \
    --build-arg USER_ID=${UID} \
    --file build/${BUILD_TAG}/Dockerfile \
    --platform linux/amd64,linux/arm64 \
    --provenance=false \
    --sbom=false \
#    --push \

# ---------------------------------------------------------------------------
echo Changing back to build directory...

popd > /dev/null

# ---------------------------------------------------------------------------
echo Done.
