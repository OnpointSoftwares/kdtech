/**
 * KDTech Solutions - Admin Panel JavaScript
 * Interactive functionality for the admin interface
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize admin functionality
    initializeAdmin();
});

function initializeAdmin() {
    // Mobile sidebar toggle
    initializeMobileSidebar();
    
    // Initialize tooltips
    initializeTooltips();
    
    // Initialize form validation
    initializeFormValidation();
    
    // Initialize data tables
    initializeDataTables();
    
    // Initialize notifications
    initializeNotifications();
    
    // Auto-refresh dashboard stats
    initializeAutoRefresh();
}

/**
 * Mobile Sidebar Toggle
 */
function initializeMobileSidebar() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            if (overlay) {
                overlay.classList.toggle('show');
            }
        });
    }
    
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }
}

/**
 * Initialize Bootstrap Tooltips
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Form Validation
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
}

/**
 * Data Tables Enhancement
 */
function initializeDataTables() {
    const tables = document.querySelectorAll('.data-table');
    
    tables.forEach(function(table) {
        // Add search functionality
        addTableSearch(table);
        
        // Add sorting functionality
        addTableSorting(table);
        
        // Add row selection
        addRowSelection(table);
    });
}

function addTableSearch(table) {
    const searchInput = table.parentElement.querySelector('.table-search');
    if (!searchInput) return;
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(function(row) {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });
}

function addTableSorting(table) {
    const headers = table.querySelectorAll('th[data-sortable]');
    
    headers.forEach(function(header, index) {
        header.style.cursor = 'pointer';
        header.innerHTML += ' <i class="fas fa-sort text-muted"></i>';
        
        header.addEventListener('click', function() {
            sortTable(table, index, header);
        });
    });
}

function sortTable(table, columnIndex, header) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const isAscending = !header.classList.contains('sort-asc');
    
    // Reset all headers
    table.querySelectorAll('th').forEach(function(th) {
        th.classList.remove('sort-asc', 'sort-desc');
        const icon = th.querySelector('i');
        if (icon) {
            icon.className = 'fas fa-sort text-muted';
        }
    });
    
    // Sort rows
    rows.sort(function(a, b) {
        const aText = a.cells[columnIndex].textContent.trim();
        const bText = b.cells[columnIndex].textContent.trim();
        
        const aValue = isNaN(aText) ? aText.toLowerCase() : parseFloat(aText);
        const bValue = isNaN(bText) ? bText.toLowerCase() : parseFloat(bText);
        
        if (isAscending) {
            return aValue > bValue ? 1 : -1;
        } else {
            return aValue < bValue ? 1 : -1;
        }
    });
    
    // Update header styling
    header.classList.add(isAscending ? 'sort-asc' : 'sort-desc');
    const icon = header.querySelector('i');
    if (icon) {
        icon.className = `fas fa-sort-${isAscending ? 'up' : 'down'} text-primary`;
    }
    
    // Reorder rows in DOM
    rows.forEach(function(row) {
        tbody.appendChild(row);
    });
}

function addRowSelection(table) {
    const selectAllCheckbox = table.querySelector('th input[type="checkbox"]');
    const rowCheckboxes = table.querySelectorAll('td input[type="checkbox"]');
    
    if (selectAllCheckbox && rowCheckboxes.length > 0) {
        selectAllCheckbox.addEventListener('change', function() {
            rowCheckboxes.forEach(function(checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
                toggleRowSelection(checkbox.closest('tr'), checkbox.checked);
            });
            updateBulkActions();
        });
        
        rowCheckboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                toggleRowSelection(this.closest('tr'), this.checked);
                updateSelectAllState();
                updateBulkActions();
            });
        });
    }
}

function toggleRowSelection(row, selected) {
    if (selected) {
        row.classList.add('table-row-selected');
    } else {
        row.classList.remove('table-row-selected');
    }
}

function updateSelectAllState() {
    const selectAllCheckbox = document.querySelector('th input[type="checkbox"]');
    const rowCheckboxes = document.querySelectorAll('td input[type="checkbox"]');
    
    if (selectAllCheckbox && rowCheckboxes.length > 0) {
        const checkedCount = Array.from(rowCheckboxes).filter(cb => cb.checked).length;
        selectAllCheckbox.checked = checkedCount === rowCheckboxes.length;
        selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < rowCheckboxes.length;
    }
}

function updateBulkActions() {
    const selectedCount = document.querySelectorAll('td input[type="checkbox"]:checked').length;
    const bulkActions = document.querySelector('.bulk-actions');
    
    if (bulkActions) {
        if (selectedCount > 0) {
            bulkActions.style.display = 'block';
            bulkActions.querySelector('.selected-count').textContent = selectedCount;
        } else {
            bulkActions.style.display = 'none';
        }
    }
}

/**
 * Notifications System
 */
function initializeNotifications() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            fadeOut(alert);
        }, 5000);
    });
}

function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show notification-toast`;
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.notification-container') || document.body;
    container.appendChild(notification);
    
    if (duration > 0) {
        setTimeout(function() {
            fadeOut(notification);
        }, duration);
    }
}

function fadeOut(element) {
    element.style.transition = 'opacity 0.5s ease';
    element.style.opacity = '0';
    setTimeout(function() {
        if (element.parentNode) {
            element.parentNode.removeChild(element);
        }
    }, 500);
}

/**
 * Auto-refresh Dashboard Stats
 */
function initializeAutoRefresh() {
    if (document.querySelector('.dashboard-content')) {
        // Refresh stats every 5 minutes
        setInterval(refreshDashboardStats, 300000);
    }
}

function refreshDashboardStats() {
    fetch('api/stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatCards(data.data);
            }
        })
        .catch(error => {
            console.error('Error refreshing stats:', error);
        });
}

function updateStatCards(stats) {
    // Update order stats
    if (stats.orders) {
        updateStatCard('total-orders', stats.orders.total_orders);
        updateStatCard('pending-orders', stats.orders.pending_orders);
        updateStatCard('total-revenue', 'KES ' + formatNumber(stats.orders.total_revenue));
    }
    
    // Update portfolio stats
    if (stats.portfolio) {
        updateStatCard('total-projects', stats.portfolio.total_projects);
        updateStatCard('featured-projects', stats.portfolio.featured_projects);
    }
    
    // Update product stats
    if (stats.products) {
        updateStatCard('active-products', stats.products.active_products);
        updateStatCard('low-stock-products', stats.products.low_stock_products);
    }
}

function updateStatCard(id, value) {
    const element = document.getElementById(id);
    if (element) {
        element.textContent = value;
        element.classList.add('stat-updated');
        setTimeout(() => element.classList.remove('stat-updated'), 1000);
    }
}

/**
 * Utility Functions
 */
function formatNumber(num) {
    return new Intl.NumberFormat().format(num);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString();
}

function formatDateTime(dateString) {
    return new Date(dateString).toLocaleString();
}

/**
 * AJAX Helper Functions
 */
function makeRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        },
    };
    
    const finalOptions = { ...defaultOptions, ...options };
    
    return fetch(url, finalOptions)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        });
}

function showLoading(element) {
    element.classList.add('loading');
    const spinner = document.createElement('div');
    spinner.className = 'spinner-border spinner-border-sm me-2';
    element.insertBefore(spinner, element.firstChild);
}

function hideLoading(element) {
    element.classList.remove('loading');
    const spinner = element.querySelector('.spinner-border');
    if (spinner) {
        spinner.remove();
    }
}

/**
 * Form Helpers
 */
function serializeForm(form) {
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        if (data[key]) {
            if (Array.isArray(data[key])) {
                data[key].push(value);
            } else {
                data[key] = [data[key], value];
            }
        } else {
            data[key] = value;
        }
    }
    
    return data;
}

function resetForm(form) {
    form.reset();
    form.classList.remove('was-validated');
    const errorMessages = form.querySelectorAll('.invalid-feedback');
    errorMessages.forEach(msg => msg.style.display = 'none');
}

/**
 * Modal Helpers
 */
function openModal(modalId, data = {}) {
    const modal = document.getElementById(modalId);
    if (modal) {
        // Populate modal with data if provided
        Object.keys(data).forEach(key => {
            const element = modal.querySelector(`[name="${key}"]`);
            if (element) {
                element.value = data[key];
            }
        });
        
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) {
            bsModal.hide();
        }
    }
}

/**
 * Export Functions
 */
window.AdminJS = {
    showNotification,
    makeRequest,
    showLoading,
    hideLoading,
    openModal,
    closeModal,
    serializeForm,
    resetForm,
    formatNumber,
    formatDate,
    formatDateTime
};
