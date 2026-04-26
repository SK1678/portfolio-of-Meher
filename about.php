<?php
$page_title = "About Me";
include 'includes/public_head.php';

// Fetch Dynamic Personal Info
$bio_defaults = ['First Name' => 'Meher Kanti', 'Last Name' => 'Sarkar', 'Age' => '24 Years', 'Nationality' => 'Bangladeshi', 'Freelance' => 'Available', 'Address' => 'Dhaka, Bangladesh', 'Phone' => '+880 1XXX XXXXXX', 'Email' => 'meher@example.com', 'Languages' => 'English, Bengali'];
$personal_info = json_decode($settings['about_personal_info'] ?? json_encode($bio_defaults), true);

$info_list = [];
foreach ($personal_info as $k => $v)
    $info_list[] = ['label' => $k, 'val' => $v];

// Fetch Resume Items
$experience = $conn->query("SELECT * FROM about_items WHERE type='experience' ORDER BY sort_order ASC, id DESC");
$education = $conn->query("SELECT * FROM about_items WHERE type='education' ORDER BY sort_order ASC, id DESC");
$certifications = $conn->query("SELECT * FROM about_items WHERE type='certification' ORDER BY sort_order ASC, id DESC");
$skills = $conn->query("SELECT * FROM skills ORDER BY sort_order ASC, id DESC");
?>

<div class="about-section-outer">
    <?php include 'includes/public_nav.php'; ?>

    <script>
        function checkAccess(url, password) {
            const input = prompt("This resource is password protected. Please enter the password:");
            if (input === null) return; // Cancelled
            if (input === password) {
                window.open(url, '_blank');
            } else {
                alert("Incorrect password. Access denied.");
            }
        }
    </script>

    <div class="about-container">
        <!-- HEADER SECTION -->
        <header class="about-header">
            <h1 class="about-title text-uppercase">ABOUT <span class="accent-text">ME</span></h1>
            <div class="subtitle-wrapper">
                <div class="decor-line"></div>
                <p class="subtitle-text text-uppercase">I Design and Code Beautiful Things, and I Love What I Do.</p>
                <div class="decor-line"></div>
            </div>
        </header>

        <!-- TOP CONTENT (IMAGE & INFO) -->
        <?php 
        $has_image = !empty($settings['about_image']) && file_exists($settings['about_image']);
        $bio_data = json_decode($settings['about_personal_info'] ?? '[]', true);
        $has_bio = !empty($bio_data);
        $has_cv = !empty($settings['cv_link']) && $settings['cv_link'] !== '#';
        
        if ($has_image || $has_bio || $has_cv): 
        ?>
        <div class="about-top-grid <?= (!$has_bio) ? 'justify-content-center' : '' ?>">
            <!-- Left: Image -->
            <?php if ($has_image): ?>
            <div class="about-image-area">
                <div class="image-box">
                    <div class="image-frame-bg"></div>
                    <img src="<?= htmlspecialchars($settings['about_image']) ?>" class="profile-pic" alt="Profile">
                </div>
            </div>
            <?php elseif ($has_bio): ?>
            <div class="about-image-area">
                <div class="image-box">
                    <div class="image-frame-bg"></div>
                    <div class="profile-pic d-flex align-items-center justify-content-center bg-dark text-muted" style="aspect-ratio: 1/1; font-size: 5rem;">
                        <i class="fa fa-user"></i>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Right: Personal Info -->
            <?php if ($has_bio || $has_cv): ?>
            <div class="about-info-area <?= (!$has_image) ? 'text-center' : '' ?>">
                <?php if ($has_bio): ?>
                <div class="bio-grid">
                    <?php
                    $mid = max(1, ceil(count($bio_data) / 2));
                    $cols = array_chunk($bio_data, $mid);

                    foreach ($cols as $col):
                    ?>
                        <div class="bio-column">
                            <?php foreach ($col as $item): ?>
                                <div class="bio-item">
                                    <span class="bio-label"><?= htmlspecialchars($item['label']) ?></span>
                                    <span class="bio-value">
                                        <?php if (($item['type'] ?? '') == 'link'): ?>
                                            <a href="<?= htmlspecialchars($item['value']) ?>" target="_blank"
                                                class="accent-text text-decoration-none">Click Here</a>
                                        <?php elseif (($item['type'] ?? '') == 'date' || stripos($item['label'], 'Date') !== false || stripos($item['label'], 'DoB') !== false): ?>
                                            <?php
                                            if (!empty($item['value'])) {
                                                $dob = new DateTime($item['value']);
                                                $today = new DateTime();
                                                $age = $today->diff($dob)->y;
                                                echo $dob->format('d-m-Y') . " ($age Years)";
                                            } else {
                                                echo "—";
                                            }
                                            ?>
                                        <?php else: ?>
                                            <?= !empty($item['value']) ? htmlspecialchars($item['value']) : '—' ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php if ($has_cv): ?>
                <div class="cv-action mt-4">
                    <a href="<?= htmlspecialchars($settings['cv_link']) ?>" target="_blank" class="cv-download-btn">
                        <i class="fa fa-file-pdf me-2"></i> View CV
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="separator-line"></div>
        <?php endif; ?>

        <!-- CAREER OBJECTIVE SECTION -->
        <?php if (!empty($settings['career_objective'])): ?>
            <div class="career-objective-section" style="margin-top: -30px; margin-bottom: 40px;">
                <h2 class="resume-heading ">CAREER OBJECTIVE</h2>
                <div class="career-text-wrap" style="padding-top: 5px;">
                    <p class="mb-0 text-white"
                        style="font-family: var(--body-font); font-size: 1.05rem; line-height: 1.8; opacity: 0.85; text-align: justify;">
                        <?= nl2br(htmlspecialchars($settings['career_objective'])) ?>
                    </p>
                </div>
            </div>
            <div class="separator-line"></div>
        <?php endif; ?>

        <!-- RESUME SECTION (EXP & EDU) -->
        <?php if ($experience->num_rows > 0 || $education->num_rows > 0): ?>
        <div class="resume-grid">
            <!-- Experience -->
            <?php if ($experience->num_rows > 0): ?>
            <div class="resume-col">
                <h2 class="resume-heading">EXPERIENCE</h2>
                <div class="timeline-container">
                    <?php while ($exp = $experience->fetch_assoc()): ?>
                        <div class="timeline-card">
                            <div class="timeline-bullet"></div>
                            <div class="timeline-card-inner">
                                <div class="date-tag">
                                    <i class="fa fa-calendar-alt"></i> <?= htmlspecialchars($exp['period']) ?>
                                    <?php if (!empty($exp['duration'])): ?>
                                        <span class="opacity-75 ms-1"
                                            style="font-size: 0.8em;">(<?= htmlspecialchars($exp['duration']) ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="job-heading">
                                    <span class="job-title"><?= htmlspecialchars($exp['title']) ?></span>
                                    <span class="job-sep">—</span>
                                    <span class="job-org accent-text"><?= htmlspecialchars($exp['organization']) ?></span>
                                </h3>
                                <p class="job-desc"><?= nl2br(htmlspecialchars($exp['description'])) ?></p>

                                <!-- Multiple Attachments -->
                                <div class="attachments-display mt-2 d-flex flex-wrap gap-2">
                                    <?php
                                    $item_id = $exp['id'];
                                    $atts = $conn->query("SELECT * FROM item_attachments WHERE item_id=$item_id");
                                    while ($att = $atts->fetch_assoc()):
                                        $is_locked = $att['is_protected'];
                                        ?>
                                        <a href="<?= $is_locked ? 'javascript:void(0)' : htmlspecialchars($att['url']) ?>"
                                            <?= $is_locked ? 'onclick="checkAccess(\'' . htmlspecialchars($att['url']) . '\', \'' . htmlspecialchars($att['password']) . '\')"' : 'target="_blank"' ?> class="att-tag"
                                            title="<?= $is_locked ? 'Password Protected' : 'Open Link' ?>">
                                            <?= htmlspecialchars($att['label']) ?>
                                        </a>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Education -->
            <?php if ($education->num_rows > 0): ?>
            <div class="resume-col">
                <h2 class="resume-heading">EDUCATION</h2>
                <div class="timeline-container">
                    <?php while ($edu = $education->fetch_assoc()): ?>
                        <div class="timeline-card">
                            <div class="timeline-bullet"></div>
                            <div class="timeline-card-inner">
                                <div class="date-tag">
                                    <i class="fa fa-calendar-alt"></i> <?= htmlspecialchars($edu['period']) ?>
                                    <?php if (!empty($edu['duration'])): ?>
                                        <span class="opacity-75 ms-1"
                                            style="font-size: 0.8em;">(<?= htmlspecialchars($edu['duration']) ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="job-heading">
                                    <span class="job-title"><?= htmlspecialchars($edu['title']) ?></span>
                                    <span class="job-sep">—</span>
                                    <span class="job-org accent-text"><?= htmlspecialchars($edu['organization']) ?></span>
                                </h3>
                                <p class="job-desc"><?= nl2br(htmlspecialchars($edu['description'])) ?></p>

                                <!-- Multiple Attachments -->
                                <div class="attachments-display mt-2 d-flex flex-wrap gap-2">
                                    <?php
                                    $item_id = $edu['id'];
                                    $atts = $conn->query("SELECT * FROM item_attachments WHERE item_id=$item_id");
                                    while ($att = $atts->fetch_assoc()):
                                        $is_locked = $att['is_protected'];
                                        ?>
                                        <a href="<?= $is_locked ? 'javascript:void(0)' : htmlspecialchars($att['url']) ?>"
                                            <?= $is_locked ? 'onclick="checkAccess(\'' . htmlspecialchars($att['url']) . '\', \'' . htmlspecialchars($att['password']) . '\')"' : 'target="_blank"' ?> class="att-tag"
                                            title="<?= $is_locked ? 'Password Protected' : 'Open Link' ?>">
                                            <?= htmlspecialchars($att['label']) ?>
                                        </a>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="separator-line"></div>
        <?php endif; ?>

        <!-- SKILLS SECTION -->
        <?php if ($skills->num_rows > 0): ?>
        <div class="skills-section">
            <h2 class="resume-heading skill-title">SKILLS</h2>
            <div class="skills-grid">
                <?php while ($skill = $skills->fetch_assoc()): ?>
                    <div class="skill-entry">
                        <div class="skill-name"><?= htmlspecialchars($skill['skill_name']) ?></div>
                        <div class="skill-track">
                            <div class="skill-fill" data-width="<?= $skill['percentage'] ?>%" style="width: 0%;">
                                <div class="skill-tooltip"><?= $skill['percentage'] ?>%</div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <div class="separator-line"></div>
        <?php endif; ?>

        <!-- CERTIFICATIONS & AWARDS -->
        <?php if ($certifications->num_rows > 0): ?>
        <div class="certs-section">
            <h2 class="resume-heading skill-title">AWARDS & CERTIFICATIONS</h2>
            <div class="certs-grid">
                <?php while ($cert = $certifications->fetch_assoc()): ?>
                    <div class="cert-card">
                        <div class="cert-info">
                            <h3 class="cert-name">
                                <?= htmlspecialchars($cert['title']) ?>
                            </h3>
                            <p class="cert-issuer accent-text"><?= htmlspecialchars($cert['organization']) ?>
                                &nbsp;
                                <span class="cert-year-inline ms-2">
                                    <?= htmlspecialchars($cert['period']) ?>
                                </span>
                            </p>
                            <?php if (!empty($cert['description'])): ?>
                                <p class="cert-desc"><?= htmlspecialchars($cert['description']) ?></p>
                            <?php endif; ?>

                            <!-- Attachments (Certificates) -->
                            <div class="attachments-display mt-2 d-flex flex-wrap gap-2">
                                <?php
                                $item_id = $cert['id'];
                                $atts = $conn->query("SELECT * FROM item_attachments WHERE item_id=$item_id");
                                while ($att = $atts->fetch_assoc()):
                                    $is_locked = $att['is_protected'];
                                    ?>
                                    <a href="<?= $is_locked ? 'javascript:void(0)' : htmlspecialchars($att['url']) ?>"
                                        <?= $is_locked ? 'onclick="checkAccess(\'' . htmlspecialchars($att['url']) . '\', \'' . htmlspecialchars($att['password']) . '\')"' : 'target="_blank"' ?> class="att-tag"
                                        title="<?= $is_locked ? 'Password Protected' : 'Open Certificate' ?>">
                                        <?= htmlspecialchars($att['label']) ?>
                                    </a>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <div class="separator-line"></div>
        <?php endif; ?>
    </div>
</div>

<style>
    /* Force Enable Scroll */
    html,
    body {
        overflow-y: auto !important;
        overflow-x: hidden !important;
        height: auto !important;
        min-height: 100vh !important;
    }

    /* Premium Scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: #111;
    }

    ::-webkit-scrollbar-thumb {
        background: var(--primary-theme);
        border-radius: 10px;
    }

    /* Reset and Theme Bases */
    .about-section-outer {
        background-color: #111111;
        color: #ffffff;
        min-height: 100vh;
        width: 100%;
        font-family: 'Poppins', sans-serif;
        padding: 80px 0;
        position: relative;
        z-index: 1;
    }

    .accent-text {
        color: var(--primary-theme);
    }

    .about-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* Header Styling */
    .about-header {
        text-align: center;
        margin-bottom: 90px;
    }

    .about-title {
        font-size: 4.5rem;
        font-weight: 900;
        letter-spacing: 2px;
        margin-bottom: 20px;
        line-height: 1;
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

    /* Top Grid Styling */
    .about-top-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 60px;
        align-items: flex-start;
        margin-bottom: 80px;
    }

    .about-image-area {
        flex: 0 0 420px;
    }

    .about-info-area {
        flex: 1;
    }

    .image-box {
        position: relative;
        padding-bottom: 30px;
        padding-right: 30px;
    }

    .profile-pic {
        width: 100%;
        position: relative;
        z-index: 2;
        border-radius: 4px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
    }

    .image-frame-bg {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 100%;
        height: 100%;
        border: 4px solid var(--primary-theme);
        border-radius: 4px;
        z-index: 1;
    }

    .bio-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 25px 50px;
        margin-bottom: 45px;
    }

    .bio-item {
        font-size: 0.9rem;
        display: flex;
        align-items: flex-start;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        padding-bottom: 15px;
    }

    .bio-label {
        color: #bbbbbb;
        font-weight: 500;
        min-width: 130px;
        display: inline-block;
        opacity: 0.8;
    }

    .bio-value {
        font-weight: 700;
        color: #ffffff;
        letter-spacing: 0.5px;
    }

    .cv-download-btn {
        background: var(--primary-theme);
        color: #000;
        padding: 18px 50px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 900;
        font-size: 0.85rem;
        display: inline-block;
        letter-spacing: 2px;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .cv-download-btn:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
        color: #000;
    }

    .separator-line {
        height: 1px;
        background: rgba(255, 255, 255, 0.05);
        margin: 80px 0;
    }

    /* Resume (Timeline) Styling */
    .resume-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 80px;
    }

    .resume-col {
        flex: 1;
        min-width: 300px;
    }

    .resume-heading {
        font-size: 1.6rem;
        font-weight: 800;
        margin-bottom: 50px;
        letter-spacing: 2px;
    }

    .timeline-container {
        border-left: 1px solid var(--primary-theme);
        margin-left: 10px;
        position: relative;
        opacity: 0.8;
    }

    .timeline-card {
        position: relative;
        padding-left: 50px;
        padding-bottom: 55px;
    }

    .timeline-bullet {
        position: absolute;
        left: -9px;
        top: 0;
        width: 18px;
        height: 18px;
        background: #111;
        border: 2px solid var(--primary-theme);
        border-radius: 50%;
        z-index: 2;
    }

    .date-tag {
        background: #222;
        display: inline-block;
        padding: 5px 18px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 800;
        margin-bottom: 18px;
        letter-spacing: 1.5px;
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.03);
    }

    .job-heading {
        font-size: 1.15rem;
        font-weight: 800;
        margin-bottom: 15px;
        letter-spacing: 0.5px;
    }

    .job-title {
        color: #fff;
    }

    .job-sep {
        color: #444;
        margin: 0 12px;
        font-weight: 400;
    }

    .job-org {
        font-weight: 800;
    }

    .job-desc {
        font-size: 0.85rem;
        color: #bbbbbb;
        line-height: 1.9;
        font-weight: 400;
    }

    /* Skills Styling */
    .skill-title {
        text-align: center;
        margin-bottom: 80px;
    }

    .skills-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 50px 80px;
    }

    .skill-name {
        font-weight: 800;
        font-size: 0.9rem;
        margin-bottom: 20px;
        letter-spacing: 1.5px;
    }

    .skill-track {
        height: 2px;
        background: #252525;
        position: relative;
    }

    .skill-fill {
        height: 100%;
        background: var(--primary-theme);
        position: relative;
        transition: width 1s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .skill-tooltip {
        position: absolute;
        right: -24px;
        top: -42px;
        background: #1a1a1a;
        border: 1px solid var(--primary-theme);
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 900;
        color: #fff;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .skill-tooltip::after {
        content: '';
        position: absolute;
        bottom: -6px;
        left: 50%;
        transform: translateX(-50%);
        border-left: 6px solid transparent;
        border-right: 6px solid transparent;
        border-top: 6px solid #252525;
    }

    /* Attachment Micro-Tags */
    .att-tag {
        display: inline-block;
        padding: 1px 12px;
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--primary-theme);
        border: 1px solid var(--primary-theme);
        border-radius: 4px;
        text-decoration: none !important;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .att-tag:hover {
        background: var(--primary-theme);
        color: #000;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
    }

    /* Certifications Styling */
    .certs-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 30px;
        margin-bottom: 50px;
    }

    .cert-card {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.05);
        padding: 25px;
        border-radius: 8px;
        display: flex;
        gap: 25px;
        transition: all 0.3s ease;
    }

    .cert-card:hover {
        border-color: var(--primary-theme);
        transform: translateY(-5px);
        background: rgba(255, 255, 255, 0.04);
    }

    .cert-year-inline {
        background: #222;
        color: #fff;
        padding: 3px 10px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 800;
        border: 1px solid rgba(255, 255, 255, 0.1);
        letter-spacing: 1px;
        vertical-align: middle;
        display: inline-block;
        margin-bottom: 3px;
    }

    .cert-info {
        flex: 1;
    }

    .cert-name {
        font-size: 1.1rem;
        font-weight: 800;
        margin-bottom: 5px;
        color: #fff;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
    }

    .cert-issuer {
        font-size: 0.85rem;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .cert-desc {
        font-size: 0.8rem;
        color: #888;
        line-height: 1.6;
        margin-bottom: 15px;
    }

    /* Mobile Responsive */
    @media (max-width: 991px) {
        .about-top-grid {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .bio-grid {
            text-align: left;
        }

        .resume-grid {
            flex-direction: column;
        }

        .skills-grid {
            grid-template-columns: 1fr;
        }

        .certs-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const skillFills = document.querySelectorAll('.skill-fill');

        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = entry.target;
                    target.style.width = target.getAttribute('data-width');
                    observer.unobserve(target); // Only animate once
                }
            });
        }, {
            threshold: 0.1, // Trigger when 10% of the bar is visible
            rootMargin: "0px 0px -50px 0px" // Trigger slightly before it fully hits the bottom
        });

        skillFills.forEach(fill => observer.observe(fill));
    });
</script>

<?php include 'includes/public_footer.php'; ?>