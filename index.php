<?php
$page_title = "Home";
include 'includes/public_head.php';

$hero_buttons = json_decode($settings['hero_buttons'] ?? '[]', true);
?>

    <div class="hero-container">
        <?php include 'includes/public_nav.php'; ?>

        <!-- Background Media -->
        <div class="bg-media">
            <?php if (($settings['bg_type'] ?? 'image') === 'video'): ?>
                <video autoplay muted loop id="bg-video">
                    <source src="<?= $settings['bg_media'] ?? '' ?>" type="video/mp4">
                </video>
            <?php elseif (($settings['bg_type'] ?? 'image') === 'slider'):
                $images = explode(',', $settings['bg_media'] ?? '');
                ?>
                <div class="slider">
                    <?php foreach ($images as $img): ?>
                        <div class="slide" style="background-image: url('<?= trim($img) ?>');"></div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-image"
                    style="background-image: url('<?= $settings['bg_media'] ?? 'assets/uploads/default_bg.jpg' ?>');"></div>
            <?php endif; ?>
            <div class="overlay"></div>
        </div>

        <!-- Content -->
        <div class="hero-content">
            <h1 class="fade-in"><?= htmlspecialchars($settings['name'] ?? 'Meher Kanti Sarkar') ?></h1>
            <p class="typing-text" data-titles="<?= htmlspecialchars($settings['title'] ?? 'I\'m a Designer') ?>">
                <span class="cursor">|</span>
            </p>

            <div class="cta-buttons">
                <?php foreach ($hero_buttons as $btn): 
                    $color = $btn['color'] ?? '#8e44ad';
                    $is_outline = ($btn['is_outline'] ?? 0) == 1;
                    $contrast = (hexdec(substr($color, 1, 2)) + hexdec(substr($color, 3, 2)) + hexdec(substr($color, 5, 2)) > 382) ? '#000' : '#fff';
                    
                    if ($is_outline) {
                        $style = "border: 2px solid $color; background: transparent; color: $color;";
                        $hover_style = "onmouseover=\"this.style.background='$color'; this.style.color='$contrast'\" onmouseout=\"this.style.background='transparent'; this.style.color='$color'\"";
                    } else {
                        $style = "background: $color; color: $contrast; border: 2px solid $color;";
                        $hover_style = "onmouseover=\"this.style.opacity='0.9'; this.style.transform='translateY(-5px)'\" onmouseout=\"this.style.opacity='1'; this.style.transform='translateY(0)'\"";
                    }
                ?>
                    <a href="<?= htmlspecialchars($btn['link']) ?>" class="btn-dynamic" style="<?= $style ?>" <?= $hover_style ?>>
                        <?= htmlspecialchars($btn['text']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="social-links">
            <?php 
            $platforms = [
                'twitter' => 'x-twitter', 
                'facebook' => 'facebook-f', 
                'linkedin' => 'linkedin-in', 
                'github' => 'github', 
                'instagram' => 'instagram',
                'whatsapp' => 'whatsapp',
                'threads' => 'at', // Fallback for threads in older FA versions
                'tiktok' => 'tiktok',
                'messenger' => 'facebook-messenger'
            ];
            foreach ($platforms as $key => $icon): 
                $link = trim($settings["social_$key"] ?? '');
                if (empty($link) || $link === '#') continue;
            ?>
                <a href="<?= htmlspecialchars($link) ?>" target="_blank"><i class="fab fa-<?= $icon ?>"></i></a>
            <?php endforeach; ?>
        </div>
    </div>

<?php include 'includes/public_footer.php'; ?>