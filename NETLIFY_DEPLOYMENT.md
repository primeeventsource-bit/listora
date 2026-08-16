# Listora on Netlify

## Quick Deploy to Netlify

This folder contains the **Listora static preview site** configured for Netlify deployment.

### What's Included
- `listora-home-preview.html` - Complete static website with embedded styles
- `netlify.toml` - Netlify configuration with caching, redirects, and security headers

### Deploy to Netlify in 3 Steps

#### Step 1: Connect to Netlify
1. Go to [netlify.com](https://netlify.com)
2. Sign up or log in with GitHub
3. Click **Add new site** → **Import an existing project**
4. Select your GitHub account
5. Choose the **listora** repository
6. Click **Deploy site**

#### Step 2: Configure Domain
1. After deployment, go to **Site settings**
2. In the **Domain management** section, click **Add custom domain**
3. Enter: `listora1.com`
4. Verify domain ownership in your registrar's DNS settings
5. Add the provided CNAME record to your DNS

#### Step 3: Enable HTTPS (Automatic)
- Netlify automatically provisions SSL certificate via Let's Encrypt
- All traffic redirects to HTTPS

### Site Details
- **File**: `listora-home-preview.html`
- **Size**: Single static HTML file (all CSS embedded)
- **Load Time**: ~100-200ms globally
- **Cost**: Free (Netlify free tier)
- **Bandwidth**: Unlimited for free tier

### Features Configured
✅ Security headers (XSS protection, CSRF mitigation)  
✅ Cache optimization (1 hour for HTML, 1 year for assets)  
✅ Automatic HTTPS/SSL  
✅ Global CDN distribution  
✅ Zero downtime deployments  

### Update Your Site
1. Edit `listora-home-preview.html`
2. Commit: `git add . && git commit -m "Update listing content"`
3. Push: `git push origin main`
4. Netlify automatically detects changes and redeploys

### DNS Configuration
Point your domain `listora1.com` to Netlify:

**For Namecheap, GoDaddy, etc.:**
1. Log into your domain registrar
2. Find **DNS Settings** or **DNS Management**
3. Add CNAME record:
   - Name: `listora1`
   - Value: `<your-netlify-domain>.netlify.app` (provided by Netlify)

**Or use Netlify's nameservers:**
- Netlify provides 4 nameservers
- Update your domain registrar to use these nameservers

### Monitoring
- **Netlify Analytics**: Free site analytics
- **Build Logs**: View deployment status in dashboard
- **Performance**: Netlify provides performance monitoring

### Support
- Netlify Docs: [docs.netlify.com](https://docs.netlify.com)
- Community: [netlify.com/support](https://netlify.com/support)

---
**Project**: Listora Static Preview  
**URL**: https://listora1.com  
**Deployment Platform**: Netlify  
**Status**: Ready to deploy
