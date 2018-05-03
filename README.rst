Prilov - Laravel backend
------------------------

Backend basado en Laravel para Prilov.

Desarrollo
##########

Se puede ejecutar una versón rápida de la aplicación usando Docker. Para esto es
necesario tener:

- `composer <https://getcomposer.org/>`_
- `Docker <https://docs.docker.com/>`_
- `Docker Compose <https://docs.docker.com/compose/>`_
- Un certificado ssl para el dominio a usar

Generar archivos .env y completar:

.. code-block:: bash

    $ cp caddy/.env.example caddy/.env
    $ cp laravel/.env.example laravel/.env
    $ cp php-fpm/.env.example php-fpm/.env
    $ cp mysql/.env.example mysql/.env

Instalar y configurar Laravel localmente.

.. code-block:: bash

    $ cd laravel
    $ cp .env.example .env
    $ composer install
    # php artisan migrate --step
    $ php artisan key:generate
    $ php artisan passport:keys
    $ php artisan passport:client --personal -n
    $ cd ..

Ejecutar docker

.. code-block:: bash

    $ docker-compose up --build -d

Cargar datos de prueba

.. code-block:: bash

    $ php artisan migration:refresh --step --seed

Unit testing

.. code-block:: bash

    :laravel$ phpunit
