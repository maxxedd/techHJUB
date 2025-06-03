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
$favorites = json_decode(file_get_contents('data/favorites.json'), true) ?: [];
$cart = json_decode(file_get_contents('data/addtocart.json'), true) ?: [];

// Get user's favorites
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
        echo json_encode([
            'success' => true,
            'count' => count(array_filter($cart, fn($item) => $item['user_id'] == $user['id']))
        ]);
        exit;
    }
}

// Organize favorites by type
// Organize favorites by type
$favorite_products = [];
$favorite_prebuilds = [];
$favorite_custom_builds = [];

foreach ($user_favorites as $fav) {
    switch ($fav['type']) {
        case 'product':
            foreach ($products as $product) {
                // Cast IDs to string for consistent comparison
                if ((string) $product['id'] === (string) $fav['product_id']) {
                    $favorite_products[] = $product;
                    break;
                }
            }
            break;
        case 'prebuild':
            foreach ($prebuilds as $prebuild) {
                // Cast IDs to string for consistent comparison
                if ((string) $prebuild['id'] === (string) $fav['product_id']) {
                    $favorite_prebuilds[] = $prebuild;
                    break;
                }
            }
            break;
        case 'custom_build':
            foreach ($custom_builds as $build) {
                // Cast IDs to string for consistent comparison
                if ((string) $build['id'] === (string) $fav['product_id']) {
                    $favorite_custom_builds[] = $build;
                    break;
                }
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Preferences | TechHub Solution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/heartbtn.css">
    <link rel="stylesheet" href="css/preference.css">

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

    <!-- Navigation -->
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
                    <!-- Icons -->
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
    <div class="container py-5">
        <h1 class="mb-4"><i class="bi bi-heart-fill text-danger me-2"></i> My Favorites</h1>

        <!-- Prebuilds Section -->
        <div class="favorite-section">
            <h3 class="mb-4">Prebuilt Systems</h3>
            <?php if (!empty($favorite_prebuilds)): ?>
                <div class="row">
                    <?php foreach ($favorite_prebuilds as $prebuild): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card favorite-card h-100">
                                <div class="position-relative">
                                    <img src="images/products/<?= $prebuild['id'] ?>.jpg" class="card-img-top product-img"
                                        alt="<?= htmlspecialchars($prebuild['name']) ?>"
                                        onerror="this.onerror=null;this.src='images/techhub.png';">
                                    <form method="POST">
                                        <input type="hidden" name="ajax" value="true">
                                        <input type="hidden" name="favorite" value="true">
                                        <input type="hidden" name="product_id" value="<?= $prebuild['id'] ?>">
                                        <input type="hidden" name="type" value="prebuild">
                                    </form>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($prebuild['name']) ?></h5>
                                    <p class="card-text"><?= htmlspecialchars($prebuild['description']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="price">₱<?= number_format($prebuild['price'], 2) ?></span>
                                        <form method="POST">
                                            <input type="hidden" name="ajax" value="true">
                                            <input type="hidden" name="cart" value="true">
                                            <input type="hidden" name="product_id" value="<?= $prebuild['id'] ?>">
                                            <input type="hidden" name="type" value="prebuild">
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-favorites">
                    <i class="bi bi-pc-display text-muted" style="font-size: 3rem;"></i>
                    <p class="mt-3 text-muted">No favorite prebuilds yet</p>
                    <a href="products.php?type=prebuild" class="btn btn-primary mt-2">
                        <i class="bi bi-search"></i> Browse Prebuilds
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Custom Builds Section -->
        <div class="favorite-section">
            <h3 class="mb-4">Custom Builds</h3>
            <?php if (!empty($favorite_custom_builds)): ?>
                <div class="row">
                    <?php foreach ($favorite_custom_builds as $build): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card favorite-card h-100">
                                <div class="position-relative">
                                    <img src="images/builds/<?= $build['id'] ?>.jpg" class="card-img-top product-img"
                                        alt="<?= htmlspecialchars($build['name']) ?>"
                                        onerror="this.onerror=null;this.src='images/techhub.png';">
                                    <form method="POST">
                                        <input type="hidden" name="ajax" value="true">
                                        <input type="hidden" name="favorite" value="true">
                                        <input type="hidden" name="product_id" value="<?= $build['id'] ?>">
                                        <input type="hidden" name="type" value="custom_build">
                                    </form>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($build['name']) ?></h5>
                                    <p class="card-text"><?= htmlspecialchars($build['description']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="price">₱<?= number_format($build['price'], 2) ?></span>
                                        <form method="POST">
                                            <input type="hidden" name="ajax" value="true">
                                            <input type="hidden" name="cart" value="true">
                                            <input type="hidden" name="product_id" value="<?= $build['id'] ?>">
                                            <input type="hidden" name="type" value="custom_build">
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-favorites">
                    <i class="bi bi-pc text-muted" style="font-size: 3rem;"></i>
                    <p class="mt-3 text-muted">No favorite custom builds yet</p>
                    <a href="buildnow.php" class="btn btn-primary mt-2">
                        <i class="bi bi-tools"></i> Create Custom Build
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Products Section -->
        <div class="favorite-section">
            <h3 class="mb-4">Products</h3>
            <?php if (!empty($favorite_products)): ?>
                <div class="row">
                    <?php foreach ($favorite_products as $product): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card favorite-card h-100">
                                <div class="position-relative">
                                    <img src="images/products/<?= $product['id'] ?>.jpg" class="card-img-top product-img"
                                        alt="<?= htmlspecialchars($product['name']) ?>"
                                        onerror="this.onerror=null;this.src='images/techhub.png';">
                                    <form method="POST">
                                        <input type="hidden" name="ajax" value="true">
                                        <input type="hidden" name="favorite" value="true">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <input type="hidden" name="type" value="product">
                                    </form>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                    <span
                                        class="category-badge"><?= htmlspecialchars($product['category'] ?? 'Uncategorized') ?></span>
                                    <p class="card-text mt-2"><?= htmlspecialchars($product['description']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                                        <form method="POST">
                                            <input type="hidden" name="ajax" value="true">
                                            <input type="hidden" name="cart" value="true">
                                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                            <input type="hidden" name="type" value="product">
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-favorites">
                    <i class="bi bi-cpu text-muted" style="font-size: 3rem;"></i>
                    <p class="mt-3 text-muted">No favorite products yet</p>
                    <a href="products.php" class="btn btn-primary mt-2">
                        <i class="bi bi-search"></i> Browse Products
                    </a>
                </div>
            <?php endif; ?>
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

        // Handle form submissions with fetch
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(this);

                fetch('preference.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (formData.has('favorite')) {
                                // Update favorites count in navbar
                                const favCount = document.querySelector('.badge.bg-danger');
                                if (favCount) {
                                    favCount.textContent = data.count;
                                }

                                // Remove the card from UI
                                const card = this.closest('.col-md-4');
                                if (card) {
                                    card.remove();
                                    showToast('Removed from favorites', 'success');
                                }
                            } else if (formData.has('cart')) {
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
                                showToast('Added to cart', 'success');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('Failed to process request', 'danger');
                    });
            });
        });

        // Auto-hide toasts after 5 seconds
        document.addEventListener('DOMContentLoaded', function () {
            const toasts = document.querySelectorAll('.toast.show');
            toasts.forEach(toast => {
                setTimeout(() => {
                    const bsToast = bootstrap.Toast.getInstance(toast);
                    if (bsToast) {
                        bsToast.hide();
                    }
                }, 5000);
            });
        });
    </script>
</body>

</html>