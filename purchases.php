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
$addtocart = json_decode(file_get_contents('data/addtocart.json'), true) ?: [];
$purchases = json_decode(file_get_contents('data/purchases.json'), true) ?: [];

$favorites = json_decode(file_get_contents('data/favorites.json'), true) ?: [];
$builded = json_decode(file_get_contents('data/builded.json'), true) ?: [];

// Get user's cart items and purchases
$user_cart = array_filter($addtocart, fn($item) => $item['user_id'] == $user['id']);
$user_purchases = array_filter($purchases, fn($purchase) => $purchase['user_id'] == $user['id']);
$user_favorites = array_filter($favorites, fn($fav) => $fav['user_id'] == $user['id']);


// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    // Handle cart quantity update
    if (isset($_POST['update_quantity'])) {
        $cart_id = $_POST['cart_id'];
        $quantity = (int) $_POST['quantity'];

        foreach ($addtocart as &$item) {
            if ($item['id'] == $cart_id && $item['user_id'] == $user['id']) {
                $item['quantity'] = $quantity;
                break;
            }
        }

        file_put_contents('data/addtocart.json', json_encode($addtocart, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true]);
        exit;
    }

    // Handle remove from cart
    if (isset($_POST['remove_from_cart'])) {
        $cart_id = $_POST['cart_id'];

        $addtocart = array_filter($addtocart, fn($item) => $item['id'] != $cart_id || $item['user_id'] != $user['id']);

        file_put_contents('data/addtocart.json', json_encode(array_values($addtocart), JSON_PRETTY_PRINT));
        echo json_encode(['success' => true, 'count' => count(array_filter($addtocart, fn($item) => $item['user_id'] == $user['id']))]);
        exit;
    }

    // Handle checkout
    if (isset($_POST['checkout'])) {
        $cart_ids = is_array($_POST['cart_ids'])
            ? $_POST['cart_ids']
            : explode(',', $_POST['cart_ids']);

        $total_amount = 0;
        $items = [];

        // Get checked items from cart
        foreach ($addtocart as $key => $item) {
            if (in_array($item['id'], $cart_ids) && $item['user_id'] == $user['id']) {
                // Find product details
                $product = null;
                $found = false;

                if ($item['type'] == 'product') {
                    foreach ($products as $p) {
                        if ($p['id'] == $item['product_id']) {
                            $product = $p;
                            $found = true;
                            break;
                        }
                    }
                } elseif ($item['type'] == 'prebuild') {
                    foreach ($prebuilds as $p) {
                        if ($p['id'] == $item['product_id']) {
                            $product = $p;
                            $found = true;
                            break;
                        }
                    }
                } elseif ($item['type'] == 'custom_build') {
                    foreach ($custom_builds as $p) {
                        if ($p['id'] == $item['product_id']) {
                            $product = $p;
                            $found = true;
                            break;
                        }
                    }
                } elseif ($item['type'] == 'saved_build') {
                    foreach ($builded as $b) {
                        if ($b['id'] == $item['product_id']) {
                            // Calculate total price from components
                            $total_price = 0;
                            foreach ($b['components'] as $component_id) {
                                $component_found = false;
                                foreach ($products as $p) {
                                    if ($p['id'] === $component_id) {
                                        $total_price += $p['price'];
                                        $component_found = true;
                                        break;
                                    }
                                }
                                if (!$component_found) {
                                    // Log missing component error
                                    error_log("Component not found: $component_id for build {$b['id']}");
                                }
                            }

                            $product = [
                                'id' => $b['id'],
                                'name' => $b['name'] ?? 'Custom Build',
                                'price' => $total_price,
                                'description' => 'Custom PC Build'
                            ];
                            $found = true;
                            break;
                        }
                    }
                }

                if ($found && $product) {
                    $total_amount += $product['price'] * $item['quantity'];
                    $items[] = [
                        'product_id' => $product['id'],
                        'name' => $product['name'],
                        'type' => $item['type'],
                        'quantity' => $item['quantity'],
                        'price' => $product['price'],
                        'subtotal' => $product['price'] * $item['quantity']
                    ];
                    // Remove from cart
                    unset($addtocart[$key]);
                } else {
                    // Log product not found error
                    error_log("Product not found: {$item['product_id']} (type: {$item['type']})");
                }
            }
        }

        if (!empty($items)) {
            $building_fee = $total_amount * 0.05;
            if ($building_fee > 180) {
                $building_fee = 180;
            }

            $total_amount += $building_fee;

            // Create new purchase
            $purchase_id = uniqid();
            $purchases[] = [
                'id' => $purchase_id,
                'user_id' => $user['id'],
                'items' => $items,
                'subtotal' => $total_amount - $building_fee, // Store original subtotal
                'building_fee' => $building_fee,
                'total_amount' => $total_amount,
                'status' => 'pending',
                'payment_method' => $_POST['payment_method'] ?? 'COD',
                //'shipping_address' => $_POST['shipping_address'] ?? '',
                'contact_number' => $_POST['contact_number'] ?? '',
                'created_at' => date('Y-m-d H:i:s')
            ];

            // Save data
            file_put_contents('data/addtocart.json', json_encode(array_values($addtocart), JSON_PRETTY_PRINT));
            file_put_contents('data/purchases.json', json_encode($purchases, JSON_PRETTY_PRINT));


            $employees = json_decode(file_get_contents('data/users.json'), true) ?: [];
            $employee_emails = array_column(array_filter($employees, function ($e) {
                return $e['type'] === 'employee';
            }), 'email');

            if (!empty($employee_emails)) {
                $assigned_to = $employee_emails[array_rand($employee_emails)];

                $jobs = json_decode(file_get_contents('data/jobs.json'), true) ?: [];

                $description = "Process and fulfill this order:\n";
                foreach ($items as $item) {
                    $description .= sprintf(
                        "- %s (x%d): ₱%s\n",
                        htmlspecialchars($item['name']),
                        $item['quantity'],
                        number_format($item['subtotal'], 2)
                    );
                }
                $description .= "\nTotal Amount: ₱" . number_format($total_amount, 2);

                $newJob = [
                    'id' => uniqid(),
                    'title' => 'Order #' . $purchase_id,
                    'description' => $description,
                    'assigned_to' => $assigned_to,
                    'status' => 'Pending',
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_by' => 'system'
                ];
                $jobs[] = $newJob;
                file_put_contents('data/jobs.json', json_encode($jobs, JSON_PRETTY_PRINT));
            }

            echo json_encode(['success' => true, 'count' => count(array_filter($addtocart, fn($item) => $item['user_id'] == $user['id']))]);
            exit;

        } else {
            error_log("Checkout failed - no valid items found");
            echo json_encode(['success' => false, 'message' => 'No valid items found for checkout']);
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
        // ... [existing cart and checkout handlers] ...

        // Handle order cancellation
        if (isset($_POST['cancel_order'])) {
            $purchase_id = $_POST['purchase_id'];
            $updated = false;

            foreach ($purchases as &$purchase) {
                if ($purchase['id'] == $purchase_id && $purchase['user_id'] == $user['id']) {
                    // Only allow cancellation of pending orders
                    if ($purchase['status'] == 'pending') {
                        $purchase['status'] = 'cancelled';
                        $updated = true;
                    }
                    break;
                }
            }

            if ($updated) {
                file_put_contents('data/purchases.json', json_encode($purchases, JSON_PRETTY_PRINT));
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Order cannot be cancelled']);
            }
            exit;
        }
    }
    // After creating the purchase, create a job task
    $employees = json_decode(file_get_contents('data/users.json'), true) ?: [];
    $employee_emails = array_column(array_filter($employees, function ($e) {
        return $e['type'] === 'employee';
    }), 'email');

    if (!empty($employee_emails)) {
        $assigned_to = $employee_emails[array_rand($employee_emails)];

        $jobs = json_decode(file_get_contents('data/jobs.json'), true) ?: [];
        $newJob = [
            'id' => uniqid(),
            'title' => 'Order #' . $purchase_id,
            'description' => 'Process and fulfill order #' . $purchase_id,
            'assigned_to' => $assigned_to,
            'status' => 'Pending',
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => 'system'
        ];
        $jobs[] = $newJob;
        file_put_contents('data/jobs.json', json_encode($jobs, JSON_PRETTY_PRINT));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
        // ... existing cart and cancellation handlers ...

        // Handle order received (mark as completed)
        if (isset($_POST['order_received'])) {
            $purchase_id = $_POST['purchase_id'];
            $updated = false;

            foreach ($purchases as &$purchase) {
                if ($purchase['id'] == $purchase_id && $purchase['user_id'] == $user['id']) {
                    // Only allow marking pending orders as completed
                    if ($purchase['status'] == 'pending') {
                        $purchase['status'] = 'completed';
                        $updated = true;

                        $jobs = json_decode(file_get_contents('data/jobs.json'), true) ?: [];
                        foreach ($jobs as &$job) {
                            if (strpos($job['title'], 'Order #' . $purchase_id) !== false) {
                                $job['status'] = 'Completed';
                                $job['completed_at'] = date('Y-m-d H:i:s');
                                break;
                            }
                        }
                        file_put_contents('data/jobs.json', json_encode($jobs, JSON_PRETTY_PRINT));
                    }
                    break;
                }
            }

            if ($updated) {
                file_put_contents('data/purchases.json', json_encode($purchases, JSON_PRETTY_PRINT));
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Order cannot be marked as received']);
            }
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchases | TechHub Solution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        a[href="purchases.php"].btn.nav-link.active,
        a[href="purchases.php"].btn.active,
        a[href="purchases.php"].btn.position-relative.me-3 {
            background: hwb(212 0% 0%) !important;
            color: #fff !important;
            border-radius: 50%;
            box-shadow: 0 0 0 0.2rem rgba(0, 98, 255, 0.25);
        }

        a[href="purchases.php"].btn.position-relative.me-3 i {
            color: #fff !important;
        }

        .cart-item-checkbox {
            transform: scale(1.5);
            margin-right: 15px;
        }

        .checkout-btn-container {
            position: sticky;
            bottom: 20px;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            display: none;
        }

        .purchase-card {
            transition: all 0.3s;
        }

        .purchase-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .btn-order-received {
            background-color: #198754;
            color: white;
        }

        .btn-order-received:hover {
            background-color: #146c43;
        }
    </style>
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
                            <a class="nav-link" href="dashboard.php"><i class="bi bi-house"></i> Home</a>
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
                            <i class="bi bi-cart active" style="font-size: 1.5rem;"></i>
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
                <i class="bi bi-cart me-2"></i>
                My Purchases
            </h2>
            <p class="text-muted">
                Manage your cart and view purchase history
            </p>
        </div>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs mb-4" id="purchasesTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="cart-tab" data-bs-toggle="tab" data-bs-target="#cart" type="button"
                    role="tab">
                    <i class="bi bi-cart3 me-1"></i> My Cart (<?= count($user_cart) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="purchases-tab" data-bs-toggle="tab" data-bs-target="#purchases"
                    type="button" role="tab">
                    <i class="bi bi-receipt me-1"></i> Purchase History (<?= count($user_purchases) ?>)
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="purchasesTabsContent">
            <!-- Cart Tab -->
            <div class="tab-pane fade show active" id="cart" role="tabpanel" aria-labelledby="cart-tab">
                <?php if (empty($user_cart)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i> Your cart is empty. Start shopping now!
                    </div>
                <?php else: ?>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5><i class="bi bi-cart3 me-2"></i> Cart Items</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th width="50px"></th>
                                                    <th>Product</th>
                                                    <th>Price</th>
                                                    <th>Quantity</th>
                                                    <th>Subtotal</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($user_cart as $cart_item):
                                                    // Find product details
                                                    $product = null;
                                                    if ($cart_item['type'] == 'product') {
                                                        $product = array_values(array_filter($products, fn($p) => $p['id'] == $cart_item['product_id']))[0] ?? null;
                                                    } elseif ($cart_item['type'] == 'prebuild') {
                                                        $product = array_values(array_filter($prebuilds, fn($p) => $p['id'] == $cart_item['product_id']))[0] ?? null;
                                                    } elseif ($cart_item['type'] == 'custom_build') {
                                                        $product = array_values(array_filter($custom_builds, fn($p) => $p['id'] == $cart_item['product_id']))[0] ?? null;
                                                    } elseif ($cart_item['type'] == 'saved_build') {
                                                        $build = array_values(array_filter($builded, fn($b) => $b['id'] == $cart_item['product_id']))[0] ?? null;
                                                        if ($build) {
                                                            // Calculate total price from components
                                                            $total_price = 0;
                                                            foreach ($build['components'] as $component_id) {
                                                                $component = array_values(array_filter($products, fn($p) => $p['id'] === $component_id))[0] ?? null;
                                                                if ($component) {
                                                                    $total_price += $component['price'];
                                                                }
                                                            }

                                                            $product = [
                                                                'id' => $build['id'],
                                                                'name' => $build['name'] ?? 'Custom Build',
                                                                'price' => $total_price,
                                                                'description' => 'Custom PC Build'
                                                            ];
                                                        }
                                                    }

                                                    if ($product):
                                                        ?>
                                                        <tr class="cart-item-row" data-cart-id="<?= $cart_item['id'] ?>">
                                                            <td>
                                                                <input type="checkbox" class="form-check-input cart-item-checkbox"
                                                                    data-cart-id="<?= $cart_item['id'] ?>"
                                                                    data-price="<?= $product['price'] ?>">
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <img src="
                                                                    <?=
                                                                        ($cart_item['type'] == 'saved_build') ? 'images/techhub.png' :
                                                                        (($cart_item['type'] == 'custom_build') ? 'images/builds/' : 'images/products/') . $product['id'] . '.jpg'
                                                                        ?>" class="rounded me-3" width="60" height="60"
                                                                        onerror="this.onerror=null;this.src='images/techhub.png';">
                                                                    <div>
                                                                        <h6 class="mb-0"><?= htmlspecialchars($product['name']) ?>
                                                                        </h6>
                                                                        <small
                                                                            class="text-muted"><?= ucfirst($cart_item['type']) ?></small>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>₱<?= number_format($product['price'], 2) ?></td>
                                                            <td>
                                                                <div class="input-group" style="width: 120px;">
                                                                    <button class="btn btn-outline-secondary quantity-minus"
                                                                        type="button">-</button>
                                                                    <input type="number"
                                                                        class="form-control text-center quantity-input"
                                                                        value="<?= $cart_item['quantity'] ?>" min="1">
                                                                    <button class="btn btn-outline-secondary quantity-plus"
                                                                        type="button">+</button>
                                                                </div>
                                                            </td>
                                                            <td class="subtotal">
                                                                ₱<?= number_format($product['price'] * $cart_item['quantity'], 2) ?>
                                                            </td>
                                                            <td>
                                                                <button class="btn btn-sm btn-danger remove-from-cart"
                                                                    data-cart-id="<?= $cart_item['id'] ?>">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endif; endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5><i class="bi bi-receipt me-2"></i> Order Summary</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotal:</span>
                                        <span id="summary-subtotal">₱0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Building/Handling Fee:</span>
                                        <span id="summary-building-fee">₱0.00</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>Total:</span>
                                        <span id="summary-total">₱0.00</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Checkout Button Container (will be shown when items are selected) -->
                            <div class="checkout-btn-container" id="checkout-btn-container">
                                <button class="btn btn-primary w-100" data-bs-toggle="modal"
                                    data-bs-target="#checkoutModal">
                                    <i class="bi bi-credit-card me-2"></i> Proceed to Checkout
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Purchases Tab -->
            <div class="tab-pane fade" id="purchases" role="tabpanel" aria-labelledby="purchases-tab">
                <?php if (empty($user_purchases)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i> You haven't made any purchases yet.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach (array_reverse($user_purchases) as $purchase): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card purchase-card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="mb-0">Order #<?= substr($purchase['id'], 0, 8) ?></h5>
                                            <small
                                                class="text-muted"><?= date('M d, Y h:i A', strtotime($purchase['created_at'])) ?></small>
                                        </div>
                                        <span class="status-badge status-<?= $purchase['status'] ?>">
                                            <?= ucfirst($purchase['status']) ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <h6>Items:</h6>
                                            <ul class="list-group list-group-flush">
                                                <?php foreach ($purchase['items'] as $item): ?>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <?= htmlspecialchars($item['name']) ?>
                                                            <small class="text-muted d-block"><?= ucfirst($item['type']) ?></small>
                                                        </div>
                                                        <div class="text-end">
                                                            <span>₱<?= number_format($item['price'], 2) ?> x
                                                                <?= $item['quantity'] ?></span>
                                                            <div class="fw-bold">₱<?= number_format($item['subtotal'], 2) ?></div>
                                                        </div>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>

                                        <div class="d-flex justify-content-between mt-2">
                                            <span>Subtotal:</span>
                                            <span>₱<?= number_format($purchase['subtotal'], 2) ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Building/Handling Fee:</span>
                                            <span>₱<?= number_format($purchase['building_fee'], 2) ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between fw-bold mt-2">
                                            <span>Total Amount:</span>
                                            <span>₱<?= number_format($purchase['total_amount'], 2) ?></span>
                                        </div>

                                        <?php if (!empty($purchase['contact_number'])): ?>
                                            <div class="mt-2">
                                                <h6>Contact Number:</h6>
                                                <p><?= htmlspecialchars($purchase['contact_number']) ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer text-end">
                                        <?php if ($purchase['status'] == 'pending'): ?>
                                            <button class="btn btn-sm btn-order-received order-received"
                                                data-purchase-id="<?= $purchase['id'] ?>">
                                                <i class="bi bi-check-circle me-1"></i> Order Received
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger cancel-order"
                                                data-purchase-id="<?= $purchase['id'] ?>">
                                                Cancel Order
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Checkout Modal -->
    <div class="modal fade" id="checkoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-credit-card me-2"></i> Checkout</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="checkoutForm">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-3">Shipping Information</h5>

                                <div class="mb-3">
                                    <label for="fullName" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="fullName"
                                        value="<?= htmlspecialchars($user['username']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="contactNumber" class="form-label">Contact Number</label>
                                    <input type="tel" class="form-control" id="contactNumber" required>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h5 class="mb-3">Order Summary</h5>

                                <div class="card mb-3">
                                    <div class="card-body">
                                        <ul class="list-group list-group-flush" id="checkout-items-list">
                                            <!-- Items will be populated by JavaScript -->
                                        </ul>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span id="modal-subtotal">₱0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Building/Handling Fee:</span>
                                    <span id="modal-building-fee">₱0.00</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between fw-bold mb-4">
                                    <span>Total:</span>
                                    <span id="modal-total">₱0.00</span>
                                </div>

                                <h5 class="mb-3">Payment Method</h5>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="paymentMethod" id="cod"
                                        value="COD" checked>
                                    <label class="form-check-label" for="cod">
                                        <i class="bi bi-cash-coin me-2"></i> Cash on Pick-up (COP)
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="paymentMethod" id="gcash"
                                        value="GCash">
                                    <label class="form-check-label" for="gcash">
                                        <i class="bi bi-phone me-2"></i> GCash
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="paymentMethod" id="creditCard"
                                        value="Credit Card">
                                    <label class="form-check-label" for="creditCard">
                                        <i class="bi bi-credit-card me-2"></i> Credit/Debit Card
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmCheckout">
                        <i class="bi bi-check-circle me-2"></i> Confirm Order
                    </button>
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

        // Cart functionality
        document.addEventListener('DOMContentLoaded', function () {
            // Quantity controls
            document.querySelectorAll('.quantity-minus').forEach(btn => {
                btn.addEventListener('click', function () {
                    const input = this.nextElementSibling;
                    if (parseInt(input.value) > 1) {
                        input.value = parseInt(input.value) - 1;
                        updateCartItem(input);
                    }
                });
            });

            document.querySelectorAll('.quantity-plus').forEach(btn => {
                btn.addEventListener('click', function () {
                    const input = this.previousElementSibling;
                    input.value = parseInt(input.value) + 1;
                    updateCartItem(input);
                });
            });

            document.querySelectorAll('.quantity-input').forEach(input => {
                input.addEventListener('change', function () {
                    if (parseInt(this.value) < 1) this.value = 1;
                    updateCartItem(this);
                });
            });

            // Remove from cart
            document.querySelectorAll('.remove-from-cart').forEach(btn => {
                btn.addEventListener('click', function () {
                    const cartId = this.dataset.cartId;
                    if (confirm('Are you sure you want to remove this item from your cart?')) {
                        fetch('purchases.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `ajax=true&remove_from_cart=true&cart_id=${cartId}`
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    showToast('Item removed from cart', 'success');
                                    document.querySelector(`.cart-item-row[data-cart-id="${cartId}"]`).remove();
                                    const cartCount = document.querySelector('.badge.bg-primary');
                                    if (cartCount) {
                                        cartCount.textContent = data.count;
                                    }
                                    updateSummary();
                                } else {
                                    showToast('Failed to remove item', 'danger');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showToast('Failed to remove item', 'danger');
                            });
                    }
                });
            });

            // Checkbox selection
            document.querySelectorAll('.cart-item-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    updateSummary();
                });
            });

            // Confirm checkout
            document.getElementById('confirmCheckout').addEventListener('click', function () {
                const selectedItems = Array.from(document.querySelectorAll('.cart-item-checkbox:checked'))
                    .map(checkbox => checkbox.dataset.cartId);

                const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
                const contactNumber = document.getElementById('contactNumber').value;

                if (!contactNumber) {
                    showToast('Please fill in your contact number', 'danger');
                    return;
                }
                fetch('purchases.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `ajax=true&checkout=true&cart_ids=${selectedItems.join(',')}&payment_method=${paymentMethod}&contact_number=${encodeURIComponent(contactNumber)}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('Order placed successfully!', 'success');

                            // Update cart count in navbar
                            const cartCount = document.querySelector('.badge.bg-primary');
                            if (cartCount) {
                                cartCount.textContent = data.count;
                            }

                            // Close modal and reload page
                            const modal = bootstrap.Modal.getInstance(document.getElementById('checkoutModal'));
                            modal.hide();

                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            showToast(data.message || 'Failed to place order', 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Failed to place order', 'danger');
                    });
            });

            // Initialize summary
            updateSummary();
        });

        // Update cart item quantity
        function updateCartItem(input) {
            const row = input.closest('tr');
            const cartId = row.dataset.cartId;
            const quantity = parseInt(input.value);
            const price = parseFloat(row.querySelector('.cart-item-checkbox').dataset.price);

            fetch('purchases.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=true&update_quantity=true&cart_id=${cartId}&quantity=${quantity}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update subtotal
                        row.querySelector('.subtotal').textContent = `₱${(price * quantity).toFixed(2)}`;

                        // Update summary if item is checked
                        if (row.querySelector('.cart-item-checkbox').checked) {
                            updateSummary();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Failed to update quantity', 'danger');
                });
        }

        // Update order summary
        // Update order summary
        function updateSummary() {
            let subtotal = 0;
            const selectedItems = [];

            document.querySelectorAll('.cart-item-checkbox:checked').forEach(checkbox => {
                const row = checkbox.closest('tr');
                const price = parseFloat(checkbox.dataset.price);
                const quantity = parseInt(row.querySelector('.quantity-input').value);
                subtotal += price * quantity;

                selectedItems.push({
                    cartId: checkbox.dataset.cartId,
                    name: row.querySelector('h6').textContent,
                    type: row.querySelector('small').textContent,
                    price: price,
                    quantity: quantity,
                    subtotal: price * quantity
                });
            });

            let buildingFee = subtotal * 0.05;
            buildingFee = Math.min(buildingFee, 180);
            buildingFee = parseFloat(buildingFee.toFixed(2));

            const total = subtotal + buildingFee;

            // Update summary in cart
            document.getElementById('summary-subtotal').textContent = `₱${subtotal.toFixed(2)}`;
            document.getElementById('summary-building-fee').textContent = `₱${buildingFee.toFixed(2)}`;
            document.getElementById('summary-total').textContent = `₱${total.toFixed(2)}`;

            // Always update modal summary (remove visibility check)
            document.getElementById('modal-subtotal').textContent = `₱${subtotal.toFixed(2)}`;
            document.getElementById('modal-building-fee').textContent = `₱${buildingFee.toFixed(2)}`;
            document.getElementById('modal-total').textContent = `₱${total.toFixed(2)}`;

            // Always populate items list
            const itemsList = document.getElementById('checkout-items-list');
            itemsList.innerHTML = '';

            selectedItems.forEach(item => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-center';
                li.innerHTML = `
            <div>
                ${item.name}
                <small class="text-muted d-block">${item.type}</small>
            </div>
            <div class="text-end">
                <span>₱${item.price.toFixed(2)} x ${item.quantity}</span>
                <div class="fw-bold">₱${item.subtotal.toFixed(2)}</div>
            </div>
        `;
                itemsList.appendChild(li);
            });

            // Show/hide checkout button container
            const checkoutContainer = document.getElementById('checkout-btn-container');
            checkoutContainer.style.display = selectedItems.length > 0 ? 'block' : 'none';
        }


        // When checkout modal is shown, update its content
        document.getElementById('checkoutModal').addEventListener('show.bs.modal', function () {
            updateSummary();
        });

        // Cancel order functionality
        document.querySelectorAll('.cancel-order').forEach(btn => {
            btn.addEventListener('click', function () {
                const purchaseId = this.dataset.purchaseId;
                if (confirm('Are you sure you want to cancel this order?')) {
                    fetch('purchases.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `ajax=true&cancel_order=true&purchase_id=${purchaseId}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showToast('Order cancelled successfully', 'success');

                                // Update UI
                                const card = this.closest('.card');
                                const statusBadge = card.querySelector('.status-badge');

                                // Update status badge
                                statusBadge.classList.remove('status-pending');
                                statusBadge.classList.add('status-cancelled');
                                statusBadge.textContent = 'Cancelled';

                                // Remove cancel button
                                this.remove();
                            } else {
                                showToast(data.message || 'Failed to cancel order', 'danger');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showToast('Failed to cancel order', 'danger');
                        });
                }
            });
        });

        // Order received functionality (ADDED)
        document.querySelectorAll('.order-received').forEach(btn => {
            btn.addEventListener('click', function () {
                const purchaseId = this.dataset.purchaseId;
                if (confirm('Are you sure you have received this order?')) {
                    fetch('purchases.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `ajax=true&order_received=true&purchase_id=${purchaseId}`
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showToast('Order marked as received!', 'success');

                                // Update UI
                                const card = this.closest('.card');
                                const statusBadge = card.querySelector('.status-badge');

                                // Update status badge
                                statusBadge.classList.remove('status-pending');
                                statusBadge.classList.add('status-completed');
                                statusBadge.textContent = 'Completed';

                                // Remove action buttons
                                this.closest('.card-footer').innerHTML = '';
                            } else {
                                showToast(data.message || 'Failed to update order', 'danger');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showToast('Failed to update order', 'danger');
                        });
                }
            });
        });

    </script>
</body>

</html>