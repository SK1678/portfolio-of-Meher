<?php
require 'db.php';

// Settings are already fetched in db.php via require 'db.php' powyżej.


// Global Fonts & Styling
$heading_font = $settings['heading_font'] ?? 'Outfit';
$body_font = $settings['body_font'] ?? 'Outfit';
$font_query_str = "family=" . str_replace(' ', '+', $heading_font) . ":wght@300;400;700&family=" . str_replace(' ', '+', $body_font) . ":wght@300;400;700";

// For pages that might have specific font needs (like the hero name)
$name_style = json_decode($settings['hero_name_style'] ?? '{}', true);
$title_style = json_decode($settings['hero_title_style'] ?? '{}', true);
$selected_fonts = array_unique([$name_style['font'] ?? 'Outfit', $title_style['font'] ?? 'Outfit']);
$extra_fonts = implode('&family=', array_map(function ($f) {
    return str_replace(' ', '+', $f) . ':wght@300;400;700'; }, $selected_fonts));
$cv = $settings['cache_version'] ?? '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= BASE_PATH ?>">
    <title><?= htmlspecialchars($settings['site_title'] ?? 'Meher Kanti Sarkar | Portfolio') ?> | <?= $page_title ?? '' ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= htmlspecialchars($settings['meta_description'] ?? '') ?>">
    <meta name="keywords" content="<?= htmlspecialchars($settings['meta_keywords'] ?? '') ?>">
    <meta name="robots" content="<?= ($settings['index_site'] ?? '1') == '1' ? 'index, follow' : 'noindex, nofollow' ?>">
    
    <!-- Favicon -->
    <?php if(!empty($settings['favicon'])): ?>
        <link rel="icon" href="<?= $settings['favicon'] ?>">
    <?php endif; ?>

    <link rel="stylesheet" href="assets/css/style.css?v=<?= $cv ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=<?= $extra_fonts ?>&<?= $font_query_str ?>&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-theme: <?= $settings['theme_color'] ?? '#8e44ad' ?>;
            --secondary-theme: <?= $settings['secondary_color'] ?? '#ffcc00' ?>;
            --body-font: '<?= $body_font ?>', sans-serif;
            --heading-font: '<?= $heading_font ?>', sans-serif;
            --body-text: <?= $settings['body_text_color'] ?? '#ffffff' ?>;
            --heading-text: <?= $settings['heading_color'] ?? '#ffffff' ?>;
            --btn-bg: <?= $settings['btn_bg_color'] ?? '#8e44ad' ?>;
            --btn-text: <?= $settings['btn_text_color'] ?? '#ffffff' ?>;
        }

        body { font-family: var(--body-font); color: var(--body-text); background: #000; }
        h1, h2, h3, h4, h5, h6 { font-family: var(--heading-font); color: var(--heading-text); }

        /* Custom Theme Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            background-color: #111;
        }
        ::-webkit-scrollbar-thumb {
            background-color: var(--primary-theme);
            border-radius: 10px;
            border: 2px solid #111;
        }
        ::-webkit-scrollbar-thumb:hover {
            background-color: var(--secondary-theme);
        }
        /* Firefox Support */
        * {
            scrollbar-width: thin;
            scrollbar-color: var(--primary-theme) #111;
        }
        
        /* Name Style - Local Overrides Global */
        .hero-container .hero-content h1 {
            font-family: <?= !empty($name_style['font']) ? "'".$name_style['font']."', sans-serif" : "var(--heading-font)" ?>;
            font-size: <?= $name_style['size'] ?? '5' ?>rem;
            <?php if (($name_style['color_type'] ?? 'default') === 'gradient'): ?>
                background: linear-gradient(135deg, <?= $name_style['color_1'] ?? '#fff' ?>, <?= $name_style['color_2'] ?? '#ffcc00' ?>);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            <?php elseif (($name_style['color_type'] ?? 'default') === 'plain'): ?>
                color: <?= $name_style['color_1'] ?? 'var(--primary-theme)' ?>;
            <?php else: ?>
                color: var(--primary-theme);
            <?php endif; ?>
        }

        /* Title Style - Local Overrides Global */
        .hero-content .typing-text {
            font-family: <?= !empty($title_style['font']) ? "'".$title_style['font']."', sans-serif" : "var(--body-font)" ?>;
            font-size: <?= $title_style['size'] ?? '1.5' ?>rem;
            <?php if (($title_style['color_type'] ?? 'default') === 'gradient'): ?>
                background: linear-gradient(135deg, <?= $title_style['color_1'] ?? '#ddd' ?>, <?= $title_style['color_2'] ?? '#fff' ?>);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            <?php elseif (($title_style['color_type'] ?? 'default') === 'plain'): ?>
                color: <?= $title_style['color_1'] ?? 'var(--secondary-theme)' ?>;
            <?php else: ?>
                color: var(--secondary-theme);
            <?php endif; ?>
        }

        /* Dynamic Buttons - Local Overrides Global */
        .btn-dynamic { 
            display: inline-block;
            padding: 14px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 800;
            font-size: 0.9rem;
            letter-spacing: 1.5px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            margin: 0 10px;
        }

        .btn-dynamic:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.4);
        }

        /* Directional Page Transitions */
        .page-transition-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            z-index: 9999;
            transition: transform 0.7s cubic-bezier(0.86, 0, 0.07, 1);
            pointer-events: none;
            transform: translateY(0);
        }

        /* Slide UP (Moving Forward) */
        body.slide-up-in .page-transition-overlay {
            transform: translateY(-100%);
        }
        body.slide-up-out .page-transition-overlay {
            transform: translateY(100%); /* Start below */
        }
        body.slide-up-out.animating .page-transition-overlay {
            transform: translateY(0);
        }

        /* Slide DOWN (Moving Back) */
        body.slide-down-in .page-transition-overlay {
            transform: translateY(100%);
        }
        body.slide-down-out .page-transition-overlay {
            transform: translateY(-100%); /* Start above */
        }
        body.slide-down-out.animating .page-transition-overlay {
            transform: translateY(0);
        }

        /* Initial State: Screen Covered */
        body:not(.loaded) .page-transition-overlay {
            transform: translateY(0);
        }

        /* Fail-safe if JS fails to reveal page */
        body.js-failed .page-transition-overlay {
            display: none !important;
        }
    </style>
    <script>
        // Emergency fail-safe: Force show page if it's still black after 1.5 seconds
        setTimeout(function() {
            if (!document.body.classList.contains('loaded')) {
                document.body.classList.add('js-failed');
                console.warn("Page transition fail-safe triggered.");
            }
        }, 1500);
    </script>
</head>
<body>
    <div class="page-transition-overlay"></div>
