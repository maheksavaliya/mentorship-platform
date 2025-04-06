<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Connect - Professional Guidance Platform</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .navbar {
            background: linear-gradient(135deg, #00b4db 0%, #0083b0 100%);
            padding: 1rem 0;
            transition: all 0.3s ease;
        }
        .navbar.scrolled {
            background: rgba(0, 180, 219, 0.95);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .nav-link {
            font-weight: 500;
            padding: 0.5rem 1.2rem !important;
            border-radius: 50px;
            transition: all 0.3s ease;
            margin: 0 0.3rem;
        }
        .nav-link.login-btn {
            background: #ffffff;
            color: #00b4db !important;
        }
        .nav-link.register-btn {
            background: #ff6b6b;
            color: white !important;
        }
        .nav-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .nav-link.login-btn:hover {
            background: #f8f9fa;
        }
        .nav-link.register-btn:hover {
            background: #ff5252;
        }
        .hero-section {
            background: linear-gradient(135deg, #00b4db 0%, #0083b0 100%);
            padding: 160px 0 100px;
            color: white;
        }
        .btn-light {
            background: white;
            color: #00b4db;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-outline-light {
            border: 2px solid white;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 600;
            color: white !important;
        }
        .navbar-brand i {
            font-size: 1.8rem;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        .hero-section img {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body>
    <!-- Loading Animation -->
    <div class="loading"></div>

    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap me-2"></i>
                Mentor Connect
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link login-btn" href="mentee/login">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link register-btn" href="mentee/register">
                            <i class="fas fa-user-plus me-1"></i> Register
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Connect with Expert Mentors</h1>
                    <p class="lead mb-4">Get professional guidance and accelerate your career growth with experienced mentors in your field.</p>
                    <div class="d-flex gap-3">
                        <a href="mentee/register" class="btn btn-primary btn-lg">
                            <i class="fas fa-rocket me-2"></i>Get Started
                        </a>
                        <a href="#learn-more" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-info-circle me-2"></i>Learn More
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="https://img.freepik.com/free-vector/mentoring-illustration-concept_114360-712.jpg" 
                         alt="Mentorship" 
                         class="img-fluid" 
                         style="max-width: 100%; height: auto;">
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5 fw-bold">Why Choose Mentor Connect?</h2>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x mb-3"></i>
                            <h3 class="h4 mb-3">Expert Mentors</h3>
                            <p class="text-muted">Connect with experienced professionals who have achieved success in their fields.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-line fa-3x mb-3"></i>
                            <h3 class="h4 mb-3">Career Growth</h3>
                            <p class="text-muted">Get personalized guidance to accelerate your professional development.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-comments fa-3x mb-3"></i>
                            <h3 class="h4 mb-3">Interactive Sessions</h3>
                            <p class="text-muted">Engage in meaningful discussions and receive valuable feedback.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <img src="https://img.freepik.com/free-vector/business-team-discussing-ideas-startup_74855-4380.jpg" alt="Mentorship Process" class="img-fluid rounded-3 shadow">
                </div>
                <div class="col-md-6">
                    <h2 class="fw-bold mb-4">How It Works</h2>
                    <div class="mt-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="rounded-circle bg-primary text-white p-3 me-3">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div>
                                <h4 class="h5 mb-2">1. Create Your Profile</h4>
                                <p class="text-muted mb-0">Sign up and create your professional profile to showcase your skills and goals.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-4">
                            <div class="rounded-circle bg-primary text-white p-3 me-3">
                                <i class="fas fa-search"></i>
                            </div>
                            <div>
                                <h4 class="h5 mb-2">2. Find Your Mentor</h4>
                                <p class="text-muted mb-0">Browse through our network of mentors and find the perfect match for your career goals.</p>
                            </div>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-primary text-white p-3 me-3">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <div>
                                <h4 class="h5 mb-2">3. Start Learning</h4>
                                <p class="text-muted mb-0">Connect with your mentor and begin your journey towards professional growth.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5 class="mb-3">About Mentor Connect</h5>
                    <p class="mb-0">Connecting professionals with expert mentors to accelerate career growth and development.</p>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-3">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">About Us</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">How It Works</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="mb-3">Connect With Us</h5>
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
            <hr class="mt-4 bg-light">
            <div class="text-center">
                <p class="mb-0">&copy; 2024 Mentor Connect. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Toast Notification -->
    <div class="toast" role="alert">
        <div class="toast-body">
            Welcome to Mentor Connect! 🎓
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Remove loading animation when page is loaded
        window.addEventListener('load', function() {
            document.querySelector('.loading').style.display = 'none';
        });

        // Show toast notification
        setTimeout(function() {
            document.querySelector('.toast').classList.add('show');
            setTimeout(function() {
                document.querySelector('.toast').classList.remove('show');
            }, 3000);
        }, 1000);

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                document.querySelector('.navbar').classList.add('scrolled');
            } else {
                document.querySelector('.navbar').classList.remove('scrolled');
            }
        });
    </script>
</body>
</html> 