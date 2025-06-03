<?php
session_start();
if (isset($_SESSION['user'])) {
  header("Location: dashboard.php");
  exit();
}

$products = json_decode(file_get_contents('data/products.json'), true) ?: [];
$prebuilds = json_decode(file_get_contents('data/prebuilds.json'), true) ?: [];
$custom_builds = json_decode(file_get_contents('data/custom_builds.json'), true) ?: [];

// Handle login/register errors passed from auth processing
$login_error = $_GET['login_error'] ?? '';
$register_error = $_GET['register_error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Welcome to TechHub Solution</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/style.css">
</head>

<body>
  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-main sticky-top">
    <div class="container-fluid">
      <div class="d-flex align-items-center">
        <img src="images/techhub.png" alt="TechHub Logo" class="me-2"
          style="height:40px; width:auto; border-radius:50%;">
        <a class="navbar-brand" href="index.php">TechHub Solution</a>
      </div>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav me-auto">
          <li class="nav-item">
            <a class="nav-link active" href="index.php"><i class="bi bi-house"></i> Home</a>
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

        <div class="d-flex align-items-center">
          <!-- Customer Preference (Heart Icon) -->
          <a href="#" class="btn position-relative me-3" data-bs-toggle="modal" data-bs-target="#loginModal">
            <i class="bi bi-heart" style="font-size: 1.5rem;"></i>
          </a>

          <!-- Build Now Icon -->
          <a href="#" class="btn position-relative me-3" data-bs-toggle="modal" data-bs-target="#loginModal">
            <i class="bi bi-tools" style="font-size: 1.5rem;"></i>
          </a>

          <!-- Cart Icon -->
          <a href="#" class="btn position-relative me-3" data-bs-toggle="modal" data-bs-target="#loginModal">
            <i class="bi bi-cart" style="font-size: 1.5rem;"></i>
          </a>

          <!-- Login/Register Buttons -->
          <button class="btn btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#registerModal">Register</button>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="container mt-5">
    <!-- Hero Section -->
    <div class="hero-section text-center mb-5">
      <h1 class="display-4 fw-bold mb-3">Build Your Dream PC</h1>
      <p class="lead mb-4">Customize high-performance computers tailored to your needs</p>
      <a href="#" class="btn btn-build-now btn-lg" data-bs-toggle="modal" data-bs-target="#loginModal">
        <i class="bi bi-tools me-2"></i> Build Now
      </a>
    </div>

    <div class="container mt-5">
      <!-- Prebuilds Section -->
      <section class="mb-5">
        <h2 class="section-title mb-4">Prebuilt Systems</h2>
        <div class="row">
          <?php foreach (array_slice($prebuilds, 0, 3) as $prebuild): ?>
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
                    <div class="product-actions">
                      <a href="#" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="bi <?= $is_favorite ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
                      </a>
                      <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="bi bi-cart-plus"></i>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- Custom Builds Section -->
      <section class="mb-5">
        <h2 class="section-title mb-4">Custom Builds</h2>
        <div class="row">
          <?php foreach (array_slice($custom_builds, 0, 3) as $build): ?>
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
                    <div class="product-actions">
                      <a href="#" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="bi bi-heart"></i>
                      </a>
                      <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="bi bi-cart-plus"></i>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <!-- Products Section -->
      <section class="mb-5">
        <h2 class="section-title mb-4">Featured Products</h2>
        <div class="row">
          <?php foreach (array_slice($products, 0, 3) as $product): ?>
            <div class="col-md-4 mb-4">
              <div class="card product-card h-100">
                <img src="images/products/<?= $product['id'] ?>.jpg" class="card-img-top product-img"
                  alt="<?= htmlspecialchars($product['name']) ?>"
                  onerror="this.onerror=null;this.src='images/techhub.png';">
                <div class="card-body">
                  <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                  <p class="card-text"><?= htmlspecialchars($product['description']) ?></p>
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="price">₱<?= number_format($product['price'], 2) ?></span>
                    <div class="product-actions">
                      <a href="#" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="bi bi-heart"></i>
                      </a>
                      <a href="#" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="bi bi-cart-plus"></i>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="text-center mt-3">
          <a href="products.php" class="btn btn-primary">View All Products</a>
        </div>
      </section>
    </div>
  </div>

  <!-- Login Modal -->
  <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="loginModalLabel">Login to TechHub Solution</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <?php if ($login_error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($login_error) ?></div>
          <?php endif; ?>
          <form id="loginForm" action="auth_process.php" method="POST">
            <input type="hidden" name="action" value="login">
            <div class="mb-3">
              <label for="loginEmail" class="form-label">Email address</label>
              <input type="email" class="form-control" id="loginEmail" name="email" required>
            </div>
            <div class="mb-3">
              <label for="loginPassword" class="form-label">Password</label>
              <input type="password" class="form-control" id="loginPassword" name="password" required>
            </div>
            <div class="mb-3 form-check">
              <input type="checkbox" class="form-check-input" id="rememberMe">
              <label class="form-check-label" for="rememberMe">Remember me</label>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
          </form>
        </div>
        <div class="modal-footer justify-content-center">
          <p>Don't have an account? <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal"
              data-bs-dismiss="modal">Register here</a></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Register Modal -->
  <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="registerModalLabel">Create Account</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <?php if ($register_error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($register_error) ?></div>
          <?php endif; ?>
          <form id="registerForm" action="auth_process.php" method="POST">
            <input type="hidden" name="action" value="register">
            <div class="mb-3">
              <label for="registerUsername" class="form-label">Username</label>
              <input type="text" class="form-control" id="registerUsername" name="username" required>
            </div>
            <div class="mb-3">
              <label for="registerEmail" class="form-label">Email address</label>
              <input type="email" class="form-control" id="registerEmail" name="email" required>
            </div>
            <div class="mb-3">
              <label for="registerPassword" class="form-label">Password</label>
              <input type="password" class="form-control" id="registerPassword" name="password" required>
            </div>
            <div class="mb-3">
              <label for="userType" class="form-label">I am a:</label>
              <select class="form-select" id="userType" name="user_type" required>
                <option value="customer">Customer</option>
                <option value="employee">Employee</option>
              </select>
            </div>
            <div class="mb-3" id="passkeyGroup" style="display: none;">
              <label for="passkey" class="form-label">Company Passkey</label>
              <input type="text" class="form-control" id="passkey" name="passkey">
              <small class="text-muted">Employees must provide the company passkey</small>
            </div>
            <button type="submit" class="btn btn-primary w-100">Register</button>
          </form>
        </div>
        <div class="modal-footer justify-content-center">
          <p>Already have an account? <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal"
              data-bs-dismiss="modal">Login here</a></p>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Show passkey field when employee is selected
    document.getElementById('userType').addEventListener('change', function () {
      const passkeyGroup = document.getElementById('passkeyGroup');
      passkeyGroup.style.display = this.value === 'employee' ? 'block' : 'none';
    });
  </script>
</body>

</html>