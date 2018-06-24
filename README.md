### composer自动加载注意
composer.json中加入需要autoload的目录或文件，修改后要重新composer install生成新的autoload.php

### 单元测试
```
./vendor/bin/phpunit --bootstrap ./vendor/autoload.php ./tests/ReflectionTest
```

### 测试工程添加framework依赖
测试工程添加：
```
    "repositories": [
        {
            "type": "path",
            "url": "F:/workspace/edphp",
            "options": {
                "symlink": true
            }
        }
    ],
```
然后添加framework依赖，以链接形式：
```
composer require "edphp/framework @dev"
```