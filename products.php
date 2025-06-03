<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}


$user = $_SESSION['user'];
$user_type = $user['type'];

// Load data
$products = json_decode(file_get_contents('data/products.json'), true) ?: [];
$prebuilds = json_decode(file_get_contents('data/prebuilds.json'), true) ?: [];
$custom_builds = json_decode(file_get_contents('data/custom_builds.json'), true) ?: [];
$inventory = json_decode(file_get_contents('data/inventory.json'), true) ?: [];
$favorites = json_decode(file_get_contents('data/favorites.json'), true) ?: [];
$cart = json_decode(file_get_contents('data/addtocart.json'), true) ?: [];

// Get user-specific data
$user_favorites = array_filter($favorites, fn($fav) => $fav['user_id'] == $user['id']);
$user_cart = array_filter($cart, fn($item) => $item['user_id'] == $user['id']);

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
        echo json_encode([
            'success' => true,
            'is_favorite' => !$is_favorite,
            'count' => count(array_filter($new_favorites, fn($fav) => $fav['user_id'] == $user['id']))
        ]);
        exit;
    }

    if (isset($_POST['cart'])) {
        $product_id = $_POST['product_id'];
        $type = $_POST['type'];

        // Check if item already in cart
        $found = false;
        foreach ($cart as &$item) {
            if ($item['user_id'] == $user['id'] && $item['product_id'] == $product_id && $item['type'] == $type) {
                $item['quantity'] += 1;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $cart[] = [
                'id' => uniqid(),
                'user_id' => $user['id'],
                'product_id' => $product_id,
                'type' => $type,
                'quantity' => 1,
                'added_at' => date('Y-m-d H:i:s')
            ];
        }

        file_put_contents('data/addtocart.json', json_encode($cart, JSON_PRETTY_PRINT));

        // Get updated user cart count
        $user_cart = array_filter($cart, fn($item) => $item['user_id'] == $user['id']);
        $cart_count = count($user_cart);

        // Return success with count
        echo json_encode([
            'success' => true,
            'count' => $cart_count
        ]);
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

        header('Location: products.php');
        exit();
    }

    // Handle Delete Product
    if (isset($_GET['delete'])) {
        $products = array_filter($products, fn($product) => $product['id'] !== $_GET['delete']);
        file_put_contents('data/products.json', json_encode(array_values($products), JSON_PRETTY_PRINT));

        $inventory = array_filter($inventory, fn($item) => $item['product_id'] !== $_GET['delete']);
        file_put_contents('data/inventory.json', json_encode(array_values($inventory), JSON_PRETTY_PRINT));

        header('Location: products.php');
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
        header('Location: products.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $user_type === 'employee' ? 'Products Management' : 'Our Products' ?> | TechHub Solution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/heartbtn.css">
    <link rel="stylesheet" href="css/products.css">
    <style>
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
        }

        .stock-high {
            color: #28a745;
        }

        .stock-medium {
            color: #ffc107;
        }

        .stock-low {
            color: #dc3545;
        }

        .category-badge {
            background-color: #6e8efb;
            color: white;
            padding: 0.25em 0.4em;
            font-size: 0.75rem;
            border-radius: 0.25rem;
            display: inline-block;
        }
    </style>
</head>

<body>
    <!-- Toast Notifications -->
    <div class="toast-container">
        <div id="toast" class="toast align-items-center text-white" role="alert" aria-live="assertive"
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
                            <a class="nav-link" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
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
                            <a class="nav-link active" href="products.php"><i class="bi bi-collection"></i> Products</a>
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
                            <a class="nav-link" href="dashboard.php"><i class="bi bi-house"></i> Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="products.php"><i class="bi bi-collection"></i> Products</a>
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
        <div class="welcome-header mb-4">
            <h2 class="section-title">
                <i class="bi bi-collection me-2"></i>
                <?= $user_type === 'employee' ? 'Products Management' : 'Our Products' ?>
            </h2>
            <p class="text-muted">
                <?= $user_type === 'employee' ? 'View and manage all product listings' : 'Browse our product catalog' ?>
            </p>
        </div>

        <?php if ($user_type === 'employee'): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Add New Product</h5>
                    <form method="POST">
                        <input type="hidden" name="add_product" value="1">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Product Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="price" class="form-label">Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="stock" class="form-label">Initial Stock</label>
                                <input type="number" class="form-control" id="stock" name="stock" value="0" min="0"
                                    required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="Processor (CPU)">Processor</option>
                                    <option value="CPU Cooler">CPU Cooler</option>
                                    <option value="Motherboard">Motherboard</option>
                                    <option value="Memory (RAM)">Memory (RAM)</option>
                                    <option value="Storage (SSD/HDD)">Storage</option>
                                    <option value="Graphics Card (GPU)">Graphics Card</option>
                                    <option value="Power Supply (PSU)">Power Supply</option>
                                    <option value="Case">Case</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="location" class="form-label">Initial Location</label>
                                <select class="form-select" id="location" name="location" required>
                                    <option value="Warehouse A">Warehouse A</option>
                                    <option value="Warehouse B">Warehouse B</option>
                                    <option value="Storefront">Storefront</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Add Product
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($user_type === 'employee'): ?>
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="h1"><?= count($products) ?></div>
                            <div class="text-muted">Total Products</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="h1"><?= count(array_unique(array_column($products, 'category'))) ?></div>
                            <div class="text-muted">Categories</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="h1 text-danger">
                                <?= count(array_filter($inventory, fn($item) => $item['stock'] < 10)) ?>
                            </div>
                            <div class="text-muted">Low Stock</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <div class="h1 text-warning">
                                <?= count(array_filter($inventory, fn($item) => $item['stock'] === 0)) ?>
                            </div>
                            <div class="text-muted">Out of Stock</div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Product Tabs -->
        <ul class="nav nav-tabs mb-4" id="productTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="components-tab" data-bs-toggle="tab" data-bs-target="#components"
                    type="button" role="tab">Components</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="prebuilds-tab" data-bs-toggle="tab" data-bs-target="#prebuilds"
                    type="button" role="tab">Prebuilt Systems</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="custom-tab" data-bs-toggle="tab" data-bs-target="#custom" type="button"
                    role="tab">Custom Builds</button>
            </li>
        </ul>

        <div class="tab-content" id="productTabsContent">
            <!-- Components Tab -->
            <div class="tab-pane fade show active" id="components" role="tabpanel">
                <div class="row">
                    <?php foreach ($products as $product):
                        $is_favorite = in_array($product['id'], array_column($user_favorites, 'product_id'));
                        $inventory_item = array_values(array_filter($inventory, fn($item) => $item['product_id'] === $product['id']))[0] ?? ['stock' => 0];
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
                                            <?php if ($user_type === 'employee'): ?>
                                                <span
                                                    class="<?= $inventory_item['stock'] < 10 ? 'stock-low' : ($inventory_item['stock'] < 25 ? 'stock-medium' : 'stock-high') ?>">
                                                    <?= $inventory_item['stock'] ?> in stock
                                                </span>
                                            <?php else: ?>
                                                <button class="btn-favorite toggle-favorite"
                                                    data-product-id="<?= $product['id'] ?>" data-type="product"
                                                    data-is-favorite="<?= $is_favorite ? 'true' : 'false' ?>">
                                                    <i class="bi <?= $is_favorite ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                                                </button>
                                                <button class="btn btn-sm btn-primary ms-2 add-to-cart"
                                                    data-product-id="<?= $product['id'] ?>" data-type="product">
                                                    <i class="bi bi-cart-plus"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($user_type === 'employee'): ?>
                                    <div class="card-footer bg-transparent">
                                        <div class="d-flex justify-content-between">
                                            <a href="?delete=<?= $product['id'] ?>" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Are you sure you want to delete this product?')">
                                                <i class="bi bi-trash"></i> Delete
                                            </a>
                                            <div class="input-group input-group-sm w-50">
                                                <input type="number" class="form-control form-control-sm"
                                                    value="<?= $inventory_item['stock'] ?>" min="0"
                                                    onchange="updateStock('<?= $product['id'] ?>', this.value)">
                                                <span class="input-group-text">units</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Prebuilds Tab -->
            <div class="tab-pane fade" id="prebuilds" role="tabpanel">
                <div class="row">
                    <?php foreach (array_slice($prebuilds, 0, 6) as $prebuild):
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
            </div>

            <!-- Custom Builds Tab -->
            <div class="tab-pane fade" id="custom" role="tabpanel">
                <div class="row">
                    <?php foreach (array_slice($custom_builds, 0, 6) as $build):
                        $is_favorite = in_array($build['id'], array_column($user_favorites, 'product_id'));
                        ?>
                        <div class="col-md-4 mb-4">
                            <div class="card product-card h-100">
                                <img src="images/builds/<?= $build['id'] ?>.jpg" class="card-img-top product-img"
                                    alt="<?= htmlspecialchars($build['name']) ?>"
                                    onerror="this.onerror=null;this.src='images/techhub.png';">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($build['name']) ?></h5>
                                    <p class="card-text"><?= htmlspecialchars($build['description']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="price">₱<?= number_format($build['price'], 2) ?></span>
                                        <div>
                                            <button class="btn-favorite toggle-favorite"
                                                data-product-id="<?= $build['id'] ?>" data-type="custom_build"
                                                data-is-favorite="<?= $is_favorite ? 'true' : 'false' ?>">
                                                <i class="bi <?= $is_favorite ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                                            </button>
                                            <button class="btn btn-sm btn-primary ms-2 add-to-cart"
                                                data-product-id="<?= $build['id'] ?>" data-type="custom_build">
                                                <i class="bi bi-cart-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
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

        // Update stock (for employees)
        function updateStock(productId, newStock) {
            if (confirm('Update stock quantity to ' + newStock + '?')) {
                window.location.href = 'products.php?update_stock=' + newStock + '&id=' + productId;
            } else {
                // Reset to original value if canceled
                event.target.value = event.target.defaultValue;
            }
        }

        // Handle favorite button clicks
        document.querySelectorAll('.toggle-favorite').forEach(btn => {
            btn.addEventListener('click', function () {
                const productId = this.dataset.productId;
                const type = this.dataset.type;
                const isFavorite = this.dataset.isFavorite === 'true';
                const icon = this.querySelector('i');

                fetch('products.php', {
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

                fetch('products.php', {
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