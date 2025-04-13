// Check if jQuery is loaded, if not load it from CDN
if (typeof jQuery == 'undefined') {
    var script = document.createElement('script');
    script.src = 'https://code.jquery.com/jquery-3.7.1.min.js';
    script.type = 'text/javascript';
    document.getElementsByTagName('head')[0].appendChild(script);
}

// Wait for jQuery to be ready
$(document).ready(function() {
    // Sliding Panel Component
    class SlidingPanel {
        constructor(triggerSelector, panelSelector, options = {}) {
            this.trigger = $(triggerSelector);
            this.panel = $(panelSelector);
            this.options = {
                direction: options.direction || 'right',
                speed: options.speed || 300,
                overlay: options.overlay || true
            };
            this.init();
        }

        init() {
            const self = this;
            this.trigger.on('click', function(e) {
                e.preventDefault();
                self.togglePanel();
            });

            if (this.options.overlay) {
                this.panel.prepend('<div class="panel-overlay"></div>');
                $('.panel-overlay').on('click', function() {
                    self.closePanel();
                });
            }
        }

        togglePanel() {
            if (this.panel.hasClass('open')) {
                this.closePanel();
            } else {
                this.openPanel();
            }
        }

        openPanel() {
            this.panel.addClass('open').slideDown(this.options.speed);
            $('body').addClass('panel-open');
        }

        closePanel() {
            this.panel.removeClass('open').slideUp(this.options.speed);
            $('body').removeClass('panel-open');
        }
    }

    // Book Card Component
    class BookCard {
        constructor(cardSelector) {
            this.card = $(cardSelector);
            this.init();
        }

        init() {
            this.card.hover(
                function() {
                    $(this).addClass('hover').animate({
                        'box-shadow': '0 8px 16px rgba(0,0,0,0.3)',
                        'transform': 'translateY(-5px)'
                    }, 200);
                },
                function() {
                    $(this).removeClass('hover').animate({
                        'box-shadow': '0 4px 8px rgba(0,0,0,0.2)',
                        'transform': 'translateY(0)'
                    }, 200);
                }
            );

            // Wishlist button animation
            this.card.find('.wishlist-btn').on('click', function(e) {
                e.preventDefault();
                const btn = $(this);
                btn.addClass('active').find('i').addClass('fa-bounce');
                setTimeout(() => {
                    btn.removeClass('active').find('i').removeClass('fa-bounce');
                }, 1000);
            });

            // Borrow button animation
            this.card.find('.borrow-btn').on('click', function(e) {
                e.preventDefault();
                const btn = $(this);
                btn.addClass('active').find('i').addClass('fa-shake');
                setTimeout(() => {
                    btn.removeClass('active').find('i').removeClass('fa-shake');
                }, 1000);
            });
        }
    }

    // Reading Time Counter Component
    class ReadingTimeCounter {
        constructor(containerSelector) {
            this.container = $(containerSelector);
            this.startTime = null;
            this.timer = null;
            this.isActive = false;
            this.init();
        }

        init() {
            this.container.html(`
                <div class="reading-time">
                    <i class="fas fa-clock"></i>
                    <span class="time">00:00:00</span>
                </div>
            `);

            // Start counter when book reader is opened
            $(document).on('bookReaderOpened', () => this.startCounter());
            
            // Stop counter when book reader is closed
            $(document).on('bookReaderClosed', () => this.stopCounter());

            // Handle visibility change
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.pauseCounter();
                } else if (this.isActive) {
                    this.resumeCounter();
                }
            });
        }

        startCounter() {
            this.startTime = new Date();
            this.isActive = true;
            this.updateCounter();
        }

        stopCounter() {
            clearInterval(this.timer);
            this.isActive = false;
            this.container.find('.time').text('00:00:00');
        }

        pauseCounter() {
            clearInterval(this.timer);
        }

        resumeCounter() {
            if (this.isActive) {
                this.updateCounter();
            }
        }

        updateCounter() {
            this.timer = setInterval(() => {
                const now = new Date();
                const diff = now - this.startTime;
                const hours = Math.floor(diff / 3600000);
                const minutes = Math.floor((diff % 3600000) / 60000);
                const seconds = Math.floor((diff % 60000) / 1000);
                
                const timeString = [
                    hours.toString().padStart(2, '0'),
                    minutes.toString().padStart(2, '0'),
                    seconds.toString().padStart(2, '0')
                ].join(':');
                
                this.container.find('.time').text(timeString);
            }, 1000);
        }
    }

    // Gradient Background Component
    class GradientBackground {
        constructor(selector, colors = ['#2c1810', '#1a1a1a', '#2c1810']) {
            this.element = $(selector);
            this.colors = colors;
            this.currentIndex = 0;
            this.init();
        }

        init() {
            this.element.css({
                'background': `linear-gradient(45deg, ${this.colors.join(', ')})`,
                'background-size': '400% 400%',
                'animation': 'gradient 15s ease infinite'
            });

            // Add keyframes dynamically
            const style = document.createElement('style');
            style.textContent = `
                @keyframes gradient {
                    0% { background-position: 0% 50%; }
                    50% { background-position: 100% 50%; }
                    100% { background-position: 0% 50%; }
                }
            `;
            document.head.appendChild(style);
        }
    }

    // Book Data Loader Component
    class BookDataLoader {
        constructor(containerSelector, jsonUrl) {
            this.container = $(containerSelector);
            this.jsonUrl = jsonUrl;
            this.init();
        }

        init() {
            this.loadBooks();
        }

        loadBooks() {
            $.getJSON(this.jsonUrl, (data) => {
                this.renderBooks(data);
            }).fail(() => {
                console.error('Failed to load book data');
            });
        }

        renderBooks(books) {
            this.container.empty();
            books.forEach(book => {
                const card = $(`
                    <div class="book-card animate-fade-in">
                        <div class="book-cover">
                            <img src="${book.cover_image}" alt="${book.title}">
                        </div>
                        <div class="book-info">
                            <h3>${book.title}</h3>
                            <p class="author">${book.author}</p>
                            <p class="description">${book.description}</p>
                            <div class="book-actions">
                                <button class="wishlist-btn">
                                    <i class="fas fa-heart"></i> Add to Wishlist
                                </button>
                                <button class="borrow-btn">
                                    <i class="fas fa-book"></i> Borrow
                                </button>
                            </div>
                        </div>
                    </div>
                `);
                
                this.container.append(card);
                new BookCard(card);
            });
        }
    }

    // Initialize components
    $(document).ready(function() {
        // Initialize sliding panels
        new SlidingPanel('.book-details-trigger', '.book-details-panel');
        new SlidingPanel('.user-profile-trigger', '.user-profile-panel');

        // Initialize book cards
        $('.book-card').each(function() {
            new BookCard(this);
        });

        // Initialize reading time counter
        new ReadingTimeCounter('.reading-time-container');

        // Initialize gradient background
        new GradientBackground('.header-section');

        // Initialize book data loader
        new BookDataLoader('.books-container', 'data/books.json');
    });
}); 