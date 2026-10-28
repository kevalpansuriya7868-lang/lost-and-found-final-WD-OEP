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

        <!-- Ambient Laid-Down Items Collage (Dropped Belongings Theme Art) -->
        <div class="scattered-items-stage" aria-hidden="true">
            <!-- 1. Dropped Leather Wallet (Tilted -14deg) -->
            <div class="dropped-item item-wallet" title="Lost Wallet">
                <svg class="dropped-svg" viewBox="0 0 280 180" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="dropWalletLeather" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#b45309" />
                            <stop offset="40%" stop-color="#854d0e" />
                            <stop offset="100%" stop-color="#451a03" />
                        </linearGradient>
                        <linearGradient id="dropCashGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#10b981" />
                            <stop offset="100%" stop-color="#047857" />
                        </linearGradient>
                        <filter id="walletShadow" x="-20%" y="-20%" width="150%" height="160%">
                            <feDropShadow dx="0" dy="16" stdDeviation="14" flood-color="#000000" flood-opacity="0.55" />
                        </filter>
                    </defs>
                    <g filter="url(#walletShadow)">
                        <!-- Cash slipping out -->
                        <rect x="70" y="22" width="115" height="42" rx="4" fill="url(#dropCashGrad)" transform="rotate(-6 130 43)" />
                        <circle cx="128" cy="40" r="8" stroke="#a7f3d0" stroke-width="1.5" fill="none" transform="rotate(-6 130 43)" />
                        <!-- Cards slipping out -->
                        <rect x="55" y="36" width="90" height="50" rx="5" fill="#0284c7" transform="rotate(-12 100 61)" />
                        <rect x="110" y="32" width="95" height="50" rx="5" fill="#f59e0b" transform="rotate(8 157 57)" />
                        <!-- Wallet main body -->
                        <rect x="35" y="48" width="195" height="105" rx="14" fill="url(#dropWalletLeather)" stroke="rgba(255,255,255,0.2)" stroke-width="1.5" />
                        <!-- Stitching lines -->
                        <rect x="41" y="54" width="183" height="93" rx="10" fill="none" stroke="#fde68a" stroke-width="1.2" stroke-dasharray="5 3" opacity="0.6" />
                        <!-- Inner fold flap -->
                        <path d="M 180 75 L 225 75 A 12 12 0 0 1 225 105 L 180 105 Z" fill="#451a03" stroke="#fde68a" stroke-width="1" stroke-dasharray="4 2" />
                        <circle cx="212" cy="90" r="6" fill="#fbbf24" stroke="#d97706" stroke-width="1.5" />
                        <!-- Tape label on wallet: "lost something" -->
                        <g transform="translate(115, 115) rotate(-3)">
                            <rect x="-65" y="-13" width="130" height="26" rx="4" fill="#fffbeb" stroke="#d97706" stroke-width="1" />
                            <rect x="-60" y="-13" width="12" height="6" fill="rgba(245, 158, 11, 0.4)" />
                            <rect x="48" y="-13" width="12" height="6" fill="rgba(245, 158, 11, 0.4)" />
                            <text x="0" y="5" fill="#92400e" font-family="'Outfit', sans-serif" font-size="12" font-weight="800" letter-spacing="0.5" text-anchor="middle">lost something</text>
                        </g>
                    </g>
                </svg>
            </div>

            <!-- 2. Dropped Mobile Phone (Tilted 16deg) -->
            <div class="dropped-item item-phone" title="Found Mobile">
                <svg class="dropped-svg" viewBox="0 0 240 280" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="dropPhoneBody" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#334155" />
                            <stop offset="100%" stop-color="#0f172a" />
                        </linearGradient>
                        <linearGradient id="dropPhoneScreen" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#0369a1" />
                            <stop offset="50%" stop-color="#0284c7" />
                            <stop offset="100%" stop-color="#0c4a6e" />
                        </linearGradient>
                        <filter id="phoneShadow" x="-20%" y="-20%" width="150%" height="160%">
                            <feDropShadow dx="0" dy="18" stdDeviation="15" flood-color="#000000" flood-opacity="0.6" />
                        </filter>
                    </defs>
                    <g filter="url(#phoneShadow)">
                        <!-- Phone casing -->
                        <rect x="45" y="25" width="140" height="225" rx="24" fill="url(#dropPhoneBody)" stroke="rgba(255,255,255,0.25)" stroke-width="2" />
                        <!-- Screen glass -->
                        <rect x="52" y="33" width="126" height="209" rx="18" fill="url(#dropPhoneScreen)" />
                        <!-- Speaker / camera notch -->
                        <rect x="95" y="40" width="40" height="7" rx="3.5" fill="#0f172a" />
                        <!-- Screen radar circles -->
                        <circle cx="115" cy="115" r="38" stroke="#38bdf8" stroke-width="1.5" stroke-dasharray="4 3" opacity="0.4" />
                        <circle cx="115" cy="115" r="22" stroke="#38bdf8" stroke-width="1.5" opacity="0.7" />
                        <!-- Map locator pin on screen -->
                        <path d="M 115 102 C 109 102 105 106 105 112 C 105 120 115 127 115 127 C 115 127 125 120 125 112 C 125 106 121 102 115 102 Z" fill="#38bdf8" />
                        <circle cx="115" cy="111" r="3" fill="#0f172a" />
                        <!-- Notification banner on screen with "find it" -->
                        <g transform="translate(115, 175)">
                            <rect x="-52" y="-16" width="104" height="32" rx="16" fill="rgba(15, 23, 42, 0.9)" stroke="#38bdf8" stroke-width="1.5" />
                            <circle cx="-34" cy="0" r="5" fill="#38bdf8" />
                            <text x="6" y="5" fill="#ffffff" font-family="'Outfit', sans-serif" font-size="13" font-weight="800" letter-spacing="0.5" text-anchor="middle">find it</text>
                        </g>
                        <!-- Home indicator bar -->
                        <rect x="95" y="230" width="40" height="3" rx="1.5" fill="#ffffff" opacity="0.6" />
                    </g>
                </svg>
            </div>

            <!-- 3. Dropped Polaroid of One Person Returning Item to Another Person (Tilted -8deg) -->
            <div class="dropped-item item-return" title="Reunited Item">
                <svg class="dropped-svg" viewBox="0 0 280 230" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="dropReturnGradA" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#38bdf8" />
                            <stop offset="100%" stop-color="#0284c7" />
                        </linearGradient>
                        <linearGradient id="dropReturnGradB" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#a78bfa" />
                            <stop offset="100%" stop-color="#7c3aed" />
                        </linearGradient>
                        <linearGradient id="dropBoxGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#34d399" />
                            <stop offset="100%" stop-color="#059669" />
                        </linearGradient>
                        <filter id="returnShadow" x="-20%" y="-20%" width="150%" height="160%">
                            <feDropShadow dx="0" dy="16" stdDeviation="14" flood-color="#000000" flood-opacity="0.55" />
                        </filter>
                    </defs>
                    <g filter="url(#returnShadow)">
                        <!-- Polaroid Photo Frame / Laid Card -->
                        <rect x="25" y="15" width="230" height="195" rx="12" fill="#1e293b" stroke="rgba(255,255,255,0.2)" stroke-width="1.5" />
                        <rect x="110" y="8" width="60" height="15" rx="2" fill="rgba(99, 102, 241, 0.45)" transform="rotate(-2 140 15)" />
                        <!-- Inner photo canvas -->
                        <rect x="35" y="25" width="210" height="135" rx="8" fill="#0f172a" />
                        <!-- Overhead gratitude stars -->
                        <path d="M 140 40 L 142 46 L 148 48 L 142 50 L 140 56 L 138 50 L 132 48 L 138 46 Z" fill="#fbbf24" />
                        <circle cx="118" cy="46" r="2" fill="#38bdf8" />
                        <circle cx="162" cy="46" r="2" fill="#a78bfa" />
                        <!-- Person 1 (Returning/Finder on Left) -->
                        <circle cx="75" cy="62" r="14" fill="url(#dropReturnGradA)" />
                        <path d="M 64 58 C 64 50 86 50 86 58" fill="#0f172a" opacity="0.6" />
                        <path d="M 55 110 C 55 88 95 88 95 110 Z" fill="url(#dropReturnGradA)" />
                        <!-- Arm extending toward center holding item -->
                        <path d="M 88 92 Q 112 94 125 97" stroke="url(#dropReturnGradA)" stroke-width="7" stroke-linecap="round" />
                        <!-- Person 2 (Receiving Owner on Right) -->
                        <circle cx="205" cy="62" r="14" fill="url(#dropReturnGradB)" />
                        <path d="M 194 58 C 194 50 216 50 216 58" fill="#0f172a" opacity="0.6" />
                        <path d="M 185 110 C 185 88 225 88 225 110 Z" fill="url(#dropReturnGradB)" />
                        <!-- Arm extending to receive -->
                        <path d="M 192 92 Q 168 94 155 97" stroke="url(#dropReturnGradB)" stroke-width="7" stroke-linecap="round" />
                        <!-- Item Box in Middle Being Handed Over -->
                        <rect x="124" y="85" width="32" height="26" rx="4" fill="url(#dropBoxGrad)" stroke="#a7f3d0" stroke-width="1.2" />
                        <line x1="140" y1="85" x2="140" y2="111" stroke="#ffffff" stroke-width="2" />
                        <line x1="124" y1="98" x2="156" y2="98" stroke="#ffffff" stroke-width="2" />
                        <!-- Caption at bottom of photo -->
                        <text x="140" y="185" fill="#e2e8f0" font-family="'Outfit', sans-serif" font-size="13" font-weight="700" letter-spacing="0.5" text-anchor="middle">returning item to owner</text>
                    </g>
                </svg>
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
