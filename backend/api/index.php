<?php
/**
 * KDTech Solutions - API Router
 * Main API endpoint handler with routing
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../models/Portfolio.php';
require_once '../models/Product.php';
require_once '../models/Order.php';
require_once 'ApiResponse.php';

class ApiRouter {
    private $method;
    private $endpoint;
    private $params;

    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->parseRequest();
    }

    private function parseRequest() {
        $request = $_SERVER['REQUEST_URI'];
        $path = parse_url($request, PHP_URL_PATH);
        
        // Remove /backend/api/ from path
        $path = str_replace('/backend/api/', '', $path);
        $path = trim($path, '/');
        
        $parts = explode('/', $path);
        $this->endpoint = $parts[0] ?? '';
        $this->params = array_slice($parts, 1);
    }

    public function route() {
        try {
            switch ($this->endpoint) {
                case 'portfolio':
                    return $this->handlePortfolio();
                case 'products':
                    return $this->handleProducts();
                case 'services':
                    return $this->handleServices();
                case 'orders':
                    return $this->handleOrders();
                case 'quotes':
                    return $this->handleQuotes();
                case 'contact':
                    return $this->handleContact();
                case 'categories':
                    return $this->handleCategories();
                case 'stats':
                    return $this->handleStats();
                default:
                    return ApiResponse::error('Endpoint not found', 404);
            }
        } catch (Exception $e) {
            error_log("API Error: " . $e->getMessage());
            return ApiResponse::error('Internal server error', 500);
        }
    }

    private function handlePortfolio() {
        $portfolio = new Portfolio();

        switch ($this->method) {
            case 'GET':
                if (empty($this->params)) {
                    // Get all projects with pagination
                    $page = $_GET['page'] ?? 1;
                    $limit = $_GET['limit'] ?? 12;
                    $category = $_GET['category'] ?? null;
                    $featured = $_GET['featured'] ?? null;
                    
                    $offset = ($page - 1) * $limit;
                    
                    if ($featured) {
                        $projects = $portfolio->getFeaturedProjects($limit);
                    } elseif ($category) {
                        $projects = $portfolio->getProjectsByCategory($category, $limit);
                    } else {
                        $projects = $portfolio->getProjectsWithCategory($limit, $offset);
                    }
                    
                    $total = $portfolio->count(['is_active' => 1]);
                    
                    return ApiResponse::success([
                        'projects' => $projects,
                        'pagination' => [
                            'current_page' => (int)$page,
                            'per_page' => (int)$limit,
                            'total' => $total,
                            'total_pages' => ceil($total / $limit)
                        ]
                    ]);
                } else {
                    // Get single project by slug or ID
                    $identifier = $this->params[0];
                    if (is_numeric($identifier)) {
                        $project = $portfolio->find($identifier);
                    } else {
                        $project = $portfolio->getBySlug($identifier);
                    }
                    
                    if (!$project) {
                        return ApiResponse::error('Project not found', 404);
                    }
                    
                    // Get related projects
                    $related = $portfolio->getRelatedProjects($project['id'], 4);
                    $project['related'] = $related;
                    
                    return ApiResponse::success($project);
                }
                
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                $errors = $portfolio->validateProjectData($data);
                
                if (!empty($errors)) {
                    return ApiResponse::error('Validation failed', 400, $errors);
                }
                
                $project = $portfolio->createProject($data);
                if ($project) {
                    return ApiResponse::success($project, 'Project created successfully', 201);
                } else {
                    return ApiResponse::error('Failed to create project', 500);
                }
                
            default:
                return ApiResponse::error('Method not allowed', 405);
        }
    }

    private function handleProducts() {
        $product = new Product();

        switch ($this->method) {
            case 'GET':
                if (empty($this->params)) {
                    // Get all products with pagination
                    $page = $_GET['page'] ?? 1;
                    $limit = $_GET['limit'] ?? 12;
                    $category = $_GET['category'] ?? null;
                    $featured = $_GET['featured'] ?? null;
                    $search = $_GET['search'] ?? null;
                    
                    $offset = ($page - 1) * $limit;
                    
                    if ($search) {
                        $products = $product->searchProducts($search, $limit);
                        $total = count($products);
                    } elseif ($featured) {
                        $products = $product->getFeaturedProducts($limit);
                        $total = count($products);
                    } elseif ($category) {
                        $products = $product->getProductsByCategory($category, $limit);
                        $total = count($products);
                    } else {
                        $products = $product->getProductsWithCategory(['is_active' => 1], 'sort_order ASC, name ASC', $limit, $offset);
                        $total = $product->count(['is_active' => 1]);
                    }
                    
                    return ApiResponse::success([
                        'products' => $products,
                        'pagination' => [
                            'current_page' => (int)$page,
                            'per_page' => (int)$limit,
                            'total' => $total,
                            'total_pages' => ceil($total / $limit)
                        ]
                    ]);
                } else {
                    // Get single product
                    $identifier = $this->params[0];
                    if (is_numeric($identifier)) {
                        $productData = $product->find($identifier);
                    } else {
                        $productData = $product->getBySlug($identifier);
                    }
                    
                    if (!$productData) {
                        return ApiResponse::error('Product not found', 404);
                    }
                    
                    // Get related products
                    $related = $product->getRelatedProducts($productData['id'], 4);
                    $productData['related'] = $related;
                    
                    return ApiResponse::success($productData);
                }
                
            default:
                return ApiResponse::error('Method not allowed', 405);
        }
    }

    private function handleServices() {
        $service = new Service();

        switch ($this->method) {
            case 'GET':
                if (empty($this->params)) {
                    $featured = $_GET['featured'] ?? null;
                    $category = $_GET['category'] ?? null;
                    
                    if ($featured) {
                        $services = $service->getFeaturedServices();
                    } elseif ($category) {
                        $services = $service->getServicesByCategory($category);
                    } else {
                        $services = $service->all(['is_active' => 1], 'sort_order ASC, title ASC');
                    }
                    
                    return ApiResponse::success($services);
                } else {
                    // Get single service
                    $identifier = $this->params[0];
                    if (is_numeric($identifier)) {
                        $serviceData = $service->find($identifier);
                    } else {
                        $serviceData = $service->getBySlug($identifier);
                    }
                    
                    if (!$serviceData) {
                        return ApiResponse::error('Service not found', 404);
                    }
                    
                    return ApiResponse::success($serviceData);
                }
                
            default:
                return ApiResponse::error('Method not allowed', 405);
        }
    }

    private function handleOrders() {
        $order = new Order();

        switch ($this->method) {
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                
                // Validate required fields
                $required = ['customer_name', 'customer_email', 'order_type', 'items'];
                $missing = [];
                
                foreach ($required as $field) {
                    if (!isset($data[$field]) || empty($data[$field])) {
                        $missing[] = $field;
                    }
                }
                
                if (!empty($missing)) {
                    return ApiResponse::error('Missing required fields: ' . implode(', ', $missing), 400);
                }
                
                // Validate email
                if (!filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
                    return ApiResponse::error('Invalid email address', 400);
                }
                
                // Create order
                $newOrder = $order->createOrder($data, $data['items']);
                if ($newOrder) {
                    return ApiResponse::success($newOrder, 'Order created successfully', 201);
                } else {
                    return ApiResponse::error('Failed to create order', 500);
                }
                
            case 'GET':
                if (!empty($this->params)) {
                    // Get single order
                    $orderId = $this->params[0];
                    $orderData = $order->getOrderWithItems($orderId);
                    
                    if (!$orderData) {
                        return ApiResponse::error('Order not found', 404);
                    }
                    
                    return ApiResponse::success($orderData);
                }
                break;
                
            default:
                return ApiResponse::error('Method not allowed', 405);
        }
    }

    private function handleQuotes() {
        switch ($this->method) {
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                
                // Validate required fields
                $required = ['customer_name', 'customer_email', 'service_type', 'project_description'];
                $missing = [];
                
                foreach ($required as $field) {
                    if (!isset($data[$field]) || empty($data[$field])) {
                        $missing[] = $field;
                    }
                }
                
                if (!empty($missing)) {
                    return ApiResponse::error('Missing required fields: ' . implode(', ', $missing), 400);
                }
                
                // Generate quote number
                $data['quote_number'] = 'QT' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                // Process requirements if array
                if (isset($data['requirements']) && is_array($data['requirements'])) {
                    $data['requirements'] = json_encode($data['requirements']);
                }
                
                // Create quote using base model functionality
                $db = new Database();
                $conn = $db->getConnection();
                
                $sql = "INSERT INTO quotes (quote_number, customer_name, customer_email, customer_phone, 
                        company_name, service_type, project_description, requirements, budget_range, 
                        timeline, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([
                    $data['quote_number'],
                    $data['customer_name'],
                    $data['customer_email'],
                    $data['customer_phone'] ?? null,
                    $data['company_name'] ?? null,
                    $data['service_type'],
                    $data['project_description'],
                    $data['requirements'] ?? null,
                    $data['budget_range'] ?? null,
                    $data['timeline'] ?? null
                ]);
                
                if ($result) {
                    return ApiResponse::success([
                        'quote_number' => $data['quote_number'],
                        'message' => 'Quote request submitted successfully'
                    ], 'Quote request created', 201);
                } else {
                    return ApiResponse::error('Failed to create quote request', 500);
                }
                
            default:
                return ApiResponse::error('Method not allowed', 405);
        }
    }

    private function handleContact() {
        switch ($this->method) {
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                
                // Validate required fields
                $required = ['name', 'email', 'message'];
                $missing = [];
                
                foreach ($required as $field) {
                    if (!isset($data[$field]) || empty($data[$field])) {
                        $missing[] = $field;
                    }
                }
                
                if (!empty($missing)) {
                    return ApiResponse::error('Missing required fields: ' . implode(', ', $missing), 400);
                }
                
                // Validate email
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    return ApiResponse::error('Invalid email address', 400);
                }
                
                // Create contact message
                $db = new Database();
                $conn = $db->getConnection();
                
                $sql = "INSERT INTO contact_messages (name, email, phone, company, subject, message, 
                        message_type, ip_address, user_agent, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([
                    $data['name'],
                    $data['email'],
                    $data['phone'] ?? null,
                    $data['company'] ?? null,
                    $data['subject'] ?? 'General Inquiry',
                    $data['message'],
                    $data['message_type'] ?? 'general',
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                ]);
                
                if ($result) {
                    return ApiResponse::success([
                        'message' => 'Message sent successfully'
                    ], 'Contact message sent', 201);
                } else {
                    return ApiResponse::error('Failed to send message', 500);
                }
                
            default:
                return ApiResponse::error('Method not allowed', 405);
        }
    }

    private function handleCategories() {
        switch ($this->method) {
            case 'GET':
                $type = $_GET['type'] ?? null;
                $db = new Database();
                $conn = $db->getConnection();
                
                $sql = "SELECT * FROM categories WHERE is_active = 1";
                $params = [];
                
                if ($type) {
                    $sql .= " AND type = ?";
                    $params[] = $type;
                }
                
                $sql .= " ORDER BY sort_order ASC, name ASC";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $categories = $stmt->fetchAll();
                
                return ApiResponse::success($categories);
                
            default:
                return ApiResponse::error('Method not allowed', 405);
        }
    }

    private function handleStats() {
        switch ($this->method) {
            case 'GET':
                $portfolio = new Portfolio();
                $product = new Product();
                $order = new Order();
                
                $stats = [
                    'portfolio' => $portfolio->getPortfolioStats(),
                    'products' => $product->getProductStats(),
                    'orders' => $order->getOrderStats()
                ];
                
                return ApiResponse::success($stats);
                
            default:
                return ApiResponse::error('Method not allowed', 405);
        }
    }
}

// Initialize and route the request
$router = new ApiRouter();
echo $router->route();
?>
