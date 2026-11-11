<?php
/* Men + Women support.
 * services.audience = 'women' | 'men' | 'unisex'
 * - fresh install: column comes from schema, seed calls add_men_services()
 * - existing database: migrate_audience() adds the column and men's services once (automatic, safe to re-run)
 */
function add_men_services(PDO $db): void {
    $has = $db->query("SELECT 1 FROM services WHERE audience='men' LIMIT 1");
    if ($has && $has->fetchColumn()) return;
    require_once __DIR__ . '/images.php';
    $U = 'https://images.unsplash.com/photo-'; $q = '?auto=format&fit=crop&w=900&q=80';
    $sv = $db->prepare('INSERT INTO services (name,category,description,benefits,price,duration,image,icon,audience) VALUES (?,?,?,?,?,?,?,?,?)');
    $list = [
      ["Men's Haircut & Styling",'Hair','Precision cut, wash and styling by our barbers, tailored to your head shape and lifestyle.','Sharp finish|Style advice|Hair wash included',1500,45,service_img("Men's Haircut & Styling"),'scissors','men'],
      ['Beard Trim & Shaping','Grooming','Expert beard sculpting with hot towel, clean line-up and conditioning oil for a defined look.','Defined lines|Hot towel|Beard oil',800,30,service_img("Beard Trim & Shaping"),'razor','men'],
      ['Classic Hot Towel Shave','Grooming','Traditional straight-razor shave with steaming towels, rich lather and a soothing aftershave.','Smooth shave|Skin soothing|Deep relaxation',1200,30,service_img("Classic Hot Towel Shave"),'razor','men'],
      ["Men's Facial & Cleanup",'Skin',"Deep-cleansing facial made for men's skin: exfoliation, de-tan, extraction and hydration.",'Deep cleanse|De-tan|Fresh skin',2500,45,service_img("Men's Facial & Cleanup"),'sparkle','men'],
      ["Men's Hair Color & Highlights",'Hair','Grey coverage, natural tones or bold highlights using gentle ammonia-free colour.','Grey coverage|Natural look|Hair-safe formula',3500,60,service_img("Men's Hair Color & Highlights"),'drop','men'],
      ['Scalp Massage & Hair Spa','Spa','Relaxing head, neck and shoulder massage with a nourishing scalp treatment.','Stress relief|Healthy scalp|Better sleep',2000,40,service_img("Scalp Massage & Hair Spa"),'lotus','men'],
      ['Groom Package','Grooming','Complete groom makeover: haircut, beard styling, facial and a relaxing massage for the big day.','Full makeover|Trial included|Priority booking',12000,120,service_img("Groom Package"),'crown','men'],
    ];
    foreach ($list as $s) $sv->execute($s);

    // who performs them (looked up by username/name, so it works with any ids)
    $assign = [
      'daniel' => ["Men's Haircut & Styling",'Beard Trim & Shaping','Classic Hot Towel Shave',"Men's Hair Color & Highlights",'Scalp Massage & Hair Spa','Groom Package'],
      'sophia' => ["Men's Haircut & Styling","Men's Hair Color & Highlights",'Groom Package'],
      'zara'   => ["Men's Facial & Cleanup",'Scalp Massage & Hair Spa'],
    ];
    $uid = $db->prepare('SELECT id FROM users WHERE username=?'); $sid = $db->prepare('SELECT id FROM services WHERE name=?');
    $chk = $db->prepare('SELECT 1 FROM stylist_services WHERE stylist_id=? AND service_id=?'); $ins = $db->prepare('INSERT INTO stylist_services (stylist_id,service_id) VALUES (?,?)');
    foreach ($assign as $user => $names) {
        $uid->execute([$user]); $u = (int)$uid->fetchColumn(); if (!$u) continue;
        foreach ($names as $n) { $sid->execute([$n]); $s = (int)$sid->fetchColumn(); if (!$s) continue;
            $chk->execute([$u, $s]); if (!$chk->fetchColumn()) $ins->execute([$u, $s]); }
    }
}

function migrate_audience(PDO $db): void {
    try { $db->query('SELECT audience FROM services LIMIT 1'); return; } catch (Throwable $e) { /* column missing -> migrate */ }
    $db->exec("ALTER TABLE services ADD COLUMN audience VARCHAR(10) NOT NULL DEFAULT 'women'");
    $u = $db->prepare("UPDATE services SET audience='unisex' WHERE name=?");
    foreach (['Manicure','Pedicure','Facial','Hair Color','Hair Spa & Treatment','Relaxing Body Massage'] as $n) $u->execute([$n]);
    add_men_services($db);
}

/* Full catalogue: every service visible, each with the right audience, plus the Japanese Head Spa.
 * Safe to run many times (looks services up by name). */
function ensure_catalog(PDO $db): void {
    require_once __DIR__ . '/images.php';
    $has = $db->prepare('SELECT id FROM services WHERE name=?');
    $ins = $db->prepare('INSERT INTO services (name,category,description,benefits,price,duration,image,icon,audience,active) VALUES (?,?,?,?,?,?,?,?,?,1)');

    // Japanese Head Spa (new)
    $has->execute(['Japanese Head Spa']);
    if (!$has->fetchColumn()) {
        $ins->execute(['Japanese Head Spa', 'Spa',
            'A traditional Japanese scalp ritual: gentle steam, deep scalp cleansing, aroma-oil massage and a slow warm-water rinse that melts away stress and refreshes your hair from the roots.',
            'Deep scalp detox|Stress relief|Healthier hair growth', 7500, 75, service_img('Japanese Head Spa'), 'lotus', 'unisex']);
    }
    // missing core women services (older databases)
    $core = [
        ['Hair Color','Hair','Global colour, highlights and balayage using ammonia-free premium colour.','Vibrant shade|Hair-safe formula|Colour consultation',8000,120,'drop','women'],
        ['Hair Spa & Treatment','Hair','Nourishing keratin spa that repairs damage, adds shine and tames frizz.','Frizz control|Deep conditioning|Shine boost',4500,75,'leaf','women'],
        ['Bridal Makeup','Makeup','Complete bridal makeup with trial consultation, airbrush finish and hair setting.','Airbrush finish|Long-wear|Trial included',25000,150,'crown','women'],
        ['Relaxing Body Massage','Spa','Full-body aromatherapy massage to melt away stress and muscle tension.','Stress relief|Better sleep|Aromatherapy',6000,60,'lotus','unisex'],
        ['Waxing & Threading','Skin','Gentle, hygienic hair removal with soothing post-care for smooth skin.','Smooth finish|Gentle formula|Quick service',1200,30,'feather','women'],
    ];
    foreach ($core as [$n,$c,$d,$b,$p,$m,$i,$a]) {
        $has->execute([$n]);
        if (!$has->fetchColumn()) $ins->execute([$n,$c,$d,$b,$p,$m,service_img($n),$i,$a]);
    }
    // men's services (adds them only if the database has none yet)
    add_men_services($db);

    // who each service is for
    $aud = ['Hair Styling'=>'women','Hair Color'=>'women','Hair Spa & Treatment'=>'women','Facial'=>'women','Bridal Makeup'=>'women','Waxing & Threading'=>'women',
            'Manicure'=>'unisex','Pedicure'=>'unisex','Relaxing Body Massage'=>'unisex','Japanese Head Spa'=>'unisex'];
    $ua = $db->prepare('UPDATE services SET audience=?, active=1 WHERE name=?');
    foreach ($aud as $n => $a) $ua->execute([$a, $n]);
    $db->exec("UPDATE services SET active=1 WHERE audience='men'");

    // stylists for the new / restored services
    $assign = ['sophia'=>['Hair Color','Hair Spa & Treatment','Bridal Makeup','Japanese Head Spa'],
               'zara'  =>['Waxing & Threading','Relaxing Body Massage','Japanese Head Spa'],
               'ayesha'=>['Manicure','Pedicure'],
               'daniel'=>['Hair Color','Hair Spa & Treatment']];
    $uid = $db->prepare('SELECT id FROM users WHERE username=?'); $sid = $db->prepare('SELECT id FROM services WHERE name=?');
    $chk = $db->prepare('SELECT 1 FROM stylist_services WHERE stylist_id=? AND service_id=?'); $lnk = $db->prepare('INSERT INTO stylist_services (stylist_id,service_id) VALUES (?,?)');
    foreach ($assign as $user => $names) {
        $uid->execute([$user]); $u = (int)$uid->fetchColumn(); if (!$u) continue;
        foreach ($names as $n) { $sid->execute([$n]); $s = (int)$sid->fetchColumn(); if (!$s) continue;
            $chk->execute([$u,$s]); if (!$chk->fetchColumn()) $lnk->execute([$u,$s]); }
    }
}
