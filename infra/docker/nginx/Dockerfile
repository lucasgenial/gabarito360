FROM node:24-alpine AS assets

WORKDIR /build

ARG VITE_REVERB_APP_KEY=gabarito360-key
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT=80
ARG VITE_REVERB_SCHEME=http

ENV VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY}
ENV VITE_REVERB_HOST=${VITE_REVERB_HOST}
ENV VITE_REVERB_PORT=${VITE_REVERB_PORT}
ENV VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME}

COPY backend/package.json backend/package-lock.json ./
RUN npm ci

COPY backend/ ./
RUN npm run build

FROM nginx:1.27-alpine

COPY infra/docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY backend/public/ /var/www/html/public/
COPY --from=assets /build/public/build /var/www/html/public/build

EXPOSE 80
