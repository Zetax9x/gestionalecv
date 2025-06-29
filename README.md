# gestionalecv

This repository provides a minimal Laravel style structure for managing core resources.

## Modules

### Volunteer
* **Model**: `app/Models/Volunteer.php`
* **Service**: `app/Services/VolunteerService.php`
* **Migration**: `database/migrations/2024_01_01_000000_create_volunteers_table.php`
* **Routes**: defined under the `volunteers` prefix in `routes/api.php`

### Vehicle
* **Model**: `app/Models/Vehicle.php`
* **Service**: `app/Services/VehicleService.php`
* **Migration**: `database/migrations/2024_01_01_000001_create_vehicles_table.php`
* **Routes**: defined under the `vehicles` prefix in `routes/api.php`

### Checklist
* **Model**: `app/Models/Checklist.php`
* **Service**: `app/Services/ChecklistService.php`
* **Migration**: `database/migrations/2024_01_01_000002_create_checklists_table.php`
* **Routes**: defined under the `checklists` prefix in `routes/api.php`

These files are placeholders to help guide future development. Add columns to the migrations, fillable attributes to the models, and API endpoints to the routes as requirements evolve.
