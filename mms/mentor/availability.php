<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in and is a mentor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mentor') {
    header("Location: ../login.php");
    exit();
}

// Get mentor's information
try {
    // First get the mentor ID from the users table
    $stmt = $conn->prepare("SELECT m.id as mentor_id, m.*, u.* FROM users u 
                           JOIN mentors m ON u.id = m.user_id 
                           WHERE u.id = ? AND u.role = 'mentor'");
    $stmt->execute([$_SESSION['user_id']]);
    $mentor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mentor) {
        error_log("No mentor found for user_id: " . $_SESSION['user_id']);
        header("Location: ../login.php");
        exit();
    }

    // Get mentor's availability slots
    $stmt = $conn->prepare("SELECT * FROM availability WHERE mentor_id = ? ORDER BY day_of_week, start_time");
    $stmt->execute([$mentor['mentor_id']]);
    $availability = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $error = "An error occurred. Please try again.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Begin transaction
        $conn->beginTransaction();

        // Delete existing availability
        $stmt = $conn->prepare("DELETE FROM availability WHERE mentor_id = ?");
        $stmt->execute([$mentor['mentor_id']]);

        // Insert new availability slots
        if (isset($_POST['slots']) && is_array($_POST['slots'])) {
            $stmt = $conn->prepare("INSERT INTO availability (mentor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
            
            foreach ($_POST['slots'] as $slot) {
                if (isset($slot['day']) && isset($slot['start_time']) && isset($slot['end_time'])) {
                    $stmt->execute([
                        $mentor['mentor_id'],
                        $slot['day'],
                        $slot['start_time'],
                        $slot['end_time']
                    ]);
                }
            }
        }

        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = "Availability updated successfully!";
        header("Location: availability.php");
        exit();

    } catch(PDOException $e) {
        $conn->rollBack();
        error_log("Error: " . $e->getMessage());
        $error = "Failed to update availability. Please try again.";
    }
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Availability - MMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            --background-color: #f3f4f6;
            --card-shadow: 0 10px 25px rgba(99, 102, 241, 0.1);
            --hover-shadow: 0 15px 30px rgba(99, 102, 241, 0.2);
        }

        body {
            background: var(--background-color);
            font-family: 'Inter', sans-serif;
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            background: white;
            color: #1f2937;
            border: none;
            border-radius: 15px;
            margin-bottom: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: var(--card-shadow);
        }

        .btn-back:hover {
            transform: translateX(-5px);
            background: #1f2937;
            color: white;
        }

        .availability-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--card-shadow);
        }

        .day-card {
            background: #f9fafb;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .time-slot {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .btn-add-slot {
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 25px;
            transition: all 0.3s ease;
        }

        .btn-add-slot:hover {
            transform: translateY(-2px);
            box-shadow: var(--hover-shadow);
        }

        .btn-remove-slot {
            color: #ef4444;
            background: none;
            border: none;
            padding: 0;
            margin-left: 10px;
        }

        .btn-remove-slot:hover {
            color: #dc2626;
        }

        .time-input {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 8px 12px;
            width: 150px;
        }

        .time-input:focus {
            border-color: #6366f1;
            outline: none;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.3s ease forwards;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>

        <div class="availability-card">
            <h2 class="mb-4">Manage Your Availability</h2>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?php echo $_SESSION['success']; ?></div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <form id="availabilityForm" method="POST">
                <?php foreach ($days as $index => $day): ?>
                    <div class="day-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><?php echo $day; ?></h5>
                            <button type="button" class="btn btn-add-slot" onclick="addTimeSlot('<?php echo $day; ?>')">
                                <i class="fas fa-plus"></i> Add Time Slot
                            </button>
                        </div>
                        <div id="slots-<?php echo $day; ?>">
                            <?php
                            $daySlots = array_filter($availability ?? [], function($slot) use ($index) {
                                return $slot['day_of_week'] == $index;
                            });
                            foreach ($daySlots as $slot):
                            ?>
                            <div class="time-slot d-flex align-items-center">
                                <input type="hidden" name="slots[][day]" value="<?php echo $index; ?>">
                                <div class="me-3">
                                    <label class="form-label">Start Time</label>
                                    <input type="time" name="slots[][start_time]" class="time-input" value="<?php echo $slot['start_time']; ?>" required>
                                </div>
                                <div class="me-3">
                                    <label class="form-label">End Time</label>
                                    <input type="time" name="slots[][end_time]" class="time-input" value="<?php echo $slot['end_time']; ?>" required>
                                </div>
                                <button type="button" class="btn-remove-slot" onclick="removeTimeSlot(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-add-slot">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function addTimeSlot(day) {
            const container = document.getElementById(`slots-${day}`);
            const slot = document.createElement('div');
            slot.className = 'time-slot d-flex align-items-center animate-fade-in';
            
            const dayIndex = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'].indexOf(day);
            const currentTime = new Date();
            const hours = String(currentTime.getHours()).padStart(2, '0');
            const minutes = String(currentTime.getMinutes()).padStart(2, '0');
            const defaultStartTime = `${hours}:${minutes}`;
            const defaultEndTime = `${String(Math.min(23, currentTime.getHours() + 1)).padStart(2, '0')}:${minutes}`;
            
            slot.innerHTML = `
                <input type="hidden" name="slots[][day]" value="${dayIndex}">
                <div class="me-3">
                    <label class="form-label">Start Time</label>
                    <input type="time" name="slots[][start_time]" class="time-input" value="${defaultStartTime}" required 
                           onchange="validateTimeSlot(this)">
                </div>
                <div class="me-3">
                    <label class="form-label">End Time</label>
                    <input type="time" name="slots[][end_time]" class="time-input" value="${defaultEndTime}" required
                           onchange="validateTimeSlot(this)">
                </div>
                <button type="button" class="btn-remove-slot" onclick="removeTimeSlot(this)">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            
            container.appendChild(slot);
        }

        function validateTimeSlot(input) {
            const timeSlot = input.closest('.time-slot');
            const startTime = timeSlot.querySelector('input[name="slots[][start_time]"]').value;
            const endTime = timeSlot.querySelector('input[name="slots[][end_time]"]').value;
            
            if (startTime && endTime && startTime >= endTime) {
                alert('End time must be after start time');
                input.value = '';
            }
        }

        function removeTimeSlot(button) {
            const slot = button.closest('.time-slot');
            slot.style.opacity = '0';
            slot.style.transform = 'translateY(10px)';
            setTimeout(() => slot.remove(), 300);
        }

        // Add form validation before submit
        document.getElementById('availabilityForm').onsubmit = function(e) {
            const slots = document.querySelectorAll('.time-slot');
            let isValid = true;

            slots.forEach(slot => {
                const startTime = slot.querySelector('input[name="slots[][start_time]"]').value;
                const endTime = slot.querySelector('input[name="slots[][end_time]"]').value;
                
                if (!startTime || !endTime || startTime >= endTime) {
                    isValid = false;
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Please check your time slots. End time must be after start time for all slots.');
            }
        };
    </script>
</body>
</html> 