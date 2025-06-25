<!-- filepath: c:\Users\mandy\jobportal-php-mysql\main\index.php -->
<?php
// No PHP logic needed for static homepage
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobPortal - Find Your Dream Job</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #181828 0%, #23233a 100%);
            color: #f3f3fa;
            font-family: 'Inter', Arial, sans-serif;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            padding-top: 68px;
        }
        .hero {
            position: relative;
            min-height: 92vh;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            text-align: center;
            z-index: 1;
            padding-top: 5rem;
            padding-bottom: 5rem;
            overflow: hidden;
        }
        .hero-bg {
            position: absolute;
            top: 0; left: 0; width: 100vw; height: 100%;
            z-index: 0;
            pointer-events: none;
            background: radial-gradient(ellipse at 60% 20%, #7b1fa2 0%, transparent 60%),
                        radial-gradient(ellipse at 20% 80%, #1976d2 0%, transparent 70%);
            animation: bgMove 12s ease-in-out infinite alternate;
            filter: blur(2px) brightness(0.8);
            opacity: 0.7;
        }
        @keyframes bgMove {
            0% { background-position: 60% 20%, 20% 80%; }
            100% { background-position: 65% 25%, 15% 75%; }
        }
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            letter-spacing: -2px;
            color: #fff;
            margin-bottom: 1.2rem;
            text-shadow: 0 4px 32px rgba(123,63,228,0.10);
            z-index: 2;
            line-height: 1.08;
            animation: fadeInUp 1.1s cubic-bezier(.4,1.4,.6,1);
        }
        .hero-subtitle {
            font-size: 1.5rem;
            color: #b3b3c6;
            margin-bottom: 2.8rem;
            z-index: 2;
            font-weight: 400;
            animation: fadeInUp 1.3s cubic-bezier(.4,1.4,.6,1);
        }
        .cta-btn-sticky {
            position: fixed;
            bottom: 2.2rem;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255,255,255,0.18);
            backdrop-filter: blur(16px) saturate(1.2);
            color: #fff;
            font-weight: 700;
            font-size: 1.2rem;
            border: none;
            border-radius: 30px;
            padding: 1.1rem 3.2rem;
            box-shadow: 0 8px 32px rgba(123,63,228,0.18);
            transition: background 0.2s, box-shadow 0.2s;
            z-index: 100;
            outline: none;
            animation: fadeInUp 1.5s cubic-bezier(.4,1.4,.6,1);
        }
        .cta-btn-sticky:hover {
            background: linear-gradient(135deg, #00e0d6 0%, #7b3fe4 100%);
            color: #fff;
            box-shadow: 0 12px 48px rgba(0,224,214,0.18);
        }
        .glass-panel {
            background: rgba(255,255,255,0.10);
            border-radius: 32px;
            box-shadow: 0 8px 32px rgba(30,20,60,0.13);
            backdrop-filter: blur(18px) saturate(1.2);
            border: 1.5px solid rgba(255,255,255,0.13);
            margin: -3rem auto 2rem auto;
            max-width: 1100px;
            padding: 3.5rem 2rem 2.5rem 2rem;
            z-index: 2;
            position: relative;
            animation: fadeInUp 1.2s cubic-bezier(.4,1.4,.6,1);
        }
        .features-title {
            font-size: 2.1rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 2.2rem;
            text-align: center;
            letter-spacing: -1px;
        }
        .features-list {
            display: flex;
            flex-wrap: wrap;
            gap: 2.5rem;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        .feature-item {
            background: rgba(255,255,255,0.13);
            border-radius: 24px;
            box-shadow: 0 2px 12px rgba(123,63,228,0.08);
            padding: 2.2rem 1.7rem 1.7rem 1.7rem;
            min-width: 260px;
            max-width: 320px;
            flex: 1 1 260px;
            text-align: center;
            color: #f3f3fa;
            transition: transform 0.18s, box-shadow 0.18s;
            border: 1.5px solid rgba(255,255,255,0.10);
            backdrop-filter: blur(8px) saturate(1.1);
            animation: fadeInUp 1.3s cubic-bezier(.4,1.4,.6,1);
        }
        .feature-item:hover {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 8px 32px rgba(123,63,228,0.13);
        }
        .feature-icon {
            font-size: 2.7rem;
            margin-bottom: 1.1rem;
            color: #00e0d6;
            filter: drop-shadow(0 2px 8px #7b3fe4aa);
        }
        .feature-title {
            font-size: 1.18rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #fff;
            letter-spacing: -0.5px;
        }
        .feature-desc {
            color: #b3b3c6;
            font-size: 1.05rem;
            font-weight: 400;
        }
        .testimonials-section {
            max-width: 1100px;
            margin: 2rem auto 0 auto;
            padding: 2.5rem 1rem 2rem 1rem;
            text-align: center;
            animation: fadeInUp 1.4s cubic-bezier(.4,1.4,.6,1);
        }
        .testimonials-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 2rem;
            letter-spacing: -0.5px;
        }
        .testimonial-carousel {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            gap: 2rem;
            justify-content: flex-start;
            scroll-snap-type: x mandatory;
            padding-bottom: 1rem;
        }
        .testimonial-item {
            background: rgba(255,255,255,0.13);
            border-radius: 18px;
            box-shadow: 0 2px 12px rgba(123,63,228,0.08);
            padding: 2rem 1.5rem 1.5rem 1.5rem;
            min-width: 320px;
            max-width: 340px;
            flex: 0 0 320px;
            color: #f3f3fa;
            font-size: 1.05rem;
            position: relative;
            scroll-snap-align: start;
            border: 1.5px solid rgba(255,255,255,0.10);
            backdrop-filter: blur(8px) saturate(1.1);
            margin-bottom: 1rem;
        }
        .testimonial-quote {
            font-size: 1.5rem;
            color: #00e0d6;
            margin-bottom: 1rem;
        }
        .testimonial-user {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            margin-top: 1.2rem;
            font-size: 1rem;
            color: #b3b3c6;
            justify-content: center;
        }
        .testimonial-user img {
            width: 38px; height: 38px; border-radius: 50%; object-fit: cover;
            border: 2px solid #7b3fe4;
        }
        @media (max-width: 900px) {
            .features-list, .testimonial-carousel { flex-direction: column; gap: 1.5rem; }
            .features-section, .testimonials-section { padding: 2rem 0.5rem; }
            .testimonial-item { min-width: 90vw; max-width: 98vw; }
        }
        @media (max-width: 600px) {
            .hero-title { font-size: 2.1rem; }
            .features-title { font-size: 1.3rem; }
            .features-section { border-radius: 18px; }
            .feature-item, .testimonial-item { min-width: 90vw; max-width: 98vw; }
            .cta-btn-sticky { font-size: 1rem; padding: 0.8rem 1.5rem; }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .main-header-glass {
            position: fixed;
            top: 0; left: 0; width: 100vw;
            height: 68px;
            z-index: 2000;
            background: rgba(30, 30, 50, 0.38);
            backdrop-filter: blur(18px) saturate(1.2);
            box-shadow: 0 2px 16px rgba(30,20,60,0.10);
            border-bottom: 1.5px solid rgba(255,255,255,0.10);
            display: flex;
            align-items: center;
            transition: background 0.18s;
            animation: fadeInUp 0.8s cubic-bezier(.4,1.4,.6,1);
        }
        .nav-link-glass {
            color: #f3f3fa;
            font-weight: 500;
            font-size: 1.08rem;
            text-decoration: none;
            padding: 0.3rem 1.1rem;
            border-radius: 18px;
            transition: background 0.18s, color 0.18s;
            opacity: 0.92;
        }
        .nav-link-glass:hover, .nav-link-glass:focus {
            background: rgba(0,224,214,0.10);
            color: #00e0d6;
            text-decoration: none;
        }
        .nav-link-cta {
            background: linear-gradient(135deg, #00e0d6 0%, #7b3fe4 100%);
            color: #fff !important;
            font-weight: 700;
            border-radius: 22px;
            padding: 0.3rem 1.5rem;
            margin-left: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,224,214,0.10);
            transition: background 0.18s, color 0.18s;
        }
        .nav-link-cta:hover, .nav-link-cta:focus {
            background: linear-gradient(135deg, #7b3fe4 0%, #00e0d6 100%);
            color: #fff;
        }
    </style>
</head>

<body>
    <header class="main-header-glass">
        <div class="container-fluid d-flex align-items-center justify-content-between px-4" style="height:68px;">
            <div class="brand" style="font-size:1.7rem; font-weight:800; letter-spacing:-1.5px; color:#fff;">
                <i class="fas fa-rocket me-2" style="color:#00e0d6;"></i>Job<span style="color:#00e0d6;">Portal</span>
            </div>
            <nav class="d-flex align-items-center gap-3">
                <a href="/main/job-list.php" class="nav-link-glass">Jobs</a>
                <a href="#" class="nav-link-glass">About</a>
                <a href="#" class="nav-link-glass">Contact</a>
                <a href="/main/login.php" class="nav-link-glass nav-link-cta">Login / Sign Up</a>
            </nav>
        </div>
    </header>
    <div class="hero">
        <div class="hero-bg"></div>
        <div class="container position-relative" style="z-index:2;">
            <div class="hero-title">Your Next<br>Career Move<br><span style="color:#00e0d6; font-weight:700;">Starts Here.</span></div>
            <div class="hero-subtitle">A new era of job search. Effortless. Curated. Beautiful.<br>Discover top jobs, apply in one click, and get hired faster.</div>
        </div>
    </div>
    <div class="glass-panel">
        <div class="features-title">Why JobPortal?</div>
        <div class="features-list">
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                <div class="feature-title">1-Click Apply</div>
                <div class="feature-desc">Apply to jobs instantly with your profile. No more tedious forms.</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-briefcase"></i></div>
                <div class="feature-title">Curated Opportunities</div>
                <div class="feature-desc">Handpicked jobs from top companies, updated daily.</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-user-shield"></i></div>
                <div class="feature-title">Verified Employers</div>
                <div class="feature-desc">We screen every employer for authenticity and quality.</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                <div class="feature-title">Career Growth</div>
                <div class="feature-desc">Resources, tips, and personalized recommendations to boost your career.</div>
            </div>
        </div>
    </div>
    <div class="testimonials-section">
        <div class="testimonials-title">What Our Users Say</div>
        <div class="testimonial-carousel">
            <div class="testimonial-item">
                <div class="testimonial-quote"><i class="fas fa-quote-left"></i></div>
                "I landed my dream job in just two weeks! The process was so easy and the jobs were top-notch."
                <div class="testimonial-user">
                    <img src="/img/testimonial-1.jpg" alt="User 1">
                    <span>Priya S., Product Designer</span>
                </div>
            </div>
            <div class="testimonial-item">
                <div class="testimonial-quote"><i class="fas fa-quote-left"></i></div>
                "As a recruiter, I found the best talent faster than ever. The platform is intuitive and powerful."
                <div class="testimonial-user">
                    <img src="/img/testimonial-2.jpg" alt="User 2">
                    <span>Rahul M., Recruiter</span>
                </div>
            </div>
            <div class="testimonial-item">
                <div class="testimonial-quote"><i class="fas fa-quote-left"></i></div>
                "The 1-click apply feature is a game changer. Highly recommend to all job seekers!"
                <div class="testimonial-user">
                    <img src="/img/testimonial-3.jpg" alt="User 3">
                    <span>Emily T., Software Engineer</span>
                </div>
            </div>
        </div>
    </div>
    <a href="/main/login.php" class="cta-btn-sticky">Get Started Free</a>
    <footer style="width:100vw; background: linear-gradient(90deg, #23233a 0%, #181828 100%); border-top: 1.5px solid #23233a; margin-top:2rem; padding: 1.5rem 0 1rem 0; text-align:center; font-size:1rem; color:#b3b3c6;">
        <div style="font-weight:600; letter-spacing:-0.5px; font-size:1.2rem;">
            <i class="fas fa-envelope me-2" style="color:#7b1fa2;"></i>Contact us: <a href="mailto:support@jobportal.com" style="color:#00e0d6; text-decoration:underline;">support@jobportal.com</a>
        </div>
        <div style="margin-top:0.5rem; color:#7b1fa2; font-size:1rem;">
            <i class="fas fa-phone me-2"></i>+1 (800) 123-4567
        </div>
        <div style="margin-top:0.5rem; color:#b3b3c6; font-size:0.98rem;">
            &copy; <?= date('Y') ?> <span style="color:#00e0d6;">Job</span><span style="color:#7b1fa2;">Portal</span> &mdash; Your gateway to new opportunities
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>