[![wakatime](https://wakatime.com/badge/user/d7f8cf89-fee2-46da-89df-70b82216f2c2/project/bbf41023-2501-4c28-8db0-d3b1a10cf616.svg)](https://wakatime.com/badge/user/d7f8cf89-fee2-46da-89df-70b82216f2c2/project/bbf41023-2501-4c28-8db0-d3b1a10cf616)
# AutoLuxAz

# Create Laravel project
```shell
docker-compose run --rm composer create-project --prefer-dist laravel/laravel .
```
# Create install
```shell
docker-compose run --rm composer install
```

# Build Container

```shell
docker-compose up -d --build nginx 
# other build problem
# DOCKER_BUILDKIT=0 docker-compose up --build nginx

```

# Folder permission problem
```shell
docker compose exec -it -u root php chown -R www-data:www-data .
```

### Create storage link
```bash
docker compose run --rm artisan  storage:link
```

### Migrate our migration
```bash
docker compose run --rm artisan  migrate
```

# Clear cache
```shell
 docker compose run --rm artisan optimize:clear
 ```


# JWT renew secret key
```shell
 docker compose run --rm artisan jwt:secret
 ```



# Clear database and generate seed
```shell
 docker compose run --rm artisan migrate:fresh --seed
 ```


# Only seed 
```shell
 docker compose run --rm artisan db:seed
 ```


# Specification seed 
```shell
 docker compose run --rm artisan db:seed --class=UserSeeder
 docker compose run --rm artisan db:seed --class=UserSeeder
 ```


# Swagger
```shell
 docker compose run --rm  artisan  l5-swagger:generate --all
 ```


# Node project create
```shell
  docker compose run --rm npm create vite@latest . -- --template react-ts
  ```



