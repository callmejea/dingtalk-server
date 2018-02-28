### 增加 类库到你的composer中, require写上项目地址, 检出
## API里不全的自己去找官方的文档, 写进去

```php
<?php
namespace YourNameSpace;

/**
 * User: jea 
 * Date: 2017/3/25
 * Time: 15:57
 */
include 'vendor/autoload.php';

use DingTalk\DingTalk;


$corpId     = 'your id';
$corpSecret = 'your corpSecret';

$c = new DingTalk($corpId, $corpSecret, '86509264');
// $c->addUser();
// $c->getUserContent('');
// $c->getDepartmentMemberById();
// $c->updateUser();
print_r($c);
```
