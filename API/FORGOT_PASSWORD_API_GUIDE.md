# Forgot Password API Guide

## Overview

This API handles the complete password reset flow using OTP verification sent via Gmail.

## Fixed Issues

✅ Corrected PHPMailer path from `../PHPMailer/src/` to `../PHPMailer-master/src/`
✅ Fixed OTP field mapping from `otp` to `reset_token`
✅ Added proper error handling for missing PHPMailer files
✅ Improved database field consistency

## API Endpoints

### 1. Request OTP (Send Password Reset Email)

**Endpoint:** `POST /API/forgot-password.php`

**Request Body (JSON):**

```json
{
  "email": "user@example.com",
  "action": "send_otp"
}
```

**Response (Success):**

```json
{
  "status": "success",
  "message": "OTP sent to your email",
  "email": "user@example.com"
}
```

**Response (Error):**

```json
{
  "status": "error",
  "message": "User not found"
}
```

---

### 2. Verify OTP

**Endpoint:** `POST /API/forgot-password.php`

**Request Body (JSON):**

```json
{
  "email": "user@example.com",
  "otp": "123456",
  "action": "verify_otp"
}
```

**Response (Success):**

```json
{
  "status": "success",
  "message": "OTP verified successfully",
  "temporary_password": "a1b2c3d4e5f6",
  "note": "Please use this temporary password to login and update your password"
}
```

**Response (Error):**

```json
{
  "status": "error",
  "message": "Invalid OTP"
}
```

or

```json
{
  "status": "error",
  "message": "OTP has expired"
}
```

---

### 3. Update Password (Set New Password)

**Endpoint:** `POST /API/forgot-password.php`

**Request Body (JSON):**

```json
{
  "email": "user@example.com",
  "temporary_password": "a1b2c3d4e5f6",
  "new_password": "NewPassword123",
  "confirm_password": "NewPassword123",
  "action": "update_password"
}
```

**Response (Success):**

```json
{
  "status": "success",
  "message": "Password updated successfully. Please login with your new password."
}
```

**Response (Error):**

```json
{
  "status": "error",
  "message": "Passwords do not match"
}
```

---

## Testing with Postman

### Step 1: Request OTP

1. Open Postman
2. Create a new **POST** request
3. URL: `http://localhost/HR-EMPLOYEE-MANAGEMENT/API/forgot-password.php`
4. Go to **Body** → Select **raw** → Select **JSON**
5. Paste:

```json
{
  "email": "user@example.com",
  "action": "send_otp"
}
```

6. Click **Send**
7. Check the user's email for the OTP

### Step 2: Verify OTP

1. Create another **POST** request
2. Same URL
3. Paste (replace OTP with actual value from email):

```json
{
  "email": "user@example.com",
  "otp": "123456",
  "action": "verify_otp"
}
```

4. Click **Send**
5. Copy the `temporary_password` from the response

### Step 3: Update Password

1. Create another **POST** request
2. Same URL
3. Paste:

```json
{
  "email": "user@example.com",
  "temporary_password": "a1b2c3d4e5f6",
  "new_password": "MyNewPassword123",
  "confirm_password": "MyNewPassword123",
  "action": "update_password"
}
```

4. Click **Send**
5. User can now login with the new password

---

## Database Requirements

The `user` table should have these columns (already exist in your DB):

- `email` - User email address
- `password` - Hashed password
- `reset_token` - Stores the OTP
- `token_expiry` - OTP expiration timestamp
- `reset_required` - Flag to indicate password reset needed (0 or 1)

---

## Email Configuration

Verify your `mailer-config.php` has correct Gmail credentials:

```php
return [
    'host' => 'smtp.gmail.com',
    'username' => 'your-email@gmail.com',
    'password' => 'your-app-password', // Use Gmail App Password
    'port' => 587,
    'encryption' => 'tls',
    'from_email' => 'your-email@gmail.com',
    'from_name' => 'Employee Management System',
    'reply_to' => 'your-email@gmail.com'
];
```

**Note:** For Gmail, use an [App Password](https://myaccount.google.com/apppasswords) instead of your regular password.

---

## Error Handling

| Error               | Cause                | Solution                                    |
| ------------------- | -------------------- | ------------------------------------------- |
| PHPMailer not found | Incorrect path       | Verify `PHPMailer-master/` folder exists    |
| User not found      | Email doesn't exist  | Use valid email address                     |
| Invalid OTP         | Wrong code           | Check email for correct OTP                 |
| OTP has expired     | More than 15 minutes | Request new OTP                             |
| Connection failed   | Database error       | Verify database credentials in `Config.php` |

---

## Flow Diagram

```
User Request OTP
    ↓
API sends OTP via email (valid for 15 minutes)
    ↓
User receives OTP in email
    ↓
User submits OTP
    ↓
API verifies OTP & generates temporary password
    ↓
User receives temporary password
    ↓
User logs in with temporary password
    ↓
User submits new password
    ↓
API updates password with new one
    ↓
User logs in with new password ✓
```
