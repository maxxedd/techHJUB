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

// Load employee and user data
$employees = json_decode(file_get_contents('data/employees.json'), true) ?: [];
$users = json_decode(file_get_contents('data/users.json'), true) ?: [];
$departments = json_decode(file_get_contents('data/departments.json'), true) ?: [];

// Combine employee data with user accounts
$employeeData = [];
foreach ($employees as $emp) {
    $userAccount = array_filter($users, fn($u) => $u['email'] === $emp['email']);
    $employeeData[] = array_merge($emp, !empty($userAccount) ? current($userAccount) : []);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new employee
    if (isset($_POST['add_employee'])) {
        $newEmployee = [
            'employeeID' => 'EMP-' . strtoupper(uniqid()),
            'fullName' => htmlspecialchars($_POST['full_name']),
            'email' => htmlspecialchars($_POST['email']),
            'phone' => htmlspecialchars($_POST['phone']),
            'position' => htmlspecialchars($_POST['position']),
            'department' => htmlspecialchars($_POST['department']),
            'hireDate' => date('Y-m-d'),
            'status' => 'active'
        ];

        $employees[] = $newEmployee;
        file_put_contents('data/employees.json', json_encode($employees, JSON_PRETTY_PRINT));

        $_SESSION['success'] = 'Employee added successfully';
        header('Location: employees.php');
        exit();
    }

    // Delete employee
    if (isset($_POST['delete_employee'])) {
        $employees = array_filter($employees, fn($emp) => $emp['employeeID'] !== $_POST['employee_id']);
        file_put_contents('data/employees.json', json_encode(array_values($employees), JSON_PRETTY_PRINT));

        $_SESSION['success'] = 'Employee deleted successfully';
        header('Location: employees.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management | TechHub Solution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">

</head>

<body>
    <!-- Toast Notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="toast show align-items-center text-white bg-success" role="alert" aria-live="assertive"
                aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body"><?= $_SESSION['success'] ?></div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                        aria-label="Close"></button>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
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
                        <a class="nav-link active" href="employees.php"><i class="bi bi-people"></i> Employees</a>
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
                <i class="bi bi-people me-2"></i>
                Employee Management
                <span><?= htmlspecialchars($user['username']) ?></span>
            </h2>
            <p class="text-muted">Manage all employee records and information</p>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?= count($employeeData) ?></div>
                    <div class="stat-label">Total Employees</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <?= count(array_filter($employeeData, fn($emp) => !empty($emp['username']))) ?>
                    </div>
                    <div class="stat-label">Active Accounts</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <?= count(array_unique(array_column($employeeData, 'position'))) ?>
                    </div>
                    <div class="stat-label">Positions</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-number">
                        <?= count($departments) ?>
                    </div>
                    <div class="stat-label">Departments</div>
                </div>
            </div>
        </div>

        <!-- Employee Directory -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-columns me-2"></i> Employee Directory
                    </h5>
                    <div>
                        <input type="text" class="form-control me-2" id="employeeSearch"
                            placeholder="Search employees..." style="width: 250px;">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                            <i class="bi bi-plus-circle me-1"></i> Add Employee
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="employeeTable">
                        <thead class="table-light">
                            <tr>
                                <th>Employee</th>
                                <th>Position</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employeeData as $emp): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3">
                                                <?= !empty($emp['username']) ? strtoupper(substr($emp['username'], 0, 1)) : '?' ?>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?= htmlspecialchars($emp['fullName'] ?? 'N/A') ?></h6>
                                                <small class="text-muted">ID:
                                                    <?= htmlspecialchars($emp['employeeID'] ?? 'N/A') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($emp['position'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="department-badge"
                                            style="background-color: <?= getDepartmentColor($emp['department'] ?? '') ?>">
                                            <?= htmlspecialchars($emp['department'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= (!empty($emp['username']) ? 'success' : 'secondary') ?>">
                                            <?= (!empty($emp['username']) ? 'Active' : 'Inactive') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($emp['email'] ?? 'N/A') ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($emp['phone'] ?? 'N/A') ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip"
                                                title="View Profile">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip"
                                                title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="employee_id"
                                                    value="<?= htmlspecialchars($emp['employeeID']) ?>">
                                                <input type="hidden" name="delete_employee" value="1">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="tooltip" title="Delete"
                                                    onclick="return confirm('Are you sure you want to delete this employee?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($employeeData)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="bi bi-people" style="font-size: 2rem; opacity: 0.5;"></i>
                                        <p class="mt-3 text-muted">No employee records found</p>
                                        <button class="btn btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#addEmployeeModal">
                                            <i class="bi bi-plus-circle me-1"></i> Add First Employee
                                        </button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if (count($employeeData) > 10): ?>
                    <nav aria-label="Employee pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1">Previous</a>
                            </li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item">
                                <a class="page-link" href="#">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>

        <!-- Employee Cards View -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-grid me-2"></i> Employee Cards
                    </h5>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="toggleView">
                        <label class="form-check-label" for="toggleView">Grid View</label>
                    </div>
                </div>

                <div class="row" id="employeeCards">
                    <?php foreach ($employeeData as $emp): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card employee-card h-100">
                                <div class="card-body text-center">
                                    <div class="user-avatar mx-auto mb-3">
                                        <?= !empty($emp['username']) ? strtoupper(substr($emp['username'], 0, 1)) : '?' ?>
                                    </div>
                                    <h5><?= htmlspecialchars($emp['fullName'] ?? 'N/A') ?></h5>
                                    <div class="text-muted mb-2"><?= htmlspecialchars($emp['position'] ?? 'N/A') ?></div>
                                    <span class="badge bg-<?= (!empty($emp['username']) ? 'success' : 'secondary') ?> mb-3">
                                        <?= (!empty($emp['username']) ? 'Active' : 'Inactive') ?>
                                    </span>

                                    <div class="employee-details">
                                        <div class="row text-start small">
                                            <div class="col-6 mb-2">
                                                <i class="bi bi-person-badge me-1"></i>
                                                <span><?= htmlspecialchars($emp['employeeID'] ?? 'N/A') ?></span>
                                            </div>
                                            <div class="col-6 mb-2">
                                                <i class="bi bi-building me-1"></i>
                                                <span><?= htmlspecialchars($emp['department'] ?? 'N/A') ?></span>
                                            </div>
                                            <div class="col-12 mb-2">
                                                <i class="bi bi-envelope me-1"></i>
                                                <span><?= htmlspecialchars($emp['email'] ?? 'N/A') ?></span>
                                            </div>
                                            <div class="col-12 mb-2">
                                                <i class="bi bi-telephone me-1"></i>
                                                <span><?= htmlspecialchars($emp['phone'] ?? 'N/A') ?></span>
                                            </div>
                                            <div class="col-12">
                                                <i class="bi bi-calendar me-1"></i>
                                                <span>Joined:
                                                    <?= !empty($emp['hireDate']) ? htmlspecialchars($emp['hireDate']) : 'N/A' ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary flex-grow-1" data-bs-toggle="tooltip"
                                            title="View Profile">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary flex-grow-1"
                                            data-bs-toggle="tooltip" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" class="d-inline flex-grow-1">
                                            <input type="hidden" name="employee_id"
                                                value="<?= htmlspecialchars($emp['employeeID']) ?>">
                                            <input type="hidden" name="delete_employee" value="1">
                                            <button type="submit" class="btn btn-sm btn-outline-danger w-100"
                                                data-bs-toggle="tooltip" title="Delete"
                                                onclick="return confirm('Are you sure you want to delete this employee?')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone</label>
                            <input type="tel" class="form-control" name="phone">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Position</label>
                                <input type="text" class="form-control" name="position" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department</label>
                                <select class="form-select" name="department" required>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= htmlspecialchars($dept['name']) ?>">
                                            <?= htmlspecialchars($dept['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_employee" value="1" class="btn btn-primary">Add
                            Employee</button>
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
        // Employee search functionality
        document.getElementById('employeeSearch').addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#employeeTable tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Toggle between table and card view
        document.getElementById('toggleView').addEventListener('change', function () {
            const table = document.querySelector('.table-responsive');
            const cards = document.getElementById('employeeCards');

            if (this.checked) {
                table.style.display = 'none';
                cards.style.display = 'flex';
            } else {
                table.style.display = 'block';
                cards.style.display = 'none';
            }
        });

        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>

</html>

<?php
// Helper function to get department color
function getDepartmentColor($department)
{
    $colors = [
        'IT' => '#4e73df',
        'HR' => '#1cc88a',
        'Finance' => '#36b9cc',
        'Marketing' => '#f6c23e',
        'Operations' => '#e74a3b',
        'Sales' => '#858796'
    ];

    return $colors[$department] ?? '#dddfeb';
}
?>