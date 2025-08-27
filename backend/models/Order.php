<?php
/**
 * KDTech Solutions - Order Model
 * Handles order management and processing
 */

require_once 'BaseModel.php';

class Order extends BaseModel {
    protected $table = 'orders';
    protected $fillable = [
        'order_number', 'customer_name', 'customer_email', 'customer_phone',
        'company_name', 'billing_address', 'shipping_address', 'order_type',
        'subtotal', 'tax_amount', 'shipping_amount', 'discount_amount',
        'total_amount', 'currency', 'order_status', 'payment_status',
        'payment_method', 'payment_reference', 'notes', 'admin_notes'
    ];

    /**
     * Create new order with items
     */
    public function createOrder($orderData, $items = []) {
        try {
            $this->beginTransaction();

            // Generate unique order number
            $orderData['order_number'] = $this->generateOrderNumber();
            
            // Calculate totals
            $totals = $this->calculateTotals($items);
            $orderData = array_merge($orderData, $totals);

            // Create order
            $order = $this->create($orderData);
            if (!$order) {
                throw new Exception('Failed to create order');
            }

            // Create order items
            $orderItemModel = new OrderItem();
            foreach ($items as $item) {
                $item['order_id'] = $order['id'];
                $orderItem = $orderItemModel->create($item);
                if (!$orderItem) {
                    throw new Exception('Failed to create order item');
                }
            }

            $this->commit();

            // Send order confirmation email
            $this->sendOrderConfirmation($order['id']);

            return $order;
        } catch (Exception $e) {
            $this->rollback();
            error_log("Create order error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get order with items
     */
    public function getOrderWithItems($orderId) {
        $order = $this->find($orderId);
        if (!$order) {
            return null;
        }

        $orderItemModel = new OrderItem();
        $order['items'] = $orderItemModel->all(['order_id' => $orderId]);

        return $order;
    }

    /**
     * Update order status
     */
    public function updateStatus($orderId, $status, $notes = '') {
        $validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
        
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        $updateData = ['order_status' => $status];
        if ($notes) {
            $updateData['admin_notes'] = $notes;
        }

        $result = $this->update($orderId, $updateData);
        
        if ($result) {
            // Log status change
            $this->logActivity($orderId, 'status_updated', "Status changed to: {$status}");
            
            // Send status update email to customer
            $this->sendStatusUpdate($orderId, $status);
        }

        return $result;
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus($orderId, $paymentStatus, $paymentReference = '') {
        $validStatuses = ['pending', 'paid', 'failed', 'refunded', 'partial'];
        
        if (!in_array($paymentStatus, $validStatuses)) {
            return false;
        }

        $updateData = ['payment_status' => $paymentStatus];
        if ($paymentReference) {
            $updateData['payment_reference'] = $paymentReference;
        }

        $result = $this->update($orderId, $updateData);
        
        if ($result) {
            $this->logActivity($orderId, 'payment_updated', "Payment status: {$paymentStatus}");
        }

        return $result;
    }

    /**
     * Get orders by customer email
     */
    public function getCustomerOrders($email, $limit = 10) {
        return $this->all(['customer_email' => $email], 'order_date DESC', $limit);
    }

    /**
     * Get orders by status
     */
    public function getOrdersByStatus($status, $limit = null) {
        return $this->all(['order_status' => $status], 'order_date DESC', $limit);
    }

    /**
     * Get recent orders
     */
    public function getRecentOrders($limit = 20) {
        return $this->all([], 'order_date DESC', $limit);
    }

    /**
     * Get order statistics
     */
    public function getOrderStats($dateFrom = null, $dateTo = null) {
        $whereClause = '';
        $params = [];

        if ($dateFrom && $dateTo) {
            $whereClause = 'WHERE order_date BETWEEN ? AND ?';
            $params = [$dateFrom, $dateTo];
        }

        $sql = "
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN order_status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END) as total_revenue,
                AVG(CASE WHEN payment_status = 'paid' THEN total_amount ELSE NULL END) as average_order_value
            FROM {$this->table} 
            {$whereClause}
        ";

        $result = $this->query($sql, $params);
        return $result[0] ?? [];
    }

    /**
     * Search orders
     */
    public function searchOrders($query, $limit = 20) {
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE order_number LIKE ? 
            OR customer_name LIKE ? 
            OR customer_email LIKE ? 
            OR company_name LIKE ?
            ORDER BY order_date DESC 
            LIMIT {$limit}
        ";

        $searchTerm = "%{$query}%";
        return $this->query($sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }

    /**
     * Generate unique order number
     */
    private function generateOrderNumber() {
        $prefix = 'KDT';
        $date = date('Ymd');
        
        do {
            $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $orderNumber = $prefix . $date . $random;
        } while ($this->orderNumberExists($orderNumber));

        return $orderNumber;
    }

    /**
     * Check if order number exists
     */
    private function orderNumberExists($orderNumber) {
        $result = $this->all(['order_number' => $orderNumber]);
        return !empty($result);
    }

    /**
     * Calculate order totals
     */
    private function calculateTotals($items) {
        $subtotal = 0;
        
        foreach ($items as $item) {
            $subtotal += $item['total_price'];
        }

        // Get tax rate from settings
        $taxRate = $this->getSetting('tax_rate', 16.00) / 100;
        $taxAmount = $subtotal * $taxRate;
        
        // Shipping calculation (simplified)
        $shippingAmount = $this->calculateShipping($subtotal);
        
        $totalAmount = $subtotal + $taxAmount + $shippingAmount;

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_amount' => $shippingAmount,
            'total_amount' => $totalAmount
        ];
    }

    /**
     * Calculate shipping cost
     */
    private function calculateShipping($subtotal) {
        // Free shipping over 50,000 KES
        if ($subtotal >= 50000) {
            return 0;
        }
        
        // Flat rate shipping
        return 1500; // 1,500 KES
    }

    /**
     * Get setting value
     */
    private function getSetting($key, $default = null) {
        $sql = "SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1";
        $result = $this->query($sql, [$key]);
        
        return $result ? $result[0]['setting_value'] : $default;
    }

    /**
     * Send order confirmation email
     */
    private function sendOrderConfirmation($orderId) {
        // Implementation would depend on your email service
        // This is a placeholder for the email functionality
        $this->logActivity($orderId, 'email_sent', 'Order confirmation email sent');
    }

    /**
     * Send status update email
     */
    private function sendStatusUpdate($orderId, $status) {
        // Implementation would depend on your email service
        $this->logActivity($orderId, 'email_sent', "Status update email sent: {$status}");
    }

    /**
     * Log activity
     */
    private function logActivity($orderId, $action, $description) {
        $sql = "
            INSERT INTO activity_logs (entity_type, entity_id, action, description, created_at) 
            VALUES ('order', ?, ?, ?, NOW())
        ";
        
        $this->query($sql, [$orderId, $action, $description]);
    }
}

/**
 * Order Item Model
 */
class OrderItem extends BaseModel {
    protected $table = 'order_items';
    protected $fillable = [
        'order_id', 'item_type', 'item_id', 'item_name', 'item_description',
        'quantity', 'unit_price', 'total_price', 'item_data'
    ];

    /**
     * Get items for an order
     */
    public function getOrderItems($orderId) {
        return $this->all(['order_id' => $orderId], 'id ASC');
    }

    /**
     * Calculate item total
     */
    public function calculateItemTotal($quantity, $unitPrice) {
        return $quantity * $unitPrice;
    }
}
?>
