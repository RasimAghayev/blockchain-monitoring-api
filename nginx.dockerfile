# Start with Alpine Linux for a minimal base image
FROM nginx:alpine

# Add metadata labels
LABEL maintainer="Rasim Aghayev <rasimaqayev@gmail.com>" \
      version="1.0" \
      description="Production-ready Nginx server"

# Ensure the 'nginx' user and group exist or create them
RUN if ! getent group nginx; then \
        addgroup -g 101 -S nginx; \
    fi && \
    if ! id -u nginx; then \
        adduser -S -D -H -u 101 -h /var/cache/nginx -s /sbin/nologin -G nginx -g nginx nginx; \
    fi

# Install required packages
RUN apk add --no-cache \
    curl \
    tzdata \
    ca-certificates

# Create necessary directories with proper permissions
RUN mkdir -p /var/cache/nginx \
             /var/run/nginx \
             /var/log/nginx \
             /var/www/html/be \
             /var/www/html/fe \
             /etc/nginx/conf.d && \
    chown -R nginx:nginx /var/run/nginx \
                         /var/cache/nginx \
                         /var/log/nginx

# Copy custom Nginx configuration
COPY --chown=nginx:nginx ./server/nginx/nginx.conf /etc/nginx/nginx.conf
COPY --chown=nginx:nginx ./server/nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy static content
COPY --chown=nginx:nginx ./src/be/ /var/www/html/be
COPY --chown=nginx:nginx ./src/fe/ /var/www/html/fe

# Implement healthcheck
HEALTHCHECK --interval=30s --timeout=3s \
    CMD curl -f http://localhost/health || exit 1

# Set working directory
WORKDIR /var/www/html

# Expose ports
EXPOSE 80 443

# Switch to non-root user
USER nginx

# Start Nginx
CMD ["nginx", "-g", "daemon off;"]
