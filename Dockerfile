# ============================================================================
# Dockerfile — PHP Development Server Container
# ============================================================================
#
# Builds a lightweight Docker image for the Pomodoro Timer application.
# Uses PHP 8.2 CLI with PostgreSQL PDO extension for Supabase connectivity.
# Serves the application via PHP's built-in web server on port 8000.
#
# Build:  docker build -t pomodoro-timer .
# Run:    docker run -p 8000:8000 --env-file .env pomodoro-timer
#
# Required environment variables (set via --env-file or -e flags):
#   DB_HOST, DB_NAME, DB_USER, DB_PASSWORD, DB_PORT
#   SPOTIFY_CLIENT_ID, SPOTIFY_CLIENT_SECRET, SPOTIFY_REDIRECT_URI
#   ADMIN1_USERNAME, ADMIN1_PASSWORD_HASH
#   SUPABASE_URL, SUPABASE_ANON_KEY
# ============================================================================

FROM php:8.2-cli
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql
WORKDIR /var/www/html
COPY . .
EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000"]