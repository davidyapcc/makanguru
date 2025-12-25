# Chat Rate Limiting Documentation

## Overview

MakanGuru implements **session-based rate limiting** to prevent abuse of the AI chat interface and ensure fair usage of the AI API services. The rate limiting system is designed to be user-friendly with persona-specific feedback messages.

---

## Features

### 1. **Session-Based Tracking**
- Rate limits are enforced per browser session (no authentication required)
- Uses Laravel's session system for tracking message timestamps
- Automatically cleans up expired timestamps

### 2. **Configurable Limits**
- Easily configurable via `.env` file
- Default: 5 messages per 60 seconds (1 minute)
- Can be adjusted based on server capacity and API quotas

### 3. **User-Friendly Feedback**
- **Persona-specific messages**: Each AI persona (Mak Cik, Gym Bro, Atas Friend) has unique rate limit warnings
- **Visual indicators**: Yellow warning banner with countdown timer
- **Disabled send button**: Prevents form submission when rate limited
- **Real-time countdown**: Shows seconds until limit resets

### 4. **Automatic Reset**
- Rate limit window slides with each message
- Oldest messages automatically expire from tracking
- Users can send more messages once old ones fall outside the time window

---

## Configuration

### Environment Variables

Add these variables to your `.env` file:

```ini
# Chat Configuration
# Rate limiting: Maximum messages per time window (default: 5)
CHAT_RATE_LIMIT_MAX=5

# Rate limiting: Time window in seconds (default: 60 = 1 minute)
CHAT_RATE_LIMIT_WINDOW=60

# Default persona: makcik, gymbro, or atas (default: makcik)
CHAT_DEFAULT_PERSONA=makcik

# Default AI model: gemini, groq-openai, or groq-meta (default: groq-openai)
CHAT_DEFAULT_MODEL=groq-openai
```

### Configuration File

The configuration is centralized in `config/chat.php`:

```php
return [
    'rate_limit' => [
        'max_messages' => env('CHAT_RATE_LIMIT_MAX', 5),
        'window_seconds' => env('CHAT_RATE_LIMIT_WINDOW', 60),
    ],
    'default_persona' => env('CHAT_DEFAULT_PERSONA', 'makcik'),
    'default_model' => env('CHAT_DEFAULT_MODEL', 'groq-openai'),
];
```

---

## How It Works

### 1. Message Tracking

When a user sends a message:
1. System checks session for existing message timestamps
2. Removes timestamps older than the rate limit window
3. Counts remaining timestamps
4. If count >= max limit: **Rate limit triggered**
5. If count < max limit: **Message allowed**, current timestamp added

### 2. Rate Limit Calculation

```php
// Example with default settings:
// Max Messages: 5
// Time Window: 60 seconds

// User sends messages at:
// T0: Message 1 (allowed)
// T5: Message 2 (allowed)
// T10: Message 3 (allowed)
// T15: Message 4 (allowed)
// T20: Message 5 (allowed)
// T25: Message 6 (RATE LIMITED - must wait until T60 when Message 1 expires)
```

### 3. Reset Timer

The system calculates how long until the oldest message expires:

```php
$resetIn = $rateLimitWindow - $now->diffInSeconds($oldestMessage);
```

This provides users with an accurate countdown.

---

## Persona-Specific Messages

### Mak Cik (Nurturing, Scolding)
```
"Adoi! Slow down lah! Mak Cik cannot keep up with you asking so fast.
Give me {$seconds} seconds to rest, okay? Don't be so impatient!"
```

### Gym Bro (Fitness Analogy)
```
"Woah bro! Too much too fast sia! Even protein shakes need rest time
between sets. Chill for {$seconds} seconds, then we go again. No rush!"
```

### Atas Friend (Sophisticated, Condescending)
```
"Darling, please! One must not be so... eager. Quality takes time.
Give me {$seconds} seconds to compose myself. Patience is a virtue, after all."
```

---

## Implementation Details

### Files Modified

1. **`app/Livewire/ChatInterface.php`**
   - Added rate limit properties
   - Implemented `isRateLimited()` method
   - Added `getRateLimitMessage()` for persona-specific messages
   - Updated `sendMessage()` to check rate limits

2. **`resources/views/livewire/chat-interface.blade.php`**
   - Added rate limit warning banner
   - Disabled send button when rate limited
   - Visual countdown display

3. **`config/chat.php`** (New)
   - Centralized chat configuration
   - Rate limit settings
   - Default persona and model

4. **`.env.example`**
   - Added chat configuration examples
   - Documented all options

### Code Structure

```php
// ChatInterface.php
class ChatInterface extends Component
{
    private int $maxMessagesPerWindow;  // Loaded from config
    private int $rateLimitWindow;       // Loaded from config
    public ?string $rateLimitMessage;   // Error message to display
    public ?int $rateLimitResetIn;      // Seconds until reset

    public function sendMessage(): void
    {
        if ($this->isRateLimited()) {
            return; // Prevent message from being sent
        }
        // Process message...
    }

    private function isRateLimited(): bool
    {
        // Session-based tracking
        $sessionKey = 'chat_messages_' . session()->getId();
        $messages = session()->get($sessionKey, []);

        // Filter expired messages
        // Count remaining messages
        // Return true if exceeded limit
    }
}
```

---

## Testing

### Automated Tests

We've created comprehensive tests in `tests/Feature/ChatRateLimitTest.php`:

```bash
php artisan test --filter ChatRateLimitTest
```

**Test Coverage:**
1. ✅ Users can send messages up to the rate limit
2. ✅ Users cannot send messages beyond the rate limit
3. ✅ Rate limit messages are persona-specific
4. ✅ Rate limit resets after time window

**Results:**
```
Tests:    4 passed (13 assertions)
Duration: 67.67s
```

### Manual Testing

1. **Test Basic Rate Limit:**
   ```bash
   php artisan serve
   # Visit http://127.0.0.1:8000
   # Send 5 messages quickly
   # 6th message should show rate limit warning
   ```

2. **Test Different Personas:**
   ```bash
   # Switch between Mak Cik, Gym Bro, Atas Friend
   # Trigger rate limit for each
   # Verify different warning messages
   ```

3. **Test Reset:**
   ```bash
   # Trigger rate limit
   # Wait 60 seconds
   # Verify you can send messages again
   ```

---

## Customization Examples

### Stricter Limits (Production with Limited API Quota)

```ini
# .env
CHAT_RATE_LIMIT_MAX=3       # Only 3 messages
CHAT_RATE_LIMIT_WINDOW=120  # Per 2 minutes
```

### Generous Limits (Development/Testing)

```ini
# .env
CHAT_RATE_LIMIT_MAX=20      # 20 messages
CHAT_RATE_LIMIT_WINDOW=60   # Per minute
```

### Per-Hour Limits

```ini
# .env
CHAT_RATE_LIMIT_MAX=100     # 100 messages
CHAT_RATE_LIMIT_WINDOW=3600 # Per hour (3600 seconds)
```

---

## Troubleshooting

### Issue: Rate limit triggers immediately

**Cause:** Session not being maintained across requests

**Solution:**
```bash
# Check session configuration in .env
SESSION_DRIVER=database  # or 'file', 'redis'
SESSION_LIFETIME=120

# Clear config cache
php artisan config:clear
php artisan cache:clear
```

### Issue: Rate limit persists after time window

**Cause:** Server time sync issues

**Solution:**
```bash
# Check server time
date

# Ensure APP_TIMEZONE is set correctly in .env
APP_TIMEZONE=Asia/Kuala_Lumpur
```

### Issue: Different users share rate limit

**Cause:** Session ID collision (very rare)

**Solution:**
```bash
# Regenerate app key
php artisan key:generate

# Clear all sessions
php artisan session:clear
```

---

## Performance Considerations

### Session Storage

**Database (Recommended for Production):**
```ini
SESSION_DRIVER=database
```
- Persistent across server restarts
- Works with load balancers
- Requires migration: `php artisan session:table`

**Redis (Best Performance):**
```ini
SESSION_DRIVER=redis
REDIS_CLIENT=phpredis
```
- Extremely fast
- Auto-cleanup of expired data
- Requires Redis server

**File (Development Only):**
```ini
SESSION_DRIVER=file
```
- Simple setup
- Not suitable for production
- Doesn't work with multiple servers

### Memory Usage

Each rate limit entry stores:
- Session ID: ~40 bytes
- Timestamps array: ~24 bytes per message
- Total per user: ~160 bytes (for 5 messages)

**Estimated for 1,000 concurrent users:**
- 1,000 sessions × 160 bytes = ~156 KB

Very lightweight! 🚀

---

## Security Considerations

### 1. **No Authentication Required**
- Rate limits work without user accounts
- Based on browser session only
- Users can bypass by clearing cookies (acceptable trade-off)

### 2. **Server-Side Enforcement**
- All checks done in backend
- Frontend disabling is UI-only (not security)
- Cannot be bypassed via browser dev tools

### 3. **DoS Protection**
- Prevents single user from overwhelming API
- Reduces risk of API quota exhaustion
- Limits cost of malicious usage

### 4. **Privacy**
- No personal data stored
- Only timestamps tracked
- Auto-cleanup prevents accumulation

---

## Future Enhancements

### Potential Improvements

1. **IP-Based Rate Limiting**
   - Track by IP address in addition to session
   - Prevent cookie-clearing bypass
   - Requires IP detection middleware

2. **Dynamic Rate Limits**
   - Adjust limits based on API quota usage
   - Tighter limits during peak hours
   - Looser limits for verified users

3. **Rate Limit Analytics**
   - Track how often users hit limits
   - Identify potential abuse patterns
   - Optimize limits based on data

4. **User Feedback**
   - Allow users to request higher limits
   - Email notification system
   - Gamification (reward good behavior)

---

## API Cost Impact

### Cost Savings Example

**Without Rate Limiting:**
- Malicious user sends 1,000 requests/minute
- Cost per request: $0.001 (estimated)
- Cost per minute: $1.00
- Cost per hour: $60.00
- Cost per day: $1,440.00

**With Rate Limiting (5 messages/minute):**
- Maximum requests: 5/minute
- Cost per minute: $0.005
- Cost per hour: $0.30
- Cost per day: $7.20

**Savings: 99.5%** 💰

---

## Conclusion

The rate limiting system provides:
- ✅ **Protection** against abuse and API quota exhaustion
- ✅ **User-friendly** feedback with persona-specific messages
- ✅ **Configurable** limits for different environments
- ✅ **Tested** with comprehensive test coverage
- ✅ **Performant** with minimal overhead
- ✅ **Scalable** for production deployment

This implementation follows Laravel best practices and maintains MakanGuru's high engineering standards (PSR-12, SOLID, type safety).

---

*Last Updated: 2025-12-21*
*Author: AI-assisted development with Claude*
