#!/usr/bin/env bash

WORKSPACE=${WORKSPACE:-$CIRCLE_BRANCH}
WORKSPACE=${WORKSPACE//[^[:alnum:]]/}
WORKSPACE=${WORKSPACE,,}
WORKSPACE=${WORKSPACE:0:14}
echo "export TF_WORKSPACE=${WORKSPACE}"

#VERSION=${VERSION:-$(cat ~/project/VERSION 2>/dev/null)}
echo "export TF_VAR_OPG_DOCKER_TAG=ddpb3663-8770285"
echo "export VERSION=${VERSION}"
