FROM redis:7-alpine

# Copy custom Redis configuration
COPY docker/redis/redis.conf /usr/local/etc/redis/redis.conf

# Create directory for Redis data
RUN mkdir -p /data

# Set proper permissions
RUN chown -R redis:redis /data

# Expose Redis port
EXPOSE 6379

# Start Redis with custom configuration
CMD ["redis-server", "/usr/local/etc/redis/redis.conf"]



