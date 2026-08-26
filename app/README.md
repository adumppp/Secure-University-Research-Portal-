# App source placeholder

The full PHP application source (portal pages, includes, RBAC
enforcement, upload validation) lives here in the published repo.

Before publishing, the application code should be copied from the
deployment host (`/var/www/researchportal/public/`) and reviewed:

- [ ] `includes/db.php` — replace the DB password with an env var
      or placeholder (never commit real credentials)
- [ ] Remove any `.php.original` backup files from the copy
- [ ] Confirm no real user data in seed/sample records
- [ ] Verify all `prepare(` statements survive the copy unchanged

Suggested layout once populated:

```
app/
├── index.php
├── login.php
├── logout.php
├── register.php
├── dashboard.php
├── my-research.php
├── research.php
├── confidential.php
├── view-doc.php
├── upload.php
├── log-access.php
├── search.php
├── admin/
├── assets/
└── includes/
    ├── auth.php
    ├── db.php
    ├── functions.php
    └── nav.php
```
