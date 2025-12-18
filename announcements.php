<?php
session_start();
require_once 'config.php';
require_once 'helpers.php'; 

$perPage = 10; // 10 announcements per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

// Count total announcements
$totalResult = $conn->query("SELECT COUNT(*) as total FROM announcement_blog");
$totalRow = $totalResult->fetch_assoc();
$totalAnnouncements = $totalRow['total'];
$totalPages = ceil($totalAnnouncements / $perPage);

// Calculate offset
$offset = ($page - 1) * $perPage;

// Fetch announcements for current page
$stmt = $conn->prepare("SELECT title, content, created_at FROM announcement_blog ORDER BY created_at DESC LIMIT ?, ?");
$stmt->bind_param("ii", $offset, $perPage);
$stmt->execute();
$result = $stmt->get_result();

$announcements = [];
while ($row = $result->fetch_assoc()) {
    $announcements[] = $row;
}
function truncateWords($text, $limit = 40) {
    $words = preg_split('/\s+/', strip_tags($text));
    if(count($words) <= $limit) return $text;
    return implode(' ', array_slice($words, 0, $limit)) . '...';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Taste of Paradise | Announcements</title>
    <link rel="stylesheet" href="../static/css/announcements.css"/>
</head>
<body>
    <header>  
        <nav class="navbar announcements-navbar">
            <div class="logo">
                <img src="../static/image/logo.png" alt="Taste of Paradise" />
            </div>

            <!-- Hamburger -->
            <button class="hamburger" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <!-- Dropdown -->
            <div class="hamburger-menu">
                <a href="../index.php">Home</a>
                <a href="full_menu.php">Menu</a>
                <a href="announcements.php">Announcements</a>
            </div>
        </nav>
    </header>

    <div id="announcementModal" aria-hidden="true" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.55); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:#fff; width:min(600px, 92vw); border-radius:12px; overflow:hidden; max-height:85vh; overflow-y:auto;">
            <div style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1px solid #eee; position:sticky; top:0; background:#fff; z-index:1;">
                <h3 id="amTitle" style="margin:0; font-size:1.25rem;">Announcement</h3>
                <button id="amClose" type="button" aria-label="Close" style="background:#1a1a1a; color:#fff; border:none; width:28px; height:28px; border-radius:50%; cursor:pointer;">×</button>
            </div>
            <div style="padding:14px 16px;">
                <p id="amDate" style="font-size:0.9rem; color:#8b5a2b; margin-bottom:12px;"></p>
                <div id="amContent" style="white-space:pre-wrap; line-height:1.6;"></div>
            </div>
        </div>
    </div>

    <main class="announcements-list-section">
        <h1 style="margin-bottom:2rem; color:#4b2e0b;">Announcements</h1>

        <?php if (!empty($announcements)): ?>
            <div class="announcements-list">
                <?php foreach ($announcements as $ann): 
                    $preview = truncateWords($ann['content'], 40); // 40-word preview
                ?>
                    <div class="announcement-card full-card">
                        <h2><?php echo htmlspecialchars($ann['title']); ?></h2>
                        <p class="announcement-date">
                            <?php echo date('F j, Y', strtotime($ann['created_at'])); ?>
                        </p>
                        <div class="announcement-content">
                            <?php echo renderMarkdown($preview); ?>
                        </div>
                        <div style="margin-top:0.8rem; text-align:right;">
                            <button type="button" class="announcement-view-btn"
                                data-title="<?php echo htmlspecialchars($ann['title'], ENT_QUOTES); ?>"
                                data-content="<?php echo htmlspecialchars(renderMarkdown($ann['content']), ENT_QUOTES); ?>"
                                data-date="<?php echo date('F j, Y', strtotime($ann['created_at'])); ?>">
                                View
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div style="text-align:center; margin-top:2rem;">
                    <?php
                    $maxVisible = 5; // max pages to show
                    $start = max(1, $page - floor($maxVisible / 2));
                    $end = min($totalPages, $start + $maxVisible - 1);
                    $start = max(1, $end - $maxVisible + 1); // adjust start if near the end

                    // Previous page
                    if ($page > 1) {
                        echo '<a href="?page='.($page-1).'" class="view-all-btn" style="margin:0 3px;">&lt;</a>';
                    }

                    // Page numbers
                    for ($i = $start; $i <= $end; $i++) {
                        $active = $i === $page ? 'background:#6a3a10;' : '';
                        echo '<a href="?page='.$i.'" class="view-all-btn" style="margin:0 3px; '.$active.'">'.$i.'</a>';
                    }

                    // Next page
                    if ($page < $totalPages) {
                        echo '<a href="?page='.($page+1).'" class="view-all-btn" style="margin:0 3px;">&gt;</a>';
                    }
                    ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <p class="no-announcements">No announcements available at this time.</p>
        <?php endif; ?>

        <div style="text-align:center; margin-top:3rem;">
            <a href="../index.php" class="view-all-btn">Back to Home</a>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Taste of Paradise. All rights reserved.</p>
    </footer>
</body>
<script>
    (() => {
        const navbar = document.querySelector('.announcements-navbar');
        const hamburger = navbar.querySelector('.hamburger');
        const menu = navbar.querySelector('.hamburger-menu');

        hamburger.addEventListener('click', (e) => {
            e.stopPropagation();
            hamburger.classList.toggle('active');
            menu.classList.toggle('show'); // use 'show' like homepage
        });


        document.addEventListener('click', (e) => {
            if (!navbar.contains(e.target)) {
                hamburger.classList.remove('active');
                menu.classList.remove('active');
            }
        });
    })();
    (function initAnnouncementModal() {
        const modal = document.getElementById('announcementModal');
        const titleEl = document.getElementById('amTitle');
        const contentEl = document.getElementById('amContent');
        const dateEl = document.getElementById('amDate');
        const closeBtn = document.getElementById('amClose');

        function openModal(data) {
            titleEl.textContent = data.title;
            contentEl.innerHTML = data.content; // already rendered Markdown
            dateEl.textContent = data.date;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => { if(e.target === modal) closeModal(); });

        document.querySelectorAll('.announcement-view-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                openModal({
                    title: btn.getAttribute('data-title'),
                    content: btn.getAttribute('data-content'),
                    date: btn.getAttribute('data-date')
                });
            });
        });
    })();
</script>
</html>
<?php $conn->close(); ?>
