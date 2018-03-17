Una venta (`Sale`) es el punto de entrada de las ventas hechas en el portal por un vendedor. Una venta tiene
los productos que un usuario le ha comprado a un vendedor en una sola orden.

Una venta puede tener los siguientes estados:

- **10**: Carro de compras
- **20**: En pago
- **30**: Pagada
- **40**: Enviada
- **41**: Entregada
- **49**: Recibida
- **90**: Completada
- **91**: Completada con devolución
- **92**: Completada con devolución parcial
- **99**: Cancelada

Las rutas `/sales` son para las modificaciones que hace el vendedor sobre las ventas. Las modificaciones
se pueden hacer una vez la venta ha sido pagada.

El vendedor puede ingresar información sobre el despacho en la propiedad `shipment_details`, y puede marcar
la orden como despachada o entregada con la propiedad `status`.

La información de despacho debe ser un objeto. **NO tiene una estructura definida, y los datos enviados
no se validan**. Una propiedad especial es `tracking_codes`, en donde se debe enviar un objeto con los
códigos de rastreo de el envío, y la compañía asociada a cada código.

Adicionalmente un administrador puede marcar la orden como cancelada.

Ejemplos:

```
# Guarda información sobre el despacho.
{
    "shipment_details": {
        "note": "Una nota sobre el envío.",
        "tracking_codes" [
            {
                "code": "TRACK000000000",
                "company": "Fedex",
            },
            {
                "code": "TRACK000000001",
                "company": "UPS",
            }
        ]
    }
}

# Marca la orden como enviada.
{
    "status": 40
}

# Marca la orden como entregada.
{
    "status": 41
}

# Marca la orden como cancelada.
{
    "status": 99
}

```
