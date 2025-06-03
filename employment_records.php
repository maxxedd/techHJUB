<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$user = $_SESSION['user'];
$user_type = $user['type'];

// Only employees can access
if ($user_type !== 'employee') {
    header('Location: dashboard.php');
    exit();
}

// Load data
$products = json_decode(file_get_contents('data/products.json'), true) ?: [];
$prebuilds = json_decode(file_get_contents('data/prebuilds.json'), true) ?: [];
$custom_builds = json_decode(file_get_contents('data/custom_builds.json'), true) ?: [];
$inventory = json_decode(file_get_contents('data/inventory.json'), true) ?: [];
$favorites = json_decode(file_get_contents('data/favorites.json'), true) ?: [];
$addtocart = json_decode(file_get_contents('data/addtocart.json'), true) ?: [];

// Get user's favorites and cart items
$user_favorites = array_filter($favorites, fn($fav) => $fav['user_id'] == $user['id']);
$user_cart = array_filter($addtocart, fn($item) => $item['user_id'] == $user['id']);

// Load employment records
$records = json_decode(file_get_contents('data/employment_records.json'), true) ?: [];
$employees = json_decode(file_get_contents('data/employees.json'), true) ?: [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle AJAX requests first
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');

        if (isset($_POST['favorite'])) {
            $product_id = $_POST['product_id'];
            $type = $_POST['type'];

            // Check if already favorited
            $is_favorite = false;
            $new_favorites = [];

            foreach ($favorites as $fav) {
                if ($fav['user_id'] == $user['id'] && $fav['product_id'] == $product_id && $fav['type'] == $type) {
                    $is_favorite = true;
                    continue; // Skip adding this one (remove from favorites)
                }
                $new_favorites[] = $fav;
            }

            if (!$is_favorite) {
                $new_favorites[] = [
                    'id' => uniqid(),
                    'user_id' => $user['id'],
                    'product_id' => $product_id,
                    'type' => $type,
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }

            file_put_contents('data/favorites.json', json_encode($new_favorites, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true, 'is_favorite' => !$is_favorite, 'count' => count($new_favorites)]);
            exit;
        }

        if (isset($_POST['cart'])) {
            $product_id = $_POST['product_id'];
            $type = $_POST['type'];

            // Check if item already in cart
            $found = false;
            foreach ($addtocart as &$item) {
                if ($item['user_id'] == $user['id'] && $item['product_id'] == $product_id && $item['type'] == $type) {
                    $item['quantity'] += 1;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $addtocart[] = [
                    'id' => uniqid(),
                    'user_id' => $user['id'],
                    'product_id' => $product_id,
                    'type' => $type,
                    'quantity' => 1,
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }

            file_put_contents('data/addtocart.json', json_encode($addtocart, JSON_PRETTY_PRINT));
            echo json_encode(['success' => true, 'count' => count(array_filter($addtocart, fn($item) => $item['user_id'] == $user['id']))]);
            exit;
        }
    }

    // Add new record
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $newRecord = [
            'employee' => $_POST['employee_email'],
            'details' => htmlspecialchars($_POST['details']),
            'date_added' => date('Y-m-d H:i:s'),
            'added_by' => $user['email']
        ];

        $records[] = $newRecord;
        file_put_contents('data/employment_records.json', json_encode($records, JSON_PRETTY_PRINT));
        header('Location: employment_records.php');
        exit();
    }

    // Delete record
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $records = array_filter($records, function ($record) {
            return $record['employee'] !== $_POST['employee_email'] ||
                $record['details'] !== $_POST['details'];
        });
        file_put_contents('data/employment_records.json', json_encode(array_values($records), JSON_PRETTY_PRINT));
        header('Location: employment_records.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employment Records | TechHub Solution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/heartbtn.css">
</head>

<body>
    <!-- Toast Notifications -->
    <div class="toast-container">
        <div id="toast" class="toast align-items-center text-white bg-success" role="alert" aria-live="assertive"
            aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="toast-message"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-main">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <img src="images/techhub.png" alt="TechHub Logo" class="me-2"
                    style="height:40px; width:auto; border-radius:50%;">
                <a class="navbar-brand" href="dashboard.php">TechHub Solution</a>
            </div>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Employee Navigation -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="employees.php"><i class="bi bi-people"></i> Employees</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="jobs.php"><i class="bi bi-briefcase"></i> Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dtr.php"><i class="bi bi-clock-history"></i> DTR</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="employment_records.php"><i class="bi bi-file-earmark-text"></i>
                            Records</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php"><i class="bi bi-collection"></i> Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="company.php"><i class="bi bi-building"></i> Company</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ads_promo.php"><i class="bi bi-megaphone"></i> Ads & Promo</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contracts.php"><i class="bi bi-file-earmark-medical"></i>
                            Contracts</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center">
                    <!-- Icons -->
                    <div class="dropdown">
                        <button class="btn dropdown-toggle d-flex align-items-center text-white" type="button"
                            data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <?= strtoupper(substr($user['username'], 0, 1)) ?>
                            </div>
                            <span><?= htmlspecialchars($user['username']) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <h6 class="dropdown-header">Logged in as <?= ucfirst($user_type) ?></h6>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>
                                    Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-5">
        <!-- Welcome Header -->
        <div class="welcome-header mb-4">
            <h2>
                <i class="bi bi-file-earmark-text me-2"></i>
                Employment Records
            </h2>
            <p class="text-muted">
                <i class="bi bi-calendar me-1"></i> <?= date('l, F j, Y') ?> • <?= date('h:i A') ?>
            </p>
        </div>

        <!-- Employment Records Content -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="card-title mb-0">All Records</h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                        <i class="bi bi-plus-circle"></i> Add New Record
                    </button>
                </div>

                <?php if (!empty($records)): ?>
                    <div class="row">
                        <?php foreach ($records as $record):
                            $employee = array_filter($employees, function ($emp) use ($record) {
                                return $emp['email'] === $record['employee'];
                            });
                            $employee = reset($employee);
                            ?>
                            <div class="col-md-6 mb-4">
                                <div class="card record-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h5 class="card-title">
                                                    <?php echo isset($employee['name']) ? htmlspecialchars($employee['name']) : htmlspecialchars($record['employee'] ?? 'Unknown'); ?>
                                                </h5>
                                                <h6 class="card-subtitle mb-2 text-muted">
                                                    <?php echo $employee ? htmlspecialchars($employee['position']) : 'Employee'; ?>
                                                </h6>
                                            </div>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="employee_email"
                                                    value="<?php echo htmlspecialchars($record['employee']); ?>">
                                                <input type="hidden" name="details"
                                                    value="<?php echo htmlspecialchars($record['details']); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Are you sure you want to delete this record?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                        <p class="card-text mt-3"><?php echo htmlspecialchars($record['details']); ?></p>
                                        <div class="d-flex justify-content-between align-items-center mt-4">
                                            <small class="text-muted">
                                                Record added:
                                                <?php echo isset($record['date_added']) && strtotime($record['date_added']) ? date('M j, Y', strtotime($record['date_added'])) : 'Unknown'; ?>
                                            </small>
                                            <small class="text-muted">
                                                By: <?php echo htmlspecialchars($record['added_by'] ?? 'Unknown'); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-file-earmark-text text-muted" style="font-size: 3rem;"></i>
                        <h4 class="mt-3">No employment records found</h4>
                        <p class="text-muted">Add new records to get started</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Record Modal -->
    <div class="modal fade" id="addRecordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Employment Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Employee</label>
                            <select class="form-select" name="employee_email" required>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?php echo htmlspecialchars($employee['email']); ?>">
                                        <?php echo htmlspecialchars($employee['name'] ?? 'Unknown'); ?>
                                        (<?php echo htmlspecialchars($employee['email'] ?? 'Unknown'); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Record Details</label>
                            <textarea class="form-control" name="details" rows="4" required
                                placeholder="E.g., Promoted to Senior Developer - 2024"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- AI Assistant Button -->
    <button class="ai-assistant-btn" data-bs-toggle="modal" data-bs-target="#aiModal">
        <i class="bi bi-robot"></i>
    </button>

    <!-- AI Modal -->
    <div class="modal fade" id="aiModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-robot me-2"></i> AI Assistant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>How can I help you today?</p>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" placeholder="Ask me anything...">
                        <button class="btn btn-primary" type="button">Ask</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize toast
        const toastEl = document.getElementById('toast');
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });

        // Show toast notification
        function showToast(message, type = 'success') {
            const toastMessage = document.getElementById('toast-message');
            toastMessage.textContent = message;
            toastEl.classList.remove('bg-success', 'bg-danger');
            toastEl.classList.add(`bg-${type}`);
            toast.show();
        }

        // Handle favorite button clicks
        document.querySelectorAll('.toggle-favorite').forEach(btn => {
            btn.addEventListener('click', function () {
                const productId = this.dataset.productId;
                const type = this.dataset.type;
                const isFavorite = this.dataset.isFavorite === 'true';
                const icon = this.querySelector('i');

                fetch('dashboard.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `ajax=true&favorite=true&product_id=${productId}&type=${type}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update icon
                            if (isFavorite) {
                                icon.classList.remove('bi-heart-fill');
                                icon.classList.add('bi-heart');
                                this.dataset.isFavorite = 'false';
                                showToast('Removed from favorites', 'success');
                            } else {
                                icon.classList.remove('bi-heart');
                                icon.classList.add('bi-heart-fill');
                                this.dataset.isFavorite = 'true';
                                showToast('Added to favorites', 'success');
                            }

                            // Update favorites count in navbar
                            const favCount = document.querySelector('.badge.bg-danger');
                            if (favCount) {
                                favCount.textContent = data.count;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Failed to update favorites', 'danger');
                    });
            });
        });

        // Handle add to cart button clicks
        document.querySelectorAll('.add-to-cart').forEach(btn => {
            btn.addEventListener('click', function () {
                const productId = this.dataset.productId;
                const type = this.dataset.type;

                fetch('dashboard.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `ajax=true&cart=true&product_id=${productId}&type=${type}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('Added to cart', 'success');

                            // Update cart count in navbar
                            const cartCount = document.querySelector('.badge.bg-primary');
                            if (cartCount) {
                                cartCount.textContent = data.count;
                            } else {
                                // If this is the first item in cart, create the badge
                                const cartIcon = document.querySelector('.bi-cart').parentElement;
                                const badge = document.createElement('span');
                                badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary';
                                badge.textContent = data.count;
                                cartIcon.appendChild(badge);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Failed to add to cart', 'danger');
                    });
            });
        });
    </script>
</body>

</html>