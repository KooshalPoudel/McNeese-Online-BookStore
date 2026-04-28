# McNeese Online Bookstore

A web-based e-commerce platform built for McNeese State University students to shop for textbooks and academic supplies online. Students can register, search the catalog by course code or keyword, manage a shopping cart, and complete checkout — all from desktop or mobile.

## Features

- User registration and login with session timeout
- Search by course code, ISBN, title, or author
- Categorized inventory (textbooks and office supplies)
- Shopping cart and checkout flow
- User profile management with profile pictures
- Mobile-responsive design
- Admin and student roles

## Tech Stack

- **Backend:** PHP 8+ with MySQLi
- **Database:** MySQL / MariaDB
- **Frontend:** Vanilla HTML, CSS, and JavaScript
- **Server:** Apache (XAMPP / WAMP / MAMP)

## Project Structure

```
mcneese_bookstore/
├── index.php              # Homepage with featured books
├── logout.php             # Logout handler
├── database.sql           # Database schema
├── css/
│   └── style.css          # Global styles
├── js/
│   └── main.js            # Client-side scripts
├── includes/
│   ├── config.php         # DB connection & session (gitignored)
│   ├── config.example.php # Template — copy to config.php
│   ├── header.php         # Shared header
│   ├── footer.php         # Shared footer
│   └── admin_guard.php    # Admin route protection
└── pages/
    ├── login.php          # Login page
    ├── register.php       # Registration page
    ├── books.php          # Catalog browse page
    ├── search.php         # Search results
    ├── cart.php           # Shopping cart
    ├── checkout.php       # Checkout flow
    ├── profile.php        # User profile
    ├── orders.php         # Order history
    ├── order_details.php  # Single order view
    └── Admin/
        ├── orders.php         # Admin orders list
        └── order_details.php  # Admin order detail
```

## Getting Started

### Prerequisites

- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB
- Apache (XAMPP, WAMP, MAMP, or LAMP)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/KooshalPoudel/McNeese-Online-BookStore.git
   cd McNeese-Online-BookStore
   ```

2. **Move it into your web root**

   Place the project folder inside your server's document root (e.g., `C:\xampp\htdocs\` for XAMPP).

3. **Set up the database**

   Open phpMyAdmin (or the MySQL CLI) and run the SQL in `database.sql`. This creates the `mcneese_bookstore` database along with the `users`, `books`, `cart`, `orders`, and `order_items` tables.

4. **Configure the project**
   ```bash
   cp includes/config.example.php includes/config.php
   ```
   Then edit `includes/config.php` and set your DB credentials and `SITE_URL`.

5. **Start the server**

   Open your browser and go to:
   ```
   http://localhost/mcneese_bookstore
   ```

## Configuration

All site settings live in `includes/config.php`:

| Constant | Description |
|---|---|
| `DB_HOST` | Database host (usually `localhost`) |
| `DB_USER` | MySQL username |
| `DB_PASS` | MySQL password |
| `DB_NAME` | Database name (`mcneese_bookstore`) |
| `SITE_URL` | Base URL of the site |
| `SESSION_TIMEOUT` | Session expiry in seconds |

## Security Notes

- `includes/config.php` is gitignored — never commit real credentials.
- Passwords are stored hashed.
- All user input is sanitized via the `sanitize()` helper.
- Session timeout is enforced on every page load.
- Admin pages are protected via `admin_guard.php`.

## Roadmap

- [ ] Email notifications on checkout
- [ ] Payment gateway integration
- [ ] Wishlist feature
- [ ] Book reviews and ratings

## License

This project is for educational purposes at McNeese State University.
