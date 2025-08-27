<?php
/**
 * KDTech Solutions - Portfolio Model
 * Handles portfolio project management
 */

require_once 'BaseModel.php';

class Portfolio extends BaseModel {
    protected $table = 'portfolio_projects';
    protected $fillable = [
        'category_id', 'title', 'slug', 'client_name', 'project_type',
        'short_description', 'full_description', 'technologies', 'project_url',
        'github_url', 'image_url', 'gallery_images', 'start_date', 'end_date',
        'project_status', 'is_featured', 'is_active', 'meta_title',
        'meta_description', 'sort_order'
    ];

    /**
     * Create new portfolio project
     */
    public function createProject($data) {
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['title']);
        }

        // Process technologies array
        if (isset($data['technologies']) && is_array($data['technologies'])) {
            $data['technologies'] = json_encode($data['technologies']);
        }

        // Process gallery images array
        if (isset($data['gallery_images']) && is_array($data['gallery_images'])) {
            $data['gallery_images'] = json_encode($data['gallery_images']);
        }

        // Set default values
        $data['project_status'] = $data['project_status'] ?? 'completed';
        $data['is_active'] = $data['is_active'] ?? true;
        $data['is_featured'] = $data['is_featured'] ?? false;

        $project = $this->create($data);
        
        if ($project) {
            $this->logActivity($project['id'], 'created', 'Portfolio project created');
        }

        return $project;
    }

    /**
     * Update portfolio project
     */
    public function updateProject($id, $data) {
        // Generate new slug if title changed
        $existingProject = $this->find($id);
        if ($existingProject && isset($data['title']) && $data['title'] !== $existingProject['title']) {
            $data['slug'] = $this->generateSlug($data['title'], $id);
        }

        // Process technologies array
        if (isset($data['technologies']) && is_array($data['technologies'])) {
            $data['technologies'] = json_encode($data['technologies']);
        }

        // Process gallery images array
        if (isset($data['gallery_images']) && is_array($data['gallery_images'])) {
            $data['gallery_images'] = json_encode($data['gallery_images']);
        }

        $result = $this->update($id, $data);
        
        if ($result) {
            $this->logActivity($id, 'updated', 'Portfolio project updated');
        }

        return $result;
    }

    /**
     * Get featured projects
     */
    public function getFeaturedProjects($limit = 6) {
        return $this->all(
            ['is_featured' => 1, 'is_active' => 1],
            'sort_order ASC, created_at DESC',
            $limit
        );
    }

    /**
     * Get projects by category
     */
    public function getProjectsByCategory($categoryId, $limit = null) {
        return $this->all(
            ['category_id' => $categoryId, 'is_active' => 1],
            'sort_order ASC, created_at DESC',
            $limit
        );
    }

    /**
     * Get projects by status
     */
    public function getProjectsByStatus($status, $limit = null) {
        return $this->all(
            ['project_status' => $status, 'is_active' => 1],
            'created_at DESC',
            $limit
        );
    }

    /**
     * Get recent projects
     */
    public function getRecentProjects($limit = 12) {
        return $this->all(
            ['is_active' => 1],
            'created_at DESC',
            $limit
        );
    }

    /**
     * Get project by slug
     */
    public function getBySlug($slug) {
        $projects = $this->all(['slug' => $slug, 'is_active' => 1]);
        return !empty($projects) ? $projects[0] : null;
    }

    /**
     * Get projects with category information
     */
    public function getProjectsWithCategory($limit = null, $offset = 0) {
        $sql = "
            SELECT p.*, c.name as category_name, c.slug as category_slug
            FROM {$this->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.is_active = 1
            ORDER BY p.sort_order ASC, p.created_at DESC
        ";

        if ($limit) {
            $sql .= " LIMIT {$limit}";
            if ($offset > 0) {
                $sql .= " OFFSET {$offset}";
            }
        }

        $results = $this->query($sql);
        return array_map([$this, 'processProjectData'], $results);
    }

    /**
     * Search projects
     */
    public function searchProjects($query, $limit = 20) {
        $sql = "
            SELECT p.*, c.name as category_name
            FROM {$this->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.is_active = 1
            AND (
                p.title LIKE ? 
                OR p.short_description LIKE ?
                OR p.client_name LIKE ?
                OR p.project_type LIKE ?
                OR c.name LIKE ?
            )
            ORDER BY p.created_at DESC
            LIMIT {$limit}
        ";

        $searchTerm = "%{$query}%";
        $results = $this->query($sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        return array_map([$this, 'processProjectData'], $results);
    }

    /**
     * Get portfolio statistics
     */
    public function getPortfolioStats() {
        $sql = "
            SELECT 
                COUNT(*) as total_projects,
                SUM(CASE WHEN project_status = 'completed' THEN 1 ELSE 0 END) as completed_projects,
                SUM(CASE WHEN project_status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_projects,
                SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured_projects,
                COUNT(DISTINCT client_name) as unique_clients,
                COUNT(DISTINCT project_type) as project_types
            FROM {$this->table} 
            WHERE is_active = 1
        ";

        $result = $this->query($sql);
        return $result[0] ?? [];
    }

    /**
     * Get projects by technology
     */
    public function getProjectsByTechnology($technology, $limit = 10) {
        $sql = "
            SELECT * FROM {$this->table}
            WHERE is_active = 1
            AND JSON_SEARCH(technologies, 'one', ?) IS NOT NULL
            ORDER BY created_at DESC
            LIMIT {$limit}
        ";

        $results = $this->query($sql, [$technology]);
        return array_map([$this, 'processProjectData'], $results);
    }

    /**
     * Get all technologies used
     */
    public function getAllTechnologies() {
        $sql = "
            SELECT technologies FROM {$this->table}
            WHERE is_active = 1 AND technologies IS NOT NULL
        ";

        $results = $this->query($sql);
        $allTechnologies = [];

        foreach ($results as $result) {
            $technologies = json_decode($result['technologies'], true);
            if (is_array($technologies)) {
                $allTechnologies = array_merge($allTechnologies, $technologies);
            }
        }

        return array_unique($allTechnologies);
    }

    /**
     * Get project categories with counts
     */
    public function getCategoriesWithCounts() {
        $sql = "
            SELECT c.*, COUNT(p.id) as project_count
            FROM categories c
            LEFT JOIN {$this->table} p ON c.id = p.category_id AND p.is_active = 1
            WHERE c.type = 'portfolio' AND c.is_active = 1
            GROUP BY c.id
            ORDER BY c.sort_order ASC, c.name ASC
        ";

        return $this->query($sql);
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured($id) {
        $project = $this->find($id);
        if (!$project) {
            return false;
        }

        $newStatus = !$project['is_featured'];
        $result = $this->update($id, ['is_featured' => $newStatus]);
        
        if ($result) {
            $status = $newStatus ? 'featured' : 'unfeatured';
            $this->logActivity($id, 'featured_toggled', "Project {$status}");
        }

        return $result;
    }

    /**
     * Update sort order
     */
    public function updateSortOrder($id, $sortOrder) {
        $result = $this->update($id, ['sort_order' => $sortOrder]);
        
        if ($result) {
            $this->logActivity($id, 'sort_updated', "Sort order updated to {$sortOrder}");
        }

        return $result;
    }

    /**
     * Get related projects
     */
    public function getRelatedProjects($projectId, $limit = 4) {
        $project = $this->find($projectId);
        if (!$project) {
            return [];
        }

        $sql = "
            SELECT * FROM {$this->table}
            WHERE is_active = 1 
            AND id != ?
            AND (category_id = ? OR project_type = ?)
            ORDER BY RAND()
            LIMIT {$limit}
        ";

        $results = $this->query($sql, [$projectId, $project['category_id'], $project['project_type']]);
        return array_map([$this, 'processProjectData'], $results);
    }

    /**
     * Process project data (decode JSON fields)
     */
    private function processProjectData($project) {
        if (isset($project['technologies']) && $project['technologies']) {
            $project['technologies'] = json_decode($project['technologies'], true) ?? [];
        }

        if (isset($project['gallery_images']) && $project['gallery_images']) {
            $project['gallery_images'] = json_decode($project['gallery_images'], true) ?? [];
        }

        return $project;
    }

    /**
     * Log activity
     */
    private function logActivity($projectId, $action, $description) {
        $sql = "
            INSERT INTO activity_logs (entity_type, entity_id, action, description, created_at) 
            VALUES ('portfolio', ?, ?, ?, NOW())
        ";
        
        $this->query($sql, [$projectId, $action, $description]);
    }

    /**
     * Validate project data
     */
    public function validateProjectData($data, $isUpdate = false) {
        $errors = [];

        // Required fields for new projects
        if (!$isUpdate) {
            $required = ['title', 'short_description', 'project_type'];
            $missing = $this->validateRequired($data, $required);
            
            if (!empty($missing)) {
                $errors['required'] = 'Missing required fields: ' . implode(', ', $missing);
            }
        }

        // Validate URLs if provided
        if (isset($data['project_url']) && $data['project_url'] && !filter_var($data['project_url'], FILTER_VALIDATE_URL)) {
            $errors['project_url'] = 'Invalid project URL format';
        }

        if (isset($data['github_url']) && $data['github_url'] && !filter_var($data['github_url'], FILTER_VALIDATE_URL)) {
            $errors['github_url'] = 'Invalid GitHub URL format';
        }

        // Validate dates
        if (isset($data['start_date']) && $data['start_date'] && !strtotime($data['start_date'])) {
            $errors['start_date'] = 'Invalid start date format';
        }

        if (isset($data['end_date']) && $data['end_date'] && !strtotime($data['end_date'])) {
            $errors['end_date'] = 'Invalid end date format';
        }

        // Validate date logic
        if (isset($data['start_date']) && isset($data['end_date']) && 
            $data['start_date'] && $data['end_date'] && 
            strtotime($data['start_date']) > strtotime($data['end_date'])) {
            $errors['dates'] = 'End date must be after start date';
        }

        return $errors;
    }
}
?>
