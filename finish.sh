#!/bin/bash



# ------------------------------------------------------------------
# 1. Clear Laravel file-based caches
# ------------------------------------------------------------------

set -e

APP_DIR="/srv/www/php/dummy-api"
ARTISAN="$APP_DIR/artisan"


echo ""
echo "[1/1] Clear Laravel caches..."
if [ -f "$ARTISAN" ]; then
    php "$ARTISAN" config:clear --no-interaction 2>/dev/null && echo "  Config: OK"
    php "$ARTISAN" view:clear --no-interaction 2>/dev/null && echo "  View:   OK"
    php "$ARTISAN" route:clear --no-interaction 2>/dev/null && echo "  Route:  OK"
    php "$ARTISAN" optimize:clear --no-interaction 2>/dev/null && echo "  Optimize: OK"
else
    echo "  [!] Artisan tidak ditemukan di $ARTISAN"
fi

# ------------------------------------------------------------------
echo ""
echo "========================================"
echo "  Finish selesai — semua service siap."
echo "========================================"