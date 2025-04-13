<footer class="footer mt-auto py-4" style="background-color: var(--dark-accent); border-top: 2px solid var(--accent-color);">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 mb-md-0">
                <h5 class="mb-3" style="font-family: 'Cormorant Garamond', serif; color: var(--light-accent);">
                    <i class="fas fa-book-open me-2"></i>E-Library
                </h5>
                <p class="mb-0" style="color: var(--text-color);">
                    Your digital gateway to knowledge. Explore, learn, and discover with our vast collection of books.
                </p>
            </div>
            <div class="col-md-4 mb-4 mb-md-0">
                <h5 class="mb-3" style="font-family: 'Cormorant Garamond', serif; color: var(--light-accent);">Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="index.php" class="text-decoration-none" style="color: var(--text-color); transition: all 0.3s ease;">
                            <i class="fas fa-home me-2"></i>Home
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="books.php" class="text-decoration-none" style="color: var(--text-color); transition: all 0.3s ease;">
                            <i class="fas fa-book me-2"></i>Books
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="categories.php" class="text-decoration-none" style="color: var(--text-color); transition: all 0.3s ease;">
                            <i class="fas fa-tags me-2"></i>Categories
                        </a>
                    </li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="mb-2">
                            <a href="my-books.php" class="text-decoration-none" style="color: var(--text-color); transition: all 0.3s ease;">
                                <i class="fas fa-bookmark me-2"></i>My Books
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="col-md-4">
                <h5 class="mb-3" style="font-family: 'Cormorant Garamond', serif; color: var(--light-accent);">Contact Us</h5>
                <ul class="list-unstyled">
                    <li class="mb-2" style="color: var(--text-color);">
                        <i class="fas fa-envelope me-2"></i>support@elibrary.com
                    </li>
                    <li class="mb-2" style="color: var(--text-color);">
                        <i class="fas fa-phone me-2"></i>+1 (555) 123-4567
                    </li>
                    <li class="mb-2" style="color: var(--text-color);">
                        <i class="fas fa-map-marker-alt me-2"></i>123 Library Street, Book City
                    </li>
                </ul>
                <div class="social-links mt-3">
                    <a href="#" class="me-3" style="color: var(--text-color); transition: all 0.3s ease;">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="me-3" style="color: var(--text-color); transition: all 0.3s ease;">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="me-3" style="color: var(--text-color); transition: all 0.3s ease;">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" style="color: var(--text-color); transition: all 0.3s ease;">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
            </div>
        </div>
        <hr class="my-4" style="border-color: var(--accent-color);">
        <div class="row">
            <div class="col-md-6 text-center text-md-start">
                <p class="mb-0" style="color: var(--text-color);">
                    &copy; <?php echo date('Y'); ?> E-Library. All rights reserved.
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <a href="privacy.php" class="text-decoration-none me-3" style="color: var(--text-color); transition: all 0.3s ease;">
                    Privacy Policy
                </a>
                <a href="terms.php" class="text-decoration-none" style="color: var(--text-color); transition: all 0.3s ease;">
                    Terms of Service
                </a>
            </div>
        </div>
    </div>
</footer>

<style>
    .footer {
        transition: all 0.3s ease;
    }
    .footer:hover {
        box-shadow: 0 -4px 8px rgba(0, 0, 0, 0.3);
    }
    .footer a:hover {
        color: var(--light-accent) !important;
        transform: translateY(-2px);
    }
    .social-links a:hover {
        transform: scale(1.2);
    }
</style> 