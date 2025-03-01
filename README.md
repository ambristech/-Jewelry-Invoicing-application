Jewelry Shop Invoice System



![Free -Jewelry-billing-software-india-GST](https://github.com/user-attachments/assets/c84c10f6-0a2f-4e89-8503-c0e5c72d3f5b)

The Jewelry Shop Invoice System is a web-based application designed to manage inventory, track stock, and generate invoices for a jewelry retail business. Built with PHP and MySQL, it offers a responsive interface for managing categories, items, stock receipts, sales, and reports, accessible on both desktop and mobile devices.

Features
User Authentication: Secure login for admin users.
Category Management: Add, edit, and delete hierarchical categories.
Item Management: Add and delete items with unique names and price tracking.
Stock Management: Receive items with auto-generated invoice numbers and track stock levels.
Sales Processing: Sell items with stock validation and invoice generation.
Invoice Search: Search and view invoices with PDF download option.
Stock Reporting: Detailed reports with filters for category, date range, and stock status.
Responsive Design: Works seamlessly on desktop and mobile devices.


Requirements
Server: Apache or similar web server
PHP: Version 7.4 or higher
MySQL: Version 5.7 or higher
Libraries:
FPDF for PDF generation (download and place in project folder)
jQuery 3.6.0 (included via CDN)
jQuery UI 1.12.1 (included via CDN for datepicker)
Bootstrap 4.6.2 (included via CDN)


Installation

1. Clone or Download
Clone the repository or download the ZIP file:
git clone https://github.com/ambristech/-Jewelry-Invoicing-application
Place the files in your web server directory (e.g., htdocs for XAMPP).


2. Set Up the Database
Create the database and tables:
Open your MySQL client (e.g., phpMyAdmin, MySQL Workbench, or command line).
Run the SQL script:
mysql -u root -p < jewelry_shop.sql
Enter your MySQL password when prompted.
The script creates the jewelry_shop database with sample data.

Verify:
USE jewelry_shop;
SHOW TABLES;
3. Configure Database Connection
Edit db_connect.php with your MySQL credentials:
$host = "localhost";
$dbname = "jewelry_shop";
$username = "root"; // Your MySQL username
$password = "";     // Your MySQL password


4. Install FPDF Library
Download FPDF from http://www.fpdf.org/.
Extract and place the fpdf folder in your project directory (e.g., jewelry-shop-invoice-system/fpdf).
Ensure download_invoice.php points to the correct path:
require('fpdf/fpdf.php');

5. Start the Server
Start Apache and MySQL (e.g., via XAMPP control panel).
Access the application at http://localhost/jewelry-shop-invoice-system/login.php.


Usage
Default Credentials
Username: admin
Password: admin123
Action: Change the password after first login via "Change Password" in the sidebar.


Key Pages
Login: login.php - Authenticate to access the system.
Dashboard: dashboard.php - Overview with menu and summary stats.
Category List: category_list.php - Manage categories.
Item List: item_list.php - Manage items.
Receive Items: receive_items.php - Add stock with date selection.
Sell Items: sell_items.php - Process sales with stock validation.
Search Invoice: search_invoice.php - Find and view invoices.
Stock Report: stock_report.php - View filtered stock reports.
Change Password: change_password.php - Update admin password.


Example Workflow
Login: Use admin/admin123.
Add Category: Go to "Category List", add "Bracelets" under "Jewelry".
Add Item: Go to "Item List", add "Pearl Bracelet" (Price: $100) in "Bracelets".
Receive Stock: Go to "Receive Items", select "Pearl Bracelet", quantity 10, date "25-02-2025".
Sell Item: Go to "Sell Items", sell 2 "Pearl Bracelets" to "Jane Smith" on "27-02-2025".
View Invoice: Search "INV170..." in "Search Invoice", view and download PDF.
Check Stock: Go to "Stock Report", filter by "Bracelets" and date range "25-02-2025 to 27-02-2025".

File Structure

jewelry-shop-invoice-system/
├── db_connect.php          # Database connection
├── login.php              # Login page
├── logout.php             # Logout script
├── dashboard.php          # Main dashboard
├── category_list.php      # Category management
├── edit_category.php      # Edit category page
├── category_items.php     # Items in a category
├── item_list.php          # Item management
├── receive_items.php      # Receive stock
├── sell_items.php         # Sell items
├── search_invoice.php     # Invoice search
├── stock_report.php       # Stock report
├── change_password.php    # Password update
├── item_invoices.php      # Invoices for an item
├── invoice.php            # Invoice details
├── download_invoice.php   # PDF generation
├── jewelry_shop.sql       # Database schema and sample data
└── README.md              # This file

Troubleshooting
"Invalid username or password":
Verify admins table has admin with hash $2y$10$Q8X8X8X8X8X8X8X8X8X8Xu123456789012345678901234567890.
Check db_connect.php credentials.

Database Errors:
Ensure MySQL is running and the jewelry_shop database is created.
Run jewelry_shop.sql if tables are missing.

PDF Download Fails:
Confirm FPDF is installed and path in download_invoice.php is correct.

Datepicker Not Working:
Check jQuery UI CDN links or local files are included.

Development Notes
PHP: Uses PDO for secure database interactions.
Frontend: Bootstrap 4.6.2 for styling, jQuery UI for datepicker.
Security: Inputs are sanitized with filter_input, passwords hashed with password_hash.
Responsive: Mobile-friendly with sidebar collapse at 768px.

Contributing
Fork the repository, make changes, and submit a pull request.
Report issues or suggest features via GitHub Issues.

License
This project is licensed under the MIT License - see the LICENSE file for details (create if needed).

Contact
For support or queries, contact:

Email: ambristech@gmail.com
GitHub: ambristech
