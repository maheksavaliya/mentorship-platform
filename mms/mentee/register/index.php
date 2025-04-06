<?php
session_start();
require_once '../../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO mentees (name, email, password) VALUES (?, ?, ?)";
        
        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute([$name, $email, $hashed_password]);
            
            $_SESSION['success_msg'] = "Registration successful! Please login.";
            header("Location: ../login");
            exit();
        } catch(PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate email error
                $error = "This email is already registered. Please use a different email.";
            } else {
                $error = "Registration failed. Please try again.";
                error_log("Registration error: " . $e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentee Registration - MMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #6f42c1 0%, #007bff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 600px;
            width: 90%;
        }
        .card-body {
            padding: 3rem;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 20px;
            border: 2px solid #eee;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #6f42c1;
            box-shadow: 0 0 0 0.2rem rgba(111,66,193,0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #6f42c1 0%, #007bff 100%);
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 500;
            width: 100%;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #00b4db 0%, #0083b0 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(0,180,219,0.4);
            }
            70% {
                box-shadow: 0 0 0 20px rgba(0,180,219,0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(0,180,219,0);
            }
        }
        .icon-circle i {
            font-size: 2.5rem;
            color: white;
        }
        .form-floating label {
            padding-left: 20px;
        }
        .form-floating > .form-control {
            padding-left: 20px;
        }
        .password-toggle {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }
        .alert {
            border-radius: 15px;
            padding: 15px 20px;
        }
        .password-strength {
            height: 5px;
            margin-top: 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .password-strength.weak { background: #ff4444; width: 30%; }
        .password-strength.medium { background: #ffbb33; width: 60%; }
        .password-strength.strong { background: #00C851; width: 100%; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card mx-auto">
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="icon-circle">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h2 class="mb-4">Create Your Account</h2>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="needs-validation" novalidate>
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="name" name="name" placeholder="Your Name" required>
                        <label for="name"><i class="fas fa-user me-2"></i>Full Name</label>
                        <div class="invalid-feedback">Please enter your name.</div>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
                        <label for="email"><i class="fas fa-envelope me-2"></i>Email Address</label>
                        <div class="invalid-feedback">Please enter a valid email address.</div>
                    </div>

                    <div class="form-floating mb-3 position-relative">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required minlength="8">
                        <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                        <span class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </span>
                        <div class="password-strength"></div>
                        <div class="invalid-feedback">Password must be at least 8 characters long.</div>
                    </div>

                    <div class="form-floating mb-4 position-relative">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                        <label for="confirm_password"><i class="fas fa-lock me-2"></i>Confirm Password</label>
                        <span class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </span>
                        <div class="invalid-feedback">Passwords do not match.</div>
                    </div>

                    <button type="submit" class="btn btn-primary mb-4">
                        <i class="fas fa-user-plus me-2"></i>Create Account
                    </button>

                    <div class="text-center">
                        <p class="mb-0">Already have an account? <a href="../login" class="text-primary">Login here</a></p>
                        <a href="../../" class="text-muted"><i class="fas fa-home me-1"></i>Back to Home</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password visibility toggle
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = passwordInput.nextElementSibling.nextElementSibling.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = this.nextElementSibling.nextElementSibling.nextElementSibling;
            
            // Reset classes
            strengthBar.className = 'password-strength';
            
            if (password.length === 0) {
                strengthBar.style.width = '0';
                return;
            }
            
            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
            
            switch(strength) {
                case 0:
                case 1:
                    strengthBar.classList.add('weak');
                    break;
                case 2:
                case 3:
                    strengthBar.classList.add('medium');
                    break;
                case 4:
                    strengthBar.classList.add('strong');
                    break;
            }
        });

        // Confirm password validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            if (this.value !== document.getElementById('password').value) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html> 