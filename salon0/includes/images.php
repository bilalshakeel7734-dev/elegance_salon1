<?php
/* ONE place for every photo on the site. Change a URL here and it updates everywhere
 * (pages, seed data and - once - existing databases). */
const IMG_BASE = 'https://images.unsplash.com/photo-';

function img_u(string $id, int $w = 1200, int $q = 85): string {
    return IMG_BASE . $id . "?auto=format&fit=crop&w=$w&q=$q&ixlib=rb-4.0.3";
}

/* page backgrounds / banners */
function site_img(string $key, int $w = 1600): string {
    static $m = [
        'hero'      => '1560066984-138dadb4c035',  // luxury salon interior (checked)
        'about_hero'=> '1562322140-8baeececf3df',  // stylist blow-drying hair (checked)
        'about'     => '1521590832167-7bcbfaa6381f',  // salon interior
        'interior'  => '1521590832167-7bcbfaa6381f',
        'contact'   => '1521590832167-7bcbfaa6381f',
        'services'  => '1570172619644-dfd03ed5d881',  // facial treatment
        'stylists'  => '1560066984-138dadb4c035',
        'login'     => '1562322140-8baeececf3df',
        'register'  => '1570172619644-dfd03ed5d881',
        'cta'       => '1562322140-8baeececf3df',
    ];


    return img_u($m[$key] ?? $m['hero'], $w, 85);
}

/* service photos by service name (women / unisex + men).
 * Swap any id here, or drop your own file in assets/img/services/<slug>.jpg (see README.txt). */
function service_images(): array {
    return [
        // ---- women / unisex ----
        'Hair Styling'          => '1562322140-8baeececf3df',  // stylist blow-drying hair
        'Manicure'              => '1610992015732-2449b76344bc', // manicured hands
        'Pedicure'              => '1519415510236-718bdfcd89c8', // feet pedicure
        'Facial'                => '1570172619644-dfd03ed5d881', // facial mask treatment
        'Hair Color'            => '1522337360788-8b13dee7a37e', // hair colouring
        'Hair Spa & Treatment'  => '1560066984-138dadb4c035',  // luxury salon interior
        'Bridal Makeup'         => '1487412947147-5cebf100ffc2', // makeup artist
        'Relaxing Body Massage' => '1544161515-4ab6ce6db874',  // spa massage
        'Waxing & Threading'    => '1519823551278-64ac92734fb1', // spa / skin care
        'Japanese Head Spa'     => '1540555700478-4be289fbecef', // spa ritual
        // ---- men ----
        "Men's Haircut & Styling"       => '1503951914875-452162b0f3f1', // barber haircut
        'Beard Trim & Shaping'          => '1621605815971-fbc98d665033', // beard trim
        'Classic Hot Towel Shave'       => '1599351431202-1e0f0137899a', // hot towel shave
        "Men's Facial & Cleanup"        => '1570172619644-dfd03ed5d881', // facial
        "Men's Hair Color & Highlights" => '1622286342621-4bd786c2447c', // barbershop
        'Scalp Massage & Hair Spa'      => '1519823551278-64ac92734fb1', // head / scalp spa
        'Groom Package'                 => '1521590832167-7bcbfaa6381f', // salon interior
    ];
}
/* stylist portraits by username (real portraits, portrait crop) */
function stylist_images(): array {
    return [
        'sophia' => '1494790108377-be9c29b29330',
        'ayesha' => '1573496359142-b8d87734a5a2',
        'zara'   => '1438761681033-6461ffad8d80',
        'daniel' => '1500648767791-00dcc994a43e',
    ];
}
/* Drop your own photo in assets/img/services/<name>.jpg (e.g. pedicure.jpg, manicure.jpg, hair-styling.jpg, facial.jpg)
 * and it is used instead of the online one. */
function local_img(string $dir, string $name): ?string {
    $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-');
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $x) {
        if (is_file(__DIR__ . "/../assets/img/$dir/$slug.$x")) return function_exists('url') ? url("assets/img/$dir/$slug.$x") : "assets/img/$dir/$slug.$x";
    }
    return null;
}
function service_img(string $name): string { $m = service_images(); return local_img('services', $name) ?? img_u($m[$name] ?? '1562322140-8baeececf3df', 1000, 85); }
function stylist_img(string $user): string { $m = stylist_images(); return local_img('stylists', $user) ?? img_u($m[$user] ?? '1580489944761-15a19d654956', 900, 88); }

/* Runs once per database (flag file). To run it again, delete database/.images_v5
 * - makes the full catalogue visible: Women, Men and the new Japanese Head Spa
 * - fixes who each service is for, and refreshes the photos */
function migrate_images(PDO $db): void {
    $flag = __DIR__ . '/../database/.images_v5';
    if (is_file($flag)) return;
    try {
        ensure_catalog($db);   // see audience.php
        $u = $db->prepare('UPDATE services SET image=? WHERE name=?');
        foreach (service_images() as $n => $id) $u->execute([service_img($n), $n]);
        $s = $db->prepare('UPDATE stylist_profiles SET photo=? WHERE user_id=(SELECT id FROM users WHERE username=?)');
        foreach (stylist_images() as $un => $id) $s->execute([stylist_img($un), $un]);
        @file_put_contents($flag, date('c'));
    } catch (Throwable $e) { /* never break the site because of photos */ }
}
