# Imagem PHP 8.2 com FPM (FastCGI Process Manager), usada pelo Nginx.
FROM php:8.2-fpm-alpine

# Extensões necessárias:
#  - pdo_mysql: driver de acesso ao MySQL usado pelo Yii2 (yii\db\Connection)
#  - bcmath:    aritmética de precisão arbitrária (útil ao lidar com dinheiro)
RUN docker-php-ext-install pdo pdo_mysql bcmath

# Composer (copiado da imagem oficial) para instalar dependências dentro do container
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# git/unzip ajudam o Composer a baixar/extrair pacotes
RUN apk add --no-cache git unzip

WORKDIR /var/www

# O código é montado via volume (docker-compose), então não copiamos nada aqui.
# Em produção você faria COPY do código + `composer install --no-dev`.
CMD ["php-fpm"]
