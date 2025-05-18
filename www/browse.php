<?php
// browse.php – Eenvoudige manga-zoekpagina met Jikan API
require_once __DIR__.'/config/config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ophalen zoekterm en limiet uit de URL\​
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;

// Initialiseer data en error variabelen
$data = [];
$error = '';

if ($query !== '') {
    // Bouw de API-URL: alleen "manga", SFW, gesorteerd op chronologische mal_id
    $url = sprintf(
        "https://api.jikan.moe/v4/manga?q=%s&limit=%d&type=manga&sfw&order_by=mal_id&sort=asc",
        urlencode($query),
        $limit
    );

    // HTTP-context voor file_get_contents
    $options = [
        'http' => [
            'method'  => 'GET',
            'header'  => "User-Agent: MangaBrowser/1.0\r\n",
            'timeout' => 10
        ],
        'ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false
        ]
    ];
    $ctx = stream_context_create($options);

    // Ophalen en decoderen
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp === false) {
        $error = 'Kon de API niet bereiken of er is een fout opgetreden.';
    } else {
        $json = json_decode($resp, true);
        $data = $json['data'] ?? [];

        // Dubbele filter: SFW is al toegepast, maar sluit expliciet "Hentai" genres uit
        $data = array_filter($data, function($m) {
            foreach ($m['genres'] ?? [] as $genre) {
                if (strtolower($genre['name']) === 'hentai') {
                    return false;
                }
            }
            return true;
        });
    }
}
// Handle bookmark submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bookmark') {
    if (empty($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = 'browse.php?q=' . urlencode($query) . '&limit=' . $limit;
        header('Location: login.php');
        exit;
    }

    // Extract manga details
    $title = $_POST['title'] ?? '';
    $chapter = $_POST['chapters'] ?? '0';
    $cover = $_POST['cover'] ?? '';
    $description = $_POST['synopsis'] ?? '';
    $api_id = $_POST['api_id'] ?? '';
    $max_chapters = isset($_POST['max_chapters']) && !empty($_POST['max_chapters']) ? (int)$_POST['max_chapters'] : null;
    
    // Add to bookmarks
    try {
        $stmt = $pdo->prepare("
            INSERT INTO bookmarks (user_id, manga_title, last_chapter, max_chapters, notes, cover_image, description, api_id, api_source)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'jikan')
            ON DUPLICATE KEY UPDATE 
                last_chapter = VALUES(last_chapter),
                max_chapters = VALUES(max_chapters),
                updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([
            $_SESSION['user_id'], 
            $title, 
            $chapter,
            $max_chapters,
            '',  // Notes empty by default
            $cover,
            $description,
            $api_id
        ]);
        
        $success_message = "'" . htmlspecialchars($title) . "' toegevoegd aan je collectie.";
    } catch (PDOException $e) {
        $error = "Er is een fout opgetreden bij het opslaan: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Manga zoeken - Manga Tracker</title>  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/style-fixes.css">
</head>
<body>
  <?php include __DIR__.'/header.php'; ?>
    <div class="container">
    <h1>Manga zoeken</h1>
    <form method="GET" style="display: flex; gap: 10px; align-items: center; margin-bottom: 20px;">
      <input type="text" name="q" placeholder="Titel..." value="<?= htmlspecialchars($query) ?>" required style="flex-grow: 1;">
      <label style="margin-bottom: 0;">
        Aantal:
        <input type="number" name="limit" min="1" max="20" value="<?= $limit ?>" style="width: 60px;">
      </label>
      <button type="submit" class="btn btn-sm">Zoeken</button>
    </form>

<?php if ($error): ?>
  <p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($query !== ''): ?>
  <p>Resultaten voor <strong><?= htmlspecialchars($query) ?></strong> (max <?= $limit ?>)</p>

  <?php if (count($data) > 0): ?>    <div class="search-results">
      <?php foreach ($data as $m): ?>
        <div class="search-result">
          <img src="<?= htmlspecialchars($m['images']['jpg']['image_url'] ?? 'assets/images/no-cover.png') ?>" 
               alt="Cover" class="search-result-image">
          <div class="search-result-content">
            <h3 class="manga-title"><?= htmlspecialchars($m['title'] ?? '–') ?></h3>
            <p class="manga-chapter"><strong>Hoofdstukken:</strong> <?= htmlspecialchars($m['chapters'] ?: '?') ?></p>
            <p><strong>Score:</strong> <?= htmlspecialchars($m['score'] ?: '?') ?></p>
            <p class="manga-description"><?= htmlspecialchars(mb_strimwidth($m['synopsis'] ?? '', 0, 120, '…')) ?></p>
            
            <?php if (!empty($_SESSION['user_id'])): ?>
            <form method="POST" class="search-result-actions">
              <input type="hidden" name="action" value="bookmark">
              <input type="hidden" name="api_id" value="<?= htmlspecialchars($m['mal_id'] ?? '') ?>">
              <input type="hidden" name="title" value="<?= htmlspecialchars($m['title'] ?? '') ?>">
              <input type="hidden" name="chapters" value="<?= htmlspecialchars($m['chapters'] ?? '0') ?>">
              <input type="hidden" name="max_chapters" value="<?= htmlspecialchars($m['chapters'] ?? '') ?>">
              <input type="hidden" name="cover" value="<?= htmlspecialchars($m['images']['jpg']['image_url'] ?? '') ?>">
              <input type="hidden" name="synopsis" value="<?= htmlspecialchars($m['synopsis'] ?? '') ?>">
              <button type="submit" class="btn btn-sm">
                <i class="fas fa-bookmark"></i> Bookmark toevoegen
              </button>
            </form>
            <?php else: ?>
            <p class="search-result-actions" style="font-size: 0.9rem;">
              <a href="login.php?redirect=browse.php?q=<?= urlencode($query) ?>&limit=<?= $limit ?>">Log in</a> om toe te voegen aan je collectie
            </p>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>Geen manga gevonden.</p>  <?php endif; ?>
<?php endif; ?>

<?php if (isset($success_message)): ?>
  <div style="background: #d4edda; color: #155724; padding: 0.75rem; border-radius: 4px; margin-top: 1.5rem;">
    <?= htmlspecialchars($success_message) ?>
  </div>
<?php endif; ?>
</div> <!-- /.container -->

<?php include __DIR__.'/footer.php'; ?>
</body>
</html>
