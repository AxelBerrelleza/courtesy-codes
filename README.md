### Para ejecutar
Después de clonar el repositorio asegurarse de estar en la raíz del proyecto
```bash
docker compose build --pull --no-cache
docker compose up --wait
docker compose exec php bin/console doctrine:fixtures:load
```
Después de la espera se podrá acceder a http//localhost/api/doc y empezar a consumir la api

### Para ejecutar los test:
```bash
docker compose exec php bin/console doctrine:database:create -e test
docker compose exec php bin/console doctrine:migrations:migrate -n -e test
docker compose exec php bin/console doctrine:fixtures:load -n -e test
docker compose exec php bin/phpunit --testdox
```
