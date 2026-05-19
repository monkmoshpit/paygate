FROM php:8.2-apache

# Instalar extensões necessárias para o Redis e o Laravel funcionarem
RUN apt-get update && apt-get install -y \
    zip \
    unzip \
    git \
    && pecl install redis \
    && docker-php-ext-enable redis

# Habilitar o mod_rewrite do Apache para as rotas da API funcionarem
RUN a2enmod rewrite

# Apontar a raiz do servidor para a pasta public do Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# Copiar os arquivos do projeto
COPY . .

# Instalar o Composer e as dependências
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Dar permissões para as pastas de cache do Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD ["apache2-foreground"]