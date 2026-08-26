# config/

Hardened configuration files — publication-ready, secrets scrubbed.

Every file here mirrors what's actually deployed on the main web server.
Replace the `<placeholder>` tokens with real values before deploying.

## Layout

```
config/
├── ssh/
│   └── sshd_config                          hardened SSH daemon (port 2222, key-only)
├── ufw/
│   └── rules.sh                             default-deny firewall + explicit port-22 deny
├── nginx/
│   ├── nginx.conf                           TLS 1.3, 6 security headers, server_tokens off
│   └── sites-available/
│       └── researchportal                   HTTPS vhost, /uploads and /includes 403, PHP-FPM
├── mariadb/
│   ├── 50-server.cnf                        bind 127.0.0.1, local-infile=0
│   └── schema.sql                           schema + least-privilege portaluser grants
├── pam/
│   └── pwquality.conf                       minlen=12, complexity, enforce_for_root
├── fail2ban/
│   ├── jail.local                           4 jails — sshd, nginx-http-auth, -botsearch, -limit-req
│   └── jail.d/
│       └── defaults-debian.conf             nftables allports ban action (Debian default)
├── logrotate/
│   └── researchportal                       daily/14-day nginx logs, weekly/4-week auth.log
└── sudoers/
    └── researchportal                       command whitelist (apply with visudo)
```

## Placeholders to replace before deployment

| Token | Meaning |
|---|---|
| `<strong-password>` | A 12+ character strong password for `portaluser` (generate with `openssl rand -base64 24`) |
| `<backup-server-ip>` | IP of your isolated backup host |
| `<server-ip>` | IP of the web server itself |

## Pre-deployment checklist

- [ ] All `<placeholder>` tokens replaced with real values
- [ ] `sshd_config` deployed, `sudo systemctl restart sshd`, key login verified
- [ ] `ufw rules.sh` executed, `ufw status verbose` matches expected output
- [ ] Self-signed TLS cert generated (commands in `nginx/sites-available/researchportal`)
- [ ] `nginx.conf` + vhost symlinked, `nginx -t` clean
- [ ] `schema.sql` applied as root, `SHOW GRANTS FOR portaluser@localhost` matches
- [ ] `pwquality.conf` deployed, weak password rejected in a test
- [ ] `fail2ban/jail.local` deployed, `fail2ban-client status` shows 4 jails
- [ ] `logrotate` config deployed, `logrotate -d` runs clean
- [ ] `sudoers/researchportal` applied via `visudo -f`, `sudo whoami` correctly denied
