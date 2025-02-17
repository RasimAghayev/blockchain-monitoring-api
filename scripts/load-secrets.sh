#!/bin/sh

# Load Docker secrets into environment variables
for secret in /run/secrets/*; do
  var_name=$(basename "$secret" | tr '[:lower:]' '[:upper:]')
  export "$var_name"=$(cat "$secret")
done

# Start the PHP-FPM service or other processes
exec "$@"
