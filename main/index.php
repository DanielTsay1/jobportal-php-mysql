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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #2563eb;
            --primary-blue-dark: #1d4ed8;
            --accent-blue: #3b82f6;
            --bg-light: #f8fafc;
            --bg-white: #ffffff;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --border-light: #e5e7eb;
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.08), 0 2px 4px -2px rgb(0 0 0 / 0.08);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-light);
            color: var(--text-dark);
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
            padding-top: 68px;
        }

        .navbar {
            background: var(--bg-white);
            box-shadow: var(--shadow-md);
            padding: 1rem 0;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }

        .navbar-brand {
            font-weight: 900;
            font-size: 1.5rem;
            color: var(--primary-blue);
            text-decoration: none;
        }

        .hero {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            color: white;
            padding: 7rem 0 3rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-bg {
            position: absolute;
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%;
            z-index: 0;
            pointer-events: none;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
        }

        .hero-title {
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 900;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            letter-spacing: -0.02em;
            position: relative;
            z-index: 2;
            animation: fadeInUp 1s ease-out;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2.5rem;
            opacity: 0.95;
            font-weight: 400;
            position: relative;
            z-index: 2;
            animation: fadeInUp 1s ease-out 0.3s both;
        }

        .cta-btn {
            background: var(--bg-white);
            color: var(--primary-blue);
            border: none;
            border-radius: 12px;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            z-index: 2;
            animation: fadeInUp 1s ease-out 0.6s both;
        }

        .cta-btn:hover {
            background: var(--bg-light);
            color: var(--primary-blue-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.13);
        }

        .features-section {
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            padding: 3rem 2rem;
            margin: -3rem auto 2rem auto;
            max-width: 1000px;
            border: 1px solid var(--border-light);
            position: relative;
            z-index: 3;
            animation: fadeInUp 1s ease-out 0.9s both;
        }

        .features-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-dark);
            margin-bottom: 2rem;
            text-align: center;
        }

        .features-list {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            justify-content: center;
        }

        .feature-item {
            background: var(--bg-light);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            padding: 2rem 1.5rem;
            min-width: 280px;
            max-width: 320px;
            flex: 1 1 280px;
            text-align: center;
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
        }

        .feature-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 24px 0 rgba(37,99,235,0.10);
            border-color: var(--primary-blue);
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-blue);
            transition: transform 0.3s ease;
        }

        .feature-item:hover .feature-icon {
            transform: scale(1.1);
        }

        .feature-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--text-dark);
        }

        .feature-desc {
            color: var(--text-light);
            font-size: 1rem;
            line-height: 1.6;
        }

        .testimonials-section {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            text-align: center;
            animation: fadeInUp 1s ease-out 1.2s both;
        }

        .testimonials-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 2rem;
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
            background: var(--bg-white);
            border-radius: 18px;
            box-shadow: var(--shadow-md);
            padding: 2rem 1.5rem;
            min-width: 320px;
            max-width: 340px;
            flex: 0 0 320px;
            border: 1px solid var(--border-light);
            scroll-snap-align: start;
            transition: all 0.3s ease;
        }

        .testimonial-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px 0 rgba(37,99,235,0.10);
        }

        .testimonial-quote {
            font-size: 1.5rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
        }

        .testimonial-text {
            color: var(--text-dark);
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .testimonial-user {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            justify-content: center;
            color: var(--text-light);
        }

        .testimonial-user img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary-blue);
        }

        .nav-link {
            color: var(--text-dark);
            font-weight: 500;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: var(--bg-light);
            color: var(--primary-blue);
        }

        .nav-link-cta {
            background: var(--primary-blue);
            color: white !important;
            font-weight: 600;
            border-radius: 8px;
            padding: 0.5rem 1.5rem;
            margin-left: 0.5rem;
            transition: all 0.3s ease;
        }

        .nav-link-cta:hover {
            background: var(--primary-blue-dark);
            color: white;
            transform: translateY(-1px);
        }

        .typewriter-container {
            display: inline-block;
            font-size: clamp(2.5rem, 5vw, 4rem);
            font-weight: 900;
            color: white;
            line-height: 1.2;
            min-height: 4.5em;
            text-align: center;
            white-space: pre-line;
            position: relative;
            z-index: 2;
        }

        .typewriter-cursor {
            display: inline-block;
            color: white;
            font-weight: 700;
            font-size: 1em;
            margin-left: 2px;
            animation: blinkCursor 1s steps(1) infinite;
            vertical-align: bottom;
        }

        @keyframes blinkCursor {
            0%, 49% { opacity: 1; }
            50%, 100% { opacity: 0; }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .trusted-section {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            text-align: center;
            animation: fadeInUp 1s ease-out 1.5s both;
        }

        .trusted-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-light);
            margin-bottom: 1.5rem;
        }

        .trusted-logos {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            justify-content: center;
            align-items: center;
        }

        .trusted-logos img {
            height: 40px;
            opacity: 0.6;
            filter: grayscale(1);
            transition: all 0.3s ease;
        }

        .trusted-logos img:hover {
            opacity: 1;
            filter: none;
            transform: scale(1.1);
        }

        @media (max-width: 900px) {
            .features-list, .testimonial-carousel {
                flex-direction: column;
                gap: 1.5rem;
            }
            
            .features-section, .testimonials-section {
                padding: 2rem 1rem;
            }
            
            .testimonial-item {
                min-width: 90vw;
                max-width: 98vw;
            }
        }

        @media (max-width: 600px) {
            .hero-title {
                font-size: 2.1rem;
            }
            
            .features-title {
                font-size: 1.5rem;
            }
            
            .feature-item, .testimonial-item {
                min-width: 90vw;
                max-width: 98vw;
            }
            
            .cta-btn {
                font-size: 1rem;
                padding: 0.8rem 1.5rem;
            }
            
            .trusted-logos img {
                height: 30px;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container-fluid d-flex align-items-center justify-content-between px-4">
            <div class="navbar-brand">
                <i class="fas fa-rocket me-2"></i>Job<span style="color: var(--accent-blue);">Portal</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="/main/job-list.php" class="nav-link">Browse Jobs</a>
                <a href="/main/login.php" class="nav-link nav-link-cta">Login / Sign Up</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-bg"></div>
        <div class="container position-relative">
            <div class="hero-title typewriter-container">
                <span id="typewriter-text"></span><span class="typewriter-cursor">|</span>
            </div>
            <div class="hero-subtitle">
                A new era of job search. Effortless. Curated. Beautiful.<br>
                Discover top jobs, apply in one click, and get hired faster.
            </div>
            <a href="/main/job-list.php" class="cta-btn">
                <i class="fas fa-search me-2"></i>Explore Jobs
            </a>
        </div>
    </section>

    <!-- Features Section -->
    <div class="features-section">
        <div class="features-title">Why Choose JobPortal?</div>
        <div class="features-list">
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="feature-title">1-Click Apply</div>
                <div class="feature-desc">Apply to jobs instantly with your profile. No more tedious forms.</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div class="feature-title">Curated Opportunities</div>
                <div class="feature-desc">Handpicked jobs from top companies, updated daily.</div>
            </div>
            <div class="feature-item">
                <div class="feature-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="feature-title">Secure & Private</div>
                <div class="feature-desc">Your data is protected with industry-leading security.</div>
            </div>
        </div>
    </div>

    <!-- Testimonials Section -->
    <div class="testimonials-section">
        <div class="testimonials-title">What Our Users Say</div>
        <div class="testimonial-carousel">
            <div class="testimonial-item">
                <div class="testimonial-quote">
                    <i class="fas fa-quote-left"></i>
                </div>
                <div class="testimonial-text">
                    "JobPortal made my job search incredibly smooth. I found my dream job within weeks!"
                </div>
                <div class="testimonial-user">
                    <img src="/img/testimonial-1.jpg" alt="User">
                    <div>
                        <div style="font-weight: 600; color: var(--text-dark);">Sarah Johnson</div>
                        <div style="font-size: 0.9rem;">Software Engineer</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-item">
                <div class="testimonial-quote">
                    <i class="fas fa-quote-left"></i>
                </div>
                <div class="testimonial-text">
                    "The one-click apply feature saved me hours. Highly recommended!"
                </div>
                <div class="testimonial-user">
                    <img src="/img/testimonial-2.jpg" alt="User">
                    <div>
                        <div style="font-weight: 600; color: var(--text-dark);">Mike Chen</div>
                        <div style="font-size: 0.9rem;">Product Manager</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-item">
                <div class="testimonial-quote">
                    <i class="fas fa-quote-left"></i>
                </div>
                <div class="testimonial-text">
                    "Clean interface and great job recommendations. Love it!"
                </div>
                <div class="testimonial-user">
                    <img src="/img/testimonial-3.jpg" alt="User">
                    <div>
                        <div style="font-weight: 600; color: var(--text-dark);">Emily Davis</div>
                        <div style="font-size: 0.9rem;">UX Designer</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Trusted By Section -->
    <div class="trusted-section">
        <div class="trusted-title">Trusted by Leading Companies</div>
        <div class="trusted-logos">
            <img src="/img/com-logo-1.jpg" alt="Company 1">
            <img src="/img/com-logo-2.jpg" alt="Company 2">
            <img src="/img/com-logo-3.jpg" alt="Company 3">
            <img src="/img/com-logo-4.jpg" alt="Company 4">
            <img src="/img/com-logo-5.jpg" alt="Company 5">
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple typewriter animation
        document.addEventListener('DOMContentLoaded', function() {
            const lines = [
                'Your Next',
                'Career Move',
                'Starts Here.'
            ];
            const typewriter = document.getElementById('typewriter-text');
            const cursor = document.querySelector('.typewriter-cursor');
            let lineIdx = 0, charIdx = 0;

            function typeLine() {
                if (lineIdx >= lines.length) {
                    return;
                }
                const line = lines[lineIdx];
                if (charIdx <= line.length) {
                    let html = '';
                    for (let i = 0; i < lineIdx; ++i) {
                        html += lines[i] + '<br>';
                    }
                    html += line.slice(0, charIdx);
                    typewriter.innerHTML = html;
                    setTimeout(typeLine, 80);
                    charIdx++;
                } else {
                    charIdx = 0;
                    lineIdx++;
                    setTimeout(typeLine, 800);
                }
            }
            
            // Start typewriter after a short delay
            setTimeout(typeLine, 500);
        });
    </script>
</body>

</html>