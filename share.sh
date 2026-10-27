#!/bin/zsh
set -euo pipefail

cd "$(dirname "$0")"
export PATH="/opt/homebrew/opt/php@8.4/bin:/opt/homebrew/bin:$PATH"

if ! curl -sf -o /dev/null --max-time 2 http://127.0.0.1:8000/login; then
  echo "Starting Crest on http://127.0.0.1:8000 ..."
  php artisan serve --host=127.0.0.1 --port=8000 >/tmp/crest-serve.log 2>&1 &
  sleep 2
fi

echo "Opening a public tunnel (needs Wi-Fi)..."
cloudflared tunnel --url http://127.0.0.1:8000 --no-autoupdate 2>&1 | tee /tmp/crest-tunnel.log | while IFS= read -r line; do
  echo "$line"
  if [[ "$line" == *"trycloudflare.com"* ]]; then
    url=$(echo "$line" | grep -oE 'https://[a-z0-9-]+\.trycloudflare\.com' | head -1)
    if [[ -n "$url" ]]; then
      perl -i -pe "s|^APP_URL=.*|APP_URL=$url|" .env
      php artisan config:clear >/dev/null
      echo ""
      echo "Share this link:"
      echo "  $url"
      echo ""
      echo "School admin: demoschool@mailinator.com / password"
      echo "Teacher:      teacher@orangebook.ng / password"
      echo "Student:      student@orangebook.ng / password"
      echo "Parent:       parent@orangebook.ng / password"
      echo ""
      echo "Leave this window open. Ctrl+C stops the public link."
    fi
  fi
done
