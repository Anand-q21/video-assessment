# Video Platform API

A comprehensive video sharing platform API built with Symfony and API Platform, featuring user authentication, video upload/management, social interactions, and advanced search capabilities.

## 🚀 Tech Stack

### Backend Framework
- **PHP 8.2+** - Core programming language
- **Symfony 6.4** - Web application framework
- **API Platform 4.2** - REST and GraphQL API framework
- **Doctrine ORM 3.5** - Database abstraction layer

### Database
- **MySQL 8.0** - Primary database
- **Doctrine Migrations** - Database schema management

### Authentication & Security
- **Lexik JWT Authentication Bundle** - JWT token management
- **Symfony Security Bundle** - Authentication and authorization
- **CORS Bundle** - Cross-origin resource sharing

### Frontend Assets
- **Symfony Asset Mapper** - Asset management
- **Stimulus Bundle** - JavaScript framework
- **Turbo** - SPA-like page acceleration

### Development & Testing
- **PHPUnit 11.5** - Testing framework
- **Symfony Web Profiler** - Development debugging
- **Symfony Maker Bundle** - Code generation

### Deployment
- **Docker** - Containerization
- **Docker Compose** - Multi-container orchestration

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 8.0
- Docker & Docker Compose (optional)

## 🛠️ Installation & Setup

### Local Development

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd assessment_project
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Environment setup**
   ```bash
   cp .env .env.local
   # Edit .env.local with your database credentials
   ```

4. **Database setup**
   ```bash
   # Create database
   php bin/console doctrine:database:create
   
   # Run migrations
   php bin/console doctrine:migrations:migrate
   ```

5. **Generate JWT keys**
   ```bash
   php bin/console lexik:jwt:generate-keypair
   ```

6. **Start development server**
   ```bash
   symfony server:start
   # OR
   php -S localhost:8000 -t public
   ```

### Docker Setup

1. **Start services**
   ```bash
   docker-compose up -d
   ```

2. **Run migrations**
   ```bash
   docker-compose exec app php bin/console doctrine:migrations:migrate
   ```

## 📚 API Documentation

The API documentation is available at:
- **Swagger UI**: `http://localhost:8000/api`
- **API Docs**: `http://localhost:8000/api/docs`

## 🔗 API Endpoints

### 🔐 Authentication
```http
POST   /api/register      # User registration
POST   /api/login         # User login
POST   /api/refresh       # Refresh JWT token
POST   /api/logout        # User logout (Auth required)
POST   /api/logout-all    # Logout from all devices (Auth required)
GET    /api/me            # Get current user profile (Auth required)
```

### 🎥 Video Management
```http
POST   /api/videos/upload           # Upload video file (Auth required)
POST   /api/videos/upload-chunk     # Upload video in chunks (Auth required)
GET    /api/videos                  # Get public videos (paginated)
GET    /api/videos/{id}             # Get specific video
PUT    /api/videos/{id}             # Update video details (Auth required)
DELETE /api/videos/{id}             # Delete video (Auth required)
GET    /api/videos/user/{userId}    # Get user's videos
GET    /api/videos/search           # Search videos
GET    /api/videos/trending         # Get trending videos
```

### 👍 Video Interactions
```http
POST   /api/videos/{id}/like    # Like a video (Auth required)
DELETE /api/videos/{id}/like    # Unlike a video (Auth required)
POST   /api/videos/{id}/view    # Record video view
```

### 👤 User Profile Management
```http
GET    /api/profile/{id}              # Get user profile
PUT    /api/profile                   # Update own profile (Auth required)
POST   /api/profile/{id}/follow       # Follow user (Auth required)
DELETE /api/profile/{id}/unfollow     # Unfollow user (Auth required)
GET    /api/profile/{id}/followers    # Get user followers
GET    /api/profile/{id}/following    # Get user following
GET    /api/profile/search            # Search users
```

### 👥 User Social Features
```http
POST   /api/users/{userId}/follow    # Follow user (Auth required)
DELETE /api/users/{userId}/follow    # Unfollow user (Auth required)
GET    /api/users/{userId}/stats     # Get user statistics
GET    /api/users/search             # Search users
PUT    /api/users/profile            # Update profile (Auth required)
```

### 🔑 Simple Authentication (Alternative)
```http
POST   /simple/login      # Simple login with basic token
GET    /simple/profile    # Get profile with simple auth (Auth required)
```

### 📱 Feed System
```http
GET    /api/feed/vertical              # Get vertical feed
GET    /api/feed/home                  # Get personalized home feed (Auth required)
GET    /api/feed/trending              # Get trending feed
GET    /api/feed/chronological         # Get chronological feed
GET    /api/feed/creator/{userId}      # Get creator channel
GET    /api/feed/discover              # Get discover feed
GET    /api/feed/popular               # Get popular feed
```

### 🔍 Search & Discovery
```http
GET    /api/search/videos                # Search videos with filters
GET    /api/search/hashtags/{hashtag}    # Search by hashtag
GET    /api/search/suggestions           # Get search suggestions
GET    /api/search/hashtags/trending     # Get trending hashtags
GET    /api/search/hashtags/popular      # Get popular hashtags
GET    /api/search/hashtags              # Search hashtags
GET    /api/search/categories            # Browse content categories
```

## 📝 Request/Response Examples

### User Registration
```http
POST /api/register
Content-Type: application/json

{
  "email": "user@example.com",
  "username": "johndoe",
  "firstName": "John",
  "password": "securepassword123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "id": 1,
    "email": "user@example.com",
    "username": "johndoe"
  }
}
```

### User Login
```http
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "securepassword123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "refresh_token": "def50200e8d4f...",
    "user": {
      "id": 1,
      "email": "user@example.com",
      "username": "johndoe",
      "roles": ["ROLE_USER"]
    }
  }
}
```

### Video Upload
```http
POST /api/videos/upload
Authorization: Bearer <jwt_token>
Content-Type: multipart/form-data

video: <video_file>
title: "My Amazing Video"
description: "This is a great video #awesome #content"
```

**Response:**
```json
{
  "success": true,
  "message": "Video uploaded successfully",
  "data": {
    "id": 1,
    "title": "My Amazing Video",
    "status": "processing",
    "filename": "video_123456.mp4"
  }
}
```

### Search Videos
```http
GET /api/search/videos?q=funny&page=1&limit=20&sortBy=relevance
```

**Response:**
```json
{
  "success": true,
  "message": "Search results",
  "data": [
    {
      "id": 1,
      "title": "Funny Cat Video",
      "description": "A hilarious cat compilation",
      "thumbnailPath": "/uploads/thumbnails/thumb_1.jpg",
      "duration": 120,
      "viewsCount": 1500,
      "likesCount": 89,
      "createdAt": "2024-01-15 10:30:00",
      "user": {
        "id": 2,
        "username": "catLover",
        "firstName": "Jane"
      }
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 150,
    "pages": 8
  }
}
```

## 🔧 Configuration

### Environment Variables
```bash
# App Configuration
APP_ENV=dev
APP_SECRET=your_app_secret

# Database Configuration
DATABASE_URL="mysql://root:password@127.0.0.1:3306/assessment_project?serverVersion=8.0&charset=utf8mb4"

# JWT Authentication
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your_passphrase

# CORS Settings
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'

# Mailer (Optional)
MAILER_DSN=null://null

# Messenger (Optional)
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
```

### File Upload Configuration
- **Max file size**: Configurable via PHP settings
- **Supported formats**: MP4, AVI, MOV, WMV
- **Upload directory**: `public/uploads/videos/`
- **Thumbnail directory**: `public/uploads/thumbnails/`

## 🚀 Deployment

### Production Deployment
1. Set `APP_ENV=prod` in environment
2. Configure production database
3. Generate production JWT keys
4. Set up file storage (local or cloud)
5. Configure web server (Nginx/Apache)

### Docker Production
```bash
docker build -t video-platform-api .
docker run -p 8080:8080 video-platform-api
```

## 🧪 Testing

```bash
# Run all tests
php bin/phpunit

# Run specific test suite
php bin/phpunit tests/Controller/
```

## 📊 Features

- ✅ User authentication with JWT
- ✅ Video upload with chunked support
- ✅ Video management (CRUD operations)
- ✅ Social features (follow/unfollow, likes)
- ✅ Advanced search with filters
- ✅ Hashtag system
- ✅ Multiple feed types
- ✅ Pagination support
- ✅ File upload handling
- ✅ Soft delete functionality
- ✅ Performance optimizations
- ✅ API documentation
- ✅ Docker support

## 🔒 Security Features

- JWT-based authentication
- Password hashing with Symfony's password hasher
- CORS protection
- Input validation and sanitization
- SQL injection protection via Doctrine ORM
- Rate limiting ready (can be implemented)

## 📈 Performance Optimizations

- Database indexing on frequently queried columns
- Optimized video repository queries
- Caching service implementation
- Pagination for large datasets
- Efficient feed algorithms

