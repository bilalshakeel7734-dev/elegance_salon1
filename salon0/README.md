# Elegance Salon Management System (PHP + MySQL)

## Run (XAMPP / WAMP)
1. Copy the `salon` folder to `C:\xampp\htdocs\`
2. Start Apache and MySQL in XAMPP
3. Open http://localhost/salon/ (database, tables and demo data are created automatically)
   - Different MySQL password? Edit config.php. Optional: import database/schema.sql in phpMyAdmin.

## Demo logins
Client: client / client123 | Admin: admin / admin123 | Receptionist: reception / reception123 | Stylist: sophia / stylist123

## Access rules
Login ya Register kiye baghair koi bhi website page (Home, About, Services, Stylists, Contact) open nahi hota.
Guest sirf `login.php` aur `register.php` dekh sakta hai; baqi sab pages login par redirect ho jate hain.

## Services for Women & Men
- Every service has an audience: Women, Men, or Women & Men (set it in Admin > Services > "For").
- Home page and Services page have a Women / Men switch; the booking page shows who each service is for.
- Existing databases are upgraded automatically on first load (adds the `audience` column and 7 men's services; your data is untouched).

## Database driver fix (XAMPP)

The project now selects MySQL automatically when PHP's `pdo_mysql` driver is enabled. If it is not enabled but `pdo_sqlite` is enabled, the project uses the local SQLite database file at `database/salon.sqlite` automatically (no MySQL service is needed for that mode).

For the normal MySQL setup, open XAMPP Control Panel → Apache → Config → PHP (php.ini). Find these lines and remove the leading semicolon if present:

```ini
extension=pdo_mysql
extension=mysqli
```

Save `php.ini`, restart Apache, and start MySQL. Keep `DB_USER` as `root` and `DB_PASS` empty unless you set a MySQL password yourself.
