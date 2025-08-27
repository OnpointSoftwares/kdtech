<?php
/**
 * KDTech Solutions - Base Model Class
 * Provides common database operations and utilities
 */

require_once __DIR__ . '/../config/database.php';

abstract class BaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    protected $timestamps = true;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Find a record by ID
     */
    public function find($id) {
        try {
            $query = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result ? $this->hideFields($result) : null;
        } catch (PDOException $e) {
            error_log("Find error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all records with optional conditions
     */
    public function all($conditions = [], $orderBy = null, $limit = null, $offset = 0) {
        try {
            $query = "SELECT * FROM {$this->table}";
            $params = [];

            // Add WHERE conditions
            if (!empty($conditions)) {
                $whereClause = [];
                foreach ($conditions as $field => $value) {
                    if (is_array($value)) {
                        $placeholders = str_repeat('?,', count($value) - 1) . '?';
                        $whereClause[] = "{$field} IN ({$placeholders})";
                        $params = array_merge($params, $value);
                    } else {
                        $whereClause[] = "{$field} = ?";
                        $params[] = $value;
                    }
                }
                $query .= " WHERE " . implode(' AND ', $whereClause);
            }

            // Add ORDER BY
            if ($orderBy) {
                $query .= " ORDER BY {$orderBy}";
            }

            // Add LIMIT and OFFSET
            if ($limit) {
                $query .= " LIMIT {$limit}";
                if ($offset > 0) {
                    $query .= " OFFSET {$offset}";
                }
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            $results = $stmt->fetchAll();
            return array_map([$this, 'hideFields'], $results);
        } catch (PDOException $e) {
            error_log("All error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new record
     */
    public function create($data) {
        try {
            // Filter data to only include fillable fields
            $filteredData = $this->filterFillable($data);
            
            // Add timestamps
            if ($this->timestamps) {
                $filteredData['created_at'] = date('Y-m-d H:i:s');
                $filteredData['updated_at'] = date('Y-m-d H:i:s');
            }

            $fields = array_keys($filteredData);
            $placeholders = ':' . implode(', :', $fields);
            
            $query = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES ({$placeholders})";
            $stmt = $this->db->prepare($query);
            
            foreach ($filteredData as $field => $value) {
                $stmt->bindValue(":{$field}", $value);
            }
            
            $stmt->execute();
            $id = $this->db->lastInsertId();
            
            return $this->find($id);
        } catch (PDOException $e) {
            error_log("Create error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a record
     */
    public function update($id, $data) {
        try {
            // Filter data to only include fillable fields
            $filteredData = $this->filterFillable($data);
            
            // Add updated timestamp
            if ($this->timestamps) {
                $filteredData['updated_at'] = date('Y-m-d H:i:s');
            }

            $fields = array_keys($filteredData);
            $setClause = implode(' = ?, ', $fields) . ' = ?';
            
            $query = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = ?";
            $stmt = $this->db->prepare($query);
            
            $values = array_values($filteredData);
            $values[] = $id;
            
            $result = $stmt->execute($values);
            
            return $result ? $this->find($id) : false;
        } catch (PDOException $e) {
            error_log("Update error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a record
     */
    public function delete($id) {
        try {
            $query = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Count records with optional conditions
     */
    public function count($conditions = []) {
        try {
            $query = "SELECT COUNT(*) as total FROM {$this->table}";
            $params = [];

            if (!empty($conditions)) {
                $whereClause = [];
                foreach ($conditions as $field => $value) {
                    $whereClause[] = "{$field} = ?";
                    $params[] = $value;
                }
                $query .= " WHERE " . implode(' AND ', $whereClause);
            }

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            $result = $stmt->fetch();
            return (int) $result['total'];
        } catch (PDOException $e) {
            error_log("Count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Execute custom query
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->db->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->db->rollback();
    }

    /**
     * Filter data to only include fillable fields
     */
    protected function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Hide sensitive fields from output
     */
    protected function hideFields($data) {
        if (empty($this->hidden) || !is_array($data)) {
            return $data;
        }

        foreach ($this->hidden as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    /**
     * Validate required fields
     */
    protected function validateRequired($data, $required = []) {
        $missing = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * Sanitize input data
     */
    protected function sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate unique slug
     */
    protected function generateSlug($title, $id = null) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $id)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if slug exists
     */
    protected function slugExists($slug, $excludeId = null) {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE slug = ?";
        $params = [$slug];

        if ($excludeId) {
            $query .= " AND {$this->primaryKey} != ?";
            $params[] = $excludeId;
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }
}
?>
