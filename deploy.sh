#!/bin/bash

# KDTech Solutions - Production Deployment Script
# This script prepares the website for production deployment

echo "🚀 KDTech Solutions - Production Deployment"
echo "==========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="kdtech-solutions"
BACKUP_DIR="backups"
DEPLOY_DIR="production"

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if required commands exist
check_requirements() {
    print_status "Checking system requirements..."
    
    commands=("php" "mysql" "composer" "zip")
    for cmd in "${commands[@]}"; do
        if ! command -v $cmd &> /dev/null; then
            print_warning "$cmd is not installed (optional for some features)"
        else
            print_success "$cmd is available"
        fi
    done
}

# Create backup of existing deployment
create_backup() {
    print_status "Creating backup..."
    
    if [ -d "$DEPLOY_DIR" ]; then
        timestamp=$(date +"%Y%m%d_%H%M%S")
        backup_name="${PROJECT_NAME}_backup_${timestamp}"
        
        mkdir -p "$BACKUP_DIR"
        cp -r "$DEPLOY_DIR" "$BACKUP_DIR/$backup_name"
        print_success "Backup created: $BACKUP_DIR/$backup_name"
    else
        print_status "No existing deployment found, skipping backup"
    fi
}

# Prepare production directory
prepare_production() {
    print_status "Preparing production directory..."
    
    # Remove old production directory
    if [ -d "$DEPLOY_DIR" ]; then
        rm -rf "$DEPLOY_DIR"
    fi
    
    # Create new production directory
    mkdir -p "$DEPLOY_DIR"
    
    # Copy website files (excluding development files)
    print_status "Copying website files..."
    
    # Frontend files
    cp -r css "$DEPLOY_DIR/"
    cp -r js "$DEPLOY_DIR/"
    cp -r assets "$DEPLOY_DIR/" 2>/dev/null || print_warning "Assets directory not found"
    cp -r images "$DEPLOY_DIR/" 2>/dev/null || print_warning "Images directory not found"
    cp -r fonts "$DEPLOY_DIR/" 2>/dev/null || print_warning "Fonts directory not found"
    
    # HTML files
    cp *.html "$DEPLOY_DIR/" 2>/dev/null || print_warning "No HTML files found"
    
    # Backend files
    cp -r backend "$DEPLOY_DIR/"
    
    # Configuration files
    cp .htaccess "$DEPLOY_DIR/" 2>/dev/null || print_warning ".htaccess not found"
    cp robots.txt "$DEPLOY_DIR/" 2>/dev/null || print_warning "robots.txt not found"
    cp sitemap.xml "$DEPLOY_DIR/" 2>/dev/null || print_warning "sitemap.xml not found"
    
    print_success "Files copied to production directory"
}

# Optimize for production
optimize_production() {
    print_status "Optimizing for production..."
    
    # Remove development files
    find "$DEPLOY_DIR" -name "*.md" -delete
    find "$DEPLOY_DIR" -name ".git*" -delete
    find "$DEPLOY_DIR" -name "*.example" -delete
    find "$DEPLOY_DIR" -name "deploy.sh" -delete
    find "$DEPLOY_DIR" -name "*.log" -delete
    
    # Set proper permissions
    print_status "Setting file permissions..."
    find "$DEPLOY_DIR" -type f -exec chmod 644 {} \;
    find "$DEPLOY_DIR" -type d -exec chmod 755 {} \;
    
    # Make backend directories writable
    if [ -d "$DEPLOY_DIR/backend" ]; then
        chmod 755 "$DEPLOY_DIR/backend"
        
        # Create uploads directory if it doesn't exist
        mkdir -p "$DEPLOY_DIR/uploads/portfolio"
        mkdir -p "$DEPLOY_DIR/uploads/products"
        mkdir -p "$DEPLOY_DIR/uploads/temp"
        chmod -R 755 "$DEPLOY_DIR/uploads"
    fi
    
    print_success "Production optimization complete"
}

# Setup database
setup_database() {
    print_status "Database setup..."
    
    read -p "Do you want to setup the database now? (y/n): " setup_db
    
    if [ "$setup_db" = "y" ] || [ "$setup_db" = "Y" ]; then
        read -p "Enter MySQL username: " db_user
        read -s -p "Enter MySQL password: " db_pass
        echo
        read -p "Enter database name (default: kdtech_solutions): " db_name
        db_name=${db_name:-kdtech_solutions}
        
        print_status "Creating database and importing schema..."
        
        # Create database
        mysql -u "$db_user" -p"$db_pass" -e "CREATE DATABASE IF NOT EXISTS $db_name;"
        
        # Import schema
        if [ -f "$DEPLOY_DIR/backend/database/schema.sql" ]; then
            mysql -u "$db_user" -p"$db_pass" "$db_name" < "$DEPLOY_DIR/backend/database/schema.sql"
            print_success "Database schema imported"
        else
            print_error "Schema file not found"
        fi
        
        # Create .env file
        print_status "Creating environment configuration..."
        cat > "$DEPLOY_DIR/backend/.env" << EOF
# KDTech Solutions - Production Environment
DB_HOST=localhost
DB_NAME=$db_name
DB_USERNAME=$db_user
DB_PASSWORD=$db_pass
DB_CHARSET=utf8mb4

APP_NAME="KDTech Solutions"
APP_ENV=production
APP_DEBUG=false

ADMIN_EMAIL=admin@kdtechsolutions.com
DEFAULT_ADMIN_USERNAME=admin
DEFAULT_ADMIN_PASSWORD=kdtech2024

# Update these with your actual values
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@kdtechsolutions.com
MAIL_FROM_NAME="KDTech Solutions"

# Security Settings
SESSION_LIFETIME=7200
CSRF_TOKEN_EXPIRY=3600
PASSWORD_MIN_LENGTH=8

# Payment Settings
PAYMENT_CURRENCY=KES
TAX_RATE=16.00
SHIPPING_RATE=1500
FREE_SHIPPING_THRESHOLD=50000

# Contact Information
COMPANY_PHONE=+254-700-123-456
COMPANY_EMAIL=info@kdtechsolutions.com
COMPANY_ADDRESS="Nairobi, Kenya"
EOF
        
        chmod 600 "$DEPLOY_DIR/backend/.env"
        print_success "Environment file created"
        
    else
        print_warning "Database setup skipped. Remember to:"
        print_warning "1. Create the database manually"
        print_warning "2. Import backend/database/schema.sql"
        print_warning "3. Create backend/.env file with your configuration"
    fi
}

# Create .htaccess for Apache
create_htaccess() {
    print_status "Creating .htaccess file..."
    
    cat > "$DEPLOY_DIR/.htaccess" << 'EOF'
# KDTech Solutions - Apache Configuration

# Enable rewrite engine
RewriteEngine On

# Force HTTPS (uncomment in production)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# API routing
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^backend/api/(.*)$ backend/api/index.php [QSA,L]

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
</IfModule>

# Compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
</IfModule>

# Browser caching
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
</IfModule>

# Protect sensitive files
<Files ".env">
    Order allow,deny
    Deny from all
</Files>

<Files "*.log">
    Order allow,deny
    Deny from all
</Files>

# Prevent access to PHP files in uploads
<Directory "uploads">
    <Files "*.php">
        Order allow,deny
        Deny from all
    </Files>
</Directory>

# Custom error pages
ErrorDocument 404 /404.html
ErrorDocument 500 /500.html
EOF
    
    print_success ".htaccess file created"
}

# Create robots.txt
create_robots() {
    print_status "Creating robots.txt..."
    
    cat > "$DEPLOY_DIR/robots.txt" << EOF
User-agent: *
Allow: /

# Disallow admin areas
Disallow: /backend/admin/
Disallow: /backend/api/
Disallow: /uploads/temp/

# Sitemap
Sitemap: https://kdtechsolutions.com/sitemap.xml
EOF
    
    print_success "robots.txt created"
}

# Generate deployment report
generate_report() {
    print_status "Generating deployment report..."
    
    report_file="deployment_report_$(date +%Y%m%d_%H%M%S).txt"
    
    cat > "$report_file" << EOF
KDTech Solutions - Deployment Report
===================================
Date: $(date)
Deployment Directory: $DEPLOY_DIR

Files Deployed:
- Frontend: HTML, CSS, JS files
- Backend: PHP application with API endpoints
- Database: Schema and models
- Configuration: .htaccess, robots.txt

Next Steps:
1. Upload the '$DEPLOY_DIR' directory to your web server
2. Point your domain to the uploaded directory
3. Update DNS settings if needed
4. Test all functionality:
   - Website loading
   - Contact forms
   - Admin panel access
   - API endpoints
5. Update environment variables in backend/.env
6. Change default admin credentials
7. Setup SSL certificate (recommended)
8. Configure email settings for notifications
9. Setup regular backups
10. Monitor error logs

Admin Panel Access:
URL: https://yourdomain.com/backend/admin/
Default Username: admin
Default Password: kdtech2024
** CHANGE THESE CREDENTIALS IMMEDIATELY **

Important Security Notes:
- Change default admin credentials
- Update .env file with production values
- Enable HTTPS
- Regular security updates
- Monitor access logs

Support:
For technical support, contact: admin@kdtechsolutions.com
EOF
    
    print_success "Deployment report created: $report_file"
}

# Create deployment package
create_package() {
    print_status "Creating deployment package..."
    
    package_name="${PROJECT_NAME}_production_$(date +%Y%m%d_%H%M%S).zip"
    
    if command -v zip &> /dev/null; then
        zip -r "$package_name" "$DEPLOY_DIR" -x "*.DS_Store" "*/Thumbs.db"
        print_success "Deployment package created: $package_name"
    else
        print_warning "zip command not found. Package not created."
        print_status "You can manually compress the '$DEPLOY_DIR' directory"
    fi
}

# Main deployment process
main() {
    echo
    print_status "Starting deployment process..."
    echo
    
    check_requirements
    echo
    
    create_backup
    echo
    
    prepare_production
    echo
    
    optimize_production
    echo
    
    create_htaccess
    echo
    
    create_robots
    echo
    
    setup_database
    echo
    
    generate_report
    echo
    
    create_package
    echo
    
    print_success "🎉 Deployment completed successfully!"
    echo
    print_status "Next steps:"
    echo "1. Upload the '$DEPLOY_DIR' directory to your web server"
    echo "2. Configure your web server to point to the uploaded directory"
    echo "3. Test the website and admin panel"
    echo "4. Update production settings in backend/.env"
    echo "5. Change default admin credentials"
    echo
    print_warning "⚠️  Security reminder: Change default admin credentials immediately!"
    echo
}

# Run main function
main "$@"
