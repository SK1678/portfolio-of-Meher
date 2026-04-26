<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /po/admin/login");
    exit;
}
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $updates = [];
    $upload_dir = '../assets/uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    if (isset($_POST['is_global_settings'])) {
        // ─── GLOBAL SITE SETTINGS ─────────────────────────────
        $updates = [
            'site_title' => $_POST['site_title'] ?? '',
            'admin_email' => $_POST['admin_email'] ?? '',
            'timezone' => $_POST['timezone'] ?? 'UTC',
            'theme_color' => $_POST['theme_color'] ?? '#000',
            'secondary_color' => $_POST['secondary_color'] ?? '#fff',
            'body_text_color' => $_POST['body_text_color'] ?? '#fff',
            'heading_color' => $_POST['heading_color'] ?? '#fff',
            'heading_font' => $_POST['heading_font'] ?? 'Arial',
            'body_font' => $_POST['body_font'] ?? 'Arial',
            'btn_bg_color' => $_POST['btn_bg_color'] ?? '#000',
            'btn_text_color' => $_POST['btn_text_color'] ?? '#fff',
            'dark_mode' => isset($_POST['dark_mode']) ? '1' : '0',
            'meta_description' => $_POST['meta_description'] ?? '',
            'meta_keywords' => $_POST['meta_keywords'] ?? '',
            'google_analytics' => $_POST['google_analytics'] ?? '',
            'index_site' => isset($_POST['index_site']) ? '1' : '0',
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
            'post_hero_bg' => $_POST['post_hero_bg'] ?? '',
            'smtp_host' => $_POST['smtp_host'] ?? '',
            'smtp_port' => $_POST['smtp_port'] ?? '',
            'smtp_user' => $_POST['smtp_user'] ?? '',
            'smtp_pass' => $_POST['smtp_pass'] ?? '',
            'smtp_enc' => $_POST['smtp_enc'] ?? '',
            'smtp_from_email' => $_POST['smtp_from_email'] ?? '',
            'smtp_from_name' => $_POST['smtp_from_name'] ?? ''
        ];

        // Handle Favicon
        if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] == 0) {
            $ext = pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION);
            $new_name = 'favicon_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['favicon']['tmp_name'], $upload_dir . $new_name)) {
                $updates['favicon'] = 'assets/uploads/' . $new_name;
            }
        } elseif (isset($_POST['existing_favicon'])) {
            $updates['favicon'] = $_POST['existing_favicon'];
        }

        $redirect = "/po/admin/settings";
    } elseif (isset($_POST['is_basic_settings'])) {
        // ─── BASIC INFORMATION SETTINGS ───────────────────────
        $updates = [
            'name' => $_POST['name'] ?? '',
            'first_name' => $_POST['first_name'] ?? '',
            'last_name' => $_POST['last_name'] ?? '',
            'dob' => $_POST['dob'] ?? '',
            'gender' => $_POST['gender'] ?? '',
            'nationality' => $_POST['nationality'] ?? '',
            'religion' => $_POST['religion'] ?? '',
            'nid_no' => $_POST['nid_no'] ?? '',
            'passport_no' => $_POST['passport_no'] ?? '',
            'dl_no' => $_POST['dl_no'] ?? '',
            'father_name' => $_POST['father_name'] ?? '',
            'mother_name' => $_POST['mother_name'] ?? '',
            'contact_email' => $_POST['contact_email'] ?? '',
            'alt_email' => $_POST['alt_email'] ?? '',
            'contact_phone' => $_POST['contact_phone'] ?? '',
            'secondary_phone' => $_POST['secondary_phone'] ?? '',
            'present_address' => $_POST['present_address'] ?? '',
            'permanent_address' => $_POST['permanent_address'] ?? '',
            'cv_link' => $_POST['cv_link'] ?? '',
            'social_github' => $_POST['social_github'] ?? '',
            'social_linkedin' => $_POST['social_linkedin'] ?? '',
            'social_twitter' => $_POST['social_twitter'] ?? '',
            'social_facebook' => $_POST['social_facebook'] ?? '',
            'social_whatsapp' => $_POST['social_whatsapp'] ?? '',
            'social_threads' => $_POST['social_threads'] ?? '',
            'social_tiktok' => $_POST['social_tiktok'] ?? '',
            'social_messenger' => $_POST['social_messenger'] ?? ''
        ];

        // Handle CV File Upload
        if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] == 0) {
            $ext = pathinfo($_FILES['cv_file']['name'], PATHINFO_EXTENSION);
            $new_name = 'cv_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['cv_file']['tmp_name'], $upload_dir . $new_name)) {
                $updates['cv_link'] = 'assets/uploads/' . $new_name;
            }
        }

        $redirect = "/po/admin/settings_basic";
    } else {
        // ─── HOMEPAGE SETTINGS ────────────────────────────────
        $updates = [
            'name' => $_POST['name'] ?? '',
            'title' => $_POST['title'] ?? '',
            'hero_name_style' => json_encode($_POST['name_style'] ?? []),
            'hero_title_style' => json_encode($_POST['title_style'] ?? []),
            'social_twitter' => $_POST['social_twitter'] ?? '',
            'social_facebook' => $_POST['social_facebook'] ?? '',
            'social_linkedin' => $_POST['social_linkedin'] ?? '',
            'social_github' => $_POST['social_github'] ?? '',
            'social_whatsapp' => $_POST['social_whatsapp'] ?? '',
            'social_threads' => $_POST['social_threads'] ?? '',
            'social_tiktok' => $_POST['social_tiktok'] ?? '',
            'social_messenger' => $_POST['social_messenger'] ?? '',
            'bg_type' => $_POST['bg_type'] ?? 'plain',
            'post_hero_bg' => $_POST['post_hero_bg'] ?? ''
        ];

        // CTA Buttons
        $buttons = $_POST['buttons'] ?? [];
        $processed_buttons = [];
        foreach ($buttons as $index => $btn) {
            if (empty($btn['text'])) continue;
            $btn_link = $btn['link'] ?? '';
            $file_key = "button_files_$index";
            if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == 0) {
                $ext = pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION);
                $new_name = 'btn_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $upload_dir . $new_name)) {
                    $btn_link = 'assets/uploads/' . $new_name;
                }
            }
            $processed_buttons[] = [
                'text' => $btn['text'], 
                'link' => $btn_link, 
                'color' => $btn['color'] ?? '#000',
                'is_outline' => $btn['is_outline'] ?? 0
            ];
        }
        $updates['hero_buttons'] = json_encode($processed_buttons);

        // Background Media
        $current_media = $_POST['existing_media'] ?? [];
        $newly_uploaded = [];
        if (!empty($_FILES['bg_files']['name'][0])) {
            foreach ($_FILES['bg_files']['name'] as $key => $name) {
                if ($_FILES['bg_files']['error'][$key] == 0) {
                    $ext = pathinfo($name, PATHINFO_EXTENSION);
                    $new_name = 'bg_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['bg_files']['tmp_name'][$key], $upload_dir . $new_name)) {
                        $newly_uploaded[] = 'assets/uploads/' . $new_name;
                    }
                }
            }
        }
        $all_media = array_merge((array)$current_media, $newly_uploaded);
        if (!empty($all_media)) {
            $updates['bg_media'] = (($updates['bg_type'] ?? '') === 'slider') ? implode(',', $all_media) : $all_media[0];
        }

        $redirect = "/po/admin";
    }

    // ─── UPDATE DATABASE ──────────────────────────────────────
    foreach ($updates as $key => $val) {
        $stmt = $conn->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        if ($stmt) {
            $stmt->bind_param("sss", $key, $val, $val);
            $stmt->execute();
        } else {
            die("SQL Prepare Error: " . $conn->error);
        }
    }

    header("Location: $redirect?status=success");
    exit;
}
