FROM node:10-alpine

WORKDIR /srv/docs

COPY package.* ./

RUN npm install

CMD npm run watch
