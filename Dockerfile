# =============================================================================
# Stage 1: Builder — compile PHP extensions + install Composer dependencies
# Build tools (git, unzip, *-dev) are excluded from the final image
# =============================================================================
FROM php:8.2-apache AS builder

RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libpng-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libsqlite3-dev \
    unzip \
    git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo_sqlite zip gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# =============================================================================
# Stage 2: Runtime — lean production image without build tools
# =============================================================================
FROM php:8.2-apache AS runtime

# Install runtime dependencies using the same -dev package names as the builder
# to avoid Debian version-specific library name mismatches (e.g. libzip4 vs libzip4t64).
# git and unzip are intentionally excluded — build-time only.
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libpng-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libsqlite3-dev \
    sqlite3 \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Copy compiled PHP extension .so files from builder
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/

# Enable extensions (creates .ini files pointing to copied .so files)
RUN docker-php-ext-enable pdo_sqlite zip gd

# Enable required Apache modules
RUN a2enmod rewrite headers

# ---------------------------------------------------------------------------
# PHP security hardening
# ---------------------------------------------------------------------------
RUN { \
    echo "upload_max_filesize = 50M"; \
    echo "post_max_size = 50M"; \
    echo "memory_limit = 256M"; \
    echo "expose_php = Off"; \
    echo "display_errors = Off"; \
    echo "display_startup_errors = Off"; \
    echo "log_errors = On"; \
    echo "error_log = /proc/self/fd/2"; \
    echo "disable_functions = exec,passthru,shell_exec,system,proc_open,popen,pcntl_exec"; \
    echo "session.cookie_httponly = On"; \
    echo "session.use_strict_mode = On"; \
    echo "session.use_only_cookies = On"; \
} > /usr/local/etc/php/conf.d/security.ini

# ---------------------------------------------------------------------------
# Apache security hardening
# ---------------------------------------------------------------------------
RUN { \
    echo "ServerTokens Prod"; \
    echo "ServerSignature Off"; \
    echo "TraceEnable Off"; \
    echo "Header always set X-Content-Type-Options nosniff"; \
    echo "Header always set X-Frame-Options SAMEORIGIN"; \
    echo "Header always set Referrer-Policy no-referrer-when-downgrade"; \
} > /etc/apache2/conf-available/hardening.conf \
    && a2enconf hardening

WORKDIR /var/www/html

# Copy vendor from builder, then copy application source
COPY --from=builder /var/www/html/vendor ./vendor
COPY . .

# Set ownership and least-privilege permissions
# Note: Apache main process must start as root to bind port 80,
#       then drops to www-data for worker processes (default behaviour).
RUN mkdir -p /var/www/html/data/backups \
    && chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && chmod -R 775 /var/www/html/data \
    # Protect sensitive files from web access
    && chmod 640 /var/www/html/composer.json \
    && chmod 640 /var/www/html/composer.lock 2>/dev/null || true

HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD curl -fsSo /dev/null http://localhost/ || exit 1

EXPOSE 80
