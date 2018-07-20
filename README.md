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

### 控制器相关
wrap_return_object参数默认为true。控制器返回数据统一用Msg对象包装起来。也可以控制器直接返回Msg对象。

### 数据库操作
DB 初始化

Query 组装,用户入口。把参数都塞进options 
Connection 封装对应driver调用
Builder 将options解析封装成driver query，交给connection执行。
query.get() -> connection.get() -> builder.select()
                                -> connection.query()

改造的话，主要是在builder里。connection会添加特定数据库的额外配置到options中。                                
##### Query Builder
db('tableName')返回query对象。

//where开头，get结尾确定查询
db('accounts')->where('username', 'edwardsbean')->get();
db('accounts')->where('votes', '>', 100)->get();
db('accounts')->whereBetween('votes', [1, 100])->get();
db('accounts')->where('username', 'edwardsbean')->getOne();
db('accounts')->where(['username'=>'edwardsbean'])->get();
db('accounts')->where('username', 'edwardsbean')->order('age', 'desc')->get(); //多字段排序？？？
db('accounts')->where('username', 'edwardsbean')->paginate(0, 20);

//update with id
db('accounts')->save(['id'=>1, 'username'=>'edwardsbean']);
//insert without id
db('accounts')->save(['username'=>'edwardsbean']);
//多条数据
db('accounts')->saveAll(['username'=>'edwardsbean']);

##### Raw Query
DB::insert/update/delete/statement('insert into users (id, name) values (?, ?)', [1, 'Dayle']);

##### Method Query
输入参数大于1个时，需使用array
查询关键字：
- findBy
- existsBy
- deleteBy
- removeBy
- countBy

逻辑关键字：
- Gte   大于等于
- Gt    大于
- Lt    小于
- Lte   小于等于
- In
- not
- Notint
- Between
- Or    
- And
- Like
- Notlike

db('accounts')->findByUsername('edwardsbean');
db('accounts')->findByUsernameAndAge(['edwardsbean', 18]);
db('accounts')->findByUsernameLike('%ed%');
db('accounts')->findByAgeGte(18);
db('accounts')->existsById('5asfdrqwerqw');
db('accounts')->count();
db('accounts')->countByAge(28);
db('accounts')->deleteByUsername("edwardsbean"); //return affect rows
db('accounts')->removeByUsername("edwardsbean"); //return list of object
db('accounts')->removeByUsernameOrAge(["ed", 18]);
db('accounts')->
db('accounts')->
db('accounts')->
db('accounts')->

##### Transaction 
DB::transaction(function () {
    db('users')->update(['votes' => 1]);

    db('posts')->delete();
}, 5);

##### Chunking Result(TODO)