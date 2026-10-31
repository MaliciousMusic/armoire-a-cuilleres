<?php
/* Le contenu du site : réglages de la boutique, carte, équipe, horaires, données structurées. */
declare(strict_types=1);

/** Réglages par défaut (modifiables dans /admin → Réglages) */
function default_settings(): array
{
    $h = fn(int $a, int $b, int $c = 0, int $d = 0) => [[$a * 60 + $c, $b * 60 + $d]];
    return [
        'shop' => [
            'name' => 'L’Armoire à Cuillères',
            'phone' => '04 63 22 63 56',
            'email' => 'hello@larmoireacuilleres.com',
            'street' => '11 rue des Chaussetiers',
            'postal' => '63000',
            'city' => 'Clermont-Ferrand',
            'lat' => 45.778325,
            'lng' => 3.0841183,
            'maps_url' => 'https://www.google.com/maps/place/L%27Armoire+%C3%A0+cuill%C3%A8res/@45.778325,3.0841183,17z/data=!4m6!3m5!1s0x47f71bdd60ae5327:0x1307a65303df46c3!8m2!3d45.778325!4d3.0841183',
            'owner' => '[Nom du propriétaire ou de la société]',
            'siren' => '[SIREN]',
            'since' => 2013,
        ],
        'social' => [
            'instagram' => 'https://www.instagram.com/larmoireacuilleres/',
            'facebook' => 'https://www.facebook.com/larmoireacuilleres',
            'tiktok' => 'https://www.tiktok.com/@larmoireacuilleres',
        ],
        // 0 = dimanche … 6 = samedi ; plages en minutes depuis minuit
        'hours' => [
            'days' => [$h(11, 19), [], $h(13, 19), $h(13, 19), $h(13, 19), $h(13, 19), $h(13, 19)],
            'closures' => [], // [{"from":"2026-12-24","to":"2026-12-26","label":{"fr":"Noël"}}]
        ],
        'brunch' => [
            'enabled' => true,
            'price_cents' => 2300,
            'day' => 0,
            'from' => '11h30',
        ],
        'loyalty' => [
            'goal' => 10,
            'reward' => ['fr' => 'une boisson chaude et une pâtisserie offertes', 'en' => 'a free hot drink and pastry', 'de' => 'ein Heißgetränk und ein Gebäck gratis', 'es' => 'una bebida caliente y un pastel gratis', 'zh' => '免费热饮一杯和甜点一份'],
            'cooldown_min' => 120,
        ],
        'orders' => [
            'enabled' => true,
            'lead_hours' => 48,
        ],
        'rating' => ['value' => 4.6, 'count' => 600, 'show' => true],
        'announcement' => ['fr' => '', 'en' => '', 'de' => '', 'es' => '', 'zh' => ''],
    ];
}

/** Un réglage, complété par sa valeur par défaut */
function conf(string $k): array
{
    $d = default_settings()[$k] ?? [];
    $v = setting($k, []);
    return is_array($v) ? array_replace($d, $v) : $d;
}

function seed_content(PDO $pdo): void
{
    if ((int) $pdo->query('SELECT COUNT(*) FROM menu_sections')->fetchColumn() > 0) return;
    $data = require APP_DIR . '/lib/seed-data.php';
    $j = fn($v) => json_encode($v, JSON_UNESCAPED_UNICODE);
    $ps = $pdo->prepare('INSERT INTO menu_sections (position, title, note, price_cents, visible) VALUES (?, ?, ?, ?, 1)');
    $pi = $pdo->prepare('INSERT INTO menu_items (section_id, position, name, descr, price_cents, is_extra, tags, visible) VALUES (?, ?, ?, ?, ?, ?, ?, 1)');
    foreach ($data['menu'] as $i => $s) {
        $ps->execute([$i * 10, $j($s['t']), isset($s['note']) ? $j($s['note']) : null, $s['p'] ?? null]);
        $sid = (int) $pdo->lastInsertId();
        foreach ($s['items'] as $k => $it) {
            $tags = $it['tags'] ?? '';
            $pi->execute([$sid, $k * 10, $j($it['n']), isset($it['d']) ? $j($it['d']) : null, $it['p'] ?? null, str_contains($tags, 'extra') ? 1 : 0, $tags]);
        }
    }
    $pt = $pdo->prepare('INSERT INTO team (position, name, role, bio, tint, visible) VALUES (?, ?, ?, ?, ?, 1)');
    foreach ($data['team'] as $i => $m) $pt->execute([$i * 10, $m['name'], $j($m['role']), $j($m['bio']), $m['tint']]);
}

/** La carte : sections visibles et leurs articles visibles */
function menu(bool $all = false): array
{
    $sections = q('SELECT * FROM menu_sections' . ($all ? '' : ' WHERE visible = 1') . ' ORDER BY position, id')->fetchAll();
    $items = q('SELECT * FROM menu_items' . ($all ? '' : ' WHERE visible = 1') . ' ORDER BY position, id')->fetchAll();
    $by = [];
    foreach ($items as $it) $by[$it['section_id']][] = $it;
    foreach ($sections as &$s) $s['items'] = $by[$s['id']] ?? [];
    return array_values(array_filter($sections, fn($s) => $all || $s['items']));
}

function team(bool $all = false): array
{
    return q('SELECT * FROM team' . ($all ? '' : ' WHERE visible = 1') . ' ORDER BY position, id')->fetchAll();
}

/** Les horaires pour le navigateur (statut « ouvert / fermé » calculé côté visiteur : la page reste en cache) */
function hours_payload(): array
{
    $h = conf('hours');
    return ['days' => $h['days'], 'closures' => array_map(fn($c) => ['from' => $c['from'], 'to' => $c['to']], $h['closures'] ?? [])];
}

function fmt_time(int $m, string $l): string
{
    $hh = intdiv($m, 60);
    $mm = $m % 60;
    return match ($l) {
        'en' => ($hh % 12 ?: 12) . ($mm ? ':' . str_pad((string) $mm, 2, '0', STR_PAD_LEFT) : '') . ($hh < 12 ? 'am' : 'pm'),
        'de', 'zh' => sprintf('%d:%02d', $hh, $mm),
        'es' => sprintf('%d:%02d', $hh, $mm),
        default => $hh . 'h' . ($mm ? str_pad((string) $mm, 2, '0', STR_PAD_LEFT) : ''),
    };
}

/** Lignes d'horaires regroupées : [["Mardi – samedi", "13h – 19h"], …] (lundi en premier) */
function hours_lines(string $l): array
{
    $days = conf('hours')['days'];
    $names = explode(',', t('days.long'));
    $order = [1, 2, 3, 4, 5, 6, 0];
    $fmt = fn($slots) => $slots ? implode(' · ', array_map(fn($s) => fmt_time($s[0], $l) . ' – ' . fmt_time($s[1], $l), $slots)) : t('hours.closed');
    $lines = [];
    $i = 0;
    while ($i < 7) {
        $j = $i;
        while ($j + 1 < 7 && $days[$order[$j + 1]] == $days[$order[$i]]) $j++;
        $label = $i === $j ? $names[$order[$i]] : $names[$order[$i]] . ' – ' . $names[$order[$j]];
        $lines[] = [mb_strtoupper(mb_substr($label, 0, 1)) . mb_substr($label, 1), $fmt($days[$order[$i]])];
        $i = $j + 1;
    }
    return $lines;
}

/** Les jours d'ouverture en deux morceaux pour le hero : ["Du mardi", "au dimanche"] (ou « Ouvert / tous les jours ») */
function open_days_phrase(string $l): array
{
    $days = conf('hours')['days'];
    $names = explode(',', t('days.long'));
    $open = array_map(fn($d) => (bool) $days[$d], range(0, 6));
    if (!in_array(false, $open, true)) return [t('hero.rot.all.a'), t('hero.rot.all.b')];
    if (!in_array(true, $open, true)) return [t('hours.title'), t('hours.closed')];
    // une seule plage, éventuellement à cheval sur la fin de semaine : elle commence après un jour fermé
    $starts = array_values(array_filter(range(0, 6), fn($d) => $open[$d] && !$open[($d + 6) % 7]));
    if (count($starts) === 1) {
        $a = $starts[0];
        $b = $a;
        while ($open[($b + 1) % 7]) $b = ($b + 1) % 7;
        return [t('hero.rot.days.a', ['a' => $names[$a]]), t('hero.rot.days.b', ['b' => $names[$b]])];
    }
    return [t('hours.title'), implode(', ', array_map(fn($d) => $names[$d], array_keys(array_filter($open))))];
}

function shop_address_line(): string
{
    $s = conf('shop');
    return $s['street'] . ', ' . $s['postal'] . ' ' . $s['city'];
}

function phone_intl(string $p): string
{
    $d = preg_replace('/\D/', '', $p) ?? '';
    return str_starts_with($d, '0') ? '+33' . substr($d, 1) : '+' . $d;
}

/** Les trajets à pied (OpenStreetMap, routing.openstreetmap.de, calculés le 31 oct. 2026) */
function walking_routes(): array
{
    return [
        ['id' => 'victoire', 'm' => 140, 'min' => 2, 'at' => [3.0855611, 45.7780433],
            'path' => [[3.085456, 45.778042], [3.085453, 45.778347], [3.085403, 45.778347], [3.084973, 45.778345], [3.084894, 45.778344], [3.084484, 45.778352], [3.084218, 45.778349], [3.084118, 45.778349]]],
        ['id' => 'jaude', 'm' => 380, 'min' => 5, 'at' => [3.0821365, 45.7764128],
            'path' => [[3.08205, 45.776401], [3.08199, 45.776609], [3.082444, 45.776668], [3.082324, 45.777091], [3.08245, 45.777102], [3.082461, 45.777215], [3.08278, 45.777245], [3.083001, 45.777302], [3.083066, 45.777304], [3.083576, 45.77737], [3.083684, 45.777355], [3.083737, 45.777431], [3.083701, 45.7776], [3.083763, 45.777674], [3.08374, 45.777782], [3.083638, 45.778204], [3.08363, 45.778362], [3.083984, 45.778348], [3.084118, 45.778349]]],
        ['id' => 'gaillard', 'm' => 310, 'min' => 4, 'at' => [3.0828393, 45.7803214],
            'path' => [[3.082839, 45.780323], [3.082964, 45.780335], [3.083004, 45.780199], [3.083076, 45.78021], [3.083091, 45.780075], [3.083085, 45.779988], [3.08306, 45.779904], [3.083051, 45.779738], [3.083279, 45.779709], [3.083287, 45.779601], [3.083325, 45.779082], [3.083368, 45.77889], [3.083393, 45.778728], [3.083468, 45.77873], [3.083466, 45.778425], [3.08361, 45.778408], [3.08363, 45.778362], [3.083984, 45.778348], [3.084118, 45.778349]]],
    ];
}

/* ---------------------------------------------------------------- Données structurées (schema.org) */

function jsonld_business(string $l): array
{
    $s = conf('shop');
    $so = conf('social');
    $base = config()['base_url'];
    $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $spec = [];
    foreach (conf('hours')['days'] as $d => $slots) {
        foreach ($slots as $slot) {
            $spec[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => 'https://schema.org/' . $dayNames[$d],
                'opens' => sprintf('%02d:%02d', intdiv($slot[0], 60), $slot[0] % 60), 'closes' => sprintf('%02d:%02d', intdiv($slot[1], 60), $slot[1] % 60)];
        }
    }
    foreach (conf('hours')['closures'] ?? [] as $c) {
        $spec[] = ['@type' => 'OpeningHoursSpecification', 'validFrom' => $c['from'], 'validThrough' => $c['to'], 'opens' => '00:00', 'closes' => '00:00'];
    }
    $sections = [];
    foreach (menu() as $sec) {
        $items = [];
        foreach ($sec['items'] as $it) {
            $price = $it['price_cents'] ?? $sec['price_cents'];
            $item = ['@type' => 'MenuItem', 'name' => tr($it['name'], $l)];
            if ($it['descr']) $item['description'] = tr($it['descr'], $l);
            if ($price !== null) $item['offers'] = ['@type' => 'Offer', 'price' => number_format($price / 100, 2, '.', ''), 'priceCurrency' => 'EUR'];
            if (str_contains($it['tags'], 'vegan')) $item['suitableForDiet'] = 'https://schema.org/VeganDiet';
            $items[] = $item;
        }
        $sections[] = ['@type' => 'MenuSection', 'name' => tr($sec['title'], $l), 'hasMenuItem' => $items];
    }
    $sameAs = array_values(array_filter([$so['instagram'], $so['facebook'], $so['tiktok'], $s['maps_url']]));
    return [
        '@type' => ['CafeOrCoffeeShop', 'Bakery'],
        '@id' => $base . '/#boutique',
        'name' => $s['name'],
        'alternateName' => ['L’Armoire à Cuillères', 'Armoire à Cuillères', 'L’Armoire', 'Bar à chocolat L’Armoire à Cuillères'],
        'description' => t('seo.business'),
        'slogan' => t('hero.title'),
        'url' => url('home', $l, true),
        'logo' => $base . '/assets/icons/icon-512.png',
        'image' => [$base . '/assets/img/og-armoire.jpg', $base . '/assets/img/insta/fondants.webp', $base . '/assets/img/insta/table.webp', $base . '/assets/img/insta/rue.webp'],
        'telephone' => phone_intl($s['phone']),
        'email' => $s['email'],
        'priceRange' => '€',
        'currenciesAccepted' => 'EUR',
        'paymentAccepted' => 'Cash, Credit Card',
        'servesCuisine' => explode(',', t('seo.cuisine')),
        'keywords' => t('seo.keywords'),
        'knowsAbout' => explode(',', t('seo.knows')),
        'address' => ['@type' => 'PostalAddress', 'streetAddress' => $s['street'], 'postalCode' => $s['postal'], 'addressLocality' => $s['city'], 'addressRegion' => 'Auvergne-Rhône-Alpes', 'addressCountry' => 'FR'],
        'geo' => ['@type' => 'GeoCoordinates', 'latitude' => $s['lat'], 'longitude' => $s['lng']],
        'hasMap' => $s['maps_url'],
        'areaServed' => ['@type' => 'City', 'name' => 'Clermont-Ferrand'],
        'openingHoursSpecification' => $spec,
        'acceptsReservations' => 'True',
        'smokingAllowed' => false,
        'amenityFeature' => array_map(fn($f) => ['@type' => 'LocationFeatureSpecification', 'name' => $f, 'value' => true], explode(',', t('seo.amenities'))),
        'founder' => ['@type' => 'Person', 'name' => $s['owner']],
        'foundingDate' => (string) $s['since'],
        'sameAs' => $sameAs,
        'hasMenu' => ['@type' => 'Menu', 'name' => t('menu.title'), 'url' => url('home', $l, true) . '#carte', 'inLanguage' => LANGS[$l]['hreflang'], 'hasMenuSection' => $sections],
        'potentialAction' => [
            ['@type' => 'OrderAction', 'name' => t('order.title'), 'target' => url('order', $l, true)],
        ],
    ];
}

/** Questions fréquentes (affichées et en FAQPage) */
function faq(string $l): array
{
    $out = [];
    for ($i = 1; $i <= 9; $i++) {
        $q = t("faq.q$i");
        if ($q === "faq.q$i") break;
        $out[] = [$q, t("faq.a$i", faq_vars($l))];
    }
    return $out;
}

function faq_vars(string $l): array
{
    $s = conf('shop');
    $lo = conf('loyalty');
    return ['phone' => $s['phone'], 'email' => $s['email'], 'goal' => $lo['goal'], 'reward' => tr($lo['reward'], $l),
        'lead' => conf('orders')['lead_hours'], 'brunch_price' => fmt_price(conf('brunch')['price_cents'], $l), 'address' => shop_address_line()];
}

/* ---------------------------------------------------------------- Allergènes (les 14 du règlement européen INCO) */

const ALLERGENS = ['gluten', 'crustaces', 'oeufs', 'poisson', 'arachides', 'soja', 'lait', 'fruits_coque', 'celeri', 'moutarde', 'sesame', 'sulfites', 'lupin', 'mollusques'];

/**
 * Valeurs de départ, déduites des recettes habituelles : À FAIRE VALIDER PAR LA BOUTIQUE (admin → La carte).
 * Clé : un mot du nom (français) ; valeur : les allergènes.
 */
function default_allergens(string $name, string $section): string
{
    $n = mb_strtolower($name);
    $s = mb_strtolower($section);
    $rules = [
        'praliné' => 'lait,fruits_coque', 'brownie' => 'gluten,oeufs,lait,fruits_coque', 'fondant' => 'gluten,oeufs,lait',
        'cheesecake' => 'gluten,oeufs,lait', 'tarte' => 'gluten,oeufs,lait', 'cake' => 'gluten,oeufs,lait', 'cookie' => 'gluten,oeufs,lait,soja',
        'formule enfant' => 'gluten,oeufs,lait,soja', 'arôme' => 'fruits_coque', 'lait végétal' => 'gluten,fruits_coque,soja', 'chantilly' => 'lait',
        'viennois' => 'lait', 'cappuccino' => 'lait', 'latte' => 'lait', 'mocha' => 'lait', 'matcha' => 'lait', 'chaï' => 'lait',
        'merveilleux' => 'fruits_coque', 'limoncha' => 'fruits_coque', 'chocolat' => 'lait,soja', 'mocha' => 'lait',
    ];
    foreach ($rules as $k => $v) if (str_contains($n, $k)) return $v;
    if (str_contains($s, 'chocolat')) return 'lait,soja';
    return '';
}

function seed_allergens(PDO $pdo): void
{
    $rows = $pdo->query('SELECT i.id, i.name, i.allergens, s.title FROM menu_items i JOIN menu_sections s ON s.id = i.section_id')->fetchAll();
    $up = $pdo->prepare('UPDATE menu_items SET allergens = ? WHERE id = ?');
    foreach ($rows as $r) {
        if ($r['allergens'] !== '') continue;
        $up->execute([default_allergens(tr($r['name'], 'fr'), tr($r['title'], 'fr')), $r['id']]);
    }
}

/** Couleur d'un cru (les teintes de l'ancienne appli) ; à défaut, d'après le pourcentage */
function cru_color(string $name): string
{
    $known = ['blanc ivoire' => '#EBD9BA', 'kewane' => '#B88A62', 'caramélia' => '#B67E4F', 'elianza lait' => '#A87550', 'vanuari lait' => '#9A6A47',
        'z-caramel' => '#A26637', 'elianza noir' => '#6A4027', 'vanuari noir' => '#5A3421', 'z-café' => '#4B2C1B', 'mokaya' => '#432717',
        'arcango' => '#372014', 'noir infini' => '#29160D'];
    $n = mb_strtolower($name);
    foreach ($known as $k => $c) if (str_contains($n, $k)) return $c;
    $pc = preg_match('/(\d{2})\s?%/', $name, $m) ? (int) $m[1] : 60;
    $t = max(0, min(1, ($pc - 30) / 70));
    $mix = fn($a, $b) => (int) round($a + ($b - $a) * $t);
    return sprintf('#%02X%02X%02X', $mix(0xEB, 0x29), $mix(0xD9, 0x16), $mix(0xBA, 0x0D));
}

/** Les crus d'une rubrique : nom, %, notes, couleur, groupe (0 doux, 1 équilibrés, 2 intenses) */
function crus_of(array $sec, string $l): array
{
    $out = [];
    foreach ($sec['items'] as $it) {
        $fr = tr($it['name'], 'fr');
        if (!preg_match('/^(.*?)\s*(\d{2})\s?%/u', tr($it['name'], $l), $m) && !preg_match('/^(.*?)\s*(\d{2})\s?%/u', $fr, $m)) continue;
        $pc = (int) $m[2];
        $out[] = ['id' => (int) $it['id'], 'name' => trim($m[1]), 'pc' => $pc, 'notes' => tr($it['descr'] ?? '', $l), 'color' => cru_color($fr),
            'bio' => str_contains($it['tags'], 'bio'), 'group' => $pc < 40 ? 0 : ($pc < 64 ? 1 : 2), 'allergens' => $it['allergens'] ?? '', 'visible' => (int) $it['visible']];
    }
    return $out;
}

/* ---------------------------------------------------------------- L'ardoise : les pâtisseries du jour (mise à jour par l'équipe dans /admin) */

function ardoise_default(): array
{
    $it = fn(string $n, int $p, string $al) => ['name' => ['fr' => $n], 'price_cents' => $p, 'allergens' => $al];
    return [
        'visible' => true,
        'date' => null,
        'note' => ['fr' => 'Fait maison par [prénoms de l’équipe]', 'en' => 'Homemade by [team first names]', 'de' => 'Hausgemacht von [Vornamen des Teams]', 'es' => 'Hecho en casa por [nombres del equipo]', 'zh' => '由[团队成员名字]手工制作'],
        'items' => [
            $it('Fondant lait caramélisé', 550, 'gluten,oeufs,lait'),
            $it('Fondant noir pointe de sel', 550, 'gluten,oeufs,lait'),
            $it('Brownie noix, noisettes', 550, 'gluten,oeufs,lait,fruits_coque'),
            $it('Cheesecake citron vert', 550, 'gluten,oeufs,lait'),
            $it('Tarte citron meringuée', 520, 'gluten,oeufs,lait'),
            $it('Cake marbré chocolat', 490, 'gluten,oeufs,lait'),
            $it('Cookie pépites de chocolat', 390, 'gluten,oeufs,lait,soja'),
        ],
    ];
}

function ardoise(): array
{
    $a = setting('ardoise');
    return is_array($a) ? array_replace(ardoise_default(), $a) : ardoise_default();
}

/** « Ardoise du 31/10 » selon la langue */
function ardoise_date(?string $iso, string $l): string
{
    if (!$iso || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $iso, $m)) return '';
    $d = $l === 'zh' ? (int) $m[2] . '月' . (int) $m[3] . '日' : ($l === 'en' ? $m[3] . '/' . $m[2] : $m[3] . '/' . $m[2]);
    return t('ardoise.date', ['date' => $d]);
}
