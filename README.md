# Ridesphere - Vehicle Rental Platform

A full-featured vehicle rental management system with OTP email verification, built with PHP, MySQL, and vanilla JavaScript.

## Quick Start

### Prerequisites
- XAMPP (Apache + MySQL) running
- Composer (installed globally or locally)
- Valid Gmail account with app password

### Initial Setup

1. **Initialize Database**
   - Visit: `http://localhost/ridesphere/setup_database.php`
   - This creates all required tables and schema

2. **Verify Installation**
   - All components should be automatically ready after setup
   - Database, tables, and PHP dependencies are configured

### System Features

- **User Authentication**
  - Signup with email OTP verification
  - Login with email/password
  - Bcrypt password hashing with MD5 legacy support

- **OTP Email Verification**
  - 6-digit OTP sent to user's email via Gmail SMTP
  - 10-minute expiration window
  - 60-second resend cooldown
  - Professional modal UI with digit-by-digit input

- **Vehicle Management**
  - Create/list/update vehicles with image upload
  - Track vehicle availability and booking status
  - Owner dashboard for vehicle management

- **Booking System**
  - Renters can browse and book vehicles
  - Owners can confirm/complete bookings
  - Automatic vehicle status management

- **Messaging**
  - Direct messaging between owners and renters

### File Structure

```
ridesphere/
├── index.php                  # Single-page frontend
├── script.js                  # Frontend logic (OTP, auth, booking flows)
├── styles.css                 # Responsive UI styling
│
├── auth.php                   # Authentication API (signup, login, OTP)
├── vehicles.php               # Vehicle management API
├── bookings.php               # Booking management API
├── messages.php               # Messaging API
├── db.php                     # PDO database connector
│
├── sendEmailViaPHPMailer.php  # Gmail SMTP email delivery
├── create_tables.php          # Database schema definition
├── setup_database.php         # Database initialization
│
├── migrations/
│   └── add_image_column.php   # Image column migration
│
├── uploads/                   # Vehicle image storage
├── vendor/                    # Composer dependencies (PHPMailer)
└── composer.json              # Project dependencies
```

### Database Schema

**users table**
- id, email, password, name, phone, role (owner/renter)
- email_verified (OTP verified status)
- created_at, updated_at

**vehicles table**
- id, owner_id, name, description, rate, status
- image_filename, image_path
- created_at, updated_at

**bookings table**
- id, vehicle_id, renter_id, start_date, end_date
- status (pending/confirmed/completed/cancelled)
- total_amount, created_at, updated_at

**messages table**
- id, sender_id, receiver_id, message, created_at

**otp_pending_emails table** (auto-created)
- email, otp_code, expires_at

### API Endpoints

All endpoints use POST with JSON body and return JSON responses.

**Authentication** (`auth.php`)
- `signup` - Register new user
- `login` - User login
- `send_otp_to_email` - Send OTP to email
- `verify_otp_for_email` - Verify OTP code

**Vehicles** (`vehicles.php`)
- `get_vehicles` - List all available vehicles
- `get_owner_vehicles` - Owner's vehicle list
- `add_vehicle` - Create new vehicle (legacy)
- `add_vehicle_with_image` - Create vehicle with image
- `update_vehicle` - Update vehicle details

**Bookings** (`bookings.php`)
- `create_booking` - Create new booking
- `get_bookings` - List bookings
- `get_owner_bookings` - Owner's booking list
- `update_booking_status` - Update booking status

**Messages** (`messages.php`)
- `send_message` - Send message between users
- `get_messages` - Fetch conversation messages

### OTP Email Configuration

The system uses Gmail SMTP via PHPMailer:
- **From Email:** ridesphererentcar@gmail.com
- **SMTP Server:** smtp.gmail.com:587 (TLS)
- **App Password:** Configured in `sendEmailViaPHPMailer.php`

For local testing, ensure your mail server is reachable. For production, update credentials in `sendEmailViaPHPMailer.php`.

### Development Workflow

1. **Run XAMPP** - Start Apache and MySQL
2. **Access Application** - `http://localhost/ridesphere/`
3. **Initialize DB** - Visit setup page on first run
4. **Test Signup** - Create account with real email to receive OTP
5. **Test Features** - Login as owner/renter and test workflows

### Troubleshooting

- **Database connection failed** - Ensure MySQL is running in XAMPP
- **Email not received** - Check Gmail settings and app password configuration
- **OTP modal not showing** - Clear browser cache and check JavaScript console for errors
- **Image upload fails** - Verify `uploads/` directory exists and is writable

### Security Notes

- Passwords are hashed with bcrypt
- Legacy MD5 passwords are auto-upgraded to bcrypt on first login
- OTP codes are single-use and expire after 10 minutes
- Session-less authentication using frontend user storage
- HTTPS recommended for production deployment

### License & Credits

Built for educational purposes as a comprehensive vehicle rental platform demo.
