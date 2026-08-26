#!/bin/bash
# ═══════════════════════════════════════════════════════════
# Secure Research Portal — Host firewall rules
# Target: Ubuntu Server 24.04 LTS (UFW)
# Apply once on a fresh install, then verify with:
#   sudo ufw status verbose
# ═══════════════════════════════════════════════════════════

# --- Default policy: deny everything inbound ---
# Anything not explicitly allowed below is dropped.
ufw default deny incoming
ufw default allow outgoing

# --- Explicit allows (the only inbound paths into the server) ---
ufw allow 2222/tcp comment 'SSH — hardened, key-only'
ufw allow 80/tcp    comment 'HTTP — 301 redirect to HTTPS'
ufw allow 443/tcp   comment 'HTTPS — portal traffic'

# --- Defence-in-depth: explicit DENY for default SSH port ---
# Nothing listens on port 22 anymore, but an explicit deny rule
# guarantees the policy survives misconfiguration.
ufw deny 22/tcp     comment 'SSH default port — blocked'

# --- Activate ---
ufw enable

# --- Expected result ---
# sudo ufw status verbose
#   Default: deny (incoming), allow (outgoing)
#   22/tcp  DENY  Anywhere
#   80/tcp  ALLOW Anywhere
#   443/tcp ALLOW Anywhere
#   2222/tcp ALLOW Anywhere
#
# Validation from an external machine:
#   nmap -p 22,80,443,2222,3306 <server-ip>
#   → 22 filtered/closed · 80 open · 443 open · 2222 open · 3306 filtered
