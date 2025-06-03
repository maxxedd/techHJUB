<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$user = $_SESSION['user'];
$user_type = $user['type'];

// Load ads data
$ads = json_decode(file_get_contents('data/ads.json'), true) ?: [];

$favorites = json_decode(file_get_contents('data/favorites.json'), true) ?: [];
$addtocart = json_decode(file_get_contents('data/addtocart.json'), true) ?: [];

$user_favorites = array_filter($favorites, fn($fav) => $fav['user_id'] == $user['id']);
$user_cart = array_filter($addtocart, fn($item) => $item['user_id'] == $user['id']);
// END OF NEW CODE

// Handle form submissions (for employees only)
if ($user_type === 'employee' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $newAd = [
                    'id' => uniqid(),
                    'title' => htmlspecialchars($_POST['title']),
                    'description' => htmlspecialchars($_POST['description']),
                    'image' => 'https://via.placeholder.com/800x400?text=' . urlencode($_POST['title']),
                    'start_date' => htmlspecialchars($_POST['start_date']),
                    'end_date' => htmlspecialchars($_POST['end_date']),
                    'is_active' => isset($_POST['is_active']),
                    'created_by' => $user['email'],
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $ads[] = $newAd;
                break;

            case 'update':
                foreach ($ads as &$ad) {
                    if ($ad['id'] === $_POST['id']) {
                        $ad['title'] = htmlspecialchars($_POST['title']);
                        $ad['description'] = htmlspecialchars($_POST['description']);
                        $ad['start_date'] = htmlspecialchars($_POST['start_date']);
                        $ad['end_date'] = htmlspecialchars($_POST['end_date']);
                        $ad['is_active'] = isset($_POST['is_active']);
                        break;
                    }
                }
                break;

            case 'delete':
                $ads = array_filter($ads, function ($ad) {
                    return $ad['id'] !== $_POST['id'];
                });
                $ads = array_values($ads); // Reindex array
                break;
        }

        file_put_contents('data/ads.json', json_encode($ads, JSON_PRETTY_PRINT));
        header('Location: ads_promo.php');
        exit();
    }
}

// Get active promotions for notification badge
$activePromos = array_filter(
    $ads,
    fn($ad) =>
    $ad['is_active'] &&
    strtotime($ad['start_date']) <= time() &&
    strtotime($ad['end_date']) >= time()
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $user_type === 'employee' ? 'Ads & Promotions Management' : 'Current Promotions'; ?> | TechHub
        Solution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .promo-card {
            transition: all 0.3s;
            height: 100%;
        }

        .promo-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .promo-img {
            height: 200px;
            object-fit: cover;
            border-radius: 8px 8px 0 0;
        }

        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .status-active {
            background-color: #28a745;
            color: white;
        }

        .status-inactive {
            background-color: #6c757d;
            color: white;
        }

        .status-upcoming {
            background-color: #ffc107;
            color: var(--text-dark);
        }

        .status-expired {
            background-color: #dc3545;
            color: white;
        }

        .no-promotions {
            min-height: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow);
        }

        .no-promotions i {
            font-size: 3rem;
            color: var(--khaki-light);
            margin-bottom: 1rem;
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
                            <a class="nav-link" href="employees.php"><i class="bi bi-people"></i> Employees</a>
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
                            <a class="nav-link active" href="ads_promo.php"><i class="bi bi-megaphone"></i> Ads & Promo</a>
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
                            <a class="nav-link active" href="ads_promo.php"><i class="bi bi-megaphone"></i> Ads & Promo</a>
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
                <i class="bi bi-megaphone me-2"></i>
                <?= $user_type === 'employee' ? 'Ads & Promotions Management' : 'Current Promotions' ?>
            </h2>
            <p class="text-muted">
                <?= $user_type === 'employee' ? 'Manage all advertising campaigns and promotions' : 'Check out our latest offers and promotions' ?>
            </p>
        </div>

        <?php if ($user_type === 'employee'): ?>
            <!-- Employee Controls -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Promotion Management</h3>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAdModal">
                            <i class="bi bi-plus-circle me-2"></i> Create New
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Promotions Grid -->
        <div class="row">
            <?php foreach ($ads as $ad):
                $currentTime = time();
                $startTime = !empty($ad['start_date']) ? strtotime($ad['start_date']) : 0;
                $endTime = !empty($ad['end_date']) ? strtotime($ad['end_date']) : 0;

                $status = '';
                $statusClass = '';

                if (empty($ad['is_active'])) {
                    $status = 'Inactive';
                    $statusClass = 'status-inactive';
                } elseif ($currentTime < $startTime) {
                    $status = 'Upcoming';
                    $statusClass = 'status-upcoming';
                } elseif ($currentTime > $endTime) {
                    $status = 'Expired';
                    $statusClass = 'status-expired';
                } else {
                    $status = 'Active';
                    $statusClass = 'status-active';
                }
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card promo-card">
                        <div class="position-relative">
                            <img src="images/techhub.png" class="card-img-top promo-img"
                                alt="<?= htmlspecialchars($ad['title']) ?>">
                            <span class="status-badge <?= $statusClass ?>"><?= $status ?></span>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($ad['title']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($ad['description']) ?></p>
                            <div class="d-flex justify-content-between text-muted small">
                                <span><i class="bi bi-calendar-event"></i> <?= date('M j, Y', $startTime) ?></span>
                                <span><i class="bi bi-calendar-check"></i> <?= date('M j, Y', $endTime) ?></span>
                            </div>
                        </div>
                        <?php if ($user_type === 'employee'): ?>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#editAdModal<?= $ad['id'] ?>">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <form method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this promotion?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $ad['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($user_type === 'employee'): ?>
                    <!-- Edit Ad Modal -->
                    <div class="modal fade" id="editAdModal<?= $ad['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Promotion</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="POST">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?= $ad['id'] ?>">
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Title</label>
                                            <input type="text" class="form-control" name="title"
                                                value="<?= htmlspecialchars($ad['title']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea class="form-control" name="description" rows="3"
                                                required><?= htmlspecialchars($ad['description']) ?></textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Start Date</label>
                                                <input type="date" class="form-control" name="start_date"
                                                    value="<?= htmlspecialchars($ad['start_date']) ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">End Date</label>
                                                <input type="date" class="form-control" name="end_date"
                                                    value="<?= htmlspecialchars($ad['end_date']) ?>" required>
                                            </div>
                                        </div>
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" name="is_active"
                                                id="isActive<?= $ad['id'] ?>" <?= $ad['is_active'] ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="isActive<?= $ad['id'] ?>">Active
                                                Promotion</label>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <?php if (empty($ads)): ?>
                <div class="col-12">
                    <div class="no-promotions">
                        <i class="bi bi-megaphone"></i>
                        <h4>No promotions available</h4>
                        <p class="text-muted">Check back later for exciting offers!</p>
                        <?php if ($user_type === 'employee'): ?>
                            <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#newAdModal">
                                <i class="bi bi-plus-circle me-2"></i> Create Your First Promotion
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($user_type === 'employee'): ?>
        <!-- New Ad Modal -->
        <div class="modal fade" id="newAdModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Promotion</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3" required></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" class="form-control" name="start_date" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">End Date</label>
                                    <input type="date" class="form-control" name="end_date" required>
                                </div>
                            </div>
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" name="is_active" id="isActiveNew" checked>
                                <label class="form-check-label" for="isActiveNew">Active Promotion</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Promotion</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

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

        // Set default dates for new promotion form
        document.addEventListener('DOMContentLoaded', function () {
            const today = new Date().toISOString().split('T')[0];
            const startDateInput = document.querySelector('#newAdModal input[name="start_date"]');
            const endDateInput = document.querySelector('#newAdModal input[name="end_date"]');

            if (startDateInput && !startDateInput.value) {
                startDateInput.value = today;

                // Set end date to 7 days from today by default
                const endDate = new Date();
                endDate.setDate(endDate.getDate() + 7);
                const endDateStr = endDate.toISOString().split('T')[0];
                endDateInput.value = endDateStr;
            }

            // Show success message if redirected after creating/updating
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success')) {
                showToast('Operation completed successfully!');
            }
        });
    </script>
</body>

</html>