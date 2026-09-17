# Existing Reservations Page Implementation

## Objective
Create the Existing Reservations page to match the provided UI while keeping the same application layout, sidebar, header, footer, and Nebula background used across the project.

## Requirements
- Match the mock-up layout closely.
- Remove the Export CSV button.
- Use the same app shell as the rest of the project.
- Keep the page visually aligned with the existing dashboard and reservation pages.
- Apply the same styling conventions already used in the app.

## Design reference
The implementation should follow the provided UI:
- Title: Existing Reservations
- Subtitle: Manage and review all resource requests across the institute.
- Top summary cards for total, pending, and rejected reservations
- Filter bar with search, resource type, date, and status controls
- Reservation table with resource name, reserved by, date, time, status, and action buttons
- Pagination section at the bottom

## Must-have changes
### 1. Remove Export CSV section
The page should not include any Export CSV button or related action.

### 2. Use the app layout
The page must extend the existing app layout and keep the shared UI structure:
- left sidebar
- top navigation/header
- Nebula background image
- footer

The implementation should not create a separate standalone page shell.

### 3. Keep the app theme consistent
Use the current Nebula styling patterns already present in:
- `resources/views/layouts/app.blade.php`
- `resources/views/reservations/create.blade.php`
- `resources/views/dashboards/dashboard.blade.php`

Follow the same:
- spacing
- border radius
- card shadow
- subtle grey/light backgrounds
- blue/green/red status coloring

## Page structure
### Header section
- Title on the left side
- Subtitle beneath it
- No right-side action button

### Summary cards
Add three summary cards:
1. Total Reservations
2. Pending
3. Rejected

Each card should:
- have a subtle border and white card style
- include an icon on the left or top
- show a large numeric count
- include a status label or trend badge where applicable

### Filter row
Use a single horizontal filter bar with these fields:
- Search by name
- Resource type dropdown
- Date range input
- Status dropdown

### Reservation table
The table must include these columns:
- Resource name
- Reserved by
- Date
- Time
- Status
- Action

Each reservation row should show:
- resource name
- reserved user initials or avatar
- date and time formatted in a clean layout
- status pill
- edit action
- delete action

### Pagination area
Include a pagination footer with:
- previous button
- current page highlight
- numbered pages
- next button

## Status styling
Map reservation states to these colors:
- Pending -> blue
- Confirmed -> green
- Rejected -> red

Use consistent pill badges with rounded corners and medium-weight text.

## Route and controller scope
The route currently points to a placeholder view and should be updated to render the completed page properly.

Files to review:
- `routes/web.php`
- `app/Http/Controllers/ReservationController.php`
- `resources/views/reservations/index.blade.php`

## Suggested implementation flow
1. Replace the placeholder `reservations/index.blade.php` content.
2. Remove the CSV export element from the UI.
3. Keep all shared layout elements from the app layout.
4. Populate the page with static or dynamic reservation data matching the mock-up.
5. Match spacing and typography to existing pages.
6. Verify the page renders correctly with the sidebar, footer, and background.

## Acceptance checklist
- [ ] Export CSV button is not visible
- [ ] Sidebar and layout match the rest of the app
- [ ] Background and footer match project theme
- [ ] Summary cards display correctly
- [ ] Filter section matches the mock-up structure
- [ ] Reservation table is styled consistently
- [ ] Status pills are correct
- [ ] Pagination matches the style pattern

## Final note
This page should look like a proper part of the existing Nebula admin interface rather than a stand-alone custom design. The goal is to balance the provided mock-up with the project’s established UI pattern.
