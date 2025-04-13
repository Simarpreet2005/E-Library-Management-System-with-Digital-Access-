# E-Library Management System

A modern, elegant library management system built with PHP and MySQL, featuring a dark academia theme.

![Library System Preview](https://images.unsplash.com/photo-1481627834876-b7833e8f5570?q=80&w=228&auto=format&fit=crop)

## Features

- 📚 **Book Management**
  - Add, edit, and delete books
  - Upload book covers and PDF files
  - Track book quantities and availability
  - Categorize books

- 👥 **User Management**
  - User registration and authentication
  - Role-based access (Admin/User)
  - User profiles and settings

- 🔄 **Borrowing System**
  - Request and borrow books
  - Track borrowing history
  - Return books
  - View due dates

- ⭐ **Additional Features**
  - Book favorites
  - Book reservations
  - Search functionality
  - Responsive design
  - Dark academia theme

## Tech Stack

- **Frontend**
  - HTML5
  - CSS3 (Tailwind CSS)
  - JavaScript
  - Font Awesome Icons
  - Google Fonts (Cormorant Garamond, Inter)

- **Backend**
  - PHP 8.0+
  - MySQL
  - PDO for database operations

## Installation

1. **Prerequisites**
   - PHP 8.0 or higher
   - MySQL 5.7 or higher
   - Web server (Apache/Nginx)
   - Composer (for dependencies)

2. **Setup**
   ```bash
   # Clone the repository
   git clone https://github.com/SurichaSinha/E-Library-Management-System.git

   # Navigate to project directory
   cd E-Library-Management-System

   # Import database
   mysql -u your_username -p your_database_name < database/elibrary.sql

   # Configure database connection
   # Edit config/database.php with your credentials
   ```

3. **Configuration**
   - Update database credentials in `config/database.php`
   - Set up file upload directories with proper permissions
   - Configure your web server to point to the project directory

## Directory Structure

```
E-Library-Management-System/
├── admin/                 # Admin panel files
├── config/               # Configuration files
├── database/            # Database schema and migrations
├── includes/            # Common PHP includes
├── uploads/            # Uploaded files
│   ├── books/         # PDF files
│   └── covers/        # Book cover images
├── assets/            # Static assets
│   ├── css/          # Stylesheets
│   ├── js/           # JavaScript files
│   └── images/       # Static images
├── index.php         # Main entry point
└── README.md         # Project documentation
```

## Usage

1. **Admin Panel**
   - Access admin features at `/admin`
   - Manage books, users, and borrowing requests
   - View system statistics

2. **User Features**
   - Register and login at `/register.php` and `/login.php`
   - Browse and search books
   - Borrow and return books
   - Manage favorites and reservations

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments

- Background images from [Unsplash](https://unsplash.com)
- Icons from [Font Awesome](https://fontawesome.com)
- Fonts from [Google Fonts](https://fonts.google.com)

## Contact

Suricha Sinha - [GitHub](https://github.com/SurichaSinha)

Project Link: [https://github.com/SurichaSinha/E-Library-Management-System](https://github.com/SurichaSinha/E-Library-Management-System) 