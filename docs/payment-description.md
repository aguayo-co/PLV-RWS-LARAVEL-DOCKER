Un pago puede tener los siguientes estados:

- **00**: Esperando confirmación
- **10**: Pagada
- **99**: Cancelada

El pago tiene la siguiente información:

- uniqid: Identificador único aleatorio.
- request: Objeto con información sobre la solicitud de pago.
- attempts: Arreglo con información sobre los posibles intentos de pago hechos. Esta información proviene de
la pasarela de pagos. Se almacenan cada una de las notificaciones hechas por la pasarela.

Para enviar un usuario al proceso de pago, se debe solicitar un nuevo pago especificando que pasarela
usar por parámetros de URL (`?gateway=pasarela_seleccionada`). Dependiendo de la pasarela, podría ser necesario enviar información adicional con la solicitud.

La respuesta incluirá, en la propiedad `request_data`, la información mínima necesaria para enviar al usuario a la pasarela de pagos.

Una vez un pago es generado, la orden pasa deja de estar en carro de compras, los productos se marcan
como no disponibles, y no se puede generar un nuevo pago para la misma.

Las pasarelas disponibles y la información adicional necesaria para cada una se indican a continuación:

##### PayU

- **Nombre**: pay_u (`?gateway=pay_u`)
- **Parámetros adicionales**: Ninguno
- **request_data**:
    - test
    - accountId
    - merchantId
    - referenceCode
    - amount
    - currency
    - signature
    - description
    - confirmationUrl
    - buyerFullName
    - buyerEmail
    - gatewayUrl: URL a la que se debe enviar la petición POST.

Aparte de `gatewayUrl`, la información sobre las variables se encuentra en la
[documentación](http://developers.payulatam.com/es/web_checkout/variables.html)
de PayU.
