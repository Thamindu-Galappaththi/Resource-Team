# Reservation Page Implementation Specification

## 1. Goal

Build a **Create New Reservation** page that follows the supplied Nebula UI reference while preserving the existing application shell:

- Shared header
- Fixed sidebar dashboard navigation
- Shared footer
- Existing Nebula background image
- Existing Bootstrap and Tabler Icons styling conventions

The page must be usable on desktop and mobile and must support resource availability and optional resource add-ons.

## 2. Existing Integration Points

The page should be implemented in:

- `resources/views/reservations/create.blade.php`
- `routes/web.php`

The existing layout already provides the shell and background through:

- `resources/views/layouts/app.blade.php`
- `resources/views/components/sidebar.blade.php`

Existing lookup endpoints that can be reused:

- `GET /resource-categories`
- `GET /resource-types`
- `GET /resource-list`
- `GET /locations`

The current create reservation view is only a placeholder. There is currently no reservation table, reservation model, reservation controller, or reservation submission endpoint.

## 3. Page Structure

### Page heading

- Heading: `Create New Reservation`
- Supporting text: `Define your requirements and book premium assets across the Nebula network.`

### Reservation Details section

Use a prominent section panel containing:

1. Reservation date
2. Start time
3. End time
4. Location
5. Resource category
6. Available resource
7. Special requirements

The old **Resource Specifications** block from the reference image must be removed from this section.

### Resource preview

After the user selects an available resource, display a preview containing:

- Resource name/model
- Resource category
- Resource type
- Location
- Availability/status
- Resource description or feature details when available

The preview should remain hidden or display an empty state until a resource is selected.

### Resource Add-ons section

Initially show only an action button:

- Label: `Add Resource Add-on`
- Include an add/plus icon
- Mark the feature as optional where appropriate

When clicked, reveal the add-on selection box containing:

1. Add-on category
2. Available add-on selector
3. Add-on requirements textarea
4. Add-on preview
5. Add or remove add-on actions

The add-on panel must not appear by default. It should be possible to remove a selected add-on and return to the collapsed state.

When an add-on is selected, show a preview containing:

- Add-on resource name/model
- Add-on category/type
- Add-on details/specifications
- Availability/status
- User-entered add-on requirements

### Submission

Place the primary action at the bottom of the page:

- Button label: `Complete Reservation`
- Disable the button until the required reservation fields are valid.
- Show a success message after a successful submission.
- Preserve entered values and show validation errors after a failed submission.

## 4. Form Behavior

### Dependent resource selection

1. Load categories, resources, and locations from the existing lookup endpoints.
2. Populate the resource category selector.
3. Filter the resource selector by the selected category.
4. Filter again by the selected location.
5. Filter out resources that are not available for the selected date and time.
6. Clear the selected resource and preview whenever category, location, date, start time, or end time changes.
7. Display a clear empty state when no resource is available.

Resource availability must be checked by the server during submission even if the browser filters the list first.

### Time validation

- Reservation date is required.
- Start and end times are required.
- End time must be later than start time.
- Reservations crossing midnight are not supported unless explicitly added to the business rules later.
- Date/time values should be normalized consistently before storage.

### Add-on behavior

- Add-ons are optional.
- An add-on can only be selected after a primary resource is selected.
- Add-on choices should be limited to available add-on resources for the same reservation period.
- Add-on resources must not duplicate the primary resource.
- Add-on selections must be validated again on the server.

## 5. Data Model Requirements

### Reservations table

Create a migration for a `reservations` table with at least:

- `id`
- `user_id` foreign key
- `resource_id` foreign key
- `location_id` foreign key
- `reservation_date` date
- `start_time` time
- `end_time` time
- `special_requirements` nullable text
- `status` string, defaulting to `pending`
- timestamps

Recommended reservation statuses:

- `pending`
- `approved`
- `rejected`
- `cancelled`
- `completed`

### Reservation add-ons table

Create a migration for a `reservation_add_ons` table with at least:

- `id`
- `reservation_id` foreign key
- `resource_id` foreign key
- `requirements` nullable text
- timestamps

The add-on should reference the existing `resources` table rather than duplicating resource data.

### Models and relationships

Add:

- `App\Models\Reservation`
- `App\Models\ReservationAddOn`

Relationships should include:

- Reservation belongs to user
- Reservation belongs to primary resource
- Reservation belongs to location
- Reservation has many add-ons
- ReservationAddOn belongs to reservation
- ReservationAddOn belongs to resource

## 6. Backend Endpoints

Add a reservation controller and routes:

- `GET /reservations/create` renders the page
- `POST /reservations` creates a reservation
- Optional: `GET /reservations/available-resources` returns resources available for a date/time/location filter

The create page may use the existing lookup endpoints for initial data, but the final `POST /reservations` request must be authoritative.

Recommended request payload:

```json
{
  "reservation_date": "2026-08-23",
  "start_time": "09:00",
  "end_time": "17:00",
  "location_id": 1,
  "resource_category_id": 1,
  "resource_id": 12,
  "special_requirements": "Catering setup needed 15 minutes before session start.",
  "add_ons": [
    {
      "resource_id": 21,
      "requirements": "Set up before the meeting begins."
    }
  ]
}
```

Use a Form Request for validation and a database transaction when creating the reservation and its add-ons.

## 7. Availability Rule

A resource is unavailable when an existing reservation for that resource overlaps the requested period and has an active status.

The overlap condition is:

```text
existing.start_time < requested.end_time
AND existing.end_time > requested.start_time
```

Only reservations with statuses such as `pending` or `approved` should block availability. Cancelled, rejected, and completed reservations should not block a future booking unless the business rules require otherwise.

The same availability check must be applied to:

- The primary resource
- Every selected add-on resource

Use a transaction and re-check availability immediately before saving to reduce race conditions between two users submitting the same resource.

## 8. Validation Rules

Required fields:

- `reservation_date`
- `start_time`
- `end_time`
- `location_id`
- `resource_category_id`
- `resource_id`

Additional rules:

- Date must be a valid date and should not be in the past.
- Location must exist.
- Category must exist.
- Resource must exist and belong to the selected category.
- Resource must belong to the selected location when location ownership is enforced.
- End time must be after start time.
- Special requirements are optional and length-limited.
- Every add-on resource must exist and be available.
- Add-on resources must be unique within the request.

## 9. UI and Accessibility Requirements

- Match the reference image with light translucent panels over the Nebula background.
- Use existing Bootstrap classes where practical.
- Use Tabler or Bootstrap icons already loaded by the shared layout.
- Keep labels visible above inputs.
- Use clear focus states and keyboard-accessible buttons.
- Associate every label with its input using `for` and `id`.
- Add `aria-live` feedback for availability, validation, and submission messages.
- Do not rely on placeholder text as the only field label.
- Keep controls usable at mobile widths without horizontal scrolling.

## 10. Files Expected During Implementation

### Backend

- `database/migrations/*_create_reservations_table.php`
- `database/migrations/*_create_reservation_add_ons_table.php`
- `app/Models/Reservation.php`
- `app/Models/ReservationAddOn.php`
- `app/Http/Controllers/ReservationController.php`
- `app/Http/Requests/StoreReservationRequest.php`
- `routes/web.php`

### Frontend

- `resources/views/reservations/create.blade.php`
- A page-specific stylesheet only if the existing global stylesheet cannot support the design
- Page-specific JavaScript in the Blade stack or an existing frontend asset, following current project conventions

### Tests

- Feature test for displaying the create reservation page
- Feature test for successful reservation creation
- Validation test for missing and invalid fields
- Availability test for overlapping reservations
- Add-on availability and duplicate-resource tests

## 11. Acceptance Criteria

The implementation is complete when:

- The page uses the existing header, footer, sidebar, and Nebula background.
- The placeholder content is replaced by the requested reservation form.
- Resource options update based on category, location, date, and time.
- Unavailable resources cannot be submitted.
- The resource preview appears after selecting a resource.
- Resource Specifications is not shown as a standalone section.
- Special requirements can be entered.
- The add-on panel is hidden until its button is clicked.
- Add-on selection shows resource, name, and add-on details.
- A reservation and optional add-ons persist successfully.
- Server-side validation prevents invalid or conflicting reservations.
- The UI works at desktop and mobile widths.
- Automated tests cover creation, validation, availability, and add-ons.

## 12. Implementation Order

1. Add migrations and models.
2. Add request validation and reservation controller.
3. Add availability query and reservation routes.
4. Replace the placeholder Blade view.
5. Add dependent selectors, previews, and add-on interaction.
6. Add feature tests.
7. Run migrations and the test suite.
8. Review the page at desktop and mobile widths against the supplied reference image.
