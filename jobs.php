<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$user = $_SESSION['user'];

if ($user['type'] !== 'employee') {
    header('Location: dashboard.php');
    exit();
}

$jobs = json_decode(file_get_contents('data/jobs.json'), true) ?: [];
$employees = json_decode(file_get_contents('data/users.json'), true) ?: [];

// Handle add job
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title']) && isset($_POST['assigned_to'])) {
    $newJob = [
        'id' => uniqid(),
        'title' => $_POST['title'],
        'description' => $_POST['description'] ?? '',
        'assigned_to' => $_POST['assigned_to'],
        'status' => 'Pending',
        'created_at' => date('Y-m-d H:i:s'),
        'created_by' => $user['email']
    ];
    $jobs[] = $newJob;
    file_put_contents('data/jobs.json', json_encode($jobs, JSON_PRETTY_PRINT));
    header('Location: jobs.php');
    exit();
}

// Handle job status update
if (isset($_GET['complete'])) {
    $jobId = $_GET['complete'];
    foreach ($jobs as &$job) {
        if ($job['id'] === $jobId) {
            $job['status'] = 'Completed';
            $job['completed_at'] = date('Y-m-d H:i:s');
            break;
        }
    }
    file_put_contents('data/jobs.json', json_encode($jobs, JSON_PRETTY_PRINT));
    header('Location: jobs.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks Management | Techhub Solution</title>
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
                        <a class="nav-link active" href="jobs.php"><i class="bi bi-briefcase"></i> Tasks</a>
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


                    <div class="d-flex align-items-center">
                        <div class="dropdown">
                            <button class="btn dropdown-toggle d-flex align-items-center text-white" type="button"
                                data-bs-toggle="dropdown">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                </div>
                                <span><?php echo htmlspecialchars($user['username']); ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <h6 class="dropdown-header">Logged in as Employee</h6>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="logout.php"><i
                                            class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
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
                <i class="bi bi-briefcase me-2"></i> Tasks Management
                <span style="color: var(--khaki-dark);"><?php echo htmlspecialchars($user['username']); ?></span>
            </h2>
            <p class="mb-0">Assign and track task assignments for employees</p>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <?php
            $totalJobs = count($jobs);
            $pendingJobs = count(array_filter($jobs, fn($job) => $job['status'] === 'Pending'));
            $completedJobs = count(array_filter($jobs, fn($job) => $job['status'] === 'Completed'));
            ?>
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalJobs; ?></div>
                    <div class="stat-label">Total Tasks</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $pendingJobs; ?></div>
                    <div class="stat-label">Pending Tasks</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $completedJobs; ?></div>
                    <div class="stat-label">Completed Tasks</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Assign New Task</h5>
                        <form method="POST">
                            <div class="mb-3">
                                <label for="title" class="form-label">Task Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="assigned_to" class="form-label">Assign To</label>
                                <select class="form-select" id="assigned_to" name="assigned_to" required>
                                    <option value="">Select Employee</option>
                                    <?php foreach ($employees as $emp): ?>
                                        <?php if ($emp['type'] === 'employee'): ?>
                                            <option value="<?php echo htmlspecialchars($emp['email']); ?>">
                                                <?php echo htmlspecialchars($emp['username']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-1"></i> Assign Task
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Recent Activity</h5>
                        <div class="list-group list-group-flush">
                            <?php
                            $recentJobs = array_slice(array_reverse($jobs), 0, 3);
                            foreach ($recentJobs as $job):
                                $datetime = new DateTime($job['created_at']);
                                ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($job['title']); ?></h6>
                                        <small class="text-muted"><?php echo $datetime->format('M j'); ?></small>
                                    </div>
                                    <small class="text-muted">Assigned to:
                                        <?php echo htmlspecialchars($job['assigned_to']); ?></small>
                                    <span
                                        class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $job['status'])); ?>">
                                        <?php echo htmlspecialchars($job['status']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($recentJobs)): ?>
                                <div class="list-group-item">
                                    <p class="mb-0 text-muted">No recent job activity</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- All Jobs Table -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-4">All Tasks</h5>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse($jobs) as $job):
                                $createdAt = new DateTime($job['created_at']);
                                $assignedTo = array_filter($employees, fn($emp) => $emp['email'] === $job['assigned_to']);
                                $assignedToName = !empty($assignedTo) ? current($assignedTo)['username'] : $job['assigned_to'];
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($job['title']); ?></strong>
                                        <?php if (!empty($job['description'])): ?>
                                            <small
                                                class="d-block text-muted"><?php echo nl2br(htmlspecialchars($job['description'])); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($assignedToName); ?></td>
                                    <td>
                                        <span
                                            class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $job['status'])); ?>">
                                            <?php echo htmlspecialchars($job['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $createdAt->format('M j, Y'); ?>
                                        <small class="d-block text-muted"><?php echo $createdAt->format('h:i A'); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($job['status'] !== 'Completed'): ?>
                                            <a href="jobs.php?complete=<?php echo $job['id']; ?>"
                                                class="btn btn-sm btn-success">
                                                <i class="bi bi-check-circle"></i> Complete
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Completed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($jobs)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No Tasks found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div> <!-- AI Assistant Button -->
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