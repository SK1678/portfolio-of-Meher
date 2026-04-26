<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Access denied.");
}
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $upload_dir = '../assets/uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    // 1. Save Personal Info (Bio)
    $bio_data = [];
    if (!empty($_POST['bio_label'])) {
        foreach ($_POST['bio_label'] as $i => $label) {
            if (empty($label)) continue;
            $bio_data[] = [
                'label' => $label,
                'value' => $_POST['bio_value'][$i] ?? '',
                'type' => $_POST['bio_type'][$i] ?? 'text'
            ];
        }
    }
    $bio_json = json_encode($bio_data);
    $stmt = $conn->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES ('about_personal_info', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("ss", $bio_json, $bio_json);
    $stmt->execute();

    if (isset($_POST['career_objective'])) {
        $co = $_POST['career_objective'];
        $stmt_co = $conn->prepare("INSERT INTO homepage_settings (setting_key, setting_value) VALUES ('career_objective', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt_co->bind_param("ss", $co, $co);
        $stmt_co->execute();
    }

    // 2. Handle About Image
    if (isset($_FILES['about_image']) && $_FILES['about_image']['error'] == 0) {
        $ext = pathinfo($_FILES['about_image']['name'], PATHINFO_EXTENSION);
        $new_name = 'about_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['about_image']['tmp_name'], $upload_dir . $new_name)) {
            $img_path = 'assets/uploads/' . $new_name;
            $conn->query("INSERT INTO homepage_settings (setting_key, setting_value) VALUES ('about_image', '$img_path') ON DUPLICATE KEY UPDATE setting_value = '$img_path'");
        }
    }

    // 3. Handle CV File
    if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] == 0) {
        $ext = pathinfo($_FILES['cv_file']['name'], PATHINFO_EXTENSION);
        $new_name = 'resume_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['cv_file']['tmp_name'], $upload_dir . $new_name)) {
            $cv_path = 'assets/uploads/' . $new_name;
            $conn->query("INSERT INTO homepage_settings (setting_key, setting_value) VALUES ('cv_link', '$cv_path') ON DUPLICATE KEY UPDATE setting_value = '$cv_path'");
        }
    }

    // 4. Handle Experience & Education (Wipe and Re-insert)
    // Note: This will also clear item_attachments due to ON DELETE CASCADE
    $conn->query("DELETE FROM about_items");
    
    // Process Resume Items (Exp, Edu, Certs)
    $sections = ['exp' => 'experience', 'edu' => 'education', 'cert' => 'certification'];
    foreach ($sections as $key => $type) {
        if (!empty($_POST[$key]['title'])) {
            foreach ($_POST[$key]['title'] as $i => $title) {
                if (empty($title)) continue;
                $period = $_POST[$key]['period'][$i] ?? '';
                $duration = $_POST[$key]['duration'][$i] ?? null;
                $org = $_POST[$key]['org'][$i] ?? '';
                $desc = $_POST[$key]['desc'][$i] ?? '';
                
                $stmt = $conn->prepare("INSERT INTO about_items (type, title, organization, period, duration, description, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssi", $type, $title, $org, $period, $duration, $desc, $i);
                $stmt->execute();
                $item_id = $conn->insert_id;

                // Handle Multiple Attachments for this item
                if (!empty($_POST[$key]['attachments'][$i]['label'])) {
                    foreach ($_POST[$key]['attachments'][$i]['label'] as $j => $att_label) {
                        if (empty($att_label)) continue;
                        $att_url = $_POST[$key]['attachments'][$i]['url'][$j] ?? '';
                        
                        // Handle File Upload if present
                        $file_key = $key . "_files";
                        if (!empty($_FILES[$file_key]['name'][$i][$j])) {
                            $target_dir = "../assets/uploads/";
                            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                            
                            $filename = time() . "_" . basename($_FILES[$file_key]['name'][$i][$j]);
                            $target_file = $target_dir . $filename;
                            
                            if (move_uploaded_file($_FILES[$file_key]['tmp_name'][$i][$j], $target_file)) {
                                $att_url = "assets/uploads/" . $filename;
                            }
                        }

                        // Clean up "Selected: " prefix if manual URL wasn't entered
                        $att_url = str_replace('Selected: ', '', $att_url);

                        $is_protected = ($_POST[$key]['attachments'][$i]['is_protected'][$j] ?? 0) == 1 ? 1 : 0;
                        $password = $_POST[$key]['attachments'][$i]['password'][$j] ?? '';

                        $astmt = $conn->prepare("INSERT INTO item_attachments (item_id, label, url, is_protected, password) VALUES (?, ?, ?, ?, ?)");
                        $astmt->bind_param("issis", $item_id, $att_label, $att_url, $is_protected, $password);
                        $astmt->execute();
                    }
                }
            }
        }
    }

    // 5. Handle Skills (Wipe and Re-insert)
    $conn->query("DELETE FROM skills");
    if (!empty($_POST['skill']['name'])) {
        foreach ($_POST['skill']['name'] as $i => $name) {
            if (empty($name)) continue;
            $percent = $_POST['skill']['percent'][$i] ?? 0;
            $stmt = $conn->prepare("INSERT INTO skills (skill_name, percentage, sort_order) VALUES (?, ?, ?)");
            $stmt->bind_param("sii", $name, $percent, $i);
            $stmt->execute();
        }
    }

    $active_tab = $_POST['active_tab'] ?? 'personal';
    header("Location: " . BASE_PATH . "admin/about_settings?status=success&tab=$active_tab");
    exit;
}
