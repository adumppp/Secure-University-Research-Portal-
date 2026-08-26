# docs/

Screenshots, diagrams, and evidence for the README.

## Layout

```
docs/
├── screenshots/         evidence captures (see list below)
└── architecture/        diagram sources
```

## Screenshot checklist (from the technical report)

Export these from your evidence collection into `screenshots/`, then
reference them from the README sections listed:

| # | Screenshot | README section |
|---|---|---|
| 1 | `nmap-before.png` — ports 22/80/3306 open, nginx/1.24.0 exposed | §7 Before/After |
| 2 | `nmap-after.png` — only 80/443/2222, 3306 filtered | §7 Before/After |
| 3 | `headers-after.png` — curl -Ik with all 6 headers + Server: nginx | §5.2 Network |
| 4 | `tls13.png` — openssl s_client TLS 1.3 accepted / TLS 1.1 rejected | §5.2 Network |
| 5 | `fail2ban-ban.png` — fail2ban.log Found×3 → Ban | §5.5 Monitoring |
| 6 | `fail2ban-status.png` — fail2ban-client status with banned IP | §5.5 Monitoring |
| 7 | `show-grants.png` — portaluser SHOW GRANTS output | §5.3 Database |
| 8 | `db-denied.png` — ERROR 1142 on mysql.user | §5.3 Database |
| 9 | `sqlmap-result.png` — "all tested parameters do not appear to be injectable" | §6 Validation |
| 10 | `bcrypt-hashes.png` — $2y$12$ prefix across users | §5.4 Application |
| 11 | `xss-encoded.png` — &lt;script&gt; rendered as text | §5.4 Application |
| 12 | `upload-rejected.png` — test.exe rejected | §5.4 Application |
| 13 | `rbac-guest-blocked.png` — Guest blocked from /upload.php | §5.4 Application |
| 14 | `restore-complete.png` — restore.sh STEP 0–7 output | §5.6 BCP/DR |
| 15 | `portal-restored.png` — HTTP 200 + headers after restore | §5.6 BCP/DR |
| 16 | `vm2-backups.png` — /received-backups/ listing on VM2 | §5.6 BCP/DR |

## Sanitization rules for every screenshot

- [ ] Blur or crop any real IP outside `192.168.56.x`
- [ ] Confirm no password/passphrase visible in terminal scrollback
- [ ] Replace the lab admin username in terminal prompts with `portaladmin` (or crop the prompt)
- [ ] No personally-identifiable user data in browser screenshots
