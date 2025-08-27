# KDTech Solutions - Backend System

A comprehensive PHP backend system for the KDTech Solutions website, providing order management, portfolio administration, product catalog, and content management capabilities.

## Features

### 🚀 Core Functionality
- **Order Management**: Complete order processing system with status tracking
- **Portfolio Management**: Dynamic portfolio project management with categories
- **Product Catalog**: Full product management with inventory tracking
- **Service Management**: Service offerings with detailed descriptions
- **Quote System**: Customer quote requests and management
- **Contact Management**: Contact form submissions and inquiries
- **Admin Panel**: Comprehensive admin dashboard with statistics

### 🛡️ Security Features
- PDO prepared statements for SQL injection prevention
- Session-based authentication
- Input validation and sanitization
- Error logging and handling
- CSRF protection ready
- Environment-based configuration

### 📊 Admin Dashboard
- Real-time statistics and analytics
- Order status management
- Portfolio project administration
- Product inventory management
- Low stock alerts
- Recent activity monitoring
- Responsive admin interface

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (optional, for future dependencies)

### Setup Instructions

1. **Database Setup**
   ```bash
   # Create database
   mysql -u root -p
   CREATE DATABASE kdtech_solutions;
   
   # Import schema
   mysql -u root -p kdtech_solutions < database/schema.sql
   ```

2. **Environment Configuration**
   ```bash
   # Copy environment file
   cp .env.example .env
   
   # Edit with your database credentials
   nano .env
   ```

3. **File Permissions**
   ```bash
   # Set proper permissions
   chmod 755 backend/
   chmod 644 backend/config/*
   chmod 600 .env
   
   # Create uploads directory
   mkdir -p uploads/portfolio uploads/products uploads/temp
   chmod 755 uploads/ uploads/*/
   ```

4. **Web Server Configuration**
   
   **Apache (.htaccess)**
   ```apache
   RewriteEngine On
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^api/(.*)$ api/index.php [QSA,L]
   ```
   
   **Nginx**
   ```nginx
   location /backend/api/ {
       try_files $uri $uri/ /backend/api/index.php?$query_string;
   }
   ```

## API Endpoints

### Portfolio API
```
GET    /api/portfolio              # Get all projects
GET    /api/portfolio/{id}         # Get single project
POST   /api/portfolio              # Create project (admin)
PUT    /api/portfolio/{id}         # Update project (admin)
DELETE /api/portfolio/{id}         # Delete project (admin)
```

### Products API
```
GET    /api/products               # Get all products
GET    /api/products/{id}          # Get single product
POST   /api/products               # Create product (admin)
PUT    /api/products/{id}          # Update product (admin)
```

### Orders API
```
POST   /api/orders                 # Create new order
GET    /api/orders/{id}            # Get order details
PUT    /api/orders/{id}/status     # Update order status (admin)
```

### Other Endpoints
```
GET    /api/services               # Get services
POST   /api/quotes                 # Submit quote request
POST   /api/contact                # Submit contact message
GET    /api/categories             # Get categories
GET    /api/stats                  # Get dashboard statistics (admin)
```

## Database Schema

### Core Tables
- `users` - Admin users and authentication
- `categories` - Content categorization
- `portfolio_projects` - Portfolio project data
- `products` - Product catalog
- `services` - Service offerings
- `orders` & `order_items` - Order management
- `quotes` - Quote requests
- `contact_messages` - Contact form submissions
- `settings` - Application settings
- `activity_logs` - System activity tracking

### Key Features
- Foreign key constraints for data integrity
- Indexes for performance optimization
- JSON fields for flexible data storage
- Audit trails with timestamps
- Soft delete capabilities

## Admin Panel

### Access
- URL: `/backend/admin/`
- Default credentials: `admin` / `kdtech2024`
- Change default credentials immediately after setup

### Features
- **Dashboard**: Overview with key statistics
- **Order Management**: Process and track orders
- **Portfolio**: Manage projects and showcase work
- **Products**: Inventory and catalog management
- **Services**: Service offerings administration
- **Messages**: Customer inquiries and quotes
- **Settings**: System configuration

## Development

### File Structure
```
backend/
├── config/
│   └── database.php          # Database configuration
├── models/
│   ├── BaseModel.php         # Base model class
│   ├── Order.php             # Order management
│   ├── Portfolio.php         # Portfolio projects
│   └── Product.php           # Products & services
├── api/
│   ├── index.php             # API router
│   └── ApiResponse.php       # Response helper
├── admin/
│   ├── index.php             # Admin dashboard
│   ├── login.php             # Authentication
│   └── assets/               # Admin assets
├── database/
│   └── schema.sql            # Database schema
└── .env.example              # Environment template
```

### Model Usage
```php
// Create new portfolio project
$portfolio = new Portfolio();
$project = $portfolio->createProject([
    'title' => 'New Project',
    'client_name' => 'Client Name',
    'technologies' => ['PHP', 'MySQL', 'JavaScript'],
    'is_featured' => true
]);

// Get featured products
$product = new Product();
$featured = $product->getFeaturedProducts(8);

// Process order
$order = new Order();
$newOrder = $order->createOrder($orderData, $items);
```

### API Response Format
```json
{
    "success": true,
    "message": "Success",
    "timestamp": "2024-01-15T10:30:00+00:00",
    "status_code": 200,
    "data": {
        // Response data
    }
}
```

## Security Considerations

### Production Deployment
1. **Change Default Credentials**
   - Update admin username/password
   - Use strong, unique passwords

2. **Environment Security**
   - Keep `.env` file outside web root
   - Use environment variables for sensitive data
   - Enable HTTPS for all admin operations

3. **Database Security**
   - Use dedicated database user with minimal privileges
   - Enable MySQL SSL connections
   - Regular database backups

4. **File Security**
   - Restrict file upload types and sizes
   - Scan uploaded files for malware
   - Store uploads outside web root when possible

### Monitoring
- Enable error logging
- Monitor failed login attempts
- Track API usage and rate limiting
- Regular security updates

## Maintenance

### Regular Tasks
- Database backups (daily)
- Log file rotation
- Security updates
- Performance monitoring
- Inventory level checks

### Backup Strategy
```bash
# Database backup
mysqldump -u username -p kdtech_solutions > backup_$(date +%Y%m%d).sql

# File backup
tar -czf files_backup_$(date +%Y%m%d).tar.gz uploads/
```

## Support

For technical support or questions:
- Email: admin@kdtechsolutions.com
- Documentation: Check inline code comments
- Logs: Check `error_log` files for debugging

## License

Proprietary software for KDTech Solutions. All rights reserved.

---

**Version**: 1.0.0  
**Last Updated**: January 2024  
**Developed by**: KDTech Solutions Development Team
