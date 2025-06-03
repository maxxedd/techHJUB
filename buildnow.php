<?php
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: login.php');
  exit();
}

$user = $_SESSION['user'];
$user_id = $user['id'];
$user_type = isset($user['type']) ? $user['type'] : 'customer';

// Load data
$products = json_decode(file_get_contents('data/products.json'), true) ?: [];
$prebuilds = json_decode(file_get_contents('data/prebuilds.json'), true) ?: [];
$custom_builds = json_decode(file_get_contents('data/custom_builds.json'), true) ?: [];
$builded = json_decode(file_get_contents('data/builded.json'), true) ?: [];
$favorites = json_decode(file_get_contents('data/favorites.json'), true) ?: [];
$addtocart = json_decode(file_get_contents('data/addtocart.json'), true) ?: [];

// Get current user's favorites
$user_favorites = array_filter($favorites, fn($fav) => isset($fav['user_id']) && $fav['user_id'] === $user_id);
$user_cart = array_filter($addtocart, fn($item) => $item['user_id'] === $user_id);


// Categorize products
$categories = [
  'Processor (CPU)' => array_filter($products, fn($p) => $p['category'] === 'Processor (CPU)'),
  'CPU Cooler' => array_filter($products, fn($p) => $p['category'] === 'CPU Cooler'),
  'Motherboard' => array_filter($products, fn($p) => $p['category'] === 'Motherboard'),
  'Memory (RAM)' => array_filter($products, fn($p) => $p['category'] === 'Memory (RAM)'),
  'Storage (SSD/HDD)' => array_filter($products, fn($p) => $p['category'] === 'Storage (SSD/HDD)'),
  'Graphics Card (GPU)' => array_filter($products, fn($p) => $p['category'] === 'Graphics Card (GPU)'),
  'Power Supply (PSU)' => array_filter($products, fn($p) => $p['category'] === 'Power Supply (PSU)'),
  'Case' => array_filter($products, fn($p) => $p['category'] === 'Case')
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Save new build
  if (isset($_POST['save_build'])) {
    $new_build = [
      'id' => uniqid(),
      'user_id' => $user_id,
      'name' => $_POST['build_name'] ?? 'My Custom Build',
      'components' => [
        'Processor (CPU)' => $_POST['Processor (CPU)'] ?? null,
        'CPU Cooler' => $_POST['CPU Cooler'] ?? null,
        'Motherboard' => $_POST['Motherboard'] ?? null,
        'Memory (RAM)' => $_POST['Memory (RAM)'] ?? null,
        'Storage (SSD/HDD)' => $_POST['Storage (SSD/HDD)'] ?? null,
        'Graphics Card (GPU)' => $_POST['Graphics Card (GPU)'] ?? null,
        'Power Supply (PSU)' => $_POST['Power Supply (PSU)'] ?? null,
        'Case' => $_POST['Case'] ?? null
      ],
      'created_at' => date('Y-m-d H:i:s'),
      'is_favorite' => false
    ];

    $builded[] = $new_build;
    file_put_contents('data/builded.json', json_encode($builded, JSON_PRETTY_PRINT));
    $success = "Build saved successfully!";
  }

  // Add build to cart
  if (isset($_POST['add_to_cart'])) {
    $build_id = $_POST['build_id'];
    $selected_build = array_filter($builded, fn($b) => $b['id'] === $build_id);

    if (!empty($selected_build)) {
      $selected_build = reset($selected_build);
      $cart = json_decode(file_get_contents('data/addtocart.json'), true) ?: [];

      $cart_item = [
        'id' => uniqid(),
        'user_id' => $user_id,
        'product_id' => $build_id,  // Changed from 'build_id'
        'type' => 'saved_build',     // New type identifier
        'quantity' => 1,             // Add quantity field
        'added_at' => date('Y-m-d H:i:s')
      ];

      $cart[] = $cart_item;
      file_put_contents('data/addtocart.json', json_encode($cart, JSON_PRETTY_PRINT));
      $success = "Build added to cart successfully!";
    }
  }

  // Delete build
  if (isset($_POST['delete_build'])) {
    $build_id = $_POST['build_id'];
    $builded = array_values(array_filter($builded, fn($b) => $b['id'] !== $build_id || $b['user_id'] !== $user_id));
    file_put_contents('data/builded.json', json_encode($builded, JSON_PRETTY_PRINT));
    $success = "Build deleted successfully!";
  }

  // Toggle favorite status
  if (isset($_POST['toggle_favorite'])) {
    $build_id = $_POST['build_id'];
    foreach ($builded as &$build) {
      if ($build['id'] === $build_id && $build['user_id'] === $user_id) {
        $build['is_favorite'] = !$build['is_favorite'];
        break;
      }
    }
    file_put_contents('data/builded.json', json_encode($builded, JSON_PRETTY_PRINT));
    $success = "Favorite status updated!";
  }
}

// Get user's saved builds
$user_builds = array_filter($builded, fn($b) => $b['user_id'] === $user_id);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Build Your PC | TechHub Solution</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/buildnow.css">
  <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
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
              <a class="nav-link" href="employment_records.php"><i class="bi bi-file-earmark-text"></i> Records</a>
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
              <a class="nav-link" href="contracts.php"><i class="bi bi-file-earmark-medical"></i> Contracts</a>
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
          <a href="buildnow.php" class="btn position-relative me-3" class="nav-link active">
            <i class="bi bi-tools" style="font-size: 1.5rem;"></i>
          </a>

          <div class="d-flex align-items-center">
            <a href="preferences.php" class="btn position-relative me-3">
              <i class="bi bi-heart" style="font-size: 1.5rem;"></i>
              <?php if (count($user_favorites) > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                  <?= count($user_favorites) ?>
                </span>
              <?php endif; ?>
            </a>

            <!-- Updated cart icon with badge -->
            <a href="purchases.php" class="btn position-relative me-3">
              <i class="bi bi-cart" style="font-size: 1.5rem;"></i>
              <?php if (count($user_cart) > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary">
                  <?= count($user_cart) ?>
                </span>
              <?php endif; ?>
            </a>

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
                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
  </nav>

  <!-- Main Content -->
  <div class="container py-5">
    <?php if (isset($success)): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="row">
      <div class="col-lg-8">
        <h1 class="mb-4">Build Your Custom PC</h1>

        <form method="POST" id="buildForm">
          <div class="build-section">
            <div class="build-category">
              <h3 class="mb-0"><i class="bi bi-pc-display-horizontal me-2"></i> CUSTOM BUILD</h3>
            </div>

            <div class="mb-4">
              <label for="build_name" class="form-label">Build Name</label>
              <input type="text" class="form-control form-control-lg" id="build_name" name="build_name"
                placeholder="My Awesome PC Build" required>
            </div>

            <!-- Processor -->
            <div class="component-section">
              <h4><i class="bi bi-cpu me-2"></i> Processor (CPU)</h4>
              <div class="row">
                <?php foreach ($categories['Processor (CPU)'] as $product): ?>
                  <div class="col-md-6">
                    <div class="component-card"
                      onclick="selectComponent(this, 'Processor (CPU)', '<?= $product['id'] ?>')">
                      <h5><?= htmlspecialchars($product['name']) ?></h5>
                      <p class="text-muted"><?= htmlspecialchars($product['description']) ?></p>
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                        <input type="radio" name="Processor (CPU)" value="<?= $product['id'] ?>" style="display: none;">
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- CPU Cooler -->
            <div class="component-section">
              <h4><i class="bi bi-fan me-2"></i> CPU Cooler</h4>
              <div class="row">
                <?php foreach ($categories['CPU Cooler'] as $product): ?>
                  <div class="col-md-6">
                    <div class="component-card" onclick="selectComponent(this, 'CPU Cooler', '<?= $product['id'] ?>')">
                      <h5><?= htmlspecialchars($product['name']) ?></h5>
                      <p class="text-muted"><?= htmlspecialchars($product['description']) ?></p>
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                        <input type="radio" name="CPU Cooler" value="<?= $product['id'] ?>" style="display: none;">
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Motherboard -->
            <div class="component-section">
              <h4><i class="bi bi-Motherboard me-2"></i> Motherboard</h4>
              <div class="row">
                <?php foreach ($categories['Motherboard'] as $product): ?>
                  <div class="col-md-6">
                    <div class="component-card" onclick="selectComponent(this, 'Motherboard', '<?= $product['id'] ?>')">
                      <h5><?= htmlspecialchars($product['name']) ?></h5>
                      <p class="text-muted"><?= htmlspecialchars($product['description']) ?></p>
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                        <input type="radio" name="Motherboard" value="<?= $product['id'] ?>" style="display: none;">
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Memory -->
            <div class="component-section">
              <h4><i class="bi bi-Memory (RAM) me-2"></i> Memory (RAM)</h4>
              <div class="row">
                <?php foreach ($categories['Memory (RAM)'] as $product): ?>
                  <div class="col-md-6">
                    <div class="component-card" onclick="selectComponent(this, 'Memory (RAM)', '<?= $product['id'] ?>')">
                      <h5><?= htmlspecialchars($product['name']) ?></h5>
                      <p class="text-muted"><?= htmlspecialchars($product['description']) ?></p>
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                        <input type="radio" name="Memory (RAM)" value="<?= $product['id'] ?>" style="display: none;">
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Storage -->
            <div class="component-section">
              <h4><i class="bi bi-hdd-stack me-2"></i> Storage (SSD/HDD)</h4>
              <div class="row">
                <?php foreach ($categories['Storage (SSD/HDD)'] as $product): ?>
                  <div class="col-md-6">
                    <div class="component-card"
                      onclick="selectComponent(this, 'Storage (SSD/HDD)', '<?= $product['id'] ?>')">
                      <h5><?= htmlspecialchars($product['name']) ?></h5>
                      <p class="text-muted"><?= htmlspecialchars($product['description']) ?></p>
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                        <input type="radio" name="Storage (SSD/HDD)" value="<?= $product['id'] ?>" style="display: none;">
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- GPU -->
            <div class="component-section">
              <h4><i class="bi bi-Graphics Card (GPU)-card me-2"></i> Graphics Card (GPU)</h4>
              <div class="row">
                <?php foreach ($categories['Graphics Card (GPU)'] as $product): ?>
                  <div class="col-md-6">
                    <div class="component-card"
                      onclick="selectComponent(this, 'Graphics Card (GPU)', '<?= $product['id'] ?>')">
                      <h5><?= htmlspecialchars($product['name']) ?></h5>
                      <p class="text-muted"><?= htmlspecialchars($product['description']) ?></p>
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                        <input type="radio" name="Graphics Card (GPU)" value="<?= $product['id'] ?>"
                          style="display: none;">
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- PSU -->
            <div class="component-section">
              <h4><i class="bi bi-lightning-charge me-2"></i> Power Supply (PSU)</h4>
              <div class="row">
                <?php foreach ($categories['Power Supply (PSU)'] as $product): ?>
                  <div class="col-md-6">
                    <div class="component-card"
                      onclick="selectComponent(this, 'Power Supply (PSU)', '<?= $product['id'] ?>')">
                      <h5><?= htmlspecialchars($product['name']) ?></h5>
                      <p class="text-muted"><?= htmlspecialchars($product['description']) ?></p>
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                        <input type="radio" name="Power Supply (PSU)" value="<?= $product['id'] ?>"
                          style="display: none;">
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Case -->
            <div class="component-section">
              <h4><i class="bi bi-pc me-2"></i> Case</h4>
              <div class="row">
                <?php foreach ($categories['Case'] as $product): ?>
                  <div class="col-md-6">
                    <div class="component-card" onclick="selectComponent(this, 'Case', '<?= $product['id'] ?>')">
                      <h5><?= htmlspecialchars($product['name']) ?></h5>
                      <p class="text-muted"><?= htmlspecialchars($product['description']) ?></p>
                      <div class="d-flex justify-content-between align-items-center">
                        <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                        <input type="radio" name="Case" value="<?= $product['id'] ?>" style="display: none;">
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <button type="submit" name="save_build" class="btn btn-primary btn-lg w-100 py-3">
              <i class="bi bi-save me-2"></i> Save Build
            </button>
          </div>
        </form>

        <!-- Prebuilds Section -->
        <div class="build-section mt-5">
          <h2 class="mb-4"><i class="bi bi-box-seam me-2"></i> Prebuilt Systems</h2>
          <div class="row">
            <?php foreach (array_slice($prebuilds, 0, 3) as $prebuild): ?>
              <div class="col-md-4 mb-4">
                <div class="card prebuild-card h-100">
                  <img src="images/techhub.png" class="card-img-top"
                    alt="<?= htmlspecialchars($build['name']) ?>">
                  <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($prebuild['name']) ?></h5>
                    <p class="card-text text-muted">
                      <?= isset($prebuild['description']) ? htmlspecialchars($prebuild['description']) : '' ?>
                    </p>
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="price">₱<?= number_format($prebuild['price'], 2) ?></span>
                      <a href="#" class="btn btn-sm btn-outline-primary">View Details</a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="text-center mt-3">
            <a href="products.php?type=prebuild" class="btn btn-primary">View All Prebuilds</a>
          </div>
        </div>

        <!-- Custom Builds Section -->
        <div class="build-section mt-4">
          <h2 class="mb-4"><i class="bi bi-tools me-2"></i> Custom Builds</h2>
          <div class="row">
            <?php foreach (array_slice($custom_builds, 0, 3) as $build): ?>
              <div class="col-md-4 mb-4">
                <div class="card custombuild-card h-100">
                  <img src="images/techhub.png" class="card-img-top"
                    alt="<?= htmlspecialchars($build['name']) ?>">
                  <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($build['name']) ?></h5>
                    <p class="card-text text-muted"><?= htmlspecialchars($build['description']) ?></p>
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="price">₱<?= number_format($build['price'], 2) ?></span>
                      <a href="#" class="btn btn-sm btn-outline-primary">View Details</a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="text-center mt-3">
            <a href="products.php?type=custom" class="btn btn-primary">View All Custom Builds</a>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <!-- Build Summary -->
        <div class="build-section">
          <h3 class="mb-4"><i class="bi bi-clipboard-check me-2"></i> Build Summary</h3>
          <div id="buildSummary">
            <p class="text-muted">Select components to see your build summary</p>
          </div>

          <div class="summary-item">
            <h5>Shipping</h5>
            <p class="mb-0">Standard Delivery (3-5 business days)</p>
          </div>

          <div class="summary-item">
            <h5>Subtotal: <span id="totalPrice" class="float-end">₱0.00</span></h5>
          </div>

          <div class="mt-4">
            <small class="text-muted">Available payment methods: Credit Card, GCash, PayPal</small>
          </div>
        </div>

        <!-- Saved Builds -->
        <?php if (!empty($user_builds)): ?>
          <div class="build-section mt-4">
            <h3 class="mb-4"><i class="bi bi-collection me-2"></i> Your Saved Builds</h3>

            <?php foreach ($user_builds as $build): ?>
              <div class="saved-build-card">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <h5><?= htmlspecialchars($build['name']) ?></h5>
                    <p class="small text-muted mb-2">
                      Created:
                      <?php
                      if (!empty($build['created_at']) && strtotime($build['created_at']) !== false) {
                        echo date('M j, Y', strtotime($build['created_at']));
                      } else {
                        echo 'Unknown';
                      }
                      ?>
                    </p>

                    <?php
                    $total_price = 0;
                    if (isset($build['components']) && is_array($build['components'])) {
                      foreach ($build['components'] as $component_id) {
                        $product = current(array_filter($products, fn($p) => $p['id'] === $component_id));
                        if ($product) {
                          $total_price += $product['price'];
                        }
                      }
                    }
                    ?>
                    <p class="price mb-0">₱<?= number_format($total_price, 2) ?></p>
                  </div>

                  <form method="POST" class="d-inline">
                    <input type="hidden" name="build_id" value="<?= $build['id'] ?>">
                  </form>
                </div>

                <div class="build-actions">
                  <form method="POST" class="w-100">
                    <input type="hidden" name="build_id" value="<?= $build['id'] ?>">
                    <button type="submit" name="add_to_cart" class="add-to-cart-btn w-100">
                      <i class="bi bi-cart-plus me-1"></i> Add to Cart
                    </button>
                  </form>
                </div>

                <div class="build-actions">
                  <form method="POST" class="w-100"
                    onsubmit="return confirm('Are you sure you want to delete this build?');">
                    <input type="hidden" name="build_id" value="<?= $build['id'] ?>">
                    <button type="submit" name="delete_build" class="delete-build-btn w-100">
                      <i class="bi bi-trash me-1"></i> Delete Build
                    </button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <button class="ai-assistant-btn" data-bs-toggle="modal" data-bs-target="#aiModal">
    <i class="bi bi-robot"></i>
  </button>

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
    function selectComponent(element, type, id) {
      // Remove selected class from all cards of this type
      document.querySelectorAll(`.component-card input[name="${type}"]`).forEach(radio => {
        radio.parentElement.classList.remove('selected');
      });

      // Add selected class to clicked card
      element.classList.add('selected');

      // Check the radio button
      const radio = element.querySelector('input[type="radio"]');
      radio.checked = true;

      // Update build summary
      updateBuildSummary();
    }

    function updateBuildSummary() {
      const form = document.getElementById('buildForm');
      const formData = new FormData(form);
      const components = {};
      let totalPrice = 0;

      // Get all selected components
      formData.forEach((value, key) => {
        if (key !== 'build_name' && key !== 'save_build') {
          const product = findProductById(value);
          if (product) {
            components[key] = product;
            totalPrice += parseFloat(product.price);
          }
        }
      });

      // Update summary display
      const summaryDiv = document.getElementById('buildSummary');
      const totalPriceSpan = document.getElementById('totalPrice');

      if (Object.keys(components).length === 0) {
        summaryDiv.innerHTML = '<p class="text-muted">Select components to see your build summary</p>';
        totalPriceSpan.textContent = '₱0.00';
        return;
      }

      let html = '<div class="list-group list-group-flush">';
      for (const [type, product] of Object.entries(components)) {
        html += `
          <div class="list-group-item px-0 py-2">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <strong>${type.charAt(0).toUpperCase() + type.slice(1)}</strong><br>
                <small class="text-muted">${product.name}</small>
              </div>
              <span class="price">₱${parseFloat(product.price).toFixed(2)}</span>
            </div>
          </div>
        `;
      }
      html += '</div>';

      summaryDiv.innerHTML = html;
      totalPriceSpan.textContent = `₱${totalPrice.toFixed(2)}`;
    }

    function findProductById(id) {
      const products = <?= json_encode($products) ?>;
      return products.find(p => p.id === id);
    }

    // Initialize event listeners
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.component-card').forEach(card => {
        card.addEventListener('click', function () {
          const radio = this.querySelector('input[type="radio"]');
          if (radio) {
            selectComponent(this, radio.name, radio.value);
          }
        });
      });

      // Update summary when form changes
      document.getElementById('buildForm').addEventListener('change', updateBuildSummary);
    });
  </script>
</body>

</html>