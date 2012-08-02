# Drupal 7.14 for SAE

Drupal 7.14的SAE移植，基本功能可正常使用

## 特性

 - 支持简洁URL(clean url)
 - 支持文件上传
 - 支持数据库主从分离
 - 支持Memcache缓存
 - <del>支持邮件发送</del>

## 安装

 - 在SAE后台初始化Mysql服务
 - 在SAE后台Storage面板创建一个新域"drupal"
 - 上传安装包
 - 访问`http://youapp.sinaapp.com/install.php`进行安装(数据库不需要配置，可直接跳过)
 
### 启用Memcache

启用Memcache会让应用运行得更快，并且消耗更少的资源，启用方法：

 - 在SAE后台启用Memcache服务
 - 在Drupal后台开启Memcache模块
 
## 附带的语言包

 - 简体中文
 - 繁体中文

## 附带的模块

 - [Memcache](http://drupal.org/project/memcache/) (支持Memcache, 针对SAE有修改)
 - [SMTP](http://drupal.org/project/smtp/) (暂时无法使用)
 - [BUEditor](http://drupal.org/project/bueditor/) (简单的编辑器)
 - [Markdown](http://drupal.org/project/markdown/) (支持Markdown格式撰写内容)
 
## 附带的风格

 - [bluemasters](http://drupal.org/project/bluemasters/)
 - [business](http://drupal.org/project/business/)
 - [corporateclean](http://drupal.org/project/corporateclean/)
 - [danland](http://drupal.org/project/danland/)
 - [touch](http://drupal.org/project/touch/)
 - [simpleclean](http://drupal.org/project/simpleclean/)
 
## 缺陷

 - 由于SAE不能写本地IO，因此自动更新将会无效(该问题无法解决)
 - 默认的HTTP请求模块在SAE无法执行，已简单修改，但对特定情况不一定会成功
 - Email不支持普通的发送方式，导致发送无法成功
 
## TODO

 - 支持邮件发送(使用SaeMail)
 - 进一步优化存储的性能
 
## 链接

 - [项目主页](http://maxmars.net/p/sae-drupal)
 - [Github](https://github.com/ddliu/sae-drupal)
