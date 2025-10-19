<?php
require_once '../config/db_connect.php';
session_start();

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Redirect only when the user submits and is not logged in

    $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
    $email = $conn->real_escape_string(trim($_POST['email'] ?? ''));
    $phone = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $subject = $conn->real_escape_string(trim($_POST['subject'] ?? ''));
    $message = $conn->real_escape_string(trim($_POST['message'] ?? ''));

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $errorMessage = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Please enter a valid email address.";
    } else {
        // Use stored procedure
        $stmt = $conn->prepare("CALL InsertContactMessage(?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);

        if ($stmt->execute()) {
            $successMessage = "Thank you for your message! We'll get back to you soon.";
            $name = $email = $phone = $subject = $message = '';
        } else {
            $errorMessage = "There was an error sending your message. Please try again.";
        }

        $stmt->close();
    }
}

// Get customer ID if logged in
$customer_id = $_SESSION['customer_id'] ?? null;

// Fetch number of items in cart
$cartCount = 0;
if ($customer_id) {
    $cartQuery = $conn->query("SELECT SUM(quantity) as total_qty FROM cart WHERE customer_id = $customer_id");
    if ($cartQuery && $row = $cartQuery->fetch_assoc()) {
        $cartCount = $row['total_qty'] ?? 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flakies - Contact Us</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            min-height: 100vh;
        }

        nav {
            background: #2d2d2d;
            color: #f4e04d;
            padding: 1.5rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: -1px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
            align-items: center;
        }

        .nav-links a {
            color: #f4e04d;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
        }

        .nav-links a:hover {
            opacity: 0.8;
        }

        .auth-btn {
            display: inline-block;
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid #f4e04d;
        }

        .login-btn {
            background: transparent;
            color: #f4e04d;
        }

        .login-btn:hover {
            background: #f4e04d;
            color: #2d2d2d;
        }

        .register-btn {
            background: transparent;
            color: #2d2d2d;
        }

        .register-btn:hover {
            background: #f4e04d;
            color: #2d2d2d;
        }

        .cart-btn {
            background: #2d2d2d;
            color: #f4e04d;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.3s;
            border: 2px solid #f4e04d;
            cursor: pointer;
        }

        .cart-btn:hover {
            transform: scale(1.05);
            background: #f4e04d;
            color: #2d2d2d;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }

        .hero-section {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            margin-bottom: 3rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            text-align: center;
            color: #2d2d2d;
        }

        .hero-section h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-section p {
            font-size: 1.2rem;
            color: #666;
        }

        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .contact-info-section {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            color: #2d2d2d;
        }

        .contact-info-section h2 {
            font-size: 1.8rem;
            margin-bottom: 2rem;
            color: #2d2d2d;
            border-bottom: 3px solid #f4e04d;
            padding-bottom: 1rem;
        }

        .contact-item {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2rem;
            align-items: flex-start;
        }

        .contact-icon {
            font-size: 2rem;
            min-width: 2rem;
        }

        .contact-content h3 {
            font-size: 1.1rem;
            color: #2d2d2d;
            margin-bottom: 0.3rem;
            font-weight: 700;
        }

        .contact-content p {
            color: #666;
            line-height: 1.6;
        }

        .contact-content a {
            color: #d4a942;
            text-decoration: none;
            font-weight: 600;
            transition: opacity 0.3s;
        }

        .contact-content a:hover {
            opacity: 0.8;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .social-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            border: none;
            cursor: pointer;
            font-size: 1.5rem;
            transition: transform 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .social-btn:hover {
            transform: scale(1.1);
        }

        .form-section {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .form-section h2 {
            font-size: 1.8rem;
            margin-bottom: 2rem;
            color: #2d2d2d;
            border-bottom: 3px solid #f4e04d;
            padding-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2d2d2d;
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 1rem;
            transition: border-color 0.3s;
            color: #2d2d2d;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #d4a942;
            box-shadow: 0 0 0 3px rgba(212, 169, 66, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .required {
            color: #f56565;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .submit-btn {
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            color: #2d2d2d;
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }

        .submit-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(212, 169, 66, 0.4);
        }

        .submit-btn:active {
            transform: scale(0.98);
        }

        .success-message {
            background: #e6ffe6;
            border-left: 4px solid #48bb78;
            color: #22543d;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .error-message {
            background: #ffe6e6;
            border-left: 4px solid #f56565;
            color: #742a2a;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .map-section {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            margin-top: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            text-align: center;
            color: #2d2d2d;
        }

        .map-section h2 {
            font-size: 1.8rem;
            margin-bottom: 1rem;
            color: #2d2d2d;
            border-bottom: 3px solid #f4e04d;
            padding-bottom: 1rem;
        }

        .map-placeholder {
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            width: 100%;
            height: 300px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-top: 1rem;
        }

        .required-message {
        color: #f56565;
        display: none;
        font-weight: 500;
        margin-top: 5px;
        font-size: 0.9rem;
    }

        @media (max-width: 768px) {
            .content-wrapper {
                grid-template-columns: 1fr;
            }

            .hero-section h1 {
                font-size: 2rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .nav-links {
                gap: 1rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <nav>
        <div class="logo">
            <img src="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo" style="height:40px; margin-right:10px;">
            Flakies
        </div>
        <ul class="nav-links">
            <li><a href="../index.php">Home</a></li>
            <li><a href="./menu.php">Menu</a></li>
            <li><a href="./about.php">about</a></li>

            <?php if (isset($_SESSION['customer_id'])): ?>
                <li>
                    <a href="./cart.php" class="auth-btn cart-btn">
                        🛒 Cart (<?php echo $cartCount; ?>)
                    </a>
                </li>
                <li><a href="../login/logout.php" class="auth-btn login-btn">Logout</a></li>
            <?php else: ?>
                <li><a href="../login/login.php" class="auth-btn login-btn">Login</a></li>
                <li><a href="../login/register.php" class="auth-btn register-btn">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="container">
        <div class="hero-section">
            <h1>Get in Touch 💬</h1>
            <p>Have questions? We'd love to hear from you. Contact us anytime!</p>
        </div>

        <div class="content-wrapper">
            <!-- Contact Information -->
            <div class="contact-info-section">
                <h2>📍 Contact Information</h2>

                <div class="contact-item">
                    <div class="contact-icon">📍</div>
                    <div class="contact-content">
                        <h3>Address</h3>
                        <p>Pasay City, Metro Manila<br>Philippines 🇵🇭</p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">📱</div>
                    <div class="contact-content">
                        <h3>Phone</h3>
                        <p><a href="tel:+639170000000">+63 917 000 0000</a></p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">✉️</div>
                    <div class="contact-content">
                        <h3>Email</h3>
                        <p><a href="mailto:hello@flakies.com">hello@flakies.com</a></p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">⏰</div>
                    <div class="contact-content">
                        <h3>Hours of Operation</h3>
                        <p>Monday - Sunday<br>9:00 AM - 8:00 PM</p>
                    </div>
                </div>

                <h3 style="margin-top: 2rem; margin-bottom: 1rem; color: #2d2d2d;">Follow Us</h3>
                <div class="social-links">
                    <button class="social-btn" title="Facebook">f</button>
                    <button class="social-btn" title="Instagram">📷</button>
                    <button class="social-btn" title="Twitter">𝕏</button>
                    <button class="social-btn" title="TikTok">♪</button>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="form-section">
                <h2>Send us a Message</h2>

                <?php if ($successMessage): ?>
                    <div class="success-message">✓ <?php echo htmlspecialchars($successMessage); ?></div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="error-message">✗ <?php echo htmlspecialchars($errorMessage); ?></div>
                <?php endif; ?>

                <form id="contactForm" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>">
                            <small class="required-message">*Required</small>
                        </div>
                        <div class="form-group">
                            <label for="email">Email <span class="required">*</span></label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                            <small class="required-message">*Required / Invalid Email</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>" placeholder="+63 917 000 0000">
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject <span class="required">*</span></label>
                        <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($subject ?? ''); ?>" placeholder="How can we help?">
                        <small class="required-message">*Required</small>
                    </div>

                    <div class="form-group">
                        <label for="message">Message <span class="required">*</span></label>
                        <textarea id="message" name="message" placeholder="Tell us more..."><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                        <small class="required-message">*Required</small>
                    </div>

                    <button type="submit" class="submit-btn">Send Message ✉️</button>
                </form>
            </div>
        </div>

        <!-- Map Section -->
        <div class="map-section">
            <h2>Find Us Here</h2>
            <!-- Map Section -->
            <div class="map-section">
                <div style="width: 100%; height: 400px; border-radius: 15px; overflow: hidden;">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3858.1385734496016!2d121.0493210751221!3d14.761221873123954!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397b0215c5f5509%3A0xe47b1ff182ae61df!2s2651%20Magnolia%20St%2C%20Caloocan%2C%20Metro%20Manila!5e0!3m2!1sfil!2sph!4v1760868403578!5m2!1sfil!2sph" 
                    width="800" 
                    height="600" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('contactForm').addEventListener('submit', function(e) {
    let isValid = true;
    const name = document.getElementById('name');
    const email = document.getElementById('email');
    const subject = document.getElementById('subject');
    const message = document.getElementById('message');

    // Check if user is logged in (from PHP session)
    const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

    if (!isLoggedIn) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Login Required',
            text: 'Please login first to send a message!',
            confirmButtonText: 'Go to Login'
        }).then(() => {
            window.location.href = '../login/login.php';
        });
        return; // stop further validation
    }

    function validateField(field, checkEmail=false) {
        const msg = field.nextElementSibling;
        if (field.value.trim() === '' || (checkEmail && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value))) {
            field.style.borderColor = '#f56565';
            msg.style.display = 'block';
            isValid = false;
        } else {
            field.style.borderColor = '#d4a942';
            msg.style.display = 'none';
        }
    }

    validateField(name);
    validateField(email, true);
    validateField(subject);
    validateField(message);

    if (!isValid) {
        e.preventDefault();
    }
});
</script>
</body>
</html>