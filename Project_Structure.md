# Limingdao (WebStack-Hugo) 项目目录架构说明

本文档对本项目的根目录架构及各项工程文件、数据脚本的用途进行了全景梳理，旨在帮助后续开发与维护人员快速定位文件。

## 1. 核心运行配置与文档
| 文件/目录 | 用途说明 |
| --- | --- |
| `config.toml` | **核心配置文件**。Hugo 站点的全局设置，包括站点标题、菜单栏配置、分类路由定义、预加载选项等。 |
| `vercel.json` | **Vercel 部署配置**。用于定制 Serverless 路由、重定向或构建行为。 |
| `README.md` | **项目主说明文档**。包含项目的入门指南、前后台规则介绍及版本更新日志。 |
| `Limingdao_Project_Manual.md` | **项目运维手册**。包含运维部署流程、内部运行机制及用户提审流程的详细文档。 |
| `Project_Report_Beta_1.0.2.md`| **阶段性重构报告**。记录了项目早期 Beta 1.0.2 版本的重构思想、迭代历史和 Bug 修复日志。 |
| `Project_Structure.md` | **项目架构说明**。即本文档，用于速查各文件定位。 |

## 2. Hugo 框架与内容目录
| 文件/目录 | 用途说明 |
| --- | --- |
| `content/` | **内容源目录**。存放所有的书签 Markdown 数据（如 `content/bookmarks/` 下存有各个导航条目）。 |
| `themes/` | **主题目录**。存放 `webstack` 主题文件，包括底层的 HTML 模板。 |
| `layouts/` | **自定义模板目录**。用于覆盖 `themes/` 中的默认布局页面，项目中的绝大部份界面重构都发生在这里。 |
| `static/` | **静态资源目录**。存放不需要经过编译的静态文件，如图片、`robots.txt`、第三方 JS 插件以及站内提交表单界面等，在构建时将原封不动地复制到根目录。 |
| `assets/` | **前端资产目录**。存放需要 Hugo 管道 (Pipes) 处理编译的源文件，如 SCSS/CSS 样式表等。 |
| `data/` | **数据目录**。存放静态数据文件，例如 `sitelink_status.json`（由拨测脚本生成的网站连通性数据）。 |
| `i18n/` | **多语言翻译目录**。存放 `zh-CN` 等多语言映射表。 |
| `archetypes/` | **内容模板目录**。在使用 `hugo new` 命令建立新内容时，所依据的 front-matter 模板。 |
| `public/` | **发布目录**。执行 `hugo` 构建后生成的最终纯静态网站文件，直接用于部署发布。 |
| `resources/` | **资源缓存目录**。Hugo 生成的图像处理缓存等，可加快后续编译速度。 |

## 3. DevOps 与第三方工具目录
| 文件/目录 | 用途说明 |
| --- | --- |
| `.github/` | **GitHub 自动化相关**。存放 GitHub Actions 的 Workflows 工作流配置（如 `deploy.yml`）。 |
| `.obsidian/` | **Obsidian 配置文件**。用于支持在本地使用 Obsidian 进行高效的 Markdown 书签编写、搜索与 Git 自动同步配置。 |
| `Clippings/` | **Obsidian Web Clipper 插件源目录**。可能为 Obsidian 网页剪藏插件保存捕获数据的默认目录。 |
| `skills/` | **AI 辅助或个人配置**。扩展能力的自定义配置或规则。 |
| `.git/` & `.gitignore`| **Git 版本控制系统**。 |

## 4. 自动化处理与爬虫数据脚本
项目中包含了大量的辅助脚本，主要用于数据的批量处理、清洗与爬取。

*   **数据导入与清洗脚本**：
    *   `import_csv.py` / `.ps1` / `.js`：不同语言版本的数据导入脚本，用于将收集到的 CSV 表格转换为 Hugo 所需的 Markdown 格式书签。
    *   `cleanup_duplicates.js`：用于扫描 `content/bookmarks/` 检测并清理重复收录的网址。
    *   `fix_markdown.py`：对残缺或格式不规范的 Markdown 头部 Frontmatter 进行自动化修复的工具。
    *   `force_recommend_2.js`：用于批量强制将特定书签的推荐等级(`recommend`) 提升到核心推流级别。
*   **数据抓取脚本 (Claw123导航数据)**：
    *   `scrape_claw123.py` & `analyze_claw123.py`：用于分析和爬取目标网站 `claw123.com` 数据的爬虫脚本。
    *   `setup_and_scrape.ps1`：一键配置环境并执行上述数据拉取动作的自动化批处理。
*   **状态与一致性审查脚本**：
    *   `audit_bookmarks.py` / `.ps1` / `.js`：各种语言编写的书签数据审计脚本。
    *   `audit_bookmarks_ascii.ps1`：包含 ASCII 或特定字符编码处理的审计检查工具。
*   **其它脚本**：
    *   `remove_imported.ps1`：导入完成后，用于快速清理过渡或残留的源文件。
    *   `post-build.js`：用于在 Hugo 构建完成后执行特定 Node.js 后处理任务。

## 5. 零散文件
*   `test.html` / `ref.html`：本地的临时抓取缓存网页、测试页面或开发调试遗留文件。
*   `.hugo_build.lock`：当前 Hugo 开启了本地 Server 产生的运行锁文件。
