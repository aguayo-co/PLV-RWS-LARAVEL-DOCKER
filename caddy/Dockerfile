FROM golang:alpine as caddy-build

RUN apk add --no-cache git
RUN go get -u github.com/mholt/caddy
RUN go get -u github.com/caddyserver/builds
RUN go get -u github.com/caddyserver/dnsproviders/cloudflare

WORKDIR $GOPATH/src/github.com/mholt/caddy/caddy

RUN sed -i 's|// This is where other plugins get plugged in (imported)|_ "github.com/caddyserver/dnsproviders/cloudflare"|' caddymain/run.go

RUN go run build.go -goos=linux -goarch=amd64


FROM alpine:3.7

RUN apk --no-cache add curl

RUN mkdir -p /srv/caddy/html \
    /srv/caddy/.caddy

WORKDIR /srv/caddy

ENV CADDYPATH=/srv/caddy/.caddy

COPY Caddyfile* /srv/caddy/
COPY --from=caddy-build /go/src/github.com/mholt/caddy/caddy/caddy /usr/local/bin/

EXPOSE 80/tcp
EXPOSE 443/tcp

CMD ["caddy", "-agree"]
