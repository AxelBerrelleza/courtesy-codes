### Desarrollar una API para control de codigos de cortesia.
- [x] La API debe autenticar mediante api-key con un header "X-API-KEY"

### Consideraciones:
  - Se debe usear acceso basado en roles que puede variar para cada endpoint
  - El rol de usuario basico no realiza acciones en este flujo, pero puede ser asignado 
  al canjear un codigo.  

| Endpoint                            | Acción         | Admin | Promoter | User | Nota                                              |
| ----------------------------------- | -------------- | ----- | -------- | ---- | ------------------------------------------------- |
| POST /events/{id}/courtesy-codes    | Crear códigos  | ✅     | ❌        | ❌    | El Admin centraliza la creación.                  |
| GET /events/{id}/courtesy-codes     | Listar códigos | ✅     | ⚠️        | ❌    | El Promotor solo ve los de **su evento**.         |
| GET /courtesy-codes/{code}/validate | Validar código | ✅     | ⚠️        | ❌    | El Promotor necesita validar antes de canjear.    |
| POST /courtesy-codes/{code}/redeem  | Canjear código | ❌     | ⚠️        | ❌    | El Promotor solo canjea los de **su evento**.     |
| DELETE /courtesy-codes/{code}       | Invalidar      | ✅     | ⚠️        | ❌    | El Promotor podría anular si un invitado cancela. |

### Endpoints a generar:
  - [x] crear codigos POST /events/{event_id}/courtesy-codes
  - [x] listar codigos GET /events/{event_id}/courtesy-codes
  - [x] validar codigo GET /courtesy-codes/{code}/validate
  - [x] canjear codigo POST /courtesy-codes/{code}/redeem
  - [x] invalidar codigo DELETE /courtesy-codes/{code}

### Desiciones tecnias a resaltar
Para está API opte por aplicar pessimistic locking, el cual hace un bloqueo de fila en la base de datos
liberando el registro hasta que la transaccion se complete o reciba un rollback
