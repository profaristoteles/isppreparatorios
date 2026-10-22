FROM php:8.3-apache

# Instala extensões necessárias para o site e limpa cache do apt
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# Configura o php.ini oficial de produção e endurece diretivas de segurança
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i 's/expose_php = On/expose_php = Off/' "$PHP_INI_DIR/php.ini" \
    && sed -i 's/display_errors = On/display_errors = Off/' "$PHP_INI_DIR/php.ini" \
    && sed -i 's/display_startup_errors = On/display_startup_errors = Off/' "$PHP_INI_DIR/php.ini" \
    && sed -i 's/log_errors = Off/log_errors = On/' "$PHP_INI_DIR/php.ini"

# Hardening do Apache (oculta versão nos cabeçalhos HTTP e páginas de erro)
RUN echo "ServerTokens Prod" >> /etc/apache2/apache2.conf \
    && echo "ServerSignature Off" >> /etc/apache2/apache2.conf

# Bloqueia execução de scripts PHP e CGIs no diretório de uploads montado
RUN { \
    echo '<Directory "/var/www/html/uploads">'; \
    echo '    Options -ExecCGI -Indexes'; \
    echo '    <FilesMatch "(?i)\.(php|phtml|php3|php4|php5|php7|php8|phps|pl|py|cgi|sh|bash|exe)$">'; \
    echo '        Require all denied'; \
    echo '    </FilesMatch>'; \
    echo '    php_admin_flag engine off'; \
    echo '</Directory>'; \
} >> /etc/apache2/conf-available/security-uploads.conf \
    && a2enconf security-uploads

# Habilita o mod_rewrite do Apache
RUN a2enmod rewrite

# Copia os arquivos do projeto para o container
COPY . /var/www/html/

# Ajusta permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

WORKDIR /var/www/html

EXPOSE 80
