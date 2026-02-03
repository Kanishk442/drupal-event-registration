# Drupal 10 – Event Registration Module

## Overview
This project is a custom **Drupal 10** module that allows administrators to configure events and users to register for those events through a custom form.  
It stores registrations in custom database tables and sends email notifications using the Drupal Mail API.

**No contributed modules are used.**  
The implementation follows Drupal 10 coding standards, PSR-4 autoloading, and Dependency Injection.

---

## Features

### Admin Features
- Create and manage event configurations
- Define:
  - Event Name
  - Event Category
  - Event Date
  - Registration Start Date
  - Registration End Date
- Configure admin email notifications
- View all registrations
- Filter registrations by:
  - Event Date
  - Event Name (AJAX-based)
- Export registrations as CSV
- View total participants per event

### User Features
- Register for events using a custom form
- Registration allowed only between configured start and end dates
- Dynamic dropdowns using AJAX:
  - Event Category
  - Event Date
  - Event Name
- Email confirmation after successful registration

---

## Installation Steps

1. Clone or download the repository
2. Place the module inside:
# Drupal 10 – Event Registration Module

## Overview
This project is a custom **Drupal 10** module that allows administrators to configure events and users to register for those events through a custom form.  
It stores registrations in custom database tables and sends email notifications using the Drupal Mail API.

**No contributed modules are used.**  
The implementation follows Drupal 10 coding standards, PSR-4 autoloading, and Dependency Injection.

---

## Features

### Admin Features
- Create and manage event configurations
- Define:
  - Event Name
  - Event Category
  - Event Date
  - Registration Start Date
  - Registration End Date
- Configure admin email notifications
- View all registrations
- Filter registrations by:
  - Event Date
  - Event Name (AJAX-based)
- Export registrations as CSV
- View total participants per event

### User Features
- Register for events using a custom form
- Registration allowed only between configured start and end dates
- Dynamic dropdowns using AJAX:
  - Event Category
  - Event Date
  - Event Name
- Email confirmation after successful registration

---

## Installation Steps

1. Clone or download the repository
2. Place the module inside:
web/modules/custom/event_registration
3. Enable the module from:
Admin → Extend → Event Registration
4. Run database updates if prompted

---

## Important URLs

| Purpose | URL |
|------|-----|
| Admin settings | `/admin/config/event-registration/settings` |
| Create events | `/admin/config/event-registration/event` |
| User registration form | `/event/register` |
| View registrations | `/admin/content/event-registrations` |

---

## Database Tables

### 1. Event Configuration Table  
**Table name:** `event_registration_event_config`

| Column | Description |
|------|------------|
| id | Primary key |
| event_name | Name of the event |
| category | Event category |
| event_date | Event date |
| registration_start | Registration start datetime |
| registration_end | Registration end datetime |
| created | Timestamp |

---

### 2. Event Registration Table  
**Table name:** `event_registration_registrations`

| Column | Description |
|------|------------|
| id | Primary key |
| event_config_id | Foreign key to event config |
| full_name | User full name |
| email | User email |
| college_name | College name |
| department | Department |
| created | Submission timestamp |

---

## Validation Rules

- Prevents duplicate registrations using:
4. Run database updates if prompted

---

## Important URLs

| Purpose | URL |
|------|-----|
| Admin settings | `/admin/config/event-registration/settings` |
| Create events | `/admin/config/event-registration/event` |
| User registration form | `/event/register` |
| View registrations | `/admin/content/event-registrations` |

---

## Database Tables

### 1. Event Configuration Table  
**Table name:** `event_registration_event_config`

| Column | Description |
|------|------------|
| id | Primary key |
| event_name | Name of the event |
| category | Event category |
| event_date | Event date |
| registration_start | Registration start datetime |
| registration_end | Registration end datetime |
| created | Timestamp |

---

### 2. Event Registration Table  
**Table name:** `event_registration_registrations`

| Column | Description |
|------|------------|
| id | Primary key |
| event_config_id | Foreign key to event config |
| full_name | User full name |
| email | User email |
| college_name | College name |
| department | Department |
| created | Submission timestamp |

---

## Validation Rules

- Prevents duplicate registrations using:
Email + Event Date
- Email format validation
- No special characters allowed in text fields
- User-friendly validation error messages

---

## Email Notification Logic

- Uses **Drupal Mail API**
- Sends confirmation email to:
- Registered user
- Admin (if enabled in settings)
- Email content includes:
- User Name
- Event Name
- Event Date
- Event Category

---

## Technical Details

- Drupal version: **10.x**
- PHP: **8.x**
- No contributed modules
- Uses:
- Form API
- Config API
- Dependency Injection
- PSR-4 autoloading

---

## Project Structure

event_registration/
├── config/
├── js/
├── sql/
├── src/
│ ├── Controller
│ ├── Form
│ ├── Entity
│ └── Service
├── templates/
├── event_registration.info.yml
├── event_registration.install
├── event_registration.routing.yml
├── event_registration.permissions.yml
├── event_registration.services.yml

---

## Notes

- Database schema is defined in `event_registration.install`
- SQL dump is provided in the `sql/` directory
- AJAX callbacks are implemented in forms
- Admin pages are protected using custom permissions

---

## Author
**Kanishk Tomar**
