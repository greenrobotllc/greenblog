#!/bin/bash
# CI Setup Script
# Automates GreenBlog installation, post creation, and static file generation
set -e

BASE_URL="${1:-http://localhost:8080}"
COOKIE_JAR=$(mktemp)

cleanup() {
  rm -f "$COOKIE_JAR"
}
trap cleanup EXIT

echo "=== Setting up GreenBlog at $BASE_URL ==="

# Step 0: GET setup.php first to check requirements
echo "Checking setup requirements..."
SETUP_GET=$(curl -s "$BASE_URL/setup.php")
if echo "$SETUP_GET" | grep -q "already installed"; then
  echo "Already installed, continuing..."
elif echo "$SETUP_GET" | grep -q "Action Required"; then
  echo "ERROR: Setup requirements not met:"
  echo "$SETUP_GET" | grep -oP '(?<=<td>)[^<]+' | paste - - - - | head -20
  exit 1
else
  echo "Requirements OK, proceeding with install..."
fi

# Step 1: Run the installer via POST to setup.php
echo "Running installer..."
SETUP_RESPONSE=$(curl -s -w "\n%{http_code}" -c "$COOKIE_JAR" -X POST "$BASE_URL/setup.php" \
  -d "site_name=CI+Test+Blog" \
  -d "site_description=A+test+blog+for+CI" \
  -d "site_url=$BASE_URL" \
  -d "admin_username=admin" \
  -d "admin_password=testpassword123" \
  -d "admin_email=admin@example.com")

SETUP_HTTP_CODE=$(echo "$SETUP_RESPONSE" | tail -1)
SETUP_BODY=$(echo "$SETUP_RESPONSE" | sed '$d')

if echo "$SETUP_BODY" | grep -q "Installation successful"; then
  echo "Installation successful!"
elif echo "$SETUP_BODY" | grep -q "already installed"; then
  echo "Already installed, continuing..."
else
  echo "Setup failed (HTTP $SETUP_HTTP_CODE):"
  echo "$SETUP_BODY" | grep -oP '(?<=<td>)[^<]+' | paste - - - - | head -20
  echo "---"
  echo "$SETUP_BODY" | grep -i "error\|fail\|not met\|prerequisites" | head -5
  exit 1
fi

# Step 2: GET the login page to obtain a session cookie and CSRF token
echo "Getting login page..."
# Clear cookie jar for fresh session
> "$COOKIE_JAR"
LOGIN_PAGE=$(curl -s -c "$COOKIE_JAR" "$BASE_URL/admin/login.php")
LOGIN_CSRF=$(echo "$LOGIN_PAGE" | grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')

if [ -z "$LOGIN_CSRF" ]; then
  echo "Error: Could not get CSRF token from login page"
  echo "Login page response:"
  echo "$LOGIN_PAGE" | head -10
  exit 1
fi
echo "Login CSRF token: ${LOGIN_CSRF:0:10}..."

# Step 3: POST login with CSRF token
echo "Logging in..."
LOGIN_RESULT=$(curl -s -w "\n%{http_code}" -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST "$BASE_URL/admin/login.php" \
  -d "csrf_token=$LOGIN_CSRF" \
  -d "username=admin" \
  -d "password=testpassword123")

LOGIN_HTTP=$(echo "$LOGIN_RESULT" | tail -1)
echo "Login response: HTTP $LOGIN_HTTP"

# A 302 redirect means successful login
if [ "$LOGIN_HTTP" = "302" ]; then
  echo "Login successful (got redirect)!"
else
  LOGIN_BODY=$(echo "$LOGIN_RESULT" | sed '$d')
  LOGIN_BODY=$(echo "$LOGIN_RESULT" | sed '$d')
  if echo "$LOGIN_BODY" | grep -q "Invalid form submission"; then
    echo "CSRF token mismatch - retrying with fresh session..."
    > "$COOKIE_JAR"
    LOGIN_PAGE2=$(curl -s -c "$COOKIE_JAR" "$BASE_URL/admin/login.php")
    LOGIN_CSRF2=$(echo "$LOGIN_PAGE2" | grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
    echo "Cookies after GET:"
    cat "$COOKIE_JAR"
    LOGIN_RESULT2=$(curl -s -w "\n%{http_code}" -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST "$BASE_URL/admin/login.php" \
      -d "csrf_token=$LOGIN_CSRF2" \
      -d "username=admin" \
      -d "password=testpassword123")
    LOGIN_HTTP2=$(echo "$LOGIN_RESULT2" | tail -1)
    echo "Retry login response: HTTP $LOGIN_HTTP2"
    if [ "$LOGIN_HTTP2" != "302" ]; then
      echo "Login still failing"
      echo "$LOGIN_RESULT2" | sed '$d' | grep -i "error\|invalid" | head -5
    fi
  elif echo "$LOGIN_BODY" | grep -q "Invalid username"; then
    echo "Wrong credentials!"
    exit 1
  fi
  echo "Login returned HTTP $LOGIN_HTTP, continuing..."
fi

# Step 4: Get CSRF token from the new post form
echo "Getting post form..."
NEW_POST_PAGE=$(curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" "$BASE_URL/admin/posts.php?action=new")
POST_CSRF=$(echo "$NEW_POST_PAGE" | grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')

if [ -z "$POST_CSRF" ]; then
  echo "Warning: Could not extract CSRF token from post form"
  echo "Page response (first 5 lines):"
  echo "$NEW_POST_PAGE" | head -5
  # The session might have redirected us to login - try again
  echo "Retrying login..."
  LOGIN_PAGE2=$(curl -s -c "$COOKIE_JAR" "$BASE_URL/admin/login.php")
  LOGIN_CSRF2=$(echo "$LOGIN_PAGE2" | grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
  if [ -n "$LOGIN_CSRF2" ]; then
    curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST "$BASE_URL/admin/login.php" \
      -d "csrf_token=$LOGIN_CSRF2" \
      -d "username=admin" \
      -d "password=testpassword123" > /dev/null
    NEW_POST_PAGE=$(curl -s -b "$COOKIE_JAR" -c "$COOKIE_JAR" "$BASE_URL/admin/posts.php?action=new")
    POST_CSRF=$(echo "$NEW_POST_PAGE" | grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
  fi
  if [ -z "$POST_CSRF" ]; then
    echo "Error: Still could not get CSRF token"
    exit 1
  fi
fi
echo "Post CSRF token: ${POST_CSRF:0:10}..."

# Step 5: Create a test post
echo "Creating test post..."
CREATE_RESULT=$(curl -s -w "\n%{http_code}" -b "$COOKIE_JAR" -c "$COOKIE_JAR" -X POST "$BASE_URL/admin/posts.php" \
  -d "csrf_token=$POST_CSRF" \
  -d "action=create" \
  -d "title=Hello+World" \
  --data-urlencode "content=<p>This is a test post for CI testing. It contains some sample content to validate accessibility and HTML structure.</p>" \
  -d "excerpt=A+test+post+for+CI" \
  -d "status=published" \
  -d "categories[]=1")

CREATE_HTTP=$(echo "$CREATE_RESULT" | tail -1)
echo "Create post response: HTTP $CREATE_HTTP"
if [ "$CREATE_HTTP" = "302" ] || [ "$CREATE_HTTP" = "200" ]; then
  echo "Post created successfully."
else
  echo "Post creation may have failed."
  echo "$CREATE_RESULT" | sed '$d' | head -5
fi

# Step 6: Verify static files were generated
echo "Checking static files..."
sleep 1

HOMEPAGE_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/static/")
echo "Static homepage status: $HOMEPAGE_STATUS"

if [ "$HOMEPAGE_STATUS" != "200" ]; then
  echo "Trying manual regeneration..."
  REGEN_PAGE=$(curl -s -b "$COOKIE_JAR" "$BASE_URL/admin/regenerate.php")
  REGEN_CSRF=$(echo "$REGEN_PAGE" | grep -o 'name="csrf_token" value="[^"]*"' | head -1 | sed 's/.*value="//;s/"//')
  if [ -n "$REGEN_CSRF" ]; then
    curl -s -b "$COOKIE_JAR" -X POST "$BASE_URL/admin/regenerate.php" \
      -d "csrf_token=$REGEN_CSRF" -L > /dev/null
    echo "Regeneration triggered."
    sleep 1
  fi
fi

POST_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/static/hello-world/")
echo "Hello World post status: $POST_STATUS"

echo "=== CI Setup complete ==="
