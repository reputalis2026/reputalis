# Operaciones en el servidor (VPS)

**Propósito:** resumen de lo configurado en el entorno de producción para seguridad, correo e informes. **No contiene secretos** (contraseñas, app passwords ni claves privadas).

---

## Contexto

- **Hostname típico:** `srv1268490.hstgr.cloud` (puede variar).
- **Dominio de la app:** `reputalis.org` (HTTPS con Let's Encrypt donde aplique).
- **Proyecto desplegado:** `/var/www/reputalis` (propietario habitual de la app: `www-data`).

---

## SSH (endurecimiento)

- **Root por SSH deshabilitado** (`PermitRootLogin no`).
- **Autenticación por contraseña deshabilitada** (`PasswordAuthentication no`); acceso con **clave** (usuario `ubuntu` con sudo).
- **Deploy key Git** (solo este servidor): clave dedicada en `/root/.ssh/id_ed25519_github_reputalis` y bloque `Host github.com` en `/root/.ssh/config` con `IdentitiesOnly yes`.
- **Backup de configuración SSH:** `/root/ssh-backups-20260512-094511` (ajustar fecha si se hacen nuevos backups).

---

## Fail2Ban

- Instalado y activo; jails típicas: `sshd`, `nginx-botsearch`, `nginx-bad-request`.
- Ajustes conservadores en `/etc/fail2ban/jail.local` (tiempos y reintentos moderados).
- **Backup:** `/root/fail2ban-backups-20260512-101222`.

---

## Correo e informes diarios

- **Postfix** envía a través de **Gmail como relay SMTP** (puerto **587**, TLS). Credenciales en archivos **no documentados aquí** (`/etc/postfix/sasl_passwd` y `.db`, permisos restrictivos).
- **Mapa genérico** reescribe remitentes internos hacia la cuenta Gmail autorizada para evitar rebotes por políticas de Gmail.
- **Script de informe:** `/usr/local/bin/daily-security-report.sh` — genera texto con estado de Fail2Ban, jails, fragmento de log, disco/memoria/uptime y envía copia al correo configurado en el propio script.
- **Copias locales:** `/root/reports/security/security-report-*.txt`.
- **Cron diario:** `/etc/cron.d/reputalis-security-report` — ejecución a las **06:30 UTC** (ver archivo para la línea exacta).

---

## Adminer y archivos sensibles

- **Adminer** que estaba en `public/` se **retiró de la web** y se guardó fuera del árbol del proyecto en cuarentena bajo `/root/quarantine/reputalis/` (nombre con sufijo `quarantined-` y fecha). No ejecutar Adminer expuesto en público.

---

## Qué no documentar aquí

- Valores de `APP_KEY`, contraseñas de BD, app passwords de Gmail, contenido de `authorized_keys` o claves privadas.
- Si cambias relay o credenciales, actualiza solo los archivos del sistema (`postfix`, `sasl_passwd`) y **no** copies secretos al repositorio Git.

---

## Ver también

- [`docs/RUNBOOK.md`](RUNBOOK.md) — SSH, Fail2Ban, Git, informe diario, logs.
- [`docs/HANDOFFS.md`](HANDOFFS.md) — registro cronológico de cambios y pendientes.
