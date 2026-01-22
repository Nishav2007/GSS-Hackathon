# 💧 ASTHA - MELAMCHI WATER ALERT SYSTEM

A comprehensive web application that provides real-time water flow monitoring and instant notifications for 42 locations across Nepal. Track when Melamchi water arrives in your specific area with live dashboard updates.

## 🌟 Features

- **Real-Time Monitoring**: Live water status updates with automatic page refresh (30s for dashboard, 60s for history)
- **42 Locations**: Coverage across Kathmandu, Lalitpur, Bhaktapur, and other major cities
- **User Dashboard**: Personalized dashboard showing water status for your location
- **History Tracking**: Complete water event history with date range filtering
- **Admin Panel**: Control water flow status for all locations with one-click toggle
- **User Management**: View all registered users and their statistics
- **Beautiful UI**: Modern teal-themed design with CSS animations
- **Responsive Design**: Works seamlessly on mobile, tablet, and desktop

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Server**: Apache 2.4+ (XAMPP recommended)

## 📋 Prerequisites

- XAMPP (or LAMP/WAMP) installed
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser (Chrome, Firefox, Edge, Safari)

## 🚀 Installation

### Step 1: Place Files

Extract or clone the project files to your web server directory:

```
C:\xampp\htdocs\Astha\
```

### Step 2: Start XAMPP Services

1. Open XAMPP Control Panel
2. Start **Apache** service
3. Start **MySQL** service

### Step 3: Create Database

1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Click on **"Import"** tab
3. Click **"Choose File"** and select `setup_database.sql`
4. Click **"Go"** to execute the SQL script
5. Verify that database `Astha` is created with 4 tables and 42 locations

### Step 4: Configure (Optional)

If your database credentials are different from default, edit `config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'Astha');
```

### Step 5: Access the Application

Open your web browser and navigate to:

```
http://localhost/Astha/
```

## 👤 Default Credentials

### Admin Login
- **Username**: `admin`
- **Password**: `admin123`
- **URL**: `http://localhost/Astha/admin-login.php`

### Test User (Pre-loaded)
- **Email**: `test@example.com`
- **Password**: `test123`
- **Location**: Chabahil

## 📁 Project Structure

```
Astha/
├── index.php                    # Homepage (landing page)
├── register.php                 # User registration
├── login.php                    # User login
├── dashboard.php                # User dashboard (protected)
├── history.php                  # Water history (protected)
├── billing.php                  # Billing & usage history (protected)
├── topup.php                    # Khalti topup page (protected)
├── khalti-initiate.php          # Khalti payment initiate API
├── khalti-callback.php          # Khalti payment verification callback
├── payment-success.php          # Payment success page
├── payment-failed.php           # Payment failed page
├── admin-login.php              # Admin login
├── admin-panel.php              # Admin control panel (protected)
├── logout.php                   # Logout handler
├── config.php                   # Database config + helpers
├── Astha-theme.css              # Main stylesheet
├── setup_database.sql           # Database setup script
├── database_update.sql          # Migration script (existing DB)
└── README.md                    # This file
```

## 🗄️ Database Schema

### Tables

1. **locations** (42 records)
   - Stores all 42 locations with water status
   - Fields: id, location_name, district, zone, water_status, status_updated_at

2. **users**
   - User accounts with location assignment
   - Fields: id, name, email, password (bcrypt), location_id

3. **admins**
   - Admin accounts (not used - login hardcoded)
   - Fields: id, username, password

4. **water_events**
   - History of water arrivals
   - Fields: id, location_id, arrival_date, arrival_time, admin_id, created_at

## 🎨 Design Features

- **Teal Color Theme**: Modern gradient-based design
- **CSS Icons**: Pure CSS icons (no images/fonts)
- **Animations**: Floating water drops, pulsing badges, wave effects
- **Responsive**: Mobile-first approach with breakpoints
- **Auto-Refresh**: Live updates without manual refresh

## 🔐 Security Features

- **SQL Injection Prevention**: Prepared statements throughout
- **Password Security**: Bcrypt hashing
- **XSS Prevention**: HTML escaping on all outputs
- **Session Security**: Regenerated session IDs on login
- **Input Validation**: Server-side validation for all inputs
- **Idempotent Payments**: Khalti callback updates pending only

## 💰 Wallet & Billing Features

- Wallet balance per user (in paisa)
- Billing rate: **1000 liters = Rs 32**
- Admin enters liters per user, system deducts in 1000L blocks
- Full billing history (topups + deductions + water usage)
- Auto suspension when balance drops below **-Rs 1000**
- Wallet warning emails at thresholds (0, -900, suspension)

## 🔔 Email Notifications

- Water flow email to all users in a location when admin turns flow **ON**
- Wallet warnings sent via Hostinger SMTP
- Sender: **Astha Water Alerts**

## 📊 User Features

### Dashboard
- Live water status display (FLOWING / AVAILABLE / NO WATER)
- Statistics: Total events, This month, This week
- Recent events table (last 5)
- Auto-refresh every 30 seconds

### History
- Complete water event history
- Date range filtering
- CSV export functionality
- Auto-refresh every 60 seconds

## 🔧 Admin Features

### Water Flow Control
- View all 42 locations with current status
- One-click toggle to turn water ON/OFF
- Search locations by name
- See user count and events per location
- Automatic water event creation when turning ON

### User Management
- View all registered users
- Search users by name, email, or location
- See user statistics (events received, registration date)

## 🧪 Testing Checklist

- [x] Register new user
- [x] Login with correct credentials
- [x] View dashboard (water status displays)
- [x] Auto-refresh working (30s)
- [x] View history page
- [x] Admin login (admin/admin123)
- [x] Toggle water ON/OFF
- [x] User dashboard updates after admin change
- [x] Search locations/users
- [x] Logout functionality

## 🐛 Troubleshooting

### Database Connection Failed
- Check MySQL is running in XAMPP
- Verify credentials in `config.php`
- Ensure database `Astha` exists

### Page Not Auto-Refreshing
- Check meta refresh tag exists
- Disable browser cache (Ctrl+Shift+R)
- Check browser console for errors

### Admin Login Not Working
- Ensure exact credentials: `admin` / `admin123` (case-sensitive)
- Check session is started in `config.php`
- Clear browser cookies

### Water Status Not Updating
- Verify `water_status` column exists in locations table
- Check admin form POST is reaching backend
- Inspect database: `SELECT water_status FROM locations`

### CSS Not Loading
- Verify `Astha-theme.css` path is correct
- Clear browser cache
- Check file permissions

## 🔧 Environment Variables

Set these in your server environment (or Apache `SetEnv`):

```
APP_BASE_URL=http://localhost/Astha
TOPUP_URL=https://nishavmansinghpradhan.com/Astha

KHALTI_ENV=sandbox
KHALTI_SECRET_KEY=your_khalti_secret_key

SMTP_HOST=smtp.hostinger.com
SMTP_PORT=587
SMTP_USER=gooddream@nishavmansinghpradhan.com
SMTP_PASS=your_smtp_password
SMTP_FROM_EMAIL=gooddream@nishavmansinghpradhan.com
SMTP_FROM_NAME=Astha Water Alerts
```

## 📈 Future Enhancements

- Email notifications (PHPMailer + SMTP)
- SMS alerts (Twilio API)
- Push notifications (Firebase)
- Real-time WebSocket updates
- Mobile app (React Native)
- Data analytics charts
- Multi-language support (Nepali, English)
- Predictive analytics (ML)

## 📝 License

This project is created for educational and community purposes.

## 👥 Credits

Built with 💧 for the Kathmandu community

## 📞 Support

For issues or questions, please check the troubleshooting section or review the code comments.

---

**Version**: 1.0.0  
**Last Updated**: 2024  
**Status**: Production Ready
