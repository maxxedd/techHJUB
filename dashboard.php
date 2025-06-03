<?php

date_default_timezone_set('Asia/Manila');

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$user = $_SESSION['user'];
$user_type = $user['type'];

//date_default_timezone_set('Asia/Manila'); // Replace with your timezone

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

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
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

// Employee product management
if ($user_type === 'employee') {
    // Handle Add Product
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
        $newProduct = [
            'id' => uniqid(),
            'name' => htmlspecialchars($_POST['name']),
            'price' => (float) $_POST['price'],
            'description' => htmlspecialchars($_POST['description']),
            'category' => htmlspecialchars($_POST['category']),
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $user['id']
        ];
        $products[] = $newProduct;
        file_put_contents('data/products.json', json_encode($products, JSON_PRETTY_PRINT));

        // Add to inventory
        $inventory[] = [
            'product_id' => $newProduct['id'],
            'stock' => (int) $_POST['stock'],
            'location' => htmlspecialchars($_POST['location']),
            'last_updated' => date('Y-m-d H:i:s')
        ];
        file_put_contents('data/inventory.json', json_encode($inventory, JSON_PRETTY_PRINT));

        header('Location: dashboard.php');
        exit();
    }

    // Handle Delete Product
    if (isset($_GET['delete'])) {
        $products = array_filter($products, fn($product) => $product['id'] !== $_GET['delete']);
        file_put_contents('data/products.json', json_encode(array_values($products), JSON_PRETTY_PRINT));

        $inventory = array_filter($inventory, fn($item) => $item['product_id'] !== $_GET['delete']);
        file_put_contents('data/inventory.json', json_encode(array_values($inventory), JSON_PRETTY_PRINT));

        header('Location: dashboard.php');
        exit();
    }

    // Handle Update Stock
    if (isset($_GET['update_stock']) && isset($_GET['id'])) {
        $productId = $_GET['id'];
        $newStock = (int) $_GET['update_stock'];

        foreach ($inventory as &$item) {
            if ($item['product_id'] === $productId) {
                $item['stock'] = $newStock;
                $item['last_updated'] = date('Y-m-d H:i:s');
                break;
            }
        }
        file_put_contents('data/inventory.json', json_encode($inventory, JSON_PRETTY_PRINT));
        header('Location: dashboard.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | TechHub Solution</title>
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
                <?php if ($user_type == 'employee'): ?>
                    <!-- Employee Navigation -->
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="employees.php">
                                <i class="bi bi-people"></i> Employees
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="jobs.php"><i class="bi bi-briefcase"></i> Jobs</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="dtr.php"><i class="bi bi-clock-history"></i> DTR</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="employment_records.php"><i class="bi bi-file-earmark-text"></i>
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
                <?php else: ?>
                    <!-- Customer Navigation -->
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php"><i class="bi bi-house"></i> Home</a>
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
                    </ul>
                <?php endif; ?>

                <div class="d-flex align-items-center">
                    <?php if ($user_type === 'customer'): ?>
                        <!-- Show these buttons only for customers -->
                        <a href="buildnow.php" class="btn position-relative me-3">
                            <i class="bi bi-tools" style="font-size: 1.5rem;"></i>
                        </a>

                        <a href="preferences.php" class="btn position-relative me-3">
                            <i class="bi bi-heart" style="font-size: 1.5rem;"></i>
                            <?php if (count($user_favorites) > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= count($user_favorites) ?>
                                </span>
                            <?php endif; ?>
                        </a>

                        <a href="purchases.php" class="btn position-relative me-3">
                            <i class="bi bi-cart" style="font-size: 1.5rem;"></i>
                            <?php if (count($user_cart) > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                                    <?= count($user_cart) ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>

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
    </nav>

    <!-- Main Content -->
    <div class="container mt-5">
        <!-- Welcome Header -->
        <div class="welcome-header mb-4">
            <h2>
                <i class="bi bi-<?= $user_type == 'employee' ? 'person-badge' : 'person' ?> me-2"></i>
                Welcome <?= $user_type == 'employee' ? 'Employee' : 'Back' ?>,
                <span><?= htmlspecialchars($user['username']) ?></span>
            </h2>
            <p class="text-muted">
                <i class="bi bi-calendar me-1"></i>
                <span id="current-date"></span> •
                <span id="current-time"></span>
            </p>

        </div>

        <?php if ($user_type === 'employee'): ?>
            <!-- Employee Dashboard Content -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= count($products) ?></div>
                        <div class="stat-label">Total Products</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number">
                            <?= count(array_filter($inventory, fn($item) => $item['stock'] < 10)) ?>
                        </div>
                        <div class="stat-label">Low Stock</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number">
                            <?= count(array_filter($inventory, fn($item) => $item['stock'] === 0)) ?>
                        </div>
                        <div class="stat-label">Out of Stock</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= count($user_cart) ?></div>
                        <div class="stat-label">Pending Orders</div>
                    </div>
                </div>
            </div>

            <!-- Recent Products -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="bi bi-collection me-2"></i> Recent Products</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($products, 0, 5) as $product):
                                    $stock = array_values(array_filter($inventory, fn($item) => $item['product_id'] === $product['id']))[0]['stock'] ?? 0;
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($product['name']) ?></td>
                                        <td><?= ucfirst($product['category'] ?? 'N/A') ?></td>
                                        <td>₱<?= number_format($product['price'], 2) ?></td>
                                        <td class="<?= $stock < 10 ? 'text-danger' : '' ?>"><?= $stock ?></td>
                                        <td>
                                            <a href="products.php?delete=<?= $product['id'] ?>" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Customer Dashboard Content -->
            <div class="hero-section text-center mb-5">
                <h1 class="display-4 fw-bold mb-3">Build Your Dream PC</h1>
                <p class="lead mb-4">Customize high-performance computers tailored to your needs</p>
                <a href="buildnow.php" class="btn btn-build-now btn-lg">
                    <i class="bi bi-tools me-2"></i> Build Now
                </a>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= count($custom_builds) ?></div>
                        <div class="stat-label">Custom Builds</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= count($prebuilds) ?></div>
                        <div class="stat-label">Prebuilds</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= count($user_favorites) ?></div>
                        <div class="stat-label">Wishlist</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-number"><?= count($user_cart) ?></div>
                        <div class="stat-label">Cart Items</div>
                    </div>
                </div>
            </div>

            <!-- Featured Products -->
            <section class="mb-5">
                <h2 class="section-title mb-4">Featured Products</h2>
                <div class="row">
                    <?php foreach (array_slice($products, 0, 3) as $product):
                        $is_favorite = in_array($product['id'], array_column($user_favorites, 'product_id'));
                        ?>
                        <div class="col-md-4 mb-4">
                            <div class="card product-card h-100">
                                <img src="images/products/<?= $product['id'] ?>.jpg" class="card-img-top product-img"
                                    alt="<?= htmlspecialchars($product['name']) ?>"
                                    onerror="this.onerror=null;this.src='images/techhub.png';">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                    <span
                                        class="category-badge"><?= htmlspecialchars($product['category'] ?? 'Uncategorized') ?></span>
                                    <p class="card-text mt-2"><?= htmlspecialchars($product['description']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                                        <div>
                                            <button class="btn-favorite toggle-favorite" data-product-id="<?= $product['id'] ?>"
                                                data-type="product" data-is-favorite="<?= $is_favorite ? 'true' : 'false' ?>">
                                                <i class="bi <?= $is_favorite ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                                            </button>
                                            <button class="btn btn-sm btn-primary ms-2 add-to-cart"
                                                data-product-id="<?= $product['id'] ?>" data-type="product">
                                                <i class="bi bi-cart-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Prebuilds Section -->
            <section class="mb-5">
                <h2 class="section-title mb-4">Prebuilt Systems</h2>
                <div class="row">
                    <?php foreach (array_slice($prebuilds, 0, 3) as $prebuild):
                        $is_favorite = in_array($prebuild['id'], array_column($user_favorites, 'product_id'));
                        ?>
                        <div class="col-md-4 mb-4">
                            <div class="card product-card h-100">
                                <img src="images/products/<?= $prebuild['id'] ?>.jpg" class="card-img-top product-img"
                                    alt="<?= htmlspecialchars($prebuild['name']) ?>"
                                    onerror="this.onerror=null;this.src='images/techhub.png';">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($prebuild['name']) ?></h5>
                                    <p class="card-text"><?= htmlspecialchars($prebuild['description']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="price">₱<?= number_format($prebuild['price'], 2) ?></span>
                                        <div>
                                            <button class="btn-favorite toggle-favorite"
                                                data-product-id="<?= $prebuild['id'] ?>" data-type="prebuild"
                                                data-is-favorite="<?= $is_favorite ? 'true' : 'false' ?>">
                                                <i class="bi <?= $is_favorite ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                                            </button>
                                            <button class="btn btn-sm btn-primary ms-2 add-to-cart"
                                                data-product-id="<?= $prebuild['id'] ?>" data-type="prebuild">
                                                <i class="bi bi-cart-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
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

        // Update time display
        function updateDateTime() {
            const now = new Date();

            // Format date
            const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('current-date').textContent = now.toLocaleDateString(undefined, dateOptions);

            // Format time
            const timeOptions = { hour: 'numeric', minute: 'numeric', hour12: true };
            document.getElementById('current-time').textContent = now.toLocaleTimeString(undefined, timeOptions);
        }

        updateDateTime();

        // Update every minute
        setInterval(updateDateTime, 60000);
    </script>
</body>

</html>