### Para ejecutar
Después de clonar el repositorio asegurarse de estar en la raíz del proyecto
```bash
docker compose build --pull --no-cache
docker compose up --wait
docker compose exec php bin/console doctrine:fixtures:load
```
Después de la espera se podrá acceder a http://localhost/api/doc y 
empezar a consumir la api, se puede consumir también desde la UI de
documentación, en la parte superior derecha se muestra un botón 
para autenticarse.

### Para ejecutar los test:
```bash
docker compose exec php bin/console doctrine:database:create -e test
docker compose exec php bin/console doctrine:migrations:migrate -n -e test
docker compose exec php bin/console doctrine:fixtures:load -n -e test
docker compose exec php bin/phpunit --testdox
```

### Las partes mas importantes del proyecto se encuentran en las siguientes carpetas
config/
migrations/
tests/
src/
├── Controller
├── DataFixtures
├── Dto
├── Entity
├── Enum
├── Repository
├── Security
└── Service

### Lecturas relevantes
- requerimientos.md: donde fui escribiendo el analisis sobre los requerimientos
- steps.md: donde redacte lo avanzado para esta plantilla
- En el proyecto deje comentarios que inician con **@important**
