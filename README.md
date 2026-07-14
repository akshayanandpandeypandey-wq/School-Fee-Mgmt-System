# Greenwood School Fee Core (Fee Management System)

A state-of-the-art, secure, and student-centric **School Fee Management & Auditing System** built with **PHP**, **MySQL (MariaDB)**, **CSS Variables**, and **JavaScript**.

This platform features a professional responsive layout, light/dark theme manager, student directories, class-based automated fee schedules, dashboard visualizations, printable fee receipts, and detailed financial reports.

---

## 📋 Features

- 🔐 **Secure Authentication** - Multi-user roles (Admin, Cashier) with bcrypt-encrypted password verification.
- 👨‍🎓 **Student Directory** - Comprehensive database logs tracking scholar details and guardian profiles.
- 📋 **Automated Billing Allocations** - Computes outstanding dues dynamically based on class-level fee structures.
- 💵 **Fee Collections Ledger** - Interactive logs supporting CSV downloads, quick searches, and filter configurations.
- 📄 **Auditor-Ready Invoicing** - Clean, professional invoice templates optimized for screen display and printer output.
- 📊 **Dynamic Visualizations** - Real-time statistics summaries and line/doughnut reports using **Chart.js**.
- ⚙️ **System Configurations** - Editable institution profile cards, local currency symbols, and staff user registration.
- 🌓 **Ambient Dark / Light Toggle** - Glassmorphic theme state manager saved in local storage.

---

## 🗂️ Upgraded Project Architecture

```
SchoolFeeManagement/
│
├── index.php                 # Interactive Analytics Dashboard
├── login.php                 # Secure Sign-In Portal
├── logout.php                # Session Termination Handler
│
├── db_connection.php         # DB connection & Session Auth Helpers
├── db_init.php               # Database Initializer & Automated Migration Wizard
├── database_update.sql       # Upgraded Relational Schema Definitions
│
├── students.php              # Enrolled Student Accounts List
├── student_profile.php       # Student Billing Ledger & Profile view
├── add_student.php           # Register new student profile
├── edit_student.php          # Update student profile credentials
├── delete_student.php        # Cascade-safe removal of student account
│
├── fee_structures.php        # Class-wise standard fee configuration rate schedules
│
├── view_payments.php        # Transaction log ledger list
├── add_payment.php           # Log dynamic fee collection payment
├── edit_payment.php          # Adjust transaction billing details
├── delete_payment.php        # Transaction audit removal
├── receipt.php               # Professional invoice preview & printable receipt
│
├── reports.php               # Class-wise audits & Monthly category earnings reports
├── settings.php              # Config settings & Staff account manager
│
├── header.php                # Shared global HTML header layouts
├── sidebar.php               # Shared sidebar navigation layouts
├── footer.php                # Shared script inclusions & structural HTML footers
│
├── css/
│   └── style.css             # Main stylesheet (glassmorphic layout variables)
│
├── js/
│   └── script.js             # Theme controllers, alert dimmers, and CSV exporters
│
└── schoolfee.sql             # Deprecated legacy SQL database schema
```

---

## 🚀 Installation & Setup

### Prerequisites
- **XAMPP** (or equivalent Apache server + MySQL/MariaDB database)
- **PHP** 7.4+
- **Web Browser** (Chrome, Firefox, Safari, Edge)

---

### Step 1: Clone or Place files in Webroot
Place the `SchoolFeeManagement` folder inside your server's webroot directory.
For XAMPP, this is:
```
C:\xampp\htdocs\SchoolFeeManagement
```

---

### Step 2: Start Services & Open Database Setup Wizard
1. Open the **XAMPP Control Panel**.
2. Click **Start** for both **Apache** and **MySQL**.
3. Open your browser and navigate to:
   ```
   http://localhost/SchoolFeeManagement/db_init.php
   ```
4. The wizard will automatically:
   - Check/Create database `school_fee_management`.
   - Setup upgraded schema tables (`users`, `students`, `fee_structures`, `fee_payments`, `school_settings`).
   - **Perform Automated Data Migration:** If you have data in an old `fee_payments` table, it will extract unique students, register them in the student directory, mapping existing logs safely with generated receipt numbers.
   - Seed default settings, standard class structures, and staff logins.

---

### Step 3: Access the Application
Once the setup wizard completes, you will be redirected to the secure login portal:
```
http://localhost/SchoolFeeManagement/login.php
```

#### Default Credentials:
- **Administrator Portal:**
  - Username: `admin`
  - Password: `admin123`
- **Cashier Portal:**
  - Username: `cashier`
  - Password: `cashier123`

*Tip: Please update your password immediately after logging in using the **System Settings** page.*

---

## 🔒 Security Measures

- **SQL Injection Prevention** - Prepared statements used for database record inputs.
- **Cross-Site Scripting (XSS) Shield** - Sanitized input readings and escaped variables outputs.
- **Secure Password Hashing** - Credentials validated using PHP standard `password_hash()` and `password_verify()`.
- **Private Route Protection** - Checked sessions authentication checking functions before rendering restricted panels.

---

## 👨‍💻 Support & Audit logs
- Ensure Apache and MySQL are running in the control panel.
- To reset or re-install clean database sample entries, re-run `db_init.php` at any time.
- Verify browser console outputs (F12) for front-end events.
