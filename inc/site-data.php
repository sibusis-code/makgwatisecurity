<?php
/**
 * Shared CMS data loader for the public site.
 * Site settings have hardcoded fallbacks (today's real copy) so a
 * transient DB hiccup never blanks the nav/hero/footer. The content
 * collections (services, courses, testimonials, branches, faqs)
 * degrade to an empty array on DB failure — the page renders without
 * that section rather than fataling.
 */
require_once __DIR__ . '/../admin/db.php';

function site_settings(): array {
    static $settings = null;
    if ($settings !== null) return $settings;
    $defaults = [
        'whatsapp_number'       => '27790260098',
        'phone_head_office'     => '015 001 2295',
        'head_office_contact'   => 'Ally',
        'psira_number'          => '4464345',
        'training_number'       => '4333959',
        'saps_number'           => '4001370',
        'pftc_number'           => 'T2311004',
        'professionals_trained' => '500+',
        'training_pass_rate'    => '95%',
        'branch_count'          => '6',
        'hero_badge'            => 'PSIRA Certified Elite Security',
        'hero_title'            => 'Protect What <span class="highlight">Matters Most</span> With Elite Security',
        'hero_subtitle'         => 'Makgwati Security delivers world-class protection services across South Africa — from VIP close protection and armed response to professional PSIRA-certified training.',
        'footer_description'    => 'Elite security solutions for individuals, businesses, and organisations across South Africa. PSIRA registered and fully certified.',
        'copyright_name'        => 'Makgwati Security',
    ];
    try {
        $rows = db()->query('SELECT `key`, `value` FROM site_settings')->fetchAll();
        foreach ($rows as $r) $defaults[$r['key']] = $r['value'];
    } catch (Throwable $e) { /* fall back to defaults above */ }
    return $settings = $defaults;
}

function setting(string $key, string $default = ''): string {
    $s = site_settings();
    return $s[$key] ?? $default;
}

function get_services(bool $onlyActive = true): array {
    try {
        $sql = 'SELECT * FROM services' . ($onlyActive ? ' WHERE active = 1' : '') . ' ORDER BY sort_order ASC, id ASC';
        return db()->query($sql)->fetchAll();
    } catch (Throwable $e) { return []; }
}

function get_training_courses(): array {
    try { return db()->query('SELECT * FROM training_courses WHERE active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll(); }
    catch (Throwable $e) { return []; }
}

function get_testimonials(): array {
    try { return db()->query('SELECT * FROM testimonials WHERE active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll(); }
    catch (Throwable $e) { return []; }
}

function get_branches(): array {
    try { return db()->query('SELECT * FROM branches ORDER BY sort_order ASC, id ASC')->fetchAll(); }
    catch (Throwable $e) { return []; }
}

function get_head_office(): ?array {
    foreach (get_branches() as $b) {
        if (!empty($b['is_head_office'])) return $b;
    }
    return null;
}

function get_faqs_json(): string {
    try {
        $rows = db()->query('SELECT keywords, answer_html FROM faqs WHERE active = 1 ORDER BY sort_order ASC, id ASC')->fetchAll();
        return json_encode($rows, JSON_UNESCAPED_SLASHES);
    } catch (Throwable $e) { return '[]'; }
}

function get_published_posts(?int $limit = null): array {
    try {
        $sql = 'SELECT * FROM blog_posts WHERE status = "published" AND published_at <= NOW() ORDER BY published_at DESC';
        if ($limit !== null) $sql .= ' LIMIT ' . (int)$limit;
        return db()->query($sql)->fetchAll();
    } catch (Throwable $e) { return []; }
}

function get_post_by_slug(string $slug): ?array {
    try {
        $stmt = db()->prepare('SELECT * FROM blog_posts WHERE slug = :slug AND status = "published" AND published_at <= NOW() LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    } catch (Throwable $e) { return null; }
}

function wa_link(string $number, string $text = ''): string {
    $url = 'https://wa.me/' . preg_replace('/\D/', '', $number);
    if ($text !== '') $url .= '?text=' . rawurlencode($text);
    return $url;
}
