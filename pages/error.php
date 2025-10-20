<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flakies - Page Not Found</title>
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
            display: flex;
            flex-direction: column;
        }

        .container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .error-content {
            background: white;
            border-radius: 30px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            text-align: center;
            max-width: 600px;
            width: 100%;
        }

        .error-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        .error-code {
            font-size: 4rem;
            font-weight: 900;
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .error-title {
            font-size: 2rem;
            color: #2d2d2d;
            margin-bottom: 1rem;
            font-weight: 800;
        }

        .error-message {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.6;
        }

        .error-description {
            font-size: 0.95rem;
            color: #999;
            margin-bottom: 2rem;
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 15px;
            border-left: 4px solid #f4e04d;
        }

        .error-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2rem;
            font-size: 1rem;
            font-weight: 700;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            color: #2d2d2d;
        }

        .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(212, 169, 66, 0.4);
        }

        .btn-secondary {
            background: #2d2d2d;
            color: #f4e04d;
            border: 2px solid #f4e04d;
        }

        .btn-secondary:hover {
            background: #1a1a1a;
        }

        .suggestions {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #f0f0f0;
        }

        .suggestions h3 {
            font-size: 1rem;
            color: #2d2d2d;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .suggestion-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .suggestion-link {
            padding: 0.8rem;
            background: #f8f9fa;
            border-radius: 10px;
            text-decoration: none;
            color: #667eea;
            font-weight: 600;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .suggestion-link:hover {
            background: #f4e04d;
            color: #2d2d2d;
            border-color: #d4a942;
        }

        @media(max-width:768px) {
            .error-code {
                font-size: 3rem;
            }

            .error-title {
                font-size: 1.5rem;
            }

            .error-content {
                padding: 2rem;
            }

            .error-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="error-content">
            <div class="error-icon">😕</div>
            <div class="error-code">404</div>
            <h1 class="error-title">Page Not Found</h1>
            <p class="error-message">Oops! The page you're looking for doesn't exist.</p>
            <div class="error-description">
                The requested URL was not found on this server.
            </div>
            <div class="error-buttons">
                <a href="/Flakies/index.php" class="btn btn-primary">🏠 Go Home</a>
                <a href="/Flakies/pages/menu.php" class="btn btn-secondary">🍚 Browse Menu</a>
            </div>
            <div class="suggestions">
                <h3>Quick Links</h3>
                <div class="suggestion-links">
                    <a href="/Flakies/index.php" class="suggestion-link">Home</a>
                    <a href="/Flakies/pages/menu.php" class="suggestion-link">Menu</a>
                    <a href="/Flakies/pages/about.php" class="suggestion-link">About</a>
                    <a href="/Flakies/pages/contact.php" class="suggestion-link">Contact</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>