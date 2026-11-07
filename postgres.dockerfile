# Use the official PostgreSQL Alpine image
# Digest pinned (R91): a floating tag silently changes the build input.
FROM postgres:16-alpine@sha256:aa6cb33cf4c19c765cefcf48d96176258800870a9a1e930ad126200f9d825139

# Add metadata labels
LABEL maintainer="Rasim Aghayev <rasimaqayev@gmail.com>" \
      version="1.0" \
      description="Production-ready Postgres server"

# Set environment variables for customization
ENV POSTGRES_DB=invoicedb \
    POSTGRES_USER=pgsql \
    POSTGRES_PASSWORD=pgsql \
    PGDATA=/var/lib/postgresql/data/pgdata

# Install additional tools and extensions
RUN apk add --no-cache \
    pg_cron \
    postgis \
    timescaledb \
    && mkdir -p /docker-entrypoint-initdb.d

# Copy initialization scripts
COPY ./server/postgres /docker-entrypoint-initdb.d/

# Custom PostgreSQL configuration
COPY ./server/postgres/postgresql.conf /etc/postgresql/postgresql.conf

# Switch to root to create PGDATA directory
USER root

# Create necessary directories with proper permissions
RUN mkdir -p "$PGDATA" \
    && chown -R postgres:postgres "$PGDATA" \
    && chmod 700 "$PGDATA"

# Use default postgres user
USER postgres

# Health check
HEALTHCHECK --interval=30s --timeout=5s --retries=3 \
    CMD pg_isready -U $POSTGRES_USER -d $POSTGRES_DB || exit 1

# Expose PostgreSQL port
EXPOSE 5432


# Set the default command
CMD ["postgres", "-c", "config_file=/etc/postgresql/postgresql.conf"]