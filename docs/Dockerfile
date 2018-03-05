FROM node:9.7-alpine

WORKDIR /srv/docs

RUN npm install -g raml2html nodemon

VOLUME /srv/docs

CMD nodemon -e raml --exec "raml2html -i api.raml -o api.html"
