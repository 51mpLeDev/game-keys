.PHONY: help build up down restart logs ps shell migrate fresh seed test test-race install

help:
	@echo "Game Keys"
	@echo ""
	@echo "Docker:"
	@echo "  make build       Build containers"
	@echo "  make up          Start containers"
	@echo "  make down        Stop containers"
	@echo "  make restart     Restart containers"
	@echo "  make logs        Show container logs"
	@echo "  make ps          Show container status"
	@echo ""
	@echo "Laravel:"
	@echo "  make shell       Open Laravel container shell"
	@echo "  make migrate     Run migrations"
	@echo "  make queue       Run queue"
	@echo "  make fresh       Fresh migrations + seed"
	@echo "  make seed        Run database seeders"
	@echo "  make test        Run tests"
	@echo "  make test-race   Run concurrency tests"

build:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose down
	docker compose up -d

logs:
	docker compose logs -f

ps:
	docker compose ps

shell:
	docker compose exec backend bash

migrate:
	docker compose exec backend php artisan migrate

fresh:
	docker compose exec backend php artisan migrate:fresh --seed

seed:
	docker compose exec backend php artisan db:seed

queue:
	docker compose exec backend php artisan queue:work

test:
	docker compose exec -e DB_DATABASE=game_keys_test -e QUEUE_CONNECTION=sync backend php artisan migrate:fresh --force
	docker compose exec -e DB_DATABASE=game_keys_test -e QUEUE_CONNECTION=sync backend php artisan test \
		tests/Unit \
		tests/Feature/ExampleTest.php \
		tests/Feature/MockProviderTest.php \
		tests/Feature/OrderDeliveryRecoveryTest.php \
		tests/Feature/PaymentWebhookTest.php \
		tests/Feature/OrderCreationIdempotencyTest.php

test-race:
	docker compose -f docker-compose.yml -f docker-compose.test.yml up -d --force-recreate backend nginx
	docker compose exec backend php artisan migrate:fresh --force
	docker compose exec backend php artisan test \
		--filter='(OrderCreationConcurrencyTest|PaymentWebhookConcurrencyTest)'; \
	status=$$?; \
	docker compose up -d --force-recreate backend nginx; \
	exit $$status

install:
	docker compose exec backend composer install
	docker compose exec frontend npm install