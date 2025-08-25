// Theme Switcher JavaScript
// Enhances theme switching experience with smooth transitions and localStorage

document.addEventListener('DOMContentLoaded', function() {
    
    // Theme configuration
    const themes = {
        'default': {
            name: 'Default',
            file: 'index.html',
            icon: 'fas fa-building',
            description: 'Professional & Corporate'
        },
        'alternative': {
            name: 'Alternative',
            file: 'index-alt.html',
            icon: 'fas fa-rocket',
            description: 'Modern & Creative'
        },
        'dark': {
            name: 'Dark',
            file: 'index-dark.html',
            icon: 'fas fa-moon',
            description: 'Futuristic & Tech-Focused'
        }
    };
    
    // Get current theme based on filename
    function getCurrentTheme() {
        const path = window.location.pathname;
        const filename = path.split('/').pop();
        
        if (filename === 'index-alt.html') return 'alternative';
        if (filename === 'index-dark.html') return 'dark';
        return 'default';
    }
    
    // Save theme preference to localStorage
    function saveThemePreference(theme) {
        try {
            localStorage.setItem('kdtech-theme-preference', theme);
        } catch (e) {
            console.log('LocalStorage not available');
        }
    }
    
    // Get saved theme preference
    function getSavedThemePreference() {
        try {
            return localStorage.getItem('kdtech-theme-preference') || 'default';
        } catch (e) {
            return 'default';
        }
    }
    
    // Add smooth transition effect when switching themes
    function addTransitionEffect() {
        document.body.style.transition = 'opacity 0.3s ease';
        document.body.style.opacity = '0';
        
        setTimeout(() => {
            document.body.style.opacity = '1';
        }, 100);
    }
    
    // Handle theme switching
    function switchTheme(targetTheme) {
        if (targetTheme === getCurrentTheme()) return;
        
        // Save preference
        saveThemePreference(targetTheme);
        
        // Add transition effect
        addTransitionEffect();
        
        // Navigate to new theme
        setTimeout(() => {
            window.location.href = themes[targetTheme].file;
        }, 150);
    }
    
    // Initialize theme switcher
    function initThemeSwitcher() {
        const currentTheme = getCurrentTheme();
        
        // Update active states in dropdown
        const dropdownItems = document.querySelectorAll('#themeDropdown + .dropdown-menu .dropdown-item');
        dropdownItems.forEach(item => {
            const href = item.getAttribute('href');
            item.classList.remove('active');
            
            if ((href === 'index.html' && currentTheme === 'default') ||
                (href === 'index-alt.html' && currentTheme === 'alternative') ||
                (href === 'index-dark.html' && currentTheme === 'dark')) {
                item.classList.add('active');
            }
            
            // Add click handlers
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const targetTheme = href === 'index.html' ? 'default' : 
                                  href === 'index-alt.html' ? 'alternative' : 'dark';
                switchTheme(targetTheme);
            });
        });
        
        // Update footer theme switcher links
        const footerLinks = document.querySelectorAll('.theme-switcher a');
        footerLinks.forEach(link => {
            const href = link.getAttribute('href');
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetTheme = href === 'index.html' ? 'default' : 
                                  href === 'index-alt.html' ? 'alternative' : 'dark';
                switchTheme(targetTheme);
            });
        });
    }
    
    // Add theme preview on hover
    function addThemePreview() {
        const dropdownItems = document.querySelectorAll('#themeDropdown + .dropdown-menu .dropdown-item');
        
        dropdownItems.forEach(item => {
            const href = item.getAttribute('href');
            const themeKey = href === 'index.html' ? 'default' : 
                           href === 'index-alt.html' ? 'alternative' : 'dark';
            const theme = themes[themeKey];
            
            // Add tooltip with theme description
            item.setAttribute('title', theme.description);
            item.setAttribute('data-bs-toggle', 'tooltip');
            item.setAttribute('data-bs-placement', 'left');
            
            // Initialize Bootstrap tooltip
            if (typeof bootstrap !== 'undefined') {
                new bootstrap.Tooltip(item);
            }
        });
    }
    
    // Add keyboard shortcuts for theme switching
    function addKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Alt + 1, 2, 3 for theme switching
            if (e.altKey && !e.ctrlKey && !e.shiftKey) {
                switch (e.key) {
                    case '1':
                        e.preventDefault();
                        switchTheme('default');
                        break;
                    case '2':
                        e.preventDefault();
                        switchTheme('alternative');
                        break;
                    case '3':
                        e.preventDefault();
                        switchTheme('dark');
                        break;
                }
            }
        });
    }
    
    // Show theme switching notification
    function showThemeNotification(themeName) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'theme-notification';
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-palette me-2"></i>
                Switched to ${themeName} theme
            </div>
        `;
        
        // Add styles
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            background: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            z-index: 10000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Animate out and remove
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 2000);
    }
    
    // Check if user should be redirected to preferred theme
    function checkThemePreference() {
        const savedTheme = getSavedThemePreference();
        const currentTheme = getCurrentTheme();
        
        // Only redirect if it's the first visit to the site (no specific theme requested)
        if (savedTheme !== currentTheme && window.location.search === '' && window.location.hash === '') {
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.has('theme')) {
                // Show a subtle notification instead of auto-redirecting
                setTimeout(() => {
                    showThemeNotification(`Your preferred theme is ${themes[savedTheme].name}. Click Themes to switch.`);
                }, 2000);
            }
        }
    }
    
    // Initialize everything
    initThemeSwitcher();
    addThemePreview();
    addKeyboardShortcuts();
    checkThemePreference();
    
    // Add smooth page transitions
    window.addEventListener('beforeunload', () => {
        document.body.style.opacity = '0';
    });
    
    // Fade in page on load
    window.addEventListener('load', () => {
        document.body.style.transition = 'opacity 0.5s ease';
        document.body.style.opacity = '1';
    });
});

// Export functions for external use
window.KDTechThemes = {
    switchTheme: function(theme) {
        const event = new CustomEvent('themeSwitch', { detail: { theme } });
        document.dispatchEvent(event);
    },
    getCurrentTheme: function() {
        const path = window.location.pathname;
        const filename = path.split('/').pop();
        
        if (filename === 'index-alt.html') return 'alternative';
        if (filename === 'index-dark.html') return 'dark';
        return 'default';
    }
};
