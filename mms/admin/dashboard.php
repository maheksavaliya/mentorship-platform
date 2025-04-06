<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get admin details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

// Get total counts
$stmt = $pdo->query("SELECT 
    (SELECT COUNT(*) FROM users WHERE role = 'mentor' AND status = 'active') as total_mentors,
    (SELECT COUNT(*) FROM users WHERE role = 'mentee' AND status = 'active') as total_mentees,
    (SELECT COUNT(*) FROM appointments WHERE date >= CURRENT_DATE) as total_appointments,
    (SELECT COUNT(*) FROM mentorship_requests WHERE status = 'pending') as pending_requests");
$counts = $stmt->fetch();

// Get recent users
$stmt = $pdo->query("SELECT * FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                     ORDER BY created_at DESC LIMIT 5");
$recentUsers = $stmt->fetchAll();

// Get recent appointments
$stmt = $pdo->query("SELECT a.*, m.name as mentor_name, e.name as mentee_name 
                     FROM appointments a 
                     JOIN users m ON a.mentor_id = m.id 
                     JOIN users e ON a.mentee_id = e.id 
                     WHERE a.date >= CURRENT_DATE 
                     ORDER BY a.date ASC, a.time ASC LIMIT 5");
$recentAppointments = $stmt->fetchAll();

// Get system statistics
$stmt = $pdo->query("SELECT 
    (SELECT COUNT(*) FROM mentorships WHERE status = 'active') as active_mentorships,
    (SELECT COUNT(*) FROM appointments WHERE status = 'completed') as completed_sessions,
    (SELECT COUNT(*) FROM users WHERE status = 'pending') as pending_approvals");
$stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Mentor Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(45deg, #1e3c72, #2a5298);
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --hover-transform: translateY(-5px);
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: var(--primary-gradient) !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
        }

        .stats-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: var(--hover-transform);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }

        .stats-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .dashboard-card {
            border: none;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            margin-bottom: 30px;
            overflow: hidden;
        }

        .dashboard-card:hover {
            transform: var(--hover-transform);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }

        .card-header {
            background: white;
            border-bottom: 2px solid #f0f0f0;
            padding: 20px;
        }

        .card-header h4 {
            margin: 0;
            color: #2d3436;
            font-weight: 600;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .quick-action-btn {
            background: white;
            border: none;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: var(--card-shadow);
        }

        .quick-action-btn:hover {
            transform: var(--hover-transform);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }

        .quick-action-btn i {
            font-size: 24px;
            margin-bottom: 10px;
            color: #1e3c72;
        }

        .user-card {
            background: white;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .user-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        @media (max-width: 768px) {
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-shield-alt me-2"></i>Admin Dashboard
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="settings.php">
                            <i class="fas fa-cog me-1"></i>Settings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row" data-aos="fade-up">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stats-number text-primary"><?php echo $counts['total_mentors']; ?></div>
                    <div class="stats-label">Active Mentors</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stats-number text-success"><?php echo $counts['total_mentees']; ?></div>
                    <div class="stats-label">Active Mentees</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-info bg-opacity-10 text-info">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stats-number text-info"><?php echo $counts['total_appointments']; ?></div>
                    <div class="stats-label">Upcoming Sessions</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stats-number text-warning"><?php echo $counts['pending_requests']; ?></div>
                    <div class="stats-label">Pending Requests</div>
                </div>
            </div>
        </div>

        <div class="quick-actions" data-aos="fade-up" data-aos-delay="100">
            <button class="quick-action-btn" onclick="location.href='manage_users.php'">
                <i class="fas fa-users"></i>
                <div>Manage Users</div>
            </button>
            <button class="quick-action-btn" onclick="location.href='manage_mentorships.php'">
                <i class="fas fa-hands-helping"></i>
                <div>Mentorships</div>
            </button>
            <button class="quick-action-btn" onclick="location.href='reports.php'">
                <i class="fas fa-chart-line"></i>
                <div>Reports</div>
            </button>
            <button class="quick-action-btn" onclick="location.href='settings.php'">
                <i class="fas fa-cog"></i>
                <div>Settings</div>
            </button>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="dashboard-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>System Overview</h4>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" onclick="updateChart('weekly')">Weekly</button>
                            <button class="btn btn-sm btn-outline-primary" onclick="updateChart('monthly')">Monthly</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="systemOverview"></canvas>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="card-header">
                        <h4>Recent Appointments</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentAppointments)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No upcoming appointments</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Mentor</th>
                                            <th>Mentee</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentAppointments as $appointment): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="../assets/images/default-avatar.png" 
                                                         class="rounded-circle me-2" width="30" height="30" alt="Mentor">
                                                    <?php echo htmlspecialchars($appointment['mentor_name']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="../assets/images/default-avatar.png" 
                                                         class="rounded-circle me-2" width="30" height="30" alt="Mentee">
                                                    <?php echo htmlspecialchars($appointment['mentee_name']); ?>
                                                </div>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($appointment['date'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($appointment['time'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $appointment['status'] === 'pending' ? 'warning' : 
                                                    ($appointment['status'] === 'approved' ? 'success' : 'danger'); ?>">
                                                    <?php echo ucfirst($appointment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="viewAppointment(<?php echo $appointment['id']; ?>)">
                                                    <i class="fas fa-eye me-1"></i>View
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="dashboard-card" data-aos="fade-left" data-aos-delay="400">
                    <div class="card-header">
                        <h4><i class="fas fa-user-clock me-2"></i>Recent Users</h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentUsers)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No new users</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentUsers as $user): ?>
                            <div class="user-card">
                                <div class="d-flex align-items-center">
                                    <img src="../assets/images/default-avatar.png" 
                                         class="rounded-circle me-3" width="40" height="40" alt="User">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-user-tag me-1"></i>
                                            <?php echo ucfirst($user['role']); ?>
                                        </small>
                                        <small class="text-muted d-block">
                                            <i class="fas fa-clock me-1"></i>
                                            Joined <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                        </small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary ms-auto" 
                                            onclick="viewUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="dashboard-card" data-aos="fade-left" data-aos-delay="500">
                    <div class="card-header">
                        <h4><i class="fas fa-chart-pie me-2"></i>System Statistics</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="statisticsChart"></canvas>
                        <div class="mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>Active Mentorships</div>
                                <div class="badge bg-primary"><?php echo $stats['active_mentorships']; ?></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>Completed Sessions</div>
                                <div class="badge bg-success"><?php echo $stats['completed_sessions']; ?></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>Pending Approvals</div>
                                <div class="badge bg-warning"><?php echo $stats['pending_approvals']; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true
        });

        // System Overview Chart
        const ctx = document.getElementById('systemOverview').getContext('2d');
        const systemChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'New Users',
                    data: [12, 19, 3, 5, 2, 3, 7],
                    borderColor: '#1e3c72',
                    tension: 0.4
                }, {
                    label: 'Sessions',
                    data: [7, 11, 5, 8, 3, 7, 9],
                    borderColor: '#2a5298',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Statistics Chart
        const statsCtx = document.getElementById('statisticsChart').getContext('2d');
        const statsChart = new Chart(statsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Mentors', 'Mentees', 'Active Sessions'],
                datasets: [{
                    data: [<?php echo $counts['total_mentors']; ?>, 
                           <?php echo $counts['total_mentees']; ?>, 
                           <?php echo $counts['total_appointments']; ?>],
                    backgroundColor: ['#1e3c72', '#2a5298', '#4a90e2']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // View user profile
        function viewUser(userId) {
            window.location.href = `view_user.php?id=${userId}`;
        }

        // View appointment details
        function viewAppointment(appointmentId) {
            window.location.href = `view_appointment.php?id=${appointmentId}`;
        }

        // Update system overview chart
        function updateChart(period) {
            // Implement chart update logic based on selected period
            console.log(`Updating chart for ${period} view`);
        }
    </script>
</body>
</html> 