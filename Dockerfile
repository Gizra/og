FROM nirgn975/docker-drupal

COPY ./start.sh /var/www/html/start.sh
RUN chmod +x /var/www/html/start.sh
#ENTRYPOINT ["/var/www/html/start.sh"]

EXPOSE 80 3306 22
