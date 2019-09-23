FROM abiosoft/caddy:1.0.3

RUN mkdir -p /srv/caddy/html

WORKDIR /srv/caddy

ARG MKCERT_VERSION=1.4.0
RUN wget -O /usr/local/bin/mkcert https://github.com/FiloSottile/mkcert/releases/download/v${MKCERT_VERSION}/mkcert-v${MKCERT_VERSION}-linux-amd64
RUN chmod +x /usr/local/bin/mkcert

EXPOSE 80/tcp
EXPOSE 443/tcp

COPY docker-entrypoint.sh /usr/local/bin/
ENTRYPOINT ["docker-entrypoint.sh"]

CMD ["/bin/parent", "caddy", "--agree=$ACME_AGREE"]
