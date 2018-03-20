##### Ordenamiento

Aparte de los criterios globales, los productos se pueden ordenar por los siguientes criterios:

|valor|parametro de url|ejemplo|
|-----|----------------|-------|
|precio|price|`?orderby=-price`|
|comisión|prilov|`?orderby=prilov`|

##### Filtrado

Aparte de los criterios de filtrado globales, los productos se pueden filtrar por los siguientes criterios:

|filtro|tipo|
|------|----|
|price|Entre|
|color_ids|ContenidoEn|
|campaign_ids|ContenidoEn|
|brand_id|ContenidoEn|
|category_id|ContenidoEn|
|condition_id|ContenidoEn|
|status|ContenidoEn|

##### Agregar imágenes

Las imágenes que se envíen en el campo `images`, serán aregadas al listado de imágenes que ya tenga
el producto. Para eliminar imágenes se debe enviar la información en un campo `images_remove`.

##### Eliminación de imágenes

Para eliminar imágenes de los productos se debe pasar un arreglo con los nombres de las imagenes en le campo
**images_remove**. Cada nombre debe inluir la extension, si el archivo tiene una, pero no la ruta al mismo.

###### Ejemplo:

Para eliminar estas dos imágenes:

- `https://prilov.aguayo.co/storage/products/images/1/qwe1234567890`
- `https://prilov.aguayo.co/storage/products/images/1/asd0987654321.gif`

Se debe pasar:

````
{
    "images_remove": [
    "qwe1234567890",
    "asd0987654321.gif"
    ]
}
```
