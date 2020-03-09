# DEFINE-SERVER 插件使用说明



## 前言:

由于未知原因，Wordpress屏蔽了来自中国的访问，这就造成了服务器在中国的wordpress无法进行正常的更新与安装主题等功能。根据网络现有资源，我“组合”出了一个插件来解决此问题。

站在巨人的肩膀上

1. [wp-china-yes](https://github.com/sunxiyuan/wp-china-yes) 这个插件可以直接替换掉服务器，不过没有自定义功能。
2. [wp-smtp](https://wordpress.org/plugins/wp-smtp/) 这个插件提供了简介的配置页面。

所以我拿了1的替换服务器部分代码加上2的配置页面，组成了“DEFINE SERVER”。

## 原理：

插件功能通过替换Wordpress国内无法访问的域名来实现。

核心代码如下：

```php
$url = str_ireplace( 'api.wordpress.org', $wsOptions["apiserver"], $url );
$url = str_ireplace( 'downloads.wordpress.org', $wsOptions["downserver"], $url );
```

## 使用:

### 安装

目前还没有上架wordpress仓库，所以需要手动安装。

1. 下载插件

   你可以在[releases](/releases/latest)页面获取最新版本信息,然后下载zip压缩包。

2. 通过 WordPress 后台上传安装，或者直接将源码上传到 WordPress 插件目录`wp-content/plugins`，然后在后台启用。（路径{WP_DIR}/wp-content/plugins/define-server/define-server.php）

### 设置

插件默认激活后会设置服务器为`api.wordpress.org,downloads.wordpress.org`，也就是不会改变默认服务器。所以如果需要进一步使用插件的话，需要自行设置代理服务器。

1. 我们推荐使用Cloudflare的workers自行搭建（白 女票），详情可以参照此仓库 [Workers-Proxy](https://github.com/Siujoeng-Lau/Workers-Proxy/blob/master/README_zh.md)。如果你懒得折腾，也可以使用我的测试服务器（不保证稳定性）

   单纯worker反代

   downloads.wordpress.org=>downloads.wp.302.pub

   api.wordpress.org=>api.wp.302.pub

   worker反代+奇安信CDN（可能速度会有所改善）

   api.wordpress.org=>a.wp.302.pub

   downloads.wordpress.org=>d.wp.302.pub

2. 当然也可以通过nginx,Apache等组件进行反代服务，不过我觉得这样就违背了本插件出现的意义。（想反代就必须要有可以访问wp的服务器，而我们可以访问还要什么反代 (ノ=Д=)ノ┻━┻。）