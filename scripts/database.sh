#!/bin/bash
#let's configure environment
run-parts /etc/my_init.d

cd /app
/sbin/setuser app php app/console doctrine:migrations:migrate --no-interaction
