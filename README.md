# Campus Parcel & Lost-and-Found

Inventory tracking focused on campus parcels plus a lost-and-found workflow built with PHP, MySQL, and XAMPP. Students can check parcel arrivals, lost-item boards, history, and edit their profile, while administrators manage records, upload evidence, and monitor KPIs through dashboards.

## Features
- Unified login that routes students and admins to role-specific workspaces.
- Student portal: parcel board with courier logos, lost-and-found gallery, pickup history, and editable profile.
- Admin console: KPI cards, parcel CRUD with expiry tracking, instant status update cards, lost-item registry with photo uploads, and profile management.
- Strict status states (`pending`, `collected`) plus 6-month parcel expiry logic and lost-item cleanup timers.
- Responsive left sidebar UI, card-driven layout, and lightweight Alpine-free JavaScript for sidebar toggles.

### Default credentials
| Role | Email | Password |
| --- | --- | --- |
| Admin | admin@campus.local | password |
| Student | jason@campus.local | password |
| Student | emily@campus.local | password |
