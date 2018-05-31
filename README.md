# Prilov - Laravel backend

Backend basado en Laravel para Prilov.

## Desarrollo

Se puede ejecutar una versión rápida de la aplicación usando Docker. Para esto es
necesario tener:

- [Docker](https://docs.docker.com/)
- [Docker Compose](https://docs.docker.com/compose/)
- Un certificado SSL para el dominio a usar

Generar archivos .env y completar:

```bash
$ cp caddy/.env.example caddy/.env
$ cp php-fpm/.env.example php-fpm/.env
$ cp mysql/.env.example mysql/.env
```

Instalar Laravel (ver documentación en la carpeta de la aplicación)

Ejecutar docker

```bash
$ docker-compose up --build -d
```
