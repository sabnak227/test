# Environment Setup
## Create db inside mariadb container
```
# Login to the db server
docker exec -it "$(docker ps | grep laradock_mariadb | awk '{print $1}')" /bin/bash

# Create database, note root password is root 
mysql -uroot -p -e "CREATE DATABASE laravel"

# Give default user access to this db
mysql -uroot -p -e "GRANT ALL PRIVILEGES ON laravel.* TO 'default'@'%' IDENTIFIED BY 'secret';"

```

## Setup cron
```
# Insert the following to workspace container's cron
* * * * * cd /var/www && php artisan schedule:run >> /dev/null 2>&1

```

## Test
```
# Login to the db server
docker exec -it "$(docker ps | grep laradock_mariadb | awk '{print $1}')" /bin/bash

# Create database, note root password is root 
mysql -uroot -p -e "CREATE DATABASE laraveltest"

# Give default user access to this db
mysql -uroot -p -e "GRANT ALL PRIVILEGES ON laraveltest.* TO 'default'@'%' IDENTIFIED BY 'secret';"

# Migrate db
php artisan --env=testing migrate:fresh --seed

# Run test
phpunit
```