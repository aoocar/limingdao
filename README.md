# Limingdao 网址导航 (WebStack-Hugo 定制版)

> **🎉 v1.1 新版本发布**：新增 [**站内网站提交系统**](#五-用户提交网站收录系统-v11-新增)！用户可直接在网站上提交网址，管理员通过 GitHub PR 审核后自动收录上线并同步到本地 Obsidian。完整技术文档请参阅 [**项目运维手册**](./Limingdao_Project_Manual.md)。

本项目基于开源的 [WebStack-Hugo](https://github.com/shenweiyan/WebStack-Hugo) 主题进行了深度的二次开发与重构，旨在提供一个结构严谨、支持多级分类、且高度可定制的纯静态网址导航站。

本说明文档详细梳理了本站的各项书签收录规则、前台展示逻辑以及后台配置方法，供运营者或开发者日常维护使用。

---

## 一、 快速使用与编译

本项目基于现代化的静态生成引擎 [Hugo](https://gohugo.io/) 构建。

*   **本地开发预览**：在终端进入项目根目录，运行 `hugo server -D`，然后在浏览器访问 `http://localhost:1313/`。
*   **编译静态站点**：运行 `hugo -D`，系统会将所有静态生成的网页输出至 `public` 文件夹，您可以将其部署到 Vercel、GitHub Pages、Nginx 或任何静态服务器。

---

## 二、 书签数据录入规则 (Frontmatter)

所有的书签卡片都作为独立的 Markdown 文件，存放在 `content/bookmarks/` 目录下。添加新网站只需在该目录下新建一个 `.md` 文件即可。

每个 Markdown 文件的顶部必须包含 YAML 格式的参数区（Frontmatter），系统完全依赖这些参数来决定卡片的归属和展示级别。

### 核心参数对照表

```yaml
---
title: "网站名称（如：YouTube）"
description: "简介：在 YouTube 上畅享你喜爱的视频... (前端会自动截断显示前20字)"
sitelink: "直达链接（如：https://www.youtube.com/）"
logo: "网站Logo图片地址 (留空则使用内置默认图或自动抓取 Favicon)"

# ----- 分类与层级 (核心) -----
categories: "影音娱乐"         # 必填。大类频道 (一级分类，L1)
sub-category: "视频"           # 必填。子级菜单 (二级分类，L2)

# ----- 排序与展示规则 (核心) -----
weight: 10                   # 必填。组内排序权重 (数字越小越靠前，默认建议写10)
recommend: 2                 # 必填。站内展示级别 (决定了卡片能上探到哪一级页面，见下文详述)
---
正文内容...
```

### 1. 分类映射机制保障 (Fallback)
为了防止旧数据因为缺失分类字段（`categories` 或 `sub-category`）导致页面生成出错或卡片丢失，系统内置了强大的空值容错机制：
*   **分类缺失**：如果某书签忘记填写 `categories` 或 `sub-category`，自动化脚本或默认排版会自动将其归属到 `"未分类"`（一级分类）和 `"默认"`（二级分类）下。
*   **确保归属**：所有页面、面包屑导航（Breadcrumbs）都可以坚固呈现这些孤儿节点，永不抛出 404 错误。

### 2. recommend (展示推荐等级) 规则
`recommend` 是本站最具特色的“提权维度”，您可以通过调高该数值让重要网站全站推流：

*   **`recommend: 0` （或留空）**
    *   **权限级别**: 基础收录。
    *   **展示位置**: 仅在最底层的**二级子分类列表页** (如 `/sub-category/视频/`) 普通展示。它绝不占用高级别页面的流量位。
*   **`recommend: 1`**
    *   **权限级别**: 轻度提权。
    *   **展示位置**: 在**二级子分类页**展示，同时向上穿透到**一级频道汇总页**（如 `/categories/影音娱乐/`）中进行归类展示。但不上主页。
*   **`recommend: 2` （及以上）**
    *   **权限级别**: 核心推荐重度推流！
    *   **展示位置**: 贯通全站三端！直接在**首页 (Homepage)** 显眼位置、**一级分类汇总页**、**二级分类页** 全部同步展示。

### 3. weight (权重排序) 规则
在同一个分类方块内，卡片的排列先后顺序严格遵从 `.ByWeight`，然后进行自动降级：
*   **第一优先级**: `weight` 升序（数值越小，越排在前面。`weight: 1` 永远在 `weight: 10` 前面）。
*   **第二优先级**: 如果 `weight` 相同，则对比书签创建的 `Date` 降序（越新加入的排在越前面）。
*   **第三优先级**: `Title` 标题字符排序。

---

## 三、 网站各级页面展示逻辑与组件

本站打破了传统 WebStack 单一扁平页面的局限，被重构为标准的 `首页 (Home) > 频道 (L1) > 细分(L2) > 详情页` 现代树状视图。

### 1. 首页 (Homepage / `index.html`)
*   **布局逻辑**：遍历整个站内 `recommend >= 2` 的顶级高优数据，并抽离出其中涉及到的 `一级分类` 和 `二级分类`，组合成大标签 `<h4>` 与子级锚点 `<h5>` 嵌套展示。
*   **防爆屏截断**：每个子区块 (`h5` 节点) 最多只展示 **12** 个精华书签卡片。如果属于该类的卡片超过 12 个，系统会在底部自动生成一枚漂亮的 **[查看全部 xx 个「名称」 URL →]** 按钮，引导用户进入完整的二级分类页。
*   **导航直达**：主页内每个 `h4` 一级分类大标题右侧均附带 **[查看频道 ➔]** 按钮，一键进入频道专页。

### 2. 左侧边栏 (Sidebar) 结构
*   所有一级分类会自动抓取生成一级手风琴菜单，它的子菜单全量映射了包含的二级子分类。
*   由于二级分类数量可能众多，在展开每个一级分类的下拉菜单时，第一项永远置顶固定为：**[📌 全部「大类名」]**，方便用户统揽全局。
*   除了特殊的主页锚定跳跃，全站去往详情页的路由均遵循标准格式，左侧边栏亦具有极高的粘度体验。

### 3. 一级分类页 (Primary Category / `/categories/`)
*   专门设计的频道聚合页（如 `limingdao.com/categories/影音娱乐/`）。
*   页面**去除了左侧边栏**，留出广阔的内容视野。
*   **展示逻辑**：它抓取内部所有包含 `recommend >= 1` 的书签聚合，同样以 `h5` （二级分类分块） -> 卡片阵列 的排列方式重塑。
*   自带智能面包屑层级 `首页 > 影音娱乐`。

### 4. 二级分类页 (Secondary Category / `/sub-category/`)
*   专门的最下级内容列表页（如 `limingdao.com/sub-category/视频/`）。
*   同样**去除了左侧边栏**。
*   **展示逻辑**：不再进行任何 `recommend` 过滤（`ge 0`），毫无保留地展示划归进该小类的**全部**书签资源。
*   严谨的面包屑层级树：`首页 > 影音娱乐 > 视频`，实现完美的流量反哺。

### 5. API 兜底逻辑 (Favicon 智能获取)
为了使得全站即便在资源缺失下也足够美观，对于前端未录入 `logo` 单独配图的书签文件，底层模板采用自动探针技术：
1.  自动解析填入的 `sitelink` 主机域名。
2.  默认请求配置好的 Favicon 提取 API (`config.toml` 中配置 `faviconAPI = "https://www.google.com/s2/favicons?sz=64&domain="` 或 `"https://api.iowen.cn/favicon/"`) 。
3.  如果目标网站极其冷门连 API 都无法抓取导致图片加载引发 HTTP 异常，其图片节点的 `onerror` 事件会瞬间被捕获，将展示图片替换为您配置的 `$.Site.Params.defaultLogo` (本地缺省图标)。

---

## 四、 核心参数配置 (config.toml)

站点的大规模基础调度中心位于根目录 `config.toml` 文件：

```toml
[params]
    # 全站预加载设置
    enablePreLoad = true
    textPreLoad   = "Limingdao 正在加载"
    
    # 默认 Logo 配置 (当卡片缺配且 API 抓不到时展示)
    defaultLogo = "assets/images/default.webp"
    
    # 图标抓取 API 引擎路由设定
    faviconAPI  = "https://www.google.com/s2/favicons?sz=64&domain="
    
[taxonomies]
    # Hugo 底层依赖这些声明来开放目录编译权限，若移除将引发 404
    category = "categories"
    tag = "tags"
    sub-category = "sub-category" 
```

## 五、 用户提交网站收录系统 (v1.1 新增)

本站新增了**站内网站提交功能**，外部用户无需注册任何账号即可推荐优质网站，管理员审核后自动上线。

### 功能亮点

*   🌐 **站内提交**：用户在 `www.limingdao.com/submit/` 填写表单即可提交，无需访问 GitHub
*   🔒 **PR 审核制**：每条提交自动生成 GitHub Pull Request，管理员审核合并后才会上线
*   📝 **自动生成书签**：PHP 脚本自动生成符合项目规范的 `.md` 文件（含 YAML Frontmatter）
*   🔄 **Obsidian 同步**：通过 Obsidian Git 插件自动拉取，合并后的书签直接出现在本地 Obsidian 仓库
*   🇨🇳 **中国友好**：后端 PHP 运行在阿里云宝塔服务器上，通过服务器端 cURL 调用 GitHub API，内置重试机制

### 审核流程

```
用户提交 → PHP 创建 PR → 管理员在 GitHub 审核
  ├── ✅ Merge → Actions 自动部署 → 网站更新 + Obsidian 同步
  ├── ✏️ 修改后 Merge → 可在线调整 recommend/分类等字段
  └── ❌ Close → 拒绝收录
```

### 技术栈

| 组件 | 技术 | 位置 |
|---|---|---|
| **前端表单** | HTML + CSS (glassmorphism 暗色主题) + Vanilla JS | `static/submit/index.html` |
| **后端接口** | PHP 7.x + cURL（含重试与 XSS 防护） | `static/api/submit.php` |
| **密钥存储** | JSON 配置文件（服务器本地，不入 Git） | `api/.submit_config.json` |
| **代码管理** | GitHub API v3（分支 + 文件 + PR） | 远程 |
| **自动部署** | GitHub Actions + rsync → 宝塔面板 | `.github/workflows/deploy.yml` |
| **本地同步** | Obsidian Git 插件（每 10 分钟自动 pull） | `.obsidian/plugins/obsidian-git/` |

> 详细的架构说明、服务器配置步骤和防坑指南请参阅 [Limingdao 项目运维手册 §六](./Limingdao_Project_Manual.md)。

---

## 结语
享受管理属于您的新一代、强劲拓展能力的 URL 导航站吧！如有深层模板需求，您可以在 `themes/webstack/layouts/` 内任意覆盖并扩展核心。
