# TicketFlow (Twig project)

This is a small PHP app using Twig templates. This README includes deployment notes for hosting the app from a Git repository.

Getting started (development)

- Copy `config.example.php` to `config.php` and adjust `app_base` or `data_dir` as needed.
- Run `composer install` to install dependencies (if not committed).
- Ensure `cache/` directory is writable by the webserver.

Deployment checklist

- PHP 8.1+ (this project was tested with PHP 8.2.x).
- Webserver (Apache with mod_rewrite + AllowOverride All, or Nginx with equivalent rewrite rules).
- Run `composer install --no-dev --prefer-dist` on the server.
- Create `config.php` from `config.example.php` or set environment variables: `APP_BASE`, `DATA_DIR`.
- Ensure `cache/` is writable.

Security

- Do NOT commit `config.php` with real credentials. Use environment variables or a server-only `config.php`.
- Use HTTPS in production and set `session.cookie_secure = 1`.

Useful commands

```bash
composer install --no-dev --prefer-dist
```

If you want automated deploy from GitHub, consider adding a CI that SSHs into the server and runs `composer install` and any cache warmups.
