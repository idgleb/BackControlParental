# Control Parental API Documentation

## Overview

The Control Parental API provides endpoints for managing parental control features across mobile devices. The API is divided into three main sections:

1. **Kids App API (v1)** - For the app installed on children's devices
2. **Parent App API** - For the parent's mobile app
3. **Web AJAX API** - For the web dashboard

## Base URLs

- Production: `https://api.controlparental.com`
- Staging: `https://staging-api.controlparental.com`

## Authentication

### Parent App Authentication

The Parent App uses Bearer token authentication via Laravel Sanctum.

```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "parent@example.com",
  "password": "secure_password"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "parent@example.com"
    },
    "token": "1|laravel_sanctum_token_here",
    "expires_at": "2024-01-15T10:30:00.000Z"
  }
}
```

Use the token in subsequent requests:
```http
Authorization: Bearer 1|laravel_sanctum_token_here
```

### Kids App Authentication

The Kids App uses device-based authentication without user credentials.

```http
POST /api/v1/auth/device/register
Content-Type: application/json

{
  "device_id": "unique-device-uuid",
  "model": "Samsung Galaxy A52",
  "android_version": "12"
}
```

## Rate Limiting

- Kids App: 100 requests per minute per device
- Parent App: 200 requests per minute per user
- Web AJAX: 300 requests per minute per session

## Common Response Format

All API responses follow this format:

### Success Response
```json
{
  "success": true,
  "data": {},
  "message": "Operation completed successfully",
  "timestamp": "2024-01-15T10:30:00.000Z"
}
```

### Error Response
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid",
    "details": {
      "email": ["The email field is required"]
    }
  },
  "timestamp": "2024-01-15T10:30:00.000Z"
}
```

## Parent App Endpoints

### Devices

#### Get All Devices
```http
GET /api/parent/devices
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (integer): Page number for pagination
- `per_page` (integer): Items per page (default: 15, max: 100)
- `status` (string): Filter by status (online|offline)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "device_id": "uuid-here",
      "model": "Samsung Galaxy A52",
      "status": {
        "is_online": true,
        "last_seen": "2024-01-15T10:25:00.000Z",
        "minutes_offline": null
      },
      "battery": {
        "level": 85,
        "is_low": false,
        "last_update": "2024-01-15T10:25:00.000Z"
      },
      "location": {
        "latitude": -12.0464,
        "longitude": -77.0428,
        "accuracy": 10,
        "last_update": "2024-01-15T10:20:00.000Z"
      },
      "statistics": {
        "total_apps": 45,
        "blocked_apps": 5,
        "limited_apps": 3,
        "active_schedules": 2
      }
    }
  ],
  "links": {
    "first": "https://api.controlparental.com/api/parent/devices?page=1",
    "last": "https://api.controlparental.com/api/parent/devices?page=5",
    "prev": null,
    "next": "https://api.controlparental.com/api/parent/devices?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 73
  }
}
```

#### Block App
```http
POST /api/parent/devices/{device_id}/apps/{package_name}/block
Authorization: Bearer {token}
Content-Type: application/json

{
  "reason": "Excessive usage",
  "blocked_until": "2024-01-16T10:00:00.000Z",
  "notify_child": true,
  "allow_emergency": false
}
```

### Schedules

#### Create Schedule
```http
POST /api/parent/devices/{device_id}/schedules
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "School Hours",
  "days_of_week": [1, 2, 3, 4, 5],
  "start_time": "08:00",
  "end_time": "14:00",
  "is_active": true,
  "blocked_apps": ["com.whatsapp", "com.instagram.android"]
}
```

### Reports

#### Get Daily Usage
```http
GET /api/parent/devices/{device_id}/reports/usage/today
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": {
    "date": "2024-01-15",
    "total_screen_time_minutes": 245,
    "apps_usage": [
      {
        "package_name": "com.whatsapp",
        "app_name": "WhatsApp",
        "usage_minutes": 65,
        "launch_count": 23,
        "last_used": "2024-01-15T10:15:00.000Z"
      }
    ],
    "hourly_breakdown": {
      "08:00": 15,
      "09:00": 30,
      "10:00": 25
    },
    "blocked_attempts": 5
  }
}
```

## Kids App Endpoints

### Sync

#### Get Apps (Paginated)
```http
GET /api/v1/sync/apps?deviceId={device_id}&limit=50&offset=0
```

#### Post Events
```http
POST /api/v1/sync/events
Content-Type: application/json

{
  "device_id": "uuid-here",
  "events": [
    {
      "entity_type": "app",
      "entity_id": "com.example.app",
      "action": "update",
      "timestamp": "2024-01-15T10:30:00.000Z",
      "data": {
        "usage_time_today": 3600000,
        "last_used": 1705315800000
      }
    }
  ]
}
```

### Heartbeat
```http
POST /api/v1/devices/{device_id}/heartbeat
Content-Type: application/json

{
  "battery_level": 85,
  "latitude": -12.0464,
  "longitude": -77.0428,
  "accuracy": 10
}
```

## Error Codes

| Code | Description |
|------|-------------|
| `AUTH_FAILED` | Authentication failed |
| `DEVICE_NOT_FOUND` | Device does not exist |
| `APP_NOT_FOUND` | App not found on device |
| `VALIDATION_ERROR` | Request validation failed |
| `RATE_LIMIT_EXCEEDED` | Too many requests |
| `SERVER_ERROR` | Internal server error |

## Webhooks

Configure webhooks in your account settings to receive real-time notifications:

```json
POST https://your-webhook-url.com/notifications
Content-Type: application/json
X-Signature: sha256=signature_here

{
  "event": "device.offline",
  "data": {
    "device_id": "uuid-here",
    "went_offline_at": "2024-01-15T10:30:00.000Z"
  },
  "timestamp": "2024-01-15T10:30:05.000Z"
}
```

## SDKs

Official SDKs are available for:
- Android (Kotlin)
- iOS (Swift) - Coming soon
- Flutter - Coming soon

## Support

For API support, contact: api-support@controlparental.com 