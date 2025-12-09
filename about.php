<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Maintenance Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px 0;
        }

        .navbar-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 15px 0;
            margin-bottom: 40px;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: #667eea !important;
            display: flex;
            align-items: center;
        }

        .navbar-brand i {
            margin-right: 10px;
            font-size: 1.8rem;
        }

        .nav-link {
            color: #2d3748 !important;
            font-weight: 500;
            margin: 0 10px;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #667eea !important;
        }

        .nav-link.active {
            color: #667eea !important;
            font-weight: 600;
        }

        .hero-section {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            margin-bottom: 40px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
            text-align: center;
        }

        .hero-section h1 {
            color: #2d3748;
            font-weight: 700;
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .hero-section .subtitle {
            color: #718096;
            font-size: 1.3rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .hero-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 30px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .hero-icon i {
            font-size: 60px;
            color: white;
        }

        .section-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            color: #2d3748;
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
        }

        .section-title i {
            margin-right: 15px;
            color: #667eea;
            font-size: 2.2rem;
        }

        .section-content {
            color: #4a5568;
            font-size: 1.1rem;
            line-height: 1.8;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .feature-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border-color: #667eea;
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .feature-icon i {
            font-size: 40px;
            color: white;
        }

        .feature-title {
            color: #2d3748;
            font-weight: 600;
            font-size: 1.3rem;
            margin-bottom: 15px;
        }

        .feature-description {
            color: #718096;
            font-size: 1rem;
            line-height: 1.6;
        }

        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            color: white;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .mission-vision {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .mv-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 35px;
            color: white;
        }

        .mv-icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .mv-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .mv-text {
            font-size: 1.1rem;
            line-height: 1.7;
            opacity: 0.95;
        }

        .team-section {
            text-align: center;
            margin-top: 30px;
        }

        .team-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .team-icon i {
            font-size: 50px;
            color: white;
        }

        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 50px 40px;
            text-align: center;
            color: white;
        }

        .cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .cta-text {
            font-size: 1.2rem;
            margin-bottom: 30px;
            opacity: 0.95;
        }

        .btn-cta {
            background: white;
            color: #667eea;
            padding: 15px 40px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            border: none;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 255, 255, 0.3);
            color: #667eea;
        }

        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2rem;
            }

            .hero-section .subtitle {
                font-size: 1.1rem;
            }

            .section-title {
                font-size: 1.5rem;
            }

            .feature-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-tools"></i>
            Maintenance System
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-home me-1"></i>Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="about.php">
                        <i class="fas fa-info-circle me-1"></i>About
                    </a>
                </li>
                <?php if (isset($_SESSION['userid'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'dashboard.php' ?>">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>
                <?php else: ?>
                    
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-icon">
            <i class="fas fa-tools"></i>
        </div>
        <h1>About Our System</h1>
        <p class="subtitle">
            A comprehensive maintenance management solution designed to streamline facility operations, 
            improve response times, and ensure optimal building performance.
        </p>
    </div>

    <!-- What We Do -->
    <div class="section-card">
        <h2 class="section-title">
            <i class="fas fa-question-circle"></i>
            What We Do
        </h2>
        <div class="section-content">
            <p>
                Our Maintenance Management System is a powerful platform that connects facility users with maintenance teams 
                to ensure seamless handling of repair and maintenance requests. We provide a centralized hub where requests 
                are tracked, managed, and resolved efficiently.
            </p>
            <p class="mb-0">
                From electrical issues to plumbing emergencies, HVAC maintenance to sound system repairs, our system handles 
                it all with real-time tracking, automated notifications, and comprehensive reporting capabilities.
            </p>
        </div>
    </div>

    <!-- Key Features -->
    <div class="section-card">
        <h2 class="section-title">
            <i class="fas fa-star"></i>
            Key Features
        </h2>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <h3 class="feature-title">Quick Request Submission</h3>
                <p class="feature-description">
                    Submit maintenance requests in seconds with our intuitive interface. Attach photos and documents for better clarity.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3 class="feature-title">Real-Time Tracking</h3>
                <p class="feature-description">
                    Monitor the status of your requests from submission to completion with live updates and notifications.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="feature-title">Worker Assignment</h3>
                <p class="feature-description">
                    Efficiently assign tasks to qualified maintenance workers based on expertise and availability.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="feature-title">Emergency Priority</h3>
                <p class="feature-description">
                    Flag urgent issues as emergencies to ensure immediate attention and rapid response times.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-calculator"></i>
                </div>
                <h3 class="feature-title">Cost Tracking</h3>
                <p class="feature-description">
                    Monitor labor costs and total expenses for each maintenance job with detailed financial reporting.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3 class="feature-title">Comprehensive Reports</h3>
                <p class="feature-description">
                    Generate detailed accomplishment matrices and audit logs for accountability and analysis.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h3 class="feature-title">Communication Hub</h3>
                <p class="feature-description">
                    Keep all stakeholders informed with comments, updates, and notifications throughout the process.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-history"></i>
                </div>
                <h3 class="feature-title">Complete History</h3>
                <p class="feature-description">
                    Access full audit trails and historical data for every maintenance request and action taken.
                </p>
            </div>
        </div>
    </div>

    <!-- Mission & Vision -->
    <div class="section-card">
        <h2 class="section-title">
            <i class="fas fa-compass"></i>
            Mission & Vision
        </h2>
        <div class="mission-vision">
            <div class="mv-card">
                <div class="mv-icon">
                    <i class="fas fa-bullseye"></i>
                </div>
                <h3 class="mv-title">Our Mission</h3>
                <p class="mv-text">
                    To revolutionize facility maintenance management by providing an intuitive, efficient, and reliable 
                    platform that empowers organizations to maintain their infrastructure proactively, reduce downtime, 
                    and enhance operational excellence.
                </p>
            </div>

            <div class="mv-card">
                <div class="mv-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <h3 class="mv-title">Our Vision</h3>
                <p class="mv-text">
                    To become the leading maintenance management solution that sets the standard for transparency, 
                    efficiency, and user satisfaction in facility operations worldwide, creating safer and more 
                    productive environments for all.
                </p>
            </div>
        </div>
    </div>

    <!-- System Benefits -->
    <div class="section-card">
        <h2 class="section-title">
            <i class="fas fa-award"></i>
            System Benefits
        </h2>
        <div class="stats-section">
            <div class="stat-box">
                <div class="stat-number">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-label">Faster Response Times</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">
                    <i class="fas fa-check-double"></i>
                </div>
                <div class="stat-label">Improved Accountability</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-label">Cost Transparency</div>
            </div>
            <div class="stat-box">
                <div class="stat-number">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="stat-label">Enhanced Security</div>
            </div>
        </div>
    </div>

    <!-- Why Choose Us -->
    <div class="section-card">
        <h2 class="section-title">
            <i class="fas fa-thumbs-up"></i>
            Why Choose Our System?
        </h2>
        <div class="section-content">
            <ul style="font-size: 1.1rem; line-height: 2;">
                <li><strong>User-Friendly Interface:</strong> Designed with simplicity in mind, anyone can use our system without extensive training.</li>
                <li><strong>Comprehensive Tracking:</strong> From request submission to completion, every step is documented and traceable.</li>
                <li><strong>Secure & Reliable:</strong> Your data is protected with industry-standard security measures.</li>
                <li><strong>Real-Time Updates:</strong> Stay informed with instant notifications and status updates.</li>
                
            </ul>
        </div>
    </div>

    <!-- Team Section -->
    <div class="section-card">
        <h2 class="section-title">
            <i class="fas fa-users"></i>
            Our Commitment
        </h2>
        <div class="team-section">
            <div class="team-icon">
                <i class="fas fa-heart"></i>
            </div>
            <div class="section-content">
                <p style="font-size: 1.2rem;">
                    We are committed to continuous improvement and innovation. Our team works tirelessly to enhance 
                    the system based on user feedback and emerging industry needs. We believe in creating technology 
                    that makes a real difference in people's daily work lives.
                </p>
                <p style="font-size: 1.2rem;" class="mb-0">
                    Your success is our success. We're here to support you every step of the way with reliable 
                    technology, responsive support, and a genuine dedication to your facility's operational excellence.
                </p>
            </div>
        </div>
    </div>

    <!-- Call to Action -->
    <div class="section-card mb-5">
        <div class="cta-section">
            <h2 class="cta-title">Ready to Get Started?</h2>
            <p class="cta-text">
                Join organizations that have already transformed their maintenance operations. 
                Experience the difference of efficient, transparent, and reliable facility management.
            </p>
            <?php if (isset($_SESSION['userid'])): ?>
                <a href="<?= $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'dashboard.php' ?>" class="btn-cta">
                    <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                </a>
            <?php else: ?>
                <a href="index.php" class="btn-cta me-2">
                    <i class="fas fa-user-plus me-2"></i>Create or Login Account
                </a>
                
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>