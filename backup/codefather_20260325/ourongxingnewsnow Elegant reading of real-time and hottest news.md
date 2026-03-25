---
title: "ourongxing/newsnow: Elegant reading of real-time and hottest news"
categories: 在线工具
sub-category: 自媒体
weight: "10"
recommend: "2"
sitelink: https://github.com/ourongxing/newsnow
description: Elegant reading of real-time and hottest news. Contribute to ourongxing/newsnow development by creating an account on GitHub.
logo:
tags:
---
[![](https://github.com/ourongxing/newsnow/raw/main/public/og-image.png)](https://github.com/ourongxing/newsnow/blob/main/public/og-image.png)

English | [简体中文](https://github.com/ourongxing/newsnow/blob/main/README.zh-CN.md) | [日本語](https://github.com/ourongxing/newsnow/blob/main/README.ja-JP.md)

> [!note] Note
> This is a demo version currently supporting Chinese only. A full-featured version with better customization and English content support will be released later.

***Elegant reading of real-time and hottest news***

## Features

- Clean and elegant UI design for optimal reading experience
- Real-time updates on trending news
- GitHub OAuth login with data synchronization
- 30-minute default cache duration (logged-in users can force refresh)
- Adaptive scraping interval (minimum 2 minutes) based on source update frequency to optimize resource usage and prevent IP bans
- support MCP server
```
{
  "mcpServers": {
    "newsnow": {
      "command": "npx",
      "args": [
        "-y",
        "newsnow-mcp-server"
      ],
      "env": {
        "BASE_URL": "https://newsnow.busiyi.world"
      }
    }
  }
}
```

You can change the `BASE_URL` to your own domain.

## Deployment

### Basic Deployment

For deployments without login and caching:

1. Fork this repository
2. Import to platforms like Cloudflare Page or Vercel

### Cloudflare Page Configuration

- Build command: `pnpm run build`
- Output directory: `dist/output/public`

### GitHub OAuth Setup

1. [Create a GitHub App](https://github.com/settings/applications/new)
2. No special permissions required
3. Set callback URL to: `https://your-domain.com/api/oauth/github` (replace `your-domain` with your actual domain)
4. Obtain Client ID and Client Secret

### Environment Variables

Refer to `example.env.server`. For local development, rename it to `.env.server` and configure:

```
# Github Client ID
G_CLIENT_ID=
# Github Client Secret
G_CLIENT_SECRET=
# JWT Secret, usually the same as Client Secret
JWT_SECRET=
# Initialize database, must be set to true on first run, can be turned off afterward
INIT_TABLE=true
# Whether to enable cache
ENABLE_CACHE=true
```

### Database Support

Supported database connectors: [https://db0.unjs.io/connectors](https://db0.unjs.io/connectors) **Cloudflare D1 Database** is recommended.

1. Create D1 database in Cloudflare Worker dashboard
2. Configure database\_id and database\_name in wrangler.toml
3. If wrangler.toml doesn't exist, rename example.wrangler.toml and modify configurations
4. Changes will take effect on next deployment

### Docker Deployment

In project root directory:

```
docker compose up
```

You can also set Environment Variables in `docker-compose.yml`.

## Development

> [!note] Note
> Requires Node.js >= 20

```
corepack enable
pnpm i
pnpm dev
```

### Adding Data Sources

Refer to `shared/sources` and `server/sources` directories. The project provides complete type definitions and a clean architecture.

For detailed instructions on how to add new sources, see [CONTRIBUTING.md](https://github.com/ourongxing/newsnow/blob/main/CONTRIBUTING.md).

## Roadmap

- Add **multi-language support** (English, Chinese, more to come).
- Improve **personalization options** (category-based news, saved preferences).
- Expand **data sources** to cover global news in multiple languages.

***release when ready*** ![](https://camo.githubusercontent.com/8042bf0836a2c53910a36755eacdfb75b32a4adc12d41270d9f669312c096581/68747470733a2f2f746573746d6e6262732e6f73732d636e2d7a68616e676a69616b6f752e616c6979756e63732e636f6d2f7069632f32303235303332383137323134365f7265635f2e6769663f782d6f73732d70726f636573733d626173655f77656270)

## Contributing

Contributions are welcome! Feel free to submit pull requests or create issues for feature requests and bug reports.

See [CONTRIBUTING.md](https://github.com/ourongxing/newsnow/blob/main/CONTRIBUTING.md) for detailed guidelines on how to contribute, especially for adding new data sources.

## License

[MIT](https://github.com/ourongxing/newsnow/blob/main/LICENSE) © ourongxing