# API Validation Quick Reference

## 🔐 Registration Validation

### Required Fields
- ✅ email (valid email format)
- ✅ username (3-50 chars, alphanumeric + underscore only)
- ✅ password (minimum 8 characters)
- ✅ firstName (1-255 characters)

### Duplicate Checking
- ✅ Email must be unique
- ✅ Username must be unique

### Error Codes
- `422` - Validation failed (duplicate email/username, invalid format, etc.)
- `400` - Missing required fields or empty request body

---

## 🎥 Video Upload Validation

### File Requirements
- **Allowed Types**: MP4, AVI, MOV, QuickTime
- **Maximum Size**: 500MB
- **Title**: 3-200 characters (required)
- **Description**: 0-5000 characters (optional)

### Error Codes
- `400` - Invalid file type, file too large, missing title, validation failed

---

## 👤 Profile Update Validation

### Field Limits
- **Username**: 3-50 chars, alphanumeric + underscore, must be unique
- **First Name**: 1-255 characters
- **Bio**: 0-500 characters

### Duplicate Checking
- ✅ Username uniqueness checked when updating

### Error Codes
- `422` - Validation failed (duplicate username, invalid format, length exceeded)
- `400` - Empty request body

---

## 🔍 Search Validation

### Query Requirements
- **Minimum Length**: 2 characters
- **Maximum Length**: 200 characters
- **Sanitization**: All queries are sanitized for XSS prevention

---

## 🔑 Authentication Validation

### Login
- ✅ Email format validation
- ✅ Required fields: email, password
- ✅ Input sanitization

### Token Refresh
- ✅ Refresh token required
- ✅ Token format validation (min 10 chars)

---

## 🛡️ Security Features

### Input Sanitization
All text inputs are sanitized using `htmlspecialchars()`:
- Email, username, firstName, bio
- Video titles and descriptions
- Search queries

### XSS Prevention
- ✅ All user inputs sanitized before processing
- ✅ HTML special characters escaped

---

## 📋 Common Error Responses

### Validation Failed (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": ["Specific error message"]
}
```

### Missing Fields (400)
```json
{
  "success": false,
  "message": "Missing required fields",
  "errors": ["Email is required", "Password is required"]
}
```

### Duplicate Resource (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": ["Email already exists"]
}
```

---

## ✅ Testing Checklist

- [ ] Register with duplicate email → Should fail with "Email already exists"
- [ ] Register with duplicate username → Should fail with "Username already taken"
- [ ] Register with weak password (e.g., "123") → Should fail with password length error
- [ ] Upload non-video file → Should fail with file type error
- [ ] Upload video with title < 3 chars → Should fail with title length error
- [ ] Update profile with existing username → Should fail with "Username already taken"
- [ ] Login with invalid email format → Should fail with email format error
- [ ] Send empty request body to any POST endpoint → Should fail with "Request body cannot be empty"

---

## 📁 Modified Files

1. **NEW**: `src/Service/ValidationService.php` - Centralized validation logic
2. **MODIFIED**: `src/Controller/AuthController.php` - Enhanced registration, login, token refresh
3. **MODIFIED**: `src/Controller/VideoController.php` - Enhanced upload, update, search
4. **MODIFIED**: `src/Controller/UserProfileController.php` - Enhanced profile update, user search

All changes maintain backward compatibility with existing API consumers.
