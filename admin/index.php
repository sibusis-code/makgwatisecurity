<?php
require_once __DIR__ . '/auth.php';
require_login();
global $GALLERY_CATEGORIES, $VIDEO_FOLDERS;

$csrf        = csrf_token();
$active_tab  = $_GET['tab'] ?? 'gallery';
$flash_ok    = $_GET['ok'] ?? '';
$flash_err   = $_GET['err'] ?? '';

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
        <a href="../index.html" target="_blank" class="btn-site"><i class="fas fa-external-link-alt"></i> View Site</a>
        <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="layout">
<!-- Sidebar -->
<nav class="sidebar">
    <div class="sidebar-title">Manage</div>
    <a href="?tab=gallery" class="nav-item <?= $active_tab==='gallery'?'active':'' ?>"><i class="fas fa-images"></i> Gallery Images</a>
    <a href="?tab=videos" class="nav-item <?= $active_tab==='videos'?'active':'' ?>"><i class="fas fa-film"></i> Project Videos</a>
    <a href="?tab=logo" class="nav-item <?= $active_tab==='logo'?'active':'' ?>"><i class="fas fa-image"></i> Site Logo</a>
    <div class="sidebar-title">Account</div>
    <a href="?tab=password" class="nav-item <?= $active_tab==='password'?'active':'' ?>"><i class="fas fa-key"></i> Change Password</a>
</nav>

<!-- Main content -->
<main class="main">
    <div class="page-title">
        <?php
        $titles = ['gallery'=>'Gallery Images','videos'=>'Project Videos','logo'=>'Site Logo','password'=>'Change Password'];
        echo htmlspecialchars($titles[$active_tab] ?? 'Dashboard');
        ?>
    </div>
    <div class="page-sub">
        <?php if ($active_tab==='gallery'): ?>Upload and manage photos shown in each gallery category on the VIP Protection page.
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

    <!-- ======= GALLERY TAB ======= -->
    <?php if ($active_tab === 'gallery'): ?>
        <?php foreach ($GALLERY_CATEGORIES as $cat_label => $cat_folder): ?>
            <?php
            $images = get_images($cat_folder);
            $slug   = cat_slug($cat_label);
            ?>
            <div class="section-card">
                <h3><i class="fas fa-folder-open"></i>
                    <?= htmlspecialchars($cat_label) ?>
                    <span class="count"><?= count($images) ?> photo<?= count($images)!==1?'s':'' ?></span>
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
            $meta = read_video_meta(SITE_ROOT . $vid_folder);
            $meta_map = [];
            foreach ($meta as $m) $meta_map[$m['file']] = $m;
            ?>
            <div class="section-card">
                <h3><i class="fas fa-video"></i>
                    <?= htmlspecialchars($vid_label) ?>
                    <span class="count"><?= count($vids) ?> video<?= count($vids)!==1?'s':'' ?></span>
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
                                <label>Video File (MP4, MOV, WEBM — max 300 MB)</label>
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
    <?php endif; ?>

</main>
</div>
</body>
</html>
