<?php
/**
 * KDTech Solutions - Product Model
 * Handles product management and inventory
 */

require_once 'BaseModel.php';

class Product extends BaseModel {
    protected $table = 'products';
    protected $fillable = [
        'category_id', 'name', 'slug', 'sku', 'short_description', 'full_description',
        'specifications', 'price', 'sale_price', 'stock_quantity', 'min_stock_level',
        'image_url', 'gallery_images', 'is_featured', 'is_active', 'meta_title',
        'meta_description', 'sort_order'
    ];

    /**
     * Create new product
     */
    public function createProduct($data) {
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        // Generate SKU if not provided
        if (empty($data['sku'])) {
            $data['sku'] = $this->generateSKU($data['name']);
        }

        // Process specifications array
        if (isset($data['specifications']) && is_array($data['specifications'])) {
            $data['specifications'] = json_encode($data['specifications']);
        }

        // Process gallery images array
        if (isset($data['gallery_images']) && is_array($data['gallery_images'])) {
            $data['gallery_images'] = json_encode($data['gallery_images']);
        }

        // Set default values
        $data['is_active'] = $data['is_active'] ?? true;
        $data['is_featured'] = $data['is_featured'] ?? false;
        $data['stock_quantity'] = $data['stock_quantity'] ?? 0;
        $data['min_stock_level'] = $data['min_stock_level'] ?? 5;

        $product = $this->create($data);
        
        if ($product) {
            $this->logActivity($product['id'], 'created', 'Product created');
        }

        return $product;
    }

    /**
     * Update product
     */
    public function updateProduct($id, $data) {
        // Generate new slug if name changed
        $existingProduct = $this->find($id);
        if ($existingProduct && isset($data['name']) && $data['name'] !== $existingProduct['name']) {
            $data['slug'] = $this->generateSlug($data['name'], $id);
        }

        // Process specifications array
        if (isset($data['specifications']) && is_array($data['specifications'])) {
            $data['specifications'] = json_encode($data['specifications']);
        }

        // Process gallery images array
        if (isset($data['gallery_images']) && is_array($data['gallery_images'])) {
            $data['gallery_images'] = json_encode($data['gallery_images']);
        }

        $result = $this->update($id, $data);
        
        if ($result) {
            $this->logActivity($id, 'updated', 'Product updated');
            
            // Check for low stock
            if (isset($data['stock_quantity'])) {
                $this->checkLowStock($id);
            }
        }

        return $result;
    }

    /**
     * Get featured products
     */
    public function getFeaturedProducts($limit = 8) {
        return $this->getProductsWithCategory(
            ['is_featured' => 1, 'is_active' => 1],
            'sort_order ASC, created_at DESC',
            $limit
        );
    }

    /**
     * Get products by category
     */
    public function getProductsByCategory($categoryId, $limit = null) {
        return $this->getProductsWithCategory(
            ['category_id' => $categoryId, 'is_active' => 1],
            'sort_order ASC, name ASC',
            $limit
        );
    }

    /**
     * Get products with category information
     */
    public function getProductsWithCategory($conditions = [], $orderBy = null, $limit = null, $offset = 0) {
        $whereClause = '';
        $params = [];

        if (!empty($conditions)) {
            $whereConditions = [];
            foreach ($conditions as $field => $value) {
                $whereConditions[] = "p.{$field} = ?";
                $params[] = $value;
            }
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        $orderClause = $orderBy ? "ORDER BY {$orderBy}" : '';
        $limitClause = $limit ? "LIMIT {$limit}" : '';
        $offsetClause = $offset > 0 ? "OFFSET {$offset}" : '';

        $sql = "
            SELECT p.*, c.name as category_name, c.slug as category_slug
            FROM {$this->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            {$whereClause}
            {$orderClause}
            {$limitClause}
            {$offsetClause}
        ";

        $results = $this->query($sql, $params);
        return array_map([$this, 'processProductData'], $results);
    }

    /**
     * Get product by slug
     */
    public function getBySlug($slug) {
        $sql = "
            SELECT p.*, c.name as category_name, c.slug as category_slug
            FROM {$this->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.slug = ? AND p.is_active = 1
            LIMIT 1
        ";

        $results = $this->query($sql, [$slug]);
        if (empty($results)) {
            return null;
        }

        return $this->processProductData($results[0]);
    }

    /**
     * Search products
     */
    public function searchProducts($query, $limit = 20) {
        $sql = "
            SELECT p.*, c.name as category_name
            FROM {$this->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.is_active = 1
            AND (
                p.name LIKE ? 
                OR p.short_description LIKE ?
                OR p.sku LIKE ?
                OR c.name LIKE ?
            )
            ORDER BY p.name ASC
            LIMIT {$limit}
        ";

        $searchTerm = "%{$query}%";
        $results = $this->query($sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        return array_map([$this, 'processProductData'], $results);
    }

    /**
     * Get low stock products
     */
    public function getLowStockProducts() {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE is_active = 1
            AND stock_quantity <= min_stock_level
            ORDER BY stock_quantity ASC
        ";

        $results = $this->query($sql);
        return array_map([$this, 'processProductData'], $results);
    }

    /**
     * Update stock quantity
     */
    public function updateStock($id, $quantity, $operation = 'set') {
        $product = $this->find($id);
        if (!$product) {
            return false;
        }

        $newQuantity = $quantity;
        if ($operation === 'add') {
            $newQuantity = $product['stock_quantity'] + $quantity;
        } elseif ($operation === 'subtract') {
            $newQuantity = $product['stock_quantity'] - $quantity;
        }

        // Ensure stock doesn't go negative
        $newQuantity = max(0, $newQuantity);

        $result = $this->update($id, ['stock_quantity' => $newQuantity]);
        
        if ($result) {
            $this->logActivity($id, 'stock_updated', "Stock {$operation}: {$quantity}. New quantity: {$newQuantity}");
            $this->checkLowStock($id);
        }

        return $result;
    }

    /**
     * Check if product is in stock
     */
    public function isInStock($id, $quantity = 1) {
        $product = $this->find($id);
        return $product && $product['stock_quantity'] >= $quantity;
    }

    /**
     * Get product statistics
     */
    public function getProductStats() {
        $sql = "
            SELECT 
                COUNT(*) as total_products,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_products,
                SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured_products,
                SUM(CASE WHEN stock_quantity <= min_stock_level THEN 1 ELSE 0 END) as low_stock_products,
                SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock_products,
                AVG(price) as average_price,
                SUM(stock_quantity * price) as total_inventory_value
            FROM {$this->table}
        ";

        $result = $this->query($sql);
        return $result[0] ?? [];
    }

    /**
     * Get related products
     */
    public function getRelatedProducts($productId, $limit = 4) {
        $product = $this->find($productId);
        if (!$product) {
            return [];
        }

        $sql = "
            SELECT p.*, c.name as category_name
            FROM {$this->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.is_active = 1 
            AND p.id != ?
            AND p.category_id = ?
            ORDER BY RAND()
            LIMIT {$limit}
        ";

        $results = $this->query($sql, [$productId, $product['category_id']]);
        return array_map([$this, 'processProductData'], $results);
    }

    /**
     * Generate unique SKU
     */
    private function generateSKU($name) {
        $prefix = 'KDT';
        $nameCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 3));
        
        do {
            $random = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
            $sku = $prefix . $nameCode . $random;
        } while ($this->skuExists($sku));

        return $sku;
    }

    /**
     * Check if SKU exists
     */
    private function skuExists($sku) {
        $result = $this->all(['sku' => $sku]);
        return !empty($result);
    }

    /**
     * Check for low stock and log if necessary
     */
    private function checkLowStock($productId) {
        $product = $this->find($productId);
        if ($product && $product['stock_quantity'] <= $product['min_stock_level']) {
            $this->logActivity($productId, 'low_stock_alert', "Low stock alert: {$product['stock_quantity']} remaining");
        }
    }

    /**
     * Process product data (decode JSON fields)
     */
    private function processProductData($product) {
        if (isset($product['specifications']) && $product['specifications']) {
            $product['specifications'] = json_decode($product['specifications'], true) ?? [];
        }

        if (isset($product['gallery_images']) && $product['gallery_images']) {
            $product['gallery_images'] = json_decode($product['gallery_images'], true) ?? [];
        }

        // Calculate discount percentage if sale price exists
        if (isset($product['price']) && isset($product['sale_price']) && $product['sale_price'] > 0) {
            $product['discount_percentage'] = round((($product['price'] - $product['sale_price']) / $product['price']) * 100);
        }

        return $product;
    }

    /**
     * Log activity
     */
    private function logActivity($productId, $action, $description) {
        $sql = "
            INSERT INTO activity_logs (entity_type, entity_id, action, description, created_at) 
            VALUES ('product', ?, ?, ?, NOW())
        ";
        
        $this->query($sql, [$productId, $action, $description]);
    }

    /**
     * Validate product data
     */
    public function validateProductData($data, $isUpdate = false) {
        $errors = [];

        // Required fields for new products
        if (!$isUpdate) {
            $required = ['name', 'short_description', 'price'];
            $missing = $this->validateRequired($data, $required);
            
            if (!empty($missing)) {
                $errors['required'] = 'Missing required fields: ' . implode(', ', $missing);
            }
        }

        // Validate price
        if (isset($data['price']) && (!is_numeric($data['price']) || $data['price'] < 0)) {
            $errors['price'] = 'Price must be a valid positive number';
        }

        // Validate sale price
        if (isset($data['sale_price']) && $data['sale_price'] !== null && 
            (!is_numeric($data['sale_price']) || $data['sale_price'] < 0)) {
            $errors['sale_price'] = 'Sale price must be a valid positive number';
        }

        // Validate stock quantity
        if (isset($data['stock_quantity']) && (!is_numeric($data['stock_quantity']) || $data['stock_quantity'] < 0)) {
            $errors['stock_quantity'] = 'Stock quantity must be a valid non-negative number';
        }

        return $errors;
    }
}

/**
 * Service Model
 */
class Service extends BaseModel {
    protected $table = 'services';
    protected $fillable = [
        'category_id', 'title', 'slug', 'short_description', 'full_description',
        'features', 'price_range', 'image_url', 'icon_class', 'is_featured',
        'is_active', 'meta_title', 'meta_description', 'sort_order'
    ];

    /**
     * Create new service
     */
    public function createService($data) {
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }

        if (isset($data['features']) && is_array($data['features'])) {
            $data['features'] = json_encode($data['features']);
        }

        $data['is_active'] = $data['is_active'] ?? true;
        $data['is_featured'] = $data['is_featured'] ?? false;

        return $this->create($data);
    }

    /**
     * Get featured services
     */
    public function getFeaturedServices($limit = 6) {
        return $this->all(
            ['is_featured' => 1, 'is_active' => 1],
            'sort_order ASC, created_at DESC',
            $limit
        );
    }

    /**
     * Get services by category
     */
    public function getServicesByCategory($categoryId, $limit = null) {
        return $this->all(
            ['category_id' => $categoryId, 'is_active' => 1],
            'sort_order ASC, title ASC',
            $limit
        );
    }

    /**
     * Get service by slug
     */
    public function getBySlug($slug) {
        $services = $this->all(['slug' => $slug, 'is_active' => 1]);
        return !empty($services) ? $this->processServiceData($services[0]) : null;
    }

    /**
     * Process service data
     */
    private function processServiceData($service) {
        if (isset($service['features']) && $service['features']) {
            $service['features'] = json_decode($service['features'], true) ?? [];
        }

        return $service;
    }
}
?>
