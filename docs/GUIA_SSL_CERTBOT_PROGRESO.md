# SSL con Certbot + Nginx — Progreso y referencia rápida

Documento para recuperar contexto si hay que retomar o deshacer cambios. **No sustituye** backups en el servidor.

---

## Sistema

- **SO:** Ubuntu 24.04.3 LTS (Noble)
- **Web server:** Nginx activo, `nginx -t` OK (según contexto inicial)
- **Proyecto Laravel:** `/var/www/reputalis`

---

## Red / DNS

- **IPv4 pública del VPS:** `72.60.92.249` (comprobada con `curl -4 -s ifconfig.me`)
- **Dominios:** `reputalis.org`, `www.reputalis.org`
- **Comprobación DNS** (`dig +short A …`): ambos apuntan a `72.60.92.249`

---

## Nginx — qué está activo

Solo hay un sitio habilitado en `sites-enabled/`:

- **Enlace:** `/etc/nginx/sites-enabled/reputalis` → `/etc/nginx/sites-available/reputalis`
- **`server_name` en el vhost activo:** `reputalis.org`, `www.reputalis.org`, y la IP (redundante en `server_name`; se puede limpiar después con calma)

**Archivos en `sites-available` que también mencionan el dominio (no habilitados como enlace):**

- `reputalis.org` — mismo `server_name` apex+www; **no** está en `sites-enabled`
- `default` — incluye `reputalis.org`; **no** está en `sites-enabled`

→ Sin conflicto activo mientras solo exista el symlink `reputalis` en `sites-enabled`.

---

## Copia de seguridad del vhost (antes de Certbot)

**Archivo respaldado:** el vhost que Nginx usa de verdad.

```text
/etc/nginx/sites-available/reputalis.bak.20260402105113
```

**Restaurar en caso de problema** (ejemplo; ajustar nombre del `.bak` si creas otro):

```bash
cp -a /etc/nginx/sites-available/reputalis.bak.20260402105113 /etc/nginx/sites-available/reputalis
nginx -t && systemctl reload nginx
```

---

## Certbot — desplegado en producción

- **Paquetes:** `certbot` + `python3-certbot-nginx` (Ubuntu 24.04; p. ej. Certbot 2.9.x).
- **Emisión / despliegue:** `certbot --nginx … -d reputalis.org -d www.reputalis.org --redirect` (correo Let's Encrypt en la cuenta de la CA).
- **Certificado en disco:** `/etc/letsencrypt/live/reputalis.org/fullchain.pem` y `privkey.pem`.
- **Renovación automática:** `systemctl status certbot.timer`; prueba en seco: `certbot renew --dry-run`.

---

## Firewall (UFW) — imprescindible para HTTPS desde Internet

Con **default deny incoming**, hace falta **además del puerto 80**:

```bash
ufw allow 443/tcp
ufw reload
```

Sin **443** permitido, los navegadores externos suelen ver timeout o “no carga” en `https://`, aunque `curl` desde el propio VPS funcione.

---

## Estado y backups

Configuración verificada operativa: HTTP→HTTPS, `nginx -t` OK, `certbot renew --dry-run` OK. Antes de cambios manuales grandes en el vhost o de re-ejecutar Certbot:

```bash
cp -a /etc/nginx/sites-available/reputalis /etc/nginx/sites-available/reputalis.bak.$(date +%Y%m%d%H%M%S)
```

Ejemplo de backup ya creado en una tanda anterior:

```text
/etc/nginx/sites-available/reputalis.bak.20260402105113
```

---

## Notas

- Revisar en servidor: `APP_URL=https://reputalis.org` (y `ASSET_URL` si aplica) en `.env`.
- Zona horaria / CSAT en panel Filament es independiente de SSL.
