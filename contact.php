<?php
$page_title = "Contact Me";
require_once 'db.php';
include 'includes/public_head.php';

// Fetch contact-specific settings from the Central Information Center
$contact_email = $settings['contact_email'] ?? 'meher@example.com';
$contact_phone = $settings['contact_phone'] ?? '+880 1234 567890';
$contact_location = $settings['permanent_address'] ?? 'Dhaka, Bangladesh';
$contact_name = $settings['name'] ?? 'Meher Kanti Sarkar';
?>

<div class="contact-page-outer">
    <?php include 'includes/public_nav.php'; ?>

    <div class="contact-page-container">
        
        <!-- Header -->
        <header class="contact-header">
            <h1 class="contact-title text-uppercase">GET IN <span class="accent-text">TOUCH</span></h1>
            <div class="subtitle-wrapper">
                <div class="decor-line"></div>
                <p class="subtitle-text text-uppercase">Feel free to contact me anytimes</p>
                <div class="decor-line"></div>
            </div>
        </header>

        <div class="contact-grid">
            
            <!-- Left: Form -->
            <div class="contact-form-area">
                <h2 class="section-heading">Message Me</h2>
                <form id="contact-form" class="contact-form">
                    <div class="form-row">
                        <div class="form-group half">
                            <input type="text" name="name" class="form-input" placeholder="Name" required>
                        </div>
                        <div class="form-group half">
                            <input type="email" name="email" class="form-input" placeholder="Email" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <input type="text" name="subject" class="form-input" placeholder="Subject" required>
                    </div>
                    <div class="form-group">
                        <textarea name="message" class="form-input" rows="6" placeholder="Message" required></textarea>
                    </div>
                    <div class="form-action">
                        <button type="submit" class="contact-submit-btn">Send Message</button>
                    </div>
                </form>
            </div>

            <!-- Right: Info -->
            <div class="contact-info-area">
                <h2 class="section-heading">Contact Info</h2>
                <p class="contact-desc">Always available for freelance work if the right project comes along, Feel free to contact me!</p>
                
                <div class="info-list">
                    <div class="info-item">
                        <div class="info-icon"><i class="fa fa-user"></i></div>
                        <div class="info-text">
                            <label>Name</label>
                            <span><?= htmlspecialchars($contact_name) ?></span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fa fa-map-marker-alt"></i></div>
                        <div class="info-text">
                            <label>Location</label>
                            <span><?= htmlspecialchars($contact_location) ?></span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fa fa-phone"></i></div>
                        <div class="info-text">
                            <label>Call Me</label>
                            <span><?= htmlspecialchars($contact_phone) ?></span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fa fa-paper-plane"></i></div>
                        <div class="info-text">
                            <label>Email Me</label>
                            <span><?= htmlspecialchars($contact_email) ?></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
.contact-page-outer {
    background: #000;
    color: #fff;
    min-height: 100vh;
    padding-bottom: 100px;
}
.contact-page-container {
    max-width: 1140px;
    margin: 0 auto;
    padding: 100px 20px 0;
}

/* Grid Layout */
.contact-grid {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 80px;
    margin-top: 40px;
}

/* Header */
.contact-header {
    text-align: center;
    margin-bottom: 90px;
}
.contact-title {
    font-size: 4.5rem;
    font-weight: 900;
    letter-spacing: 2px;
    margin-bottom: 20px;
    line-height: 1;
}
.accent-text {
    color: var(--primary-theme);
}
.subtitle-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 25px;
}
.decor-line {
    width: 45px;
    height: 1px;
    background: var(--primary-theme);
    opacity: 0.8;
}
.subtitle-text {
    font-size: 0.85rem;
    letter-spacing: 3px;
    font-weight: 600;
    margin: 0;
    color: #fff;
    opacity: 0.9;
}

/* Section Headings */
.section-heading {
    font-size: 1.5rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 30px;
}
.contact-desc {
    color: #888;
    line-height: 1.6;
    font-size: 0.95rem;
    margin-bottom: 40px;
}

/* Form Styles */
.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}
.form-group {
    margin-bottom: 20px;
}
.form-group.half {
    flex: 1;
}
.form-input {
    width: 100%;
    background: #111;
    border: none;
    padding: 18px 20px;
    color: #fff;
    border-radius: 4px;
    font-family: var(--body-font);
    font-size: 0.95rem;
    transition: all 0.3s;
    box-sizing: border-box;
}
.form-input:focus {
    background: #151515;
    outline: 1px solid var(--primary-theme);
}
.contact-submit-btn {
    background: var(--primary-theme);
    color: #000;
    border: none;
    padding: 12px 35px;
    border-radius: 30px;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s;
}
.contact-submit-btn:hover {
    transform: translateY(-3px);
    opacity: 0.9;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

/* Info List */
.info-list {
    display: flex;
    flex-direction: column;
    gap: 35px;
    position: relative;
}
.info-item {
    display: flex;
    align-items: flex-start;
    gap: 0;
}
.info-icon {
    width: 60px;
    height: 50px;
    background: transparent;
    color: var(--primary-theme);
    display: flex;
    align-items: center;
    justify-content: flex-start;
    font-size: 1.4rem;
    position: relative;
}
.info-text {
    padding-left: 25px;
    border-left: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 50px;
}
.info-text label {
    display: block;
    color: #fff;
    font-weight: 700;
    font-size: 1.05rem;
    margin-bottom: 4px;
}
.info-text span {
    color: #999;
    font-size: 0.92rem;
    line-height: 1.4;
}

@keyframes accentPulse {
    0% { transform: scaleX(1); opacity: 0.6; }
    50% { transform: scaleX(1.4); opacity: 1; }
    100% { transform: scaleX(1); opacity: 0.6; }
}

@media (max-width: 768px) {
    .contact-title { font-size: 2.5rem; }
    .contact-page-container { padding-top: 60px; }
}
</style>

<script>
document.getElementById('contact-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const btn = form.querySelector('.contact-submit-btn');
    const originalText = btn.innerText;
    
    btn.innerText = 'Sending...';
    btn.disabled = true;

    const formData = new FormData(form);

    fetch('api_contact.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            form.reset();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    })
    .finally(() => {
        btn.innerText = originalText;
        btn.disabled = false;
    });
});
</script>
<?php include 'includes/public_footer.php'; ?>
