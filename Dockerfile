FROM ubuntu:22.04

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update
RUN apt-get install -y curl wget cron nginx php8.1-fpm php8.1-curl git composer

RUN printf '$( curl "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=https://themodernmilkman.co.uk&strategy=desktop" > "/var/www/pagespeed-exporter/results/c196c38b86fd2c5efa880e984ba87f71.desktop.json.temp" ; mv "/var/www/pagespeed-exporter/results/c196c38b86fd2c5efa880e984ba87f71.desktop.json.temp" "/var/www/pagespeed-exporter/results/c196c38b86fd2c5efa880e984ba87f71.desktop.json" ) &\n\
$( curl "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=https://themodernmilkman.co.uk&strategy=mobile" > "/var/www/pagespeed-exporter/results/c196c38b86fd2c5efa880e984ba87f71.mobile.json.temp" ; mv "/var/www/pagespeed-exporter/results/c196c38b86fd2c5efa880e984ba87f71.mobile.json.temp" "/var/www/pagespeed-exporter/results/c196c38b86fd2c5efa880e984ba87f71.mobile.json" ) &\n\
$( curl "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=https://themodernmilkman.co.uk/categories&strategy=desktop" > "/var/www/pagespeed-exporter/results/c00c872de54f68fcbd4df41c36e07804.desktop.json.temp" ; mv "/var/www/pagespeed-exporter/results/c00c872de54f68fcbd4df41c36e07804.desktop.json.temp" "/var/www/pagespeed-exporter/results/c00c872de54f68fcbd4df41c36e07804.desktop.json" ) &\n\
$( curl "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=https://themodernmilkman.co.uk/categories&strategy=mobile" > "/var/www/pagespeed-exporter/results/c00c872de54f68fcbd4df41c36e07804.mobile.json.temp" ; mv "/var/www/pagespeed-exporter/results/c00c872de54f68fcbd4df41c36e07804.mobile.json.temp" "/var/www/pagespeed-exporter/results/c00c872de54f68fcbd4df41c36e07804.mobile.json" ) &\n\
' > /root/pagespeed-cron.sh
RUN chmod 0644 /root/pagespeed-cron.sh
RUN crontab -l | { cat; echo "* * * * * bash /root/pagespeed-cron.sh"; } | crontab -

RUN git clone https://github.com/cvsouth/pagespeed-exporter.git /var/www/pagespeed-exporter
RUN $(cd /var/www/pagespeed-exporter && composer install --no-interaction)
RUN chown root:www-data -R /var/www/pagespeed-exporter
RUN printf 'server {\n\
  listen 9800;\n\
  listen [::]:9800;\n\
  root /var/www/pagespeed-exporter/public;\n\
  index index.php;\n\
  server_name _;\n\
  location / {\n\
    try_files $uri $uri/ /index.php;\n\
  }\n\
  location ~ \.php$ {\n\
    include snippets/fastcgi-php.conf;\n\
    fastcgi_pass unix:/run/php/php8.1-fpm.sock;\n\
  }\n\
}\n\
' > /etc/nginx/sites-enabled/pagespeed
RUN rm /etc/nginx/sites-enabled/default

CMD cron && \
    /etc/init.d/php8.1-fpm start -F && \
    nginx -g "daemon off;"
