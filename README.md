- To run the test:
```bash
docker compose exec php bin/console doctrine:database:create -e test
docker compose exec php bin/console doctrine:migrations:migrate -n -e test
docker compose exec php bin/console doctrine:fixtures:load -n -e test
docker compose exec php bin/phpunit
```
