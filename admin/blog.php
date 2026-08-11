<?php
require_once '../config/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once 'includes/auth_guard.php';
require_once '../includes/db.php';
require_once 'includes/upload_helper.php';

$db = getDB();

/* Add columns silently */
try { $db->exec("ALTER TABLE blog_posts ADD COLUMN IF NOT EXISTS category VARCHAR(60) DEFAULT 'Safari Tips'"); } catch (\Throwable $e) {}
try { $db->exec("ALTER TABLE blog_posts ADD COLUMN IF NOT EXISTS meta_description VARCHAR(255) DEFAULT ''"); } catch (\Throwable $e) {}
try { $db->exec("ALTER TABLE blog_posts ADD COLUMN IF NOT EXISTS seo_title VARCHAR(100) DEFAULT ''"); } catch (\Throwable $e) {}
try { $db->exec("ALTER TABLE blog_posts ADD COLUMN IF NOT EXISTS keywords VARCHAR(255) DEFAULT ''"); } catch (\Throwable $e) {}

$errors = [];

function blogSlug(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9\s-]/', '', $s);
    return trim(preg_replace('/[\s-]+/', '-', $s), '-');
}

$blogCategories = ['Safari Tips','Wildlife','Culture','Trekking','Conservation','Zanzibar'];
$catColors = ['Safari Tips'=>'#fbbf24','Wildlife'=>'#34d399','Culture'=>'#a78bfa','Trekking'=>'#60a5fa','Conservation'=>'#4ade80','Zanzibar'=>'#38bdf8'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        http_response_code(403); die('Invalid CSRF token.');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id       = sanitizeInt($_POST['id'] ?? 0, 0);
        $title    = sanitizeInput($_POST['title']    ?? '');
        $slug     = blogSlug(sanitizeInput($_POST['slug'] ?? '') ?: $title);
        $author   = sanitizeInput($_POST['author']   ?? '');
        $category = sanitizeInput($_POST['category'] ?? 'Safari Tips');
        $excerpt  = sanitizeInput($_POST['excerpt']  ?? '');
        $metaDesc = sanitizeInput($_POST['meta_description'] ?? '');
        $seoTitle = sanitizeInput($_POST['seo_title'] ?? '');
        $keywords = sanitizeInput($_POST['keywords'] ?? '');
        $content  = trim($_POST['content']  ?? '');
        $published= isset($_POST['published']) ? 1 : 0;
        $existing = sanitizeInput($_POST['existing_image'] ?? '');
        $urlImg   = sanitizeInput($_POST['image_url'] ?? '');

        if (empty($title))   $errors[] = 'Title is required.';
        if (empty($author))  $errors[] = 'Author name is required.';
        if (empty($excerpt)) $errors[] = 'Excerpt is required.';
        if (empty($content)) $errors[] = 'Content is required.';

        $imgR = handleImageUpload('image_file', $urlImg ?: $existing);
        if (isset($imgR['error'])) $errors[] = $imgR['error'];
        $imageUrl = $imgR['url'] ?? $existing;
        if (empty($imageUrl)) $errors[] = 'Featured image is required (upload or paste URL).';

        if (empty($errors)) {
            $chk = $db->prepare("SELECT id FROM blog_posts WHERE slug=? AND id!=? LIMIT 1");
            $chk->execute([$slug, $id]);
            if ($chk->fetch()) $slug .= '-' . time();

            if ($id) {
                $db->prepare("UPDATE blog_posts SET title=:t,slug=:sl,author=:a,category=:cat,excerpt=:ex,meta_description=:md,seo_title=:st,keywords=:kw,content=:c,image=:img,published=:pub WHERE id=:id")
                   ->execute([':t'=>$title,':sl'=>$slug,':a'=>$author,':cat'=>$category,':ex'=>$excerpt,':md'=>$metaDesc,':st'=>$seoTitle,':kw'=>$keywords,':c'=>$content,':img'=>$imageUrl,':pub'=>$published,':id'=>$id]);
            } else {
                $db->prepare("INSERT INTO blog_posts (title,slug,author,category,excerpt,meta_description,seo_title,keywords,content,image,published) VALUES (:t,:sl,:a,:cat,:ex,:md,:st,:kw,:c,:img,:pub)")
                   ->execute([':t'=>$title,':sl'=>$slug,':a'=>$author,':cat'=>$category,':ex'=>$excerpt,':md'=>$metaDesc,':st'=>$seoTitle,':kw'=>$keywords,':c'=>$content,':img'=>$imageUrl,':pub'=>$published]);
            }
            redirect(SITE_URL . '/admin/blog.php?msg=' . urlencode($id ? 'Post updated successfully.' : 'New post published!'));
        }
    }

    if ($action === 'delete') {
        $id = sanitizeInt($_POST['id'] ?? 0, 1);
        if ($id) $db->prepare("DELETE FROM blog_posts WHERE id=?")->execute([$id]);
        redirect(SITE_URL . '/admin/blog.php?msg=Post+deleted.');
    }

    if ($action === 'toggle_published') {
        $id = sanitizeInt($_POST['id'] ?? 0, 1);
        if ($id) $db->prepare("UPDATE blog_posts SET published = 1 - published WHERE id=?")->execute([$id]);
        redirect(SITE_URL . '/admin/blog.php');
    }
}

$msg    = sanitizeInput($_GET['msg'] ?? '');
$editId = sanitizeInt($_GET['edit'] ?? 0, 0);
$editing= null;
if ($editId) {
    $s = $db->prepare("SELECT * FROM blog_posts WHERE id=?"); $s->execute([$editId]);
    $editing = $s->fetch();
}
$posts     = $db->query("SELECT * FROM blog_posts ORDER BY created_at DESC")->fetchAll();
$csrfToken = generateCsrfToken();
$siteHost  = parse_url(SITE_URL, PHP_URL_HOST) ?: 'jambomasaitours.com';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Blog Posts | Jambo Masai Admin</title>
  <meta name="robots" content="noindex,nofollow">
  <link rel="icon" type="image/png" href="<?= e(getSetting('favicon_url', SITE_URL.'/assets/images/favicon.ico')) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Nanum+Myeongjo:wght@700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= SITE_URL ?>/admin/assets/admin.css">
  <!-- Mtindo ule ule wa makala unaotumika kwenye blog ya umma, ili preview
       ilingane kabisa na kitakachoonekana kwenye site -->
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/article.css?v=<?= @filemtime(__DIR__ . '/../assets/css/article.css') ?>">
  <style>
    /* ── Content editor ── */
    .content-editor{width:100%;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);color:#e5e7eb;border-radius:0 0 10px 10px;padding:.85rem 1rem;font-family:'Fira Code','Courier New',monospace;font-size:.83rem;outline:none;transition:border-color .2s;resize:vertical;min-height:340px;line-height:1.7;tab-size:2}
    .content-editor:focus{border-color:rgba(16,185,129,.5);background:rgba(16,185,129,.03)}

    /* ── Edit / Preview mode tabs ── */
    .editor-mode-tabs{display:flex;border-radius:7px;overflow:hidden;border:1px solid rgba(255,255,255,.1)}
    .mode-tab{padding:.28rem .65rem;font-family:'Montserrat',sans-serif;font-size:.62rem;font-weight:700;cursor:pointer;color:rgba(255,255,255,.4);background:rgba(255,255,255,.03);border:none;display:flex;align-items:center;gap:.3rem;transition:all .2s}
    .mode-tab i{font-size:.62rem}
    .mode-tab.active{background:rgba(16,185,129,.15);color:#10b981}

    /* ── Content live preview ──
       Preview ina darasa `.prose`, hivyo inarithi mtindo ULE ULE wa ukurasa wa
       umma kutoka assets/css/article.css (imeunganishwa kwenye <head>).
       Hapa tunaweka mwonekano wa kisanduku cha preview peke yake — usirudie
       sheria za .prose hapa, zitatofautiana na site halisi. */
    #content-preview{display:none;background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:1.1rem 1.3rem;min-height:340px}

    /* ── Toolbar ── */
    .editor-toolbar{display:flex;flex-wrap:wrap;gap:.25rem;padding:.55rem .75rem;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:10px 10px 0 0;border-bottom:none}
    .tb-sep{width:1px;background:rgba(255,255,255,.08);margin:0 .15rem;align-self:stretch}
    .tb-btn{padding:.3rem .48rem;border-radius:6px;background:transparent;border:1px solid transparent;color:rgba(255,255,255,.45);cursor:pointer;font-family:'Montserrat',sans-serif;font-size:.65rem;font-weight:700;transition:all .18s;white-space:nowrap;line-height:1;display:inline-flex;align-items:center;gap:.25rem}
    .tb-btn i{font-size:.7rem}
    .tb-btn:hover{background:rgba(16,185,129,.1);border-color:rgba(16,185,129,.3);color:#10b981}
    .tb-btn.tb-text{font-size:.68rem;font-family:'Montserrat',sans-serif}

    /* ── Category badges ── */
    .cat-option{display:none}
    .cat-label{display:inline-flex;align-items:center;gap:.4rem;padding:.42rem .85rem;border-radius:999px;border:1.5px solid rgba(255,255,255,.1);cursor:pointer;font-family:'Montserrat',sans-serif;font-size:.67rem;font-weight:600;transition:all .22s;color:rgba(255,255,255,.4);background:rgba(255,255,255,.03)}
    .cat-option:checked + .cat-label{border-color:rgba(16,185,129,.5);background:rgba(16,185,129,.12);color:#10b981}

    /* ── SEO bar ── */
    .seo-bar{height:4px;border-radius:2px;background:rgba(255,255,255,.07);margin-top:.35rem;overflow:hidden}
    .seo-fill{height:100%;border-radius:2px;transition:width .25s,background .25s}

    /* ── SERP preview ── */
    .serp-box{background:#fff;border-radius:10px;padding:1rem 1.1rem;margin-top:.85rem;font-family:Arial,sans-serif}
    .serp-url{font-size:.68rem;color:#006621;margin-bottom:.15rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .serp-title{font-size:.95rem;color:#1a0dab;font-weight:normal;margin-bottom:.15rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;cursor:pointer}
    .serp-title:hover{text-decoration:underline}
    .serp-desc{font-size:.78rem;color:#545454;line-height:1.5;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}

    /* ── Image tabs ── */
    .img-tabs{display:flex;gap:0;margin-bottom:.85rem;border-radius:8px;overflow:hidden;border:1px solid rgba(255,255,255,.1)}
    .img-tab{flex:1;padding:.42rem;text-align:center;font-family:'Montserrat',sans-serif;font-size:.68rem;font-weight:600;cursor:pointer;color:rgba(255,255,255,.4);background:rgba(255,255,255,.03);border:none;transition:all .2s}
    .img-tab.active{background:rgba(16,185,129,.15);color:#10b981}
    .img-panel{display:none}.img-panel.active{display:block}

    /* ── Image preview ── */
    .preview-img{width:100%;height:150px;object-fit:cover;border-radius:10px;border:1px solid rgba(255,255,255,.08);display:block;margin-bottom:.75rem}

    /* ── Post list card ── */
    .post-row{background:#1a1d27;border:1px solid rgba(255,255,255,.07);border-radius:14px;padding:1rem 1.1rem;margin-bottom:.6rem;display:flex;align-items:center;gap:1rem;transition:border-color .2s}
    .post-row:hover{border-color:rgba(16,185,129,.2)}

    /* ── Two-column edit grid ── */
    .edit-grid{display:grid;grid-template-columns:1fr 300px;gap:1.25rem;align-items:start}
    @media(max-width:900px){.edit-grid{grid-template-columns:1fr}}

    /* ── Char counter ── */
    .char-row{display:flex;justify-content:space-between;align-items:center;margin-top:.3rem}
    .char-count{font-family:'Montserrat',sans-serif;font-size:.58rem;color:rgba(255,255,255,.25)}
  </style>
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/admin_sidebar.php'; ?>
  <main class="admin-main">

    <!-- Header -->
    <div class="admin-header">
      <div>
        <h1 style="font-family:'Nanum Myeongjo',serif;font-size:1.4rem;color:#fff;font-weight:700;display:flex;align-items:center;gap:.5rem">
          <i class="fas fa-newspaper" style="color:#10b981;font-size:1.1rem"></i>
          <?= isset($_GET['edit']) ? ($editing ? 'Edit Post' : 'New Blog Post') : 'Blog Posts' ?>
        </h1>
        <p style="color:rgba(255,255,255,.4);font-size:.78rem;font-family:'Montserrat',sans-serif;margin-top:.15rem">
          <?= isset($_GET['edit']) ? 'Write and publish your article' : count($posts).' posts · click a post to edit' ?>
        </p>
      </div>
      <div style="display:flex;gap:.5rem">
        <?php if (isset($_GET['edit']) && $editing): ?>
        <a href="<?= url('blog-single.php?slug='.e($editing['slug'])) ?>" target="_blank" class="btn btn--outline btn--sm" style="display:inline-flex;align-items:center;gap:.35rem">
          <i class="fas fa-eye" style="font-size:.6rem"></i> Preview
        </a>
        <a href="blog.php" class="btn btn--outline btn--sm" style="display:inline-flex;align-items:center;gap:.35rem">
          <i class="fas fa-arrow-left" style="font-size:.6rem"></i> All Posts
        </a>
        <?php else: ?>
        <a href="blog.php?edit=0" class="btn btn--primary btn--sm" style="display:inline-flex;align-items:center;gap:.35rem">
          <i class="fas fa-plus" style="font-size:.6rem"></i> New Post
        </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Success message -->
    <?php if ($msg): ?>
    <div class="alert alert-success" style="margin-bottom:1.25rem">
      <i class="fas fa-check-circle"></i> <?= e($msg) ?>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['edit'])): ?>
    <!-- ══════════════════════════ EDIT / ADD FORM ══════════════════════════ -->

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error" style="margin-bottom:1.1rem">
      <i class="fas fa-exclamation-circle"></i>
      <ul style="list-style:none;margin:0">
        <?php foreach ($errors as $e_): ?><li>· <?= e($e_) ?></li><?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="blog-form">
      <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= $editing ? $editing['id'] : 0 ?>">
      <input type="hidden" name="existing_image" value="<?= e($editing['image'] ?? '') ?>">

      <div class="edit-grid">

        <!-- ── Main column ── -->
        <div>

          <!-- Title + Slug -->
          <div class="adm-card" style="margin-bottom:1rem">
            <div class="adm-card-body">
              <div class="f-group">
                <label class="f-label">Post Title <span>*</span></label>
                <input type="text" class="f-input" name="title" id="blog-title" required
                       value="<?= e($editing['title'] ?? '') ?>"
                       placeholder="e.g. Best Time to Visit Serengeti — A Complete Guide"
                       style="font-size:1.05rem;font-family:'Nanum Myeongjo',serif">
              </div>
              <div class="f-group" style="margin-bottom:0">
                <label class="f-label">URL Slug <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:.65rem">(auto-generated)</span></label>
                <div style="position:relative">
                  <span style="position:absolute;left:.85rem;top:50%;transform:translateY(-50%);font-family:'Montserrat',sans-serif;font-size:.68rem;color:rgba(255,255,255,.25)">/blog/</span>
                  <input type="text" class="f-input" name="slug" id="blog-slug"
                         value="<?= e($editing['slug'] ?? '') ?>"
                         placeholder="best-time-to-visit-serengeti"
                         style="padding-left:3.75rem">
                </div>
              </div>
            </div>
          </div>

          <!-- Excerpt -->
          <div class="adm-card" style="margin-bottom:1rem">
            <div class="adm-card-body">
              <label class="f-label">Excerpt <span>*</span> <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:.65rem">— 1–2 sentences shown in blog list</span></label>
              <textarea class="f-input" name="excerpt" rows="2" required id="blog-excerpt"
                        placeholder="A concise summary that appears in blog listings and search results..."
                        style="min-height:70px;resize:vertical"><?= e($editing['excerpt'] ?? '') ?></textarea>
              <div class="char-row">
                <div class="seo-bar" style="flex:1;margin-right:.75rem"><div class="seo-fill" id="excerpt-fill" style="width:0;background:#f87171"></div></div>
                <span id="excerpt-count" class="char-count">0 / 160</span>
              </div>
              <div class="f-hint" style="margin-top:.4rem"><i class="fas fa-info-circle" style="color:rgba(96,165,250,.6);margin-right:.3rem"></i>Plain text only — HTML tags like <code style="background:rgba(255,255,255,.07);border-radius:4px;padding:.05rem .3rem">&lt;strong&gt;</code> will show as literal text on the blog page, not bold.</div>
            </div>
          </div>

          <!-- Content editor -->
          <div class="adm-card" style="margin-bottom:1rem">
            <div class="adm-card-header">
              <h2 class="adm-card-title">Article Content <span style="font-family:'Montserrat',sans-serif;font-weight:400;font-size:.7rem;color:rgba(255,255,255,.3)">(HTML supported)</span></h2>
              <div style="display:flex;align-items:center;gap:.65rem">
                <span style="font-family:'Montserrat',sans-serif;font-size:.6rem;color:rgba(255,255,255,.25)" id="word-count">0 words</span>
                <div class="editor-mode-tabs">
                  <button type="button" class="mode-tab active" data-mode="edit" onclick="switchEditorMode('edit')"><i class="fas fa-code"></i> Edit</button>
                  <button type="button" class="mode-tab" data-mode="preview" onclick="switchEditorMode('preview')"><i class="fas fa-eye"></i> Preview</button>
                </div>
              </div>
            </div>
            <div class="adm-card-body" style="padding-top:.5rem">
              <!-- Toolbar -->
              <div class="editor-toolbar" id="editor-toolbar">
                <button type="button" class="tb-btn tb-text" onclick="tbWrap('h2')" title="Heading 2">H2</button>
                <button type="button" class="tb-btn tb-text" onclick="tbWrap('h3')" title="Heading 3">H3</button>
                <div class="tb-sep"></div>
                <button type="button" class="tb-btn" onclick="tbWrap('strong')" title="Bold"><i class="fas fa-bold"></i></button>
                <button type="button" class="tb-btn" onclick="tbWrap('em')" title="Italic"><i class="fas fa-italic"></i></button>
                <button type="button" class="tb-btn" onclick="tbWrap('u')" title="Underline"><i class="fas fa-underline"></i></button>
                <button type="button" class="tb-btn" onclick="tbWrap('s')" title="Strikethrough"><i class="fas fa-strikethrough"></i></button>
                <div class="tb-sep"></div>
                <button type="button" class="tb-btn" onclick="tbList('ul')" title="Bullet List"><i class="fas fa-list-ul"></i></button>
                <button type="button" class="tb-btn" onclick="tbList('ol')" title="Numbered List"><i class="fas fa-list-ol"></i></button>
                <div class="tb-sep"></div>
                <button type="button" class="tb-btn" onclick="tbLink()" title="Link"><i class="fas fa-link"></i></button>
                <button type="button" class="tb-btn" onclick="tbImage()" title="Image"><i class="fas fa-image"></i></button>
                <div class="tb-sep"></div>
                <button type="button" class="tb-btn" onclick="tbWrap('blockquote')" title="Blockquote"><i class="fas fa-quote-right"></i></button>
                <button type="button" class="tb-btn" onclick="tbWrap('code')" title="Inline Code"><i class="fas fa-code"></i></button>
                <button type="button" class="tb-btn" onclick="tbInsert('<hr>\n')" title="Divider"><i class="fas fa-minus"></i></button>
                <div class="tb-sep"></div>
                <button type="button" class="tb-btn" onclick="tbTip()" title="Pro Tip box" style="color:#fbbf24"><i class="fas fa-lightbulb"></i></button>
                <button type="button" class="tb-btn" onclick="tbInsert('\n<!-- more -->\n')" title="Read More break" style="font-size:.6rem;color:rgba(255,255,255,.35)">···</button>
              </div>
              <textarea class="content-editor" name="content" id="blog-content" required
                        placeholder="<h2>Introduction</h2>&#10;<p>Start your article here...</p>&#10;&#10;<h2>Section Title</h2>&#10;<p>More content here...</p>"><?= e($editing['content'] ?? '') ?></textarea>
              <div id="preview-mode-note" class="f-hint" style="display:none;margin-bottom:.6rem;background:rgba(96,165,250,.06);border:1px solid rgba(96,165,250,.2);border-radius:8px;padding:.6rem .75rem">
                <i class="fas fa-circle-info" style="color:#60a5fa;margin-right:.3rem"></i>
                No <code style="background:rgba(255,255,255,.07);border-radius:4px;padding:.05rem .3rem">&lt;p&gt;</code>/<code style="background:rgba(255,255,255,.07);border-radius:4px;padding:.05rem .3rem">&lt;h2&gt;</code> tags found, so this is being auto-split into paragraphs as <strong style="color:rgba(255,255,255,.6)">plain text</strong>. Inline formatting — <code style="background:rgba(255,255,255,.07);border-radius:4px;padding:.05rem .3rem">&lt;strong&gt;</code>, <code style="background:rgba(255,255,255,.07);border-radius:4px;padding:.05rem .3rem">&lt;em&gt;</code>, <code style="background:rgba(255,255,255,.07);border-radius:4px;padding:.05rem .3rem">&lt;u&gt;</code>, <code style="background:rgba(255,255,255,.07);border-radius:4px;padding:.05rem .3rem">&lt;a&gt;</code>, images — still renders fine. Other tags (e.g. <code style="background:rgba(255,255,255,.07);border-radius:4px;padding:.05rem .3rem">&lt;div&gt;</code>) would show as literal text. Wrap content in <code style="background:rgba(255,255,255,.07);border-radius:4px;padding:.05rem .3rem">&lt;p&gt;...&lt;/p&gt;</code> / use H2/H3 if you need full block-level HTML.
              </div>
              <div id="content-preview" class="prose"></div>
              <div class="f-hint" style="margin-top:.4rem"><i class="fas fa-info-circle" style="color:rgba(16,185,129,.5);margin-right:.3rem"></i>Use toolbar buttons to insert HTML. <kbd style="background:rgba(255,255,255,.07);border-radius:4px;padding:.1rem .35rem;font-size:.6rem">Tab</kbd> inserts 2 spaces. Switch to <strong style="color:rgba(255,255,255,.6)">Preview</strong> to see how it will look on the blog.</div>
            </div>
          </div>

          <!-- SEO Settings -->
          <div class="adm-card">
            <div class="adm-card-header">
              <h2 class="adm-card-title"><i class="fas fa-search" style="color:#fbbf24;font-size:.85rem;margin-right:.4rem"></i>SEO Settings</h2>
            </div>
            <div class="adm-card-body">
              <!-- SEO Title -->
              <div class="f-group">
                <label class="f-label">SEO Title <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:.65rem">(leave empty to use article title)</span></label>
                <input type="text" class="f-input" name="seo_title" id="seo-title"
                       value="<?= e($editing['seo_title'] ?? '') ?>"
                       placeholder="SEO title for search engines...">
                <div class="char-row">
                  <div class="seo-bar" style="flex:1;margin-right:.75rem"><div class="seo-fill" id="seo-title-fill" style="width:0;background:#f87171"></div></div>
                  <span id="seo-title-count" class="char-count">0 / 60</span>
                </div>
              </div>
              <!-- Meta Description -->
              <div class="f-group">
                <label class="f-label">Meta Description <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:.65rem">(recommended 150–160 chars)</span></label>
                <textarea class="f-input" name="meta_description" id="meta-desc" rows="2"
                          placeholder="Brief description for Google search results..."
                          style="min-height:65px;resize:none"><?= e($editing['meta_description'] ?? '') ?></textarea>
                <div class="char-row">
                  <div class="seo-bar" style="flex:1;margin-right:.75rem"><div class="seo-fill" id="meta-fill" style="width:0;background:#f87171"></div></div>
                  <span id="meta-count" class="char-count">0 / 160</span>
                </div>
              </div>
              <!-- Keywords -->
              <div class="f-group" style="margin-bottom:0">
                <label class="f-label">Keywords <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:.65rem">(comma separated)</span></label>
                <input type="text" class="f-input" name="keywords" id="seo-keywords"
                       value="<?= e($editing['keywords'] ?? '') ?>"
                       placeholder="safari Tanzania, Serengeti tour, wildlife Africa...">
              </div>
              <!-- Google SERP Preview -->
              <div style="margin-top:1rem">
                <p style="font-family:'Montserrat',sans-serif;font-size:.65rem;color:rgba(255,255,255,.3);margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.04em">Google Preview</p>
                <div class="serp-box">
                  <div class="serp-url" id="serp-url"><?= htmlspecialchars($siteHost) ?> › blog › <span id="serp-slug-part"><?= e($editing['slug'] ?? 'article-url-slug') ?></span></div>
                  <div class="serp-title" id="serp-title"><?= e($editing['seo_title'] ?: $editing['title'] ?? 'Article Title') ?></div>
                  <div class="serp-desc" id="serp-desc"><?= e($editing['meta_description'] ?? 'Meta description will appear here...') ?></div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- ── Sidebar column ── -->
        <div>

          <!-- Publish -->
          <div class="adm-card" style="margin-bottom:1rem">
            <div class="adm-card-header"><h2 class="adm-card-title">Publish</h2></div>
            <div class="adm-card-body">
              <!-- Status toggle -->
              <div style="display:flex;align-items:center;justify-content:space-between;padding:.6rem .75rem;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);border-radius:10px;margin-bottom:.75rem">
                <span style="font-family:'Montserrat',sans-serif;font-size:.75rem;color:rgba(255,255,255,.6)">Status</span>
                <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                  <div style="position:relative;width:40px;height:22px;flex-shrink:0">
                    <input type="checkbox" name="published" value="1" id="pub-toggle"
                           <?= ($editing['published'] ?? 0) ? 'checked' : '' ?>
                           style="opacity:0;width:0;height:0;position:absolute">
                    <div id="pub-slider" style="position:absolute;inset:0;border-radius:22px;background:<?= ($editing['published']??0)?'#10b981':'rgba(255,255,255,.12)' ?>;cursor:pointer;transition:background .3s"></div>
                    <div id="pub-thumb" style="position:absolute;width:16px;height:16px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:transform .3s;<?= ($editing['published']??0)?'transform:translateX(18px)':'' ?>"></div>
                  </div>
                  <span id="pub-label" style="font-family:'Montserrat',sans-serif;font-size:.75rem;font-weight:600;color:<?= ($editing['published']??0)?'#10b981':'rgba(255,255,255,.4)' ?>"><?= ($editing['published']??0)?'Published':'Draft' ?></span>
                </label>
              </div>
              <button type="submit" class="btn btn--primary w-full" style="display:flex;align-items:center;justify-content:center;gap:.4rem;padding:.8rem;font-size:.8rem">
                <i class="fas fa-save" style="font-size:.7rem"></i>
                <?= $editing ? 'Update Post' : 'Publish Post' ?>
              </button>
              <a href="blog.php" class="btn btn--outline w-full" style="display:flex;align-items:center;justify-content:center;gap:.4rem;margin-top:.45rem;font-size:.78rem">
                Cancel
              </a>
            </div>
          </div>

          <!-- Category -->
          <div class="adm-card" style="margin-bottom:1rem">
            <div class="adm-card-header"><h2 class="adm-card-title"><i class="fas fa-tag" style="color:#fbbf24;font-size:.82rem;margin-right:.4rem"></i>Category</h2></div>
            <div class="adm-card-body">
              <div style="display:flex;flex-wrap:wrap;gap:.4rem">
                <?php
                $catIcons = ['Safari Tips'=>'fa-lightbulb','Wildlife'=>'fa-paw','Culture'=>'fa-users','Trekking'=>'fa-mountain','Conservation'=>'fa-leaf','Zanzibar'=>'fa-umbrella-beach'];
                foreach ($blogCategories as $cat):
                  $isSel = ($editing['category'] ?? 'Safari Tips') === $cat;
                  $ci = str_replace(' ','-',$cat);
                ?>
                <input type="radio" name="category" value="<?= e($cat) ?>" id="cat-<?= $ci ?>"
                       class="cat-option" <?= $isSel?'checked':'' ?>>
                <label for="cat-<?= $ci ?>" class="cat-label" style="<?= $isSel?'border-color:rgba(16,185,129,.5);background:rgba(16,185,129,.12);color:#10b981':'' ?>">
                  <i class="fas <?= $catIcons[$cat]??'fa-tag' ?>" style="font-size:.6rem"></i><?= $cat ?>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Author -->
          <div class="adm-card" style="margin-bottom:1rem">
            <div class="adm-card-header"><h2 class="adm-card-title"><i class="fas fa-user" style="color:#60a5fa;font-size:.82rem;margin-right:.4rem"></i>Author</h2></div>
            <div class="adm-card-body">
              <div class="f-group" style="margin-bottom:0">
                <input type="text" class="f-input" name="author" required
                       value="<?= e($editing['author'] ?? 'Jambo Masai Team') ?>"
                       placeholder="Author name">
              </div>
            </div>
          </div>

          <!-- Featured Image -->
          <div class="adm-card">
            <div class="adm-card-header"><h2 class="adm-card-title"><i class="fas fa-image" style="color:#f97316;font-size:.82rem;margin-right:.4rem"></i>Featured Image</h2></div>
            <div class="adm-card-body">
              <!-- Preview -->
              <div id="img-preview-wrap" style="<?= empty($editing['image']) ? 'display:none' : '' ?>">
                <img src="<?= e($editing['image'] ?? '') ?>" alt="" class="preview-img" id="img-preview"
                     style="<?= empty($editing['image']) ? 'display:none' : '' ?>">
              </div>

              <!-- Tabs -->
              <div class="img-tabs">
                <button type="button" class="img-tab active" onclick="switchImgTab('url',this)">
                  <i class="fas fa-link" style="font-size:.6rem;margin-right:.3rem"></i>URL
                </button>
                <button type="button" class="img-tab" onclick="switchImgTab('upload',this)">
                  <i class="fas fa-upload" style="font-size:.6rem;margin-right:.3rem"></i>Upload
                </button>
              </div>

              <!-- URL panel -->
              <div class="img-panel active" id="img-panel-url">
                <input type="url" class="f-input" name="image_url" id="blog-url"
                       value="<?= e($editing['image'] ?? '') ?>"
                       placeholder="https://... image URL"
                       style="margin-bottom:0">
              </div>

              <!-- Upload panel -->
              <div class="img-panel" id="img-panel-upload">
                <input type="file" class="f-input" name="image_file" id="blog-file" accept="image/jpeg,image/png,image/webp">
                <div class="f-hint">JPG / PNG / WebP · Max 8 MB</div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </form>

    <?php else: ?>
    <!-- ══════════════════════════ POSTS LIST ══════════════════════════ -->

    <!-- Stats -->
    <div class="adm-stats" style="grid-template-columns:repeat(4,1fr);margin-bottom:1.25rem">
      <?php
      $total     = count($posts);
      $published = count(array_filter($posts, fn($p) => $p['published']));
      $drafts    = $total - $published;
      $cats      = count(array_unique(array_column($posts, 'category')));
      foreach ([
        ['fa-newspaper',   'rgba(16,185,129,.15)','#10b981', $total,     'Total Posts'],
        ['fa-check-circle','rgba(52,211,153,.15)', '#34d399', $published, 'Published'],
        ['fa-edit',        'rgba(245,158,11,.15)', '#fbbf24', $drafts,    'Drafts'],
        ['fa-tags',        'rgba(96,165,250,.15)', '#60a5fa', $cats,      'Categories'],
      ] as $w): ?>
      <div class="adm-stat">
        <div class="adm-stat-icon" style="background:<?= $w[1] ?>"><i class="fas <?= $w[0] ?>" style="color:<?= $w[2] ?>;font-size:.9rem"></i></div>
        <div><div class="adm-stat-val"><?= $w[3] ?></div><div class="adm-stat-lbl"><?= $w[4] ?></div></div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Posts table -->
    <div class="adm-card">
      <div class="adm-card-header">
        <h2 class="adm-card-title">All Posts (<?= $total ?>)</h2>
        <a href="blog.php?edit=0" class="btn btn--primary btn--sm" style="display:inline-flex;align-items:center;gap:.35rem">
          <i class="fas fa-plus" style="font-size:.6rem"></i> New Post
        </a>
      </div>
      <div class="adm-table-wrap">
        <table class="adm-table">
          <thead>
            <tr>
              <th style="width:80px">Image</th>
              <th>Title</th>
              <th>Category</th>
              <th>Author</th>
              <th>Status</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($posts)): ?>
            <tr><td colspan="7" style="text-align:center;padding:2.5rem;color:rgba(255,255,255,.25)">
              <i class="fas fa-newspaper" style="font-size:2rem;display:block;margin-bottom:.65rem;color:rgba(255,255,255,.1)"></i>
              No posts yet. <a href="blog.php?edit=0" style="color:#10b981;text-decoration:none">Write your first article →</a>
            </td></tr>
            <?php else: foreach ($posts as $p):
              $cat    = $p['category'] ?? 'Safari Tips';
              $catClr = $catColors[$cat] ?? '#fbbf24';
            ?>
            <tr>
              <td>
                <?php if ($p['image']): ?>
                <img src="<?= e($p['image']) ?>" alt="" class="img-small" style="width:70px;height:50px">
                <?php else: ?>
                <div style="width:70px;height:50px;background:rgba(255,255,255,.04);border-radius:8px;display:flex;align-items:center;justify-content:center"><i class="fas fa-image" style="color:rgba(255,255,255,.15)"></i></div>
                <?php endif; ?>
              </td>
              <td style="max-width:260px">
                <div style="font-weight:600;color:#fff;font-size:.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><?= e($p['title']) ?></div>
                <div style="font-size:.68rem;color:rgba(255,255,255,.28);font-family:'Montserrat',sans-serif;margin-top:.1rem">/blog/<?= e($p['slug']) ?></div>
              </td>
              <td>
                <span style="font-family:'Montserrat',sans-serif;font-size:.6rem;font-weight:700;padding:.18rem .65rem;border-radius:999px;color:<?= $catClr ?>;background:<?= $catClr ?>18;border:1px solid <?= $catClr ?>30;white-space:nowrap">
                  <?= e($cat) ?>
                </span>
              </td>
              <td style="font-size:.8rem;color:rgba(255,255,255,.5)"><?= e($p['author']) ?></td>
              <td>
                <form method="POST" style="display:inline">
                  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
                  <input type="hidden" name="action" value="toggle_published">
                  <input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <button type="submit" class="badge <?= $p['published']?'badge-confirmed':'badge-pending' ?>" style="cursor:pointer;border:none">
                    <?= $p['published'] ? '● Published' : '○ Draft' ?>
                  </button>
                </form>
              </td>
              <td style="font-size:.75rem;color:rgba(255,255,255,.35);font-family:'Montserrat',sans-serif"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
              <td style="white-space:nowrap">
                <a href="blog.php?edit=<?= $p['id'] ?>" class="btn btn--outline btn--sm" style="font-size:.62rem;padding:.28rem .6rem" title="Edit">
                  <i class="fas fa-pen"></i>
                </a>
                <?php if ($p['published']): ?>
                <a href="<?= url('blog-single.php?slug='.e($p['slug'])) ?>" class="btn btn--outline btn--sm" style="font-size:.62rem;padding:.28rem .6rem" target="_blank" title="View live">
                  <i class="fas fa-eye"></i>
                </a>
                <?php endif; ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this post permanently?')">
                  <input type="hidden" name="<?= CSRF_TOKEN_NAME ?>" value="<?= e($csrfToken) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= $p['id'] ?>">
                  <button type="submit" class="btn btn--danger btn--sm" style="font-size:.62rem;padding:.28rem .6rem" title="Delete">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php endif; ?>

  </main>
</div>

<script>
/* ── Auto-slug from title ── */
var bt = document.getElementById('blog-title');
var bs = document.getElementById('blog-slug');
if (bt && bs && !bs.value) {
  bt.addEventListener('input', function(){
    var slug = this.value.toLowerCase().trim()
      .replace(/[^a-z0-9\s-]/g,'').replace(/[\s-]+/g,'-').replace(/^-+|-+$/g,'');
    bs.value = slug;
    updateSerp();
  });
}
if (bs) bs.addEventListener('input', updateSerp);

/* ── Mirrors includes/functions.php blogContent()/inlineSafeHtml() so the preview matches the live blog page ── */
function inlineSafeHtmlPreview(text){
  var html = text
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;')
    .replace(/\n/g,'<br>\n');
  html = html.replace(/&lt;(\/?(?:strong|em|b|i|u|s|code))&gt;/gi, '<$1>');
  html = html.replace(/&lt;a href=&quot;(.*?)&quot;&gt;/gi, '<a href="$1">');
  html = html.replace(/&lt;\/a&gt;/gi, '</a>');
  html = html.replace(/&lt;img\s+(.*?)&gt;/gi, function(_, attrs){
    return '<img ' + attrs.replace(/&quot;/g,'"') + '>';
  });
  return html;
}

/* Lazima ilingane na stripChecklistGlyphs() ya includes/functions.php —
   ondoa alama za checkbox zilizoandikwa kwa mkono ili preview ionyeshe
   kile kile kitakachotoka kwenye site. */
function stripChecklistGlyphsPreview(html){
  return html.replace(
    /(<(?:li|label|p)\b[^>]*>)\s*(?:&nbsp;|\s)*(?:[☐-☒❏▢□❑❒]|\[\s*[xX✓]?\s*\])\s*/gi,
    '$1'
  );
}

function blogContentPreview(text){
  text = text.trim();
  if (!text) return '';
  // Already has block-level HTML — render as-is
  if (/<(p|h[1-6]|ul|ol|li|blockquote|div|table|hr)\b/i.test(text)) {
    return stripChecklistGlyphsPreview(text);
  }
  // Plain text — build <p> paragraphs from blank lines, preserving safe inline tags
  var paragraphs = text.split(/\n{2,}/);
  var out = '';
  paragraphs.forEach(function(para){
    para = para.trim();
    if (!para) return;
    out += '<p>' + inlineSafeHtmlPreview(para) + '</p>\n';
  });
  return out || '<p>' + inlineSafeHtmlPreview(text) + '</p>';
}

/* ── Edit / Preview mode ── */
function switchEditorMode(mode){
  var ta      = document.getElementById('blog-content');
  var toolbar = document.getElementById('editor-toolbar');
  var preview = document.getElementById('content-preview');
  var note    = document.getElementById('preview-mode-note');
  if (!ta || !toolbar || !preview) return;
  document.querySelectorAll('.mode-tab').forEach(function(t){ t.classList.toggle('active', t.dataset.mode === mode); });
  if (mode === 'preview') {
    var text     = ta.value.trim();
    var isPlain  = text && !/<(p|h[1-6]|ul|ol|li|blockquote|div|table|hr)\b/i.test(text);
    var rendered = blogContentPreview(ta.value);
    preview.innerHTML = rendered || '<p style="color:rgba(255,255,255,.25);font-style:italic">Nothing to preview yet — write some content first.</p>';
    if (note) note.style.display = isPlain ? 'block' : 'none';
    toolbar.style.display = 'none';
    ta.style.display = 'none';
    preview.style.display = 'block';
  } else {
    if (note) note.style.display = 'none';
    toolbar.style.display = 'flex';
    ta.style.display = 'block';
    preview.style.display = 'none';
  }
}

/* ── Toolbar helpers ── */
function getTA(){ return document.getElementById('blog-content'); }

function tbWrap(tag) {
  var ta = getTA(); if (!ta) return;
  var s = ta.selectionStart, e = ta.selectionEnd;
  var sel = ta.value.substring(s, e) || 'Your text here';
  var ins = '<' + tag + '>' + sel + '</' + tag + '>';
  ta.value = ta.value.substring(0,s) + ins + ta.value.substring(e);
  ta.focus(); ta.selectionStart = s + tag.length + 2; ta.selectionEnd = s + ins.length - tag.length - 3;
  updateWordCount();
}

function tbInsert(html) {
  var ta = getTA(); if (!ta) return;
  var s = ta.selectionStart;
  ta.value = ta.value.substring(0,s) + html + ta.value.substring(s);
  ta.focus(); ta.selectionStart = ta.selectionEnd = s + html.length;
  updateWordCount();
}

function tbList(type) {
  var ta = getTA(); if (!ta) return;
  var s = ta.selectionStart, e = ta.selectionEnd;
  var sel = ta.value.substring(s, e).trim();
  var items = sel ? sel.split('\n').map(function(l){ return '  <li>' + (l.trim()||'Item') + '</li>'; }).join('\n') : '  <li>Item 1</li>\n  <li>Item 2</li>';
  var ins = '<' + type + '>\n' + items + '\n</' + type + '>\n';
  ta.value = ta.value.substring(0,s) + ins + ta.value.substring(e);
  ta.focus(); updateWordCount();
}

function tbLink() {
  var ta = getTA(); if (!ta) return;
  var href = prompt('URL:', 'https://');
  if (!href) return;
  var s = ta.selectionStart, e = ta.selectionEnd;
  var sel = ta.value.substring(s, e) || 'link text';
  var ins = '<a href="' + href + '">' + sel + '</a>';
  ta.value = ta.value.substring(0,s) + ins + ta.value.substring(e);
  ta.focus(); updateWordCount();
}

function tbImage() {
  var ta = getTA(); if (!ta) return;
  var src = prompt('Image URL:', 'https://');
  if (!src) return;
  var alt = prompt('Alt text:', '') || '';
  tbInsert('\n<img src="' + src + '" alt="' + alt + '" class="rounded-xl w-full my-6">\n');
}

function tbTip() {
  var ta = getTA(); if (!ta) return;
  var s = ta.selectionStart, e = ta.selectionEnd;
  var sel = ta.value.substring(s, e) || 'Your pro tip here.';
  var ins = '<div class="glass rounded-2xl p-5 my-6 border-l-4 border-emerald-500"><p><strong class="text-emerald-400">Pro Tip:</strong> ' + sel + '</p></div>\n';
  ta.value = ta.value.substring(0,s) + ins + ta.value.substring(e);
  ta.focus(); updateWordCount();
}

/* Tab key inserts spaces */
var bContent = document.getElementById('blog-content');
if (bContent) {
  bContent.addEventListener('keydown', function(ev){
    if (ev.key === 'Tab') {
      ev.preventDefault();
      var s = this.selectionStart;
      this.value = this.value.substring(0,s) + '  ' + this.value.substring(s);
      this.selectionStart = this.selectionEnd = s + 2;
    }
  });
  bContent.addEventListener('input', updateWordCount);
  updateWordCount();
}

/* ── Word count ── */
function updateWordCount(){
  var ta = getTA(), wc = document.getElementById('word-count');
  if (!ta || !wc) return;
  var words = ta.value.replace(/<[^>]*>/g,'').replace(/\s+/g,' ').trim().split(' ').filter(Boolean).length;
  wc.textContent = words.toLocaleString() + ' words · ~' + Math.max(1,Math.ceil(words/200)) + ' min read';
}

/* ── SEO char bars ── */
function seoBar(inputId, fillId, countId, max){
  var inp = document.getElementById(inputId);
  var fill = document.getElementById(fillId);
  var cnt  = document.getElementById(countId);
  if (!inp) return;
  function update(){
    var len = inp.value.length, pct = Math.min(100, len/max*100);
    var clr = len < Math.floor(max*0.4) ? '#f87171' : len <= max ? '#10b981' : '#fbbf24';
    if (fill){ fill.style.width = pct+'%'; fill.style.background = clr; }
    if (cnt) cnt.textContent = len + ' / ' + max;
    if (inputId === 'seo-title' || inputId === 'meta-desc') updateSerp();
  }
  inp.addEventListener('input', update); update();
}
seoBar('blog-excerpt','excerpt-fill','excerpt-count',160);
seoBar('seo-title','seo-title-fill','seo-title-count',60);
seoBar('meta-desc','meta-fill','meta-count',160);

/* ── Google SERP preview ── */
function updateSerp(){
  var titleEl  = document.getElementById('serp-title');
  var descEl   = document.getElementById('serp-desc');
  var slugEl   = document.getElementById('serp-slug-part');

  var rawTitle = (document.getElementById('blog-title') || {}).value || '';
  var seoTitle = (document.getElementById('seo-title') || {}).value || '';
  var metaDesc = (document.getElementById('meta-desc') || {}).value || '';
  var slugVal  = (document.getElementById('blog-slug') || {}).value || '';

  if (titleEl) titleEl.textContent = (seoTitle || rawTitle || 'Article Title').substring(0,60);
  if (descEl)  descEl.textContent  = metaDesc || 'Meta description will appear here...';
  if (slugEl)  slugEl.textContent  = slugVal  || 'article-url-slug';
}

/* Hook title input to SERP */
var btEl = document.getElementById('blog-title');
if (btEl) btEl.addEventListener('input', updateSerp);
var stEl = document.getElementById('seo-title');
if (stEl) stEl.addEventListener('input', updateSerp);
var mdEl = document.getElementById('meta-desc');
if (mdEl) mdEl.addEventListener('input', updateSerp);
updateSerp();

/* ── Image tabs ── */
function switchImgTab(panel, btn){
  document.querySelectorAll('.img-tab').forEach(function(t){ t.classList.remove('active'); });
  document.querySelectorAll('.img-panel').forEach(function(p){ p.classList.remove('active'); });
  btn.classList.add('active');
  var p = document.getElementById('img-panel-' + panel);
  if (p) p.classList.add('active');
}

/* ── Image preview ── */
var bFile = document.getElementById('blog-file');
var bUrl  = document.getElementById('blog-url');
var bPrev = document.getElementById('img-preview');
var bWrap = document.getElementById('img-preview-wrap');

function showPreview(src){
  if (!src) return;
  if (!bPrev) return;
  bPrev.src = src; bPrev.style.display = 'block';
  if (bWrap) bWrap.style.display = 'block';
}
if (bFile) bFile.addEventListener('change', function(){
  if (this.files[0]){ if (bUrl) bUrl.value = ''; showPreview(URL.createObjectURL(this.files[0])); }
});
if (bUrl) {
  var urlTimer;
  bUrl.addEventListener('input', function(){
    clearTimeout(urlTimer);
    var v = this.value.trim();
    urlTimer = setTimeout(function(){ if (v) showPreview(v); }, 600);
  });
}

/* ── Category radio styling ── */
document.querySelectorAll('.cat-option').forEach(function(r){
  r.addEventListener('change', function(){
    document.querySelectorAll('.cat-label').forEach(function(l){
      l.style.borderColor=''; l.style.background=''; l.style.color='';
    });
    var lbl = this.nextElementSibling;
    if (lbl){ lbl.style.borderColor='rgba(16,185,129,.5)'; lbl.style.background='rgba(16,185,129,.12)'; lbl.style.color='#10b981'; }
  });
});

/* ── Publish toggle styling ── */
var pubToggle = document.getElementById('pub-toggle');
var pubSlider = document.getElementById('pub-slider');
var pubThumb  = document.getElementById('pub-thumb');
var pubLabel  = document.getElementById('pub-label');
if (pubToggle) {
  pubToggle.addEventListener('change', function(){
    if (pubSlider) pubSlider.style.background = this.checked ? '#10b981' : 'rgba(255,255,255,.12)';
    if (pubThumb)  pubThumb.style.transform   = this.checked ? 'translateX(18px)' : '';
    if (pubLabel){ pubLabel.textContent = this.checked ? 'Published' : 'Draft'; pubLabel.style.color = this.checked ? '#10b981' : 'rgba(255,255,255,.4)'; }
  });
}
</script>
</body>
</html>
