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

test:
	docker compose exec backend php artisan test

test-race:
	docker compose exec backend php artisan test --testsuite=Concurrency

install:
	docker compose exec backend composer install
	docker compose exec frontend npm install