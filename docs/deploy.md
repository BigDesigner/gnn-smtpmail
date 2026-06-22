# Deployment Procedures

## CI/CD Pipeline
- **Linting:** Automated PHPCS check on push.
- **Testing:** Unit tests run via PHPUnit (if applicable).
- **Build:** Asset compilation (SCSS/JS) via Webpack/Vite.

## Staging & Production
- **Staging:** Sync `main` branch to staging environment.
- **Production:** Deploy tagged releases to live server.
