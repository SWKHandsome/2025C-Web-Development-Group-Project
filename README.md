# Campus Parcel & Lost-and-Found

Inventory tracking focused on campus parcels plus a lost-and-found workflow built with PHP, MySQL, and XAMPP. Students can check parcel arrivals, lost-item boards, history, and edit their profile, while administrators manage records, upload evidence, and monitor KPIs through dashboards.

## Features
- Unified login that routes students and admins to role-specific workspaces.
- Student portal: parcel board with courier logos, lost-and-found gallery, pickup history, and editable profile.
- Admin console: KPI cards, parcel CRUD with expiry tracking, instant status update cards, lost-item registry with photo uploads, and profile management.
- Strict status states (`pending`, `collected`) plus 6-month parcel expiry logic and lost-item cleanup timers.
- Responsive left sidebar UI, card-driven layout, and lightweight Alpine-free JavaScript for sidebar toggles.

## Part A – Environment Setup Checklist
1. Install **XAMPP 8.x** (or WAMP/MAMP) and ensure Apache + MySQL services run.
2. Place this project folder inside `htdocs` (e.g., `d:/XAMPP/htdocs/Web Development/2025C-Web-Development-Group-Project`).
3. Start Apache/MySQL from XAMPP Control Panel.
4. Create **info.php** in `htdocs` with:
   ```php
   <?php phpinfo();
   ```
   Visit `http://localhost/info.php` and capture the screenshot for the report.
5. `php.ini` highlights to document:
   - `display_errors`: toggles verbose PHP errors (set `On` for development, `Off` when deploying).
   - `upload_max_filesize`: controls maximum upload payload (increase to handle lost-item photos, e.g., `16M`).
   - `post_max_size`: must be ≥ `upload_max_filesize` to accept large forms/uploads.

## Part B – Application Setup
1. **Dependencies**: none beyond PHP 8.2+ and MySQL 8.
2. **Database**:
   ```bash
   mysql -u root -p < database.sql
   ```
   This creates `campus_inventory`, schema, and seed users.
3. **Configure connection**: adjust `config/database.php` if your database user/password differ.
4. **Base URL**: `config/app.php` defaults to `/Web%20Development/2025C-Web-Development-Group-Project`. Update if you mount the project elsewhere.

### Default credentials
| Role | Email | Password |
| --- | --- | --- |
| Admin | admin@campus.local | password |
| Student | jason@campus.local | password |
| Student | emily@campus.local | password |

## Part C – php.ini Tweaks & Testing
1. Edit `php.ini` (XAMPP: `xampp/php/php.ini`).
   - Set `display_errors = On` during development; take a screenshot.
   - Raise `upload_max_filesize = 16M` (and `post_max_size = 16M`) for photo uploads; take another screenshot.
2. Restart Apache after each change (XAMPP Control Panel › Stop/Start).
3. Visit `info.php` again to confirm new values and capture the "after" screenshot.

## Part D – Documentation & Presentation Tips
- **PDF Report**: include environment steps, php.ini screenshots, ERD/schema notes, key UI screenshots, testing summary (e.g., login, parcel add/edit, lost-item upload, status change), and answers to the short questions below.
- **Slides**: highlight scenario selection (Campus Parcel & Lost-and-Found), feature demo shots, architecture diagram, and lessons learned.
- **Deliverables**:
  - Zipped project folder (this repo).
  - Database export (`mysqldump campus_inventory > campus_inventory.sql`).
  - PDF report & presentation slides.

## Directory Map
```
config/        # app + database configuration
lib/           # helpers, authentication utilities
partials/      # shared layout (head, sidebar, flash, footer)
admin/         # admin dashboard, parcels, lost items, profile
student/       # student dashboard, lost board, history, profile
auth/          # login/logout endpoints
assets/        # css, js, courier SVGs
uploads/       # runtime upload targets (.gitkeep placeholders)
```

## Short Answer References
1. **Role of `php.ini`**: the master configuration file that defines PHP runtime behavior—extensions, resource limits, error visibility, upload sizes, etc.—and every PHP request inherits these directives when Apache loads PHP.
2. **Two bundled stacks**: XAMPP (cross-platform) and WAMP (Windows) package Apache, MySQL/MariaDB, PHP, and supporting tools for quick installs.
3. **Purpose of `phpinfo()`**: outputs the active PHP configuration (version, loaded extensions, ini values, environment variables) so you can verify settings or troubleshoot mismatches quickly.

## Testing Script (suggested)
1. Login as admin, add parcel for each student, mark one as collected.
2. Upload at least one lost item photo and edit status.
3. Login as student, verify parcel table, lost board, profile edit, and collection history reflects admin changes.
4. Toggle php.ini values, restart Apache, and capture phpinfo before/after.

Happy building & presenting! ✅
