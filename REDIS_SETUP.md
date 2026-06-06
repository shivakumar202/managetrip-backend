# Redis Caching Setup Guide

## Redis Configuration

To use Redis caching in your Laravel application, follow these steps:

### 1. Install Redis Server (if not already installed)

**Windows (using WSL or XAMPP):**
```bash
# If using XAMPP, Redis is often included. Check if it's running:
# Usually at http://localhost:6379

# Or install via WSL Ubuntu:
wsl apt-get update
wsl apt-get install redis-server
wsl redis-server
```

**macOS:**
```bash
brew install redis
brew services start redis
```

**Linux:**
```bash
sudo apt-get update
sudo apt-get install redis-server
sudo systemctl start redis-server
```

### 2. Configure .env File

Add or update these settings in your `.env` file:

```env
# Cache Configuration
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

### 3. Install Predis (PHP Redis Client)

```bash
cd backend
composer require predis/predis
```

### 4. Cache TTL (Time To Live) Configuration

The current caching setup uses these TTLs:
- **Service Locations**: 24 hours
- **Services by Location**: 24 hours  
- **Hotel Search Results**: 6 hours
- **Hotel Details**: 24 hours
- **Hotel Prices**: 2 hours

You can adjust these in `TripsController.php` by changing the `$cacheTTL` variable.

## Cache Management Commands

### Clear All Caches
```bash
php artisan cache:clear
```

### Clear API Caches Only
```bash
# Clear all API caches
php artisan cache:clear-api all

# Clear specific cache type
php artisan cache:clear-api locations
php artisan cache:clear-api services
php artisan cache:clear-api hotels
php artisan cache:clear-api prices
```

### Monitor Redis (optional)
```bash
redis-cli
> KEYS *
> GET key_name
> FLUSHDB  # Clears current database
> FLUSHALL # Clears all databases
```

## API Caching Overview

### Cached Endpoints

1. **GET /api/trip-service-location**
   - Cache Key: `service_locations_all`
   - TTL: 24 hours
   - Returns: All available service locations

2. **GET /api/trip-services?service_location={location}**
   - Cache Key: `services_location_{location_hash}`
   - TTL: 24 hours
   - Returns: Services for a specific location

3. **GET /api/trip/hotel?term={searchTerm}**
   - Cache Key: `hotels_search_{term_hash}`
   - TTL: 6 hours
   - Returns: Hotels matching search term

4. **GET /api/trip/hotel/{id}**
   - Cache Key: `hotel_details_{id}`
   - TTL: 24 hours
   - Returns: Hotel details with meal plans and rooms

5. **GET /api/hotel-price?hotel_id=X&room_type=Y&meal_plan_id=Z&date=D**
   - Cache Key: `hotel_price_{query_hash}`
   - TTL: 2 hours
   - Returns: Calculated hotel pricing

## Benefits

✅ **Reduced Database Queries**: Frequently requested data is cached in Redis  
✅ **Faster API Response Times**: In-memory data retrieval (microseconds)  
✅ **Lower Server Load**: Fewer database connections needed  
✅ **Better Scalability**: Handle more concurrent users  
✅ **Automatic Expiration**: Cache entries expire after TTL without manual cleanup  
✅ **Flexible Invalidation**: Cache can be cleared/refreshed via console commands  

## Performance Improvement

Expected improvements with Redis caching:

| Metric | Before Cache | After Cache |
|--------|-------------|------------|
| Hotel Search Response | ~200-500ms | ~10-50ms |
| Service Location Response | ~150-300ms | ~5-20ms |
| Price Calculation Response | ~300-800ms | ~20-100ms |
| DB Load | High | Very Low |

## Troubleshooting

### Redis Connection Error
```
Illuminate\Redis\Connections\ConnectionFailedException: Could not connect to Redis at IP:PORT
```

**Solution**: Ensure Redis is running
```bash
redis-cli ping  # Should return PONG
```

### Cache Not Working
1. Verify `CACHE_STORE=redis` in `.env`
2. Verify Redis is running: `redis-cli ping`
3. Clear application cache: `php artisan cache:clear`
4. Check if Predis is installed: `composer show | grep predis`

### Manual Cache Inspection
```bash
redis-cli
> SELECT 1  # Switch to cache database
> KEYS *  # See all cache keys
> GET service_locations_all
> DEL service_locations_all  # Delete specific key
```
