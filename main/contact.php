<!-- filepath: c:\Users\mandy\jobportal-php-mysql\main\contact.php -->
<?php
// No session required for public contact page
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Contact Us - JobPortal</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
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
        
        .contact-container {
            background: rgba(255,255,255,0.10);
            backdrop-filter: blur(18px) saturate(1.2);
            border: 1.5px solid rgba(255,255,255,0.13);
            border-radius: 24px;
            padding: 3rem 2rem;
            margin: 2rem auto;
            box-shadow: 0 8px 32px rgba(30,20,60,0.13);
        }
        
        .contact-title {
            color: #fff;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 1.5rem;
        }
        
        .contact-info {
            background: rgba(255,255,255,0.13);
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(123,63,228,0.08);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1.5px solid rgba(255,255,255,0.10);
            backdrop-filter: blur(8px) saturate(1.1);
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            color: #f3f3fa;
        }
        
        .contact-item i {
            color: #00e0d6;
            font-size: 1.2rem;
            margin-right: 1rem;
            width: 20px;
        }
        
        .map-container {
            background: rgba(255,255,255,0.13);
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(123,63,228,0.08);
            padding: 1rem;
            border: 1.5px solid rgba(255,255,255,0.10);
            backdrop-filter: blur(8px) saturate(1.1);
        }
        
        .map-container iframe {
            border-radius: 15px;
            border: none;
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
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .contact-container {
            animation: fadeInUp 0.6s cubic-bezier(.4,1.4,.6,1);
        }
    </style>
</head>

<body>
<?php include 'header.php'; ?>

    <!-- Contact Section -->
    <div class="container">
        <div class="contact-container">
            <h1 class="contact-title text-center">
                <i class="fas fa-envelope me-2"></i>Get In Touch
            </h1>
            <p class="text-center mb-5" style="color: #b3b3c6; font-size: 1.1rem;">
                Feel free to reach out to us for any inquiries or support. We are here to help you find the perfect job or hire the best talent.
            </p>
            
            <div class="row g-5">
                <div class="col-lg-6">
                    <div class="contact-info">
                        <h4 class="mb-4" style="color: #fff; font-weight: 600;">
                            <i class="fas fa-info-circle me-2" style="color: #00e0d6;"></i>Contact Information
                        </h4>
                        
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <strong>Address</strong><br>
                                123 Job Street, New York, NY 10001, USA
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <strong>Phone</strong><br>
                                +1 (555) 123-4567
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <strong>Email</strong><br>
                                support@jobportal.com
                            </div>
                        </div>
                        
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <strong>Business Hours</strong><br>
                                Monday - Friday: 9:00 AM - 6:00 PM EST
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="map-container">
                        <h4 class="mb-4" style="color: #fff; font-weight: 600;">
                            <i class="fas fa-map me-2" style="color: #00e0d6;"></i>Our Location
                        </h4>
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.183949659634!2d-74.00594108400567!3d40.71277937933185!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25a23e28c1191%3A0x49f75d3281df052a!2s150%20Park%20Row%2C%20New%20York%2C%20NY%2010007%2C%20USA!5e0!3m2!1sen!2sus!4v1640995200000!5m2!1sen!2sus" 
                            width="100%" 
                            height="300" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>