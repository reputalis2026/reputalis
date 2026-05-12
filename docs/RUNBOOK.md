# Runbook operativo (VPS Reputalis)

**Propósito:** comandos y comprobaciones habituales en el servidor. **Sin secretos:** no pegues contraseñas, app passwords ni claves privadas en tickets ni en este archivo.

**Relacionado:** [`docs/OPERACIONES_SERVIDOR.md`](OPERACIONES_SERVIDOR.md) (qué está configurado), [`README_AI.md`](../README_AI.md) (documentación del proyecto).

**Sustituye:** `<HOST>` por el hostname o IP del servidor (p. ej. el hostname indicado en `OPERACIONES_SERVIDOR.md`).

---

## Cómo entrar por SSH

1. Desde tu máquina, con la **clave privada** autorizada en el servidor (no uses contraseña si el servidor tiene `PasswordAuthentication no`):
   ```bash
   ssh ubuntu@<HOST>
   ```
2. Tareas administrativas con privilegios:
   ```bash
   sudo -i
   ```
3. Si el acceso root directo está deshabilitado, todo el trabajo de root debe hacerse vía `sudo` desde `ubuntu` (coherente con `OPERACIONES_SERVIDOR.md`).

---

## Cómo revisar Fail2Ban

```bash
sudo systemctl status fail2ban
sudo fail2ban-client status
sudo fail2ban-client status sshd
```

Para otra jail (p. ej. `nginx-botsearch`):

```bash
sudo fail2ban-client status nginx-botsearch
```

---

## Cómo comprobar Git por SSH

Desde el usuario que use la deploy key (suele ser **root** para operaciones de despliegue en este entorno):

```bash
cd /var/www/reputalis
sudo git remote -v
```

Comprobar conexión con GitHub **sin** exponer claves:

```bash
sudo ssh -T git@github.com
```

Si falla el host key, revisar `~/.ssh/known_hosts` del usuario correspondiente. La clave dedicada y `Host github.com` suelen estar en `/root/.ssh/` (ver `OPERACIONES_SERVIDOR.md`).

---

## Cómo lanzar el informe diario de seguridad

El script está en `/usr/local/bin/daily-security-report.sh` (según `OPERACIONES_SERVIDOR.md`). Ejecución manual:

```bash
sudo /usr/local/bin/daily-security-report.sh
```

Copias locales habituales: `/root/reports/security/`. El cron suele estar en `/etc/cron.d/reputalis-security-report` (ver archivo para hora exacta).

---

## Cómo revisar logs clave

**Nginx (acceso y error):**

```bash
sudo tail -n 100 /var/log/nginx/error.log
sudo tail -n 100 /var/log/nginx/access.log
```

**Sistema y servicios:**

```bash
sudo journalctl -u nginx --no-pager -n 80
sudo journalctl -u postfix --no-pager -n 50
```

**Aplicación Laravel** (rutas típicas; ajustar si el despliegue difiere):

```bash
sudo tail -n 100 /var/www/reputalis/storage/logs/laravel.log
```

**Fail2Ban:**

```bash
sudo tail -n 80 /var/log/fail2ban.log
```

**SSH (intentos de acceso):**

```bash
sudo tail -n 80 /var/log/auth.log
```
