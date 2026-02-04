### Desarrollar una API para control de códigos de cortesía.
- [x] La API debe autenticar mediante api-key con un header "X-API-KEY"

### Consideraciones:
  - Una diferencia con los requerimientos es que en esta API se canjean un codigo que
  que puede tener varios boletos
  - Se debe usar acceso basado en roles que puede variar para cada endpoint
  - El rol de usuario básico no realiza acciones en este flujo, pero puede ser asignado 
  al canjear un código.
  - El listado de códigos debe mostrar cuando y por quien fueron usados, si es el caso

| Endpoint                            | Acción         | Admin | Promoter | User | Nota                                              |
| ----------------------------------- | -------------- | ----- | -------- | ---- | ------------------------------------------------- |
| POST /events/{id}/courtesy-codes    | Crear códigos  | ✅     | ❌        | ❌    | El Admin centraliza la creación.                  |
| GET /events/{id}/courtesy-codes     | Listar códigos | ✅     | ⚠️        | ❌    | El Promotor solo ve los **que el canjeo**.        |
| GET /courtesy-codes/{code}/validate | Validar código | ✅     | ⚠️        | ❌    | El Promotor necesita validar antes de canjear.    |
| POST /courtesy-codes/{code}/redeem  | Canjear código | ❌     | ⚠️        | ❌    | El Promotor solo canjea los de **su evento**.     |
| DELETE /courtesy-codes/{code}       | Invalidar      | ✅     | ⚠️        | ❌    | El Promotor podría anular si un invitado cancela. |

### Endpoints a generar:
  - [x] crear códigos POST /events/{event_id}/courtesy-codes
  - [x] listar códigos GET /events/{event_id}/courtesy-codes
  - [x] validar código GET /courtesy-codes/{code}/validate
  - [x] canjear código POST /courtesy-codes/{code}/redeem
  - [x] invalidar código DELETE /courtesy-codes/{code}
  - EXTRAS, para una fácil revision:
  - [x] listar usuarios (listado simple id, email) GET /users
  - [ ] listar eventos GET /events

### Decisiones técnicas a resaltar
Para está API opte por aplicar pessimistic locking, el cual hace un bloqueo de fila en la base de datos
liberando el registro hasta que la transacción se complete o reciba un rollback

### Posibles race conditions
- Al canjear el código si dos usuarios tratan de canjear el mismo simultáneamente
- Al invalidar un código si un usuario trata de canjear mientras otro trata de invalidarlo simultáneamente
