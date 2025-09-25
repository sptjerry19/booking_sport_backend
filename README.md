# Booking Sport API

> **Stack**: Laravel 11 (API) + Nuxt 3 (SPA). DB: MySQL. Cache/Queue: Redis. Auth: Sanctum SPA.

A comprehensive sports venue booking system with real-time availability, pricing rules, and payment processing.

## 🚀 Features

### MVP Features

-   ✅ **Authentication & Authorization**: Sanctum SPA auth with role-based permissions (Player, Owner, Admin)
-   ✅ **Venue & Court Management**: Complete CRUD for venues and courts with image upload
-   ✅ **Sports Management**: Support for multiple sports with positions and player requirements
-   ✅ **Pricing Rules**: Flexible pricing based on day of week, time ranges, and special conditions
-   ✅ **Time Slot System**: Anti-double booking with database-level constraints
-   🔄 **Booking System**: Secure booking flow with payment integration
-   🔄 **Notifications**: Email and push notifications for bookings and reminders

### Architecture Highlights

-   **Controller-Service Pattern**: Thin controllers, business logic in services
-   **Repository Pattern**: For complex queries and data abstraction
-   **Policy-based Authorization**: Row-level security for venue owners
-   **Form Request Validation**: Comprehensive input validation
-   **Event-Driven**: Booking events trigger notifications
-   **Anti-Double Booking**: Transaction locks with unique constraints

## 📋 Prerequisites

-   PHP 8.1+
-   Composer
-   MySQL 8.0+
-   Redis 7+
-   Docker & Docker Compose (recommended)

## 🛠️ Installation

### Option 1: Docker Setup (Recommended)

```bash
# Clone the repository
git clone <repository-url>
cd booking_sport_backend

# Install dependencies
make install

# Setup development environment (starts Docker services)
make setup

# Start development server
make dev
```

### Option 2: Manual Setup

```bash
# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your database and Redis in .env

# Run migrations and seeders
php artisan migrate --seed

# Start development server
php artisan serve
```

## 🔧 Environment Configuration

Key environment variables in `.env`:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=booking_sport
DB_USERNAME=root
DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Sanctum SPA
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000
FRONTEND_URL=http://localhost:3000

# Payment (Mock for development)
PAYMENT_MOCK_MODE=true
PAYMENT_WEBHOOK_SECRET=your-webhook-secret-here
```

## 📊 Database Schema

### Core Tables

-   `users` - User accounts with profile information
-   `sports` - Available sports (football, basketball, etc.)
-   `venues` - Sports venues owned by users
-   `courts` - Individual courts within venues
-   `pricing_rules` - Flexible pricing configuration
-   `time_slots` - Generated time slots for booking
-   `bookings` - User bookings with payment status

### Key Relationships

-   User → Venues (1:N) - Owner relationship
-   Venue → Courts (1:N) - Venue contains multiple courts
-   Court → PricingRules (1:N) - Different pricing for different times
-   Court → TimeSlots (1:N) - Generated slots based on pricing rules
-   User → Bookings (1:N) - User booking history

## 🔐 Authentication & Authorization

### Roles

-   **Player**: Book courts, manage profile
-   **Owner**: Manage owned venues and courts
-   **Admin**: Full system access, approve venues

### API Authentication

Uses Laravel Sanctum SPA authentication:

```bash
# Get CSRF cookie
GET /sanctum/csrf-cookie

# Login
POST /api/v1/auth/login
{
    "email": "user@example.com",
    "password": "password"
}

# Access protected routes with session cookie
GET /api/v1/auth/me
```

## 📡 API Endpoints

### Public Endpoints

```
GET    /api/v1/sports              # List all sports
GET    /api/v1/sports/{id}         # Sport details
GET    /api/v1/venues              # List venues (with filters)
GET    /api/v1/venues/{id}         # Venue details
GET    /api/v1/venues/{id}/courts  # Courts in venue
```

### Authentication

```
POST   /api/v1/auth/register       # User registration
POST   /api/v1/auth/login          # User login
POST   /api/v1/auth/logout         # User logout
GET    /api/v1/auth/me             # Current user info
```

### Protected Endpoints (Owner/Admin)

```
POST   /api/v1/venues              # Create venue
PUT    /api/v1/venues/{id}         # Update venue
DELETE /api/v1/venues/{id}         # Delete venue

POST   /api/v1/venues/{id}/courts  # Create court
PUT    /api/v1/courts/{id}         # Update court
DELETE /api/v1/courts/{id}         # Delete court
```

## 🧪 Testing

```bash
# Run all tests
make test

# Run specific test suite
php artisan test --filter=BookingTest

# Run with coverage
php artisan test --coverage
```

## 🎯 Code Quality

```bash
# Run Laravel Pint (code formatting)
make pint

# Clear caches
make clean
```

## 📝 Development Commands

```bash
# Available make commands
make help

# Database operations
make migrate          # Run migrations
make seed            # Run seeders

# Development
make dev             # Start dev server
make clean           # Clear caches
```

## 🏗️ Architecture Patterns

### Service Layer Example

```php
class BookingService
{
    public function createBooking(User $user, CreateBookingData $data): Booking
    {
        return DB::transaction(function () use ($user, $data) {
            // Lock time slots to prevent double booking
            $slots = TimeSlot::where('court_id', $data->courtId)
                ->where('date', $data->date)
                ->whereBetween('start_time', [$data->startTime, $data->endTime])
                ->lockForUpdate()
                ->get();

            // Validate availability and create booking
            // ...
        });
    }
}
```

### Policy Example

```php
class VenuePolicy
{
    public function update(User $user, Venue $venue): bool
    {
        return $user->id === $venue->owner_id || $user->hasRole('admin');
    }
}
```

## 🚀 Deployment

### Production Checklist

-   [ ] Set `APP_ENV=production`
-   [ ] Configure proper database credentials
-   [ ] Set up Redis for caching and queues
-   [ ] Configure mail settings
-   [ ] Set up file storage (S3)
-   [ ] Configure proper CORS settings
-   [ ] Set up SSL certificates
-   [ ] Configure queue workers
-   [ ] Set up monitoring (Sentry)

## 📚 Demo Data

The system includes comprehensive seeders:

### Demo Accounts

-   **Admin**: admin@example.com / password
-   **Owner**: owner@example.com / password
-   **Player**: player@example.com / password

### Demo Data

-   5+ sports (Football, Basketball, Tennis, etc.)
-   3+ venues with realistic information
-   10+ courts across different venues
-   Pricing rules for different time periods
-   Sample bookings and time slots

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Code Standards

-   Follow PSR-12 coding standards
-   Write comprehensive tests
-   Document all public methods
-   Use semantic commit messages

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support, email dev@bookingsport.com or join our Slack channel.

---

**Built with ❤️ using Laravel 11 & modern PHP practices**
