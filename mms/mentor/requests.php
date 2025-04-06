<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a mentor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header('Location: ../login.php');
    exit();
}

// Get all pending requests for this mentor
$stmt = $pdo->prepare("SELECT br.*, u.name as mentee_name, u.email as mentee_email, 
                       ts.date, ts.start_time, ts.end_time
                       FROM booking_requests br
                       JOIN users u ON br.mentee_id = u.id
                       JOIN time_slots ts ON br.slot_id = ts.id
                       WHERE br.mentor_id = ? AND br.status = 'pending'
                       ORDER BY ts.date, ts.start_time");
$stmt->execute([$_SESSION['user_id']]);
$pending_requests = $stmt->fetchAll();

// Get all accepted requests
$stmt = $pdo->prepare("SELECT br.*, u.name as mentee_name, u.email as mentee_email,
                       ts.date, ts.start_time, ts.end_time
                       FROM booking_requests br
                       JOIN users u ON br.mentee_id = u.id
                       JOIN time_slots ts ON br.slot_id = ts.id
                       WHERE br.mentor_id = ? AND br.status = 'accepted'
                       ORDER BY ts.date, ts.start_time");
$stmt->execute([$_SESSION['user_id']]);
$accepted_requests = $stmt->fetchAll();

// Get all rejected requests
$stmt = $pdo->prepare("SELECT br.*, u.name as mentee_name, u.email as mentee_email,
                       ts.date, ts.start_time, ts.end_time
                       FROM booking_requests br
                       JOIN users u ON br.mentee_id = u.id
                       JOIN time_slots ts ON br.slot_id = ts.id
                       WHERE br.mentor_id = ? AND br.status = 'rejected'
                       ORDER BY ts.date DESC, ts.start_time DESC");
$stmt->execute([$_SESSION['user_id']]);
$rejected_requests = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requests - Mentor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .navbar {
            background: linear-gradient(45deg, #4158D0, #C850C0) !important;
        }

        .request-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .request-card:hover {
            transform: translateY(-5px);
        }

        .mentee-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-accepted {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .btn-primary {
            background: linear-gradient(45deg, #4158D0, #C850C0);
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, #C850C0, #4158D0);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(65, 88, 208, 0.3);
        }

        .nav-tabs .nav-link {
            color: #4158D0;
            border: none;
            padding: 10px 20px;
            margin-right: 5px;
            border-radius: 8px;
        }

        .nav-tabs .nav-link.active {
            background: linear-gradient(45deg, #4158D0, #C850C0);
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
            <span class="navbar-text text-white">
                Manage Mentorship Requests
            </span>
        </div>
    </nav>

    <div class="container mt-4">
        <ul class="nav nav-tabs mb-4" id="requestTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" 
                        data-bs-target="#pending" type="button" role="tab">
                    Pending Requests
                    <span class="badge bg-warning ms-2"><?php echo count($pending_requests); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="accepted-tab" data-bs-toggle="tab" 
                        data-bs-target="#accepted" type="button" role="tab">
                    Accepted Requests
                    <span class="badge bg-success ms-2"><?php echo count($accepted_requests); ?></span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="rejected-tab" data-bs-toggle="tab" 
                        data-bs-target="#rejected" type="button" role="tab">
                    Rejected Requests
                    <span class="badge bg-danger ms-2"><?php echo count($rejected_requests); ?></span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="requestTabsContent">
            <!-- Pending Requests -->
            <div class="tab-pane fade show active" id="pending" role="tabpanel">
                <?php if (empty($pending_requests)): ?>
                    <div class="alert alert-info">
                        No pending requests at the moment.
                    </div>
                <?php else: ?>
                    <?php foreach ($pending_requests as $request): ?>
                        <div class="request-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center">
                                    <img src="../images/default-avatar.png" class="mentee-image me-3" alt="Mentee">
                                    <div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($request['mentee_name']); ?></h5>
                                        <p class="mb-1 text-muted">
                                            <i class="far fa-envelope me-2"></i><?php echo htmlspecialchars($request['mentee_email']); ?>
                                        </p>
                                        <p class="mb-0">
                                            <i class="far fa-calendar me-2"></i><?php echo date('F j, Y', strtotime($request['date'])); ?>
                                            <i class="far fa-clock ms-3 me-2"></i>
                                            <?php echo date('g:i A', strtotime($request['start_time'])); ?> - 
                                            <?php echo date('g:i A', strtotime($request['end_time'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <span class="status-badge status-pending">Pending</span>
                                </div>
                            </div>
                            <div class="mt-3 d-flex justify-content-end">
                                <button class="btn btn-danger me-2" onclick="rejectRequest(<?php echo $request['id']; ?>)">
                                    <i class="fas fa-times me-2"></i>Reject
                                </button>
                                <button class="btn btn-primary" onclick="acceptRequest(<?php echo $request['id']; ?>)">
                                    <i class="fas fa-check me-2"></i>Accept
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Accepted Requests -->
            <div class="tab-pane fade" id="accepted" role="tabpanel">
                <?php if (empty($accepted_requests)): ?>
                    <div class="alert alert-info">
                        No accepted requests at the moment.
                    </div>
                <?php else: ?>
                    <?php foreach ($accepted_requests as $request): ?>
                        <div class="request-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center">
                                    <img src="../images/default-avatar.png" class="mentee-image me-3" alt="Mentee">
                                    <div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($request['mentee_name']); ?></h5>
                                        <p class="mb-1 text-muted">
                                            <i class="far fa-envelope me-2"></i><?php echo htmlspecialchars($request['mentee_email']); ?>
                                        </p>
                                        <p class="mb-0">
                                            <i class="far fa-calendar me-2"></i><?php echo date('F j, Y', strtotime($request['date'])); ?>
                                            <i class="far fa-clock ms-3 me-2"></i>
                                            <?php echo date('g:i A', strtotime($request['start_time'])); ?> - 
                                            <?php echo date('g:i A', strtotime($request['end_time'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <span class="status-badge status-accepted">Accepted</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Rejected Requests -->
            <div class="tab-pane fade" id="rejected" role="tabpanel">
                <?php if (empty($rejected_requests)): ?>
                    <div class="alert alert-info">
                        No rejected requests at the moment.
                    </div>
                <?php else: ?>
                    <?php foreach ($rejected_requests as $request): ?>
                        <div class="request-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center">
                                    <img src="../images/default-avatar.png" class="mentee-image me-3" alt="Mentee">
                                    <div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($request['mentee_name']); ?></h5>
                                        <p class="mb-1 text-muted">
                                            <i class="far fa-envelope me-2"></i><?php echo htmlspecialchars($request['mentee_email']); ?>
                                        </p>
                                        <p class="mb-0">
                                            <i class="far fa-calendar me-2"></i><?php echo date('F j, Y', strtotime($request['date'])); ?>
                                            <i class="far fa-clock ms-3 me-2"></i>
                                            <?php echo date('g:i A', strtotime($request['start_time'])); ?> - 
                                            <?php echo date('g:i A', strtotime($request['end_time'])); ?>
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <span class="status-badge status-rejected">Rejected</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function acceptRequest(requestId) {
            if (confirm('Are you sure you want to accept this request?')) {
                updateRequestStatus(requestId, 'accepted');
            }
        }

        function rejectRequest(requestId) {
            if (confirm('Are you sure you want to reject this request?')) {
                updateRequestStatus(requestId, 'rejected');
            }
        }

        function updateRequestStatus(requestId, status) {
            fetch('update_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    request_id: requestId,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(status === 'accepted' ? 
                        'Request accepted successfully. The mentee has been notified.' : 
                        'Request rejected successfully.');
                    location.reload();
                } else {
                    alert(data.message || 'An error occurred. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
    </script>
</body>
</html> 