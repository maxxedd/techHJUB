<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$user = $_SESSION['user'];
$user_type = $user['type'];
$is_employee = ($user_type === 'employee');



// Load company data
$company_file = 'data/company.json';
$company_data = json_decode(file_get_contents($company_file), true) ?: [
    'name' => 'TechHub Solution',
    'mission' => 'Our mission is to provide innovative technology solutions that empower businesses and individuals to achieve their full potential through cutting-edge hardware and expert support.',
    'vision' => 'To be the leading technology solutions provider in the region, recognized for our exceptional quality, customer service, and innovative approach to PC building and tech support.',
    'about' => 'Founded in 2023, TechHub Solution specializes in custom PC builds, hardware sales, and comprehensive tech support services. Our team of certified technicians and PC building experts are passionate about delivering the perfect technology solutions tailored to your specific needs.',
    'ceo' => 'John Doe',
    'founding_year' => '2023',
    'employees' => '25+',
    'customers' => '500+',
    'services' => [
        'Custom PC Builds',
        'Hardware Sales',
        'Tech Support',
        'System Maintenance',
        'Component Installation'
    ],
    'team' => [
        [
            'name' => 'Jane Smith',
            'position' => 'Chief Technology Officer',
            'bio' => 'Technology expert with 10+ years experience in system architecture and hardware solutions.',
            'image' => 'team1.jpg'
        ],
        [
            'name' => 'Michael Johnson',
            'position' => 'Lead Technician',
            'bio' => 'PC building specialist with certifications from major hardware manufacturers.',
            'image' => 'team2.jpg'
        ]
    ],
    'achievements' => [
        '2023 Tech Startup of the Year',
        'Best Customer Service Award 2023',
        'Top Rated PC Builder - Tech Magazine'
    ]
];

$favorites = json_decode(file_get_contents('data/favorites.json'), true) ?: [];
$addtocart = json_decode(file_get_contents('data/addtocart.json'), true) ?: [];

// Get user-specific favorites and cart items
$user_favorites = array_filter($favorites, fn($fav) => $fav['user_id'] == $user['id']);
$user_cart = array_filter($addtocart, fn($item) => $item['user_id'] == $user['id']);

// Handle form submission for employees
if ($is_employee && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic information
    if (isset($_POST['company_name'])) {
        $company_data['name'] = htmlspecialchars($_POST['company_name']);
    }
    if (isset($_POST['mission'])) {
        $company_data['mission'] = htmlspecialchars($_POST['mission']);
    }
    if (isset($_POST['vision'])) {
        $company_data['vision'] = htmlspecialchars($_POST['vision']);
    }
    if (isset($_POST['about'])) {
        $company_data['about'] = htmlspecialchars($_POST['about']);
    }
    if (isset($_POST['ceo'])) {
        $company_data['ceo'] = htmlspecialchars($_POST['ceo']);
    }
    if (isset($_POST['founding_year'])) {
        $company_data['founding_year'] = htmlspecialchars($_POST['founding_year']);
    }
    if (isset($_POST['employees'])) {
        $company_data['employees'] = htmlspecialchars($_POST['employees']);
    }
    if (isset($_POST['customers'])) {
        $company_data['customers'] = htmlspecialchars($_POST['customers']);
    }

    // Services
    $company_data['services'] = [];
    if (isset($_POST['services']) && is_array($_POST['services'])) {
        foreach ($_POST['services'] as $service) {
            if (!empty(trim($service))) {
                $company_data['services'][] = htmlspecialchars($service);
            }
        }
    }

    // Team members
    $company_data['team'] = [];
    if (isset($_POST['team_name']) && is_array($_POST['team_name'])) {
        $company_data['team'] = [];
        foreach ($_POST['team_name'] as $index => $name) {
            if (!empty(trim($name))) {
                $company_data['team'][] = [
                    'name' => htmlspecialchars($name),
                    'position' => htmlspecialchars($_POST['team_position'][$index]),
                    'bio' => htmlspecialchars($_POST['team_bio'][$index]),
                    'image' => $_POST['team_image'][$index] ?? 'default.jpg'
                ];
            }
        }
    }

    // Achievements
    $company_data['achievements'] = [];
    if (isset($_POST['achievements']) && is_array($_POST['achievements'])) {
        $company_data['achievements'] = [];
        foreach ($_POST['achievements'] as $achievement) {
            if (!empty(trim($achievement))) {
                $company_data['achievements'][] = htmlspecialchars($achievement);
            }
        }
    }

    // Save to file
    file_put_contents($company_file, json_encode($company_data, JSON_PRETTY_PRINT));

    // Success message
    $_SESSION['success'] = "Company information updated successfully!";
    header('Location: company.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company | TechHub Solution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/company.css">

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
                            <a class="nav-link" href="employment_records.php"><i class="bi bi-file-earmark-text"></i>
                                Records</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="products.php"><i class="bi bi-collection"></i> Products</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="company.php"><i class="bi bi-building"></i> Company</a>
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
                            <a class="nav-link active" href="company.php"><i class="bi bi-building"></i> Company</a>
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

    <!-- Toast Notification -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="toast-container">
            <div class="toast show align-items-center text-white bg-success" role="alert" aria-live="assertive"
                aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body"><?= $_SESSION['success'] ?></div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                        aria-label="Close"></button>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['success']); endif; ?>

    <!-- Company Header -->
    <div class="hero-section text-center mb-5">
        <h1 class="display-4 fw-bold mb-3">TechHub Solution</h1>
        <p class="lead mb-4">Customize high-performance computers tailored to your needs</p>
        </a>
    </div>


    <!-- Main Content -->
    <div class="container py-4">
        <!-- Stats Section -->
        <div class="row mb-5">
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stat-number"><?= $company_data['founding_year'] ?></div>
                    <div class="stat-label">Founded</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stat-number"><?= $company_data['employees'] ?></div>
                    <div class="stat-label">Employees</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <div class="stat-number"><?= $company_data['customers'] ?></div>
                    <div class="stat-label">Satisfied Customers</div>
                </div>
            </div>
        </div>

        <!-- About Section -->
        <div class="row mb-5">
            <div class="col-lg-6">
                <div class="card mb-4 h-100">
                    <div class="card-body">
                        <h2 class="section-title">Our Story</h2>
                        <p class="card-text"><?= nl2br(htmlspecialchars($company_data['about'])) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="mission-vision-card h-100 p-4">
                    <h2 class="section-title">Mission & Vision</h2>
                    <div class="mb-4">
                        <h5><i class="bi bi-stars text-primary me-2"></i>Mission</h5>
                        <p><?= nl2br(htmlspecialchars($company_data['mission'])) ?></p>
                    </div>
                    <div>
                        <h5><i class="bi bi-eye text-primary me-2"></i>Vision</h5>
                        <p><?= nl2br(htmlspecialchars($company_data['vision'])) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Core Values -->
        <div class="company-values mb-5">
            <h2 class="section-title">Our Core Values</h2>
            <div class="row">
                <div class="col-md-4 value-item">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-shield-check value-icon"></i>
                        <div>
                            <h5>Integrity</h5>
                            <p class="mb-0">We build trust through honest communication and ethical business practices.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 value-item">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-lightbulb value-icon"></i>
                        <div>
                            <h5>Innovation</h5>
                            <p class="mb-0">We constantly push boundaries to deliver cutting-edge technology solutions.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 value-item">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-people value-icon"></i>
                        <div>
                            <h5>Customer Focus</h5>
                            <p class="mb-0">Your satisfaction is our top priority in every interaction.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CEO Section -->
        <div class="card ceo-card mb-5">
            <div class="row g-0">
                <div class="col-md-4">
                    <img src="images/team/ceo.jpg" class="img-fluid ceo-img w-100" alt="CEO"
                        onerror="this.onerror=null;this.src='images/techhub.png';">
                </div>
                <div class="col-md-8">
                    <div class="card-body p-4">
                        <h2 class="section-title">Leadership</h2>
                        <h3 class="mb-3"><?= htmlspecialchars($company_data['ceo']) ?></h3>
                        <h6 class="text-muted mb-4">Chief Executive Officer</h6>
                        <p class="card-text">With over 15 years of experience in the technology industry, our CEO has
                            led TechHub Solution from a small startup to a regional leader in custom PC solutions. His
                            vision for customer-centric technology services drives our company forward.</p>
                        <div class="d-flex">
                            <a href="#" class="btn btn-outline-primary me-2"><i class="bi bi-linkedin"></i> LinkedIn</a>
                            <a href="#" class="btn btn-outline-secondary"><i class="bi bi-envelope"></i> Contact</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Services Section -->
        <div class="card mb-5 <?= $is_employee ? 'edit-mode' : '' ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title mb-0">Our Services</h2>
                    <?php if ($is_employee): ?>
                        <button class="btn btn-sm btn-primary toggle-edit">
                            <i class="bi bi-pencil-square"></i> Edit
                        </button>
                    <?php endif; ?>
                </div>

                <!-- View Mode -->
                <div class="view-mode">
                    <div class="row">
                        <?php foreach ($company_data['services'] as $service): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-body text-center">
                                        <i class="bi bi-gear" style="font-size: 2rem; color: #6e8efb;"></i>
                                        <h5 class="mt-3"><?= htmlspecialchars($service) ?></h5>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Edit Form -->
                <?php if ($is_employee): ?>
                    <form method="POST" class="edit-form">
                        <!-- Hidden fields to preserve other company data -->
                        <input type="hidden" name="company_name" value="<?= htmlspecialchars($company_data['name']) ?>">
                        <input type="hidden" name="mission" value="<?= htmlspecialchars($company_data['mission']) ?>">
                        <input type="hidden" name="vision" value="<?= htmlspecialchars($company_data['vision']) ?>">
                        <input type="hidden" name="about" value="<?= htmlspecialchars($company_data['about']) ?>">
                        <input type="hidden" name="ceo" value="<?= htmlspecialchars($company_data['ceo']) ?>">
                        <input type="hidden" name="founding_year"
                            value="<?= htmlspecialchars($company_data['founding_year']) ?>">
                        <input type="hidden" name="employees" value="<?= htmlspecialchars($company_data['employees']) ?>">
                        <input type="hidden" name="customers" value="<?= htmlspecialchars($company_data['customers']) ?>">
                        <?php if (!empty($company_data['team'])): ?>
                            <?php foreach ($company_data['team'] as $member): ?>
                                <input type="hidden" name="team_name[]" value="<?= htmlspecialchars($member['name']) ?>">
                                <input type="hidden" name="team_position[]" value="<?= htmlspecialchars($member['position']) ?>">
                                <input type="hidden" name="team_bio[]" value="<?= htmlspecialchars($member['bio']) ?>">
                                <input type="hidden" name="team_image[]" value="<?= htmlspecialchars($member['image']) ?>">
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if (!empty($company_data['achievements'])): ?>
                            <?php foreach ($company_data['achievements'] as $achievement): ?>
                                <input type="hidden" name="achievements[]" value="<?= htmlspecialchars($achievement) ?>">
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div id="services-container">
                            <?php foreach ($company_data['services'] as $service): ?>
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="services[]"
                                        value="<?= htmlspecialchars($service) ?>">
                                    <button class="btn btn-outline-danger remove-service" type="button">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-service">
                            <i class="bi bi-plus"></i> Add Service
                        </button>
                        <button type="submit" class="btn btn-primary ms-2">
                            <i class="bi bi-save"></i> Save Services
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Team Section -->
        <div class="card mb-5 <?= $is_employee ? 'edit-mode' : '' ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title mb-0">Meet Our Team</h2>
                    <?php if ($is_employee): ?>
                        <button class="btn btn-sm btn-primary toggle-edit">
                            <i class="bi bi-pencil-square"></i> Edit
                        </button>
                    <?php endif; ?>
                </div>

                <!-- View Mode -->
                <div class="view-mode">
                    <div class="row">
                        <?php foreach ($company_data['team'] as $member): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card team-card h-100">
                                    <img src="images/techhub.png/<?= htmlspecialchars($member['image']) ?>"
                                        class="card-img-top team-img" alt="<?= htmlspecialchars($member['name']) ?>"
                                        onerror="this.onerror=null;this.src='images/techhub.png';">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($member['name']) ?></h5>
                                        <h6 class="card-subtitle mb-2 text-muted">
                                            <?= htmlspecialchars($member['position']) ?>
                                        </h6>
                                        <p class="card-text"><?= htmlspecialchars($member['bio']) ?></p>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <a href="#" class="btn btn-sm btn-outline-primary me-1"><i
                                                class="bi bi-envelope"></i></a>
                                        <a href="#" class="btn btn-sm btn-outline-primary"><i
                                                class="bi bi-linkedin"></i></a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Edit Form -->
                <?php if ($is_employee): ?>
                    <form method="POST" class="edit-form">
                        <!-- Hidden fields to preserve other company data -->
                        <input type="hidden" name="company_name" value="<?= htmlspecialchars($company_data['name']) ?>">
                        <input type="hidden" name="mission" value="<?= htmlspecialchars($company_data['mission']) ?>">
                        <input type="hidden" name="vision" value="<?= htmlspecialchars($company_data['vision']) ?>">
                        <input type="hidden" name="about" value="<?= htmlspecialchars($company_data['about']) ?>">
                        <input type="hidden" name="ceo" value="<?= htmlspecialchars($company_data['ceo']) ?>">
                        <input type="hidden" name="founding_year"
                            value="<?= htmlspecialchars($company_data['founding_year']) ?>">
                        <input type="hidden" name="employees" value="<?= htmlspecialchars($company_data['employees']) ?>">
                        <input type="hidden" name="customers" value="<?= htmlspecialchars($company_data['customers']) ?>">
                        <input type="hidden" name="services[]"
                            value="<?= htmlspecialchars(implode('', $company_data['services'])) ?>">
                        <?php if (!empty($company_data['achievements'])): ?>
                            <?php foreach ($company_data['achievements'] as $achievement): ?>
                                <input type="hidden" name="achievements[]" value="<?= htmlspecialchars($achievement) ?>">
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div id="team-container">
                            <?php foreach ($company_data['team'] as $index => $member): ?>
                                <div class="row g-3 mb-3 team-member">
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" name="team_name[]" placeholder="Name"
                                            value="<?= htmlspecialchars($member['name']) ?>" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control" name="team_position[]" placeholder="Position"
                                            value="<?= htmlspecialchars($member['position']) ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" class="form-control" name="team_bio[]" placeholder="Bio"
                                            value="<?= htmlspecialchars($member['bio']) ?>" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" class="form-control" name="team_image[]" placeholder="Image filename"
                                            value="<?= htmlspecialchars($member['image']) ?>">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-outline-danger w-100 remove-member">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-member">
                            <i class="bi bi-plus"></i> Add Team Member
                        </button>
                        <button type="submit" class="btn btn-primary ms-2">
                            <i class="bi bi-save"></i> Save Team
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Achievements Section -->
        <div class="card mb-5 <?= $is_employee ? 'edit-mode' : '' ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title mb-0">Our Achievements</h2>
                    <?php if ($is_employee): ?>
                        <button class="btn btn-sm btn-primary toggle-edit">
                            <i class="bi bi-pencil-square"></i> Edit
                        </button>
                    <?php endif; ?>
                </div>

                <!-- View Mode -->
                <div class="view-mode">
                    <div class="d-flex flex-wrap">
                        <?php foreach ($company_data['achievements'] as $achievement): ?>
                            <span class="achievement-badge">
                                <i class="bi bi-trophy-fill text-warning me-1"></i>
                                <?= htmlspecialchars($achievement) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Edit Form -->
                <?php if ($is_employee): ?>
                    <form method="POST" class="edit-form">
                        <!-- Hidden fields to preserve other company data -->
                        <input type="hidden" name="company_name" value="<?= htmlspecialchars($company_data['name']) ?>">
                        <input type="hidden" name="mission" value="<?= htmlspecialchars($company_data['mission']) ?>">
                        <input type="hidden" name="vision" value="<?= htmlspecialchars($company_data['vision']) ?>">
                        <input type="hidden" name="about" value="<?= htmlspecialchars($company_data['about']) ?>">
                        <input type="hidden" name="ceo" value="<?= htmlspecialchars($company_data['ceo']) ?>">
                        <input type="hidden" name="founding_year"
                            value="<?= htmlspecialchars($company_data['founding_year']) ?>">
                        <input type="hidden" name="employees" value="<?= htmlspecialchars($company_data['employees']) ?>">
                        <input type="hidden" name="customers" value="<?= htmlspecialchars($company_data['customers']) ?>">
                        <input type="hidden" name="services[]"
                            value="<?= htmlspecialchars(implode('', $company_data['services'])) ?>">
                        <?php if (!empty($company_data['team'])): ?>
                            <?php foreach ($company_data['team'] as $member): ?>
                                <input type="hidden" name="team_name[]" value="<?= htmlspecialchars($member['name']) ?>">
                                <input type="hidden" name="team_position[]" value="<?= htmlspecialchars($member['position']) ?>">
                                <input type="hidden" name="team_bio[]" value="<?= htmlspecialchars($member['bio']) ?>">
                                <input type="hidden" name="team_image[]" value="<?= htmlspecialchars($member['image']) ?>">
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div id="achievements-container">
                            <?php foreach ($company_data['achievements'] as $achievement): ?>
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" name="achievements[]"
                                        value="<?= htmlspecialchars($achievement) ?>">
                                    <button class="btn btn-outline-danger remove-achievement" type="button">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-achievement">
                            <i class="bi bi-plus"></i> Add Achievement
                        </button>
                        <button type="submit" class="btn btn-primary ms-2">
                            <i class="bi bi-save"></i> Save Achievements
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Company Info Form (for employees) -->
        <?php if ($is_employee): ?>
            <div class="card mb-5">
                <div class="card-body">
                    <h2 class="section-title mb-4">Edit Company Information</h2>
                    <form method="POST">
                        <?php foreach ($company_data['services'] as $service): ?>
                            <input type="hidden" name="services[]" value="<?= htmlspecialchars($service) ?>">
                        <?php endforeach; ?>

                        <?php foreach ($company_data['team'] as $member): ?>
                            <input type="hidden" name="team_name[]" value="<?= htmlspecialchars($member['name']) ?>">
                            <input type="hidden" name="team_position[]" value="<?= htmlspecialchars($member['position']) ?>">
                            <input type="hidden" name="team_bio[]" value="<?= htmlspecialchars($member['bio']) ?>">
                            <input type="hidden" name="team_image[]" value="<?= htmlspecialchars($member['image']) ?>">
                        <?php endforeach; ?>

                        <?php foreach ($company_data['achievements'] as $achievement): ?>
                            <input type="hidden" name="achievements[]" value="<?= htmlspecialchars($achievement) ?>">
                        <?php endforeach; ?>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" class="form-control" id="company_name" name="company_name"
                                    value="<?= htmlspecialchars($company_data['name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="ceo" class="form-label">CEO Name</label>
                                <input type="text" class="form-control" id="ceo" name="ceo"
                                    value="<?= htmlspecialchars($company_data['ceo']) ?>" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="founding_year" class="form-label">Founding Year</label>
                                <input type="text" class="form-control" id="founding_year" name="founding_year"
                                    value="<?= htmlspecialchars($company_data['founding_year']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="employees" class="form-label">Employees</label>
                                <input type="text" class="form-control" id="employees" name="employees"
                                    value="<?= htmlspecialchars($company_data['employees']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="customers" class="form-label">Customers</label>
                                <input type="text" class="form-control" id="customers" name="customers"
                                    value="<?= htmlspecialchars($company_data['customers']) ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="mission" class="form-label">Mission</label>
                            <textarea class="form-control" id="mission" name="mission" rows="3"
                                required><?= htmlspecialchars($company_data['mission']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="vision" class="form-label">Vision</label>
                            <textarea class="form-control" id="vision" name="vision" rows="3"
                                required><?= htmlspecialchars($company_data['vision']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="about" class="form-label">About Us</label>
                            <textarea class="form-control" id="about" name="about" rows="5"
                                required><?= htmlspecialchars($company_data['about']) ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>TechHub Solution</h5>
                    <p>Innovative technology solutions for businesses and individuals.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="products.php" class="text-white">Products</a></li>
                        <li><a href="company.php" class="text-white">About Us</a></li>
                        <li><a href="contact.php" class="text-white">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Connect With Us</h5>
                    <div class="social-links">
                        <a href="#" class="text-white me-2"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white me-2"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="bi bi-linkedin"></i></a>
                        <a href="#" class="text-white"><i class="bi bi-instagram"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p class="mb-0">&copy; <?= date('Y') ?> TechHub Solution. All rights reserved.</p>
            </div>
        </div>
    </footer>

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
        // Toggle edit mode
        document.querySelectorAll('.toggle-edit').forEach(btn => {
            btn.addEventListener('click', function () {
                const card = this.closest('.card');
                card.classList.toggle('edit-mode');

                // Update button text
                if (card.classList.contains('edit-mode')) {
                    this.innerHTML = '<i class="bi bi-eye"></i> View';
                } else {
                    this.innerHTML = '<i class="bi bi-pencil-square"></i> Edit';
                }
            });
        });

        // Add new service field
        document.getElementById('add-service')?.addEventListener('click', function () {
            const container = document.getElementById('services-container');
            const div = document.createElement('div');
            div.className = 'input-group mb-2';
            div.innerHTML = `
                <input type="text" class="form-control" name="services[]" placeholder="Service name">
                <button class="btn btn-outline-danger remove-service" type="button">
                    <i class="bi bi-trash"></i>
                </button>
            `;
            container.appendChild(div);
        });

        // Remove service field
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-service')) {
                e.target.closest('.input-group').remove();
            }
        });

        // Add new team member field
        document.getElementById('add-member')?.addEventListener('click', function () {
            const container = document.getElementById('team-container');
            const div = document.createElement('div');
            div.className = 'row g-3 mb-3 team-member';
            div.innerHTML = `
                <div class="col-md-3">
                    <input type="text" class="form-control" name="team_name[]" placeholder="Name" required>
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control" name="team_position[]" placeholder="Position" required>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" name="team_bio[]" placeholder="Bio" required>
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control" name="team_image[]" placeholder="Image filename">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-danger w-100 remove-member">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(div);
        });

        // Remove team member field
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-member')) {
                e.target.closest('.team-member').remove();
            }
        });

        // Add new achievement field
        document.getElementById('add-achievement')?.addEventListener('click', function () {
            const container = document.getElementById('achievements-container');
            const div = document.createElement('div');
            div.className = 'input-group mb-2';
            div.innerHTML = `
                <input type="text" class="form-control" name="achievements[]" placeholder="Achievement">
                <button class="btn btn-outline-danger remove-achievement" type="button">
                    <i class="bi bi-trash"></i>
                </button>
            `;
            container.appendChild(div);
        });

        // Remove achievement field
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-achievement')) {
                e.target.closest('.input-group').remove();
            }
        });

        // Auto-hide success message
        const toast = document.querySelector('.toast');
        if (toast) {
            setTimeout(() => {
                const bsToast = bootstrap.Toast.getInstance(toast);
                if (bsToast) {
                    bsToast.hide();
                }
            }, 5000);
        }
    </script>
</body>

</html>