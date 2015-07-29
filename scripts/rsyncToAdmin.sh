#!/bin/bash

# Script to sync client to admin, and update the YML file with admin-specific params (env and redis_dsn)
#

# paths (keep traling slash)
CLIENT_PATH=~/www/opg-digi-deps-client/
ADMIN_PATH=~/www/opg-digi-deps-admin/


# rsync, delete cache, replace YML params
rsync -a --delete $CLIENT_PATH $ADMIN_PATH
rm -rf $ADMIN_PATH"app/cache/*"
sed -i -e 's/env: prod/env: admin/g' $ADMIN_PATH"app/config/parameters.yml"
sed -i -e 's/redis:\/\/redisfront/redis:\/\/redisadmin/g' $ADMIN_PATH"app/config/parameters.yml"