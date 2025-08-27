// E-commerce functionality for KDTech Solutions
class ECommerceManager {
    constructor() {
        this.cart = JSON.parse(localStorage.getItem('kdtech_cart')) || [];
        this.products = [];
        this.currentPage = 1;
        this.itemsPerPage = 9;
        this.currentFilter = 'all';
        
        this.init();
    }
    
    init() {
        this.loadProducts();
        this.setupEventListeners();
        this.updateCartUI();
        this.renderProducts();
    }
    
    // Sample products data
    loadProducts() {
        this.products = [
            {
                id: 1,
                name: 'Gaming Desktop Pro',
                category: 'desktops',
                price: 185000,
                image: 'https://images.unsplash.com/photo-1587831990711-23ca6441447b?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                description: 'High-performance gaming desktop with RTX 4070',
                specs: ['Intel i7-13700K', 'RTX 4070 8GB', '32GB DDR5 RAM', '1TB NVMe SSD'],
                inStock: true,
                featured: true
            },
            {
                id: 2,
                name: 'Business Laptop Elite',
                category: 'laptops',
                price: 125000,
                image: 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                description: 'Professional laptop for business use',
                specs: ['Intel i7-1260P', '16GB RAM', '512GB SSD', '15.6" FHD Display'],
                inStock: true,
                featured: true
            },
            {
                id: 3,
                name: '4K Monitor 27"',
                category: 'accessories',
                price: 45000,
                image: 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                description: 'Professional 4K UHD monitor',
                specs: ['27" 4K UHD', 'IPS Panel', 'USB-C Hub', 'HDR Support'],
                inStock: true,
                featured: false
            },
            {
                id: 4,
                name: 'Server Rack Unit',
                category: 'servers',
                price: 285000,
                image: 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                description: 'Enterprise-grade server solution',
                specs: ['Dual Xeon Processors', '64GB ECC RAM', '4TB Storage', 'Redundant PSU'],
                inStock: true,
                featured: true
            },
            {
                id: 5,
                name: 'Wireless Keyboard & Mouse',
                category: 'accessories',
                price: 8500,
                image: 'https://images.unsplash.com/photo-1541140532154-b024d705b90a?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                description: 'Ergonomic wireless combo set',
                specs: ['2.4GHz Wireless', 'Ergonomic Design', 'Long Battery Life', 'Quiet Keys'],
                inStock: true,
                featured: false
            },
            {
                id: 6,
                name: 'Workstation Desktop',
                category: 'desktops',
                price: 225000,
                image: 'https://images.unsplash.com/photo-1560472354-b33ff0c44a43?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80',
                description: 'Professional workstation for content creation',
                specs: ['Intel i9-13900K', 'RTX 4080', '64GB RAM', '2TB NVMe SSD'],
                inStock: true,
                featured: false
            }
        ];
    }
    
    setupEventListeners() {
        // Category filters
        document.querySelectorAll('input[name="category"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.currentFilter = e.target.id;
                this.currentPage = 1;
                this.renderProducts();
            });
        });
        
        // Search functionality
        const searchInput = document.getElementById('productSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.searchProducts(e.target.value);
            });
        }
        
        // Load more button
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', () => {
                this.currentPage++;
                this.renderProducts(true);
            });
        }
        
        // Payment method change
        document.querySelectorAll('input[name="paymentMethod"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.showPaymentDetails(e.target.value);
            });
        });
        
        // Checkout button
        const placeOrderBtn = document.getElementById('placeOrderBtn');
        if (placeOrderBtn) {
            placeOrderBtn.addEventListener('click', () => {
                this.processOrder();
            });
        }
        
        // Custom build form
        const submitCustomBuildBtn = document.getElementById('submitCustomBuildBtn');
        if (submitCustomBuildBtn) {
            submitCustomBuildBtn.addEventListener('click', () => {
                this.submitCustomBuild();
            });
        }
    }
    
    renderProducts(append = false) {
        const grid = document.getElementById('productsGrid');
        if (!grid) return;
        
        let filteredProducts = this.products;
        
        // Apply category filter
        if (this.currentFilter !== 'all') {
            filteredProducts = filteredProducts.filter(p => p.category === this.currentFilter);
        }
        
        // Pagination
        const startIndex = (this.currentPage - 1) * this.itemsPerPage;
        const endIndex = startIndex + this.itemsPerPage;
        const productsToShow = filteredProducts.slice(0, endIndex);
        
        if (!append) {
            grid.innerHTML = '';
        }
        
        productsToShow.forEach((product, index) => {
            if (append && index < startIndex) return;
            
            const productCard = this.createProductCard(product);
            grid.appendChild(productCard);
        });
        
        // Update load more button
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        if (loadMoreBtn) {
            if (productsToShow.length >= filteredProducts.length) {
                loadMoreBtn.style.display = 'none';
            } else {
                loadMoreBtn.style.display = 'block';
            }
        }
    }
    
    createProductCard(product) {
        const div = document.createElement('div');
        div.className = 'col-lg-4 col-md-6 product-item';
        div.setAttribute('data-category', product.category);
        div.setAttribute('data-aos', 'fade-up');
        
        div.innerHTML = `
            <div class="product-card h-100">
                <div class="product-image">
                    <img src="${product.image}" alt="${product.name}" class="img-fluid">
                    <div class="product-overlay">
                        <button class="btn btn-light btn-sm me-2" onclick="ecommerce.quickView(${product.id})">
                            <i class="fas fa-eye"></i> Quick View
                        </button>
                        ${product.inStock ? 
                            `<button class="btn btn-primary btn-sm" onclick="ecommerce.addToCart(${product.id})">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>` :
                            `<button class="btn btn-secondary btn-sm" disabled>
                                <i class="fas fa-times"></i> Out of Stock
                            </button>`
                        }
                    </div>
                    ${product.featured ? '<span class="product-badge">Featured</span>' : ''}
                </div>
                <div class="product-info p-3">
                    <h5 class="product-title">${product.name}</h5>
                    <p class="product-description text-muted">${product.description}</p>
                    <div class="product-specs mb-3">
                        ${product.specs.slice(0, 2).map(spec => `<small class="badge bg-light text-dark me-1">${spec}</small>`).join('')}
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="product-price">
                            <span class="price fw-bold text-primary">KES ${product.price.toLocaleString()}</span>
                        </div>
                        <div class="product-actions">
                            ${product.inStock ? 
                                `<button class="btn btn-outline-primary btn-sm" onclick="ecommerce.addToCart(${product.id})">
                                    <i class="fas fa-cart-plus"></i>
                                </button>` :
                                `<span class="text-muted small">Out of Stock</span>`
                            }
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        return div;
    }
    
    addToCart(productId) {
        const product = this.products.find(p => p.id === productId);
        if (!product || !product.inStock) return;
        
        const existingItem = this.cart.find(item => item.id === productId);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            this.cart.push({
                id: product.id,
                name: product.name,
                price: product.price,
                image: product.image,
                quantity: 1
            });
        }
        
        this.saveCart();
        this.updateCartUI();
        this.showNotification(`${product.name} added to cart!`, 'success');
    }
    
    removeFromCart(productId) {
        this.cart = this.cart.filter(item => item.id !== productId);
        this.saveCart();
        this.updateCartUI();
        this.renderCartItems();
    }
    
    updateQuantity(productId, quantity) {
        const item = this.cart.find(item => item.id === productId);
        if (item) {
            if (quantity <= 0) {
                this.removeFromCart(productId);
            } else {
                item.quantity = quantity;
                this.saveCart();
                this.updateCartUI();
                this.renderCartItems();
            }
        }
    }
    
    updateCartUI() {
        const cartCount = document.getElementById('cartCount');
        const cartTotal = document.getElementById('cartTotal');
        
        const totalItems = this.cart.reduce((sum, item) => sum + item.quantity, 0);
        const totalPrice = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        
        if (cartCount) {
            cartCount.textContent = totalItems;
            cartCount.style.display = totalItems > 0 ? 'block' : 'none';
        }
        
        if (cartTotal) {
            cartTotal.textContent = `KES ${totalPrice.toLocaleString()}`;
        }
        
        // Show/hide cart sections
        const cartEmpty = document.getElementById('cartEmpty');
        const cartSummary = document.getElementById('cartSummary');
        
        if (cartEmpty && cartSummary) {
            if (this.cart.length === 0) {
                cartEmpty.style.display = 'block';
                cartSummary.style.display = 'none';
            } else {
                cartEmpty.style.display = 'none';
                cartSummary.style.display = 'block';
            }
        }
        
        this.renderCartItems();
    }
    
    renderCartItems() {
        const cartItems = document.getElementById('cartItems');
        if (!cartItems) return;
        
        if (this.cart.length === 0) {
            cartItems.innerHTML = '';
            return;
        }
        
        cartItems.innerHTML = this.cart.map(item => `
            <div class="cart-item d-flex align-items-center mb-3 p-3 border rounded">
                <img src="${item.image}" alt="${item.name}" class="cart-item-image me-3" style="width: 60px; height: 60px; object-fit: cover;">
                <div class="cart-item-details flex-grow-1">
                    <h6 class="mb-1">${item.name}</h6>
                    <p class="mb-1 text-muted small">KES ${item.price.toLocaleString()}</p>
                    <div class="quantity-controls d-flex align-items-center">
                        <button class="btn btn-sm btn-outline-secondary" onclick="ecommerce.updateQuantity(${item.id}, ${item.quantity - 1})">-</button>
                        <span class="mx-2">${item.quantity}</span>
                        <button class="btn btn-sm btn-outline-secondary" onclick="ecommerce.updateQuantity(${item.id}, ${item.quantity + 1})">+</button>
                    </div>
                </div>
                <div class="cart-item-actions">
                    <button class="btn btn-sm btn-outline-danger" onclick="ecommerce.removeFromCart(${item.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
        
        // Update checkout summary
        this.updateCheckoutSummary();
    }
    
    updateCheckoutSummary() {
        const checkoutSummary = document.getElementById('checkoutSummary');
        if (!checkoutSummary) return;
        
        const subtotal = this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const shipping = subtotal > 50000 ? 0 : 2000; // Free shipping over KES 50,000
        const total = subtotal + shipping;
        
        checkoutSummary.innerHTML = `
            <div class="order-summary">
                ${this.cart.map(item => `
                    <div class="d-flex justify-content-between mb-2">
                        <span>${item.name} x${item.quantity}</span>
                        <span>KES ${(item.price * item.quantity).toLocaleString()}</span>
                    </div>
                `).join('')}
                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <span>KES ${subtotal.toLocaleString()}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Shipping:</span>
                    <span>${shipping === 0 ? 'Free' : 'KES ' + shipping.toLocaleString()}</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between fw-bold">
                    <span>Total:</span>
                    <span>KES ${total.toLocaleString()}</span>
                </div>
            </div>
        `;
    }
    
    showPaymentDetails(method) {
        // Hide all payment details
        document.querySelectorAll('.payment-details').forEach(el => {
            el.style.display = 'none';
        });
        
        // Show selected payment method details
        const detailsEl = document.getElementById(method + 'Details');
        if (detailsEl) {
            detailsEl.style.display = 'block';
        }
    }
    
    async processOrder() {
        const form = document.getElementById('checkoutForm');
        const formData = new FormData(form);
        
        // Validate form
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        // Prepare order data
        const orderData = {
            customer: {
                fullName: formData.get('fullName'),
                email: formData.get('email'),
                phone: formData.get('phone'),
                address: formData.get('address'),
                city: formData.get('city'),
                postalCode: formData.get('postalCode')
            },
            items: this.cart,
            payment: {
                method: formData.get('paymentMethod'),
                mpesaPhone: formData.get('mpesaPhone'),
                cardNumber: formData.get('cardNumber'),
                expiryDate: formData.get('expiryDate'),
                cvv: formData.get('cvv')
            },
            total: this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0)
        };
        
        try {
            // Show loading
            const placeOrderBtn = document.getElementById('placeOrderBtn');
            const originalText = placeOrderBtn.innerHTML;
            placeOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            placeOrderBtn.disabled = true;
            
            // Simulate API call (replace with actual API endpoint)
            const response = await fetch('/backend/api/orders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(orderData)
            });
            
            if (response.ok) {
                const result = await response.json();
                this.showNotification('Order placed successfully! You will receive a confirmation email shortly.', 'success');
                
                // Clear cart
                this.cart = [];
                this.saveCart();
                this.updateCartUI();
                
                // Close modals
                bootstrap.Modal.getInstance(document.getElementById('checkoutModal')).hide();
                bootstrap.Offcanvas.getInstance(document.getElementById('cartOffcanvas')).hide();
                
                // Redirect or show order confirmation
                setTimeout(() => {
                    window.location.href = '/order-confirmation.html?order=' + result.orderId;
                }, 2000);
            } else {
                throw new Error('Order processing failed');
            }
        } catch (error) {
            console.error('Order error:', error);
            this.showNotification('Failed to process order. Please try again.', 'error');
        } finally {
            // Reset button
            const placeOrderBtn = document.getElementById('placeOrderBtn');
            placeOrderBtn.innerHTML = originalText;
            placeOrderBtn.disabled = false;
        }
    }
    
    async submitCustomBuild() {
        const form = document.getElementById('customBuildForm');
        const formData = new FormData(form);
        
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        const buildData = {
            name: formData.get('name'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            budget: formData.get('budget'),
            buildType: formData.get('buildType'),
            primaryUse: formData.get('primaryUse'),
            requirements: formData.get('requirements')
        };
        
        try {
            const submitBtn = document.getElementById('submitCustomBuildBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
            submitBtn.disabled = true;
            
            // Simulate API call
            const response = await fetch('/backend/api/custom-builds', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(buildData)
            });
            
            if (response.ok) {
                this.showNotification('Custom build request submitted! We will contact you within 24 hours.', 'success');
                bootstrap.Modal.getInstance(document.getElementById('customBuildModal')).hide();
                form.reset();
            } else {
                throw new Error('Submission failed');
            }
        } catch (error) {
            console.error('Custom build error:', error);
            this.showNotification('Failed to submit request. Please try again.', 'error');
        } finally {
            const submitBtn = document.getElementById('submitCustomBuildBtn');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }
    
    quickView(productId) {
        const product = this.products.find(p => p.id === productId);
        if (!product) return;
        
        // Create and show quick view modal (simplified)
        alert(`${product.name}\n\nPrice: KES ${product.price.toLocaleString()}\n\nSpecs:\n${product.specs.join('\n')}\n\n${product.description}`);
    }
    
    searchProducts(query) {
        if (!query.trim()) {
            this.renderProducts();
            return;
        }
        
        const filteredProducts = this.products.filter(product =>
            product.name.toLowerCase().includes(query.toLowerCase()) ||
            product.description.toLowerCase().includes(query.toLowerCase()) ||
            product.specs.some(spec => spec.toLowerCase().includes(query.toLowerCase()))
        );
        
        const grid = document.getElementById('productsGrid');
        if (!grid) return;
        
        grid.innerHTML = '';
        
        if (filteredProducts.length === 0) {
            grid.innerHTML = `
                <div class="col-12 text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5>No products found</h5>
                    <p class="text-muted">Try adjusting your search terms</p>
                </div>
            `;
            return;
        }
        
        filteredProducts.forEach(product => {
            const productCard = this.createProductCard(product);
            grid.appendChild(productCard);
        });
    }
    
    saveCart() {
        localStorage.setItem('kdtech_cart', JSON.stringify(this.cart));
    }
    
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
}

// Initialize e-commerce functionality
let ecommerce;
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AOS
    AOS.init({
        duration: 1000,
        once: true
    });
    
    // Initialize e-commerce
    ecommerce = new ECommerceManager();
});
