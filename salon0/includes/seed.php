<?php
function seed_demo(PDO $db): void {
    require_once __DIR__ . '/images.php';
    $U = 'https://images.unsplash.com/photo-';
    $q = '?auto=format&fit=crop&w=900&q=80';
    $ins = $db->prepare('INSERT INTO users (name,username,email,phone,password,role,gender,address) VALUES (?,?,?,?,?,?,?,?)');
    $mk = fn($n,$u,$e,$p,$pw,$r,$g,$a='Karachi') => $ins->execute([$n,$u,$e,$p,password_hash($pw, PASSWORD_DEFAULT),$r,$g,$a]);
    $mk('Admin Elegance','admin','admin@elegance.com','0300-1111111','admin123','admin','female');
    $mk('Hina Receptionist','reception','reception@elegance.com','0300-2222222','reception123','receptionist','female');
    $mk('Sophia Khan','sophia','sophia@elegance.com','0301-3000001','stylist123','stylist','female');
    $mk('Ayesha Malik','ayesha','ayesha@elegance.com','0301-3000002','stylist123','stylist','female');
    $mk('Zara Ahmed','zara','zara@elegance.com','0301-3000003','stylist123','stylist','female');
    $mk('Daniel Raza','daniel','daniel@elegance.com','0301-3000004','stylist123','stylist','male');
    $mk('Demo Client','client','client@demo.com','0333-4444444','client123','client','female','Clifton, Karachi');
    $mk('Mariam Ali','mariam','mariam@demo.com','0333-5555555','client123','client','female','Gulshan, Karachi');
    $mk('Fatima Noor','fatima','fatima@demo.com','0333-6666666','client123','client','female','North Nazimabad, Karachi');

    $sp = $db->prepare('INSERT INTO stylist_profiles (user_id,title,specialties,bio,photo,experience,commission_rate,work_days,start_time,end_time,shift_note) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
    $sp->execute([3,'Creative Director & Hair Stylist','Hair Styling, Hair Color, Bridal',"Sophia has 12 years of experience crafting editorial and bridal hair for Karachi's most memorable occasions.",stylist_img('sophia'),12,25,'1,2,3,4,5,6','10:00','19:00','Morning + Evening']);
    $sp->execute([4,'Senior Nail Artist','Manicure, Pedicure, Nail Art','Ayesha is our nail artisan, known for flawless gel finishes and relaxing spa pedicures.',stylist_img('ayesha'),8,20,'2,3,4,5,6,7','11:00','20:00','Mid-day Shift']);
    $sp->execute([5,'Skin & Facial Specialist','Facial, Skin Care, Waxing',"Zara is a certified esthetician who customises every facial to your skin's needs.",stylist_img('zara'),6,20,'1,3,4,5,6,7','10:00','18:00','Day Shift']);
    $sp->execute([6,'Master Barber & Colorist','Hair Styling, Hair Color, Beard','Daniel blends classic barbering with modern colour techniques for flawless, lasting results.',stylist_img('daniel'),10,22,'1,2,3,4,5,6','12:00','21:00','Evening Shift']);

    $sv = $db->prepare('INSERT INTO services (name,category,description,benefits,price,duration,image,icon) VALUES (?,?,?,?,?,?,?,?)');
    $services = [
      ['Manicure','Nails','A complete hand treatment: shaping, cuticle care, exfoliation, massage and a flawless polish finish.','Soft hands|Healthy nails|Relaxing massage',1500,45,service_img('Manicure'),'hand'],
      ['Pedicure','Nails','Luxury foot spa with soak, scrub, callus care, massage and polish for beautiful, rested feet.','Smooth feet|Deep relaxation|Callus removal',2000,60,service_img('Pedicure'),'foot'],
      ['Hair Styling','Hair','Cut, blow-dry and styling tailored to your face shape, from everyday elegance to party glam.','Custom look|Heat protection|Long-lasting finish',3000,60,service_img('Hair Styling'),'scissors'],
      ['Facial','Skin','Deep-cleansing facial with steam, extraction, mask and massage to restore natural glow.','Glowing skin|Deep cleanse|Anti-ageing',3500,60,service_img('Facial'),'sparkle'],
      ['Hair Color','Hair','Global colour, highlights and balayage using ammonia-free premium colour.','Vibrant shade|Hair-safe formula|Colour consultation',8000,120,service_img('Hair Color'),'drop'],
      ['Hair Spa & Treatment','Hair','Nourishing keratin spa that repairs damage, adds shine and tames frizz.','Frizz control|Deep conditioning|Shine boost',4500,75,service_img('Hair Spa & Treatment'),'leaf'],
      ['Bridal Makeup','Makeup','Complete bridal makeup with trial consultation, airbrush finish and hair setting.','Airbrush finish|Long-wear|Trial included',25000,150,service_img('Bridal Makeup'),'crown'],
      ['Relaxing Body Massage','Spa','Full-body aromatherapy massage to melt away stress and muscle tension.','Stress relief|Better sleep|Aromatherapy',6000,60,service_img('Relaxing Body Massage'),'lotus'],
      ['Waxing & Threading','Skin','Gentle, hygienic hair removal with soothing post-care for smooth skin.','Smooth finish|Gentle formula|Quick service',1200,30,service_img('Waxing & Threading'),'feather'],
    ];
    foreach ($services as $s) $sv->execute($s);
    $ss = $db->prepare('INSERT INTO stylist_services VALUES (?,?)');
    foreach ([3=>[3,5,6,7],4=>[1,2],5=>[4,8,9,7],6=>[3,5,6]] as $st=>$ids) foreach ($ids as $i) $ss->execute([$st,$i]);
    $db->exec("UPDATE services SET audience='unisex' WHERE name IN ('Manicure','Pedicure','Facial','Hair Color','Hair Spa & Treatment','Relaxing Body Massage')");
    add_men_services($db);

    $db->exec("INSERT INTO suppliers (name,phone,email,address) VALUES ('Glam Beauty Traders','0321-1000001','sales@glambeauty.pk','Saddar, Karachi'),('Pro Salon Supplies','0321-1000002','orders@prosalon.pk','Gulberg, Lahore')");
    $inv = $db->prepare('INSERT INTO inventory (name,category,sku,quantity,reorder_level,unit_cost,supplier_id) VALUES (?,?,?,?,?,?,?)');
    foreach ([['Keratin Shampoo 1L','Hair Products','HP-001',18,6,1800,1],['Hair Color Tube - Brown','Hair Products','HP-002',4,8,650,1],['Nail Polish Gel Set','Nail Supplies','NS-001',25,10,2200,2],['Facial Mask Pack (10)','Skin Care','SC-001',3,5,1500,2],['Disposable Towels (100)','Consumables','CN-001',40,15,900,1],['Massage Oil 500ml','Spa','SP-001',9,5,1400,2]] as $r) $inv->execute($r);

    $ap = $db->prepare('INSERT INTO appointments (ref,client_id,stylist_id,service_id,appt_date,appt_time,end_time,price,status,notes) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $pay = $db->prepare('INSERT INTO payments (appointment_id,invoice_no,amount,method,status) VALUES (?,?,?,?,?)');
    $fb = $db->prepare('INSERT INTO feedback (appointment_id,client_id,rating,comment) VALUES (?,?,?,?)');
    $d = fn($n) => date('Y-m-d', strtotime(($n>=0?'+':'').$n.' days'));
    $rows = [[7,4,1,-14,'11:00','completed'],[7,3,3,-9,'14:00','completed'],[8,5,4,-7,'12:00','completed'],[9,3,5,-5,'10:00','completed'],
             [8,4,2,-3,'15:00','completed'],[9,6,5,-2,'17:00','completed'],[7,5,4,3,'11:00','confirmed'],[8,3,3,5,'13:00','confirmed'],[9,4,1,6,'16:00','confirmed']];
    $svm = []; foreach ($db->query('SELECT id,price,duration FROM services') as $r) $svm[$r['id']] = $r;
    $comments = [5=>'Absolutely loved it, the staff are so professional!',4=>'Great service and very relaxing atmosphere.'];
    $n = 0;
    foreach ($rows as [$c,$st,$s,$off,$t,$stt]) {
        $n++; $end = date('H:i', strtotime("$t +{$svm[$s]['duration']} minutes"));
        $ap->execute(['ES'.date('ymd').sprintf('%03d',$n),$c,$st,$s,$d($off),$t,$end,$svm[$s]['price'],$stt,'']);
        $id = (int)$db->lastInsertId();
        if ($stt === 'completed') {
            $pay->execute([$id,'INV-'.(1000+$id),$svm[$s]['price'],['cash','card','online'][$n%3],'paid']);
            if ($n <= 4) { $rt = [5,4,5,4][$n-1]; $fb->execute([$id,$c,$rt,$comments[$rt]]); }
        }
    }
    $db->exec("INSERT INTO notifications (user_id,title,message,type) VALUES (7,'Welcome to Elegance Salon','Your account is ready. Book your first appointment today!','info'),(7,'Appointment Confirmed','Your upcoming appointment has been confirmed.','success')");
}
