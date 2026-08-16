# Listora - Laravel Cloud Deployment Guide

## Prerequisites
- GitHub account: **primeeventsource-bit**
- Repository: **listora** (already pushed to GitHub)
- Laravel Cloud account at [laravel.cloud](https://laravel.cloud)

## Application Stack
- **Framework**: Laravel 12.0
- **PHP Version**: 8.2+
- **Frontend Build Tool**: Vite + Tailwind CSS
- **Database**: MySQL-compatible
- **Node Version**: 18+ (for npm build)

## Deployment Steps

### 1. Connect Laravel Cloud to GitHub
1. Log in to [Laravel Cloud Dashboard](https://laravel.cloud)
2. Click **+ New Project**
3. Select **GitHub** as the repository source
4. Authorize Laravel Cloud to access your GitHub account
5. Select the repository: `primeeventsource-bit/listora`
6. Choose the `main` branch

### 2. Configure Environment Variables
In the Laravel Cloud dashboard, set the following environment variables:

```env
APP_NAME=Listora
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com  # Replace with your actual domain
APP_KEY=                          # Laravel Cloud will generate this
APP_TIMEZONE=UTC

DB_CONNECTION=mysql
DB_HOST=<laravel-cloud-provided-host>
DB_PORT=3306
DB_DATABASE=listora
DB_USERNAME=<laravel-cloud-provided-user>
DB_PASSWORD=<laravel-cloud-provided-password>

SESSION_DRIVER=database
CACHE_DRIVER=file
QUEUE_CONNECTION=sync

LOG_CHANNEL=stack
LOG_LEVEL=info

BCRYPT_ROUNDS=12
```

**Important Notes:**
- Laravel Cloud will provide database connection details
- The `APP_KEY` can be generated in the dashboard or via CLI
- Use strong, randomly generated `DB_PASSWORD`

### 3. Database Setup
1. In the Laravel Cloud dashboard, enable **MySQL Database** for your project
2. Laravel Cloud will provide connection credentials
3. After deployment, Laravel Cloud will run migrations automatically OR you can manually run:
   ```bash
   php artisan migrate --force
   ```

### 4. Pre-Deployment Checklist

✅ **Application Configuration**
- [x] `composer.json` - PHP dependencies defined
- [x] `package.json` - Node dependencies defined
- [x] `vite.config.js` - Asset build configuration
- [x] `config/app.php` - Application settings
- [x] `database/migrations/` - Database schema files

✅ **Storage & Assets**
- [x] `public/` directory for static assets
- [x] `storage/` directory exists (for logs, uploads)
- [x] `.env.example` configured with all necessary variables

✅ **Git Configuration**
- [x] `.gitignore` properly excludes sensitive files (.env, vendor/, node_modules/)
- [x] `.git` initialized with initial commit pushed to GitHub
- [x] `.github/workflows/` - CI/CD pipelines (optional, can be enhanced)

### 5. Deploy to Laravel Cloud

#### Option A: Automatic Deployment (Recommended)
1. Push code to GitHub's `main` branch
2. Laravel Cloud will automatically detect changes and deploy
3. Watch the deployment status in the dashboard

#### Option B: Manual Deployment
1. In the Laravel Cloud dashboard, find your project
2. Click **Deploy**
3. Select the branch (main)
4. Review the deployment plan
5. Confirm deployment

### 6. Post-Deployment Tasks

After successful deployment:

1. **Verify Application Health**
   ```bash
   # Check if app is running
   curl https://your-domain.com
   
   # Check application logs
   # Via Laravel Cloud dashboard: View Logs
   ```

2. **Database Verification**
   - Verify all migrations ran successfully
   - Check database connectivity

3. **Asset Pipeline**
   - Vite will automatically build assets during deployment
   - Verify CSS/JS are loading correctly in browser

4. **Environment Variables**
   - Confirm all `.env` variables are set in Laravel Cloud
   - Test database connections
   - Verify cache/session drivers are working

### 7. Monitoring & Maintenance

**Enable Logging**
```env
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=info
```

**Monitor in Laravel Cloud Dashboard:**
- Application logs
- Database performance
- Storage usage
- Deployment history

**Set Up Automated Backups:**
- Enable database backups in Laravel Cloud settings
- Configure backup retention policy

### 8. Custom Domain Setup

If using a custom domain (e.g., listora.com):
1. In Laravel Cloud project settings, go to **Custom Domains**
2. Add your domain
3. Update your domain registrar's DNS:
   - Add CNAME record pointing to Laravel Cloud's endpoint
   - Or configure A records as provided by Laravel Cloud
4. Wait for DNS propagation (5-15 minutes typically)
5. SSL certificate will be automatically provisioned via Let's Encrypt

## Troubleshooting

### Deployment Fails
- Check Laravel Cloud logs for specific error
- Verify all required env variables are set
- Ensure `composer.json` and `package.json` have valid syntax
- Check PHP version compatibility (needs 8.2+)

### Database Connection Issues
- Verify `DB_HOST`, `DB_USER`, `DB_PASSWORD` in env
- Confirm database exists in Laravel Cloud
- Check database user has proper permissions

### Assets Not Loading
- Verify Vite build completed (check logs)
- Clear browser cache
- Check public/build/ directory exists on server

### Application Errors
- Check Laravel logs via dashboard: **Logs** tab
- Verify all migrations ran: Database tab
- Check environment variables match .env.example

## Additional Resources

- [Laravel Cloud Documentation](https://laravel.cloud/docs)
- [Laravel Deployment Guide](https://laravel.com/docs/12/deployment)
- [Vite Documentation](https://vitejs.dev/)
- [Tailwind CSS Documentation](https://tailwindcss.com/)

## Next Steps After Deployment

1. **Set Up Monitoring**
   - Configure error tracking
   - Set up uptime monitoring
   - Enable performance monitoring

2. **Configure Email**
   - Set up SMTP for Laravel mail
   - Configure email notifications

3. **Implement Analytics**
   - Add tracking to understand user behavior
   - Monitor key metrics

4. **Security Hardening**
   - Enable 2FA on Laravel Cloud account
   - Regular security audits
   - Keep dependencies updated

---

**Project**: Listora - Vacation Property Listing Platform  
**Repository**: https://github.com/primeeventsource-bit/listora  
**Framework**: Laravel 12.0  
**Last Updated**: 2026-08-15
