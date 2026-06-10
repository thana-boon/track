# 📌 Track

A web-based student activity tracking system for Thai schools — manages activity groups, student enrollment, attendance recording, and result reporting across multiple academic years.

> Built with PHP and deployed on an internal school server at Sukhon School.

---

## ✨ Features

### 👨‍💼 Admin
- Manage users, students, academic years, and class advisors
- Create and manage **track subjects** and **track groups**
- Register students into activity tracks
- View activity logs
- Backup and restore database

### 👨‍🏫 Teacher
- Manage assigned classrooms and classroom students
- Record, view, edit, and delete **class attendance**
- Print attendance sheets
- Export classroom data

### 👨‍🎓 Student
- View personal activity tracking results (`student_results`)

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP (strict types) |
| Database | MySQL |
| Server | Apache (XAMPP) |
| Extras | Activity logging, backup/restore system |

---

## 📁 Project Structure

```
track/
├── app/
│   ├── bootstrap.php          # App initialization, auth helpers
│   └── routes/                # One file per route
│       ├── dashboard.php
│       ├── login.php / logout.php
│       ├── students.php
│       ├── track_subjects.php
│       ├── track_groups.php
│       ├── register_track.php
│       ├── student_manage.php
│       ├── student_results.php
│       ├── class_room.php
│       ├── class_attendance.php
│       ├── class_attendance_view.php
│       ├── class_attendance_print.php
│       ├── report_statement.php
│       ├── academic_year.php
│       ├── activity_logs.php
│       └── backup_restore.php
├── uploads/                   # Uploaded files
├── _rep.py                    # Utility/report script (Python)
├── .htaccess                  # URL routing
└── index.php                  # Front controller / router
```

---

## 🔐 Role-Based Access

| Role | Access |
|------|--------|
| **Admin** | Full access to all routes |
| **Teacher** | Classroom and attendance management only |
| **Student** | View own activity results only |

---

## 🚀 Getting Started

### Requirements

- PHP 7.4+
- MySQL 5.7+
- Apache with `mod_rewrite` enabled (or XAMPP)

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/thana-boon/track.git
   ```

2. Place the folder in your Apache web root (e.g., `htdocs/track`)

3. Import the database schema and configure the connection in `app/bootstrap.php`

4. Access the app via `http://localhost/track`

---

## 📄 License

This project is for educational and internal school use.

---

## 👤 Author

**thana-boon** — Teacher & Developer at Sukhon School  
GitHub: [@thana-boon](https://github.com/thana-boon)
