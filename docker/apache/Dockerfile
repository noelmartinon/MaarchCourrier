FROM debian:jessie

RUN apt-get update -y
RUN apt-get install -y apache2
RUN a2enmod cgi setenvif proxy proxy_fcgi proxy_http rewrite
RUN service apache2 restart

ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2
ENV APACHE_LOCK_DIR /var/lock/apache2
ENV APACHE_PID_FILE /var/run/apache2.pid

EXPOSE 80

CMD /usr/sbin/apache2ctl -D FOREGROUND