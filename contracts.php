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

// Load contracts data
$contracts = json_decode(file_get_contents('data/contracts.json'), true) ?: [];
$employees = json_decode(file_get_contents('data/employees.json'), true) ?: [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new contract
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $newContract = [
            'employee' => $_POST['employee_email'],
            'details' => htmlspecialchars($_POST['details']),
            'date_added' => date('Y-m-d H:i:s'),
            'added_by' => $user['email']
        ];

        $contracts[] = $newContract;
        file_put_contents('data/contracts.json', json_encode($contracts, JSON_PRETTY_PRINT));
        header('Location: contracts.php');
        exit();
    }

    // Delete contract
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $contracts = array_filter($contracts, function ($contract) {
            return $contract['employee'] !== $_POST['employee_email'] ||
                $contract['details'] !== $_POST['details'];
        });
        file_put_contents('data/contracts.json', json_encode(array_values($contracts), JSON_PRETTY_PRINT));
        header('Location: contracts.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Management | TechHub Solution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>

<body>
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
                        <a class="nav-link active" href="contracts.php"><i class="bi bi-file-earmark-medical"></i>
                            Contracts</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center">
                    <?php if ($user_type === 'customer'): ?>
                        <!-- Show these buttons only for customers -->
                        <a href="buildnow.php" class="btn position-relative me-3">
                            <i class="bi bi-tools" style="font-size: 1.5rem;"></i>
                        </a>

                        <a href="preferences.php" class="btn position-relative me-3">
                            <i class="bi bi-heart" style="font-size: 1.5rem;"></i>
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
                <i class="bi bi-file-earmark-medical me-2"></i>
                Contract Management
                <span><?= htmlspecialchars($user['username']) ?></span>
            </h2>
            <p class="text-muted">View and manage all employee contracts</p>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="card-title mb-0">All Contracts</h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addContractModal">
                        <i class="bi bi-plus-circle"></i> Add New Contract
                    </button>
                </div>

                <?php if (!empty($contracts)): ?>
                    <div class="row">
                        <?php foreach ($contracts as $contract):
                            $employee = array_filter($employees, function ($emp) use ($contract) {
                                return $emp['email'] === $contract['employee'];
                            });
                            $employee = reset($employee);

                            // Determine contract status (example logic)
                            $statusClass = 'status-active';
                            $statusText = 'Active';
                            if (strpos(strtolower($contract['details']), 'expired') !== false) {
                                $statusClass = 'status-expired';
                                $statusText = 'Expired';
                            } elseif (strpos(strtolower($contract['details']), 'pending') !== false) {
                                $statusClass = 'status-pending';
                                $statusText = 'Pending';
                            }
                            ?>
                            <div class="col-md-6 mb-4">
                                <div class="card contract-card h-100">
                                    <div class="card-body position-relative">
                                        <span class="contract-status <?= $statusClass ?>">
                                            <?= $statusText ?>
                                        </span>
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h5 class="card-title">
                                                    <?= isset($employee['name']) ? htmlspecialchars($employee['name']) : htmlspecialchars($contract['employee']) ?>
                                                </h5>
                                                <h6 class="card-subtitle mb-2 text-muted">
                                                    <?= $employee ? htmlspecialchars($employee['position']) : 'Employee' ?>
                                                </h6>
                                            </div>
                                            <form method="POST">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="employee_email"
                                                    value="<?= htmlspecialchars($contract['employee']) ?>">
                                                <input type="hidden" name="details"
                                                    value="<?= htmlspecialchars($contract['details']) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                        <p class="card-text mt-3"><?= htmlspecialchars($contract['details']) ?></p>
                                        <div class="d-flex justify-content-between align-items-center mt-4">
                                            <small class="text-muted">
                                                Record added:
                                                <?= isset($contract['date_added']) && strtotime($contract['date_added']) ? date('M j, Y', strtotime($contract['date_added'])) : 'N/A' ?>
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
                        <h4 class="mt-3">No contracts found</h4>
                        <p class="text-muted">Add new contracts to get started</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Contract Modal -->
    <div class="modal fade" id="addContractModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Contract</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Employee</label>
                            <select class="form-select" name="employee_email" required>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?= htmlspecialchars($employee['email']) ?>">
                                        <?= htmlspecialchars($employee['name'] ?? 'Unknown') ?>
                                        (<?= htmlspecialchars($employee['email'] ?? 'Unknown') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contract Details</label>
                            <textarea class="form-control" name="details" rows="4" required
                                placeholder="E.g., Employment Contract Signed - 2024"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Contract</button>
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
</body>

</html>