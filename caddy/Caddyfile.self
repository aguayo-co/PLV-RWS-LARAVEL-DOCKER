{$DOMAIN} {
  tls self_signed
  root /srv/caddy/html/public
  log stdout
  errors stderr

  fastcgi / php-fpm:9000 php
  rewrite {
    to {path} {path}/ /index.php?{query}
  }
}
