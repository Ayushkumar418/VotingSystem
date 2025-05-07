# 🗳️ Online Voting System

A secure and modern online voting app built using **PHP** and **MySQL**, running on **XAMPP**. It allows users to vote online with real-time results. Admins can easily manage elections, candidates, and voters.

---

## ⚙️ Requirements

Before you start, make sure you’ve got:

* ✅ XAMPP (PHP 7.4+ & MySQL)
* ✅ Any modern web browser
* ✅ Git (optional, for cloning)

---

## 🚀 Setup Guide (Localhost using XAMPP)

### 1️⃣ Clone the Project

```bash
git clone https://github.com/Ayushkumar418/VotingSystem.git
cd VotingSystem
```

---

### 2️⃣ Move It to XAMPP's `htdocs` Folder

```bash
# For Windows users
xcopy /E /I VotingSystem C:\xampp\htdocs\VotingSystem
```

---

### 3️⃣ Set Up Database Config File

#### 🔹 For Git Bash / Linux / macOS:

```bash
cp config/database.example.php config/database.php
```

#### 🔸 For Windows CMD:

```cmd
copy config\database.example.php config\database.php
```

Then edit `config/database.php` with your MySQL DB details:

```php
$host = 'localhost';
$dbname = 'voting_system';
$username = 'root';
$password = ''; // Default is blank in XAMPP
```

---

### 4️⃣ Set Up the Database

1. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Create a new database: `voting_system`
3. Import the following SQL files:

   * `sql/setup.sql`
   * `sql/update.sql` (updates, if any)

---

### 5️⃣ Run the Project

* **Main Site:**
  👉 [http://localhost/VotingSystem](http://localhost/VotingSystem)

* **Admin Panel:**
  👉 [http://localhost/VotingSystem/admin](http://localhost/VotingSystem/admin)

#### 🧑‍💼 Default Admin Login:

```text
Email:    admin@system.com  
Password: admin123
```

---

### 6️⃣ Verify Installation

To check if everything is set up correctly:

1. Visit [http://localhost/VotingSystem/install.php](http://localhost/VotingSystem/install.php)
2. This will show you:
   * ✅ PHP Version compatibility
   * ✅ MySQL connection status
   * ✅ Required directory permissions
   * ✅ Required PHP extensions

If you see any ❌ errors, fix them before using the system.

---

## 🔥 Key Features

* 👥 Voter Registration & Login
* 🗳️ Secure One-Time Voting
* 📈 Real-Time Result Display
* 💠 Admin Panel (Manage Elections, Candidates, Voters)
* 📄 Export Results (Excel)
* 📜 Activity Logs

---

## 🛡️ Security Features

* Password Hashing
* Session Management
* Input Validation
* XSS Protection
* CSRF Tokens

---

## 📁 Folder Structure

```text
VotingSystem/
├── admin/           # Admin dashboard
├── config/          # DB config files
├── includes/        # Reusable PHP functions
├── sql/             # DB setup scripts
├── uploads/         # Candidate images
└── vendor/          # Third-party dependencies
```

---

## 🤝 Wanna Contribute?

Cool! Here's how:

1. 🍝 Fork this repo
2. 🛠️ Create a new branch
3. ✨ Add your changes
4. 🚀 Push the branch
5. 📬 Open a pull request

---

Made with ❤️ by [**Ayush Kumar**](https://github.com/Ayushkumar418)
