DONT ADD THE DB_CONNECT.PHP SA GITHUB PARA HINDI PABAGO BAGO

Install Composer for PHPMAILER

1. Install Composer (if not already installed):

Download Composer from https://getcomposer.org/
.

Install it globally so you can run composer commands in the terminal.

2. Install PHPMailer via Composer:
Open your project folder in the terminal and run:

composer require phpmailer/phpmailer

3. Include Composer Autoloader in your PHP file:
In forgot_password.php, at the very top, add:

require '../vendor/autoload.php';

PS: Just need some modification
