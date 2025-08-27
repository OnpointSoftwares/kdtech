-- Insert comprehensive IT services for KDTech Solutions
-- This script adds all the software development and IT services to the database

-- First, insert service categories
INSERT INTO categories (name, slug, type, description, is_active) VALUES
('Software Development', 'software-development', 'service', 'Custom software development services', 1),
('IT Infrastructure', 'it-infrastructure', 'service', 'IT infrastructure and support services', 1),
('Specialized Services', 'specialized-services', 'service', 'Advanced and specialized IT services', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Get category IDs
SET @software_dev_id = (SELECT id FROM categories WHERE slug = 'software-development' AND type = 'service');
SET @it_infra_id = (SELECT id FROM categories WHERE slug = 'it-infrastructure' AND type = 'service');
SET @specialized_id = (SELECT id FROM categories WHERE slug = 'specialized-services' AND type = 'service');

-- Insert Software Development Services
INSERT INTO services (category_id, title, slug, short_description, full_description, features, price_range, icon_class, is_featured, is_active, sort_order) VALUES
(@software_dev_id, 'Web Development', 'web-development', 'Modern, responsive websites and web applications using latest technologies.', 'We create modern, responsive websites and web applications that drive business growth and engagement. Our team uses the latest technologies and frameworks to deliver high-performance solutions.', '["React, Vue.js, Angular", "PHP, Node.js, Python", "E-commerce Platforms", "Progressive Web Apps", "API Development"]', 'From KES 50,000', 'fas fa-code', 1, 1, 1),

(@software_dev_id, 'Mobile App Development', 'mobile-app-development', 'Native and cross-platform mobile applications for iOS and Android.', 'Build powerful mobile applications that reach your customers wherever they are. We develop native and cross-platform apps with seamless user experiences and robust functionality.', '["React Native", "Flutter Development", "Native iOS & Android", "App Store Deployment", "Maintenance & Updates"]', 'From KES 100,000', 'fas fa-mobile-alt', 1, 1, 2),

(@software_dev_id, 'Desktop Applications', 'desktop-applications', 'Custom desktop software solutions for Windows, macOS, and Linux.', 'Create powerful desktop applications tailored to your business needs. Our solutions work across all major operating systems with native performance and user-friendly interfaces.', '["Electron Applications", "C#/.NET Development", "Java Applications", "Python GUI Apps", "Cross-platform Solutions"]', 'From KES 80,000', 'fas fa-desktop', 1, 1, 3),

(@software_dev_id, 'Database Solutions', 'database-solutions', 'Database design, optimization, and management services.', 'Design and implement robust database solutions that scale with your business. We provide comprehensive database services from design to optimization and ongoing management.', '["MySQL, PostgreSQL", "MongoDB, Redis", "Database Design", "Performance Optimization", "Data Migration"]', 'From KES 30,000', 'fas fa-database', 0, 1, 4),

(@software_dev_id, 'Cloud Development', 'cloud-development', 'Cloud-native applications and serverless solutions.', 'Leverage the power of cloud computing with our cloud-native development services. Build scalable, resilient applications that take full advantage of cloud platforms.', '["AWS, Azure, GCP", "Serverless Functions", "Microservices", "Container Deployment", "CI/CD Pipelines"]', 'From KES 75,000', 'fas fa-cloud', 0, 1, 5),

(@software_dev_id, 'E-commerce Solutions', 'ecommerce-solutions', 'Complete online store development with payment integration.', 'Launch your online business with our comprehensive e-commerce solutions. From simple stores to complex multi-vendor platforms, we have you covered.', '["WooCommerce, Shopify", "Custom E-commerce", "Payment Gateway Integration", "Inventory Management", "Multi-vendor Platforms"]', 'From KES 120,000', 'fas fa-shopping-cart', 1, 1, 6);

-- Insert IT Infrastructure Services
INSERT INTO services (category_id, title, slug, short_description, full_description, features, price_range, icon_class, is_featured, is_active, sort_order) VALUES
(@it_infra_id, 'Network Solutions', 'network-solutions', 'Professional network design, setup, and maintenance services.', 'Build a robust and secure network infrastructure for your business. Our network solutions ensure reliable connectivity, security, and optimal performance.', '["Network Design & Setup", "WiFi Infrastructure", "Security Implementation", "24/7 Monitoring", "VPN Solutions"]', 'From KES 40,000', 'fas fa-network-wired', 1, 1, 7),

(@it_infra_id, 'Server Management', 'server-management', 'Server setup, configuration, and ongoing maintenance services.', 'Keep your servers running smoothly with our comprehensive server management services. We handle everything from initial setup to ongoing monitoring and maintenance.', '["Linux/Windows Servers", "Cloud Server Setup", "Server Monitoring", "Backup Solutions", "Performance Optimization"]', 'From KES 25,000/month', 'fas fa-server', 1, 1, 8),

(@it_infra_id, 'Cybersecurity', 'cybersecurity', 'Comprehensive security solutions to protect your business.', 'Protect your business from cyber threats with our comprehensive security solutions. We provide multi-layered security to keep your data and systems safe.', '["Security Audits", "Firewall Configuration", "Antivirus Solutions", "Data Encryption", "Security Training"]', 'From KES 35,000', 'fas fa-shield-alt', 1, 1, 9),

(@it_infra_id, 'IT Support & Maintenance', 'it-support-maintenance', 'Ongoing IT support and maintenance for your business systems.', 'Keep your IT systems running smoothly with our comprehensive support and maintenance services. We provide proactive support to prevent issues before they occur.', '["Help Desk Support", "Remote Assistance", "System Updates", "Hardware Maintenance", "User Training"]', 'From KES 15,000/month', 'fas fa-tools', 0, 1, 10),

(@it_infra_id, 'Data Backup & Recovery', 'data-backup-recovery', 'Reliable data backup and disaster recovery solutions.', 'Protect your valuable business data with our comprehensive backup and recovery solutions. We ensure your data is safe and can be quickly restored when needed.', '["Automated Backups", "Cloud Storage", "Disaster Recovery", "Data Migration", "Recovery Testing"]', 'From KES 20,000', 'fas fa-hdd', 0, 1, 11),

(@it_infra_id, 'Computer Sales & Setup', 'computer-sales-setup', 'High-quality computers and professional setup services.', 'Get the right hardware for your business with our computer sales and setup services. We provide quality equipment with professional installation and configuration.', '["Custom PC Builds", "Business Laptops", "Server Hardware", "Professional Setup", "Warranty & Support"]', 'From KES 35,000', 'fas fa-laptop', 0, 1, 12);

-- Insert Specialized Services
INSERT INTO services (category_id, title, slug, short_description, full_description, features, price_range, icon_class, is_featured, is_active, sort_order) VALUES
(@specialized_id, 'AI & Machine Learning', 'ai-machine-learning', 'Artificial intelligence and machine learning solutions.', 'Harness the power of AI and machine learning to transform your business. We develop intelligent solutions that automate processes and provide valuable insights.', '["Chatbot Development", "Data Analytics", "Predictive Modeling", "Computer Vision", "Natural Language Processing"]', 'From KES 150,000', 'fas fa-brain', 1, 1, 13),

(@specialized_id, 'Business Intelligence', 'business-intelligence', 'Data visualization and business intelligence solutions.', 'Turn your data into actionable insights with our business intelligence solutions. We create dashboards and reporting systems that help you make informed decisions.', '["Dashboard Development", "Data Warehousing", "Reporting Systems", "KPI Tracking", "Analytics Integration"]', 'From KES 80,000', 'fas fa-chart-line', 0, 1, 14),

(@specialized_id, 'IT Training & Consulting', 'it-training-consulting', 'Professional IT training and technology consulting services.', 'Empower your team and optimize your technology strategy with our training and consulting services. We help you make the most of your IT investments.', '["Staff Training Programs", "Technology Consulting", "Digital Transformation", "IT Strategy Planning", "Best Practices Implementation"]', 'From KES 25,000', 'fas fa-graduation-cap', 0, 1, 15);

-- Update existing services to ensure proper categorization
UPDATE services SET 
    category_id = @software_dev_id,
    features = '["Responsive Design", "E-commerce Solutions", "CMS Development", "SEO Optimization", "Modern Frameworks"]',
    price_range = 'From KES 50,000',
    is_featured = 1
WHERE title LIKE '%Web%' OR slug LIKE '%web%';

UPDATE services SET 
    category_id = @it_infra_id,
    features = '["Network Design & Setup", "Security Implementation", "24/7 Monitoring", "Troubleshooting Support", "Performance Optimization"]',
    price_range = 'From KES 40,000',
    is_featured = 1
WHERE title LIKE '%Network%' OR slug LIKE '%network%';

-- Ensure all services have proper meta data
UPDATE services SET 
    meta_title = CONCAT(title, ' - KDTech Solutions'),
    meta_description = CONCAT(short_description, ' Professional IT services in Kenya.')
WHERE meta_title IS NULL OR meta_title = '';
