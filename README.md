# Verum

Gemstone & jewellery auction marketplace.

## Stack

| Layer | Choice |
|---|---|
| Backend | Laravel 13 (PHP 8.4) |
| Rendering | Blade + Livewire 4 (server-rendered for SEO) |
| UI | Flux UI + Tailwind CSS 4 |
| Auth | Laravel Fortify (2FA, passkeys, email verification) |
| Database | PostgreSQL 17 |
| Cache / Queue | Redis 7 + Horizon |
| Realtime | Laravel Reverb (live bidding, outbid alerts) |
| Search | Meilisearch + Laravel Scout |
| Media | Spatie Media Library |
| Roles | Spatie Permission |

Everything runs in Docker. **PHP is not installed on the host** — run PHP commands
inside the `app` container (the `make` targets do this for you).

## First run

```bash
cp .env.example .env     # if you don't already have a .env
./verum setup
```

`make` is not installed in this WSL environment, so use the `./verum` script.
A `Makefile` with the same targets is included for environments that have make.

Then open:

| Service | URL |
|---|---|
| App | http://localhost:8000 |
| Mailpit (caught emails) | http://localhost:8025 |
| Meilisearch | http://localhost:7700 |
| Horizon (queues) | http://localhost:8000/horizon |
| Reverb (WebSocket) | ws://localhost:8080 |
| Postgres | localhost:5433 |
| Redis | localhost:6380 |

Host ports avoid the defaults (5433, 6380) so they don't collide with a
Postgres/Redis already running on Windows or in another project.

## Viewing it in a browser

The stack is already running after `./verum setup`. Open
**http://localhost:8000** in your normal (Windows) browser — WSL mirrored
networking means `localhost` reaches the containers with no port forwarding.

`./verum urls` prints every local URL; `./verum health` checks the stack and
the main pages in one go.

Assets are served by the Vite dev container, which binds `0.0.0.0` inside
Docker but advertises `http://localhost:5173` to the browser (`server.origin`
in `vite.config.js`). Without that the page loads unstyled, because a browser
cannot connect to `0.0.0.0`.

## Daily use

```bash
./verum up            # start
./verum down          # stop
./verum shell         # bash inside the app container
./verum migrate       # run migrations
./verum test          # run the test suite
./verum check         # lint + types + tests
./verum assets        # production asset build
./verum logs app      # tail one service
./verum artisan <cmd> # any artisan command
```

### WSL gotcha

Bind-mounted files can lose their executable bit, which breaks
`vendor/bin/pint` and `node_modules/.bin/vp`. If you hit a
`permission denied` on either, run `./verum fix-perms`.

## Using pgAdmin on Windows instead of the container

The bundled Postgres is the default. To point the app at a Postgres running on
Windows instead, set in `.env`:

```
DB_HOST=host.docker.internal
DB_PORT=5432
```

and comment out the `postgres` service dependency if you don't want the
container running at all. WSL is in mirrored networking mode, so the Windows
Postgres must be listening and accepting connections for this to work.

## Notes

- `UID`/`GID` in `.env` map the container user to your host user so files
  created in the bind mount stay editable from WSL.
- The production image target (`prod` in `docker/php/Dockerfile`) bakes source
  in and disables opcache timestamp validation; dev uses a bind mount.
