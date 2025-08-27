<?php
/**
 * KDTech Solutions - API Response Helper
 * Standardized API response formatting
 */

class ApiResponse {
    /**
     * Send success response
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200) {
        http_response_code($statusCode);
        
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('c'),
            'status_code' => $statusCode
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Send error response
     */
    public static function error($message = 'Error', $statusCode = 400, $errors = null) {
        http_response_code($statusCode);
        
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('c'),
            'status_code' => $statusCode
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        return json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Send validation error response
     */
    public static function validationError($errors, $message = 'Validation failed') {
        return self::error($message, 422, $errors);
    }
    
    /**
     * Send not found response
     */
    public static function notFound($message = 'Resource not found') {
        return self::error($message, 404);
    }
    
    /**
     * Send unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized access') {
        return self::error($message, 401);
    }
    
    /**
     * Send forbidden response
     */
    public static function forbidden($message = 'Access forbidden') {
        return self::error($message, 403);
    }
    
    /**
     * Send server error response
     */
    public static function serverError($message = 'Internal server error') {
        return self::error($message, 500);
    }
    
    /**
     * Send paginated response
     */
    public static function paginated($data, $pagination, $message = 'Success') {
        return self::success([
            'items' => $data,
            'pagination' => $pagination
        ], $message);
    }
    
    /**
     * Send created response
     */
    public static function created($data = null, $message = 'Resource created successfully') {
        return self::success($data, $message, 201);
    }
    
    /**
     * Send updated response
     */
    public static function updated($data = null, $message = 'Resource updated successfully') {
        return self::success($data, $message, 200);
    }
    
    /**
     * Send deleted response
     */
    public static function deleted($message = 'Resource deleted successfully') {
        return self::success(null, $message, 200);
    }
    
    /**
     * Send no content response
     */
    public static function noContent() {
        http_response_code(204);
        return '';
    }
}
?>
