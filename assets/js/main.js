/**
 * Library Management System - Main JavaScript File
 * This file demonstrates various JavaScript concepts including jQuery, JSON handling,
 * DOM manipulation, events, and more.
 */

// ===== JAVASCRIPT ESSENTIALS =====
// Variables and Operators
let currentUser = null;
const MAX_BOOKS = 5;
let bookCount = 0;

// Control Statements and Looping
function checkBookLimit() {
    if (bookCount >= MAX_BOOKS) {
        showPopup("You have reached the maximum number of books you can borrow.");
        return false;
    }
    return true;
}

// Popup Boxes
function showPopup(message, type = 'info') {
    const popup = document.createElement('div');
    popup.className = `popup-content popup-${type}`;
    popup.innerHTML = `
        <p>${message}</p>
        <button class="btn btn-primary mt-3" onclick="this.parentElement.parentElement.remove()">Close</button>
    `;
    
    popupContainer.appendChild(popup);
    setTimeout(() => popup.remove(), 5000);
}

// JavaScript Objects
const bookTemplate = {
    id: 0,
    title: '',
    author: '',
    description: '',
    coverImage: '',
    isAvailable: true
};

// Working with Functions
function createBookObject(id, title, author, description, coverImage) {
    const book = Object.create(bookTemplate);
    book.id = id;
    book.title = title;
    book.author = author;
    book.description = description;
    book.coverImage = coverImage;
    return book;
}

// ===== JQUERY FUNDAMENTALS =====
$(document).ready(function() {
    // jQuery Selectors
    const $bookList = $('#book-list');
    const $searchInput = $('#search-input');
    const $categoryFilter = $('#category-filter');
    
    // jQuery Events
    $searchInput.on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        filterBooks(searchTerm);
    });
    
    $categoryFilter.on('change', function() {
        const category = $(this).val();
        filterByCategory(category);
    });
    
    // jQuery input vs :input
    $('input:input').each(function() {
        $(this).addClass('form-control');
    });
    
    // jQuery DOM manipulation methods
    function addBookToUI(book) {
        const bookElement = `
            <div class="col-md-4 mb-4 book-item" data-id="${book.id}" data-category="${book.category}">
                <div class="card h-100">
                    <img src="${book.coverImage}" class="card-img-top" alt="${book.title}">
                    <div class="card-body">
                        <h5 class="card-title">${book.title}</h5>
                        <p class="card-text">${book.author}</p>
                        <p class="card-text small">${book.description.substring(0, 100)}...</p>
                        <button class="btn btn-primary borrow-btn" data-id="${book.id}">Borrow</button>
                        <button class="btn btn-outline-primary details-btn" data-id="${book.id}">Details</button>
                    </div>
                </div>
            </div>
        `;
        $bookList.append(bookElement);
    }
    
    // Benefits of using CDN (demonstrated in header.php)
    
    // ===== JSON HANDLING =====
    // Working with JSON Objects
    function loadBooks() {
        // Simulating fetching books from an API
        const booksJSON = localStorage.getItem('books');
        if (booksJSON) {
            const books = JSON.parse(booksJSON);
            books.forEach(book => {
                addBookToUI(book);
            });
        } else {
            // Sample books data
            const sampleBooks = [
                {
                    id: 1,
                    title: "The Great Gatsby",
                    author: "F. Scott Fitzgerald",
                    description: "A story of the fabulously wealthy Jay Gatsby and his love for the beautiful Daisy Buchanan.",
                    coverImage: "assets/images/books/gatsby.jpg",
                    category: "fiction",
                    isAvailable: true
                },
                {
                    id: 2,
                    title: "To Kill a Mockingbird",
                    author: "Harper Lee",
                    description: "The story of racial injustice and the loss of innocence in the American South.",
                    coverImage: "assets/images/books/mockingbird.jpg",
                    category: "fiction",
                    isAvailable: true
                },
                {
                    id: 3,
                    title: "1984",
                    author: "George Orwell",
                    description: "A dystopian social science fiction novel and cautionary tale.",
                    coverImage: "assets/images/books/1984.jpg",
                    category: "fiction",
                    isAvailable: false
                }
            ];
            
            // Store in localStorage
            localStorage.setItem('books', JSON.stringify(sampleBooks));
            
            // Add to UI
            sampleBooks.forEach(book => {
                addBookToUI(book);
            });
        }
    }
    
    // JSON Arrays
    function getAvailableBooks() {
        const booksJSON = localStorage.getItem('books');
        if (booksJSON) {
            const books = JSON.parse(booksJSON);
            return books.filter(book => book.isAvailable);
        }
        return [];
    }
    
    // Nested JSON Objects
    function getUserPreferences() {
        const userPrefsJSON = localStorage.getItem('userPreferences');
        if (userPrefsJSON) {
            return JSON.parse(userPrefsJSON);
        }
        
        // Default preferences
        const defaultPrefs = {
            theme: "light",
            notifications: true,
            display: {
                showCoverImages: true,
                showDescriptions: true,
                itemsPerPage: 9
            },
            filters: {
                categories: [],
                availability: "all"
            }
        };
        
        localStorage.setItem('userPreferences', JSON.stringify(defaultPrefs));
        return defaultPrefs;
    }
    
    // Conversion of JSON Object to string
    function saveUserPreferences(preferences) {
        localStorage.setItem('userPreferences', JSON.stringify(preferences));
    }
    
    // Conversion of string to JSON Object
    function loadUserPreferences() {
        const prefsJSON = localStorage.getItem('userPreferences');
        if (prefsJSON) {
            return JSON.parse(prefsJSON);
        }
        return null;
    }
    
    // ===== JAVASCRIPT & DOM MANIPULATION =====
    // Introduction to the DOM and its role in web development
    // (This is demonstrated throughout the code)
    
    // Accessing and manipulating DOM elements using JavaScript
    function updateBookAvailability(bookId, isAvailable) {
        const bookElement = document.querySelector(`.book-item[data-id="${bookId}"]`);
        if (bookElement) {
            const borrowBtn = bookElement.querySelector('.borrow-btn');
            if (isAvailable) {
                borrowBtn.textContent = 'Borrow';
                borrowBtn.classList.remove('btn-secondary');
                borrowBtn.classList.add('btn-primary');
            } else {
                borrowBtn.textContent = 'Not Available';
                borrowBtn.classList.remove('btn-primary');
                borrowBtn.classList.add('btn-secondary');
                borrowBtn.disabled = true;
            }
        }
    }
    
    // Programming HTML DOM with JavaScript
    function createBookElement(book) {
        const bookDiv = document.createElement('div');
        bookDiv.className = 'col-md-4 mb-4 book-item';
        bookDiv.setAttribute('data-id', book.id);
        bookDiv.setAttribute('data-category', book.category);
        
        bookDiv.innerHTML = `
            <div class="card h-100">
                <img src="${book.coverImage}" class="card-img-top" alt="${book.title}">
                <div class="card-body">
                    <h5 class="card-title">${book.title}</h5>
                    <p class="card-text">${book.author}</p>
                    <p class="card-text small">${book.description.substring(0, 100)}...</p>
                    <button class="btn btn-primary borrow-btn" data-id="${book.id}">Borrow</button>
                    <button class="btn btn-outline-primary details-btn" data-id="${book.id}">Details</button>
                </div>
            </div>
        `;
        
        return bookDiv;
    }
    
    // Modifying HTML content and attributes
    function updateBookDetails(bookId, newDetails) {
        const bookElement = document.querySelector(`.book-item[data-id="${bookId}"]`);
        if (bookElement) {
            const titleElement = bookElement.querySelector('.card-title');
            const authorElement = bookElement.querySelector('.card-text');
            
            titleElement.textContent = newDetails.title;
            authorElement.textContent = newDetails.author;
        }
    }
    
    // Dynamic styling and manipulation of elements
    function highlightBook(bookId) {
        const bookElement = document.querySelector(`.book-item[data-id="${bookId}"]`);
        if (bookElement) {
            bookElement.classList.add('highlight');
            setTimeout(() => {
                bookElement.classList.remove('highlight');
            }, 2000);
        }
    }
    
    // ===== JAVASCRIPT EVENTS =====
    // Event handling in JavaScript
    function setupEventListeners() {
        // Assigning event handlers using DOM object properties
        const borrowButtons = document.querySelectorAll('.borrow-btn');
        borrowButtons.forEach(button => {
            button.onclick = function() {
                const bookId = this.getAttribute('data-id');
                borrowBook(bookId);
            };
        });
        
        // Using addEventListener and removeEventListener
        const detailsButtons = document.querySelectorAll('.details-btn');
        detailsButtons.forEach(button => {
            button.addEventListener('click', function() {
                const bookId = this.getAttribute('data-id');
                showBookDetails(bookId);
            });
        });
        
        // Understanding and implementing event bubbling
        document.querySelector('.book-list').addEventListener('click', function(event) {
            if (event.target.classList.contains('favorite-btn')) {
                const bookId = event.target.getAttribute('data-id');
                toggleFavorite(bookId);
            }
        });
    }
    
    // ===== APPLIED JAVASCRIPT USE CASE =====
    // Creating an interactive image gallery using DOM manipulation
    function setupImageGallery() {
        const galleryContainer = document.querySelector('.book-gallery');
        if (!galleryContainer) return;
        
        const thumbnails = galleryContainer.querySelectorAll('.thumbnail');
        const mainImage = galleryContainer.querySelector('.main-image');
        
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', function() {
                // Remove active class from all thumbnails
                thumbnails.forEach(t => t.classList.remove('active'));
                
                // Add active class to clicked thumbnail
                this.classList.add('active');
                
                // Update main image
                mainImage.src = this.src;
                mainImage.alt = this.alt;
            });
        });
    }
    
    // Implementing thumbnail previews and lightbox-style image display
    function setupLightbox() {
        const bookImages = document.querySelectorAll('.book-image');
        const lightbox = document.createElement('div');
        lightbox.className = 'lightbox';
        lightbox.innerHTML = `
            <div class="lightbox-content">
                <span class="close-lightbox">&times;</span>
                <img src="" alt="" class="lightbox-image">
            </div>
        `;
        document.body.appendChild(lightbox);
        
        bookImages.forEach(image => {
            image.addEventListener('click', function() {
                const lightboxImage = lightbox.querySelector('.lightbox-image');
                lightboxImage.src = this.src;
                lightboxImage.alt = this.alt;
                lightbox.style.display = 'flex';
            });
        });
        
        lightbox.querySelector('.close-lightbox').addEventListener('click', function() {
            lightbox.style.display = 'none';
        });
        
        lightbox.addEventListener('click', function(event) {
            if (event.target === lightbox) {
                lightbox.style.display = 'none';
            }
        });
    }
    
    // ===== GEOLOCATION API =====
    // Fetching and displaying user location
    function getUserLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                // Success callback
                function(position) {
                    const latitude = position.coords.latitude;
                    const longitude = position.coords.longitude;
                    
                    // Store location in localStorage
                    localStorage.setItem('userLocation', JSON.stringify({
                        latitude,
                        longitude
                    }));
                    
                    // Find nearby libraries (simulated)
                    findNearbyLibraries(latitude, longitude);
                },
                // Error callback
                function(error) {
                    // Handling permission requests and errors with geolocation
                    let errorMessage = "Unable to retrieve your location.";
                    
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = "Location access denied by user.";
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = "Location information unavailable.";
                            break;
                        case error.TIMEOUT:
                            errorMessage = "Location request timed out.";
                            break;
                        case error.UNKNOWN_ERROR:
                            errorMessage = "An unknown error occurred.";
                            break;
                    }
                    
                    showPopup(errorMessage, 'error');
                }
            );
        } else {
            showPopup("Geolocation is not supported by this browser.", 'error');
        }
    }
    
    // Find nearby libraries (simulated)
    function findNearbyLibraries(latitude, longitude) {
        // In a real application, this would call an API to find nearby libraries
        const nearbyLibraries = [
            { name: "Central Library", distance: "0.5 miles" },
            { name: "Community Library", distance: "1.2 miles" },
            { name: "University Library", distance: "2.5 miles" }
        ];
        
        const librariesContainer = document.querySelector('.nearby-libraries');
        if (librariesContainer) {
            librariesContainer.innerHTML = '';
            
            nearbyLibraries.forEach(library => {
                const libraryElement = document.createElement('div');
                libraryElement.className = 'library-item';
                libraryElement.innerHTML = `
                    <h5>${library.name}</h5>
                    <p>${library.distance} away</p>
                `;
                librariesContainer.appendChild(libraryElement);
            });
        }
    }
    
    // ===== HELPER FUNCTIONS =====
    function filterBooks(searchTerm) {
        const bookItems = document.querySelectorAll('.book-item');
        
        bookItems.forEach(item => {
            const title = item.querySelector('.card-title').textContent.toLowerCase();
            const author = item.querySelector('.card-text').textContent.toLowerCase();
            
            if (title.includes(searchTerm) || author.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    function filterByCategory(category) {
        const bookItems = document.querySelectorAll('.book-item');
        
        if (category === 'all') {
            bookItems.forEach(item => {
                item.style.display = '';
            });
        } else {
            bookItems.forEach(item => {
                if (item.getAttribute('data-category') === category) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }
    }
    
    function borrowBook(bookId) {
        if (!checkBookLimit()) return;
        
        // In a real application, this would make an API call
        const booksJSON = localStorage.getItem('books');
        if (booksJSON) {
            const books = JSON.parse(booksJSON);
            const bookIndex = books.findIndex(book => book.id == bookId);
            
            if (bookIndex !== -1 && books[bookIndex].isAvailable) {
                books[bookIndex].isAvailable = false;
                localStorage.setItem('books', JSON.stringify(books));
                
                updateBookAvailability(bookId, false);
                bookCount++;
                
                showPopup(`You have borrowed "${books[bookIndex].title}"`, 'success');
            }
        }
    }
    
    function showBookDetails(bookId) {
        const booksJSON = localStorage.getItem('books');
        if (booksJSON) {
            const books = JSON.parse(booksJSON);
            const book = books.find(book => book.id == bookId);
            
            if (book) {
                const detailsModal = document.createElement('div');
                detailsModal.className = 'modal fade';
                detailsModal.id = 'bookDetailsModal';
                detailsModal.innerHTML = `
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">${book.title}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <img src="${book.coverImage}" class="img-fluid mb-3" alt="${book.title}">
                                <p><strong>Author:</strong> ${book.author}</p>
                                <p><strong>Category:</strong> ${book.category}</p>
                                <p><strong>Availability:</strong> ${book.isAvailable ? 'Available' : 'Not Available'}</p>
                                <p><strong>Description:</strong></p>
                                <p>${book.description}</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                ${book.isAvailable ? `<button type="button" class="btn btn-primary borrow-btn" data-id="${book.id}">Borrow</button>` : ''}
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(detailsModal);
                
                const modal = new bootstrap.Modal(detailsModal);
                modal.show();
                
                detailsModal.addEventListener('hidden.bs.modal', function() {
                    document.body.removeChild(detailsModal);
                });
                
                // Add event listener to borrow button in modal
                const borrowBtn = detailsModal.querySelector('.borrow-btn');
                if (borrowBtn) {
                    borrowBtn.addEventListener('click', function() {
                        borrowBook(bookId);
                        modal.hide();
                    });
                }
            }
        }
    }
    
    function toggleFavorite(bookId) {
        // In a real application, this would make an API call
        const favoritesJSON = localStorage.getItem('favorites') || '[]';
        const favorites = JSON.parse(favoritesJSON);
        
        const index = favorites.indexOf(bookId);
        if (index === -1) {
            favorites.push(bookId);
            showPopup('Book added to favorites', 'success');
        } else {
            favorites.splice(index, 1);
            showPopup('Book removed from favorites', 'info');
        }
        
        localStorage.setItem('favorites', JSON.stringify(favorites));
    }
    
    // Initialize the application
    loadBooks();
    setupEventListeners();
    setupImageGallery();
    setupLightbox();
    
    // Get user location if on the home page
    if (window.location.pathname === '/' || window.location.pathname.endsWith('index.php')) {
        getUserLocation();
    }
});

// Main JavaScript file for library management system

// DOM Elements
const popupContainer = document.getElementById('popup-container');
const lightbox = document.getElementById('lightbox');
const lightboxImage = document.getElementById('lightbox-image');
const closeLightbox = document.querySelector('.close-lightbox');

// Utility Functions
function showPopup(message, type = 'success') {
    const popup = document.createElement('div');
    popup.className = `popup-content ${type}`;
    popup.textContent = message;
    
    popupContainer.appendChild(popup);
    
    setTimeout(() => {
        popup.remove();
    }, 3000);
}

function showLightbox(imageSrc) {
    lightboxImage.src = imageSrc;
    lightbox.classList.add('active');
}

function closeLightboxHandler() {
    lightbox.classList.remove('active');
}

// Event Listeners
if (closeLightbox) {
    closeLightbox.addEventListener('click', closeLightboxHandler);
}

// Close lightbox when clicking outside the image
if (lightbox) {
    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) {
            closeLightboxHandler();
        }
    });
}

// Close lightbox with Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && lightbox.classList.contains('active')) {
        closeLightboxHandler();
    }
});

// Form Validation
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            let isValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showPopup('Please fill in all required fields', 'error');
            }
        });
    });
}

// Initialize form validation
document.addEventListener('DOMContentLoaded', () => {
    initFormValidation();
});

// Lightbox functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize lightbox for book covers
    const bookCovers = document.querySelectorAll('.book-cover');
    const lightbox = document.querySelector('.lightbox');
    const lightboxImage = document.querySelector('.lightbox-image');
    const closeLightbox = document.querySelector('.close-lightbox');

    bookCovers.forEach(cover => {
        cover.addEventListener('click', function() {
            lightboxImage.src = this.src;
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });

    // Close lightbox when clicking close button or outside the image
    closeLightbox.addEventListener('click', closeLightboxHandler);
    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) {
            closeLightboxHandler();
        }
    });

    // Close lightbox with escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && lightbox.classList.contains('active')) {
            closeLightboxHandler();
        }
    });

    function closeLightboxHandler() {
        lightbox.classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    // Image loading animation
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.classList.add('image-loading');
                
                // Simulate loading delay
                setTimeout(() => {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    img.classList.remove('image-loading');
                    observer.unobserve(img);
                }, 500);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));

    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        const tooltipText = document.createElement('span');
        tooltipText.className = 'tooltip-text';
        tooltipText.textContent = element.dataset.tooltip;
        element.appendChild(tooltipText);
    });

    // Book carousel/slider
    const carousel = document.querySelector('.book-carousel');
    if (carousel) {
        let currentSlide = 0;
        const slides = carousel.querySelectorAll('.carousel-item');
        const totalSlides = slides.length;

        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.style.display = i === index ? 'block' : 'none';
            });
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            showSlide(currentSlide);
        }

        function prevSlide() {
            currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
            showSlide(currentSlide);
        }

        // Add navigation buttons if they don't exist
        if (!carousel.querySelector('.carousel-nav')) {
            const nav = document.createElement('div');
            nav.className = 'carousel-nav';
            nav.innerHTML = `
                <button class="prev-slide">&lt;</button>
                <button class="next-slide">&gt;</button>
            `;
            carousel.appendChild(nav);

            nav.querySelector('.prev-slide').addEventListener('click', prevSlide);
            nav.querySelector('.next-slide').addEventListener('click', nextSlide);
        }

        // Auto-advance slides every 5 seconds
        setInterval(nextSlide, 5000);
        showSlide(0);
    }

    // Category card hover effects
    const categoryCards = document.querySelectorAll('.category-card');
    categoryCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.querySelector('img').style.transform = 'scale(1.1)';
        });

        card.addEventListener('mouseleave', function() {
            this.querySelector('img').style.transform = 'scale(1)';
        });
    });

    // Book grid masonry layout
    const bookGrid = document.querySelector('.book-grid');
    if (bookGrid) {
        const masonry = new Masonry(bookGrid, {
            itemSelector: '.book-card',
            columnWidth: '.book-card',
            percentPosition: true,
            gutter: 20
        });

        // Re-layout masonry when images load
        const gridImages = bookGrid.querySelectorAll('img');
        gridImages.forEach(img => {
            img.addEventListener('load', () => {
                masonry.layout();
            });
        });
    }
}); 