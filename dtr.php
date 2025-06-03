<?php
session_start();
date_default_timezone_set('Asia/Manila'); // Set timezone to Philippines
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$user = $_SESSION['user'];
$user_type = $user['type'];

// Initialize user favorites to avoid undefined variable error
$user_favorites = [];

// Only employees can access
if ($user_type !== 'employee') {
    header('Location: dashboard.php');
    exit();
}

// Load DTR data
$dtr = json_decode(file_get_contents('data/dtr.json'), true) ?: [];

// Handle clock in/out
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $current_time = date('Y-m-d H:i:s');

    // Check if user is trying to clock in twice or clock out without clocking in
    $filtered_entries = array_filter($dtr, fn($entry) => $entry['employee'] === $user['email']);
    $last_entry = end($filtered_entries);

    if ($action === 'Clock In' && $last_entry && $last_entry['action'] === 'Clock In') {
        $_SESSION['error'] = 'You are already clocked in';
    } elseif ($action === 'Clock Out' && (!$last_entry || $last_entry['action'] === 'Clock Out')) {
        $_SESSION['error'] = 'You need to clock in first';
    } else {
        $dtr[] = [
            'employee' => $user['email'],
            'datetime' => $current_time,
            'action' => $action,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'location' => 'Office' // In a real app, you might get this from GPS or user input
        ];
        file_put_contents('data/dtr.json', json_encode($dtr, JSON_PRETTY_PRINT));
        $_SESSION['success'] = $action . ' recorded at ' . date('h:i A', strtotime($current_time));
    }

    header('Location: dtr.php');
    exit();
}

// Get user's entries
$user_entries = array_filter($dtr, fn($entry) => $entry['employee'] === $user['email']);
$user_entries = array_reverse($user_entries);
$last_entry = reset($user_entries) ?: null;

// Calculate today's working hours
$today = date('Y-m-d');
$today_entries = array_filter($user_entries, fn($entry) => date('Y-m-d', strtotime($entry['datetime'])) === $today);
$total_hours = 0;
$clock_in_time = null;

usort($today_entries, function ($a, $b) {
    return strtotime($a['datetime']) - strtotime($b['datetime']);
});

foreach ($today_entries as $entry) {
    if ($entry['action'] === 'Clock In' && $clock_in_time === null) {
        $clock_in_time = strtotime($entry['datetime']);
    } elseif ($entry['action'] === 'Clock Out' && $clock_in_time !== null) {
        $clock_out_time = strtotime($entry['datetime']);
        if ($clock_out_time > $clock_in_time) {  // Ensure positive time difference
            $total_hours += ($clock_out_time - $clock_in_time) / 3600; // Convert seconds to hours
        }
        $clock_in_time = null;
    }
}
if ($clock_in_time !== null) {
    $current_time = time();
    if ($current_time > $clock_in_time) {  // Ensure positive time difference
        $total_hours += ($current_time - $clock_in_time) / 3600;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Time Record | TechHub Solution</title>
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

        <?php if (isset($_SESSION['error'])): ?>
            <div class="toast show align-items-center text-white bg-danger" role="alert" aria-live="assertive"
                aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body"><?= $_SESSION['error'] ?></div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                        aria-label="Close"></button>
                </div>
            </div>
            <?php unset($_SESSION['error']); ?>
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
                        <a class="nav-link" href="employees.php">
                            <i class="bi bi-people"></i> Employees
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="jobs.php"><i class="bi bi-briefcase"></i> Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="dtr.php"><i class="bi bi-clock-history"></i> DTR</a>
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
                <i class="bi bi-clock-history me-2"></i>
                Daily Time Record
                <span><?= htmlspecialchars($user['username']) ?></span>
            </h2>
            <p class="text-muted">
                <i class="bi bi-calendar me-1"></i> <?= date('l, F j, Y') ?> •
                <span id="live-clock"><?= date('h:i:s A') ?></span> (Philippine Time)
            </p>
        </div>

        <!-- Time Clock Section -->
        <div class="row mb-4">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title mb-4">Time Clock</h5>

                        <div class="d-flex justify-content-center gap-3 mb-4">
                            <form method="POST" class="w-100">
                                <button type="submit" name="action" value="Clock In"
                                    class="btn btn-success btn-lg w-100 py-3">
                                    <i class="bi bi-box-arrow-in-right me-2"></i> Clock In
                                </button>
                            </form>
                            <form method="POST" class="w-100">
                                <button type="submit" name="action" value="Clock Out"
                                    class="btn btn-danger btn-lg w-100 py-3">
                                    <i class="bi bi-box-arrow-right me-2"></i> Clock Out
                                </button>
                            </form>
                        </div>

                        <div class="current-status">
                            <?php if ($last_entry): ?>
                                <div
                                    class="alert alert-<?= $last_entry['action'] === 'Clock In' ? 'success' : 'danger' ?> mb-0">
                                    <i
                                        class="bi bi-<?= $last_entry['action'] === 'Clock In' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
                                    Currently <?= $last_entry['action'] === 'Clock In' ? 'CLOCKED IN' : 'CLOCKED OUT' ?>
                                    <small class="d-block mt-1">Last action:
                                        <?= date('M j, h:i A', strtotime($last_entry['datetime'])) ?></small>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    No time records today
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Today's Summary</h5>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span><i class="bi bi-calendar-check me-2"></i> Date:</span>
                            <span class="fw-bold"><?= date('F j, Y') ?></span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span><i class="bi bi-hourglass-split me-2"></i> Total Hours:</span>
                            <span class="fw-bold"><?= number_format($total_hours, 2) ?> hrs</span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span><i class="bi bi-activity me-2"></i> Status:</span>
                            <span
                                class="badge bg-<?= $last_entry && $last_entry['action'] === 'Clock In' ? 'success' : 'danger' ?>">
                                <?= $last_entry && $last_entry['action'] === 'Clock In' ? 'On Duty' : 'Off Duty' ?>
                            </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-pin-map me-2"></i> Last Location:</span>
                            <span
                                class="fw-bold"><?= $last_entry ? htmlspecialchars($last_entry['location']) : 'N/A' ?></span>
                        </div>

                        <hr class="my-4">

                        <div class="text-center">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Next payroll cutoff: <?= date('F j', strtotime('next friday')) ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Time Records Section -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-check me-2"></i> My Time Records
                    </h5>
                    <div>
                        <button class="btn btn-outline-primary me-2" id="print-btn">
                            <i class="bi bi-printer me-1"></i> Print
                        </button>
                        <button class="btn btn-primary" id="export-btn">
                            <i class="bi bi-download me-1"></i> Export
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="dtr-table">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Duration</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Group entries by date
                            $grouped_entries = [];
                            foreach ($user_entries as $entry) {
                                $date = date('Y-m-d', strtotime($entry['datetime']));
                                $grouped_entries[$date][] = $entry;
                            }

                            // Display each day's records
                            foreach ($grouped_entries as $date => $entries):
                                $clock_in = null;
                                $clock_out = null;
                                $duration = 0;

                                // Find matching in/out pairs
                                foreach ($entries as $entry) {
                                    if ($entry['action'] === 'Clock In') {
                                        $clock_in = $entry;
                                    } elseif ($entry['action'] === 'Clock Out' && $clock_in) {
                                        $clock_out = $entry;

                                        // Calculate duration
                                        $in_time = strtotime($clock_in['datetime']);
                                        $out_time = strtotime($clock_out['datetime']);
                                        $duration += ($out_time - $in_time) / 3600; // hours
                            
                                        // Display the pair
                                        ?>
                                        <tr>
                                            <td><?= date('M j, Y', $in_time) ?></td>
                                            <td><?= date('h:i A', $in_time) ?></td>
                                            <td><?= date('h:i A', $out_time) ?></td>
                                            <td>
                                                <?php if (isset($out_time, $in_time) && $out_time > $in_time): ?>
                                                    <?= number_format(($out_time - $in_time) / 3600, 2) ?> hrs
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td><?= isset($clock_in['location']) ? htmlspecialchars($clock_in['location']) : 'N/A' ?>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-<?= $out_time > strtotime('today 17:00:00') ? 'warning' : 'success' ?>">
                                                    <?= $out_time > strtotime('today 17:00:00') ? 'Overtime' : 'Regular' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip"
                                                    title="View Details">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php
                                        $clock_in = null;
                                        $clock_out = null;
                                    }
                                }

                                // Handle unpaired clock in (forgot to clock out)
                                if ($clock_in && !$clock_out):
                                    ?>
                                    <tr class="table-warning">
                                        <td><?= date('M j, Y', strtotime($clock_in['datetime'])) ?></td>
                                        <td><?= date('h:i A', strtotime($clock_in['datetime'])) ?></td>
                                        <td colspan="2" class="text-danger">No clock out recorded</td>
                                        <td><?= isset($clock_in['location']) ? htmlspecialchars($clock_in['location']) : 'N/A' ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger">Pending</span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip"
                                                title="Report Issue">
                                                <i class="bi bi-exclamation-triangle"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php
                                endif;
                            endforeach;

                            if (empty($grouped_entries)):
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-clock-history" style="font-size: 2rem;"></i>
                                        <p class="mt-2">No time records found</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if (count($grouped_entries) > 10): ?>
                    <nav aria-label="DTR pagination">
                        <ul class="pagination justify-content-center mt-4">
                            <li class="page-item disabled">
                                <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
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
        // Live clock update
        function updateClock() {
            const now = new Date();
            const options = {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true,
                timeZone: 'Asia/Manila'
            };
            document.getElementById('live-clock').textContent = now.toLocaleTimeString('en-US', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Print functionality
        document.getElementById('print-btn').addEventListener('click', function () {
            window.print();
        });

        // Export functionality (simplified)
        document.getElementById('export-btn').addEventListener('click', function () {
            alert('Export feature would generate a CSV/PDF file in a real application');
        });
    </script>
</body>

</html>