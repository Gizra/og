FROM drupal:7-apache
ENV DEBIAN_FRONTEND noninteractive
ENV PULL_REQUEST_BRANCH=$BRANCH

# Install MySQL and start.
RUN apt-get update
RUN apt-get install -y \
	mysql-server \
  mysql-client \
	git
RUN apt-get clean

# Install composer.
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

COPY ./start.sh /var/www/html/start.sh
RUN chmod +x /var/www/html/start.sh
#ENTRYPOINT ["/var/www/html/start.sh"]

EXPOSE 80 3306 22
