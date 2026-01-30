1. clone dunglas/symfony-docker repo
2. modify compose.override.yaml to set env SERVER_NAME: http://localhost
3. docker compose build --pull --no-cache
4. docker compose up --wait
5. docker compose exec php composer require --dev symfony/maker-bundle
6. docker compose exec php composer require symfony/orm-pack
7. docker compose exec php composer require symfony/serializer-pack
8. docker compose stop
9. docker compose up --build --wait
10. docker compose exec php bin/console doctrine:database:create

- Extra dev packs
	- symfony/test-pack
	- zenstruck-foundry
	- orm-fixtures
	- dama/doctrine-test-bundle
