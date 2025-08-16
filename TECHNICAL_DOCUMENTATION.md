# SMARD - Technical Documentation
## Dokumentasi Teknis Lengkap Project SMARD

---

## 📋 DAFTAR ISI

1. [Overview Sistem](#overview-sistem)
2. [Arsitektur Teknis](#arsitektur-teknis)
3. [Database Design](#database-design)
4. [API Documentation](#api-documentation)
5. [Frontend Architecture](#frontend-architecture)
6. [Backend Architecture](#backend-architecture)
7. [Deployment Guide](#deployment-guide)
8. [Security Implementation](#security-implementation)
9. [Testing Strategy](#testing-strategy)
10. [Performance Optimization](#performance-optimization)
11. [Maintenance Guide](#maintenance-guide)
12. [Troubleshooting](#troubleshooting)

---

## 🏗️ OVERVIEW SISTEM

### Deskripsi Sistem
SMARD adalah aplikasi web berbasis Laravel 11 dan Vue.js 3 yang dirancang untuk mengelola peringatan cuaca bandara sesuai standar ICAO Annex 3.

### Komponen Utama
- **Frontend**: Vue.js 3 dengan Inertia.js
- **Backend**: Laravel 11 dengan PHP 8.2
- **Database**: MySQL/PostgreSQL
- **Cache**: Redis (opsional)
- **Queue**: Laravel Queue dengan Supervisor
- **File Storage**: Local/Cloud storage untuk PDF

### Diagram Arsitektur
```
┌─────────────────────────────────────────────────────────────┐
│                        CLIENT                              │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │
│  │   Browser   │  │   Mobile    │  │   Tablet    │        │
│  └─────────────┘  └─────────────┘  └─────────────┘        │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      LOAD BALANCER                         │
│                    (Nginx/HAProxy)                         │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    WEB SERVER (Nginx)                      │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                   APPLICATION SERVER                       │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │
│  │   Laravel   │  │   Queue     │  │   Cache     │        │
│  │   (PHP-FPM) │  │  (Redis)    │  │  (Redis)    │        │
│  └─────────────┘  └─────────────┘  └─────────────┘        │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                      DATABASE                              │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │
│  │    MySQL    │  │   File      │  │   Logs      │        │
│  │  (Primary)  │  │  Storage    │  │  (Files)    │        │
│  └─────────────┘  └─────────────┘  └─────────────┘        │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    EXTERNAL SERVICES                       │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐        │
│  │  WhatsApp   │  │    Email    │  │     SMS     │        │
│  │    API      │  │   Service   │  │   Gateway   │        │
│  └─────────────┘  └─────────────┘  └─────────────┘        │
└─────────────────────────────────────────────────────────────┘
```

---

## 🗄️ DATABASE DESIGN

### Entity Relationship Diagram (ERD)

```sql
-- Users Table (Forecaster Management)
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    nip VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Aerodrome Warnings Table
CREATE TABLE aerodrome_warnings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    warning_number VARCHAR(50) UNIQUE NOT NULL,
    sequence_number INT NOT NULL,
    airport_code VARCHAR(10) NOT NULL,
    start_time DATETIME NOT NULL,
    valid_to DATETIME NOT NULL,
    phenomena JSON NOT NULL,
    source VARCHAR(10) NOT NULL, -- OBS/FCST
    intensity VARCHAR(10) NOT NULL, -- WKN/INTSF/NC
    observation_time DATETIME NULL,
    forecaster_id BIGINT UNSIGNED NOT NULL,
    status ENUM('active', 'cancelled', 'expired') DEFAULT 'active',
    preview_message TEXT NOT NULL,
    translation_message TEXT NOT NULL,
    whatsapp_sent BOOLEAN DEFAULT FALSE,
    whatsapp_sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (forecaster_id) REFERENCES users(id),
    INDEX idx_warning_number (warning_number),
    INDEX idx_sequence_number (sequence_number),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- Warning Cancellations Table
CREATE TABLE warning_cancellations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    warning_id BIGINT UNSIGNED NOT NULL,
    cancellation_number VARCHAR(50) UNIQUE NOT NULL,
    cancellation_sequence_number INT NOT NULL,
    cancelled_by BIGINT UNSIGNED NOT NULL,
    cancellation_message TEXT NOT NULL,
    cancellation_translation TEXT NOT NULL,
    whatsapp_sent BOOLEAN DEFAULT FALSE,
    whatsapp_sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (warning_id) REFERENCES aerodrome_warnings(id),
    FOREIGN KEY (cancelled_by) REFERENCES users(id),
    INDEX idx_cancellation_number (cancellation_number),
    INDEX idx_warning_id (warning_id)
);

-- System Settings Table
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    key_name VARCHAR(100) UNIQUE NOT NULL,
    value TEXT NOT NULL,
    description TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Activity Logs Table
CREATE TABLE activity_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);
```

### Database Indexes
```sql
-- Performance Indexes
CREATE INDEX idx_warnings_forecaster_status ON aerodrome_warnings(forecaster_id, status);
CREATE INDEX idx_warnings_airport_status ON aerodrome_warnings(airport_code, status);
CREATE INDEX idx_warnings_validity ON aerodrome_warnings(start_time, valid_to);
CREATE INDEX idx_cancellations_warning ON warning_cancellations(warning_id, created_at);
```

---

## 🔌 API DOCUMENTATION

### Authentication Endpoints

#### POST /api/auth/login
```json
{
  "email": "forecaster@bmkg.go.id",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "Nurfaijin, S.Si., M.Sc.",
      "email": "forecaster@bmkg.go.id",
      "nip": "197111101997031001"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
  }
}
```

### Warning Management Endpoints

#### GET /api/warnings
**Query Parameters:**
- `status`: active, cancelled, expired
- `page`: page number
- `per_page`: items per page
- `date_from`: start date
- `date_to`: end date

**Response:**
```json
{
  "success": true,
  "data": {
    "warnings": [
      {
        "id": 1,
        "warning_number": "AD WRNG 1",
        "sequence_number": 1,
        "airport_code": "WAHL",
        "start_time": "2025-01-15T10:00:00Z",
        "valid_to": "2025-01-15T12:00:00Z",
        "phenomena": [
          {
            "type": "TS",
            "details": {}
          }
        ],
        "source": "OBS",
        "intensity": "INTSF",
        "status": "active",
        "forecaster": {
          "id": 1,
          "name": "Nurfaijin, S.Si., M.Sc.",
          "nip": "197111101997031001"
        },
        "created_at": "2025-01-15T09:30:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 45,
      "last_page": 3
    }
  }
}
```

#### POST /api/warnings
**Request Body:**
```json
{
  "airport_code": "WAHL",
  "start_time": "2025-01-15T10:00:00Z",
  "valid_to": "2025-01-15T12:00:00Z",
  "phenomena": [
    {
      "type": "TS",
      "details": {}
    },
    {
      "type": "SFC WSPD",
      "details": {
        "wind_speed": 25,
        "gust_speed": 35
      }
    }
  ],
  "source": "OBS",
  "intensity": "INTSF",
  "observation_time": "2025-01-15T09:30:00Z"
}
```

#### POST /api/warnings/{id}/cancel
**Request Body:**
```json
{
  "reason": "Fenomena telah mereda",
  "cancellation_message": "WAHL AD WRNG 2 VALID 151030/151200 CNL AD WRNG 1 151000/151200=",
  "cancellation_translation": "AERODROME WARNING STASIUN METEOROLOGI TUNGGUL WULUNG NOMOR 2..."
}
```

### Sequence Management Endpoints

#### GET /api/sequence/next
**Response:**
```json
{
  "success": true,
  "data": {
    "sequence_number": 15,
    "next_warning_number": "AD WRNG 15"
  }
}
```

#### POST /api/sequence/reset
**Response:**
```json
{
  "success": true,
  "message": "Sequence number berhasil direset",
  "data": {
    "new_sequence_number": 1
  }
}
```

---

## 🎨 FRONTEND ARCHITECTURE

### Component Structure
```
resources/js/
├── app.ts                 # Main application entry
├── components/
│   ├── ui/               # Reusable UI components
│   │   ├── button/
│   │   ├── input/
│   │   ├── dialog/
│   │   └── ...
│   ├── forms/            # Form components
│   │   ├── AerodromeWarningForm.vue
│   │   └── CancellationModal.vue
│   ├── layout/           # Layout components
│   │   ├── AppLayout.vue
│   │   └── ...
│   └── pages/            # Page components
│       ├── Dashboard.vue
│       ├── aerodrome/
│       │   ├── Index.vue
│       │   ├── AllWarnings.vue
│       │   └── Archive.vue
│       └── ...
├── composables/          # Vue composables
│   ├── useAppearance.ts
│   └── useInitials.ts
├── lib/                  # Utility libraries
│   └── utils.ts
└── types/                # TypeScript type definitions
    ├── index.d.ts
    └── globals.d.ts
```

### State Management
```typescript
// Reactive state menggunakan Vue 3 Composition API
const formData = reactive({
  airportCode: 'WAHL',
  warningNumber: '',
  startTime: '',
  endTime: '',
  phenomena: [],
  source: '',
  intensity: '',
  observationTime: '',
  forecaster: ''
});

// Computed properties untuk derived state
const cancellationMessage = computed(() => {
  if (!props.warningRecord) return '';
  // Logic untuk generate cancellation message
});

// Watchers untuk side effects
watch(() => formData.source, (newSource) => {
  if (newSource === 'OBS') {
    // Enable observation time field
  }
});
```

### Form Validation
```typescript
// Custom validation rules
const validationRules = {
  startTime: (value: string) => {
    if (!value) return 'Waktu mulai wajib diisi';
    if (new Date(value) <= new Date()) return 'Waktu mulai harus di masa depan';
    return true;
  },
  phenomena: (value: any[]) => {
    if (!value || value.length === 0) return 'Minimal satu fenomena harus dipilih';
    return true;
  }
};

// Validation function
const validateForm = () => {
  const errors: Record<string, string> = {};
  
  Object.keys(validationRules).forEach(field => {
    const result = validationRules[field](formData[field]);
    if (result !== true) {
      errors[field] = result;
    }
  });
  
  return errors;
};
```

---

## ⚙️ BACKEND ARCHITECTURE

### Directory Structure
```
app/
├── Console/
│   └── Commands/         # Artisan commands
├── Http/
│   ├── Controllers/      # HTTP controllers
│   ├── Middleware/       # Custom middleware
│   └── Requests/         # Form request validation
├── Models/               # Eloquent models
├── Services/             # Business logic services
├── Providers/            # Service providers
└── Exceptions/           # Custom exceptions
```

### Model Relationships
```php
// AerodromeWarning Model
class AerodromeWarning extends Model
{
    protected $fillable = [
        'warning_number',
        'sequence_number',
        'airport_code',
        'start_time',
        'valid_to',
        'phenomena',
        'source',
        'intensity',
        'observation_time',
        'forecaster_id',
        'status',
        'preview_message',
        'translation_message'
    ];

    protected $casts = [
        'phenomena' => 'array',
        'start_time' => 'datetime',
        'valid_to' => 'datetime',
        'observation_time' => 'datetime',
        'whatsapp_sent' => 'boolean'
    ];

    public function forecaster()
    {
        return $this->belongsTo(User::class, 'forecaster_id');
    }

    public function cancellations()
    {
        return $this->hasMany(WarningCancellation::class, 'warning_id');
    }
}
```

### Service Layer
```php
// WarningService
class WarningService
{
    public function createWarning(array $data): AerodromeWarning
    {
        DB::beginTransaction();
        
        try {
            // Generate sequence number
            $sequenceNumber = $this->getNextSequenceNumber();
            
            // Create warning
            $warning = AerodromeWarning::create([
                'warning_number' => "AD WRNG {$sequenceNumber}",
                'sequence_number' => $sequenceNumber,
                'airport_code' => $data['airport_code'],
                'start_time' => $data['start_time'],
                'valid_to' => $data['valid_to'],
                'phenomena' => $data['phenomena'],
                'source' => $data['source'],
                'intensity' => $data['intensity'],
                'observation_time' => $data['observation_time'] ?? null,
                'forecaster_id' => auth()->id(),
                'preview_message' => $this->generatePreviewMessage($data),
                'translation_message' => $this->generateTranslation($data)
            ]);

            // Send WhatsApp notification
            $this->sendWhatsAppNotification($warning);
            
            // Generate PDF
            $this->generatePDF($warning);
            
            DB::commit();
            return $warning;
            
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function generatePreviewMessage(array $data): string
    {
        // Logic untuk generate ICAO format message
        $startFormatted = $this->formatDateTime($data['start_time']);
        $endFormatted = $this->formatDateTime($data['valid_to']);
        $phenomenaString = $this->generatePhenomenaString($data['phenomena']);
        
        $message = "WAHL AD WRNG {$data['sequence_number']} VALID {$startFormatted}/{$endFormatted} {$phenomenaString}";
        
        if ($data['source'] === 'OBS' && isset($data['observation_time'])) {
            $obsFormatted = $this->formatObservationTime($data['observation_time']);
            $message .= " OBS AT {$obsFormatted}Z";
        } else {
            $message .= " {$data['source']}";
        }
        
        $message .= " {$data['intensity']}=";
        
        return $message;
    }
}
```

### Queue Jobs
```php
// SendWhatsAppJob
class SendWhatsAppJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private AerodromeWarning $warning
    ) {}

    public function handle(WhatsAppService $whatsAppService): void
    {
        try {
            $whatsAppService->sendWarning($this->warning);
            
            $this->warning->update([
                'whatsapp_sent' => true,
                'whatsapp_sent_at' => now()
            ]);
            
        } catch (Exception $e) {
            Log::error('WhatsApp sending failed', [
                'warning_id' => $this->warning->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
```

---

## 🚀 DEPLOYMENT GUIDE

### Prerequisites
- PHP 8.2+
- Composer 2.0+
- Node.js 18+
- MySQL 8.0+ atau PostgreSQL 13+
- Redis 6.0+ (opsional)
- Nginx atau Apache
- SSL certificate

### Environment Setup
```bash
# Clone repository
git clone https://github.com/bmkg/smard.git
cd smard

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies
npm install

# Build frontend assets
npm run build

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Environment Configuration
```env
# Application
APP_NAME="SMARD - Sistem Manajemen Aerodrome Warning"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://smard.bmkg.go.id

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=smard_production
DB_USERNAME=smard_user
DB_PASSWORD=secure_password

# Cache & Session
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# WhatsApp Integration
WHATSAPP_API_URL=https://api.whatsapp.com
WHATSAPP_API_TOKEN=your_token_here
WHATSAPP_PHONE_NUMBER=+6281234567890

# File Storage
FILESYSTEM_DISK=local
```

### Database Migration
```bash
# Run migrations
php artisan migrate --force

# Seed initial data
php artisan db:seed --force

# Create storage link
php artisan storage:link
```

### Nginx Configuration
```nginx
server {
    listen 80;
    server_name smard.bmkg.go.id;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name smard.bmkg.go.id;

    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;

    root /var/www/smard/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Supervisor Configuration
```ini
[program:smard-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/smard/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/smard/storage/logs/worker.log
stopwaitsecs=3600
```

### Docker Deployment
```dockerfile
# Dockerfile
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www

EXPOSE 9000
CMD ["php-fpm"]
```

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build: .
    container_name: smard_app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./docker/php-fpm.conf:/usr/local/etc/php-fpm.d/www.conf
    networks:
      - smard_network

  nginx:
    image: nginx:alpine
    container_name: smard_nginx
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./:/var/www
      - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf
    networks:
      - smard_network

  db:
    image: mysql:8.0
    container_name: smard_db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: smard
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_USER: smard_user
      MYSQL_PASSWORD: smard_password
    volumes:
      - db_data:/var/lib/mysql
    networks:
      - smard_network

  redis:
    image: redis:alpine
    container_name: smard_redis
    restart: unless-stopped
    networks:
      - smard_network

volumes:
  db_data:

networks:
  smard_network:
    driver: bridge
```

---

## 🔒 SECURITY IMPLEMENTATION

### Authentication & Authorization
```php
// Authentication middleware
class AuthenticateForecaster
{
    public function handle($request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Check if user is active forecaster
        if (!auth()->user()->is_active) {
            auth()->logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda telah dinonaktifkan.'
            ]);
        }

        return $next($request);
    }
}

// Authorization policies
class WarningPolicy
{
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, AerodromeWarning $warning): bool
    {
        return $user->id === $warning->forecaster_id && $warning->status === 'active';
    }

    public function cancel(User $user, AerodromeWarning $warning): bool
    {
        return $user->is_active && $warning->status === 'active';
    }
}
```

### Input Validation
```php
// Form request validation
class CreateWarningRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'airport_code' => 'required|string|max:10',
            'start_time' => 'required|date|after:now',
            'valid_to' => 'required|date|after:start_time',
            'phenomena' => 'required|array|min:1',
            'phenomena.*.type' => 'required|string|in:TS,GR,HVY RA,TSRA,HVY TSRA,SFC WSPD,SFC WIND,VIS,SQ,VA,TOX CHEM,TSUNAMI,CUSTOM',
            'source' => 'required|string|in:OBS,FCST',
            'intensity' => 'required|string|in:WKN,INTSF,NC',
            'observation_time' => 'nullable|date|before_or_equal:start_time'
        ];
    }

    public function messages(): array
    {
        return [
            'start_time.after' => 'Waktu mulai harus di masa depan.',
            'valid_to.after' => 'Waktu berakhir harus setelah waktu mulai.',
            'phenomena.min' => 'Minimal satu fenomena harus dipilih.',
            'observation_time.before_or_equal' => 'Waktu observasi harus sebelum atau sama dengan waktu mulai.'
        ];
    }
}
```

### CSRF Protection
```php
// CSRF token verification
class VerifyCsrfToken extends Middleware
{
    protected $except = [
        'api/webhook/*', // WhatsApp webhook
    ];
}
```

### Rate Limiting
```php
// Rate limiting middleware
class ThrottleRequests extends Middleware
{
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1)
    {
        $key = $this->resolveRequestSignature($request);
        
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildResponse($key, $maxAttempts);
        }
        
        $this->limiter->hit($key, $decayMinutes * 60);
        
        $response = $next($request);
        
        return $this->addHeaders(
            $response, $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }
}
```

---

## 🧪 TESTING STRATEGY

### Unit Tests
```php
// WarningServiceTest
class WarningServiceTest extends TestCase
{
    use RefreshDatabase;

    private WarningService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WarningService::class);
    }

    public function test_can_create_warning(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $data = [
            'airport_code' => 'WAHL',
            'start_time' => now()->addHour(),
            'valid_to' => now()->addHours(2),
            'phenomena' => [
                ['type' => 'TS', 'details' => []]
            ],
            'source' => 'OBS',
            'intensity' => 'INTSF',
            'observation_time' => now()
        ];

        $warning = $this->service->createWarning($data);

        $this->assertInstanceOf(AerodromeWarning::class, $warning);
        $this->assertEquals('WAHL', $warning->airport_code);
        $this->assertEquals('OBS', $warning->source);
        $this->assertEquals($user->id, $warning->forecaster_id);
    }

    public function test_generates_correct_preview_message(): void
    {
        $data = [
            'airport_code' => 'WAHL',
            'sequence_number' => 1,
            'start_time' => '2025-01-15 10:00:00',
            'valid_to' => '2025-01-15 12:00:00',
            'phenomena' => [
                ['type' => 'TS', 'details' => []]
            ],
            'source' => 'OBS',
            'intensity' => 'INTSF',
            'observation_time' => '2025-01-15 09:30:00'
        ];

        $message = $this->service->generatePreviewMessage($data);

        $this->assertEquals(
            'WAHL AD WRNG 1 VALID 151000/151200 TS OBS AT 0930Z INTSF=',
            $message
        );
    }
}
```

### Feature Tests
```php
// WarningControllerTest
class WarningControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_warning_via_api(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/warnings', [
            'airport_code' => 'WAHL',
            'start_time' => now()->addHour()->toISOString(),
            'valid_to' => now()->addHours(2)->toISOString(),
            'phenomena' => [
                ['type' => 'TS', 'details' => []]
            ],
            'source' => 'OBS',
            'intensity' => 'INTSF',
            'observation_time' => now()->toISOString()
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'warning_number',
                        'airport_code',
                        'status'
                    ]
                ]);

        $this->assertDatabaseHas('aerodrome_warnings', [
            'airport_code' => 'WAHL',
            'source' => 'OBS',
            'forecaster_id' => $user->id
        ]);
    }

    public function test_validates_required_fields(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/warnings', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'airport_code',
                    'start_time',
                    'valid_to',
                    'phenomena',
                    'source',
                    'intensity'
                ]);
    }
}
```

### Browser Tests
```php
// WarningFormTest
class WarningFormTest extends DuskTestCase
{
    public function test_can_create_warning_through_form(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/aerodrome/warnings/create')
                    ->type('start_time', now()->addHour()->format('Y-m-d\TH:i'))
                    ->type('valid_to', now()->addHours(2)->format('Y-m-d\TH:i'))
                    ->select('source', 'OBS')
                    ->select('intensity', 'INTSF')
                    ->click('@add-phenomenon')
                    ->select('@phenomenon-type', 'TS')
                    ->click('@generate-preview')
                    ->waitForText('Preview generated successfully')
                    ->click('@publish-warning')
                    ->waitForText('Warning published successfully');
        });
    }
}
```

---

## ⚡ PERFORMANCE OPTIMIZATION

### Database Optimization
```sql
-- Index optimization
CREATE INDEX idx_warnings_composite ON aerodrome_warnings(airport_code, status, created_at);
CREATE INDEX idx_warnings_forecaster_date ON aerodrome_warnings(forecaster_id, created_at DESC);

-- Query optimization
SELECT w.*, u.name as forecaster_name
FROM aerodrome_warnings w
INNER JOIN users u ON w.forecaster_id = u.id
WHERE w.airport_code = 'WAHL'
  AND w.status = 'active'
  AND w.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY w.created_at DESC
LIMIT 50;
```

### Caching Strategy
```php
// Cache frequently accessed data
class WarningRepository
{
    public function getActiveWarnings(string $airportCode): Collection
    {
        return Cache::remember(
            "active_warnings_{$airportCode}",
            now()->addMinutes(5),
            fn() => AerodromeWarning::where('airport_code', $airportCode)
                                   ->where('status', 'active')
                                   ->with('forecaster')
                                   ->get()
        );
    }

    public function getNextSequenceNumber(): int
    {
        return Cache::remember(
            'next_sequence_number',
            now()->addSeconds(30),
            fn() => AerodromeWarning::max('sequence_number') + 1
        );
    }
}
```

### Frontend Optimization
```typescript
// Lazy loading components
const AerodromeWarningForm = defineAsyncComponent(() => 
  import('./forms/AerodromeWarningForm.vue')
);

// Debounced search
const debouncedSearch = debounce((query: string) => {
  searchWarnings(query);
}, 300);

// Virtual scrolling for large lists
const virtualList = computed(() => {
  const start = currentPage.value * pageSize;
  const end = start + pageSize;
  return warnings.value.slice(start, end);
});
```

### Asset Optimization
```javascript
// Vite configuration
export default defineConfig({
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['vue', 'inertia'],
          ui: ['@headlessui/vue', '@heroicons/vue']
        }
      }
    },
    chunkSizeWarningLimit: 1000
  }
});
```

---

## 🔧 MAINTENANCE GUIDE

### Regular Maintenance Tasks
```bash
# Daily tasks
php artisan queue:work --timeout=3600
php artisan schedule:run

# Weekly tasks
php artisan backup:run
php artisan cache:clear
php artisan config:clear

# Monthly tasks
php artisan migrate:status
php artisan route:cache
php artisan view:cache
```

### Monitoring Commands
```php
// Health check command
class HealthCheckCommand extends Command
{
    protected $signature = 'health:check';

    public function handle()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'whatsapp' => $this->checkWhatsApp(),
            'storage' => $this->checkStorage()
        ];

        $failed = array_filter($checks, fn($check) => !$check['status']);

        if (!empty($failed)) {
            $this->error('Health check failed:');
            foreach ($failed as $service => $check) {
                $this->error("- {$service}: {$check['message']}");
            }
            return 1;
        }

        $this->info('All systems healthy!');
        return 0;
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => true, 'message' => 'OK'];
        } catch (Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
}
```

### Backup Strategy
```php
// Automated backup
class BackupCommand extends Command
{
    protected $signature = 'backup:create';

    public function handle()
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "smard_backup_{$timestamp}.sql";

        // Database backup
        $command = "mysqldump -u " . config('database.connections.mysql.username') .
                  " -p" . config('database.connections.mysql.password') .
                  " " . config('database.connections.mysql.database') .
                  " > storage/backups/{$filename}";

        exec($command, $output, $returnCode);

        if ($returnCode === 0) {
            $this->info("Backup created: {$filename}");
            
            // Upload to cloud storage
            Storage::disk('backups')->put($filename, file_get_contents("storage/backups/{$filename}"));
            
            // Clean old backups
            $this->cleanOldBackups();
        } else {
            $this->error('Backup failed');
        }
    }
}
```

---

## 🐛 TROUBLESHOOTING

### Common Issues

#### 1. WhatsApp Integration Not Working
```php
// Debug WhatsApp service
class WhatsAppService
{
    public function debugConnection(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.whatsapp.token')
            ])->get(config('services.whatsapp.url') . '/status');

            return [
                'status' => 'connected',
                'response' => $response->json()
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
```

#### 2. Database Connection Issues
```bash
# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();

# Check database status
php artisan migrate:status

# Reset database
php artisan migrate:fresh --seed
```

#### 3. Queue Jobs Not Processing
```bash
# Check queue status
php artisan queue:work --verbose

# Restart queue workers
php artisan queue:restart

# Clear failed jobs
php artisan queue:flush
```

#### 4. Performance Issues
```php
// Performance monitoring
class PerformanceMonitor
{
    public function monitorRequest(): void
    {
        $startTime = microtime(true);
        
        // Your application logic here
        
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;
        
        if ($executionTime > 1000) { // More than 1 second
            Log::warning('Slow request detected', [
                'execution_time' => $executionTime,
                'url' => request()->url(),
                'method' => request()->method()
            ]);
        }
    }
}
```

### Log Analysis
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check error logs
tail -f storage/logs/error.log

# Check queue logs
tail -f storage/logs/worker.log

# Search for specific errors
grep -i "error" storage/logs/laravel.log | tail -20
```

### Debug Mode
```php
// Enable debug mode temporarily
// In .env file
APP_DEBUG=true
LOG_LEVEL=debug

// Debug specific components
Log::debug('Warning creation started', ['data' => $request->all()]);
Log::debug('Preview message generated', ['message' => $previewMessage]);
Log::debug('WhatsApp sent', ['warning_id' => $warning->id]);
```

---

## 📚 ADDITIONAL RESOURCES

### Documentation Links
- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Documentation](https://vuejs.org/guide/)
- [Inertia.js Documentation](https://inertiajs.com/)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)

### Code Standards
- [PSR-12 Coding Standards](https://www.php-fig.org/psr/psr-12/)
- [Vue.js Style Guide](https://vuejs.org/style-guide/)
- [Laravel Best Practices](https://laravel.com/docs/best-practices)

### Security Resources
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security](https://laravel.com/docs/security)
- [Vue.js Security](https://vuejs.org/guide/best-practices/security.html)

---

*Dokumentasi teknis ini dibuat untuk project SMARD - Sistem Manajemen Aerodrome Warning*
*Versi: 1.0 | Tanggal: [Tanggal] | Status: Final*
