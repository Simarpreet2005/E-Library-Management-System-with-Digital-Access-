<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if book_id is provided
if (!isset($_GET['id'])) {
    header('Location: books.php');
    exit();
}

$book_id = $_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT b.* 
        FROM books b 
        WHERE b.id = ?
    ");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        header('Location: books.php');
        exit();
    }

    // Debug: Print book information
    echo "<!-- Debug Info:\n";
    echo "Book ID: " . $book_id . "\n";
    echo "File Path: " . $book['file_path'] . "\n";
    echo "Absolute Path: " . realpath($book['file_path']) . "\n";
    echo "File Exists: " . (file_exists($book['file_path']) ? 'Yes' : 'No') . "\n";
    echo "File Size: " . (file_exists($book['file_path']) ? filesize($book['file_path']) : 'N/A') . "\n";
    echo "File Permissions: " . (file_exists($book['file_path']) ? decoct(fileperms($book['file_path']) & 0777) : 'N/A') . "\n";
    echo "-->\n";

    // Check if PDF file exists
    $pdf_path = $book['file_path'];
    if (!file_exists($pdf_path)) {
        throw new Exception("PDF file not found at: " . $pdf_path);
    }

    // Verify file is readable
    if (!is_readable($pdf_path)) {
        throw new Exception("PDF file is not readable. Check file permissions.");
    }

    // Verify file is a PDF
    $mime_type = mime_content_type($pdf_path);
    if ($mime_type !== 'application/pdf') {
        throw new Exception("File is not a valid PDF. MIME type: " . $mime_type);
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($book['title']); ?> - E-Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/dark-academia.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <style>
        body {
            overflow: hidden;
            height: 100vh;
            margin: 0;
            padding: 0;
            background-color: var(--dark-accent);
            color: var(--text-color);
        }
        .main-container {
            display: flex;
            height: 100vh;
            width: 100vw;
            position: fixed;
            top: 0;
            left: 0;
        }
        .book-details-container {
            width: 300px;
            background-color: var(--secondary-color);
            padding: 20px;
            border-right: 2px solid var(--accent-color);
            overflow-y: auto;
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }
        .book-info {
            flex: 1;
            overflow-y: auto;
        }
        .bookmarks-section {
            margin-top: 20px;
            border-top: 2px solid var(--accent-color);
            padding-top: 20px;
        }
        .bookmarks-list {
            max-height: 200px;
            overflow-y: auto;
            margin-top: 10px;
        }
        .bookmark-item {
            padding: 8px;
            border-bottom: 1px solid var(--accent-color);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }
        .bookmark-item:hover {
            background-color: var(--dark-accent);
            transform: translateX(5px);
        }
        .bookmark-date {
            font-size: 0.8rem;
            color: var(--text-color);
            opacity: 0.7;
        }
        .pdf-container {
            flex: 1;
            height: 100%;
            position: relative;
            overflow: hidden;
            background-color: var(--secondary-color);
        }
        .pdf-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
        .book-cover {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
            margin-bottom: 20px;
            border: 1px solid var(--accent-color);
            transition: transform 0.3s ease;
        }
        .book-cover:hover {
            transform: scale(1.02);
        }
        .book-details {
            font-size: 0.9rem;
        }
        .book-details h2 {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--light-accent);
        }
        .book-details p {
            margin-bottom: 10px;
            color: var(--text-color);
        }
        .controls-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: var(--secondary-color);
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
            display: flex;
            gap: 10px;
            align-items: center;
            z-index: 1000;
            border: 1px solid var(--accent-color);
            transition: all 0.3s ease;
        }
        .controls-container:hover {
            transform: translateY(-5px);
        }
        .page-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .bookmark-btn {
            background-color: var(--accent-color);
            color: var(--text-color);
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .bookmark-btn:hover {
            background-color: var(--light-accent);
            transform: translateY(-2px);
        }
        .page-input {
            width: 60px;
            text-align: center;
            padding: 5px;
            border: 1px solid var(--accent-color);
            border-radius: 5px;
            background-color: var(--secondary-color);
            color: var(--text-color);
        }
        .nav-btn {
            background-color: var(--secondary-color);
            border: 1px solid var(--accent-color);
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            color: var(--text-color);
            transition: all 0.3s ease;
        }
        .nav-btn:hover {
            background-color: var(--dark-accent);
            transform: translateY(-2px);
        }
        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background-color: var(--secondary-color);
            border: 1px solid var(--accent-color);
            padding: 8px 15px;
            border-radius: 5px;
            color: var(--text-color);
            transition: all 0.3s ease;
        }
        .back-button:hover {
            background-color: var(--dark-accent);
            transform: translateX(-5px);
        }
        .toggle-details {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background-color: var(--secondary-color);
            border: 1px solid var(--accent-color);
            padding: 8px 15px;
            border-radius: 5px;
            color: var(--text-color);
            transition: all 0.3s ease;
        }
        .toggle-details:hover {
            background-color: var(--dark-accent);
            transform: translateX(5px);
        }
        .resize-handle {
            position: absolute;
            left: -5px;
            top: 0;
            bottom: 0;
            width: 10px;
            cursor: col-resize;
            background-color: var(--accent-color);
            opacity: 0.3;
            z-index: 3;
            transition: all 0.3s ease;
        }
        .resize-handle:hover {
            opacity: 0.5;
        }
        .bookmark-note {
            font-size: 0.8rem;
            color: var(--text-color);
            opacity: 0.7;
            margin-top: 5px;
            word-break: break-word;
        }
        .bookmark-actions {
            display: flex;
            gap: 5px;
        }
        .delete-bookmark {
            color: var(--light-accent);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .delete-bookmark:hover {
            color: var(--accent-color);
            transform: scale(1.2);
        }
        .modal-content {
            background-color: var(--secondary-color);
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
            width: 300px;
            border: 1px solid var(--accent-color);
        }
        .modal-backdrop {
            background: rgba(0, 0, 0, 0.7);
        }
        #pdfViewer {
            height: 100%;
            overflow-y: auto;
            padding: 20px;
            background-color: var(--secondary-color);
        }
        .pdf-page {
            display: block;
            margin: 0 auto 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
            background-color: var(--secondary-color);
            border: 1px solid var(--accent-color);
        }
        .page-number {
            text-align: center;
            color: var(--text-color);
            font-size: 0.9em;
            margin-bottom: 20px;
        }
        /* Smooth scrolling */
        #pdfViewer {
            scroll-behavior: smooth;
        }
        /* Custom scrollbar */
        #pdfViewer::-webkit-scrollbar {
            width: 8px;
        }
        #pdfViewer::-webkit-scrollbar-track {
            background-color: var(--dark-accent);
        }
        #pdfViewer::-webkit-scrollbar-thumb {
            background-color: var(--accent-color);
            border-radius: 4px;
        }
        #pdfViewer::-webkit-scrollbar-thumb:hover {
            background-color: var(--light-accent);
        }
        .book-details-container::-webkit-scrollbar {
            width: 8px;
        }
        .book-details-container::-webkit-scrollbar-track {
            background-color: var(--dark-accent);
        }
        .book-details-container::-webkit-scrollbar-thumb {
            background-color: var(--accent-color);
            border-radius: 4px;
        }
        .book-details-container::-webkit-scrollbar-thumb:hover {
            background-color: var(--light-accent);
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="main-container">
        <div class="book-details-container">
            <div class="book-info">
                <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Book Cover" class="book-cover">
                <div class="book-details">
                    <h2><?php echo htmlspecialchars($book['title']); ?></h2>
                    <p><strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                    <p><strong>Category:</strong> <?php echo htmlspecialchars($book['category']); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($book['description']); ?></p>
                </div>
            </div>
            <div class="bookmarks-section">
                <h3 style="font-family: 'Cormorant Garamond', serif; color: var(--light-accent);">Bookmarks</h3>
                <div class="bookmarks-list">
                    <!-- Bookmarks will be loaded here -->
                </div>
            </div>
        </div>
        <div class="resize-handle"></div>
        <div class="pdf-container">
            <div id="pdfViewer"></div>
        </div>
    </div>

    <a href="books.php" class="back-button animate-fade-in">
        <i class="fas fa-arrow-left me-2"></i>Back to Books
    </a>

    <button class="toggle-details animate-fade-in">
        <i class="fas fa-book me-2"></i>Toggle Details
            </button>

    <div class="controls-container animate-fade-in">
            <div class="page-controls">
                <button class="nav-btn" id="prevPage">
                    <i class="fas fa-chevron-left"></i>
                </button>
            <input type="number" class="page-input" id="currentPage" min="1">
                <span id="totalPages">/ 1</span>
                <button class="nav-btn" id="nextPage">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        <button class="bookmark-btn" id="addBookmark">
            <i class="fas fa-bookmark me-2"></i>Add Bookmark
        </button>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize PDF.js
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        let pdfDoc = null;
        let currentPage = 1;
        let totalPages = 1;
        let bookmarks = JSON.parse(localStorage.getItem('bookmarks') || '{}');
        const bookId = <?php echo $book_id; ?>;

        // Debug: Log PDF path
        console.log('PDF Path:', '<?php echo htmlspecialchars($pdf_path); ?>');

        // Load the PDF
        pdfjsLib.getDocument('<?php echo htmlspecialchars($pdf_path); ?>').promise.then(function(pdf) {
            console.log('PDF loaded successfully');
            pdfDoc = pdf;
            totalPages = pdf.numPages;
            document.getElementById('totalPages').textContent = `/${totalPages}`;
            document.getElementById('currentPage').max = totalPages;
            renderPage(currentPage);
            loadBookmarks();
        }).catch(function(error) {
            console.error('Error loading PDF:', error);
            document.getElementById('pdfViewer').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Error loading PDF file: ${error.message}
                </div>
            `;
        });

        // Render a specific page
        function renderPage(pageNum) {
            if (!pdfDoc) return;
            
            pdfDoc.getPage(pageNum).then(function(page) {
                const scale = 1.5;
                const viewport = page.getViewport({ scale: scale });
                
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                
                const renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };
                
                const pdfViewer = document.getElementById('pdfViewer');
                pdfViewer.innerHTML = '';
                pdfViewer.appendChild(canvas);
                
                page.render(renderContext);
            }).catch(function(error) {
                console.error('Error rendering page:', error);
                document.getElementById('pdfViewer').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Error rendering page. Please try again.
                    </div>
                `;
            });
        }

        // Navigation functions
        function goToPage(pageNum) {
            if (pageNum >= 1 && pageNum <= totalPages) {
                currentPage = pageNum;
                document.getElementById('currentPage').value = currentPage;
                renderPage(currentPage);
            }
        }

        // Event listeners for navigation
        document.getElementById('prevPage').addEventListener('click', () => {
            if (currentPage > 1) {
                goToPage(currentPage - 1);
            }
        });

        document.getElementById('nextPage').addEventListener('click', () => {
            if (currentPage < totalPages) {
                goToPage(currentPage + 1);
            }
        });

        document.getElementById('currentPage').addEventListener('change', function() {
            const pageNum = parseInt(this.value);
            if (!isNaN(pageNum) && pageNum >= 1 && pageNum <= totalPages) {
                goToPage(pageNum);
            } else {
                this.value = currentPage;
            }
        });

        // Bookmark functionality
        function loadBookmarks() {
            const bookmarksList = document.querySelector('.bookmarks-list');
            const bookBookmarks = bookmarks[bookId] || [];
            
            bookmarksList.innerHTML = bookBookmarks.length ? 
                bookBookmarks.map((bookmark, index) => `
                    <div class="bookmark-item">
                        <div>
                            <div>Page ${bookmark.page}</div>
                            <div class="bookmark-note">${bookmark.note || ''}</div>
                            <div class="bookmark-date">${new Date(bookmark.date).toLocaleDateString()}</div>
                        </div>
                        <div class="bookmark-actions">
                            <i class="fas fa-trash delete-bookmark"></i>
                        </div>
                    </div>
                `).join('') :
                '<div class="text-muted">No bookmarks yet</div>';

            // Add click events to bookmarks
            document.querySelectorAll('.bookmark-item').forEach((item, index) => {
                item.addEventListener('click', (e) => {
                    if (!e.target.classList.contains('delete-bookmark')) {
                        goToPage(bookBookmarks[index].page);
                    }
                });

                const deleteBtn = item.querySelector('.delete-bookmark');
                deleteBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    bookBookmarks.splice(index, 1);
                    bookmarks[bookId] = bookBookmarks;
                    localStorage.setItem('bookmarks', JSON.stringify(bookmarks));
                    loadBookmarks();
                });
            });
        }

        document.getElementById('addBookmark').addEventListener('click', () => {
            const note = prompt('Add a note for this bookmark:');
            if (note !== null) {
            if (!bookmarks[bookId]) {
                bookmarks[bookId] = [];
            }
            
            // Check if bookmark already exists for this page
            const existingBookmark = bookmarks[bookId].find(b => b.page === currentPage);
            if (existingBookmark) {
                alert('Bookmark already exists for this page!');
                return;
            }
            
            bookmarks[bookId].push({
                page: currentPage,
                date: new Date().toISOString(),
                note: note
            });
                
            localStorage.setItem('bookmarks', JSON.stringify(bookmarks));
                loadBookmarks();
            }
        });

        // Toggle book details
        document.querySelector('.toggle-details').addEventListener('click', () => {
            const container = document.querySelector('.book-details-container');
            container.style.display = container.style.display === 'none' ? 'flex' : 'none';
        });

        // Resize handle functionality
        let isResizing = false;
        const resizeHandle = document.querySelector('.resize-handle');
        const bookDetailsContainer = document.querySelector('.book-details-container');

        resizeHandle.addEventListener('mousedown', (e) => {
            isResizing = true;
            document.addEventListener('mousemove', handleResize);
            document.addEventListener('mouseup', stopResize);
        });

        function handleResize(e) {
            if (!isResizing) return;
            const newWidth = e.clientX;
            if (newWidth > 200 && newWidth < window.innerWidth - 200) {
                bookDetailsContainer.style.width = `${newWidth}px`;
            }
        }

        function stopResize() {
            isResizing = false;
            document.removeEventListener('mousemove', handleResize);
            document.removeEventListener('mouseup', stopResize);
        }
    </script>
</body>
</html> 