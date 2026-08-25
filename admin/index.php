<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_login();
global $GALLERY_CATEGORIES, $VIDEO_FOLDERS;

$csrf        = csrf_token();
$active_tab  = $_GET['tab'] ?? 'gallery';
$flash_ok    = $_GET['ok'] ?? '';
$flash_err   = $_GET['err'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Leads tab data (DB-backed; fails gracefully if DB isn't configured yet)
$leads = [];
$leads_db_error = '';
if ($active_tab === 'leads') {
    try {
        if ($status_filter && in_array($status_filter, ['new','contacted','won','lost'], true)) {
            $stmt = db()->prepare('SELECT * FROM leads WHERE status = :status ORDER BY created_at DESC');
            $stmt->execute(['status' => $status_filter]);
        } else {
            $stmt = db()->query('SELECT * FROM leads ORDER BY created_at DESC');
        }
        $leads = $stmt->fetchAll();
    } catch (Throwable $e) {
        $leads_db_error = $e->getMessage();
    }
}

// Config for the simple CRUD content tables (Phase 2)
$CRUD_TABLES = [
    'services' => [
        'label' => 'Services', 'icon' => 'fa-concierge-bell',
        'fields' => [
            'title'            => ['type'=>'text', 'label'=>'Title', 'required'=>true],
            'icon_class'       => ['type'=>'text', 'label'=>'Icon class', 'placeholder'=>'fas fa-shield-alt', 'help'=>'Font Awesome 6 class'],
            'summary'          => ['type'=>'textarea', 'label'=>'Short summary (home page card)'],
            'description'      => ['type'=>'textarea', 'label'=>'Full description (services page)'],
            'features'         => ['type'=>'features', 'label'=>'Feature bullets — one per line (services page)'],
            'whatsapp_text'    => ['type'=>'text', 'label'=>'WhatsApp enquiry text'],
            'link_href'        => ['type'=>'text', 'label'=>'Home card link URL', 'placeholder'=>'services.html'],
            'link_text'        => ['type'=>'text', 'label'=>'Home card link label', 'placeholder'=>'Learn More'],
            'show_on_home'     => ['type'=>'checkbox', 'label'=>'Show on home page'],
            'show_on_services' => ['type'=>'checkbox', 'label'=>'Show on services page'],
            'sort_order'       => ['type'=>'number', 'label'=>'Sort order'],
            'active'           => ['type'=>'checkbox', 'label'=>'Active'],
        ],
    ],
    'training_courses' => [
        'label' => 'Training Courses', 'icon' => 'fa-graduation-cap',
        'fields' => [
            'category'    => ['type'=>'text', 'label'=>'Category', 'placeholder'=>'Security Grade Courses'],
            'name'        => ['type'=>'text', 'label'=>'Course name', 'required'=>true],
            'description' => ['type'=>'textarea', 'label'=>'Description'],
            'price'       => ['type'=>'number', 'label'=>'Price (R)', 'step'=>'0.01'],
            'sort_order'  => ['type'=>'number', 'label'=>'Sort order'],
            'active'      => ['type'=>'checkbox', 'label'=>'Active'],
        ],
    ],
    'testimonials' => [
        'label' => 'Testimonials', 'icon' => 'fa-quote-right',
        'fields' => [
            'author_name' => ['type'=>'text', 'label'=>'Author name', 'required'=>true],
            'author_role' => ['type'=>'text', 'label'=>'Author role / location'],
            'rating'      => ['type'=>'number', 'label'=>'Star rating (1-5)', 'min'=>1, 'max'=>5],
            'quote_text'  => ['type'=>'textarea', 'label'=>'Quote'],
            'sort_order'  => ['type'=>'number', 'label'=>'Sort order'],
            'active'      => ['type'=>'checkbox', 'label'=>'Active (published)'],
        ],
    ],
    'branches' => [
        'label' => 'Branches', 'icon' => 'fa-map-marker-alt',
        'fields' => [
            'name'           => ['type'=>'text', 'label'=>'Branch name', 'required'=>true],
            'contact_person' => ['type'=>'text', 'label'=>'Contact person'],
            'phone'          => ['type'=>'text', 'label'=>'Phone'],
            'whatsapp'       => ['type'=>'text', 'label'=>'WhatsApp number', 'placeholder'=>'27...'],
            'badge'          => ['type'=>'text', 'label'=>'Badge', 'placeholder'=>'HQ / NEW (optional)'],
            'is_head_office' => ['type'=>'checkbox', 'label'=>'Head office'],
            'sort_order'     => ['type'=>'number', 'label'=>'Sort order'],
        ],
    ],
    'faqs' => [
        'label' => 'Chatbot FAQs', 'icon' => 'fa-comments',
        'fields' => [
            'keywords'    => ['type'=>'text', 'label'=>'Keywords (comma-separated)', 'required'=>true],
            'answer_html' => ['type'=>'textarea', 'label'=>'Answer — basic HTML like &lt;br&gt;, &lt;strong&gt; allowed'],
            'sort_order'  => ['type'=>'number', 'label'=>'Sort order'],
            'active'      => ['type'=>'checkbox', 'label'=>'Active'],
        ],
    ],
];

$crud_rows = [];
$crud_db_error = '';
if (isset($CRUD_TABLES[$active_tab])) {
    try {
        $crud_rows = db()->query("SELECT * FROM `$active_tab` ORDER BY sort_order ASC, id ASC")->fetchAll();
    } catch (Throwable $e) {
        $crud_db_error = $e->getMessage();
    }
}

$settings_rows = [];
$settings_db_error = '';
if ($active_tab === 'settings') {
    try {
        $settings_rows = db()->query('SELECT * FROM site_settings ORDER BY `key` ASC')->fetchAll();
    } catch (Throwable $e) {
        $settings_db_error = $e->getMessage();
    }
}

$blog_posts = [];
$blog_db_error = '';
if ($active_tab === 'blog') {
    try {
        $blog_posts = db()->query('SELECT * FROM blog_posts ORDER BY created_at DESC')->fetchAll();
    } catch (Throwable $e) {
        $blog_db_error = $e->getMessage();
    }
}

$admin_users = [];
$admins_db_error = '';
if ($active_tab === 'admins') {
    try {
        $admin_users = db()->query('SELECT * FROM admin_users ORDER BY created_at ASC')->fetchAll();
    } catch (Throwable $e) {
        $admins_db_error = $e->getMessage();
    }
}

// Renders one form field (label + input) for the CRUD tabs below.
function crud_field(string $col, array $field, $value): string {
    $uid = $col . '_' . substr(md5($col . microtime()), 0, 6);
    $label = htmlspecialchars($field['label'] ?? $col);
    $required = !empty($field['required']) ? 'required' : '';
    $help = !empty($field['help']) ? ' <small style="color:#94a3b8;font-weight:400;">(' . htmlspecialchars($field['help']) . ')</small>' : '';
    $wide = in_array($field['type'], ['textarea', 'features'], true) ? ' full' : '';

    switch ($field['type']) {
        case 'textarea':
            return "<div class=\"form-group$wide\"><label>$label$help</label><textarea name=\"" . htmlspecialchars($col) . '">' . htmlspecialchars((string)$value) . '</textarea></div>';
        case 'features':
            $text = '';
            if ($value) {
                $arr = json_decode((string)$value, true);
                if (is_array($arr)) $text = implode("\n", $arr);
            }
            return "<div class=\"form-group$wide\"><label>$label$help</label><textarea name=\"" . htmlspecialchars($col) . '" rows="4">' . htmlspecialchars($text) . '</textarea></div>';
        case 'checkbox':
            $checked = !empty($value) ? 'checked' : '';
            return '<div class="form-group" style="display:flex;align-items:center;gap:8px;padding-top:1.5rem;"><input type="checkbox" name="' . htmlspecialchars($col) . "\" value=\"1\" $checked id=\"$uid\" style=\"width:auto;\"><label for=\"$uid\" style=\"margin:0;\">$label</label></div>";
        case 'number':
            $step = !empty($field['step']) ? 'step="' . htmlspecialchars($field['step']) . '"' : '';
            $min  = isset($field['min']) ? 'min="' . (int)$field['min'] . '"' : '';
            $max  = isset($field['max']) ? 'max="' . (int)$field['max'] . '"' : '';
            return "<div class=\"form-group\"><label>$label$help</label><input type=\"number\" name=\"" . htmlspecialchars($col) . '" value="' . htmlspecialchars((string)$value) . "\" $step $min $max $required></div>";
        default:
            $placeholder = !empty($field['placeholder']) ? 'placeholder="' . htmlspecialchars($field['placeholder']) . '"' : '';
            return "<div class=\"form-group\"><label>$label$help</label><input type=\"text\" name=\"" . htmlspecialchars($col) . '" value="' . htmlspecialchars((string)$value) . "\" $placeholder $required></div>";
    }
}

// Helper: scan folder for images
function get_images(string $folder_rel): array {
    $dir = SITE_ROOT . $folder_rel;
    if (!is_dir($dir)) return [];
    $files = [];
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*.{jpg,jpeg,png,webp}', GLOB_BRACE) as $f) {
        $files[] = basename($f);
    }
    return $files;
}

// Helper: scan folder for videos (exclude meta.json)
function get_videos(string $folder_rel): array {
    $dir = SITE_ROOT . $folder_rel;
    if (!is_dir($dir)) return [];
    $files = [];
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*.{mp4,mov,webm}', GLOB_BRACE) as $f) {
        $files[] = basename($f);
    }
    return $files;
}

// Category slug helper
function cat_slug(string $label): string {
    return preg_replace('/[^a-z0-9]+/', '-', strtolower($label));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CMS Dashboard — Makgwati Security</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#f1f5f9;color:#1e293b;min-height:100vh;}
/* Topbar */
.topbar{background:linear-gradient(135deg,#1a365d,#2c5282);color:#fff;padding:0 1.5rem;display:flex;align-items:center;justify-content:space-between;height:58px;position:sticky;top:0;z-index:100;box-shadow:0 2px 10px rgba(0,0,0,.2);}
.topbar-logo{display:flex;align-items:center;gap:10px;font-weight:800;font-size:1rem;letter-spacing:1px;}
.topbar-logo i{color:#FF6B35;font-size:1.3rem;}
.topbar-actions{display:flex;align-items:center;gap:1rem;}
.topbar-user{font-size:.82rem;color:rgba(255,255,255,.75);}
.btn-logout{background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.25);padding:.4rem .9rem;border-radius:6px;font-size:.8rem;font-weight:600;cursor:pointer;text-decoration:none;transition:all .2s;}
.btn-logout:hover{background:rgba(255,255,255,.22);}
.btn-site{background:linear-gradient(135deg,#FF6B35,#F7931E);color:#fff;border:none;padding:.4rem .9rem;border-radius:6px;font-size:.8rem;font-weight:600;cursor:pointer;text-decoration:none;transition:all .2s;}
.btn-site:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(255,107,53,.3);}
/* Layout */
.layout{display:flex;min-height:calc(100vh - 58px);}
/* Sidebar */
.sidebar{width:220px;background:#fff;border-right:1px solid #e2e8f0;padding:1.2rem 0;flex-shrink:0;}
.sidebar-title{font-size:.7rem;font-weight:700;color:#94a3b8;letter-spacing:2px;text-transform:uppercase;padding:.6rem 1.3rem;margin-top:.5rem;}
.nav-item{display:flex;align-items:center;gap:10px;padding:.7rem 1.3rem;font-size:.88rem;font-weight:500;color:#475569;text-decoration:none;transition:all .2s;cursor:pointer;border:none;background:none;width:100%;text-align:left;}
.nav-item:hover{background:#f8fafc;color:#1a365d;}
.nav-item.active{background:rgba(255,107,53,.07);color:#FF6B35;border-right:3px solid #FF6B35;}
.nav-item i{width:18px;text-align:center;font-size:.9rem;}
/* Main */
.main{flex:1;padding:1.8rem;overflow-y:auto;}
.page-title{font-size:1.5rem;font-weight:800;color:#1a365d;margin-bottom:.3rem;}
.page-sub{color:#64748b;font-size:.9rem;margin-bottom:1.8rem;}
/* Tabs */
.tabs{display:flex;gap:.5rem;margin-bottom:1.8rem;flex-wrap:wrap;}
.tab{padding:.55rem 1.2rem;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer;border:2px solid #e2e8f0;background:#fff;color:#475569;transition:all .2s;text-decoration:none;}
.tab:hover{border-color:#FF6B35;color:#FF6B35;}
.tab.active{background:linear-gradient(135deg,#FF6B35,#F7931E);color:#fff;border-color:transparent;}
/* Flash messages */
.flash{padding:.85rem 1.1rem;border-radius:10px;margin-bottom:1.4rem;font-size:.88rem;display:flex;align-items:center;gap:.6rem;}
.flash.ok{background:#f0fff4;border:1px solid #86efac;color:#15803d;}
.flash.err{background:#fff5f5;border:1px solid #fca5a5;color:#b91c1c;}
/* Cards */
.section-card{background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:1.5rem;margin-bottom:1.6rem;box-shadow:0 2px 8px rgba(0,0,0,.04);}
.section-card h3{font-size:1rem;font-weight:700;color:#1a365d;margin-bottom:1.1rem;display:flex;align-items:center;gap:8px;}
.section-card h3 i{color:#FF6B35;}
.section-card h3 .count{background:#f1f5f9;color:#64748b;font-size:.72rem;padding:.2rem .55rem;border-radius:20px;font-weight:600;}
/* Image grid */
.img-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:.8rem;margin-bottom:1.2rem;}
.img-item{position:relative;border-radius:8px;overflow:hidden;aspect-ratio:1;background:#f1f5f9;}
.img-item img{width:100%;height:100%;object-fit:cover;display:block;}
.img-item .del-btn{position:absolute;top:4px;right:4px;background:rgba(185,28,28,.85);color:#fff;border:none;border-radius:5px;width:26px;height:26px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.72rem;transition:all .2s;}
.img-item .del-btn:hover{background:#b91c1c;}
.img-item .img-name{position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.6);color:#fff;font-size:.62rem;padding:.25rem .4rem;text-overflow:ellipsis;overflow:hidden;white-space:nowrap;}
.empty-state{color:#94a3b8;font-size:.85rem;text-align:center;padding:1.5rem;background:#f8fafc;border-radius:8px;margin-bottom:1rem;}
/* Upload form */
.upload-row{display:flex;align-items:flex-start;gap:.8rem;flex-wrap:wrap;}
.upload-input{flex:1;min-width:200px;padding:9px 13px;border:2px solid #e2e8f0;border-radius:8px;font-size:.85rem;font-family:'Inter',sans-serif;outline:none;transition:border-color .2s;}
.upload-input:focus{border-color:#FF6B35;}
.btn-upload{background:linear-gradient(135deg,#1a365d,#2c5282);color:#fff;border:none;padding:9px 18px;border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;white-space:nowrap;transition:all .2s;}
.btn-upload:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(26,54,93,.25);}
/* Video list */
.video-list{display:flex;flex-direction:column;gap:.8rem;margin-bottom:1.2rem;}
.video-item{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:1rem 1.2rem;display:flex;align-items:flex-start;gap:1rem;}
.video-thumb{width:100px;flex-shrink:0;border-radius:6px;overflow:hidden;background:#000;}
.video-thumb video{width:100%;height:65px;object-fit:cover;display:block;}
.video-info{flex:1;}
.video-info strong{display:block;color:#1a365d;font-size:.9rem;margin-bottom:.25rem;}
.video-info .video-meta{font-size:.78rem;color:#64748b;display:flex;gap:.7rem;flex-wrap:wrap;margin-bottom:.4rem;}
.video-info p{font-size:.82rem;color:#64748b;line-height:1.5;}
.video-actions{display:flex;gap:.5rem;flex-shrink:0;}
.btn-del{background:#fff5f5;color:#b91c1c;border:1px solid #fca5a5;padding:.4rem .8rem;border-radius:6px;font-size:.78rem;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;transition:all .2s;}
.btn-del:hover{background:#fee2e2;}
/* Video upload form */
.video-upload-form{background:#f8fafc;border:2px dashed #e2e8f0;border-radius:10px;padding:1.4rem;margin-top:.5rem;}
.video-upload-form h4{font-size:.88rem;font-weight:700;color:#1a365d;margin-bottom:1rem;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:.8rem;}
.form-grid .full{grid-column:1/-1;}
.form-group label{display:block;font-size:.78rem;font-weight:600;color:#1a365d;margin-bottom:.3rem;}
.form-group input,.form-group textarea,.form-group select{width:100%;padding:9px 12px;border:2px solid #e2e8f0;border-radius:8px;font-size:.85rem;font-family:'Inter',sans-serif;outline:none;transition:border-color .2s;background:#fff;}
.form-group input:focus,.form-group textarea:focus,.form-group select:focus{border-color:#FF6B35;}
.form-group textarea{resize:vertical;min-height:70px;}
/* Change password section */
.pw-form{max-width:440px;}
/* Leads table */
.leads-toolbar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.8rem;margin-bottom:1.2rem;}
.leads-filters{display:flex;gap:.4rem;flex-wrap:wrap;}
.leads-filters a{padding:.4rem .9rem;border-radius:20px;font-size:.78rem;font-weight:600;text-decoration:none;color:#475569;background:#f1f5f9;border:1px solid #e2e8f0;}
.leads-filters a.active{background:linear-gradient(135deg,#FF6B35,#F7931E);color:#fff;border-color:transparent;}
.btn-export{background:#1a365d;color:#fff;border:none;padding:.5rem 1rem;border-radius:8px;font-size:.8rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
.leads-table{width:100%;border-collapse:collapse;font-size:.84rem;}
.leads-table th{text-align:left;padding:.6rem .7rem;background:#f8fafc;color:#64748b;font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid #e2e8f0;}
.leads-table td{padding:.7rem;border-bottom:1px solid #f1f5f9;vertical-align:top;color:#334155;}
.leads-table tr:hover td{background:#f8fafc;}
.lead-status{padding:.25rem .6rem;border-radius:20px;font-size:.72rem;font-weight:700;text-transform:uppercase;border:none;cursor:pointer;}
.lead-status.new{background:#dbeafe;color:#1d4ed8;}
.lead-status.contacted{background:#fef3c7;color:#b45309;}
.lead-status.won{background:#dcfce7;color:#15803d;}
.lead-status.lost{background:#fee2e2;color:#b91c1c;}
.lead-msg{max-width:220px;color:#64748b;font-size:.8rem;}
.db-setup-notice{background:#fffbeb;border:1px solid #fcd34d;color:#92400e;border-radius:10px;padding:1.2rem;font-size:.85rem;line-height:1.6;}
.db-setup-notice code{background:#fff;padding:2px 6px;border-radius:4px;}
/* Generic CRUD tabs */
.crud-row{border:1px solid #e2e8f0;border-radius:10px;margin-bottom:.8rem;overflow:hidden;}
.crud-row summary{padding:.85rem 1.1rem;cursor:pointer;font-weight:600;color:#1a365d;font-size:.9rem;background:#f8fafc;display:flex;align-items:center;justify-content:space-between;list-style:none;}
.crud-row summary::-webkit-details-marker{display:none;}
.crud-row summary .crud-row-badge{font-size:.7rem;font-weight:700;padding:.15rem .55rem;border-radius:20px;margin-left:.6rem;}
.crud-row summary .crud-row-badge.inactive{background:#fee2e2;color:#b91c1c;}
.crud-row summary .crud-row-badge.active{background:#dcfce7;color:#15803d;}
.crud-row summary i.fa-chevron-down{transition:transform .2s;color:#94a3b8;}
.crud-row[open] summary i.fa-chevron-down{transform:rotate(180deg);}
.crud-row-body{padding:1.2rem;border-top:1px solid #e2e8f0;}
.crud-row-actions{display:flex;justify-content:space-between;align-items:center;margin-top:1rem;padding-top:1rem;border-top:1px dashed #e2e8f0;}
.add-new-card{border:2px dashed #e2e8f0;border-radius:10px;padding:1.4rem;}
.add-new-card h4{font-size:.9rem;font-weight:700;color:#1a365d;margin-bottom:1rem;}
.settings-row{display:flex;gap:.8rem;align-items:flex-end;margin-bottom:.8rem;padding-bottom:.8rem;border-bottom:1px solid #f1f5f9;}
.settings-row .key-label{width:220px;flex-shrink:0;font-size:.82rem;font-weight:600;color:#1a365d;padding-bottom:9px;word-break:break-word;}
.settings-row textarea{min-height:42px;}
/* Storage meter */
.storage-meter{margin:1.2rem .9rem .4rem;padding:.85rem .9rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;}
.storage-meter .sm-head{display:flex;justify-content:space-between;align-items:baseline;font-size:.72rem;font-weight:700;color:#1a365d;text-transform:uppercase;letter-spacing:.4px;margin-bottom:.5rem;}
.storage-meter .sm-head span{font-weight:600;text-transform:none;letter-spacing:0;color:#64748b;}
.storage-meter .sm-bar{height:7px;background:#e2e8f0;border-radius:20px;overflow:hidden;}
.storage-meter .sm-fill{height:100%;border-radius:20px;background:linear-gradient(90deg,#FF6B35,#F7931E);transition:width .3s;}
.storage-meter.warn .sm-fill{background:linear-gradient(90deg,#f59e0b,#d97706);}
.storage-meter.full .sm-fill{background:linear-gradient(90deg,#ef4444,#b91c1c);}
.storage-meter .sm-note{font-size:.71rem;color:#94a3b8;margin-top:.45rem;line-height:1.45;}
.storage-meter.warn .sm-note{color:#b45309;font-weight:600;}
.storage-meter.full .sm-note{color:#b91c1c;font-weight:600;}
/* Upload limits banner */
.limits-note{display:flex;gap:.7rem;align-items:flex-start;background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;border-radius:10px;padding:.85rem 1rem;font-size:.8rem;line-height:1.55;margin-bottom:1.4rem;}
.limits-note i{margin-top:.15rem;flex-shrink:0;}
.limits-note strong{font-weight:700;}
.limits-note.warn{background:#fffbeb;border-color:#fde68a;color:#92400e;}
/* Media health check */
.health-card{border:1px solid #e2e8f0;border-radius:10px;padding:1.1rem 1.2rem;margin-bottom:1.4rem;background:#fff;}
.health-card.attention{border-color:#fecaca;background:#fef2f2;}
.health-card.pending{border-color:#fed7aa;background:#fff7ed;}
.health-card h4{font-size:.88rem;font-weight:700;color:#1a365d;margin-bottom:.5rem;display:flex;align-items:center;gap:.5rem;}
.health-card p{font-size:.81rem;color:#64748b;line-height:1.6;margin-bottom:.8rem;}
.health-card.attention p{color:#991b1b;}
.health-card.pending p{color:#9a3412;}
.health-card ul{margin:.2rem 0 .9rem 1.1rem;font-size:.78rem;color:#475569;line-height:1.7;}
.health-card code{background:rgba(0,0,0,.05);padding:.1rem .35rem;border-radius:4px;font-size:.95em;}
.health-actions{display:flex;gap:.6rem;flex-wrap:wrap;}
.btn-scan{background:#1a365d;color:#fff;border:none;padding:.55rem 1.1rem;border-radius:8px;font-size:.82rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:.45rem;}
.btn-scan:hover{background:#2c5282;}
.btn-scan.ghost{background:#fff;color:#b91c1c;border:1px solid #fecaca;}
.btn-scan.ghost:hover{background:#fef2f2;}
/* Responsive */
@media(max-width:768px){
  .layout{flex-direction:column}
  .sidebar{width:100%;border-right:none;border-bottom:1px solid #e2e8f0;padding:.5rem 0;display:flex;overflow-x:auto;gap:0}
  .sidebar-title{display:none}
  .nav-item{padding:.6rem 1rem;white-space:nowrap}
  .main{padding:1.2rem}
  .form-grid{grid-template-columns:1fr}
}
</style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <div class="topbar-logo">
        <i class="fas fa-shield-halved"></i> Makgwati CMS
    </div>
    <div class="topbar-actions">
        <span class="topbar-user"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['mgw_user'] ?? 'Admin') ?></span>
        <a href="../index.php" target="_blank" class="btn-site"><i class="fas fa-external-link-alt"></i> View Site</a>
        <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="layout">
<!-- Sidebar -->
<nav class="sidebar">
    <div class="sidebar-title">Enquiries</div>
    <a href="?tab=leads" class="nav-item <?= $active_tab==='leads'?'active':'' ?>"><i class="fas fa-inbox"></i> Leads Inbox</a>
    <div class="sidebar-title">Content</div>
    <a href="?tab=services" class="nav-item <?= $active_tab==='services'?'active':'' ?>"><i class="fas fa-concierge-bell"></i> Services</a>
    <a href="?tab=training_courses" class="nav-item <?= $active_tab==='training_courses'?'active':'' ?>"><i class="fas fa-graduation-cap"></i> Training Courses</a>
    <a href="?tab=testimonials" class="nav-item <?= $active_tab==='testimonials'?'active':'' ?>"><i class="fas fa-quote-right"></i> Testimonials</a>
    <a href="?tab=branches" class="nav-item <?= $active_tab==='branches'?'active':'' ?>"><i class="fas fa-map-marker-alt"></i> Branches</a>
    <a href="?tab=faqs" class="nav-item <?= $active_tab==='faqs'?'active':'' ?>"><i class="fas fa-comments"></i> Chatbot FAQs</a>
    <a href="?tab=settings" class="nav-item <?= $active_tab==='settings'?'active':'' ?>"><i class="fas fa-sliders-h"></i> Site Settings</a>
    <a href="?tab=blog" class="nav-item <?= $active_tab==='blog'?'active':'' ?>"><i class="fas fa-newspaper"></i> Blog / News</a>
    <div class="sidebar-title">Manage</div>
    <a href="?tab=gallery" class="nav-item <?= $active_tab==='gallery'?'active':'' ?>"><i class="fas fa-images"></i> Gallery Images</a>
    <a href="?tab=videos" class="nav-item <?= $active_tab==='videos'?'active':'' ?>"><i class="fas fa-film"></i> Project Videos</a>
    <a href="?tab=logo" class="nav-item <?= $active_tab==='logo'?'active':'' ?>"><i class="fas fa-image"></i> Site Logo</a>
    <div class="sidebar-title">Account</div>
    <a href="?tab=password" class="nav-item <?= $active_tab==='password'?'active':'' ?>"><i class="fas fa-key"></i> Change Password</a>
    <a href="?tab=admins" class="nav-item <?= $active_tab==='admins'?'active':'' ?>"><i class="fas fa-user-shield"></i> Admin Users</a>

    <?php
    // Storage meter — so the client can see the account filling up long before
    // an upload starts failing.
    $used_pct  = storage_percent();
    $sm_class  = $used_pct >= 90 ? 'full' : ($used_pct >= 75 ? 'warn' : '');
    ?>
    <div class="storage-meter <?= $sm_class ?>">
        <div class="sm-head">Storage <span><?= round($used_pct) ?>%</span></div>
        <div class="sm-bar"><div class="sm-fill" style="width:<?= max(2, round($used_pct)) ?>%"></div></div>
        <div class="sm-note">
            <?= htmlspecialchars(format_bytes(storage_used())) ?> of
            <?= htmlspecialchars(format_bytes(STORAGE_QUOTA)) ?> used
            <?php if ($used_pct >= 90): ?>
                — almost full. Delete old photos or videos to free space.
            <?php elseif ($used_pct >= 75): ?>
                — running low. Consider clearing old media.
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Main content -->
<main class="main">
    <div class="page-title">
        <?php
        $titles = [
            'leads'=>'Leads Inbox','services'=>'Services','training_courses'=>'Training Courses',
            'testimonials'=>'Testimonials','branches'=>'Branches','faqs'=>'Chatbot FAQs','settings'=>'Site Settings',
            'blog'=>'Blog / News',
            'gallery'=>'Gallery Images','videos'=>'Project Videos','logo'=>'Site Logo','password'=>'Change Password',
            'admins'=>'Admin Users',
        ];
        echo htmlspecialchars($titles[$active_tab] ?? 'Dashboard');
        ?>
    </div>
    <div class="page-sub">
        <?php if ($active_tab==='leads'): ?>Every quote request and enrollment enquiry submitted on the website, in one place.
        <?php elseif ($active_tab==='services'): ?>Edit the service cards shown on the home page and the full Services page.
        <?php elseif ($active_tab==='training_courses'): ?>Edit course names, descriptions and prices — changes update the training page, dropdown, footer, and chatbot automatically.
        <?php elseif ($active_tab==='testimonials'): ?>Add, edit, or retire client reviews shown on the home page.
        <?php elseif ($active_tab==='branches'): ?>Manage branch locations shown on the Contact page, site footer, and chatbot.
        <?php elseif ($active_tab==='faqs'): ?>Teach the website chatbot new answers without touching any code.
        <?php elseif ($active_tab==='settings'): ?>Global values used across the site — phone numbers, WhatsApp number, registration numbers, hero text.
        <?php elseif ($active_tab==='blog'): ?>Write news, safety tips, or case studies to keep the site fresh. Draft first, publish when ready.
        <?php elseif ($active_tab==='admins'): ?>Manage who can log in to this CMS.
        <?php elseif ($active_tab==='gallery'): ?>Upload and manage photos shown in each gallery category on the VIP Protection page.
        <?php elseif ($active_tab==='videos'): ?>Upload and manage project videos with titles, dates, and descriptions.
        <?php elseif ($active_tab==='logo'): ?>Replace the website logo and hero image.
        <?php else: ?>Update your admin password.
        <?php endif; ?>
    </div>

    <?php if ($flash_ok): ?>
        <div class="flash ok"><i class="fas fa-check-circle"></i> <?= htmlspecialchars(urldecode($flash_ok)) ?></div>
    <?php endif; ?>
    <?php if ($flash_err): ?>
        <div class="flash err"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars(urldecode($flash_err)) ?></div>
    <?php endif; ?>

    <?php
    // Upload rules, stated in the actual numbers this server will accept rather
    // than the numbers in config.php — on shared hosting the two rarely match.
    if (in_array($active_tab, ['gallery', 'videos', 'logo', 'blog'], true)):
        $img_limit  = effective_limit('image');
        $vid_limit  = effective_limit('video');
        $img_capped = server_limits_are_binding('image');
        $vid_capped = server_limits_are_binding('video');
    ?>
    <div class="limits-note <?= ($img_capped || $vid_capped) ? 'warn' : '' ?>">
        <i class="fas fa-circle-info"></i>
        <div>
            <strong>Upload limits:</strong>
            photos up to <strong><?= htmlspecialchars(format_bytes($img_limit)) ?></strong><?php if ($active_tab === 'videos'): ?>,
            videos up to <strong><?= htmlspecialchars(format_bytes($vid_limit)) ?></strong><?php endif; ?>,
            maximum <strong><?= MAX_FILES_PER_FOLDER ?></strong> files per category.
            <?php if (image_processing_available()): ?>
                Photos are automatically resized and optimised for the web, so upload straight from your phone — no need to shrink them first.
            <?php endif; ?>
            <?php if ($img_capped || $vid_capped): ?>
                <br><i class="fas fa-triangle-exclamation"></i>
                Your hosting plan caps uploads at <strong><?= htmlspecialchars(format_bytes(php_upload_ceiling())) ?></strong> per file, which is lower than this site's own setting. Ask your host to raise <code>upload_max_filesize</code> and <code>post_max_size</code> if you need more.
            <?php endif; ?>
            <?php if ($active_tab === 'videos'): ?>
                <br>Large videos are slow to load for visitors on mobile data. Compress before uploading where you can — most phones offer this when sharing a video.
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // Media health check — compares the files actually on this server against
    // the gallery entries in the database. The usual reason they drift apart is
    // a code deploy that did not carry the media folders with it.
    if (in_array($active_tab, ['gallery', 'videos'], true)):
        $scan       = scan_unregistered_media();
        $n_new      = count($scan['new']);
        $n_missing  = count($scan['missing']);
        $card_class = $n_missing ? 'attention' : ($n_new ? 'pending' : '');
    ?>
    <div class="health-card <?= $card_class ?>">
        <h4>
            <i class="fas fa-<?= $n_missing ? 'triangle-exclamation' : ($n_new ? 'circle-arrow-up' : 'circle-check') ?>"></i>
            Media health check
        </h4>

        <?php if ($scan['error'] !== ''): ?>
            <p>Could not check: <?= htmlspecialchars($scan['error']) ?></p>

        <?php elseif ($n_missing): ?>
            <p>
                <strong><?= $n_missing ?></strong> gallery entr<?= $n_missing === 1 ? 'y refers' : 'ies refer' ?>
                to files that are <strong>not on this server</strong>. They show as broken images or dead video players on the live site.
                After a deploy this almost always means the media folders were not uploaded.
            </p>
            <ul>
                <?php foreach (array_slice($scan['missing'], 0, 8) as $m): ?>
                    <li><code><?= htmlspecialchars($m['file_path']) ?></code></li>
                <?php endforeach; ?>
                <?php if ($n_missing > 8): ?>
                    <li>…and <?= $n_missing - 8 ?> more</li>
                <?php endif; ?>
            </ul>
            <p>Upload the missing files by FTP and re-check. Only remove the entries if the files are gone for good.</p>

        <?php elseif ($n_new): ?>
            <p>
                <strong><?= $n_new ?></strong> file<?= $n_new === 1 ? '' : 's' ?> on the server
                <?= $n_new === 1 ? 'is' : 'are' ?> not published yet — <?= $n_new === 1 ? 'it' : 'they' ?>
                won't appear on the website until imported. Photos are compressed automatically during import.
            </p>
            <ul>
                <?php foreach (array_slice($scan['new'], 0, 8) as $f): ?>
                    <li><code><?= htmlspecialchars($f['path']) ?></code> — <?= htmlspecialchars(format_bytes($f['size'])) ?></li>
                <?php endforeach; ?>
                <?php if ($n_new > 8): ?>
                    <li>…and <?= $n_new - 8 ?> more</li>
                <?php endif; ?>
            </ul>

        <?php else: ?>
            <p>Every file on this server is published, and every gallery entry has a matching file. Nothing to fix.</p>
        <?php endif; ?>

        <div class="health-actions">
            <form method="POST" action="media-scan.php">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="import">
                <input type="hidden" name="redirect" value="?tab=<?= htmlspecialchars($active_tab) ?>">
                <button type="submit" class="btn-scan">
                    <i class="fas fa-rotate"></i>
                    <?= $n_new ? 'Import ' . $n_new . ' file' . ($n_new === 1 ? '' : 's') : 'Re-scan server' ?>
                </button>
            </form>
            <?php if ($n_missing): ?>
            <form method="POST" action="media-scan.php" onsubmit="return confirm('Remove <?= $n_missing ?> gallery entr<?= $n_missing === 1 ? 'y' : 'ies' ?> whose file is missing? The files themselves are already gone — this only clears the broken entries.')">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <input type="hidden" name="action" value="prune">
                <input type="hidden" name="redirect" value="?tab=<?= htmlspecialchars($active_tab) ?>">
                <button type="submit" class="btn-scan ghost">
                    <i class="fas fa-broom"></i> Remove <?= $n_missing ?> broken entr<?= $n_missing === 1 ? 'y' : 'ies' ?>
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ======= LEADS TAB ======= -->
    <?php if ($active_tab === 'leads'): ?>
        <?php if ($leads_db_error): ?>
            <div class="db-setup-notice">
                <i class="fas fa-database"></i> <strong>Database not connected yet.</strong><br>
                Once the MySQL database has been created, set <code>DB_HOST</code>, <code>DB_NAME</code>, <code>DB_USER</code>, <code>DB_PASS</code> in <code>admin/config.php</code> and import <code>admin/migrations/schema.sql</code>. Leads submitted on the site are not lost in the meantime — the WhatsApp copy still goes through as usual.
            </div>
        <?php else: ?>
            <div class="leads-toolbar">
                <div class="leads-filters">
                    <a href="?tab=leads" class="<?= $status_filter===''?'active':'' ?>">All</a>
                    <a href="?tab=leads&status=new" class="<?= $status_filter==='new'?'active':'' ?>">New</a>
                    <a href="?tab=leads&status=contacted" class="<?= $status_filter==='contacted'?'active':'' ?>">Contacted</a>
                    <a href="?tab=leads&status=won" class="<?= $status_filter==='won'?'active':'' ?>">Won</a>
                    <a href="?tab=leads&status=lost" class="<?= $status_filter==='lost'?'active':'' ?>">Lost</a>
                </div>
                <a href="leads-export.php" class="btn-export"><i class="fas fa-file-csv"></i> Export CSV</a>
            </div>
            <div class="section-card">
                <?php if (!$leads): ?>
                    <div class="empty-state"><i class="fas fa-inbox"></i> No leads yet<?= $status_filter ? ' with status "' . htmlspecialchars($status_filter) . '"' : '' ?>.</div>
                <?php else: ?>
                <div style="overflow-x:auto;">
                <table class="leads-table">
                    <thead><tr>
                        <th>Date</th><th>Source</th><th>Name</th><th>Phone</th><th>Email</th><th>Interest</th><th>Message</th><th>Status</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d M Y, H:i', strtotime($lead['created_at']))) ?></td>
                            <td><?= htmlspecialchars(ucfirst($lead['source_page'])) ?></td>
                            <td><strong><?= htmlspecialchars($lead['name']) ?></strong><?= $lead['location_text'] ? '<br><span style="color:#94a3b8;font-size:.75rem;">' . htmlspecialchars($lead['location_text']) . '</span>' : '' ?></td>
                            <td><a href="tel:<?= htmlspecialchars($lead['phone']) ?>"><?= htmlspecialchars($lead['phone']) ?></a></td>
                            <td><?= $lead['email'] ? htmlspecialchars($lead['email']) : '<span style="color:#cbd5e1;">—</span>' ?></td>
                            <td><?= $lead['service_interest'] ? htmlspecialchars($lead['service_interest']) : '<span style="color:#cbd5e1;">—</span>' ?></td>
                            <td class="lead-msg"><?= $lead['message'] ? htmlspecialchars($lead['message']) : '<span style="color:#cbd5e1;">—</span>' ?></td>
                            <td>
                                <form method="POST" action="leads-update.php" style="display:inline;">
                                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                    <input type="hidden" name="id" value="<?= (int)$lead['id'] ?>">
                                    <input type="hidden" name="redirect" value="?tab=leads<?= $status_filter ? '&status=' . urlencode($status_filter) : '' ?>">
                                    <select name="status" onchange="this.form.submit()" class="lead-status <?= htmlspecialchars($lead['status']) ?>">
                                        <?php foreach (['new','contacted','won','lost'] as $s): ?>
                                            <option value="<?= $s ?>" <?= $lead['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <!-- ======= GENERIC CRUD TABS (services / training_courses / testimonials / branches / faqs) ======= -->
    <?php elseif (isset($CRUD_TABLES[$active_tab])): ?>
        <?php $ct = $CRUD_TABLES[$active_tab]; ?>
        <?php if ($crud_db_error): ?>
            <div class="db-setup-notice">
                <i class="fas fa-database"></i> <strong>Database not connected yet.</strong><br>
                Set your MySQL credentials in <code>admin/config.php</code> and import <code>admin/migrations/schema.sql</code> to manage <?= htmlspecialchars(strtolower($ct['label'])) ?> here.
            </div>
        <?php else: ?>
            <div class="section-card">
                <h3><i class="fas <?= htmlspecialchars($ct['icon']) ?>"></i> Existing <?= htmlspecialchars($ct['label']) ?> <span class="count"><?= count($crud_rows) ?></span></h3>
                <?php if (!$crud_rows): ?>
                    <div class="empty-state"><i class="fas fa-inbox"></i> Nothing here yet. Add the first one below.</div>
                <?php endif; ?>
                <?php foreach ($crud_rows as $row): ?>
                    <?php
                        $isActive = array_key_exists('active', $row) ? (bool)$row['active'] : true;
                        $titleCol = $row['title'] ?? $row['name'] ?? $row['author_name'] ?? $row['keywords'] ?? ('#' . $row['id']);
                    ?>
                    <details class="crud-row">
                        <summary>
                            <span><?= htmlspecialchars((string)$titleCol) ?><?php if (array_key_exists('active', $row)): ?><span class="crud-row-badge <?= $isActive ? 'active' : 'inactive' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span><?php endif; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </summary>
                        <div class="crud-row-body">
                            <form method="POST" action="save.php">
                                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="table" value="<?= htmlspecialchars($active_tab) ?>">
                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                <input type="hidden" name="redirect" value="?tab=<?= htmlspecialchars($active_tab) ?>">
                                <div class="form-grid">
                                    <?php foreach ($ct['fields'] as $col => $field): ?>
                                        <?= crud_field($col, $field, $row[$col] ?? '') ?>
                                    <?php endforeach; ?>
                                </div>
                                <div class="crud-row-actions">
                                    <button type="submit" class="btn-upload"><i class="fas fa-save"></i> Save Changes</button>
                                </div>
                            </form>
                            <form method="POST" action="save.php" onsubmit="return confirm('Delete this entry? This cannot be undone.')" style="margin-top:.5rem;">
                                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="table" value="<?= htmlspecialchars($active_tab) ?>">
                                <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="redirect" value="?tab=<?= htmlspecialchars($active_tab) ?>">
                                <button type="submit" class="btn-del"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>

            <div class="add-new-card">
                <h4><i class="fas fa-plus-circle" style="color:#FF6B35"></i> Add New <?= htmlspecialchars(rtrim($ct['label'], 's')) ?></h4>
                <form method="POST" action="save.php">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="table" value="<?= htmlspecialchars($active_tab) ?>">
                    <input type="hidden" name="redirect" value="?tab=<?= htmlspecialchars($active_tab) ?>">
                    <div class="form-grid">
                        <?php foreach ($ct['fields'] as $col => $field): ?>
                            <?= crud_field($col, $field, '') ?>
                        <?php endforeach; ?>
                    </div>
                    <div style="margin-top:1rem;">
                        <button type="submit" class="btn-upload"><i class="fas fa-plus"></i> Add <?= htmlspecialchars(rtrim($ct['label'], 's')) ?></button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

    <!-- ======= SETTINGS TAB ======= -->
    <?php elseif ($active_tab === 'settings'): ?>
        <?php if ($settings_db_error): ?>
            <div class="db-setup-notice">
                <i class="fas fa-database"></i> <strong>Database not connected yet.</strong><br>
                Set your MySQL credentials in <code>admin/config.php</code> and import <code>admin/migrations/schema.sql</code> to manage site settings here.
            </div>
        <?php else: ?>
            <div class="section-card">
                <h3><i class="fas fa-sliders-h"></i> Global Settings <span class="count"><?= count($settings_rows) ?></span></h3>
                <p style="color:#64748b;font-size:.85rem;margin-bottom:1.2rem;">These values feed into the site's WhatsApp numbers, phone numbers, registration numbers, and hero copy wherever they're referenced.</p>
                <?php foreach ($settings_rows as $s): ?>
                    <form method="POST" action="save.php" class="settings-row">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="table" value="site_settings">
                        <input type="hidden" name="key" value="<?= htmlspecialchars($s['key']) ?>">
                        <input type="hidden" name="redirect" value="?tab=settings">
                        <div class="key-label"><?= htmlspecialchars($s['key']) ?></div>
                        <div class="form-group" style="flex:1;margin:0;">
                            <textarea name="value" rows="1" style="min-height:42px;"><?= htmlspecialchars($s['value']) ?></textarea>
                        </div>
                        <button type="submit" class="btn-upload" style="height:42px;"><i class="fas fa-save"></i></button>
                    </form>
                <?php endforeach; ?>
            </div>

            <div class="add-new-card">
                <h4><i class="fas fa-plus-circle" style="color:#FF6B35"></i> Add New Setting</h4>
                <form method="POST" action="save.php">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="table" value="site_settings">
                    <input type="hidden" name="redirect" value="?tab=settings">
                    <div class="form-grid">
                        <div class="form-group"><label>Key <small style="color:#94a3b8;font-weight:400;">(lowercase, underscores only)</small></label><input type="text" name="key" placeholder="e.g. instagram_url" pattern="[a-z0-9_]+" required></div>
                        <div class="form-group"><label>Value</label><input type="text" name="value"></div>
                    </div>
                    <div style="margin-top:1rem;">
                        <button type="submit" class="btn-upload"><i class="fas fa-plus"></i> Add Setting</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

    <!-- ======= BLOG TAB ======= -->
    <?php elseif ($active_tab === 'blog'): ?>
        <?php if ($blog_db_error): ?>
            <div class="db-setup-notice">
                <i class="fas fa-database"></i> <strong>Database not connected yet.</strong><br>
                Set your MySQL credentials in <code>admin/config.php</code> and import <code>admin/migrations/schema.sql</code> to manage blog posts here.
            </div>
        <?php else: ?>
            <div class="section-card">
                <h3><i class="fas fa-newspaper"></i> Posts <span class="count"><?= count($blog_posts) ?></span></h3>
                <?php if (!$blog_posts): ?>
                    <div class="empty-state"><i class="fas fa-newspaper"></i> No posts yet. Write the first one below.</div>
                <?php endif; ?>
                <?php foreach ($blog_posts as $post): ?>
                    <details class="crud-row">
                        <summary>
                            <span><?= htmlspecialchars($post['title']) ?><span class="crud-row-badge <?= $post['status']==='published' ? 'active' : 'inactive' ?>"><?= ucfirst($post['status']) ?></span></span>
                            <i class="fas fa-chevron-down"></i>
                        </summary>
                        <div class="crud-row-body">
                            <form method="POST" action="blog-save.php" enctype="multipart/form-data">
                                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="id" value="<?= (int)$post['id'] ?>">
                                <input type="hidden" name="redirect" value="?tab=blog">
                                <div class="form-grid">
                                    <div class="form-group"><label>Title</label><input type="text" name="title" value="<?= htmlspecialchars($post['title']) ?>" required></div>
                                    <div class="form-group"><label>URL slug</label><input type="text" name="slug" value="<?= htmlspecialchars($post['slug']) ?>"></div>
                                    <div class="form-group full"><label>Excerpt</label><textarea name="excerpt"><?= htmlspecialchars($post['excerpt']) ?></textarea></div>
                                    <div class="form-group full"><label>Body</label><textarea name="body" rows="8"><?= htmlspecialchars($post['body']) ?></textarea></div>
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select name="status">
                                            <option value="draft" <?= $post['status']==='draft'?'selected':'' ?>>Draft</option>
                                            <option value="published" <?= $post['status']==='published'?'selected':'' ?>>Published</option>
                                        </select>
                                    </div>
                                    <div class="form-group"><label>Publish date</label><input type="date" name="published_at" value="<?= $post['published_at'] ? htmlspecialchars(substr($post['published_at'],0,10)) : '' ?>"></div>
                                    <div class="form-group full">
                                        <label>Cover image <small style="color:#94a3b8;font-weight:400;">(leave blank to keep current)</small></label>
                                        <?php if ($post['cover_image']): ?><img src="../<?= htmlspecialchars($post['cover_image']) ?>" style="height:60px;border-radius:6px;margin-bottom:.5rem;display:block;"><?php endif; ?>
                                        <input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp">
                                    </div>
                                </div>
                                <div class="crud-row-actions">
                                    <button type="submit" class="btn-upload"><i class="fas fa-save"></i> Save Changes</button>
                                </div>
                            </form>
                            <form method="POST" action="blog-save.php" onsubmit="return confirm('Delete this post? This cannot be undone.')" style="margin-top:.5rem;">
                                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                <input type="hidden" name="id" value="<?= (int)$post['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="redirect" value="?tab=blog">
                                <button type="submit" class="btn-del"><i class="fas fa-trash"></i> Delete</button>
                            </form>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>

            <div class="add-new-card">
                <h4><i class="fas fa-plus-circle" style="color:#FF6B35"></i> Write New Post</h4>
                <form method="POST" action="blog-save.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="redirect" value="?tab=blog">
                    <div class="form-grid">
                        <div class="form-group"><label>Title</label><input type="text" name="title" required></div>
                        <div class="form-group"><label>URL slug <small style="color:#94a3b8;font-weight:400;">(auto-generated if blank)</small></label><input type="text" name="slug" placeholder="e.g. new-branch-opening"></div>
                        <div class="form-group full"><label>Excerpt</label><textarea name="excerpt" placeholder="One or two sentences shown in the post list"></textarea></div>
                        <div class="form-group full"><label>Body</label><textarea name="body" rows="8"></textarea></div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                        </div>
                        <div class="form-group"><label>Publish date <small style="color:#94a3b8;font-weight:400;">(defaults to today)</small></label><input type="date" name="published_at"></div>
                        <div class="form-group full"><label>Cover image</label><input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp"></div>
                    </div>
                    <div style="margin-top:1rem;">
                        <button type="submit" class="btn-upload"><i class="fas fa-plus"></i> Add Post</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

    <!-- ======= GALLERY TAB ======= -->
    <?php elseif ($active_tab === 'gallery'): ?>
        <?php foreach ($GALLERY_CATEGORIES as $cat_label => $cat_folder): ?>
            <?php
            $images = get_images($cat_folder);
            $slug   = cat_slug($cat_label);
            ?>
            <div class="section-card">
                <h3><i class="fas fa-folder-open"></i>
                    <?= htmlspecialchars($cat_label) ?>
                    <span class="count"><?= count($images) ?> / <?= MAX_FILES_PER_FOLDER ?> photo<?= count($images)!==1?'s':'' ?></span>
                </h3>

                <?php if ($images): ?>
                <div class="img-grid">
                    <?php foreach ($images as $img): ?>
                    <div class="img-item">
                        <img src="../<?= htmlspecialchars($cat_folder) ?>/<?= rawurlencode($img) ?>" loading="lazy" alt="">
                        <form method="POST" action="delete.php" onsubmit="return confirm('Delete this photo?')">
                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                            <input type="hidden" name="type" value="image">
                            <input type="hidden" name="folder" value="<?= htmlspecialchars($cat_folder) ?>">
                            <input type="hidden" name="file" value="<?= htmlspecialchars($img) ?>">
                            <input type="hidden" name="redirect" value="?tab=gallery">
                            <button type="submit" class="del-btn" title="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                        <div class="img-name"><?= htmlspecialchars($img) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <div class="empty-state"><i class="fas fa-photo-video"></i> No photos yet. Upload the first one below.</div>
                <?php endif; ?>

                <!-- Upload new image -->
                <form method="POST" action="upload.php" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="type" value="image">
                    <input type="hidden" name="folder" value="<?= htmlspecialchars($cat_folder) ?>">
                    <input type="hidden" name="redirect" value="?tab=gallery">
                    <div class="upload-row">
                        <input type="file" name="file" accept=".jpg,.jpeg,.png,.webp" required class="upload-input">
                        <button type="submit" class="btn-upload"><i class="fas fa-upload"></i> Upload Photo</button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>

    <!-- ======= VIDEOS TAB ======= -->
    <?php elseif ($active_tab === 'videos'): ?>
        <?php foreach ($VIDEO_FOLDERS as $vid_label => $vid_folder): ?>
            <?php
            $vids = get_videos($vid_folder);
            $meta_map = [];
            try {
                $stmt = db()->prepare("SELECT file_path, title, description, event_date FROM gallery_media WHERE category = :cat AND media_type = 'video'");
                $stmt->execute(['cat' => $vid_label]);
                foreach ($stmt->fetchAll() as $m) {
                    $meta_map[basename($m['file_path'])] = ['title' => $m['title'], 'date' => $m['event_date'], 'description' => $m['description']];
                }
            } catch (Throwable $e) { /* DB not configured yet — falls back to filename display below */ }
            ?>
            <div class="section-card">
                <h3><i class="fas fa-video"></i>
                    <?= htmlspecialchars($vid_label) ?>
                    <span class="count"><?= count($vids) ?> / <?= MAX_FILES_PER_FOLDER ?> video<?= count($vids)!==1?'s':'' ?></span>
                </h3>

                <?php if ($vids): ?>
                <div class="video-list">
                    <?php foreach ($vids as $vid): ?>
                        <?php $m = $meta_map[$vid] ?? []; ?>
                        <div class="video-item">
                            <div class="video-thumb">
                                <video muted preload="metadata">
                                    <source src="../<?= htmlspecialchars($vid_folder) ?>/<?= rawurlencode($vid) ?>" type="video/mp4">
                                </video>
                            </div>
                            <div class="video-info">
                                <strong><?= htmlspecialchars($m['title'] ?? $vid) ?></strong>
                                <div class="video-meta">
                                    <?php if (!empty($m['date'])): ?><span><i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($m['date']) ?></span><?php endif; ?>
                                    <span><i class="fas fa-file-video"></i> <?= htmlspecialchars($vid) ?></span>
                                </div>
                                <?php if (!empty($m['description'])): ?>
                                    <p><?= htmlspecialchars($m['description']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="video-actions">
                                <form method="POST" action="delete.php" onsubmit="return confirm('Delete this video?')">
                                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                                    <input type="hidden" name="type" value="video">
                                    <input type="hidden" name="folder" value="<?= htmlspecialchars($vid_folder) ?>">
                                    <input type="hidden" name="file" value="<?= htmlspecialchars($vid) ?>">
                                    <input type="hidden" name="redirect" value="?tab=videos">
                                    <button type="submit" class="btn-del"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <div class="empty-state"><i class="fas fa-film"></i> No videos yet. Upload the first one below.</div>
                <?php endif; ?>

                <!-- Upload new video -->
                <div class="video-upload-form">
                    <h4><i class="fas fa-plus-circle" style="color:#FF6B35"></i> Add New Video</h4>
                    <form method="POST" action="upload.php" enctype="multipart/form-data">
                        <input type="hidden" name="csrf" value="<?= $csrf ?>">
                        <input type="hidden" name="type" value="video">
                        <input type="hidden" name="folder" value="<?= htmlspecialchars($vid_folder) ?>">
                        <input type="hidden" name="redirect" value="?tab=videos">
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Video File (MP4, MOV, WEBM — max <?= htmlspecialchars(format_bytes(effective_limit('video'))) ?>)</label>
                                <input type="file" name="file" accept=".mp4,.mov,.webm" required>
                            </div>
                            <div class="form-group">
                                <label>Title</label>
                                <input type="text" name="title" placeholder="e.g. Executive Protection Assignment">
                            </div>
                            <div class="form-group">
                                <label>Date</label>
                                <input type="text" name="date" placeholder="e.g. March 2026">
                            </div>
                            <div class="form-group full">
                                <label>Description</label>
                                <textarea name="description" placeholder="Brief description of the assignment..."></textarea>
                            </div>
                            <div class="form-group full">
                                <button type="submit" class="btn-upload" style="width:100%"><i class="fas fa-upload"></i> Upload Video</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>

    <!-- ======= LOGO TAB ======= -->
    <?php elseif ($active_tab === 'logo'): ?>
        <div class="section-card">
            <h3><i class="fas fa-image"></i> Website Logo</h3>
            <p style="color:#64748b;font-size:.88rem;margin-bottom:1.2rem;">Current logo used in the navigation bar and footer across all pages.</p>
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:1.5rem;display:inline-block;margin-bottom:1.2rem;">
                <img src="../images/logo.png" alt="Current Logo" style="height:80px;width:80px;object-fit:contain;border-radius:50%;background:#fff;padding:8px;box-shadow:0 4px 15px rgba(0,0,0,0.1);">
            </div>
            <form method="POST" action="upload.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <input type="hidden" name="type" value="logo">
                <input type="hidden" name="redirect" value="?tab=logo">
                <div class="upload-row">
                    <input type="file" name="file" accept=".jpg,.jpeg,.png,.webp" required class="upload-input">
                    <button type="submit" class="btn-upload"><i class="fas fa-upload"></i> Replace Logo</button>
                </div>
                <p style="font-size:.78rem;color:#94a3b8;margin-top:.5rem;"><i class="fas fa-info-circle"></i> PNG with transparent background works best. Will replace <code>images/logo.png</code>.</p>
            </form>
        </div>

        <div class="section-card">
            <h3><i class="fas fa-panorama"></i> Hero Image</h3>
            <p style="color:#64748b;font-size:.88rem;margin-bottom:1.2rem;">The main photo shown on the home page hero section.</p>
            <?php if (file_exists(SITE_ROOT . 'images/img9.jpg')): ?>
            <div style="margin-bottom:1.2rem;">
                <img src="../images/img9.jpg" alt="Current Hero" style="width:100%;max-width:300px;border-radius:10px;border:1px solid #e2e8f0;">
            </div>
            <?php endif; ?>
            <form method="POST" action="upload.php" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <input type="hidden" name="type" value="hero">
                <input type="hidden" name="redirect" value="?tab=logo">
                <div class="upload-row">
                    <input type="file" name="file" accept=".jpg,.jpeg,.png,.webp" required class="upload-input">
                    <button type="submit" class="btn-upload"><i class="fas fa-upload"></i> Replace Hero Image</button>
                </div>
                <p style="font-size:.78rem;color:#94a3b8;margin-top:.5rem;"><i class="fas fa-info-circle"></i> Recommended: landscape photo at least 800×600 px. Will replace <code>images/img9.jpg</code>.</p>
            </form>
        </div>

    <!-- ======= PASSWORD TAB ======= -->
    <?php elseif ($active_tab === 'password'): ?>
        <div class="section-card pw-form">
            <h3><i class="fas fa-key"></i> Change Password</h3>
            <form method="POST" action="change-password.php">
                <input type="hidden" name="csrf" value="<?= $csrf ?>">
                <input type="hidden" name="redirect" value="?tab=password">
                <div class="form-group" style="margin-bottom:.9rem;">
                    <label>Current Password</label>
                    <input type="password" name="current" autocomplete="current-password" required>
                </div>
                <div class="form-group" style="margin-bottom:.9rem;">
                    <label>New Password <small style="color:#94a3b8;font-weight:400">(min 8 characters)</small></label>
                    <input type="password" name="new_pass" autocomplete="new-password" required>
                </div>
                <div class="form-group" style="margin-bottom:1.2rem;">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm" autocomplete="new-password" required>
                </div>
                <button type="submit" class="btn-upload"><i class="fas fa-save"></i> Update Password</button>
            </form>
        </div>

    <!-- ======= ADMIN USERS TAB ======= -->
    <?php elseif ($active_tab === 'admins'): ?>
        <?php if ($admins_db_error): ?>
            <div class="db-setup-notice">
                <i class="fas fa-database"></i> <strong>Database not connected yet.</strong><br>
                Set your MySQL credentials in <code>.env</code> and import <code>admin/migrations/schema.sql</code> to manage admin users here.
            </div>
        <?php else: ?>
            <div class="section-card">
                <h3><i class="fas fa-user-shield"></i> Admin Accounts <span class="count"><?= count($admin_users) ?></span></h3>
                <?php foreach ($admin_users as $u): ?>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:.9rem 0;border-bottom:1px solid #f1f5f9;">
                        <div>
                            <strong style="color:#1a365d;"><?= htmlspecialchars($u['email']) ?></strong>
                            <?php if ((int)$u['id'] === (int)($_SESSION['mgw_user_id'] ?? 0)): ?><span class="crud-row-badge active" style="margin-left:.5rem;">You</span><?php endif; ?>
                            <div style="color:#94a3b8;font-size:.78rem;margin-top:.2rem;">Added <?= htmlspecialchars(date('d M Y', strtotime($u['created_at']))) ?></div>
                        </div>
                        <form method="POST" action="users-save.php" onsubmit="return confirm('Remove this admin account?')">
                            <input type="hidden" name="csrf" value="<?= $csrf ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <input type="hidden" name="redirect" value="?tab=admins">
                            <button type="submit" class="btn-del"><i class="fas fa-trash"></i> Remove</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="add-new-card">
                <h4><i class="fas fa-user-plus" style="color:#FF6B35"></i> Add New Admin</h4>
                <form method="POST" action="users-save.php">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <input type="hidden" name="redirect" value="?tab=admins">
                    <div class="form-grid">
                        <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
                        <div class="form-group"><label>Password <small style="color:#94a3b8;font-weight:400;">(min 8 characters)</small></label><input type="password" name="password" required></div>
                    </div>
                    <div style="margin-top:1rem;">
                        <button type="submit" class="btn-upload"><i class="fas fa-plus"></i> Add Admin</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</main>
</div>

<script>
/**
 * Reject oversized files in the browser, before a single byte is uploaded.
 *
 * The server enforces these limits too — this is purely so the client finds out
 * immediately instead of watching a progress bar for ten minutes on a slow
 * connection and then getting an error.
 */
(function () {
    var LIMITS = {
        image: <?= effective_limit('image') ?>,
        video: <?= effective_limit('video') ?>
    };

    function humanSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        var units = ['KB', 'MB', 'GB'], i = -1, v = bytes;
        do { v /= 1024; i++; } while (v >= 1024 && i < units.length - 1);
        return (Math.round(v * 10) / 10) + ' ' + units[i];
    }

    // Decide which limit applies from the form's own "type" field, falling back
    // to the file input's accept list for the blog cover-image form.
    function limitFor(input) {
        var form = input.form;
        var typeField = form && form.querySelector('input[name="type"]');
        var type = typeField ? typeField.value : '';
        if (type === 'video') return { bytes: LIMITS.video, label: 'video' };
        if (type) return { bytes: LIMITS.image, label: 'image' };
        var accept = (input.getAttribute('accept') || '');
        if (accept.indexOf('mp4') !== -1) return { bytes: LIMITS.video, label: 'video' };
        return { bytes: LIMITS.image, label: 'image' };
    }

    document.addEventListener('change', function (e) {
        var input = e.target;
        if (!input || input.type !== 'file' || !input.files || !input.files.length) return;

        var limit = limitFor(input);
        var file  = input.files[0];
        if (file.size <= limit.bytes) return;

        alert(
            'That ' + limit.label + ' is ' + humanSize(file.size) + ', which is over the ' +
            humanSize(limit.bytes) + ' limit.\n\n' +
            (limit.label === 'video'
                ? 'Please compress it first. On most phones, sharing the video offers a "smaller size" option; on a computer, HandBrake (free) works well.'
                : 'Please choose a smaller photo, or resize it before uploading.')
        );
        input.value = '';
    });

    // Disable the submit button during upload so an impatient double-click does
    // not send the same large file twice.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || form.enctype !== 'multipart/form-data') return;
        var btn = form.querySelector('button[type="submit"]');
        if (!btn) return;
        setTimeout(function () {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading…';
        }, 0);
    });
})();
</script>
</body>
</html>
