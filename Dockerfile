FROM registry.service.dsd.io/opguk/php-fpm:0.1.128

RUN  apt-get update && apt-get install -y \
     php-pear php5-curl php5-memcached php5-redis php5-pgsql \
     nodejs dos2unix postgresql-client ruby && \
     apt-get clean && apt-get autoremove && \
     rm -rf /var/lib/cache/* /var/lib/log/* /tmp/* /var/tmp/*

RUN  cd /tmp && curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

RUN  gem install sass

# build app dependencies
COPY composer.json /app/
COPY composer.lock /app/
WORKDIR /app
USER app
ENV  HOME /app
RUN  composer install --prefer-source --no-interaction --no-scripts

# install remaining parts of app
ADD  . /app
USER root
RUN find . -not -user app -exec chown app:app {} \;
USER app
ENV  HOME /app
RUN  composer run-script post-install-cmd --no-interaction
RUN  composer dump-autoload --optimize
RUN sass --load-path /app/vendor/alphagov/govuk_frontend_toolkit/stylesheets /app/src/AppBundle/Resources/assets/scss/formatted-report.scss /app/src/AppBundle/Resources/views/Css/formatted-report.html.twig

# cleanup
RUN  rm /app/app/config/parameters.yml
USER root
ENV  HOME /root

# app configuration
ADD docker/confd /etc/confd

# let's make sure they always work
RUN dos2unix /app/scripts/*

# copy init scripts
ADD  docker/my_init.d /etc/my_init.d
RUN  chmod a+x /etc/my_init.d/*

ENV  OPG_SERVICE api
ADD  docker/beaver.d /etc/beaver.d
