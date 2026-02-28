# Limingdao 网址导航 - 项目完全开发与运营手册 (面向人类与AI)

> **文档定位**：本文档是 Limingdao WebStack-Hugo 网址导航项目的**唯一权威指南**。它旨在防止后续代码修改或升级引发破坏性问题，并为未来的重构、扩展提供底层逻辑说明。**无论您是接手的开发人员、网站运营者，还是协助分析代码的 AI Agent，在进行任何修改前，请务必完整阅读本指南。**

---

## 一、 项目概述与核心框架

本项目是一个基于开源 WebStack-Hugo 主题进行深度二次开发与重构的纯静态网址导航站。
*   **核心框架**：[Hugo](https://gohugo.io/) (极速的 Go 语言静态网站生成器)
*   **数据存储方案**：无传统关系型数据库 (NoSQL)。全站数据依托于 Markdown 文件的 Frontmatter (`YAML` 元数据) 驱动。
*   **核心亮点**：支持多级分类（打破了传统单页导航的局限）、自动化抓取丢失图标、完全由 Git + GitHub Actions 驱动的全量/增量自动化部署。

### 1.1 核心目录架构总览

在对本项目进行修改时，请AI或开发者严格遵守以下目录的职责划分：

*   `content/bookmarks/`：**唯一的网址数据源头**。所有收录的网站卡片都以单独的 `.md` 文件存在于此。
*   `themes/webstack/layouts/`：**视图层 (View)**。重构后的多级页面、主页渲染逻辑均在这里。不要轻易修改带有 `range` 复杂遍历的模板代码，除非您完全理解底层的筛选逻辑。
*   `static/assets/`：**静态资源层**。存放网站默认 Logo、背景图、Favicon 等。
*   `config.toml`：**全局调度中心**。控制分类注册、API节点、SEO。
*   `.github/workflows/`：**CI/CD 部署层**。控制与阿里云宝塔面板或 Vercel 的通讯链路。

---

## 二、 核心机制：Taxonomy 与 Frontmatter 数据管理

项目的数据流转完全依赖于 Hugo 的分类学 (Taxonomy) 和 Markdown 的 Frontmatter。

### 2.1 网址卡片 (Bookmark) 录入规范

在 `content/bookmarks/` 下新建 `.md` 文件，必须包含以下 YAML 头：

```yaml
---
title: "网站名称 (如：YouTube)"
description: "网站简介 (控制在20字以内)"
sitelink: "https://www.youtube.com/"
logo: "网站Logo图片地址 (留空则使用内置默认图或自动抓取 Favicon)"

# ----- 路由与分类映射层 -----
categories: "影音娱乐"         # 必填：一级大类 (频道)
sub-category: "视频"           # 必填：二级小类 (具体菜单)

# ----- 曝光算法与展示层 -----
weight: 10                   # 组内排序权重 (数字越小越靠前，默认建议写10)
recommend: 2                 # 站内推流级别 (核心机制，见 2.2 小节)
---
```

### 2.2 `recommend` 流量提权与展示机制 (🚨 核心防坑)

这是本站重构的最核心逻辑。传统的静态站往往是全部数据平铺，而本站实现了根据星级自动向高层级页面“推流”的功能：

1.  **`recommend: 0` (基础收录)**：仅存在于最底层的**二级子分类列表页** (如 `/sub-category/视频/`)。它不会占用高级别页面的流量位。
2.  **`recommend: 1` (轻度提权)**：在二级页展示的同时，向上穿透到**一级频道汇总页**（如 `/categories/影音娱乐/`）中进行展示。不上首页。
3.  **`recommend: 2` 及以上 (核心推荐)**：最高权限。横贯全站！在**首页 (Homepage)**、**一级分类页**、**二级分类页** 三端同步展示。

**AI 修改提示**：如果您被要求“将某个网站放到首页”，除了修改分类，您只需要将对应文件的 `recommend` 修改为 `2`。

### 2.3 Hugo 分类陷阱警告 (🚨 AI 分析必读)

**历史背景**：早期的 Frontmatter 中使用了 `categories` 作为一级分类的键名，但为了遵循 Hugo 的 Taxonomy 规范，同时避免重命名数百个文件导致冲突，我们在 `config.toml` 中进行了映射：
```toml
[taxonomies]
  category = "categories"    # 左侧是 Hugo 底层调用的 taxonomy 名，右侧是文件中实际存在的字段名
  "sub-category" = "sub-category"
```
**严重警告**：在处理 Hugo 主页的分类遍历时 (例如 `index.html` 中的 `$.Site.Taxonomies.category`)，**必须严格使用 `category` 作为底层变量名**，而前端 Markdown 文件里依然保留使用 `categories: "xxx"`。切勿在模板中混用两者，否则会导致页面整块空白。

---

## 三、 页面层级与渲染逻辑 (视图层)

本站被重构为标准的 `首页 (Home) > 频道 (L1) > 细分 (L2) > 详情页` 现代树状视图。

*   **首页 (`layouts/index.html`)**：通过双重嵌套 `range`，过滤出全站所有具有 `recommend >= 2` 的卡片，并自动反向推导出它们所属的大类和小类，生成分块 UI。超过 12 个卡片时自动显示“查看所有”按钮引导下沉。
*   **左侧边栏 (`layouts/partials/sidebar.html`)**：利用 Hugo 内置函数自动归集所有分类构建下拉手风琴菜单。确保了“高内聚低耦合”。
*   **一级分类页 (`layouts/category/term.html`)**：聚合 `recommend >= 1` 的数据。无侧边栏。
*   **二级分类页 (`layouts/sub-category/term.html`)**：展示 `recommend >= 0` 的全部数据，是最完整的资料库底座。无侧边栏。

---

## 四、 自动化脚本与容错兜底网络

为了降低人工操作失误带来的问题，项目内嵌了强大的容错处理：

### 4.1 自动清理与合规审计
在根目录下存放了相关脚本，供管理员在长期运营后运行：
*   **`cleanup_duplicates.js`**：查找并删除具有相同 URL 的重复网站。
*   **`audit_bookmarks.js` / `.py`**：扫描缺少 `title`, `sitelink`, `category` 的劣质书签。

### 4.2 空值分类容错 (Fallback)
如果某个书签忘记写分类，系统会在编译时自动将其归集到 `"未分类"` -> `"默认"` 层级下，坚决防止 Hugo 抛出致命的 404 导致整个编译流水线崩溃。

### 4.3 动态打底图 (Favicon Fallback)
为了解决没有配置 `logo` 图片的站点的显示问题，前端采用了探针容错技术：
1. 取网址的 Hostname。
2. 拼接 `config.toml` 中的 `faviconAPI` 去获取源站图标。
3. 若遇死链触发 img 的 `onerror` 事件，瞬间运用 JavaScript 替换展示 `defaultLogo` (内置在 `assets/` 中的默认图片)。保证界面永远规整一致。

---

## 五、 环境搭建与全链路自动化部署

### 5.1 本地测试 (Dev)
如果进行模板级或 CSS 级的开发，请在本地终端执行：
```bash
hugo server -D
```
访问 `http://localhost:1313` 进行热重载预览。

### 5.2 生产环境部署方案 1：Vercel
完全支持零配置的一键导入 Vercel 部署。只需注意在 Vercel 环境变量中设置 `HUGO_VERSION = 0.120.0` 确保支持 extended sass 功能。

### 5.3 生产环境部署方案 2：阿里云宝塔 + GitHub Actions 流水线 (当前主方案)
这是利用 GitHub 服务器在云端编译后，通过 `rsync` 安全传输到阿里云宝塔面板环境的链路体系。

**运维与未来迁移防坑指南**：
1.  **私钥环境**：服务器本地必须有 SSH 密钥对，并将私钥置于 GitHub Secrets (`ALIYUN_SERVER_SSH_KEY`)，公钥加入 `authorized_keys`。
2.  **`rsync` 握手**：目标服务器必须安装了 `rsync` 组件。
3.  **宝塔防跨站限制规避 (`.user.ini`)**：宝塔面板默认在网站根目录锁定 `.user.ini` 导致第三方无法覆盖删除源文件。因此，在 `.github/workflows/deploy.yml` 的执行脚本中，**必须**挂载 `--exclude='.user.ini'` 参数。如果未来有 AI 或开发者不知情地移除了这个屏蔽词，直接会导致 Actions 管道发生 Code 23 权限不足错误并彻底中断自动发布流程！

---

## 六、 用户提交网站收录系统 (v1.1 新增)

本系统允许外部用户在网站上直接提交网址收录请求，管理员审核通过后自动上线，同时同步到本地 Obsidian 仓库。

### 6.1 系统架构

```
用户访问 www.limingdao.com/submit/
        ↓
  填写站内提交表单（暗色主题）
        ↓
  前端 JS → POST /api/submit.php
        ↓
  PHP 脚本（宝塔服务器端执行）
  ├── 校验表单数据
  ├── 调用 GitHub API（cURL + 重试机制）
  │   ├── 获取 main 分支 SHA
  │   ├── 创建提交分支 submit/xxx
  │   ├── 在新分支创建 .md 书签文件
  │   └── 创建 Pull Request
  └── 返回提交结果
        ↓
  管理员在 GitHub 审核 PR → 合并 → 自动部署 → Obsidian 同步
```

### 6.2 核心文件

| 文件 | 职责 |
|---|---|
| `static/submit/index.html` | 提交表单前端页面 |
| `static/api/submit.php` | PHP 后端接口（含 cURL 重试、XSS 防护） |
| 服务器: `api/.submit_config.json` | 🔒 密钥配置（不在 Git 中） |

### 6.3 管理员审核流程

当用户通过网站提交网站后，系统会自动在 GitHub 仓库创建一个 Pull Request。管理员按以下流程审核：

**第一步：查看 PR 列表**
*   登录 GitHub → 进入仓库 `aoocar/limingdao` → 点击 **Pull requests** 标签页
*   提交生成的 PR 标题格式为：`📎 收录网站: XXX`

**第二步：审核 PR 内容**
*   点击进入 PR，查看自动生成的信息表格（网站名称、链接、分类、简介等）
*   点击 **Files changed** 标签页，查看将要添加的 `.md` 书签文件内容
*   确认：网站链接可访问且无恶意、分类准确、描述合理

**第三步：处理 PR**
*   ✅ **通过**：点击 **Merge pull request** → **Confirm merge** → 自动部署上线
*   ❌ **拒绝**：点击 **Close pull request** 关闭
*   ✏️ **修改后收录**：在 **Files changed** 中点击铅笔图标，可在线编辑 `recommend`、`categories` 等字段后再合并

**第四步：同步到 Obsidian**
*   Obsidian Git 插件每 10 分钟自动拉取，合并后新文件自动出现在 `content/bookmarks/`
*   也可在 Obsidian 中 `Ctrl+P` → `Obsidian Git: Pull` 手动拉取

### 6.4 提交入口
*   **侧边栏**：左侧导航栏底部红色「✈ 提交网站」链接
*   **直达**：`https://www.limingdao.com/submit/`

### 6.5 服务器维护提醒 (🚨 AI/运维必读)
*   `api/.submit_config.json` 是手动创建在服务器上的敏感文件，**绝不能**纳入 Git
*   `deploy.yml` 中 `--exclude='api/.submit_config.json'` 参数**不可移除**
*   宝塔面板上该站点必须启用 PHP（非纯静态模式）
*   PHP 脚本内置 cURL 2 次重试（间隔 2 秒），适应中国到 GitHub 的网络波动

---

## 七、 针对后续重构的前瞻性建议

*   **数据迁移**：如果将来打算转向 Laravel / Vue 全栈方案，可编写脚本批量读取 `content/bookmarks/` 下的 Markdown YAML 写入 MySQL/MongoDB。数据高度规范化，迁移成本极低。
*   **UI 升级**：重点关照 `themes/webstack/assets/css/` 下的源文件。
*   **对 AI 的寄语**：本项目是 **静态编译 + 轻量 PHP** 混合架构。Hugo 负责前端渲染，唯一的 PHP 后端仅用于用户提交接口 (`api/submit.php`)。不要引入重量级后端框架。

> *文档更新日期：2026年02月*
> *最终解释权归 Limingdao 开发与运维团队所有。*

