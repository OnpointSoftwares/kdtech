<?php
/**
 * KDTech Solutions - Admin Dashboard
 * Main admin interface for managing the website
 */

session_start();

// Simple authentication check (in production, use proper authentication)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';
require_once '../models/Portfolio.php';
require_once '../models/Product.php';
require_once '../models/Order.php';

// Get dashboard statistics
$portfolio = new Portfolio();
$product = new Product();
$order = new Order();

$portfolioStats = $portfolio->getPortfolioStats();
$productStats = $product->getProductStats();
$orderStats = $order->getOrderStats();

// Get recent activities
$recentOrders = $order->getRecentOrders(5);
$recentProjects = $portfolio->getRecentProjects(5);
$lowStockProducts = $product->getLowStockProducts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KDTech Solutions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h4><i class="fas fa-cog"></i> KDTech Admin</h4>
            </div>
            <ul class="sidebar-menu">
                <li class="active">
                    <a href="index.php"><i class="fas fa-dashboard"></i> Dashboard</a>
                </li>
                <li>
                    <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                </li>
                <li>
                    <a href="portfolio.php"><i class="fas fa-briefcase"></i> Portfolio</a>
                </li>
                <li>
                    <a href="products.php"><i class="fas fa-box"></i> Products</a>
                </li>
                <li>
                    <a href="services.php"><i class="fas fa-tools"></i> Services</a>
                </li>
                <li>
                    <a href="quotes.php"><i class="fas fa-file-invoice"></i> Quotes</a>
                </li>
                <li>
                    <a href="messages.php"><i class="fas fa-envelope"></i> Messages</a>
                </li>
                <li>
                    <a href="categories.php"><i class="fas fa-tags"></i> Categories</a>
                </li>
                <li>
                    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                </li>
                <li>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>Dashboard</h2>
                    <div class="admin-info">
                        <span>Welcome, <?php echo $_SESSION['admin_name'] ?? 'Admin'; ?></span>
                        <a href="../../index.html" class="btn btn-outline-primary btn-sm ms-2" target="_blank">
                            <i class="fas fa-external-link-alt"></i> View Site
                        </a>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-primary">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $orderStats['total_orders'] ?? 0; ?></h3>
                                <p>Total Orders</p>
                                <small class="text-success">
                                    <?php echo $orderStats['pending_orders'] ?? 0; ?> pending
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-success">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $portfolioStats['total_projects'] ?? 0; ?></h3>
                                <p>Portfolio Projects</p>
                                <small class="text-info">
                                    <?php echo $portfolioStats['featured_projects'] ?? 0; ?> featured
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-warning">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $productStats['active_products'] ?? 0; ?></h3>
                                <p>Active Products</p>
                                <small class="text-danger">
                                    <?php echo $productStats['low_stock_products'] ?? 0; ?> low stock
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card">
                            <div class="stat-icon bg-info">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="stat-info">
                                <h3>KES <?php echo number_format($orderStats['total_revenue'] ?? 0); ?></h3>
                                <p>Total Revenue</p>
                                <small class="text-muted">
                                    Avg: KES <?php echo number_format($orderStats['average_order_value'] ?? 0); ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="row">
                    <!-- Recent Orders -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-shopping-cart"></i> Recent Orders</h5>
                                <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($recentOrders)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Order #</th>
                                                    <th>Customer</th>
                                                    <th>Amount</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentOrders as $order): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                    <td>KES <?php echo number_format($order['total_amount']); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo getStatusColor($order['order_status']); ?>">
                                                            <?php echo ucfirst($order['order_status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No recent orders</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Projects -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-briefcase"></i> Recent Projects</h5>
                                <a href="portfolio.php" class="btn btn-sm btn-outline-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($recentProjects)): ?>
                                    <div class="project-list">
                                        <?php foreach ($recentProjects as $project): ?>
                                        <div class="project-item d-flex align-items-center mb-3">
                                            <div class="project-image me-3">
                                                <img src="<?php echo $project['image_url'] ?? 'assets/img/default-project.jpg'; ?>" 
                                                     alt="<?php echo htmlspecialchars($project['title']); ?>" 
                                                     class="rounded" width="50" height="50">
                                            </div>
                                            <div class="project-info flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($project['title']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($project['client_name']); ?></small>
                                                <?php if ($project['is_featured']): ?>
                                                    <span class="badge bg-warning ms-2">Featured</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No recent projects</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alert -->
                <?php if (!empty($lowStockProducts)): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h5><i class="fas fa-exclamation-triangle"></i> Low Stock Alert</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>SKU</th>
                                                <th>Current Stock</th>
                                                <th>Min Level</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($lowStockProducts as $lowStock): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($lowStock['name']); ?></td>
                                                <td><?php echo htmlspecialchars($lowStock['sku']); ?></td>
                                                <td class="text-danger"><?php echo $lowStock['stock_quantity']; ?></td>
                                                <td><?php echo $lowStock['min_stock_level']; ?></td>
                                                <td>
                                                    <a href="products.php?edit=<?php echo $lowStock['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">Update Stock</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/admin.js"></script>
</body>
</html>

<?php
function getStatusColor($status) {
    switch ($status) {
        case 'pending': return 'warning';
        case 'confirmed': return 'info';
        case 'processing': return 'primary';
        case 'shipped': return 'success';
        case 'delivered': return 'success';
        case 'cancelled': return 'danger';
        case 'refunded': return 'secondary';
        default: return 'secondary';
    }
}
?>
