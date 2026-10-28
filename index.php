<?php
/**
 * FindIt - Main Portal Home Page
 * Displays portal hero, live statistics counters, category explorer, and recent reports.
 * Enforces compulsory login: users must log in to access the portal and its contents.
 */
require_once __DIR__ . '/db.php';

// Enforce Compulsory Login
require_login();

$pdo = getDbConnection();

// Fetch live statistics
$stats = [
    'total'    => (int)$pdo->query("SELECT COUNT(*) FROM items")->fetchColumn(),
    'lost'     => (int)$pdo->query("SELECT COUNT(*) FROM items WHERE type = 'lost' AND status = 'active'")->fetchColumn(),
    'found'    => (int)$pdo->query("SELECT COUNT(*) FROM items WHERE type = 'found' AND status = 'active'")->fetchColumn(),
    'resolved' => (int)$pdo->query("SELECT COUNT(*) FROM items WHERE status IN ('claimed', 'returned')")->fetchColumn()
];

// Fetch recent items (latest 6 records)
$recentStmt = $pdo->query("SELECT * FROM items ORDER BY created_at DESC LIMIT 6");
$recentItems = $recentStmt->fetchAll();
$categories = get_categories();

render_header('Home - Community Lost & Found');
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-inner">
        <div class="hero-badge"><?= get_svg_icon('sparkles', 'svg-icon', 14) ?> Community Recovery Network</div>
        <h1 class="hero-title">Lost Something? <span>We Help You Find It.</span></h1>
        <p class="hero-desc">
            Browse active lost and found reports, or quickly publish a report to reunite belongings with their rightful owners.
        </p>

        <!-- Search Bar Form (Submits to browse.php) -->
        <form action="browse.php" method="GET" class="search-box-wrapper">
            <input type="text" name="q" class="search-input" placeholder="What did you lose or find? (e.g. iPhone, Keys, Wallet...)" aria-label="Search items">
            
            <select name="category" class="search-category-select" aria-label="Select category">
                <option value="">All Categories</option>
                <?php foreach ($categories as $slug => $cat): ?>
                    <option value="<?= htmlspecialchars($slug) ?>"><?= htmlspecialchars($cat['label']) ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-primary">
                Search
            </button>
        </form>

        <!-- Quick Action Buttons -->
        <div style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="report.php?type=lost" class="btn btn-danger">
                Report a Lost Item
            </a>
            <a href="report.php?type=found" class="btn btn-success">
                Report a Found Item
            </a>
        </div>

        <!-- Starting Page Visual Showcase: Wallet, Mobile & Person Returning Item Handover -->
        <div class="visual-story-grid">
            <div class="visual-story-card card-lost-accent">
                <div class="story-img-container">
                    <svg class="story-img-svg" viewBox="0 0 340 190" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Wallet with Lost Something banner">
                        <defs>
                            <linearGradient id="walletBgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#1e293b" />
                                <stop offset="100%" stop-color="#0f172a" />
                            </linearGradient>
                            <linearGradient id="walletLeather" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#9a3412" />
                                <stop offset="50%" stop-color="#7c2d12" />
                                <stop offset="100%" stop-color="#431407" />
                            </linearGradient>
                            <linearGradient id="cardGrad1" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#38bdf8" />
                                <stop offset="100%" stop-color="#0284c7" />
                            </linearGradient>
                            <linearGradient id="cardGrad2" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#fbbf24" />
                                <stop offset="100%" stop-color="#d97706" />
                            </linearGradient>
                            <linearGradient id="cashGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#34d399" />
                                <stop offset="100%" stop-color="#059669" />
                            </linearGradient>
                        </defs>
                        <rect width="340" height="190" rx="16" fill="url(#walletBgGrad)" />
                        <circle cx="170" cy="85" r="70" fill="#f43f5e" opacity="0.12" filter="blur(20px)" />
                        <rect x="110" y="28" width="120" height="42" rx="4" fill="url(#cashGrad)" transform="rotate(-6 170 49)" opacity="0.9" />
                        <circle cx="170" cy="46" r="10" stroke="#a7f3d0" stroke-width="2" fill="none" transform="rotate(-6 170 49)" />
                        <rect x="95" y="42" width="100" height="55" rx="6" fill="url(#cardGrad2)" transform="rotate(-12 145 69)" opacity="0.95" />
                        <rect x="105" y="58" width="20" height="15" rx="2" fill="#78350f" transform="rotate(-12 145 69)" />
                        <rect x="150" y="38" width="105" height="55" rx="6" fill="url(#cardGrad1)" transform="rotate(8 202 65)" opacity="0.95" />
                        <circle cx="175" cy="58" r="8" fill="#ffffff" opacity="0.5" transform="rotate(8 202 65)" />
                        <rect x="70" y="60" width="200" height="100" rx="14" fill="url(#walletLeather)" stroke="rgba(255,255,255,0.15)" stroke-width="1.5" />
                        <rect x="76" y="66" width="188" height="88" rx="10" fill="none" stroke="#fed7aa" stroke-width="1.2" stroke-dasharray="5 3" opacity="0.45" />
                        <path d="M 220 85 L 265 85 A 12 12 0 0 1 265 115 L 220 115 Z" fill="#431407" stroke="#fed7aa" stroke-width="1" stroke-dasharray="4 2" />
                        <circle cx="250" cy="100" r="7" fill="#fbbf24" stroke="#d97706" stroke-width="1.5" />
                        <g transform="translate(170, 150)">
                            <rect x="-85" y="-14" width="170" height="28" rx="14" fill="#f43f5e" />
                            <text x="0" y="5" fill="#ffffff" font-family="'Outfit', sans-serif" font-size="12" font-weight="800" letter-spacing="1" text-anchor="middle">LOST SOMETHING?</text>
                        </g>
                    </svg>
                </div>
                <div class="story-card-body">
                    <h3 class="story-card-title">Lost Something?</h3>
                    <p class="story-card-desc">Lost your wallet, IDs, keys or essentials? Post details right away to notify searchers.</p>
                    <a href="report.php?type=lost" class="story-card-link text-lost">Report Missing Item &rarr;</a>
                </div>
            </div>

            <div class="visual-story-card card-found-accent">
                <div class="story-img-container">
                    <svg class="story-img-svg" viewBox="0 0 340 190" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Mobile Phone with Find It banner">
                        <defs>
                            <linearGradient id="phoneBgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#1e293b" />
                                <stop offset="100%" stop-color="#0f172a" />
                            </linearGradient>
                            <linearGradient id="phoneBody" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#334155" />
                                <stop offset="100%" stop-color="#0f172a" />
                            </linearGradient>
                            <linearGradient id="screenGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#0284c7" />
                                <stop offset="50%" stop-color="#0369a1" />
                                <stop offset="100%" stop-color="#0c4a6e" />
                            </linearGradient>
                        </defs>
                        <rect width="340" height="190" rx="16" fill="url(#phoneBgGrad)" />
                        <circle cx="170" cy="85" r="70" fill="#06b6d4" opacity="0.15" filter="blur(20px)" />
                        <circle cx="170" cy="75" r="60" stroke="#06b6d4" stroke-width="1.5" stroke-dasharray="4 4" opacity="0.35" />
                        <circle cx="170" cy="75" r="85" stroke="#06b6d4" stroke-width="1" opacity="0.2" />
                        <rect x="122" y="15" width="96" height="145" rx="18" fill="url(#phoneBody)" stroke="rgba(255,255,255,0.2)" stroke-width="2" />
                        <rect x="128" y="22" width="84" height="131" rx="13" fill="url(#screenGrad)" />
                        <rect x="155" y="27" width="30" height="6" rx="3" fill="#0f172a" />
                        <circle cx="170" cy="78" r="28" stroke="#38bdf8" stroke-width="1.5" fill="none" opacity="0.4" />
                        <circle cx="170" cy="78" r="16" stroke="#38bdf8" stroke-width="2" fill="none" opacity="0.8" />
                        <path d="M 170 66 C 165 66 161 70 161 75 C 161 82 170 89 170 89 C 170 89 179 82 179 75 C 179 70 175 66 170 66 Z" fill="#38bdf8" />
                        <circle cx="170" cy="74" r="3" fill="#0f172a" />
                        <rect x="136" y="105" width="68" height="20" rx="10" fill="rgba(15,23,42,0.85)" stroke="#38bdf8" stroke-width="1" />
                        <text x="170" y="119" fill="#38bdf8" font-family="'Outfit', sans-serif" font-size="9" font-weight="700" text-anchor="middle">MATCH FOUND</text>
                        <g transform="translate(170, 150)">
                            <rect x="-65" y="-14" width="130" height="28" rx="14" fill="#0284c7" />
                            <text x="0" y="5" fill="#ffffff" font-family="'Outfit', sans-serif" font-size="12" font-weight="800" letter-spacing="1" text-anchor="middle">FIND IT!</text>
                        </g>
                    </svg>
                </div>
                <div class="story-card-body">
                    <h3 class="story-card-title">Find It!</h3>
                    <p class="story-card-desc">Browse found smartphones, electronics and items recovered by community members.</p>
                    <a href="browse.php?type=found" class="story-card-link text-found">Search Found Items &rarr;</a>
                </div>
            </div>

            <div class="visual-story-card card-reunited-accent">
                <div class="story-img-container">
                    <svg class="story-img-svg" viewBox="0 0 340 190" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Person returning item to another person">
                        <defs>
                            <linearGradient id="reuniteBgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#1e293b" />
                                <stop offset="100%" stop-color="#0f172a" />
                            </linearGradient>
                            <linearGradient id="personAGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#38bdf8" />
                                <stop offset="100%" stop-color="#0284c7" />
                            </linearGradient>
                            <linearGradient id="personBGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#a78bfa" />
                                <stop offset="100%" stop-color="#7c3aed" />
                            </linearGradient>
                            <linearGradient id="giftGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#34d399" />
                                <stop offset="100%" stop-color="#059669" />
                            </linearGradient>
                        </defs>
                        <rect width="340" height="190" rx="16" fill="url(#reuniteBgGrad)" />
                        <circle cx="170" cy="85" r="70" fill="#10b981" opacity="0.14" filter="blur(20px)" />
                        <path d="M 170 30 L 172 38 L 180 40 L 172 42 L 170 50 L 168 42 L 160 40 L 168 38 Z" fill="#fbbf24" />
                        <circle cx="145" cy="38" r="2.5" fill="#38bdf8" />
                        <circle cx="195" cy="38" r="2.5" fill="#a78bfa" />
                        <circle cx="95" cy="52" r="16" fill="url(#personAGrad)" stroke="rgba(255,255,255,0.2)" stroke-width="1.5" />
                        <path d="M 83 48 C 83 38 107 38 107 48" fill="#0f172a" opacity="0.6" />
                        <path d="M 72 108 C 72 82 118 82 118 108 Z" fill="url(#personAGrad)" />
                        <path d="M 108 86 Q 135 88 152 92" stroke="url(#personAGrad)" stroke-width="8" stroke-linecap="round" />
                        <circle cx="245" cy="52" r="16" fill="url(#personBGrad)" stroke="rgba(255,255,255,0.2)" stroke-width="1.5" />
                        <path d="M 233 48 C 233 38 257 38 257 48" fill="#0f172a" opacity="0.6" />
                        <path d="M 222 108 C 222 82 268 82 268 108 Z" fill="url(#personBGrad)" />
                        <path d="M 232 86 Q 205 88 188 92" stroke="url(#personBGrad)" stroke-width="8" stroke-linecap="round" />
                        <rect x="152" y="78" width="36" height="30" rx="5" fill="url(#giftGrad)" stroke="#a7f3d0" stroke-width="1.5" />
                        <line x1="170" y1="78" x2="170" y2="108" stroke="#ffffff" stroke-width="2.5" />
                        <line x1="152" y1="93" x2="188" y2="93" stroke="#ffffff" stroke-width="2.5" />
                        <ellipse cx="166" cy="76" rx="4" ry="2.5" fill="#ffffff" />
                        <ellipse cx="174" cy="76" rx="4" ry="2.5" fill="#ffffff" />
                        <g transform="translate(170, 150)">
                            <rect x="-85" y="-14" width="170" height="28" rx="14" fill="#059669" />
                            <text x="0" y="5" fill="#ffffff" font-family="'Outfit', sans-serif" font-size="12" font-weight="800" letter-spacing="1" text-anchor="middle">CLAIM &amp; REUNITE</text>
                        </g>
                    </svg>
                </div>
                <div class="story-card-body">
                    <h3 class="story-card-title">Claim &amp; Reunite</h3>
                    <p class="story-card-desc">Hand over found possessions safely to confirmed owners with verified claim proof.</p>
                    <a href="browse.php?type=claimed" class="story-card-link text-reunited">See Reunited Items &rarr;</a>
                </div>
            </div>
        </div>

        <!-- Live Statistics Counter -->
        <div class="stats-container">
            <div class="stat-card">
                <span class="stat-number"><?= $stats['total'] ?></span>
                <span class="stat-label">Total Reports</span>
            </div>
            <div class="stat-card">
                <span class="stat-number" style="color: var(--lost-color);"><?= $stats['lost'] ?></span>
                <span class="stat-label">Active Lost</span>
            </div>
            <div class="stat-card">
                <span class="stat-number" style="color: var(--found-color);"><?= $stats['found'] ?></span>
                <span class="stat-label">Active Found</span>
            </div>
            <div class="stat-card">
                <span class="stat-number" style="color: var(--primary);"><?= $stats['resolved'] ?></span>
                <span class="stat-label">Reunited / Resolved</span>
            </div>
        </div>
    </div>
</section>

<!-- Main Feed Container -->
<main class="main-wrapper">
    <!-- Category Quick Filters -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
        <h2 style="font-size: 1.4rem; font-weight: 800;">Explore by Category</h2>
        <a href="browse.php" style="color: var(--primary); font-weight: 600; font-size: 0.9rem;">View All Catalog &rarr;</a>
    </div>

    <div class="category-pills">
        <a href="browse.php" class="category-pill active">
            All Categories
        </a>
        <?php foreach ($categories as $slug => $cat): ?>
            <a href="browse.php?category=<?= urlencode($slug) ?>" class="category-pill">
                <?= htmlspecialchars($cat['label']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Section Title & Filter Tabs -->
    <div class="filter-bar">
        <div class="type-tabs">
            <a href="browse.php" class="tab-btn active">All Recent</a>
            <a href="browse.php?type=lost" class="tab-btn">Lost Items</a>
            <a href="browse.php?type=found" class="tab-btn">Found Items</a>
            <a href="browse.php?type=claimed" class="tab-btn">Resolved</a>
        </div>
        <div class="sort-wrapper">
            <span>Showing recent activity</span>
        </div>
    </div>

    <!-- Items Grid -->
    <div class="items-grid">
        <?php if (empty($recentItems)): ?>
            <div class="empty-state">
                <div class="empty-state-icon"><?= get_svg_icon('inbox', 'svg-icon', 48) ?></div>
                <h3>No Items Reported Yet</h3>
                <p>Be the first to publish a lost or found report in your area.</p>
                <a href="report.php" class="btn btn-primary" style="margin-top: 1rem;">Report an Item</a>
            </div>
        <?php else: ?>
            <?php foreach ($recentItems as $item): ?>
                <div class="item-card">
                    <div class="card-img-wrapper">
                        <?php if (!empty($item['image_path']) && file_exists(UPLOAD_DIR . $item['image_path'])): ?>
                            <img src="<?= UPLOAD_WEB_PATH . htmlspecialchars($item['image_path']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="card-img">
                        <?php else: ?>
                            <div class="card-placeholder-icon" style="color: var(--text-muted); display: flex; align-items: center; justify-content: center; height: 180px; background: var(--bg-body);">
                                <?= get_svg_icon('package', 'svg-icon', 48) ?>
                            </div>
                        <?php endif; ?>

                        <!-- Status Badges -->
                        <?php if ($item['status'] === 'claimed' || $item['status'] === 'returned'): ?>
                            <span class="badge badge-claimed">Resolved</span>
                        <?php elseif ($item['type'] === 'lost'): ?>
                            <span class="badge badge-lost">Lost</span>
                        <?php else: ?>
                            <span class="badge badge-found">Found</span>
                        <?php endif; ?>

                        <span class="category-tag">
                            <?= htmlspecialchars(get_category_label($item['category'])) ?>
                        </span>
                    </div>

                    <div class="card-body">
                        <h3 class="card-title">
                            <a href="item.php?id=<?= $item['id'] ?>"><?= htmlspecialchars($item['title']) ?></a>
                        </h3>
                        <p class="card-desc"><?= htmlspecialchars($item['description']) ?></p>

                        <div class="card-meta">
                            <div class="meta-item">
                                <span class="meta-icon"><?= get_svg_icon('map-pin', 'svg-icon', 14) ?></span>
                                <span><?= htmlspecialchars($item['location']) ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-icon"><?= get_svg_icon('calendar', 'svg-icon', 14) ?></span>
                                <span><?= htmlspecialchars($item['item_date']) ?> (<?= time_ago($item['created_at']) ?>)</span>
                            </div>
                        </div>

                        <div class="card-actions">
                            <a href="item.php?id=<?= $item['id'] ?>" class="btn btn-secondary btn-sm" style="flex: 1;">
                                View Details &rarr;
                            </a>
                            <?php if ($item['status'] === 'active' && $item['type'] === 'found'): ?>
                                <a href="item.php?id=<?= $item['id'] ?>#claim" class="btn btn-primary btn-sm">
                                    Claim
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Bottom Call-to-Action -->
    <div style="text-align: center; margin-top: 3rem; padding: 2.5rem; background: var(--bg-card); border-radius: var(--radius-md); border: 1px solid var(--border-color); box-shadow: var(--shadow-sm);">
        <h3 style="font-size: 1.5rem; font-weight: 800; margin-bottom: 0.5rem; color: var(--text-main);">Can't find what you are looking for?</h3>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Use our multi-filter search catalog or report a missing belonging directly.</p>
        <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="browse.php" class="btn btn-secondary">Open Advanced Search</a>
            <a href="report.php" class="btn btn-primary">+ Post a New Report</a>
        </div>
    </div>
</main>

<?php render_footer(); ?>
