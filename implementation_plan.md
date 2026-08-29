# Admin User Management Implementation Plan

This plan outlines the design and implementation of user management features. Admins (logged-in users with the `ADMIN` role) will be able to view, search, create, edit, and delete users inside the `billing_login` database.

## User Review Required

> [!IMPORTANT]
> - User operations (creation, updates, deletion) will only be accessible to users who are authenticated and have the role `ADMIN`.
> - All queries will run against the separate `$conn_login` connection pointing to `billing_login` database.
> - Password fields will be stored and modified as plain text, matching the current credentials validation logic in `index.php`.

## Proposed Changes

### Navigation Header Integration

#### [MODIFY] [header.php](file:///c:/Apache24/htdocs/sunder_billing_new/includes/header.php)
- Add a new "Users" menu option in the main menu bar, wrapped in a check `if ($_SESSION['role'] === 'ADMIN')` to ensure it is only visible to admin accounts.

---

### User Management Components

#### [NEW] [manage_users.php](file:///c:/Apache24/htdocs/sunder_billing_new/users/manage_users.php)
- A new file under a new `users` directory.
- Features a clean, green-accented table listing all users in the system.
- Includes a filter/search bar for searching users by username.
- Integrates modals for **Add User** and **Edit User**.
- Restricts access to users with `ADMIN` role.

#### [NEW] [user_save.php](file:///c:/Apache24/htdocs/sunder_billing_new/users/user_save.php)
- Handles user creation and updating via POST requests.
- Escapes input values and processes queries against `$conn_login`.
- Restricts access to users with `ADMIN` role.

#### [NEW] [user_delete.php](file:///c:/Apache24/htdocs/sunder_billing_new/users/user_delete.php)
- Handles deleting users by `id` from the database via GET/POST requests.
- Prevents admins from deleting their own currently logged-in account to avoid lockouts.
- Restricts access to users with `ADMIN` role.

---

## Verification Plan

### Manual Verification
1. Log in with the default admin account.
2. Verify that the "Users" menu option appears in the navigation bar.
3. Access the User Management dashboard, click "New", fill in username, password, role (`ADMIN`, `USER`, `BILLING`, etc.), and verify user creation.
4. Try editing the role or password of the created user.
5. Try deleting the user and verify that it is deleted from the `billing_login.user` table.
6. Verify that trying to delete your own logged-in admin account is prevented.
7. Verify that logging in with a non-admin role hides the "Users" menu option and rejects access to `users/manage_users.php`.
