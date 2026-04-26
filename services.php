<?php
require 'db.php';

// Settings are already fetched in db.php


// Fetch Services & Counters
$services = $conn->query("SELECT * FROM services ORDER BY sort_order ASC, id DESC");
$counters = $conn->query("SELECT * FROM counters ORDER BY sort_order ASC, id DESC");

$page_title = "Services";
include 'includes/public_head.php';
include 'includes/public_nav.php';
?>

<div class="services-page-wrapper">
    
    <!-- Header Section -->
    <header class="services-header">
        <h1>MY <span class="highlight">SERVICES</span></h1>
        <div class="subtitle-wrapper">
            <div class="decor-line"></div>
            <p class="subtitle-text">Professional services and achievements</p>
            <div class="decor-line"></div>
        </div>
    </header>

    <!-- Achievements Section -->
    <?php if ($counters->num_rows > 0): ?>
    <div class="custom-container">
        <div class="counters-grid">
            <?php while($c = $counters->fetch_assoc()): 
                // Remove non-numeric chars for target value
                $numeric_value = preg_replace('/[^0-9]/', '', $c['value']);
                $suffix = preg_replace('/[0-9]/', '', $c['value']);
            ?>
                <div class="counter-card">
                    <i class="fa <?= $c['icon'] ?> counter-icon"></i>
                    <span class="counter-label"><?= htmlspecialchars($c['title']) ?></span>
                    <h4 class="counter-value" data-target="<?= $numeric_value ?>" data-suffix="<?= $suffix ?>">0<?= $suffix ?></h4>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Services Section -->
    <?php if ($services->num_rows > 0): ?>
    <div class="custom-container">
        <div class="services-grid">
            <?php while($s = $services->fetch_assoc()): ?>
                <div class="service-box">
                    <i class="fa <?= $s['icon'] ?> service-icon"></i>
                    <h5 class="service-title"><?= htmlspecialchars($s['title']) ?></h5>
                    <p class="service-desc"><?= htmlspecialchars($s['description']) ?></p>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<style>
    /* Reset & Base Layout (Since Bootstrap is not used) */
    .services-page-wrapper {
        background: #111;
        min-height: 100vh;
        padding: 100px 20px 80px 20px;
        box-sizing: border-box;
    }

    .custom-container {
        max-width: 1100px;
        margin: 0 auto;
        padding: 0 15px;
    }

    /* Header */
    .services-header {
        text-align: center;
        margin-bottom: 50px;
    }
    .services-header h1 {
        font-family: var(--heading-font);
        color: #fff;
        font-size: 4.5rem;
        font-weight: 900;
        margin: 0 0 20px 0;
        letter-spacing: 2px;
        line-height: 1;
        text-transform: uppercase;
    }
    .services-header .highlight {
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
        font-family: var(--body-font);
        font-size: 0.85rem;
        letter-spacing: 3px;
        font-weight: 600;
        margin: 0;
        color: #fff;
        opacity: 0.9;
        text-transform: uppercase;
    }

    /* Counters Grid */
    .counters-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 60px;
        justify-content: center;
    }
    .counter-card {
        background: var(--primary-theme);
        border-radius: 12px;
        padding: 30px 20px;
        text-align: center;
        color: #fff;
        width: 160px; /* Force specific width to match reference */
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .counter-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.5);
    }
    .counter-icon {
        font-size: 2.5rem; /* Increased size */
        margin-bottom: 12px;
        display: block;
        opacity: 0.9;
    }
    .counter-label {
        font-family: var(--body-font);
        font-size: 0.9rem;
        display: block;
        margin-bottom: 5px;
        text-transform: uppercase;
        letter-spacing: 1px;
        opacity: 0.8;
    }
    .counter-value {
        font-family: var(--heading-font);
        font-size: 2.2rem; /* Increased size */
        font-weight: bold;
        margin: 0;
    }

    /* Services Grid */
    .services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
    }
    .service-box {
        background: transparent;
        border: 1px solid #333;
        padding: 40px 30px;
        text-align: center;
        transition: all 0.3s ease;
        cursor: default;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .service-icon {
        font-size: 2rem;
        color: #555;
        margin-bottom: 20px;
        transition: color 0.3s ease;
    }
    .service-title {
        font-family: var(--heading-font);
        color: #888;
        font-size: 1.25rem;
        font-weight: bold;
        margin: 0 0 15px 0;
        transition: color 0.3s ease;
    }
    .service-desc {
        font-family: var(--body-font);
        color: #666;
        font-size: 0.9rem;
        line-height: 1.8;
        margin: 0;
        transition: color 0.3s ease;
    }

    /* Service Hover/Active State */
    .service-box:hover {
        border-color: var(--primary-theme);
    }
    .service-box:hover .service-icon {
        color: var(--primary-theme);
    }
    .service-box:hover .service-title,
    .service-box:hover .service-desc {
        color: #fff;
    }

    .empty-state {
        text-align: center;
        color: #666;
        padding: 50px;
        width: 100%;
        grid-column: 1 / -1;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .services-header h1 { font-size: 2.2rem; }
        .counters-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 480px) {
        .counters-grid { grid-template-columns: 1fr; }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const counters = document.querySelectorAll('.counter-value');
        const speed = 200; // The lower the slower

        const animateCounter = (counter) => {
            const target = +counter.getAttribute('data-target');
            const suffix = counter.getAttribute('data-suffix');
            
            // Check if page is loaded, if not wait a bit
            if (!document.body.classList.contains('loaded')) {
                setTimeout(() => animateCounter(counter), 300);
                return;
            }

            const updateCount = () => {
                const count = +counter.innerText.replace(suffix, '');
                const inc = target / speed;

                if (count < target) {
                    counter.innerText = Math.ceil(count + inc) + suffix;
                    setTimeout(updateCount, 1);
                } else {
                    counter.innerText = target + suffix;
                }
            };

            updateCount();
        };

        const observerOptions = {
            threshold: 0.5
        };

        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        counters.forEach(counter => observer.observe(counter));
    });
</script>

<?php include 'includes/public_footer.php'; ?>
