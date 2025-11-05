# API Key Wildcard Plugin

📚 **[Full Documentation & FAQ (English)](https://faq.markus-michalski.net/en/osticket/api-key-wildcard)**

📚 **[Komplette Dokumentation & FAQ (Deutsch)](https://faq.markus-michalski.net/de/osticket/api-key-wildcard)**

- Complete guide with troubleshooting, best practices, and advanced usage

## Overview

The **API Key Wildcard Plugin** provides a **separate API endpoint** (`/api/wildcard.php`) that allows API keys with IP address `0.0.0.0` to accept requests from **any** IP address.

This solves a common development problem: osTicket's native API requires you to specify a specific IP address for each API key. This is secure for production, but cumbersome during development when your IP address changes frequently.

## ⚠️ Security Warning

**Only use wildcard API keys (0.0.0.0) in development environments!**

In production environments, API keys should always be bound to specific IP addresses.

## Key Features

- ✅ **Separate wildcard endpoint** - Standard API remains unchanged and secure
- ✅ **No core modifications** - True plugin, can be easily enabled/disabled
- ✅ **Automatic installation** - Just enable the plugin, no manual file copying
- ✅ **Automatic updates** - Version detection and auto-update on plugin file changes
- ✅ **Apache & NGINX support** - Works with both web servers

## Requirements

- **osTicket**: 1.18.x
- **PHP**: 7.4+ (recommended: PHP 8.1+)
- **Web Server**: Apache (with mod_rewrite) or NGINX

## Quick Start

### 1. Install Plugin

```bash
# Method 1: ZIP Download
# Download from: https://github.com/markus-michalski/osticket-plugins/releases
unzip api-key-wildcard-vX.X.X.zip
cp -r api-key-wildcard /path/to/osticket/include/plugins/

# Method 2: Git Clone
git clone https://github.com/markus-michalski/osticket-plugins.git
ln -s /path/to/osticket-plugins/api-key-wildcard \
      /path/to/osticket/include/plugins/api-key-wildcard
```

### 2. Enable Plugin

1. **Admin Panel** → **Manage** → **Plugins**
2. Find "API Key Wildcard Support"
3. Click **Enable**

The plugin automatically installs the wildcard endpoint and updates `.htaccess`.

### 3. Create API Key

1. **Admin Panel** → **Manage** → **API Keys**
2. Click **Add New API Key**
3. Set **IP Address**: `0.0.0.0`
4. Enable **Can Create Tickets**: ✅
5. **Save**

### 4. Use Wildcard Endpoint

```bash
# Instead of: POST /api/tickets.json (IP-restricted)
# Use:        POST /api/wildcard/tickets.json (any IP)

curl -X POST \
  -H "X-API-Key: YOUR_API_KEY_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "subject": "Test Ticket",
    "message": "This is a test message"
  }' \
  http://your-osticket/api/wildcard/tickets.json
```

## Use Cases

- **Local Development** - Dynamic IP addresses
- **API Testing** - Testing from different locations
- **MCP Servers** - [claude-mcp-osTicket](https://faq.markus-michalski.net/en/mcp/osticket) integration
- **CI/CD Pipelines** - Automated testing with dynamic IPs

## Documentation

📚 **[Full Documentation & FAQ (English)](https://faq.markus-michalski.net/en/osticket/api-key-wildcard)**

📚 **[Komplette Dokumentation & FAQ (Deutsch)](https://faq.markus-michalski.net/de/osticket/api-key-wildcard)**

The FAQ includes:

- Detailed installation instructions (Apache & NGINX)
- Troubleshooting guide (401, 404, installation errors)
- Security best practices
- Update procedures
- Integration examples (MCP Server)
- Architecture details
- API endpoint compatibility

## 📄 License

This Plugin is released under the GNU General Public License v2, compatible with osTicket core.

See [LICENSE](./LICENSE) for details.

## 💬 Support

For questions or issues, please create an issue on GitHub:

[Github Issues](https://github.com/markus-michalski/osticket-api-key-wildcard/issues)

Or check the [FAQ](https://faq.markus-michalski.net/en/osticket/api-key-wildcard) for common questions.

## ☕ Support Development

Developed by [Markus Michalski](https://github.com/markus-michalski)

This plugin is completely free and open source. If it saves you time or makes your work easier, I'd appreciate a small donation to keep me caffeinated while developing and maintaining this plugin!

[![Donate](https://img.shields.io/badge/Donate-PayPal-blue.svg)](https://paypal.me/tondiar)

Your support helps me continue improving this and other osTicket plugins. Thank you! 🙏

## Changelog

See [CHANGELOG.md](./CHANGELOG.md) for version history.
