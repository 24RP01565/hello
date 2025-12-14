<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "library");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle search functionality
$search = '';
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where = "WHERE book_id LIKE '%$search%' OR 
              book_name LIKE '%$search%' OR 
              book_title LIKE '%$search%' OR 
              author LIKE '%$search%'";
} else {
    $where = "";
}

// Handle delete action
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $delete_query = "DELETE FROM book WHERE book_id = $delete_id";
    if (mysqli_query($conn, $delete_query)) {
        $message = "<div class='alert success'>Book deleted successfully!</div>";
        header("Location: view_books.php?message=deleted");
        exit();
    }
}

// Get total number of books
$count_query = "SELECT COUNT(*) as total FROM book $where";
$count_result = mysqli_query($conn, $count_query);
$total_books = mysqli_fetch_assoc($count_result)['total'];

// Fetch books with pagination
$limit = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$query = "SELECT * FROM book $where ORDER BY book_id ASC LIMIT $limit OFFSET $offset";
$result = mysqli_query($conn, $query);

// Calculate total pages
$total_pages = ceil($total_books / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books Collection - Library Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .header h1 i {
            font-size: 2rem;
        }

        .stats {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .stat-box {
            background: rgba(255, 255, 255, 0.2);
            padding: 15px 25px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
            min-width: 150px;
        }

        .stat-box h3 {
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .stat-box p {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Search and Actions */
        .actions-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-box {
            flex: 1;
            min-width: 300px;
        }

        .search-box form {
            display: flex;
            gap: 10px;
        }

        .search-box input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .search-box input:focus {
            outline: none;
            border-color: #2575fc;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2575fc 0%, #6a11cb 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 117, 252, 0.3);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        /* Books Table */
        .books-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        thead {
            background: linear-gradient(135deg, #2575fc 0%, #6a11cb 100%);
            color: white;
        }

        th {
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.3s ease;
        }

        tbody tr:hover {
            background-color: #f8f9ff;
        }

        td {
            padding: 16px 15px;
            font-size: 14px;
            color: #555;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-small {
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: #ffc107;
            color: #333;
        }

        .btn-edit:hover {
            background: #e0a800;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
        }

        .page-link {
            padding: 10px 16px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            color: #333;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .page-link:hover {
            background: #2575fc;
            color: white;
            border-color: #2575fc;
        }

        .page-link.active {
            background: #2575fc;
            color: white;
            border-color: #2575fc;
        }

        /* Alert Messages */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 60px;
            margin-bottom: 20px;
            color: #ddd;
        }

        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }

        /* Footer */
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
            color: #666;
            font-size: 14px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            
            .actions-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                min-width: 100%;
            }
            
            th, td {
                padding: 12px 10px;
                font-size: 13px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }

        /* Book Status Indicators */
        .status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-available {
            background: #d4edda;
            color: #155724;
        }

        .status-borrowed {
            background: #fff3cd;
            color: #856404;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        function confirmDelete(bookId, bookName) {
            if (confirm(`Are you sure you want to delete "${bookName}"? This action cannot be undone.`)) {
                window.location.href = `view_books.php?delete_id=${bookId}`;
            }
            return false;
        }
    </script>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-book"></i> Library Books Collection</h1>
            <p>Manage and browse all books in the library database</p>
            
            <div class="stats">
                <div class="stat-box">
                    <h3><?php echo $total_books; ?></h3>
                    <p>Total Books</p>
                </div>
                <div class="stat-box">
                    <h3><?php echo mysqli_num_rows($result); ?></h3>
                    <p>Displayed</p>
                </div>
                <div class="stat-box">
                    <h3><?php echo date('Y'); ?></h3>
                    <p>Current Year</p>
                </div>
            </div>
        </div>

        <!-- Display Messages -->
        <?php if (isset($_GET['message']) && $_GET['message'] == 'deleted'): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i> Book deleted successfully!
            </div>
        <?php endif; ?>

        <!-- Actions Bar -->
        <div class="actions-bar">
            <div class="search-box">
                <form method="GET" action="">
                    <input type="text" name="search" placeholder="Search by ID, Name, Title or Author..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if ($search): ?>
                        <a href="view_books.php" class="btn" style="background: #6c757d; color: white;">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="actions">
                <a href="book_insert.php" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i> Add New Book
                </a>
                <button onclick="window.print()" class="btn" style="background: #17a2b8; color: white;">
                    <i class="fas fa-print"></i> Print List
                </button>
            </div>
        </div>

        <!-- Books Table -->
        <div class="books-table">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-book"></i> Book Name</th>
                            <th><i class="fas fa-heading"></i> Title</th>
                            <th><i class="fas fa-user-pen"></i> Author</th>
                            <th><i class="fas fa-calendar-day"></i> Publish Date</th>
                            <th><i class="fas fa-cog"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><strong>#<?php echo htmlspecialchars($row['book_id']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['book_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['book_title']); ?></td>
                                <td><?php echo htmlspecialchars($row['author']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['publisher_date'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="book_update.php?book_id=<?php echo $row['book_id']; ?>" 
                                           class="btn-small btn-edit">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="#" 
                                           onclick="return confirmDelete(<?php echo $row['book_id']; ?>, '<?php echo addslashes($row['book_name']); ?>')" 
                                           class="btn-small btn-delete">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-book-open"></i>
                    <h3>No Books Found</h3>
                    <p><?php echo $search ? "No books match your search criteria." : "The library database is empty."; ?></p>
                    <?php if ($search): ?>
                        <a href="view_books.php" class="btn btn-primary" style="margin-top: 20px;">
                            <i class="fas fa-arrow-left"></i> View All Books
                        </a>
                    <?php else: ?>
                        <a href="insert_book.php" class="btn btn-success" style="margin-top: 20px;">
                            <i class="fas fa-plus-circle"></i> Add First Book
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" 
                       class="page-link">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                       class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" 
                       class="page-link">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <p>
                <i class="fas fa-database"></i> Library Management System | 
                Total Records: <?php echo $total_books; ?> | 
                Page <?php echo $page; ?> of <?php echo $total_pages; ?>
            </p>
            <p>
                <a href="index.php" style="color: #2575fc; text-decoration: none; margin: 0 10px;">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="export_books.php" style="color: #2575fc; text-decoration: none; margin: 0 10px;">
                    <i class="fas fa-file-export"></i> Export CSV
                </a>
                <a href="report.php" style="color: #2575fc; text-decoration: none; margin: 0 10px;">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </p>
        </div>
    </div>

    <script>
        // Add row hover effect
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px)';
                });
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });
        });
    </script>
</body>
</html>

<?php
// Close database connection
mysqli_close($conn);
?>