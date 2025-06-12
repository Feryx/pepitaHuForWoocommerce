#### Ez egy egyszerű, bővítmény nélküli XML feed és rendelés fogadó végpont a Pepita.hu piactérhez, WooCommerce-re szabva.
# Pepita.hu XML feed for WooCommerce
This lightweight PHP-based integration provides an XML product feed and order endpoint compatible with Pepita.hu's marketplace system.
## 🔧 How to use
### 1. Add API key to `wp-config.php`
```php
define('PEPITA_API_KEY', 'Long code like 3247BIUGH7g8gGU986');
```
### 2. Add the API endpoint to functions.php
Place the provided code snippet in your theme’s functions.php, preferably in a child theme (wp-content/themes/yourtheme_child/functions.php).
### 3. Share the order endpoint with Pepita team
   ```
   https://YOURWEBSITE.hu/wp-json/pepita/v1/order/store?apikey=YOURAPIKEY
   ```
### 4. Product feed XML
You can either:
  Access it directly via browser:
  ```
  https://YOURWEBSITE.hu/path/to/pepita-feed.php
  ```
Or generate it via CRON, saving it to a static XML file.
  ```
  curl -s https://valami.hu/pepita-feed.php -o /path/to/feed.xml
  ```
