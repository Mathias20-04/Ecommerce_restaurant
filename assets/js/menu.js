// assets/js/menu.js
class MenuManager {
    constructor() {
        this.meals = [];
        this.categories = [];
        this.filteredMeals = [];
        this.currentCategory = null;
        this.searchTimeout = null;
        this.currentSlide = 0;
        this.sliderInterval = null;
        this.init();
    }

    async init() {
        await this.loadCategories();
        await this.loadMeals();
        this.renderCategories();
        this.setupEventListeners();
        this.startSlider();
    }

    async loadCategories() {
        try {
            const data = await app.apiCall('/categories/list.php');
            this.categories = data.data.categories;
            console.log('Loaded categories:', this.categories);
        } catch (error) {
            console.error('Failed to load categories:', error);
            this.showError('Failed to load categories');
        }
    }

    async loadMeals() {
        try {
            const data = await app.apiCall('/meals/list.php');
            this.meals = data.data.meals;
            console.log('Loaded meals:', this.meals);
        } catch (error) {
            console.error('Failed to load meals:', error);
            this.showError('Failed to load meals');
        }
    }

    // Slider functionality
    startSlider() {
        this.sliderInterval = setInterval(() => {
            this.nextSlide();
        }, 5000);
    }

    nextSlide() {
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.slider-dot');
        
        if (slides.length === 0) return;
        
        slides[this.currentSlide].classList.remove('active');
        dots[this.currentSlide].classList.remove('active');
        
        this.currentSlide = (this.currentSlide + 1) % slides.length;
        
        slides[this.currentSlide].classList.add('active');
        dots[this.currentSlide].classList.add('active');
    }

    goToSlide(slideIndex) {
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.slider-dot');
        
        if (slides.length === 0) return;
        
        slides[this.currentSlide].classList.remove('active');
        dots[this.currentSlide].classList.remove('active');
        
        this.currentSlide = slideIndex;
        
        slides[this.currentSlide].classList.add('active');
        dots[this.currentSlide].classList.add('active');
        
        clearInterval(this.sliderInterval);
        this.startSlider();
    }

    // Unified Search - Handles both global and category-specific search
    performSearch(searchTerm) {
        clearTimeout(this.searchTimeout);
        
        this.searchTimeout = setTimeout(() => {
            if (!searchTerm.trim()) {
                this.hideSearchResults();
                // If we're in a category view and search is cleared, show all category meals
                if (this.currentCategory) {
                    this.renderMeals();
                }
                return;
            }

            const term = searchTerm.toLowerCase();
            
            // Determine what to search based on current context
            let results;
            if (this.currentCategory) {
                // Search within current category
                results = this.filteredMeals.filter(meal => 
                    meal.meal_name.toLowerCase().includes(term) ||
                    (meal.meal_description && meal.meal_description.toLowerCase().includes(term))
                );
                this.renderFilteredMeals(results);
            } else {
                // Global search - search all meals
                results = this.meals.filter(meal => 
                    meal.meal_name.toLowerCase().includes(term) ||
                    (meal.meal_description && meal.meal_description.toLowerCase().includes(term))
                );
                this.showSearchResults(results, searchTerm);
            }
        }, 300);
    }

    showSearchResults(results, searchTerm) {
        const resultsContainer = document.getElementById('search-results');
        
        if (results.length === 0) {
            resultsContainer.innerHTML = `
                <div class="no-results">
                    No meals found for "${searchTerm}"
                </div>
            `;
        } else {
            resultsContainer.innerHTML = results.slice(0, 8).map(meal => `
                <div class="search-result-item" data-meal-id="${meal.meal_id}" data-category-id="${meal.category_id}">
                    <div class="search-result-content">
                        <div class="search-result-name">${meal.meal_name}</div>
                        <div class="search-result-category">
                            ${meal.category_name || 'Uncategorized'}
                        </div>
                    </div>
                    <div class="search-result-price">MK ${parseFloat(meal.price).toFixed(2)}</div>
                </div>
            `).join('');
        }
        
        resultsContainer.classList.add('active');
    }

    hideSearchResults() {
        const resultsContainer = document.getElementById('search-results');
        resultsContainer.classList.remove('active');
    }

    renderFilteredMeals(filteredMeals) {
        const mealsGrid = document.getElementById('meals-grid');
        const noMeals = document.getElementById('no-meals');
        const loading = document.getElementById('meals-loading');
        
        if (loading) loading.classList.add('hidden');

        if (filteredMeals.length === 0) {
            mealsGrid.innerHTML = '';
            if (noMeals) noMeals.classList.remove('hidden');
            return;
        }
        
        if (noMeals) noMeals.classList.add('hidden');
        
        mealsGrid.innerHTML = filteredMeals.map(meal => `
            <div class="meal-card">
                ${meal.image_url ? 
                    `<img src="${meal.image_url}" alt="${meal.meal_name}" class="meal-image">` : 
                    '<div class="meal-image" style="background: linear-gradient(135deg, #667eea, #764ba2); display:flex; align-items:center; justify-content:center; color:white; font-size:3rem;"><i class="fas fa-utensils"></i></div>'
                }
                <div class="meal-content">
                    <h3 class="meal-name">${meal.meal_name}</h3>
                    <p class="meal-description">${meal.meal_description || 'A delicious meal prepared with fresh ingredients and authentic flavors.'}</p>
                    <div class="meal-footer">
                        <div>
                            <div class="meal-price">MK ${parseFloat(meal.price).toFixed(2)}</div>
                            ${meal.preparation_time ? `<div class="meal-time"><i class="fas fa-clock"></i> ${meal.preparation_time} mins</div>` : ''}
                        </div>
                        ${meal.is_available ? 
                            `<button class="btn btn-primary btn-sm add-to-cart" data-meal-id="${meal.meal_id}">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>` :
                            `<button class="btn btn-outline btn-sm" disabled>
                                <i class="fas fa-times-circle"></i> Out of Stock
                            </button>`
                        }
                    </div>
                </div>
            </div>
        `).join('');
    }

    handleSearchResultClick(mealId, categoryId) {
        console.log('Search result clicked - Meal ID:', mealId, 'Category ID:', categoryId);
        
        // First, show the category that contains this meal
        this.showCategoryMeals(categoryId);
        
        // Hide search results and clear search input
        this.hideSearchResults();
        document.getElementById('global-search-input').value = '';
        
        // Scroll to the specific meal (optional enhancement)
        setTimeout(() => {
            const mealElement = document.querySelector(`[data-meal-id="${mealId}"]`);
            if (mealElement) {
                mealElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Add highlight effect
                mealElement.style.boxShadow = '0 0 0 3px rgba(231, 76, 60, 0.3)';
                setTimeout(() => {
                    mealElement.style.boxShadow = '';
                }, 2000);
            }
        }, 500);
    }

    renderCategories() {
        const categoriesGrid = document.getElementById('categories-grid');
        const loading = document.getElementById('categories-loading');
        
        if (!categoriesGrid) return;
        
        if (loading) loading.style.display = 'none';

        if (this.categories.length === 0) {
            categoriesGrid.innerHTML = '<p class="text-center">No categories available.</p>';
            return;
        }
        
        categoriesGrid.innerHTML = this.categories.map(category => {
            let mealCount = 0;
            this.meals.forEach(meal => {
                if (meal.category_id == category.category_id) {
                    mealCount++;
                }
            });
            
            return `
                <div class="category-card" data-category-id="${category.category_id}">
                    <div class="category-image-placeholder" 
                         style="background: linear-gradient(135deg, #667eea, #764ba2); 
                                display: flex; align-items: center; justify-content: center; 
                                color: white; font-size: 3rem;">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div class="category-overlay">
                        <div class="category-name">${category.category_name}</div>
                        <div class="category-description">
                            ${category.category_description || 'Explore our delicious offerings'}
                            <br><small>${mealCount} item${mealCount !== 1 ? 's' : ''} available</small>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    showCategoryMeals(categoryId) {
        const category = this.categories.find(c => c.category_id == categoryId);
        if (!category) return;

        this.currentCategory = category;
        
        document.getElementById('categories').style.display = 'none';
        document.getElementById('meals-section').classList.remove('hidden');
        document.getElementById('current-category-title').textContent = category.category_name;
        
        this.filteredMeals = this.meals.filter(meal => meal.category_id == categoryId);

        // Update search placeholder for category context
        const searchInput = document.getElementById('global-search-input');
        if (searchInput) {
            searchInput.placeholder = `Search in ${category.category_name}...`;
        }

        setTimeout(() => {
            this.renderMeals();
            
            // Scroll to meals section
            document.getElementById('meals-section').scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        }, 300);
    }

    showAllCategories() {
        document.getElementById('categories').style.display = 'block';
        document.getElementById('meals-section').classList.add('hidden');
        this.currentCategory = null;
        this.hideSearchResults();
        
        // Reset search input to global search
        const searchInput = document.getElementById('global-search-input');
        if (searchInput) {
            searchInput.value = '';
            searchInput.placeholder = 'Search meals, categories...';
        }
        
        // Scroll to categories section
        document.getElementById('categories').scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
    }

    renderMeals() {
        const mealsGrid = document.getElementById('meals-grid');
        const noMeals = document.getElementById('no-meals');
        const loading = document.getElementById('meals-loading');
        
        if (loading) loading.classList.add('hidden');

        if (this.filteredMeals.length === 0) {
            mealsGrid.innerHTML = '';
            if (noMeals) noMeals.classList.remove('hidden');
            return;
        }
        
        if (noMeals) noMeals.classList.add('hidden');
        
        mealsGrid.innerHTML = this.filteredMeals.map(meal => `
            <div class="meal-card" data-meal-id="${meal.meal_id}">
                ${meal.image_url ? 
                    `<img src="${meal.image_url}" alt="${meal.meal_name}" class="meal-image">` : 
                    '<div class="meal-image" style="background: linear-gradient(135deg, #667eea, #764ba2); display:flex; align-items:center; justify-content:center; color:white; font-size:3rem;"><i class="fas fa-utensils"></i></div>'
                }
                <div class="meal-content">
                    <h3 class="meal-name">${meal.meal_name}</h3>
                    <p class="meal-description">${meal.meal_description || 'A delicious meal prepared with fresh ingredients and authentic flavors.'}</p>
                    <div class="meal-footer">
                        <div>
                            <div class="meal-price">MK ${parseFloat(meal.price).toFixed(2)}</div>
                            ${meal.preparation_time ? `<div class="meal-time"><i class="fas fa-clock"></i> ${meal.preparation_time} mins</div>` : ''}
                        </div>
                        ${meal.is_available ? 
                            `<button class="btn btn-primary btn-sm add-to-cart" data-meal-id="${meal.meal_id}">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>` :
                            `<button class="btn btn-outline btn-sm" disabled>
                                <i class="fas fa-times-circle"></i> Out of Stock
                            </button>`
                        }
                    </div>
                </div>
            </div>
        `).join('');
    }

    showError(message) {
        console.error(message);
        app.showNotification(message, 'error');
    }

    setupEventListeners() {
        document.addEventListener('click', (e) => {
            // Slider dots
            if (e.target.classList.contains('slider-dot')) {
                const slideIndex = parseInt(e.target.getAttribute('data-slide'));
                this.goToSlide(slideIndex);
            }

            // Category click
            const categoryCard = e.target.closest('.category-card');
            if (categoryCard) {
                this.showCategoryMeals(categoryCard.getAttribute('data-category-id'));
            }

            // Back action
            if (e.target.id === 'back-to-categories') {
                e.preventDefault();
                this.showAllCategories();
            }

            // Add to cart
            if (e.target.classList.contains('add-to-cart')) {
                const mealId = e.target.getAttribute('data-meal-id');
                const meal = this.meals.find(m => m.meal_id == mealId);
                if (meal) app.addToCart(meal);
            }

            // Click search result - UPDATED with category navigation
            const searchResult = e.target.closest('.search-result-item');
            if (searchResult) {
                const mealId = searchResult.getAttribute('data-meal-id');
                const categoryId = searchResult.getAttribute('data-category-id');
                this.handleSearchResultClick(mealId, categoryId);
            }
        });

        // Unified Search - Single search input for both global and category search
        const globalSearchInput = document.getElementById('global-search-input');
        if (globalSearchInput) {
            globalSearchInput.addEventListener('input', (e) => {
                this.performSearch(e.target.value);
            });

            // Close search results when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.nav-search')) {
                    this.hideSearchResults();
                }
            });

            // Handle keyboard navigation in search results
            globalSearchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.hideSearchResults();
                }
            });
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.menuManager = new MenuManager();
});