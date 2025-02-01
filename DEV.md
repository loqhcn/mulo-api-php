# 开发时

## 开发时配置
再项目中配置
```json
// 项目的composer.json
 {
    "repositories": [
        {
            "type": "path",
            "url": "./model-api"
        }
    ],
 }
```

```bash
# 初次安装(在配置repositories后)
composer require mulo/model-api:dev-master
```

## 开发过程中

```bash

# 更新composer.json后
composer update mulo/model-api:dev-master 

# 刷新缓存
composer clear-cache
# 刷新缓存-autoload
composer dump-autoload -o

```

# 创建composer包的过程

3. 配置 composer.json 文件


4.进行版本控制


```bash
# 初始化 Git 仓库
git init

# 添加所有文件到暂存区
git add .

# 提交代码
git commit -m "Initial commit"

# 关联远程仓库
git remote add origin <your-repository-url>

# 推送代码到远程仓库
git push -u origin master
```

5. 打版本标签
为了让 Composer 能够识别包的版本，你需要在代码仓库中打版本标签。

```bash
# 打版本标签，例如 v1.0.0
git tag v1.0.0

# 推送标签到远程仓库
git push --tags
```

6. 发布到 Packagist https://packagist.org/

Packagist 是 Composer 默认的包仓库，你可以将自己的包发布到这里，让其他开发者可以方便地使用。
注册 Packagist 账号：访问 Packagist 官网，注册一个账号。
提交包：登录 Packagist 后，点击右上角的 “Submit” 按钮，输入你在代码托管平台上的仓库 URL，然后点击 “Check” 按钮。Packagist 会自动检测你的仓库，并显示包的信息。确认信息无误后，点击 “Submit” 完成提交。
自动更新配置（可选）：为了让 Packagist 能够在你更新代码仓库时自动更新包信息，你可以在代码托管平台上配置 Webhook。以 GitHub 为例，在仓库的 “Settings” -> “Webhooks” 中添加一个新的 Webhook，将 Payload URL 设置为 https://packagist.org/api/github?username=your-packagist-username，Content type 选择 application/json，触发事件选择 Just the push event，然后点击 “Add webhook”。