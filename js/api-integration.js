/**
 * KDTech Solutions - Frontend API Integration
 * Connects frontend forms to backend API endpoints
 */

class KDTechAPI {
    constructor() {
        this.baseURL = '/backend/api';
        this.init();
    }

    init() {
        this.bindContactForm();
        this.bindQuoteForm();
        this.bindOrderForms();
        this.loadPortfolio();
        this.loadProducts();
        this.loadServices();
    }

    /**
     * Contact Form Integration
     */
    bindContactForm() {
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(contactForm);
                const data = Object.fromEntries(formData.entries());
                
                try {
                    this.showLoading(contactForm);
                    const response = await this.makeRequest('/contact', {
                        method: 'POST',
                        body: JSON.stringify(data)
                    });
                    
                    if (response.success) {
                        this.showSuccess('Message sent successfully! We\'ll get back to you soon.');
                        contactForm.reset();
                    } else {
                        this.showError(response.message || 'Failed to send message');
                    }
                } catch (error) {
                    this.showError('Network error. Please try again.');
                } finally {
                    this.hideLoading(contactForm);
                }
            });
        }
    }

    /**
     * Quote Form Integration
     */
    bindQuoteForm() {
        const quoteForm = document.getElementById('quoteForm');
        if (quoteForm) {
            quoteForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const formData = new FormData(quoteForm);
                const data = Object.fromEntries(formData.entries());
                
                // Handle requirements checkboxes
                const requirements = [];
                quoteForm.querySelectorAll('input[name="requirements[]"]:checked').forEach(cb => {
                    requirements.push(cb.value);
                });
                data.requirements = requirements;
                
                try {
                    this.showLoading(quoteForm);
                    const response = await this.makeRequest('/quotes', {
                        method: 'POST',
                        body: JSON.stringify(data)
                    });
                    
                    if (response.success) {
                        this.showSuccess(`Quote request submitted! Reference: ${response.data.quote_number}`);
                        quoteForm.reset();
                    } else {
                        this.showError(response.message || 'Failed to submit quote request');
                    }
                } catch (error) {
                    this.showError('Network error. Please try again.');
                } finally {
                    this.hideLoading(quoteForm);
                }
            });
        }
    }

    /**
     * Order Form Integration
     */
    bindOrderForms() {
        // Product order buttons
        document.querySelectorAll('.order-product-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const productId = e.target.dataset.productId;
                const productName = e.target.dataset.productName;
                const productPrice = e.target.dataset.productPrice;
                
                this.openOrderModal({
                    type: 'product',
                    item_id: productId,
                    item_name: productName,
                    unit_price: productPrice
                });
            });
        });

        // Service order buttons
        document.querySelectorAll('.order-service-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const serviceId = e.target.dataset.serviceId;
                const serviceName = e.target.dataset.serviceName;
                
                this.openOrderModal({
                    type: 'service',
                    item_id: serviceId,
                    item_name: serviceName
                });
            });
        });

        // Order form submission
        const orderForm = document.getElementById('orderForm');
        if (orderForm) {
            orderForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.submitOrder(orderForm);
            });
        }
    }

    /**
     * Load Portfolio Projects
     */
    async loadPortfolio() {
        const portfolioContainer = document.getElementById('portfolio-container');
        if (!portfolioContainer) return;

        try {
            const response = await this.makeRequest('/portfolio?featured=1&limit=6');
            if (response.success && response.data) {
                this.renderPortfolio(response.data, portfolioContainer);
            }
        } catch (error) {
            console.error('Failed to load portfolio:', error);
        }
    }

    /**
     * Load Products
     */
    async loadProducts() {
        const productsContainer = document.getElementById('products-container');
        if (!productsContainer) return;

        try {
            const response = await this.makeRequest('/products?featured=1&limit=8');
            if (response.success && response.data) {
                this.renderProducts(response.data, productsContainer);
            }
        } catch (error) {
            console.error('Failed to load products:', error);
        }
    }

    /**
     * Load Services
     */
    async loadServices() {
        const servicesContainer = document.getElementById('services-container');
        if (!servicesContainer) return;

        try {
            const response = await this.makeRequest('/services?featured=1');
            if (response.success && response.data) {
                this.renderServices(response.data, servicesContainer);
            }
        } catch (error) {
            console.error('Failed to load services:', error);
        }
    }

    /**
     * Render Portfolio Projects
     */
    renderPortfolio(projects, container) {
        const html = projects.map(project => `
            <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up">
                <div class="portfolio-item">
                    <div class="portfolio-image">
                        <img src="${project.image_url || 'assets/img/portfolio/default.jpg'}" 
                             alt="${project.title}" class="img-fluid">
                        <div class="portfolio-overlay">
                            <div class="portfolio-info">
                                <h5>${project.title}</h5>
                                <p>${project.client_name}</p>
                                <div class="portfolio-links">
                                    <a href="#" class="btn btn-primary btn-sm" 
                                       onclick="viewProject(${project.id})">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    ${project.project_url ? `
                                        <a href="${project.project_url}" target="_blank" 
                                           class="btn btn-outline-light btn-sm">
                                            <i class="fas fa-external-link-alt"></i> Live Site
                                        </a>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="portfolio-content">
                        <h6>${project.title}</h6>
                        <p class="text-muted">${project.short_description}</p>
                        <div class="portfolio-tech">
                            ${(project.technologies || []).map(tech => 
                                `<span class="badge bg-primary">${tech}</span>`
                            ).join('')}
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
        
        container.innerHTML = html;
    }

    /**
     * Render Products
     */
    renderProducts(products, container) {
        const html = products.map(product => `
            <div class="col-lg-3 col-md-6 mb-4" data-aos="fade-up">
                <div class="product-card">
                    <div class="product-image">
                        <img src="${product.image_url || 'assets/img/products/default.jpg'}" 
                             alt="${product.name}" class="img-fluid">
                        ${product.sale_price ? '<span class="product-badge">Sale</span>' : ''}
                    </div>
                    <div class="product-content">
                        <h6>${product.name}</h6>
                        <p class="text-muted">${product.short_description}</p>
                        <div class="product-price">
                            ${product.sale_price ? `
                                <span class="current-price">KES ${this.formatPrice(product.sale_price)}</span>
                                <span class="original-price">KES ${this.formatPrice(product.price)}</span>
                            ` : `
                                <span class="current-price">KES ${this.formatPrice(product.price)}</span>
                            `}
                        </div>
                        <div class="product-actions">
                            <button class="btn btn-primary btn-sm order-product-btn" 
                                    data-product-id="${product.id}"
                                    data-product-name="${product.name}"
                                    data-product-price="${product.sale_price || product.price}">
                                <i class="fas fa-shopping-cart"></i> Order Now
                            </button>
                            <button class="btn btn-outline-primary btn-sm" 
                                    onclick="viewProduct(${product.id})">
                                <i class="fas fa-eye"></i> Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
        
        container.innerHTML = html;
        this.bindOrderForms(); // Re-bind order buttons
    }

    /**
     * Render Services
     */
    renderServices(services, container) {
        const html = services.map(service => `
            <div class="col-lg-4 col-md-6 mb-4" data-aos="fade-up">
                <div class="service-card">
                    <div class="service-icon">
                        <i class="${service.icon_class || 'fas fa-cog'}"></i>
                    </div>
                    <div class="service-content">
                        <h5>${service.title}</h5>
                        <p>${service.short_description}</p>
                        <ul class="service-features">
                            ${(service.features || []).map(feature => 
                                `<li><i class="fas fa-check"></i> ${feature}</li>`
                            ).join('')}
                        </ul>
                        <div class="service-actions">
                            <button class="btn btn-primary order-service-btn" 
                                    data-service-id="${service.id}"
                                    data-service-name="${service.title}">
                                <i class="fas fa-envelope"></i> Get Quote
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
        
        container.innerHTML = html;
        this.bindOrderForms(); // Re-bind order buttons
    }

    /**
     * Open Order Modal
     */
    openOrderModal(itemData) {
        const modal = document.getElementById('orderModal');
        if (modal) {
            // Populate modal with item data
            document.getElementById('order-item-name').textContent = itemData.item_name;
            document.getElementById('order-item-type').value = itemData.type;
            document.getElementById('order-item-id').value = itemData.item_id;
            
            if (itemData.unit_price) {
                document.getElementById('order-item-price').textContent = `KES ${this.formatPrice(itemData.unit_price)}`;
            }
            
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }
    }

    /**
     * Submit Order
     */
    async submitOrder(form) {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        
        // Prepare order items
        const items = [{
            item_type: data.order_item_type,
            item_id: data.order_item_id,
            item_name: data.order_item_name,
            quantity: parseInt(data.quantity) || 1,
            unit_price: parseFloat(data.unit_price) || 0,
            total_price: (parseInt(data.quantity) || 1) * (parseFloat(data.unit_price) || 0)
        }];
        
        const orderData = {
            customer_name: data.customer_name,
            customer_email: data.customer_email,
            customer_phone: data.customer_phone,
            company_name: data.company_name,
            billing_address: data.billing_address,
            order_type: data.order_item_type,
            notes: data.notes,
            items: items
        };
        
        try {
            this.showLoading(form);
            const response = await this.makeRequest('/orders', {
                method: 'POST',
                body: JSON.stringify(orderData)
            });
            
            if (response.success) {
                this.showSuccess(`Order placed successfully! Order number: ${response.data.order_number}`);
                form.reset();
                bootstrap.Modal.getInstance(document.getElementById('orderModal')).hide();
            } else {
                this.showError(response.message || 'Failed to place order');
            }
        } catch (error) {
            this.showError('Network error. Please try again.');
        } finally {
            this.hideLoading(form);
        }
    }

    /**
     * API Request Helper
     */
    async makeRequest(endpoint, options = {}) {
        const url = this.baseURL + endpoint;
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            },
        };
        
        const finalOptions = { ...defaultOptions, ...options };
        
        const response = await fetch(url, finalOptions);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    }

    /**
     * UI Helper Methods
     */
    showLoading(form) {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        }
    }

    hideLoading(form) {
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = submitBtn.dataset.originalText || 'Submit';
        }
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'danger');
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show notification-toast`;
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Add to notification container or body
        const container = document.querySelector('.notification-container') || document.body;
        container.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    formatPrice(price) {
        return new Intl.NumberFormat().format(price);
    }
}

// Global functions for modal interactions
window.viewProject = async function(projectId) {
    try {
        const api = new KDTechAPI();
        const response = await api.makeRequest(`/portfolio/${projectId}`);
        if (response.success) {
            showProjectModal(response.data);
        }
    } catch (error) {
        console.error('Failed to load project:', error);
    }
};

window.viewProduct = async function(productId) {
    try {
        const api = new KDTechAPI();
        const response = await api.makeRequest(`/products/${productId}`);
        if (response.success) {
            showProductModal(response.data);
        }
    } catch (error) {
        console.error('Failed to load product:', error);
    }
};

function showProjectModal(project) {
    // Implementation for project detail modal
    console.log('Show project modal:', project);
}

function showProductModal(product) {
    // Implementation for product detail modal
    console.log('Show product modal:', product);
}

// Initialize API integration when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.kdtechAPI = new KDTechAPI();
});
