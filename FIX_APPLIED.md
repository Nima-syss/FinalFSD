# Function Redeclaration Fix Applied

## Problem
Fatal error: Cannot redeclare `isLoggedIn()` (previously declared in auth.php:172) in functions.php on line 91

## Root Cause
Two functions were declared in both `includes/auth.php` and `includes/functions.php`:
1. `isLoggedIn()` - defined at auth.php:171 and functions.php:90
2. `logoutUser()` - defined at auth.php:187 and functions.php:118

When both files were included in the same script, PHP encountered duplicate function definitions, causing a fatal error.

## Solution Applied
Removed the duplicate functions from `includes/functions.php`:
- Removed `isLoggedIn()` function (lines 90-92)
- Removed `logoutUser()` function (lines 118-124)

These functions are now only defined in `includes/auth.php`, which is the primary authentication file.

## Files Modified
- `includes/functions.php` - Removed duplicate authentication functions

## Files Unchanged
- `includes/auth.php` - Kept as the authoritative source for authentication functions

## Usage Notes
1. **For authentication features**, include `auth.php`:
   ```php
   require_once __DIR__ . '/../includes/auth.php';
   ```

2. **For utility functions**, include `functions.php`:
   ```php
   require_once __DIR__ . '/../includes/functions.php';
   ```

3. **Both files can now be safely included together** without conflicts.

## Available Authentication Functions (from auth.php)
- `isLoggedIn()` - Check if user is logged in (returns bool)
- `checkAuth()` - Redirect to login if not authenticated
- `checkRole($allowedRoles)` - Check if user has required role
- `getCurrentUser()` - Get current user info
- `isAdmin()`, `isInstructor()`, `isStudent()` - Role checks
- `requireAdmin()`, `requireInstructor()` - Require specific roles
- `getUserRole()` - Get user's role
- `logoutUser()` - Logout and destroy session
- `getLoginUrl()`, `getDashboardUrl()` - Dynamic URL helpers

## Migration Guide
If your code was using these functions from `functions.php`, no changes are needed as long as you're including `auth.php`. The functions work identically.

If you're **only** including `functions.php` and getting undefined function errors:
```php
// Before
require_once 'includes/functions.php';
if (isLoggedIn()) { ... }

// After - add auth.php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
if (isLoggedIn()) { ... }
```

## Testing
After applying this fix:
1. Clear any PHP opcache if enabled
2. Restart your web server (Apache/Nginx)
3. Test login functionality
4. Test protected pages
5. Verify no "cannot redeclare" errors appear

## Additional Notes
- The `auth.php` version of these functions is more complete and includes additional features
- `functions.php` now focuses on utility functions, password hashing, and database operations
- A comment has been added to `functions.php` to clarify where authentication functions are located
