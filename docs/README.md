# Screenshot evidence

Images were extracted from `report jurus v2.pdf` (49 PDF pages). Page numbers below are PDF page positions. Captures document historical lab tests; they are not fresh test runs.

| Screenshot | PDF page | Evidence |
|---|---:|---|
| [nmap-before.png](screenshots/nmap-before.png) | 35 | Pre-hardening scan |
| [nmap-after.png](screenshots/nmap-after.png) | 38 | Post-hardening scan |
| [headers-after.png](screenshots/headers-after.png) | 10 | HTTPS response and security headers |
| [tls13.png](screenshots/tls13.png) | 10 | TLS 1.0 rejected (`-tls1`); TLS 1.3 accepted |
| [fail2ban-ban.png](screenshots/fail2ban-ban.png) | 12 | Found and Ban log events |
| [fail2ban-status.png](screenshots/fail2ban-status.png) | 11 | SSHD jail and banned lab IP |
| [show-grants.png](screenshots/show-grants.png) | 13 | Application database grants; credential hash redacted |
| [sqlmap-result.png](screenshots/sqlmap-result.png) | 17 | Final sqlmap result |
| [bcrypt-hashes.png](screenshots/bcrypt-hashes.png) | 18 | Bcrypt cost-12 prefixes and roles; usernames and hash suffixes redacted |
| [xss-encoded.png](screenshots/xss-encoded.png) | 22 | Encoded HTML in response |
| [upload-rejected.png](screenshots/upload-rejected.png) | 22 | File-type rejection message |
| [rbac-guest-blocked.png](screenshots/rbac-guest-blocked.png) | 19 | Guest unauthorized redirect; account name redacted |
| [restore-complete.png](screenshots/restore-complete.png) | 32 | Recovery steps 5–7 and completion summary |
| [portal-restored.png](screenshots/portal-restored.png) | 33 | HTTP 200 after recovery |
| [vm2-backups.png](screenshots/vm2-backups.png) | 28 | Backup directory listing |

## Missing source evidence

`db-denied.png` was requested by the original checklist, but no screenshot of `ERROR 1142` / denied access to `mysql.user` was found in the supplied report. The broken README image reference has been replaced with an evidence-gap note. No substitute or fabricated terminal output was added.

## Preparation

Original embedded images were exported as PNG. Admin prompt rows were cropped from the database grants, bcrypt, Fail2ban status, and backup listing captures. Gray boxes mark redacted credential material and account names. Bcrypt algorithm/cost prefixes remain visible. Command results were not rewritten. The TLS caption follows the actual command (`-tls1`, not `-tls1_1`).

