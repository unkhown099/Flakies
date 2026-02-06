DONT ADD THE DB_CONNECT.PHP SA GITHUB PARA HINDI PABAGO BAGO!!!

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

PS: GIT IGNORE THE VENDOR ALWAYS

UPDATE!!!

DOWNLOAD TINYMCE
LINK:https://www.tiny.cloud/get-tiny/self-hosted/

CREATE TABLE pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_name VARCHAR(50) UNIQUE,
    content LONGTEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

ALTER TABLE pages
ADD COLUMN section_name VARCHAR(50) NOT NULL AFTER page_name;

ALTER TABLE pages
DROP INDEX page_name;


INSERT INTO pages (page_name, section_name, content) VALUES
('about', 'hero', '<h1>About Flakies 🍚</h1><p>Bringing the authentic taste of the Philippines straight to your doorstep since day one.</p>'),
('about', 'our_story', '<h2>📖 Our Story</h2><p>Flakies was born from a passion for authentic Filipino cuisine and a dream to share the flavors of home with everyone. We believe that food is more than just sustenance—it''s a connection to culture, family, and tradition.</p><p>Starting as a small venture with handcrafted pastils and refreshing halo-halo, we''ve grown into a trusted name in Filipino delicacies. Every product we create carries the love, dedication, and authenticity that our family has instilled in us for generations.</p><p>Today, Flakies stands as a celebration of Filipino culinary heritage, delivering quality, tradition, and taste with every order.</p>'),
('about', 'our_mission', '<h2>🎯 Our Mission</h2><p>To celebrate and share authentic Filipino flavors with communities everywhere. We''re committed to:</p><ul><li>Providing premium quality ingredients in every product</li><li>Preserving traditional Filipino recipes and cooking methods</li><li>Ensuring fresh, timely delivery to our valued customers</li><li>Supporting local farmers and suppliers</li><li>Creating memorable experiences through authentic Filipino food</li></ul>'),
('about', 'our_values', '<h2>💎 Our Values</h2><div class="values-grid"><div class="value-card"><div class="value-icon">🌟</div><h3>Authenticity</h3><p>We honor traditional recipes and never compromise on the authentic Filipino taste.</p></div><div class="value-card"><div class="value-icon">❤️</div><h3>Quality</h3><p>Premium ingredients and meticulous preparation ensure every bite is perfect.</p></div><div class="value-card"><div class="value-icon">⚡</div><h3>Speed</h3><p>Fresh, quick delivery because great food should arrive at its best.</p></div><div class="value-card"><div class="value-icon">🤝</div><h3>Community</h3><p>We''re proud to serve our community and support local businesses.</p></div></div>'),
('about', 'meet_team', '<h2>👥 Meet Our Team</h2><p>Behind every delicious Flakies product is a dedicated team passionate about Filipino cuisine.</p><div class="team-grid"><div class="team-member"><div class="member-avatar">👨‍🍳</div><div class="member-info"><div class="member-name">Juan Santos</div><div class="member-role">Head Chef</div></div></div><div class="team-member"><div class="member-avatar">👩‍💼</div><div class="member-info"><div class="member-name">Maria Lopez</div><div class="member-role">Operations Manager</div></div></div><div class="team-member"><div class="member-avatar">👨‍💻</div><div class="member-info"><div class="member-name">Carlos Reyes</div><div class="member-role">Delivery Manager</div></div></div><div class="team-member"><div class="member-avatar">👩‍🍳</div><div class="member-info"><div class="member-name">Ana Garcia</div><div class="member-role">Product Specialist</div></div></div></div>');
