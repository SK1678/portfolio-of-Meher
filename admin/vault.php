<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}
require '../db.php';

$upload_dir = '../uploads/vault/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("SELECT type, content FROM file_vault WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if ($row['type'] == 'file' && file_exists($row['content'])) {
            unlink($row['content']);
        }
        $conn->query("DELETE FROM file_vault WHERE id = $id");
    }
    header("Location: vault?msg=deleted");
    exit;
}

// Handle Add/Edit Item
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    
    $title = trim($_POST['title']);
    // Since radio buttons might be disabled during edit to prevent type switching, we read it or default
    // We actually keep the checked one enabled, but let's grab it or fallback
    $type = isset($_POST['type']) ? $_POST['type'] : '';
    
    // If editing and type isn't POSTed properly, fetch it from DB
    if ($action == 'edit' && empty($type) && $id > 0) {
        $st = $conn->prepare("SELECT type FROM file_vault WHERE id = ?");
        $st->bind_param("i", $id);
        $st->execute();
        $r = $st->get_result();
        if ($row = $r->fetch_assoc()) {
            $type = $row['type'];
        }
    }
    
    $content = '';

    if ($type == 'link') {
        $content = trim($_POST['link_content'] ?? '');
    } elseif ($type == 'text') {
        $content = trim($_POST['text_content'] ?? ''); // JS populates this from editor
    } elseif ($type == 'file') {
        if ($action == 'edit') {
            // Keep existing content unless new file uploaded
            $content = trim($_POST['existing_file_content'] ?? ''); 
        }
        if (isset($_FILES['file_content']) && $_FILES['file_content']['error'] == 0) {
            $file_name = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES["file_content"]["name"]));
            $target_file = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES["file_content"]["tmp_name"], $target_file)) {
                // If edit and new file uploaded, delete old
                if ($action == 'edit' && $content && file_exists($content)) {
                    unlink($content);
                }
                $content = $target_file;
            }
        }
    }

    if (!empty($title) && $content !== '') {
        if ($action == 'add') {
            $stmt = $conn->prepare("INSERT INTO file_vault (title, type, content) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sss", $title, $type, $content);
                if ($stmt->execute()) {
                    header("Location: vault?msg=added");
                    exit;
                } else {
                    die("DB Error: " . $stmt->error);
                }
            } else {
                die("Prepare Error: " . $conn->error);
            }
        } elseif ($action == 'edit' && $id > 0) {
            $stmt = $conn->prepare("UPDATE file_vault SET title=?, content=? WHERE id=?");
            $stmt->bind_param("ssi", $title, $content, $id);
            $stmt->execute();
            header("Location: vault?msg=edited");
            exit;
        }
    } else {
        die("Missing Title or Content. Title: '$title', Type: '$type', Content: '$content'");
    }
}

// Fetch Items
$items = [];
$result = $conn->query("SELECT * FROM file_vault ORDER BY created_at DESC");
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

$page_title = "File Vault";
include 'includes/head.php';
include 'includes/sidebar.php';
?>

<div class="main-content">
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid py-4 px-4">
        <div class="row align-items-center mb-4">
            <div class="col-md-5">
                <h4 class="fw-bold mb-1">File Vault</h4>
                <p class="text-muted small mb-0">Securely store your files, links, and important notes.</p>
            </div>
            <div class="col-md-7 d-flex justify-content-md-end mt-3 mt-md-0 gap-2">
                <div class="input-group input-group-sm shadow-sm rounded-pill" style="max-width: 250px;">
                    <span class="input-group-text bg-white border-end-0 rounded-start-pill ps-3"><i class="fa fa-search text-muted"></i></span>
                    <input type="text" id="vaultSearch" class="form-control border-start-0 rounded-end-pill py-2 bg-white" placeholder="Search vault..." oninput="filterVault()">
                </div>
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" onclick="openModal('add')">
                    <i class="fa fa-plus me-2"></i>Add
                </button>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] == 'added'): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4" role="alert">
                    <i class="fa fa-check-circle me-2"></i>Item added to vault successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif ($_GET['msg'] == 'edited'): ?>
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4" role="alert">
                    <i class="fa fa-check-circle me-2"></i>Item updated successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php elseif ($_GET['msg'] == 'deleted'): ?>
                <div class="alert alert-info alert-dismissible fade show border-0 shadow-sm rounded-4" role="alert">
                    <i class="fa fa-info-circle me-2"></i>Item deleted successfully!
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <div class="card border-0 shadow-sm rounded-4 text-center py-5">
                <div class="card-body">
                    <div class="display-1 text-muted mb-3 opacity-25">
                        <i class="fa fa-folder-open"></i>
                    </div>
                    <h5 class="fw-bold text-muted">Your vault is empty</h5>
                    <p class="text-muted small mb-4">Start storing your important files, links, and notes.</p>
                    <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" onclick="openModal('add')">
                        Add First Item
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="vaultList">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-muted small fw-bold border-0 ps-4 py-3" style="width: 60px;">Type</th>
                                <th class="text-muted small fw-bold border-0 py-3">Title</th>
                                <th class="text-muted small fw-bold border-0 py-3">Date Added</th>
                                <th class="text-muted small fw-bold border-0 py-3 text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr class="list-view-card" data-title="<?= strtolower(htmlspecialchars($item['title'])) ?>">
                                    <td class="ps-4 py-3 border-bottom">
                                        <?php if ($item['type'] == 'file'): ?>
                                            <i class="fa fa-file-alt text-success fa-lg"></i>
                                        <?php elseif ($item['type'] == 'link'): ?>
                                            <i class="fa fa-link text-primary fa-lg"></i>
                                        <?php elseif ($item['type'] == 'text'): ?>
                                            <i class="fa fa-align-left text-warning fa-lg"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 border-bottom">
                                        <h6 class="fw-bold mb-0 text-truncate" style="max-width: 300px;" title="<?= htmlspecialchars($item['title']) ?>"><?= htmlspecialchars($item['title']) ?></h6>
                                        <span class="badge bg-light text-dark border rounded-pill text-capitalize mt-1" style="font-size: 0.65rem;"><?= $item['type'] ?></span>
                                    </td>
                                    <td class="py-3 border-bottom text-muted small">
                                        <?= date('M d, Y', strtotime($item['created_at'])) ?>
                                    </td>
                                    <td class="py-3 border-bottom text-end pe-4">
                                        <div class="d-flex align-items-center justify-content-end gap-2">
                                            <?php if ($item['type'] == 'link'): ?>
                                                <a href="<?= htmlspecialchars($item['content']) ?>" target="_blank" class="btn btn-sm btn-light rounded-pill px-3 fw-bold"><i class="fa fa-external-link-alt me-2"></i>Open</a>
                                            <?php elseif ($item['type'] == 'file'): ?>
                                                <a href="<?= htmlspecialchars($item['content']) ?>" download class="btn btn-sm btn-light rounded-pill px-3 fw-bold"><i class="fa fa-download me-2"></i>Download</a>
                                            <?php elseif ($item['type'] == 'text'): ?>
                                                <button type="button" class="btn btn-sm btn-light rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#viewTextModal<?= $item['id'] ?>"><i class="fa fa-eye me-2"></i>View Note</button>
                                            <?php endif; ?>

                                            <div class="dropdown ms-1">
                                                <button class="btn btn-light rounded-circle p-0 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" type="button" data-bs-toggle="dropdown">
                                                    <i class="fa fa-ellipsis-v text-muted"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm rounded-3">
                                                    <li><a class="dropdown-item" href="#" onclick="openModal('edit', <?= $item['id'] ?>)"><i class="fa fa-edit me-2 text-primary"></i> Edit</a></li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li><a class="dropdown-item text-danger" href="vault?delete=<?= $item['id'] ?>" onclick="return confirm('Are you sure you want to delete this item?');"><i class="fa fa-trash me-2"></i> Delete</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modals & Hidden Data -->
            <?php foreach ($items as $item): ?>
                <!-- Store Data for JS Edit -->
                <div id="item-data-<?= $item['id'] ?>" class="d-none" data-title="<?= htmlspecialchars($item['title']) ?>" data-type="<?= $item['type'] ?>" data-content="<?= htmlspecialchars($item['content']) ?>"></div>

                <!-- Text View Modal -->
                <?php if ($item['type'] == 'text'): ?>
                <div class="modal fade" id="viewTextModal<?= $item['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content border-0 shadow-lg rounded-4">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold"><i class="fa fa-align-left text-warning me-2"></i><?= htmlspecialchars($item['title']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4">
                                <!-- Use rich text content -->
                                <div class="p-4 bg-white rounded-4 border note-content" style="max-height: 500px; overflow-y: auto; font-size: 1rem; color: #333; line-height: 1.6;"><?= $item['content'] ?></div>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" onclick="bootstrap.Modal.getInstance(document.getElementById('viewTextModal<?= $item['id'] ?>')).hide(); setTimeout(() => openModal('edit', <?= $item['id'] ?>), 400);"><i class="fa fa-edit me-2"></i>Edit Note</button>
                                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Item Modal -->
<div class="modal fade" id="itemModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom px-4 py-3">
                <h5 class="modal-title fw-bold" id="modalTitle">Add to Vault</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="itemForm" action="vault" method="POST" enctype="multipart/form-data" onsubmit="syncEditor()">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" id="modalAction" value="add">
                    <input type="hidden" name="id" id="modalId" value="">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted small">Title / Description</label>
                        <input type="text" name="title" id="modalInputTitle" class="form-control rounded-3 fw-bold" placeholder="e.g., Server Passwords, Project Logo" style="font-size: 1.1rem;" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold text-muted small">Item Type</label>
                        <div class="d-flex gap-3">
                            <div class="form-check flex-grow-1 border rounded-3 p-2 ps-4">
                                <input class="form-check-input" type="radio" name="type" id="type_text" value="text" checked onchange="toggleInputs()">
                                <label class="form-check-label w-100" for="type_text">
                                    <i class="fa fa-align-left text-warning me-1"></i> Note
                                </label>
                            </div>
                            <div class="form-check flex-grow-1 border rounded-3 p-2 ps-4">
                                <input class="form-check-input" type="radio" name="type" id="type_link" value="link" onchange="toggleInputs()">
                                <label class="form-check-label w-100" for="type_link">
                                    <i class="fa fa-link text-primary me-1"></i> Link
                                </label>
                            </div>
                            <div class="form-check flex-grow-1 border rounded-3 p-2 ps-4">
                                <input class="form-check-input" type="radio" name="type" id="type_file" value="file" onchange="toggleInputs()">
                                <label class="form-check-label w-100" for="type_file">
                                    <i class="fa fa-file-alt text-success me-1"></i> File
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div id="input_text" class="mb-3">
                        <label class="form-label fw-bold text-muted small">Rich Content Note</label>
                        <div class="border rounded-3">
                            <!-- Custom Toolbar -->
                            <div class="editor-toolbar border-bottom px-2 py-2 d-flex flex-wrap gap-1 bg-light rounded-top align-items-center">
                                <div class="btn-group me-1">
                                    <button type="button" class="tb-btn" onclick="fmt('bold')" title="Bold"><i class="fa fa-bold"></i></button>
                                    <button type="button" class="tb-btn" onclick="fmt('italic')" title="Italic"><i class="fa fa-italic"></i></button>
                                    <button type="button" class="tb-btn" onclick="fmt('underline')" title="Underline"><i class="fa fa-underline"></i></button>
                                    <button type="button" class="tb-btn" onclick="fmt('strikethrough')" title="Strikethrough"><i class="fa fa-strikethrough"></i></button>
                                </div>
                                <div class="vr mx-1"></div>
                                <div class="btn-group me-1">
                                    <button type="button" class="tb-btn" onclick="fmtBlock('h3')">H3</button>
                                    <button type="button" class="tb-btn" onclick="fmtBlock('h4')">H4</button>
                                    <button type="button" class="tb-btn" onclick="fmtBlock('p')">P</button>
                                    <button type="button" class="tb-btn" onclick="fmtBlock('blockquote')" title="Quote"><i class="fa fa-quote-left"></i></button>
                                    <button type="button" class="tb-btn" onclick="fmtBlock('pre')" title="Code Block"><i class="fa fa-code"></i></button>
                                </div>
                                <div class="vr mx-1"></div>
                                <div class="btn-group me-1">
                                    <button type="button" class="tb-btn" onclick="fmt('insertUnorderedList')" title="Bullet List"><i class="fa fa-list-ul"></i></button>
                                    <button type="button" class="tb-btn" onclick="fmt('insertOrderedList')" title="Numbered List"><i class="fa fa-list-ol"></i></button>
                                </div>
                                <div class="vr mx-1"></div>
                                <div class="btn-group me-1">
                                    <button type="button" class="tb-btn" onclick="fmt('justifyLeft')"><i class="fa fa-align-left"></i></button>
                                    <button type="button" class="tb-btn" onclick="fmt('justifyCenter')"><i class="fa fa-align-center"></i></button>
                                    <button type="button" class="tb-btn" onclick="fmt('justifyRight')"><i class="fa fa-align-right"></i></button>
                                </div>
                                <div class="vr mx-1"></div>
                                <div class="btn-group me-1">
                                    <button type="button" class="tb-btn" onclick="insertLink()" title="Insert Link"><i class="fa fa-link"></i></button>
                                    <button type="button" class="tb-btn" onclick="fmt('removeFormat')" title="Clear Formatting"><i class="fa fa-eraser"></i></button>
                                </div>
                                <div class="vr mx-1"></div>
                                <div class="d-flex align-items-center gap-1">
                                    <input type="color" class="form-control form-control-color p-0 border-0 bg-transparent" style="width:20px;height:20px;cursor:pointer;" onchange="fmt('foreColor', this.value)" title="Text Color">
                                    <input type="color" class="form-control form-control-color p-0 border-0 bg-transparent" style="width:20px;height:20px;cursor:pointer;" onchange="fmt('hiliteColor', this.value)" title="Highlight Color" value="#ffff00">
                                </div>
                            </div>
                            <!-- Editor Content -->
                            <div id="richEditor" contenteditable="true" style="min-height:200px; max-height:400px; overflow-y:auto; padding:15px; outline:none; font-size:0.95rem; line-height:1.6;"></div>
                            <textarea name="text_content" id="hiddenTextContent" style="display:none;"></textarea>
                        </div>
                    </div>
                    
                    <div id="input_link" class="mb-3" style="display: none;">
                        <label class="form-label fw-bold text-muted small">URL / Link</label>
                        <input type="url" name="link_content" id="modalInputLink" class="form-control rounded-3" placeholder="https://example.com">
                    </div>
                    
                    <div id="input_file" class="mb-3" style="display: none;">
                        <label class="form-label fw-bold text-muted small">Upload File</label>
                        <input type="hidden" name="existing_file_content" id="existingFileContent" value="">
                        <div id="currentFileDisplay" class="mb-2 p-2 bg-light border rounded text-muted small" style="display:none;">
                            <i class="fa fa-file-alt me-2"></i> Current File: <span id="currentFileName"></span>
                        </div>
                        <input type="file" name="file_content" id="modalInputFile" class="form-control rounded-3">
                        <div class="form-text mt-2"><i class="fa fa-info-circle me-1"></i>Upload a new file to replace the existing one.</div>
                    </div>
                </div>
                <div class="modal-footer border-top px-4 py-3 bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" onclick="syncEditor()">Save to Vault</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .hover-lift {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
    }
    .smaller {
        font-size: 0.8rem;
    }
    .form-check-input:checked + .form-check-label {
        font-weight: bold;
    }
    
    /* Editor Styles */
    .tb-btn { background:#fff; border:1px solid #dee2e6; border-radius:4px; width:28px; height:28px; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:0.75rem; color:#444; transition:all 0.15s; }
    .tb-btn:hover { background:#f0f0f0; border-color:#8e44ad; color:#8e44ad; }
    #richEditor p { margin-bottom: 10px; }
    #richEditor h3 { font-size: 1.4rem; font-weight: bold; margin: 15px 0 10px; }
    #richEditor h4 { font-size: 1.2rem; font-weight: bold; margin: 15px 0 10px; }
    #richEditor blockquote { border-left: 3px solid #8e44ad; padding-left: 15px; font-style: italic; color: #666; margin: 10px 0; background: #fafafa; padding: 10px 15px; border-radius: 0 8px 8px 0; }
    #richEditor pre { background: #f4f6f9; padding: 12px; border-radius: 6px; font-family: monospace; font-size: 0.85rem; overflow-x: auto; margin: 10px 0; }
    #richEditor a { color: #8e44ad; text-decoration: underline; cursor: pointer; }
    
    /* Note View Styles */
    .note-content p { margin-bottom: 10px; }
    .note-content h3, .note-content h4 { margin-top: 15px; margin-bottom: 10px; font-weight: bold; }
    .note-content blockquote { border-left: 3px solid #8e44ad; padding: 10px 15px; font-style: italic; color: #666; background: #fafafa; margin: 15px 0; border-radius: 0 8px 8px 0; }
    .note-content pre { background: #f8f9fa; padding: 15px; border-radius: 8px; overflow-x: auto; font-family: monospace; }
</style>

<script>
function fmt(cmd, val = null) { 
    document.getElementById('richEditor').focus(); 
    document.execCommand(cmd, false, val); 
}
function fmtBlock(tag) { 
    document.getElementById('richEditor').focus(); 
    document.execCommand('formatBlock', false, tag); 
}
function insertLink() {
    const url = prompt('Enter URL (e.g., https://example.com):');
    if (url) { 
        document.getElementById('richEditor').focus(); 
        document.execCommand('createLink', false, url); 
    }
}
function syncEditor() {
    document.getElementById('hiddenTextContent').value = document.getElementById('richEditor').innerHTML;
}

function toggleInputs() {
    const isText = document.getElementById('type_text').checked;
    const isLink = document.getElementById('type_link').checked;
    const isFile = document.getElementById('type_file').checked;
    
    document.getElementById('input_text').style.display = isText ? 'block' : 'none';
    document.getElementById('input_link').style.display = isLink ? 'block' : 'none';
    document.getElementById('input_file').style.display = isFile ? 'block' : 'none';
    
    document.getElementById('modalInputLink').required = isLink;
    
    // File requirement depends on if it's new or edit
    const action = document.getElementById('modalAction').value;
    if (action === 'add' && isFile) {
        document.getElementById('modalInputFile').required = true;
    } else {
        document.getElementById('modalInputFile').required = false;
    }
}

function openModal(action, id = 0) {
    document.getElementById('modalAction').value = action;
    document.getElementById('modalId').value = id;
    
    if (action === 'add') {
        document.getElementById('modalTitle').textContent = 'Add to Vault';
        document.getElementById('itemForm').reset();
        document.getElementById('richEditor').innerHTML = '';
        document.getElementById('currentFileDisplay').style.display = 'none';
        document.getElementById('type_text').checked = true;
        
        // Enable radio buttons
        document.getElementById('type_text').disabled = false;
        document.getElementById('type_link').disabled = false;
        document.getElementById('type_file').disabled = false;
    } else {
        document.getElementById('modalTitle').textContent = 'Edit Item';
        
        const dataEl = document.getElementById('item-data-' + id);
        const title = dataEl.getAttribute('data-title');
        const type = dataEl.getAttribute('data-type');
        const content = dataEl.getAttribute('data-content');
        
        document.getElementById('modalInputTitle').value = title;
        document.getElementById('type_' + type).checked = true;
        
        // Disable radio buttons for editing to avoid complexity of type switching
        document.getElementById('type_text').disabled = true;
        document.getElementById('type_link').disabled = true;
        document.getElementById('type_file').disabled = true;
        // The one that is checked shouldn't be fully disabled in appearance, but let's just make it readonly/disabled visually
        document.getElementById('type_' + type).disabled = false;
        
        if (type === 'text') {
            document.getElementById('richEditor').innerHTML = content;
        } else if (type === 'link') {
            document.getElementById('modalInputLink').value = content;
        } else if (type === 'file') {
            document.getElementById('existingFileContent').value = content;
            document.getElementById('currentFileName').textContent = content.split('/').pop();
            document.getElementById('currentFileDisplay').style.display = 'block';
        }
    }
    
    toggleInputs();
    
    const modal = new bootstrap.Modal(document.getElementById('itemModal'));
    modal.show();
}

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    toggleInputs();
    
    // Paste handler for editor to prefer plain text or simple formatting
    document.getElementById('richEditor').addEventListener('paste', function(e) {
        e.preventDefault();
        const text = (e.originalEvent || e).clipboardData.getData('text/plain');
        document.execCommand('insertText', false, text);
    });
});

function filterVault() {
    const query = document.getElementById('vaultSearch').value.toLowerCase();
    const items = document.querySelectorAll('.list-view-card');
    
    items.forEach(item => {
        const title = item.getAttribute('data-title');
        if (title.includes(query)) {
            item.classList.remove('d-none');
        } else {
            item.classList.add('d-none');
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
