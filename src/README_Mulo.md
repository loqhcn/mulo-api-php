# MuloModel 核心库

- 主要业务实现
- 工具类存放
- 基础模型

## MuloModel管理
模型转换为mysql表结构sql
- [DataTable](mulo\model\library\DataTable.php) 创建数据结构-工具类
- [MysqlStorageEngine](mulo\model\library\storage_engine\MysqlStorageEngine.php) mysql存储引擎



## 增删改查
- [CURD](library\curd\Curd.php)


## 认证

- [认证](library\auth\lib\JWT.php)
- [跨域中间件](middleware\Cors.php)

## 异常

- [基础异常](exception\MuloException.php)


