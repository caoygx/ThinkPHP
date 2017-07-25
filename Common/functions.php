<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

/**
 * Think 系统函数库
 */

/**
 * 获取和设置配置参数 支持批量定义
 * @param string|array $name 配置变量
 * @param mixed $value 配置值
 * @param mixed $default 默认值
 * @return mixed
 */
function C($name = null, $value = null, $default = null)
{
    static $_config = array();
    // 无参数时获取所有
    if (empty($name)) {
        return $_config;
    }
    // 优先执行设置获取或赋值
    if (is_string($name)) {
        if (!strpos($name, '.')) {
            $name = strtoupper($name);
            if (is_null($value)) {
                return isset($_config[$name]) ? $_config[$name] : $default;
            }

            $_config[$name] = $value;
            return null;
        }
        // 二维数组设置和获取支持
        $name    = explode('.', $name);
        $name[0] = strtoupper($name[0]);
        if (is_null($value)) {
            return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : $default;
        }

        $_config[$name[0]][$name[1]] = $value;
        return null;
    }
    // 批量设置
    if (is_array($name)) {
        $_config = array_merge($_config, array_change_key_case($name, CASE_UPPER));
        return null;
    }
    return null; // 避免非法参数
}

/**
 * 加载配置文件 支持格式转换 仅支持一级配置
 * @param string $file 配置文件名
 * @param string $parse 配置解析方法 有些格式需要用户自己解析
 * @return array
 */
function load_config($file, $parse = CONF_PARSE)
{
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    switch ($ext) {
        case 'php':
            return include $file;
        case 'ini':
            return parse_ini_file($file);
        case 'yaml':
            return yaml_parse_file($file);
        case 'xml':
            return (array) simplexml_load_file($file);
        case 'json':
            return json_decode(file_get_contents($file), true);
        default:
            if (function_exists($parse)) {
                return $parse($file);
            } else {
                E(L('_NOT_SUPPORT_') . ':' . $ext);
            }
    }
}

/**
 * 解析yaml文件返回一个数组
 * @param string $file 配置文件名
 * @return array
 */
if (!function_exists('yaml_parse_file')) {
    function yaml_parse_file($file)
    {
        vendor('spyc.Spyc');
        return Spyc::YAMLLoad($file);
    }
}

/**
 * 抛出异常处理
 * @param string $msg 异常消息
 * @param integer $code 异常代码 默认为0
 * @throws Think\Exception
 * @return void
 */
function E($msg, $code = 0)
{
    throw new Think\Exception($msg, $code);
}

/**
 * 记录和统计时间（微秒）和内存使用情况
 * 使用方法:
 * <code>
 * G('begin'); // 记录开始标记位
 * // ... 区间运行代码
 * G('end'); // 记录结束标签位
 * echo G('begin','end',6); // 统计区间运行时间 精确到小数后6位
 * echo G('begin','end','m'); // 统计区间内存使用情况
 * 如果end标记位没有定义，则会自动以当前作为标记位
 * 其中统计内存使用需要 MEMORY_LIMIT_ON 常量为true才有效
 * </code>
 * @param string $start 开始标签
 * @param string $end 结束标签
 * @param integer|string $dec 小数位或者m
 * @return mixed
 */
function G($start, $end = '', $dec = 4)
{
    static $_info = array();
    static $_mem  = array();
    if (is_float($end)) {
        // 记录时间
        $_info[$start] = $end;
    } elseif (!empty($end)) {
        // 统计时间和内存使用
        if (!isset($_info[$end])) {
            $_info[$end] = microtime(true);
        }

        if (MEMORY_LIMIT_ON && 'm' == $dec) {
            if (!isset($_mem[$end])) {
                $_mem[$end] = memory_get_usage();
            }

            return number_format(($_mem[$end] - $_mem[$start]) / 1024);
        } else {
            return number_format(($_info[$end] - $_info[$start]), $dec);
        }

    } else {
        // 记录时间和内存使用
        $_info[$start] = microtime(true);
        if (MEMORY_LIMIT_ON) {
            $_mem[$start] = memory_get_usage();
        }

    }
    return null;
}

/**
 * 获取和设置语言定义(不区分大小写)
 * @param string|array $name 语言变量
 * @param mixed $value 语言值或者变量
 * @return mixed
 */
function L($name = null, $value = null)
{
    static $_lang = array();
    // 空参数返回所有定义
    if (empty($name)) {
        return $_lang;
    }

    // 判断语言获取(或设置)
    // 若不存在,直接返回全大写$name
    if (is_string($name)) {
        $name = strtoupper($name);
        if (is_null($value)) {
            return isset($_lang[$name]) ? $_lang[$name] : $name;
        } elseif (is_array($value)) {
            // 支持变量
            $replace = array_keys($value);
            foreach ($replace as &$v) {
                $v = '{$' . $v . '}';
            }
            return str_replace($replace, $value, isset($_lang[$name]) ? $_lang[$name] : $name);
        }
        $_lang[$name] = $value; // 语言定义
        return null;
    }
    // 批量定义
    if (is_array($name)) {
        $_lang = array_merge($_lang, array_change_key_case($name, CASE_UPPER));
    }

    return null;
}

/**
 * 添加和获取页面Trace记录
 * @param string $value 变量
 * @param string $label 标签
 * @param string $level 日志级别
 * @param boolean $record 是否记录日志
 * @return void|array
 */
function trace($value = '[think]', $label = '', $level = 'DEBUG', $record = false)
{
    return Think\Think::trace($value, $label, $level, $record);
}

/**
 * 编译文件
 * @param string $filename 文件名
 * @return string
 */
function compile($filename)
{
    $content = php_strip_whitespace($filename);
    $content = trim(substr($content, 5));
    // 替换预编译指令
    $content = preg_replace('/\/\/\[RUNTIME\](.*?)\/\/\[\/RUNTIME\]/s', '', $content);
    if (0 === strpos($content, 'namespace')) {
        $content = preg_replace('/namespace\s(.*?);/', 'namespace \\1{', $content, 1);
    } else {
        $content = 'namespace {' . $content;
    }
    if ('?>' == substr($content, -2)) {
        $content = substr($content, 0, -2);
    }

    return $content . '}';
}

/**
 * 获取模版文件 格式 资源://模块@主题/控制器/操作
 * @param string $template 模版资源地址
 * @param string $layer 视图层（目录）名称
 * @return string
 */
function T($template = '', $layer = '')
{

    // 解析模版资源地址
    if (false === strpos($template, '://')) {
        $template = 'http://' . str_replace(':', '/', $template);
    }
    $info   = parse_url($template);
    $file   = $info['host'] . (isset($info['path']) ? $info['path'] : '');
    $module = isset($info['user']) ? $info['user'] . '/' : MODULE_NAME . '/';
    $extend = $info['scheme'];
    $layer  = $layer ? $layer : C('DEFAULT_V_LAYER');

    // 获取当前主题的模版路径
    $auto = C('AUTOLOAD_NAMESPACE');
    if ($auto && isset($auto[$extend])) {
        // 扩展资源
        $baseUrl = $auto[$extend] . $module . $layer . '/';
    } elseif (C('VIEW_PATH')) {
        // 改变模块视图目录
        $baseUrl = C('VIEW_PATH');
    } elseif (defined('TMPL_PATH')) {
        // 指定全局视图目录
        $baseUrl = TMPL_PATH . $module;
    } else {
        $baseUrl = APP_PATH . $module . $layer . '/';
    }

    // 获取主题
    $theme = substr_count($file, '/') < 2 ? C('DEFAULT_THEME') : '';

    // 分析模板文件规则
    $depr = C('TMPL_FILE_DEPR');
    if ('' == $file) {
        // 如果模板文件名为空 按照默认规则定位
        $file = CONTROLLER_NAME . $depr . ACTION_NAME;
    } elseif (false === strpos($file, '/')) {
        $file = CONTROLLER_NAME . $depr . $file;
    } elseif ('/' != $depr) {
        $file = substr_count($file, '/') > 1 ? substr_replace($file, $depr, strrpos($file, '/'), 1) : str_replace('/', $depr, $file);
    }
    return $baseUrl . ($theme ? $theme . '/' : '') . $file . C('TMPL_TEMPLATE_SUFFIX');
}

//非空参数获取
function I2($name, $typeVerify=false){

    if($typeVerify){ //类型验证
        if (strpos($name, '/')) {
            // 指定修饰符
            list($name, $type) = explode('/', $name, 2);
        } elseif (C('VAR_AUTO_STRING')) {
            // 默认强制转换为字符串
            $type = 's';
        }

        if (!empty($type)) {
            $value = $_REQUEST[$name];
            switch (strtolower($type)) {
                case 'a':    // 数组
                    !is_array($value) && E($name.'类型必须是 array');
                    break;
                case 'd':    // 数字
                    !is_numeric($value) && E($name.'类型必须是 int');
                    break;
//                case 'f':    // 浮点
//                    !is_float($value) && E($name.'类型必须是 float');
//                    break;
                case 'b':    // 布尔
                    $value = strtolower($value);
                    if($value != 'true' && $value != 'false'){
                        E($name.'类型必须是 Bool');
                    }
                    break;
                case 's':// 字符串
                default:
                    !is_string($value) && E($name.'类型必须是 string');
            }
        }
    }

    $value = I($name);
    if(empty($value)){
        list($name, $type) = explode('/', $name, 2);
        E($name.'不能为空');
    }

    return $value;
}
/**
 * 获取输入参数 支持过滤和默认值
 * 使用方法:
 * <code>
 * I('id',0); 获取id参数 自动判断get或者post
 * I('post.name','','htmlspecialchars'); 获取$_POST['name']
 * I('get.'); 获取$_GET
 * </code>
 * @param string $name 变量的名称 支持指定类型
 * @param mixed $default 不存在的时候默认值
 * @param mixed $filter 参数过滤方法
 * @param mixed $datas 要获取的额外数据源
 * @return mixed
 */
function I($name, $default = '', $filter = null, $datas = null)
{
    static $_PUT = null;
    if (strpos($name, '/')) {
        // 指定修饰符
        list($name, $type) = explode('/', $name, 2);
    } elseif (C('VAR_AUTO_STRING')) {
        // 默认强制转换为字符串
        $type = 's';
    }
    if (strpos($name, '.')) {
        // 指定参数来源
        list($method, $name) = explode('.', $name, 2);
    } else {
        // 默认为自动判断
        $method = 'param';
    }
    switch (strtolower($method)) {
        case 'get':
            $input = &$_GET;
            break;
        case 'post':
            $input = &$_POST;
            break;
        case 'put':
            if (is_null($_PUT)) {
                parse_str(file_get_contents('php://input'), $_PUT);
            }
            $input = $_PUT;
            break;
        case 'param':
            switch ($_SERVER['REQUEST_METHOD']) {
            case 'POST':
                    $input = $_POST;
                    break;
            case 'PUT':
                    if (is_null($_PUT)) {
                        parse_str(file_get_contents('php://input'), $_PUT);
                    }
                    $input = $_PUT;
                    break;
            default:
                    $input = $_GET;
            }
            break;
        case 'path':
            $input = array();
            if (!empty($_SERVER['PATH_INFO'])) {
                $depr  = C('URL_PATHINFO_DEPR');
                $input = explode($depr, trim($_SERVER['PATH_INFO'], $depr));
            }
            break;
        case 'request':
            $input = &$_REQUEST;
            break;
        case 'session':
            $input = &$_SESSION;
            break;
        case 'cookie':
            $input = &$_COOKIE;
            break;
        case 'server':
            $input = &$_SERVER;
            break;
        case 'globals':
            $input = &$GLOBALS;
            break;
        case 'data':
            $input = &$datas;
            break;
        default:
            return null;
    }
    if ('' == $name) {
        // 获取全部变量
        $data    = $input;
        $filters = isset($filter) ? $filter : C('DEFAULT_FILTER');
        if ($filters) {
            if (is_string($filters)) {
                $filters = explode(',', $filters);
            }
            foreach ($filters as $filter) {
                $data = array_map_recursive($filter, $data); // 参数过滤
            }
        }
    } elseif (isset($input[$name])) {
        // 取值操作
        $data    = $input[$name];
        $filters = isset($filter) ? $filter : C('DEFAULT_FILTER');
        if ($filters) {
            if (is_string($filters)) {
                if (0 === strpos($filters, '/')) {
                    if (1 !== preg_match($filters, (string) $data)) {
                        // 支持正则验证
                        return isset($default) ? $default : null;
                    }
                } else {
                    $filters = explode(',', $filters);
                }
            } elseif (is_int($filters)) {
                $filters = array($filters);
            }

            if (is_array($filters)) {
                foreach ($filters as $filter) {
                    $filter = trim($filter);
                    if (function_exists($filter)) {
                        $data = is_array($data) ? array_map_recursive($filter, $data) : $filter($data); // 参数过滤
                    } else {
                        $data = filter_var($data, is_int($filter) ? $filter : filter_id($filter));
                        if (false === $data) {
                            return isset($default) ? $default : null;
                        }
                    }
                }
            }
        }
        if (!empty($type)) {
            switch (strtolower($type)) {
                case 'a':    // 数组
                    $data = (array) $data;
                    break;
                case 'd':    // 数字
                    $data = (int) $data;
                    break;
                case 'f':    // 浮点
                    $data = (float) $data;
                    break;
                case 'b':    // 布尔
                    $data = (boolean) $data;
                    break;
                case 's':// 字符串
                default:
                    $data = (string) $data;
            }
        }
    } else {
        // 变量默认值
        $data = isset($default) ? $default : null;
    }
    is_array($data) && array_walk_recursive($data, 'think_filter');
    return $data;
}

function array_map_recursive($filter, $data)
{
    $result = array();
    foreach ($data as $key => $val) {
        $result[$key] = is_array($val)
        ? array_map_recursive($filter, $val)
        : call_user_func($filter, $val);
    }
    return $result;
}

/**
 * 设置和获取统计数据
 * 使用方法:
 * <code>
 * N('db',1); // 记录数据库操作次数
 * N('read',1); // 记录读取次数
 * echo N('db'); // 获取当前页面数据库的所有操作次数
 * echo N('read'); // 获取当前页面读取次数
 * </code>
 * @param string $key 标识位置
 * @param integer $step 步进值
 * @param boolean $save 是否保存结果
 * @return mixed
 */
function N($key, $step = 0, $save = false)
{
    static $_num = array();
    if (!isset($_num[$key])) {
        $_num[$key] = (false !== $save) ? S('N_' . $key) : 0;
    }
    if (empty($step)) {
        return $_num[$key];
    } else {
        $_num[$key] = $_num[$key] + (int) $step;
    }
    if (false !== $save) {
        // 保存结果
        S('N_' . $key, $_num[$key], $save);
    }
    return null;
}

/**
 * 字符串命名风格转换
 * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
 * @param string $name 字符串
 * @param integer $type 转换类型
 * @return string
 */
function parse_name($name, $type = 0)
{
    if ($type) {
        return ucfirst(preg_replace_callback('/_([a-zA-Z])/', function ($match) {return strtoupper($match[1]);}, $name));
    } else {
        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }
}

/**
 * 优化的require_once
 * @param string $filename 文件地址
 * @return boolean
 */
function require_cache($filename)
{
    static $_importFiles = array();
    if (!isset($_importFiles[$filename])) {
        if (file_exists_case($filename)) {
            require $filename;
            $_importFiles[$filename] = true;
        } else {
            $_importFiles[$filename] = false;
        }
    }
    return $_importFiles[$filename];
}

/**
 * 区分大小写的文件存在判断
 * @param string $filename 文件地址
 * @return boolean
 */
function file_exists_case($filename)
{
    if (is_file($filename)) {
        if (IS_WIN && APP_DEBUG) {
            if (basename(realpath($filename)) != basename($filename)) {
                return false;
            }

        }
        return true;
    }
    return false;
}

/**
 * 导入所需的类库 同java的Import 本函数有缓存功能
 * @param string $class 类库命名空间字符串
 * @param string $baseUrl 起始路径
 * @param string $ext 导入的文件扩展名
 * @return boolean
 */
function import($class, $baseUrl = '', $ext = EXT)
{
    static $_file = array();
    $class        = str_replace(array('.', '#'), array('/', '.'), $class);
    if (isset($_file[$class . $baseUrl])) {
        return true;
    } else {
        $_file[$class . $baseUrl] = true;
    }

    $class_strut = explode('/', $class);
    if (empty($baseUrl)) {
        if ('@' == $class_strut[0] || MODULE_NAME == $class_strut[0]) {
            //加载当前模块的类库
            $baseUrl = MODULE_PATH;
            $class   = substr_replace($class, '', 0, strlen($class_strut[0]) + 1);
        } elseif ('Common' == $class_strut[0]) {
            //加载公共模块的类库
            $baseUrl = COMMON_PATH;
            $class   = substr($class, 7);
        } elseif (in_array($class_strut[0], array('Think', 'Org', 'Behavior', 'Com', 'Vendor')) || is_dir(LIB_PATH . $class_strut[0])) {
            // 系统类库包和第三方类库包
            $baseUrl = LIB_PATH;
        } else {
            // 加载其他模块的类库
            $baseUrl = APP_PATH;
        }
    }
    if (substr($baseUrl, -1) != '/') {
        $baseUrl .= '/';
    }

    $classfile = $baseUrl . $class . $ext;
    if (!class_exists(basename($class), false)) {
        // 如果类不存在 则导入类库文件
        return require_cache($classfile);
    }
    return null;
}

/**
 * 基于命名空间方式导入函数库
 * load('@.Util.Array')
 * @param string $name 函数库命名空间字符串
 * @param string $baseUrl 起始路径
 * @param string $ext 导入的文件扩展名
 * @return void
 */
function load($name, $baseUrl = '', $ext = '.php')
{
    $name = str_replace(array('.', '#'), array('/', '.'), $name);
    if (empty($baseUrl)) {
        if (0 === strpos($name, '@/')) {
            //加载当前模块函数库
            $baseUrl = MODULE_PATH . 'Common/';
            $name    = substr($name, 2);
        } else {
            //加载其他模块函数库
            $array   = explode('/', $name);
            $baseUrl = APP_PATH . array_shift($array) . '/Common/';
            $name    = implode('/', $array);
        }
    }
    if (substr($baseUrl, -1) != '/') {
        $baseUrl .= '/';
    }

    require_cache($baseUrl . $name . $ext);
}

/**
 * 快速导入第三方框架类库 所有第三方框架的类库文件统一放到 系统的Vendor目录下面
 * @param string $class 类库
 * @param string $baseUrl 基础目录
 * @param string $ext 类库后缀
 * @return boolean
 */
function vendor($class, $baseUrl = '', $ext = '.php')
{
    if (empty($baseUrl)) {
        $baseUrl = VENDOR_PATH;
    }

    return import($class, $baseUrl, $ext);
}

/**
 * 实例化模型类 格式 [资源://][模块/]模型
 * @param string $name 资源地址
 * @param string $layer 模型层名称
 * @return Think\Model
 */
function D($name = '', $layer = '')
{
    if (empty($name)) {
        return new Think\Model;
    }

    static $_model = array();
    $layer         = $layer ?: C('DEFAULT_M_LAYER');
    if (isset($_model[$name . $layer])) {
        return $_model[$name . $layer];
    }

    $class = parse_res_name($name, $layer);
    if (class_exists($class)) {
        $model = new $class(basename($name));
    } elseif (false === strpos($name, '/')) {
        // 自动加载公共模块下面的模型
        if (!C('APP_USE_NAMESPACE')) {
            import('Common/' . $layer . '/' . $class);
        } else {
            $class = '\\Common\\' . $layer . '\\' . $name . $layer;
        }
        $model = class_exists($class) ? new $class($name) : new Think\Model($name);
    } else {
        Think\Log::record('D方法实例化没找到模型类' . $class, Think\Log::NOTICE);
        $model = new Think\Model(basename($name));
    }
    $_model[$name . $layer] = $model;
    return $model;
}

/**
 * 实例化一个没有模型文件的Model
 * @param string $name Model名称 支持指定基础模型 例如 MongoModel:User
 * @param string $tablePrefix 表前缀
 * @param mixed $connection 数据库连接信息
 * @return Think\Model
 */
function M($name = '', $tablePrefix = '', $connection = '')
{
    static $_model = array();
    if (strpos($name, ':')) {
        list($class, $name) = explode(':', $name);
    } else {
        $class = 'Think\\Model';
    }
    $guid = (is_array($connection) ? implode('', $connection) : $connection) . $tablePrefix . $name . '_' . $class;
    if (!isset($_model[$guid])) {
        $_model[$guid] = new $class($name, $tablePrefix, $connection);
    }

    return $_model[$guid];
}

/**
 * 解析资源地址并导入类库文件
 * 例如 module/controller addon://module/behavior
 * @param string $name 资源地址 格式：[扩展://][模块/]资源名
 * @param string $layer 分层名称
 * @param integer $level 控制器层次
 * @return string
 */
function parse_res_name($name, $layer, $level = 1)
{
    if (strpos($name, '://')) {
        // 指定扩展资源
        list($extend, $name) = explode('://', $name);
    } else {
        $extend = '';
    }
    if (strpos($name, '/') && substr_count($name, '/') >= $level) {
        // 指定模块
        list($module, $name) = explode('/', $name, 2);
    } else {
        $module = defined('MODULE_NAME') ? MODULE_NAME : '';
    }
    $array = explode('/', $name);
    if (!C('APP_USE_NAMESPACE')) {
        $class = parse_name($name, 1);
        import($module . '/' . $layer . '/' . $class . $layer);
    } else {
        $class = $module . '\\' . $layer;
        foreach ($array as $name) {
            $class .= '\\' . parse_name($name, 1);
        }
        // 导入资源类库
        if ($extend) {
            // 扩展资源
            $class = $extend . '\\' . $class;
        }
    }
    return $class . $layer;
}

/**
 * 用于实例化访问控制器
 * @param string $name 控制器名
 * @param string $path 控制器命名空间（路径）
 * @return Think\Controller|false
 */
function controller($name, $path = '')
{
    $layer = C('DEFAULT_C_LAYER');
    if (!C('APP_USE_NAMESPACE')) {
        $class = parse_name($name, 1) . $layer;
        import(MODULE_NAME . '/' . $layer . '/' . $class);
    } else {
        $class = ($path ? basename(ADDON_PATH) . '\\' . $path : MODULE_NAME) . '\\' . $layer;
        $array = explode('/', $name);
        foreach ($array as $name) {
            $class .= '\\' . parse_name($name, 1);
        }
        $class .= $layer;
    }
    if (class_exists($class)) {
        return new $class();
    } else {
        return false;
    }
}

/**
 * 实例化多层控制器 格式：[资源://][模块/]控制器
 * @param string $name 资源地址
 * @param string $layer 控制层名称
 * @param integer $level 控制器层次
 * @return Think\Controller|false
 */
function A($name, $layer = '', $level = 0)
{
    static $_action = array();
    $layer          = $layer ?: C('DEFAULT_C_LAYER');
    $level          = $level ?: (C('DEFAULT_C_LAYER') == $layer ? C('CONTROLLER_LEVEL') : 1);
    if (isset($_action[$name . $layer])) {
        return $_action[$name . $layer];
    }

    $class = parse_res_name($name, $layer, $level);
    if (class_exists($class)) {
        $action                  = new $class();
        $_action[$name . $layer] = $action;
        return $action;
    } else {
        return false;
    }
}

/**
 * 远程调用控制器的操作方法 URL 参数格式 [资源://][模块/]控制器/操作
 * @param string $url 调用地址
 * @param string|array $vars 调用参数 支持字符串和数组
 * @param string $layer 要调用的控制层名称
 * @return mixed
 */
function R($url, $vars = array(), $layer = '')
{
    $info   = pathinfo($url);
    $action = $info['basename'];
    $module = $info['dirname'];
    $class  = A($module, $layer);
    if ($class) {
        if (is_string($vars)) {
            parse_str($vars, $vars);
        }
        return call_user_func_array(array(&$class, $action . C('ACTION_SUFFIX')), $vars);
    } else {
        return false;
    }
}

/**
 * 处理标签扩展
 * @param string $tag 标签名称
 * @param mixed $params 传入参数
 * @return void
 */
function tag($tag, &$params = null)
{
    \Think\Hook::listen($tag, $params);
}

/**
 * 执行某个行为
 * @param string $name 行为名称
 * @param string $tag 标签名称（行为类无需传入）
 * @param Mixed $params 传入的参数
 * @return void
 */
function B($name, $tag = '', &$params = null)
{
    if ('' == $tag) {
        $name .= 'Behavior';
    }
    return \Think\Hook::exec($name, $tag, $params);
}

/**
 * 去除代码中的空白和注释
 * @param string $content 代码内容
 * @return string
 */
function strip_whitespace($content)
{
    $stripStr = '';
    //分析php源码
    $tokens     = token_get_all($content);
    $last_space = false;
    for ($i = 0, $j = count($tokens); $i < $j; $i++) {
        if (is_string($tokens[$i])) {
            $last_space = false;
            $stripStr .= $tokens[$i];
        } else {
            switch ($tokens[$i][0]) {
                //过滤各种PHP注释
                case T_COMMENT:
                case T_DOC_COMMENT:
                    break;
                //过滤空格
                case T_WHITESPACE:
                    if (!$last_space) {
                        $stripStr .= ' ';
                        $last_space = true;
                    }
                    break;
                case T_START_HEREDOC:
                    $stripStr .= "<<<THINK\n";
                    break;
                case T_END_HEREDOC:
                    $stripStr .= "THINK;\n";
                    for ($k = $i + 1; $k < $j; $k++) {
                        if (is_string($tokens[$k]) && ';' == $tokens[$k]) {
                            $i = $k;
                            break;
                        } else if (T_CLOSE_TAG == $tokens[$k][0]) {
                            break;
                        }
                    }
                    break;
                default:
                    $last_space = false;
                    $stripStr .= $tokens[$i][1];
            }
        }
    }
    return $stripStr;
}

/**
 * 自定义异常处理
 * @param string $msg 异常消息
 * @param string $type 异常类型 默认为Think\Exception
 * @param integer $code 异常代码 默认为0
 * @return void
 */
function throw_exception($msg, $type = 'Think\\Exception', $code = 0)
{
    Think\Log::record('建议使用E方法替代throw_exception', Think\Log::NOTICE);
    if (class_exists($type, false)) {
        throw new $type($msg, $code);
    } else {
        Think\Think::halt($msg);
    }
    // 异常类型不存在则输出错误信息字串
}

/**
 * 浏览器友好的变量输出
 * @param mixed $var 变量
 * @param boolean $echo 是否输出 默认为True 如果为false 则返回输出字符串
 * @param string $label 标签 默认为空
 * @param boolean $strict 是否严谨 默认为true
 * @return void|string
 */
function dump($var, $echo = true, $label = null, $strict = true)
{
    $label = (null === $label) ? '' : rtrim($label) . ' ';
    if (!$strict) {
        if (ini_get('html_errors')) {
            $output = print_r($var, true);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        } else {
            $output = $label . print_r($var, true);
        }
    } else {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
            $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
        }
    }
    if ($echo) {
        echo ($output);
        return null;
    } else {
        return $output;
    }

}

/**
 * 设置当前页面的布局
 * @param string|false $layout 布局名称 为false的时候表示关闭布局
 * @return void
 */
function layout($layout)
{
    if (false !== $layout) {
        // 开启布局
        C('LAYOUT_ON', true);
        if (is_string($layout)) {
            // 设置新的布局模板
            C('LAYOUT_NAME', $layout);
        }
    } else {
// 临时关闭布局
        C('LAYOUT_ON', false);
    }
}

/**
 * URL组装 支持不同URL模式
 * @param string $url URL表达式，格式：'[模块/控制器/操作#锚点@域名]?参数1=值1&参数2=值2...'
 * @param string|array $vars 传入的参数，支持数组和字符串
 * @param string|boolean $suffix 伪静态后缀，默认为true表示获取配置值
 * @param boolean $domain 是否显示域名
 * @return string
 */
function U($url = '', $vars = '', $suffix = true, $domain = false)
{
    // 解析URL
    $info = parse_url($url);
    $url  = !empty($info['path']) ? $info['path'] : ACTION_NAME;
    if (isset($info['fragment'])) {
        // 解析锚点
        $anchor = $info['fragment'];
        if (false !== strpos($anchor, '?')) {
            // 解析参数
            list($anchor, $info['query']) = explode('?', $anchor, 2);
        }
        if (false !== strpos($anchor, '@')) {
            // 解析域名
            list($anchor, $host) = explode('@', $anchor, 2);
        }
    } elseif (false !== strpos($url, '@')) {
        // 解析域名
        list($url, $host) = explode('@', $info['path'], 2);
    }
    // 解析子域名
    if (isset($host)) {
        $domain = $host . (strpos($host, '.') ? '' : strstr($_SERVER['HTTP_HOST'], '.'));
    } elseif (true === $domain) {
        $domain = $_SERVER['HTTP_HOST'];
        if (C('APP_SUB_DOMAIN_DEPLOY')) {
            // 开启子域名部署
            $domain = 'localhost' == $domain ? 'localhost' : 'www' . strstr($_SERVER['HTTP_HOST'], '.');
            // '子域名'=>array('模块[/控制器]');
            foreach (C('APP_SUB_DOMAIN_RULES') as $key => $rule) {
                $rule = is_array($rule) ? $rule[0] : $rule;
                if (false === strpos($key, '*') && 0 === strpos($url, $rule)) {
                    $domain = $key . strstr($domain, '.'); // 生成对应子域名
                    $url    = substr_replace($url, '', 0, strlen($rule));
                    break;
                }
            }
        }
    }

    // 解析参数
    if (is_string($vars)) {
        // aaa=1&bbb=2 转换成数组
        parse_str($vars, $vars);
    } elseif (!is_array($vars)) {
        $vars = array();
    }
    if (isset($info['query'])) {
        // 解析地址里面参数 合并到vars
        parse_str($info['query'], $params);
        $vars = array_merge($params, $vars);
    }

    // URL组装
    $depr    = C('URL_PATHINFO_DEPR');
    $urlCase = C('URL_CASE_INSENSITIVE');
    if ($url) {
        if (0 === strpos($url, '/')) {
            // 定义路由
            $route = true;
            $url   = substr($url, 1);
            if ('/' != $depr) {
                $url = str_replace('/', $depr, $url);
            }
        } else {
            if ('/' != $depr) {
                // 安全替换
                $url = str_replace('/', $depr, $url);
            }
            // 解析模块、控制器和操作
            $url                 = trim($url, $depr);
            $path                = explode($depr, $url);
            $var                 = array();
            $varModule           = C('VAR_MODULE');
            $varController       = C('VAR_CONTROLLER');
            $varAction           = C('VAR_ACTION');
            $var[$varAction]     = !empty($path) ? array_pop($path) : ACTION_NAME;
            $var[$varController] = !empty($path) ? array_pop($path) : CONTROLLER_NAME;
            if ($maps = C('URL_ACTION_MAP')) {
                if (isset($maps[strtolower($var[$varController])])) {
                    $maps = $maps[strtolower($var[$varController])];
                    if ($action = array_search(strtolower($var[$varAction]), $maps)) {
                        $var[$varAction] = $action;
                    }
                }
            }
            if ($maps = C('URL_CONTROLLER_MAP')) {
                if ($controller = array_search(strtolower($var[$varController]), $maps)) {
                    $var[$varController] = $controller;
                }
            }
            if ($urlCase) {
                $var[$varController] = parse_name($var[$varController]);
            }
            $module = '';

            if (!empty($path)) {
                $var[$varModule] = implode($depr, $path);
            } else {
                // 如果为插件，自动转换路径
                if (CONTROLLER_PATH) {
                    $var[$varModule] = MODULE_NAME;
                    $varAddon        = C('VAR_ADDON');
                    if (MODULE_NAME != C('DEFAULT_MODULE')) {
                        $var[$varController] = MODULE_NAME;
                    }

                    $vars = array_merge(array($varAddon => CONTROLLER_PATH), $vars);

                } elseif (C('MULTI_MODULE')) {
                    if (MODULE_NAME != C('DEFAULT_MODULE') || !C('MODULE_ALLOW_LIST')) {
                        $var[$varModule] = MODULE_NAME;
                    }
                }
            }
            if ($maps = C('URL_MODULE_MAP')) {
                if ($_module = array_search(strtolower($var[$varModule]), $maps)) {
                    $var[$varModule] = $_module;
                }
            }
            if (isset($var[$varModule])) {
                $module = defined('BIND_MODULE') && BIND_MODULE == $var[$varModule] ? '' : $var[$varModule];
                unset($var[$varModule]);
            }

        }
    }

    if (0 == C('URL_MODEL')) {
        // 普通模式URL转换
        $url = __APP__ . '?' . C('VAR_MODULE') . "={$module}&" . http_build_query(array_reverse($var));
        if ($urlCase) {
            $url = strtolower($url);
        }
        if (!empty($vars)) {
            $vars = http_build_query($vars);
            $url .= '&' . $vars;
        }
    } else {
        // PATHINFO模式或者兼容URL模式
        if (isset($route)) {
            $url = __APP__ . '/' . rtrim($url, $depr);
        } else {
            $path = implode($depr, array_reverse($var));
            if (C('URL_ROUTER_ON')) {
                $url = Think\Route::reverse($path, $vars, $depr, $suffix);
                if (!$url) {
                    $url = $path;
                }
            } else {
                $url = $path;
            }
            $url = __APP__ . '/' . ($module ? $module . MODULE_PATHINFO_DEPR : '') . $url;
        }
        if ($urlCase) {
            $url = strtolower($url);
        }
        if (!empty($vars)) {
            // 添加参数
            foreach ($vars as $var => $val) {
                if ('' !== trim($val)) {
                    $url .= $depr . $var . $depr . urlencode($val);
                }
            }
        }
        if ($suffix) {
            $suffix = true === $suffix ? C('URL_HTML_SUFFIX') : $suffix;
            if ($pos = strpos($suffix, '|')) {
                $suffix = substr($suffix, 0, $pos);
            }
            if ($suffix && '/' != substr($url, -1)) {
                $url .= '.' . ltrim($suffix, '.');
            }
        }
    }
    if (!empty($anchor)) {
        $url .= '#' . $anchor;
    }
    if ($domain) {
        $url = (is_ssl() ? 'https://' : 'http://') . $domain . $url;
    }
    return $url;
}

/**
 * 渲染输出Widget
 * @param string $name Widget名称
 * @param array $data 传入的参数
 * @return void
 */
function W($name, $data = array())
{
    return R($name, $data, 'Widget');
}

/**
 * 判断是否SSL协议
 * @return boolean
 */
function is_ssl()
{
    if (isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))) {
        return true;
    } elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
        return true;
    }
    return false;
}

/**
 * URL重定向
 * @param string $url 重定向的URL地址
 * @param integer $time 重定向的等待时间（秒）
 * @param string $msg 重定向前的提示信息
 * @return void
 */
function redirect($url, $time = 0, $msg = '')
{
    //多行URL地址支持
    $url = str_replace(array("\n", "\r"), '', $url);
    if (empty($msg)) {
        $msg = "系统将在{$time}秒之后自动跳转到{$url}！";
    }

    if (!headers_sent()) {
        // redirect
        if (0 === $time) {
            header('Location: ' . $url);
        } else {
            header("refresh:{$time};url={$url}");
            echo ($msg);
        }
        exit();
    } else {
        $str = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
        if (0 != $time) {
            $str .= $msg;
        }

        exit($str);
    }
}

/**
 * 缓存管理
 * @param mixed $name 缓存名称，如果为数组表示进行缓存设置
 * @param mixed $value 缓存值
 * @param mixed $options 缓存参数
 * @return mixed
 */
function S($name, $value = '', $options = null)
{
    static $cache = '';
    if (is_array($options)) {
        // 缓存操作的同时初始化
        $type  = isset($options['type']) ? $options['type'] : '';
        $cache = Think\Cache::getInstance($type, $options);
    } elseif (is_array($name)) {
        // 缓存初始化
        $type  = isset($name['type']) ? $name['type'] : '';
        $cache = Think\Cache::getInstance($type, $name);
        return $cache;
    } elseif (empty($cache)) {
        // 自动初始化
        $cache = Think\Cache::getInstance();
    }
    if ('' === $value) {
        // 获取缓存
        return $cache->get($name);
    } elseif (is_null($value)) {
        // 删除缓存
        return $cache->rm($name);
    } else {
        // 缓存数据
        if (is_array($options)) {
            $expire = isset($options['expire']) ? $options['expire'] : null;
        } else {
            $expire = is_numeric($options) ? $options : null;
        }
        return $cache->set($name, $value, $expire);
    }
}

/**
 * 快速文件数据读取和保存 针对简单类型数据 字符串、数组
 * @param string $name 缓存名称
 * @param mixed $value 缓存值
 * @param string $path 缓存路径
 * @return mixed
 */
function F($name, $value = '', $path = DATA_PATH)
{
    static $_cache = array();
    $filename      = $path . $name . '.php';
    if ('' !== $value) {
        if (is_null($value)) {
            // 删除缓存
            if (false !== strpos($name, '*')) {
                return false; // TODO
            } else {
                unset($_cache[$name]);
                return Think\Storage::unlink($filename, 'F');
            }
        } else {
            Think\Storage::put($filename, serialize($value), 'F');
            // 缓存数据
            $_cache[$name] = $value;
            return null;
        }
    }
    // 获取缓存数据
    if (isset($_cache[$name])) {
        return $_cache[$name];
    }

    if (Think\Storage::has($filename, 'F')) {
        $value         = unserialize(Think\Storage::read($filename, 'F'));
        $_cache[$name] = $value;
    } else {
        $value = false;
    }
    return $value;
}

/**
 * 根据PHP各种类型变量生成唯一标识号
 * @param mixed $mix 变量
 * @return string
 */
function to_guid_string($mix)
{
    if (is_object($mix)) {
        return spl_object_hash($mix);
    } elseif (is_resource($mix)) {
        $mix = get_resource_type($mix) . strval($mix);
    } else {
        $mix = serialize($mix);
    }
    return md5($mix);
}

/**
 * XML编码
 * @param mixed $data 数据
 * @param string $root 根节点名
 * @param string $item 数字索引的子节点名
 * @param string $attr 根节点属性
 * @param string $id   数字索引子节点key转换的属性名
 * @param string $encoding 数据编码
 * @return string
 */
function xml_encode($data, $root = 'think', $item = 'item', $attr = '', $id = 'id', $encoding = 'utf-8')
{
    if (is_array($attr)) {
        $_attr = array();
        foreach ($attr as $key => $value) {
            $_attr[] = "{$key}=\"{$value}\"";
        }
        $attr = implode(' ', $_attr);
    }
    $attr = trim($attr);
    $attr = empty($attr) ? '' : " {$attr}";
    $xml  = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
    $xml .= "<{$root}{$attr}>";
    $xml .= data_to_xml($data, $item, $id);
    $xml .= "</{$root}>";
    return $xml;
}

/**
 * 数据XML编码
 * @param mixed  $data 数据
 * @param string $item 数字索引时的节点名称
 * @param string $id   数字索引key转换为的属性名
 * @return string
 */
function data_to_xml($data, $item = 'item', $id = 'id')
{
    $xml = $attr = '';
    foreach ($data as $key => $val) {
        if (is_numeric($key)) {
            $id && $attr = " {$id}=\"{$key}\"";
            $key         = $item;
        }
        $xml .= "<{$key}{$attr}>";
        $xml .= (is_array($val) || is_object($val)) ? data_to_xml($val, $item, $id) : $val;
        $xml .= "</{$key}>";
    }
    return $xml;
}

/**
 * session管理函数
 * @param string|array $name session名称 如果为数组则表示进行session设置
 * @param mixed $value session值
 * @return mixed
 */
function session($name = '', $value = '')
{
    $prefix = C('SESSION_PREFIX');
    if (is_array($name)) {
        // session初始化 在session_start 之前调用
        if (isset($name['prefix'])) {
            C('SESSION_PREFIX', $name['prefix']);
        }

        if (C('VAR_SESSION_ID') && isset($_REQUEST[C('VAR_SESSION_ID')])) {
            session_id($_REQUEST[C('VAR_SESSION_ID')]);
        } elseif (isset($name['id'])) {
            session_id($name['id']);
        }
        if ('common' == APP_MODE) {
            // 其它模式可能不支持
            ini_set('session.auto_start', 0);
        }
        if (isset($name['name'])) {
            session_name($name['name']);
        }

        if (isset($name['path'])) {
            session_save_path($name['path']);
        }

        if (isset($name['domain'])) {
            ini_set('session.cookie_domain', $name['domain']);
        }

        if (isset($name['expire'])) {
            ini_set('session.gc_maxlifetime', $name['expire']);
            ini_set('session.cookie_lifetime', $name['expire']);
        }
        if (isset($name['use_trans_sid'])) {
            ini_set('session.use_trans_sid', $name['use_trans_sid'] ? 1 : 0);
        }

        if (isset($name['use_cookies'])) {
            ini_set('session.use_cookies', $name['use_cookies'] ? 1 : 0);
        }

        if (isset($name['cache_limiter'])) {
            session_cache_limiter($name['cache_limiter']);
        }

        if (isset($name['cache_expire'])) {
            session_cache_expire($name['cache_expire']);
        }

        if (isset($name['type'])) {
            C('SESSION_TYPE', $name['type']);
        }

        if (C('SESSION_TYPE')) {
            // 读取session驱动
            $type   = C('SESSION_TYPE');
            $class  = strpos($type, '\\') ? $type : 'Think\\Session\\Driver\\' . ucwords(strtolower($type));
            $hander = new $class();
            session_set_save_handler(
                array(&$hander, "open"),
                array(&$hander, "close"),
                array(&$hander, "read"),
                array(&$hander, "write"),
                array(&$hander, "destroy"),
                array(&$hander, "gc"));
        }
        // 启动session
        if (C('SESSION_AUTO_START')) {
            session_start();
        }

    } elseif ('' === $value) {
        if ('' === $name) {
            // 获取全部的session
            return $prefix ? $_SESSION[$prefix] : $_SESSION;
        } elseif (0 === strpos($name, '[')) {
            // session 操作
            if ('[pause]' == $name) {
                // 暂停session
                session_write_close();
            } elseif ('[start]' == $name) {
                // 启动session
                session_start();
            } elseif ('[destroy]' == $name) {
                // 销毁session
                $_SESSION = array();
                session_unset();
                session_destroy();
            } elseif ('[regenerate]' == $name) {
                // 重新生成id
                session_regenerate_id();
            }
        } elseif (0 === strpos($name, '?')) {
            // 检查session
            $name = substr($name, 1);
            if (strpos($name, '.')) {
                // 支持数组
                list($name1, $name2) = explode('.', $name);
                return $prefix ? isset($_SESSION[$prefix][$name1][$name2]) : isset($_SESSION[$name1][$name2]);
            } else {
                return $prefix ? isset($_SESSION[$prefix][$name]) : isset($_SESSION[$name]);
            }
        } elseif (is_null($name)) {
            // 清空session
            if ($prefix) {
                unset($_SESSION[$prefix]);
            } else {
                $_SESSION = array();
            }
        } elseif ($prefix) {
            // 获取session
            if (strpos($name, '.')) {
                list($name1, $name2) = explode('.', $name);
                return isset($_SESSION[$prefix][$name1][$name2]) ? $_SESSION[$prefix][$name1][$name2] : null;
            } else {
                return isset($_SESSION[$prefix][$name]) ? $_SESSION[$prefix][$name] : null;
            }
        } else {
            if (strpos($name, '.')) {
                list($name1, $name2) = explode('.', $name);
                return isset($_SESSION[$name1][$name2]) ? $_SESSION[$name1][$name2] : null;
            } else {
                return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
            }
        }
    } elseif (is_null($value)) {
        // 删除session
        if (strpos($name, '.')) {
            list($name1, $name2) = explode('.', $name);
            if ($prefix) {
                unset($_SESSION[$prefix][$name1][$name2]);
            } else {
                unset($_SESSION[$name1][$name2]);
            }
        } else {
            if ($prefix) {
                unset($_SESSION[$prefix][$name]);
            } else {
                unset($_SESSION[$name]);
            }
        }
    } else {
        // 设置session
        if (strpos($name, '.')) {
            list($name1, $name2) = explode('.', $name);
            if ($prefix) {
                $_SESSION[$prefix][$name1][$name2] = $value;
            } else {
                $_SESSION[$name1][$name2] = $value;
            }
        } else {
            if ($prefix) {
                $_SESSION[$prefix][$name] = $value;
            } else {
                $_SESSION[$name] = $value;
            }
        }
    }
    return null;
}

/**
 * Cookie 设置、获取、删除
 * @param string $name cookie名称
 * @param mixed $value cookie值
 * @param mixed $option cookie参数
 * @return mixed
 */
function cookie($name = '', $value = '', $option = null)
{
    // 默认设置
    $config = array(
        'prefix'   => C('COOKIE_PREFIX'), // cookie 名称前缀
        'expire'   => C('COOKIE_EXPIRE'), // cookie 保存时间
        'path'     => C('COOKIE_PATH'), // cookie 保存路径
        'domain'   => C('COOKIE_DOMAIN'), // cookie 有效域名
        'secure'   => C('COOKIE_SECURE'), //  cookie 启用安全传输
        'httponly' => C('COOKIE_HTTPONLY'), // httponly设置
    );
    // 参数设置(会覆盖黙认设置)
    if (!is_null($option)) {
        if (is_numeric($option)) {
            $option = array('expire' => $option);
        } elseif (is_string($option)) {
            parse_str($option, $option);
        }

        $config = array_merge($config, array_change_key_case($option));
    }
    if (!empty($config['httponly'])) {
        ini_set("session.cookie_httponly", 1);
    }
    // 清除指定前缀的所有cookie
    if (is_null($name)) {
        if (empty($_COOKIE)) {
            return null;
        }

        // 要删除的cookie前缀，不指定则删除config设置的指定前缀
        $prefix = empty($value) ? $config['prefix'] : $value;
        if (!empty($prefix)) {
            // 如果前缀为空字符串将不作处理直接返回
            foreach ($_COOKIE as $key => $val) {
                if (0 === stripos($key, $prefix)) {
                    setcookie($key, '', time() - 3600, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
                    unset($_COOKIE[$key]);
                }
            }
        }
        return null;
    } elseif ('' === $name) {
        // 获取全部的cookie
        return $_COOKIE;
    }
    $name = $config['prefix'] . str_replace('.', '_', $name);
    if ('' === $value) {
        if (isset($_COOKIE[$name])) {
            $value = $_COOKIE[$name];
            if (0 === strpos($value, 'think:')) {
                $value = substr($value, 6);
                return array_map('urldecode', json_decode(MAGIC_QUOTES_GPC ? stripslashes($value) : $value, true));
            } else {
                return $value;
            }
        } else {
            return null;
        }
    } else {
        if (is_null($value)) {
            setcookie($name, '', time() - 3600, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
            unset($_COOKIE[$name]); // 删除指定cookie
        } else {
            // 设置cookie
            if (is_array($value)) {
                $value = 'think:' . json_encode(array_map('urlencode', $value));
            }
            $expire = !empty($config['expire']) ? time() + intval($config['expire']) : 0;
            setcookie($name, $value, $expire, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
            $_COOKIE[$name] = $value;
        }
    }
    return null;
}

/**
 * 加载动态扩展文件
 * @var string $path 文件路径
 * @return void
 */
function load_ext_file($path)
{
    // 加载自定义外部文件
    if ($files = C('LOAD_EXT_FILE')) {
        $files = explode(',', $files);
        foreach ($files as $file) {
            $file = $path . 'Common/' . $file . '.php';
            if (is_file($file)) {
                include $file;
            }

        }
    }
    // 加载自定义的动态配置文件
    if ($configs = C('LOAD_EXT_CONFIG')) {
        if (is_string($configs)) {
            $configs = explode(',', $configs);
        }

        foreach ($configs as $key => $config) {
            $file = is_file($config) ? $config : $path . 'Conf/' . $config . CONF_EXT;
            if (is_file($file)) {
                is_numeric($key) ? C(load_Config($file)) : C($key, load_Config($file));
            }
        }
    }
}

/**
 * 获取客户端IP地址
 * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
 * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
 * @return mixed
 */
function get_client_ip($type = 0, $adv = false)
{
    $type      = $type ? 1 : 0;
    static $ip = null;
    if (null !== $ip) {
        return $ip[$type];
    }

    if ($adv) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) {
                unset($arr[$pos]);
            }

            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    // IP地址合法验证
    $long = sprintf("%u", ip2long($ip));
    $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
    return $ip[$type];
}

/**
 * 发送HTTP状态
 * @param integer $code 状态码
 * @return void
 */
function send_http_status($code)
{
    static $_status = array(
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Moved Temporarily ', // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // 306 is deprecated but reserved
        307 => 'Temporary Redirect',
        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded',
    );
    if (isset($_status[$code])) {
        header('HTTP/1.1 ' . $code . ' ' . $_status[$code]);
        // 确保FastCGI模式下正常
        header('Status:' . $code . ' ' . $_status[$code]);
    }
}

function think_filter(&$value)
{
    // TODO 其他安全过滤

    // 过滤查询特殊字符
    if (preg_match('/^(EXP|NEQ|GT|EGT|LT|ELT|OR|XOR|LIKE|NOTLIKE|NOT BETWEEN|NOTBETWEEN|BETWEEN|NOTIN|NOT IN|IN)$/i', $value)) {
        $value .= ' ';
    }
}

// 不区分大小写的in_array实现
function in_array_case($value, $array)
{
    return in_array(strtolower($value), array_map('strtolower', $array));
}




function getExceptionTraceAsString($exception) {
    $rtn = "";
    $count = 0;
    foreach ($exception->getTrace() as $frame) {
        empty($frame['file']) && $frame['file'] = "[internal function]"; //空则赋值
        empty($frame['class']) || $frame['class'] = $frame['class']."->"; //空则不赋值，也就是非空才赋值，高手的写法，菜鸟的内心是无法理解的
        $args = "";
        if (isset($frame['args'])) {
            $args = array();
            foreach ($frame['args'] as $arg) {
                if (is_string($arg)) {
                    $args[] = "'" . $arg . "'";
                } elseif (is_array($arg)) {
                    $args[] = "Array";
                } elseif (is_null($arg)) {
                    $args[] = 'NULL';
                } elseif (is_bool($arg)) {
                    $args[] = ($arg) ? "true" : "false";
                } elseif (is_object($arg)) {
                    $args[] = get_class($arg);
                } elseif (is_resource($arg)) {
                    $args[] = get_resource_type($arg);
                } else {
                    $args[] = $arg;
                }
            }
            $args = join(", ", $args);
        }
        $rtn .= sprintf( "#%s %s(%s): %s%s(%s)\n",
            $count,
            $frame['file'],
            $frame['line'],
            $frame['class'],
            $frame['function'],
            $args );
        $count++;
    }
    return $rtn;
}










//=============== 移动设备判断 start =====================//


function isMobile(){
    $r = userAgent($_SERVER['HTTP_USER_AGENT']);
    return ($r == "mobile");
}

function androidTablet($ua){ //Find out if it is a tablet
    if(strstr(strtolower($ua), 'android') ){//Search for android in user-agent
        if(!strstr(strtolower($ua), 'mobile')){ //If there is no ''mobile' in user-agent (Android have that on their phones, but not tablets)
            return true;
        }
    }
}

function userAgent($ua){
    ## This credit must stay intact (Unless you have a deal with @lukasmig or frimerlukas@gmail.com
    ## Made by Lukas Frimer Tholander from Made In Osted Webdesign.
    ## Price will be $2

    $iphone = strstr(strtolower($ua), 'mobile'); //Search for 'mobile' in user-agent (iPhone have that)
    $android = strstr(strtolower($ua), 'android'); //Search for 'android' in user-agent
    $windowsPhone = strstr(strtolower($ua), 'phone'); //Search for 'phone' in user-agent (Windows Phone uses that)



    $androidTablet = androidTablet($ua); //Do androidTablet function
    $ipad = strstr(strtolower($ua), 'ipad'); //Search for iPad in user-agent

    if($androidTablet || $ipad){ //If it's a tablet (iPad / Android)
        return 'tablet';
    }
    elseif($iphone && !$ipad || $android && !$androidTablet || $windowsPhone){ //If it's a phone and NOT a tablet
        return 'mobile';
    }
    else{ //If it's not a mobile device
        return 'desktop';
    }
}


//=============== 移动设备判断 end =====================//











































//公共函数
function toDate($time, $format = 'Y-m-d H:i:s') {
    if (empty ( $time )) {
        return '';
    }
    $format = str_replace ( '#', ':', $format );
    return date ($format, $time );
}

//css样式状态
function getStatus2($status, $imageShow = true) {
    switch ($status) {
        case 0 :
            $showText = '禁用';
            $showImg = '<span class="label">禁用</span>';
            break;
        case 2 :
            $showText = '待审';
            $showImg = '<span class="label label-warning">待审</span>';
            break;
        case - 1 :
            $showText = '删除';
            $showImg = '<span class="label label-important">删除</span>';
            break;
        case 1 :
        default :
            $showText = '正常';
            $showImg = '<span class="label label-success">正常</span>';

    }
    return ($imageShow === true) ?  $showImg  : $showText;

}
//图片样式状态
function getStatus($status, $imageShow = true) {
    switch ($status) {
        case 0 :
            $showText = '禁用';
            $showImg = '<IMG SRC="' . '' . '__SKIN__/Common/locked.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="禁用">';
            break;
        case 2 :
            $showText = '待审';
            $showImg = '<IMG SRC="' . '' . '__SKIN__/Common/prected.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="待审">';
            break;
        case - 1 :
            $showText = '删除';
            $showImg = '<IMG SRC="' . '' . '__SKIN__/Common/del.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="删除">';
            break;
        case 1 :
        default :
            $showText = '正常';
            $showImg = '<IMG SRC="' . '' . '__SKIN__/Common/ok.gif" WIDTH="20" HEIGHT="20" BORDER="0" ALT="正常">';

    }
    return ($imageShow === true) ?  $showImg  : $showText;

}

function getDefaultStyle($style)
{
    if (empty ($style)) {
        return 'blue';
    } else {
        return $style;
    }

}

    function showStatus($status, $id)
    {
        switch ($status) {
            case 0 :
                $info = '<a href="javascript:resume(' . $id . ')">恢复</a>';
                break;
            case 2 :
                $info = '<a href="javascript:pass(' . $id . ')">批准</a>';
                break;
            case 1 :
                $info = '<a href="javascript:forbid(' . $id . ')">禁用</a>';
                break;
            case -1 :
                $info = '<a href="javascript:recycle(' . $id . ')">还原</a>';
                break;
        }
        return $info;
    }

























if (!function_exists('getallheaders')){
    function getallheaders($raw=false) { 
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$key] = $value;
            }
        }
        if($raw){
            $str = "";
            foreach ($headers as $k => $v){
                $str .= "$k: $v\r\n";
            }
            return $str;
        }
        return $headers; 
    }
}

function make_coupon_card() {
    $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $rand = $code[rand(0,25)]
        .strtoupper(dechex(date('m')))
        .date('d').substr(time(),-5)
        .substr(microtime(),2,5)
        .sprintf('%02d',rand(0,99));
    for(
        $a = md5( $rand, true ),
        $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV',
        $d = '',
        $f = 0;
        $f < 8;
        $g = ord( $a[ $f ] ),
        $d .= $s[ ( $g ^ ord( $a[ $f + 8 ] ) ) - $g & 0x1F ],
        $f++
    );
    return $d;
}
//echo make_coupon_card();


/**
 * 十进制数转换成62进制
 *
 * @param integer $num
 * @return string
 */
function to62($num) {
    $to = 62;
    $dict = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $ret = '';
    do {
        $ret = $dict[bcmod($num, $to)] . $ret;
        $num = bcdiv($num, $to);
    } while ($num > 0);
    return $ret;
}

/**
 * 62进制数转换成十进制数
 *
 * @param string $num
 * @return string
 */
function from62($num) {
    $from = 62;
    $num = strval($num);
    $dict = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $len = strlen($num);
    $dec = 0;
    for($i = 0; $i < $len; $i++) {
        $pos = strpos($dict, $num[$i]);
        $dec = bcadd(bcmul(bcpow($from, $len - $i - 1), $pos), $dec);
    }
    return $dec;
}

function datetime(){
    return date('Y-m-d H:i:s',time());
}

//字符串version转int
function versionToInt($version){
    if(strpos($version,',') === false){
        return $version;
    }else{
        $decimals = explode('.',$version)[1];
        return $version*$decimals;
    }

}



/**
 * curl
 *
 * @param
 *        	string url
 * @param
 *        	array 数据
 * @param
 *        	int 请求超时时间
 * @param
 *        	bool HTTPS时是否进行严格认证
 * @return string
 */
function curl_get_content($url, $data = "", $method = "get", $timeout = 30, $CA = false){

    // $url = "http://www.baidu.com";
    $cacert = getcwd() . '/cacert.pem'; // CA根证书
    $SSL = substr($url, 0, 8) == "https://" ? true : false;
    $ch = curl_init();
    if(is_object($data)){
        $data = (array)$data;
    }

    $data_is_json = is_json($data);

    $method = strtolower($method);
    if($method == 'get') {
        if(is_array($data)) {
            $data = http_build_query($data);
        }
        $url .= "?" . $data;
    } else {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        // curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); //data with URLEncode
    }
    //echo $url;
    //var_dump($data);exit;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout - 2);
    if($SSL && $CA) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // 只信任CA颁布的证书
        curl_setopt($ch, CURLOPT_CAINFO, $cacert); // CA根证书（用来验证的网站证书是否是CA颁布）
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // 检查证书中是否设置域名，并且是否与提供的主机名匹配
    } else if($SSL && ! $CA) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 信任任何证书
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 检查证书中是否设置域名
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Expect:'
    )); // 避免data数据过长问题
    // var_dump($data);

    $headerArr[] = 'PARAMS:android#1.4.2#wandoujias';
    if($data_is_json) $headerArr[] = 'Content-Type: application/json; charset=utf-8';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
    //curl_setopt($ch, CURLOPT_PROXY, "192.168.3.111:8888");
    $ret = curl_exec($ch);
    if(empty($ret)) {
        var_dump(curl_error($ch)); // 查看报错信息
    }
    // var_dump($ret);
    // exit('x');
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if($httpCode != 200) {
        $tmp = array();
        $tmp['http_code'] = $httpCode;
        $tmp['data'] = $ret;

        //echo "\n服务器错误：$httpCode";
        //echo $ret;
        $ret = json_encode($tmp);
    }
    curl_close($ch);
    //var_dump($ret);
    return $ret;
}

function is_json($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}





//================== 用户相关 start ================//
/**
 * 保存用户登录凭证
 */
function setUserAuth($u){
    //$u = array();

    if (C('AUTH_STORE_WAY') == 'session') {
        $_SESSION["username"] = $u['username'];
    }else{
        if(C('AUTH_ENCRYPT')){
            cookie("user_id", \Think\Crypt::encrypt($u['user_id'],C('crypt_key')));
        }else{
            //var_dump($u['user_id']);
            cookie("user_id", $u['user_id']);
        }
    }
}

/**
 * 获取用户登录凭证信息
 */
function getUserAuth(){
    $u = array();
    if (C('AUTH_STORE_WAY') == 'session') {
        $id = $_SESSION[C('USER_AUTH_KEY')];
        $u['user_id'] = $_SESSION[C('USER_AUTH_KEY')];
    }elseif($user_id = I('user_id')){
        if(C('AUTH_ENCRYPT')) {
            $u['user_id'] = \Think\Crypt::decrypt(cookie('user_id', ""), C('crypt_key'));
        }else{
            $u['user_id'] =cookie('user_id',"");
        }

    }else{
        if(C('AUTH_ENCRYPT')){
            $u['user_id'] = \Think\Crypt::decrypt(cookie('user_id',""),C('crypt_key'));
        }else {
            $u['user_id'] = cookie('user_id', "");
        }
        //var_dump($_COOKIE);
        //exit;
    }
    //$a = \Think\Crypt::encrypt('1234',C('crypt_key'));
    //var_dump($a);
    //$b = \Think\Crypt::decrypt($a,C('crypt_key'));
    //var_dump($b);exit;
    //var_dump($_COOKIE);
    //var_dump($u);exit;
    $u = array_filter($u);

    return $u;
}

/**
 * 获取用户登录凭证信息
 */
function clearUserAuth(){
    $u = array();
    if (C('AUTH_STORE_WAY') == 'session') {
        unset($_SESSION[C('USER_AUTH_KEY')]);
        unset($_SESSION["username"]);
        unset($_SESSION[C('USER_AUTH_KEY')]);
        unset($_SESSION['utype']);

    }else{

        cookie("username",null);
        cookie("user_id",null);
    }
    return $u;
}



/**
 * 得到登录的用户id
 */
function getUserId(){
    if (C('AUTH_STORE_WAY') == 'USER_AUTH_KEY') {
         $id = $_SESSION[C('USER_AUTH_KEY')];
       
    }else{
       
         $id = Cookie::get(C('USER_AUTH_KEY'));

    }
    //echo $_SESSION[C('USER_AUTH_KEY')];
    return $id;
}

/**
 * 得到登录的用户信息
 */
function getUserInfo(){
    //个人简历
    //企业信息
    //学校信息
    $sql = "";
    $user = D('Member');
    if ($id = getUserId()) {
        return $user->find(getUserId());
    }
}
//================== 用户相关 end ================//



//cgf获取选项值
function optionsValue($key,$fieldName){
    return C($fieldName)[$key];
}


//参数签名
function parameters_sign($parameters,$key='123456',$secret='654321' ){
    ksort($parameters);
    $str = "";
    foreach ($parameters as $k => $v) {
        $str .= $k.$v;
    }
    return strtolower(sha1($key.$str.$secret));
}


function ajaxReturn($data, $type = '', $json_option = 0)
{
    if (empty($type)) {
        $type = C('DEFAULT_AJAX_RETURN');
    }

    switch (strtoupper($type)) {
        case 'JSON':
            // 返回JSON数据格式到客户端 包含状态信息
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode($data, $json_option));
        case 'XML':
            // 返回xml格式数据
            header('Content-Type:text/xml; charset=utf-8');
            exit(xml_encode($data));
        case 'JSONP':
            // 返回JSON数据格式到客户端 包含状态信息
            header('Content-Type:application/json; charset=utf-8');
            $handler = isset($_GET[C('VAR_JSONP_HANDLER')]) ? $_GET[C('VAR_JSONP_HANDLER')] : C('DEFAULT_JSONP_HANDLER');
            exit($handler . '(' . json_encode($data, $json_option) . ');');
        case 'EVAL':
            // 返回可执行的js脚本
            header('Content-Type:text/html; charset=utf-8');
            exit($data);
        default:
            // 用于扩展其他返回格式数据
            Hook::listen('ajax_return', $data);
    }
}


 function success($data, $type = '', $json_option = 0)
{
    $ret = [];
    $ret['code'] = 1;
    $ret['msg'] = 'success';
    $ret['data'] = $data;
    ajaxReturn($ret,C('ret_format'));

}

function error($msg,$extra=''){
    $ret = [];
    $ret['code'] = 0;
    $ret['msg'] = $msg;
    $ret['data'] = $extra;
    ajaxReturn($ret,C('ret_format'));
}


function get_url_contents($url) {
    if (ini_get ( "allow_url_fopen" ) == "1")
        return file_get_contents ( $url );

    $ch = curl_init ();
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
    curl_setopt ( $ch, CURLOPT_URL, $url );
    $result = curl_exec ( $ch );
    curl_close ( $ch );

    return $result;
}




function ri($str){
    exit(var_dump($str));
}

//去掉图片的域名
function getImgPath($url,$prefixThumb='thumb_'){
    //$domain = ;
    $path = str_replace($prefixThumb,'',str_replace(C('FILE_URL'),"",$url)); //清除域名，及thumb_
    $arr = explode('/',$path);
    $filename = $arr[count($arr)-1];
    $fileinfo = explode('.',$filename);
    $fileinfo['path'] = C('SAVE_PATH').str_replace($filename,'',$path);
    //$filename = $filename[1];
    return $fileinfo;
}

function toHtml($str){
    $str = nl2br($str);
    $str = str_replace(' ',"&nbsp;",$str);
    return $str;
}

function unHtml(){
    $str = str_replace('&nbsp;',' ',$str);
    $str = str_replace('<br />','\n',$str);

}

/**
+----------------------------------------------------------
 * 字符串截取，支持中文和其他编码
+----------------------------------------------------------
 * @static
 * @access public
+----------------------------------------------------------
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
+----------------------------------------------------------
 * @return string
+----------------------------------------------------------
 */
function msubstr($str, $start=0, $length, $charset="utf-8", $suffix=true)
{
    if(function_exists("mb_substr"))
        return mb_substr($str, $start, $length, $charset);
    elseif(function_exists('iconv_substr')) {
        return iconv_substr($str,$start,$length,$charset);
    }
    $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
    $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
    $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
    $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
    preg_match_all($re[$charset], $str, $match);
    $slice = join("",array_slice($match[0], $start, $length));
    if($suffix) return $slice."…";
    return $slice;
}



//使用 linux系统命令 sendmail
function sendmail_linux($subject,$body,$to,$toname,$from = "",$fromname = '家谱网',$altbody = '家谱网的邮件',$wordwrap = 80,$mailconf = ''){
    Vendor('phpmail.class#phpmailer');
    $mail             = new PHPMailer();
    $mail->IsSendmail();
    $mail->SMTPDebug  = 2;                   // enables SMTP debug // 1 = errors and messages// 2 = messages only
    $from = "admin@".DOMAIN;
    $fromname = C('SITE_TITLE');
    $mail->SetFrom($from, $fromname);
    //$mail->AddReplyTo($to,$toname);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->Subject    = $subject;
    $body             = eregi_replace("[\]",'',$body);
    //$mail->AltBody    = "AltBody"; // optional, comment out and test
    $mail->MsgHTML($body);
    $mail->AddAddress($to, $toname);
    //$mail->AddAttachment("images/phpmailer.gif");      // attachment
    //$mail->AddAttachment("images/phpmailer_mini.gif"); // attachment
    if(!$mail->Send()) {
        //echo "Mailer Error: " . $mail->ErrorInfo;
        return false;
    } else {
        return true;
    }
}

//使用smtp
function sendmail($subject,$body,$to,$toname,$from = "",$fromname = '家谱网',$altbody = '家谱网的邮件',$wordwrap = 80,$mailconf = ''){
    Vendor('phpmail.class#phpmailer');
    $mail             = new PHPMailer();
    $mail->IsSMTP(); // telling the class to use SMTP
    $mail->SMTPDebug  = 2;                   // enables SMTP debug // 1 = errors and messages// 2 = messages only

    $mail->SMTPAuth   = true;                  // enable SMTP authentication
    $mail->Host       = C('M_HOST'); // sets the SMTP server
    $mail->Port       = 25;                    // set the SMTP port for the GMAIL server
    $mail->Username   = C('M_USER'); // SMTP account username
    $mail->Password   = C('M_PASSWORD');        // SMTP account password

    $from = !strpos(C('M_USER'),'@') ? C('M_USER').'@'.C('M_DOMAIN') : 'admin@163.com';
    $mail->SetFrom($from, $fromname);

    //$mail->AddReplyTo($to,$toname);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';

    $mail->Subject    = $subject;
    //$body             = eregi_replace("[\]",'',$body);
    //$mail->AltBody    = "AltBody"; // optional, comment out and test

    $mail->MsgHTML($body);

    $mail->AddAddress($to, $toname);

    //$mail->AddAttachment("images/phpmailer.gif");      // attachment
    //$mail->AddAttachment("images/phpmailer_mini.gif"); // attachment

    if(!$mail->Send()) {
        //echo "Mailer Error: " . $mail->ErrorInfo;
        return false;
    } else {
        return true;
    }
}



/*function get_client_ip() {
	if (getenv ( "HTTP_CLIENT_IP" ) && strcasecmp ( getenv ( "HTTP_CLIENT_IP" ), "unknown" ))
		$ip = getenv ( "HTTP_CLIENT_IP" );
	else if (getenv ( "HTTP_X_FORWARDED_FOR" ) && strcasecmp ( getenv ( "HTTP_X_FORWARDED_FOR" ), "unknown" ))
		$ip = getenv ( "HTTP_X_FORWARDED_FOR" );
	else if (getenv ( "REMOTE_ADDR" ) && strcasecmp ( getenv ( "REMOTE_ADDR" ), "unknown" ))
		$ip = getenv ( "REMOTE_ADDR" );
	else if (isset ( $_SERVER ['REMOTE_ADDR'] ) && $_SERVER ['REMOTE_ADDR'] && strcasecmp ( $_SERVER ['REMOTE_ADDR'], "unknown" ))
		$ip = $_SERVER ['REMOTE_ADDR'];
	else
		$ip = "unknown";
	return ($ip);
}*/

function IP($ip = '', $file = 'UTFWry.dat') {
    $_ip = array ();
    if (isset ( $_ip [$ip] )) {
        return $_ip [$ip];
    } else {
        import ( "ORG.Net.IpLocation" );
        $iplocation = new IpLocation ( $file );
        $location = $iplocation->getlocation ( $ip );
        $_ip [$ip] = $location ['country'] . $location ['area'];
    }
    return $_ip [$ip];
}

//============== ip 获取城市函数===============//
function ip2num($ip){
    $ipadd = explode('.',$ip);
    return intval($ipadd[0])*256*256*256 + intval($ipadd[1])*256*256 + intval($ipadd[2]*256) + intval($ipadd[3]);
}

/*$ipnum 运算之后的数字*/
function getcitybydb($ip){
    $ipnum = ip2num($ip);
    $m = M('Ip');
    $r = $m->query("select city,province from mmm_ip where $ipnum>=ip1 and $ipnum<=ip2 limit 1");

    $r = $r[0];
    //echo "select city,province from ip where $ipnum>=ip1 and $ipnum<=ip2 limit 1"; //select city,province from p8_fenlei_ip where ip1<= 3729367335 and ip2>=3729367335 limit 1
    if(!is_array($r)){
        //未找到，返回默认城市
        $r['province'] = '上海';
        $r['city'] = '上海';
    }
    return $r;
}

/**
 * 根据ip得到城市
 * @param string $ip
 */
function getcity($ip = ''){
    //global $onlineip;
    $ip || $ip = get_client_ip();
    if($_COOKIE["IP_province"] && $_COOKIE["IP_city"]){
        $r['province'] = $_COOKIE['IP_province'];
        $r['city']  = $_COOKIE['IP_city'];
        return $r;
    }else{
        $r = getcitybydb($ip);
        setcookie("IP_province",$r['province'],time()+7*86400);
        setcookie("IP_city",$r['city'],time()+7*86400);
        return $r;
    }
}
//============== ip 获取城市函数  end ===============//


/**
 *高亮关键字
 */
function hightLightKeyword($str,$keyword){
    $replaceStr = "<span style=' background-color:#FF0; '>$keyword</span>";
    //echo $str;
    //exit(str_ireplace("s","2","SB"));
    return str_ireplace($keyword,$replaceStr,$str);
}

/**
 *分割字符，返回有效数组
 */
function validExplode($separator,$str){

    if($str) {
        $re = array();
        $arr = explode($separator, $str);
        foreach($arr as $v) {
            if($v) $re[] = $v;
        }
        return $re;
    }
}
/**
 * 分割字符，返回有效数组
 * 与str_split 类似
 */
function strToChar($str){
    //$str = 'string';
    return preg_split('//', $str, -1, PREG_SPLIT_NO_EMPTY);
    //print_r($chars); //结果：Array ( [0] => s [1] => t [2] => r [3] => i [4] => n [5] => g )
}

//==================格式化数据 模板中使用  start ====================//
function img($v){
    if($v){
        $arr = parse_url($v);
        if(strtolower($arr['scheme']) != 'http'){
            return C('IMG_URL').$v;
        }
        return $v;
    }else{
        return C('DEFAULT_IMG');
    }
}

function getEnumTitle($id,$egroup='job_category',$way='evalue'){
    //echo $egroup;exit;
    $ids = explode(',',$id);
    $titles = '';
    $e = D('Enum');
    foreach($ids as $v){

        if($v!=''){
            $titles .= $e->getTitle($v,$egroup,$way).',';
        }
    }

    return substr($titles,0,-1);
}
/**
 * 获取男女文字
 * @param $v
 */
function sex($v) {
    if($v == 0) return "女";
    if($v == 1) return "男";
    return "";
    //return $v ? "男" : "女";
}


/**
 * 获取子女性别文字
 * @param $v
 */
function sex_child($v) {
    if($v == 0) return "女";
    if($v == 1) return "子";
    return "";
    //return $v ? "男" : "女";
}

/**
 * 获取配偶性别文字
 * @param $v
 */
function sex_spouse($v) {
    if($v == 0) return "妻";
    if($v == 1) return "夫";
    return "";
    //return $v ? "男" : "女";
}


/**
 * dateFormat 格式化日期
 * @param unknown_type $time
 * @param unknown_type $format
 */
function df($time,$format = 'Y-m-d'){
    return date($format,$time);
}

/**
 * 得到统计查询结果数量的sql语句
 * @param unknown_type $sql
 */
function getCountSql($sql) {
    return preg_replace("/(select) (.*) (from .*)/i","\$1 count(id) \$3",$sql);;
}

/**
 * 截取职位类别的字符串
 * @param $cateid 类别
 * @param $n 截取长度
 */
function cateSub($cateid,$n){
    return msubstr(getEnumTitle($cateid),0,$n);
}

/**
 * 获取省的名称
 * @param string $code 省的拼音
 * @return
 */
function getProvince($code){
    if ($code) { //code优先
        $key = 'province.'.$code; //code为省拼音
        $province = C($key); //省的汉字名称
        setcookie("IP_province",$province,time()+7*86400);
        //$_COOKIE["IP_province"] = $province;
    }elseif($_COOKIE["IP_province"]){ //cookie其次
        $province = $_COOKIE["IP_province"];
    }else{ //根据ip获取最后
        $ip = '60.190.28.48';
        $ipcity = getcity($ip);
        $province = $ipcity['province'];
    }
    return $province;
}

/**
 * 生成公司显示url
 * @param unknown_type $id
 */
function curl($id){
    return "/company/show_$id.html";

}

//当然下面的更绝,不过好像违背了php与html分离原则，但用起来确实很方便。没有class,id其它属性。
function jurl2($id,$title,$taget="_self"){

    return "<a href=\"/job/show_$id.html\" target=\"$taget\" title=\"$title\">{$title}</a>";
}

/**
 * 生成职位显示url
 * @param unknown_type $id
 */
function jurl($id){
    return "/job/show_$id.html";
}

/**
 * 生成简历显示url
 * @param unknown_type $id
 */
function rurl($id){
    return "/resume/$id/show.html";
}

/**
 * 获取省名称通过key
 * @param $code
 */
function getProvinceByKey($code){
    $area = D('Area');
    $province = $area->getProvince (); //省列表
    $province = $province[$code];
    if (!$province) {
        return '中国';
    }
}

/*得到行业分类的列表*/
function getIndustryBigClass($key){
    if ($bigClass == -1) {
        return '所有';
    }
    $e = D('Enum');
    return $e->getTitle($v[$key]);

}

//==================格式化数据  end ====================//

/**
 * 区域条件生成
 * @param unknown_type $province
 */
function areaCondition($province){
    return array(array("like","%$province%"),array("like",'全国'),array("eq",''),'or');//区域条件，多次使用，后台公司，简历列表，前台招聘首页，搜索页
}



function zhjson($v){
    if(is_array($v)){
        foreach($v as $key =>$value){
            $v[$key]=zhjson($value);
        }
        return $v;
    }else{
        return iconv("gb2312","utf-8",$v);
    }
}

function getProvincePingying($province){
    $tmp = explode(',', $province); //'江苏/南京,浙江/杭州'
    $area = array();
    if(is_array($tmp)) { // array('江苏/南京','浙江/杭州')
        foreach($tmp as $v) {
            if($v = trim($v)) {
                $t = explode('/', $v);
                $area[] = $t[0];
            }
        }
    }
    if (count($area) == 1) {
        //return $area[0];
    }
    $province = C('province');
    //$arr = array();
    foreach($province as $k => $v){
        foreach($area as $p){
            //echo "$p---$v";exit;
            if(false !== strpos($p,$v)){
                $arr[$k] = $p;
                break;
            }
        }
    }

    return $arr;
}

/**
 * 登录后的登录框内容
 */
function loginedbar(){
    //ECHO Cookie::get(C('USER_AUTH_KEY'));
    $r = getUserInfo();
    if(is_array($r)){
        $v = new View();
        $v->assign('u',$r);
        //echo __FILE__;
        //echo IROOT.'/User/Tpl/default/Public/logined';
        echo $v->fetch('../21mmm_tp/User/Tpl/default/Public/logined.html');
    }
}


//============ 旧程序 cookie函数============
function dsetcookie($var, $value = '', $life = 0, $prefix = 1, $httponly = false) {
    //global $cookiepre, $cookiedomain, $cookiepath, $_SERVER;
    $cookiepre = 'FSQ_';
    $cookiedomain = DOMAIN;
    $cookiepath = '/';
    $timestamp=time();
    $var = ($prefix ? $cookiepre : '').$var;
    if($value == '' || $life < 0) {
        $value = '';
        $life = -1;
    }
    $life = $life > 0 ? $timestamp + $life : ($life < 0 ? $timestamp - 31536000 : 0);
    $path = $httponly && PHP_VERSION < '5.2.0' ? "$cookiepath; HttpOnly" : $cookiepath;
    $secure = $_SERVER['SERVER_PORT'] == 443 ? 1 : 0;
    //echo $var; echo "--$value";
    if(PHP_VERSION < '5.2.0') {
        setcookie($var, $value, $life, $path, $cookiedomain, $secure);
    } else {
        setcookie($var, $value, $life, $path, $cookiedomain, $secure, $httponly);
    }
}

function clearcookies() {
    foreach(array('sid', 'auth', 'visitedfid', 'onlinedetail', 'loginuser', 'activationauth') as $k) {
        dsetcookie($k);
    }
}

function g_cookie($var){
    $cookiepre = 'FSQ_';
    $var=$cookiepre.$var;
    return $_COOKIE[$var];
}





// 缓存文件
function cmssavecache($name = '', $fields = '') {
    $Model = D ( $name );
    $list = $Model->select ();
    $data = array ();
    foreach ( $list as $key => $val ) {
        if (empty ( $fields )) {
            $data [$val [$Model->getPk ()]] = $val;
        } else {
            // 获取需要的字段
            if (is_string ( $fields )) {
                $fields = explode ( ',', $fields );
            }
            if (count ( $fields ) == 1) {
                $data [$val [$Model->getPk ()]] = $val [$fields [0]];
            } else {
                foreach ( $fields as $field ) {
                    $data [$val [$Model->getPk ()]] [] = $val [$field];
                }
            }
        }
    }
    $savefile = cmsgetcache ( $name );
    // 所有参数统一为大写
    $content = "<?php\nreturn " . var_export ( array_change_key_case ( $data, CASE_UPPER ), true ) . ";\n?>";
    file_put_contents ( $savefile, $content );
}

function cmsgetcache($name = '') {
    return DATA_PATH . '~' . strtolower ( $name ) . '.php';
}



function getNodeName($id) {
    if (Session::is_set ( 'nodeNameList' )) {
        $name = Session::get ( 'nodeNameList' );
        return $name [$id];
    }
    $Group = D ( "Node" );
    $list = $Group->getField ( 'id,name' );
    $name = $list [$id];
    Session::set ( 'nodeNameList', $list );
    return $name;
}

function get_pawn($pawn) {
    if ($pawn == 0)
        return "<span style='color:green'>没有</span>";
    else
        return "<span style='color:red'>有</span>";
}
function get_patent($patent) {
    if ($patent == 0)
        return "<span style='color:green'>没有</span>";
    else
        return "<span style='color:red'>有</span>";
}


function getNodeGroupName($id) {
    if (empty ( $id )) {
        return '未分组';
    }
    if (isset ( $_SESSION ['nodeGroupList'] )) {
        return $_SESSION ['nodeGroupList'] [$id];
    }
    $Group = D ( "Group" );
    $list = $Group->getField ( 'id,title' );
    $_SESSION ['nodeGroupList'] = $list;
    $name = $list [$id];
    return $name;
}

function getCardStatus($status) {
    switch ($status) {
        case 0 :
            $show = '未启用';
            break;
        case 1 :
            $show = '已启用';
            break;
        case 2 :
            $show = '使用中';
            break;
        case 3 :
            $show = '已禁用';
            break;
        case 4 :
            $show = '已作废';
            break;
    }
    return $show;

}


/**
+----------------------------------------------------------
 * 获取登录验证码 默认为4位数字
+----------------------------------------------------------
 * @param string $fmode 文件名
+----------------------------------------------------------
 * @return string
+----------------------------------------------------------
 */
function build_verify($length = 4, $mode = 1) {
    return rand_string ( $length, $mode );
}


function getGroupName($id) {
    if ($id == 0) {
        return '无上级组';
    }
    if ($list = F ( 'groupName' )) {
        return $list [$id];
    }
    $dao = D ( "Role" );
    $list = $dao->select ( array ('field' => 'id,name' ) );
    foreach ( $list as $vo ) {
        $nameList [$vo ['id']] = $vo ['name'];
    }
    $name = $nameList [$id];
    F ( 'groupName', $nameList );
    return $name;
}
function sort_by($array, $keyname = null, $sortby = 'asc') {
    $myarray = $inarray = array ();
    # First store the keyvalues in a seperate array
    foreach ( $array as $i => $befree ) {
        $myarray [$i] = $array [$i] [$keyname];
    }
    # Sort the new array by
    switch ($sortby) {
        case 'asc' :
            # Sort an array and maintain index association...
            asort ( $myarray );
            break;
        case 'desc' :
        case 'arsort' :
            # Sort an array in reverse order and maintain index association
            arsort ( $myarray );
            break;
        case 'natcasesor' :
            # Sort an array using a case insensitive "natural order" algorithm
            natcasesort ( $myarray );
            break;
    }
    # Rebuild the old array
    foreach ( $myarray as $key => $befree ) {
        $inarray [] = $array [$key];
    }
    return $inarray;
}

/**
+----------------------------------------------------------
 * 产生随机字串，可用来自动生成密码
 * 默认长度6位 字母和数字混合 支持中文
+----------------------------------------------------------
 * @param string $len 长度
 * @param string $type 字串类型
 * 0 字母 1 数字 其它 混合
 * @param string $addChars 额外字符
+----------------------------------------------------------
 * @return string
+----------------------------------------------------------
 */
function rand_string($len = 6, $type = '', $addChars = '') {
    $str = '';
    switch ($type) {
        case 0 :
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' . $addChars;
            break;
        case 1 :
            $chars = str_repeat ( '0123456789', 3 );
            break;
        case 2 :
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' . $addChars;
            break;
        case 3 :
            $chars = 'abcdefghijklmnopqrstuvwxyz' . $addChars;
            break;
        default :
            // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
            $chars = 'ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789' . $addChars;
            break;
    }
    if ($len > 10) { //位数过长重复字符串一定次数
        $chars = $type == 1 ? str_repeat ( $chars, $len ) : str_repeat ( $chars, 5 );
    }
    if ($type != 4) {
        $chars = str_shuffle ( $chars );
        $str = substr ( $chars, 0, $len );
    } else {
        // 中文随机字
        for($i = 0; $i < $len; $i ++) {
            $str .= msubstr ( $chars, floor ( mt_rand ( 0, mb_strlen ( $chars, 'utf-8' ) - 1 ) ), 1 );
        }
    }
    return $str;
}
function pwdHash($password, $type = 'md5') {
    return hash ( $type, $password );
}

/**
 * 创建js公共变量文件
 */
function createJsPublicVar(){
    $jsVar = '
	var jsDomain = "'.DOMAIN.'"; 
	var jsImg = "'.C('IMG_URL').'";
	var jsPublic = "'.WEB_PUBLIC_PATH.'";
	';
    //echo $jsVar;exit;
    file_put_contents('./Public/Js/publicVar.js',$jsVar);
}

function chkSelected($v){
    if($v){
        echo 'checked="checked"';
    }
}

function showImg($v){
    if($v){
        echo '<img src="'.C('IMG_URL').$v.'" width="90" height="100"   />';
    }
}

function sltSelected($v){
    if($v){
        echo 'checked="checked"';
    }
}

//简单加密函数
function s_encrypt($str){
    $encrypt_key = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890./:-';
    $decrypt_key = 'GNZQTCOMBUHELPKDAFWXYIRVJSabcdefghijklmnopqrstuvwxyz3246708159:|#^';

    if (strlen($str) == 0) return false;

    for ($i=0; $i<strlen($str); $i++){
        for ($j=0; $j<strlen($encrypt_key); $j++){
            if ($str[$i] == $encrypt_key[$j]){
                $enstr .= $decrypt_key[$j];
                break;
            }
        }
    }

    return $enstr;
}

//简单解密函数（与php_encrypt函数对应）
function s_decrypt($str){
    $encrypt_key = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890./:-';
    $decrypt_key = 'GNZQTCOMBUHELPKDAFWXYIRVJSabcdefghijklmnopqrstuvwxyz3246708159:|#^';

    if (strlen($str) == 0) return false;

    for ($i=0; $i<strlen($str); $i++){
        for ($j=0; $j<strlen($decrypt_key); $j++){
            if ($str[$i] == $decrypt_key[$j]){
                $enstr .= $encrypt_key[$j];
                break;
            }
        }
    }

    return $enstr;
}
//以下为home所有
function cateTitle($id){
    $c = D('Category');
    return $c->getTitle($id);
}

/*检测用户登录*/
/*function chkUser(){
	if(Cookie::get(C('USER_AUTH_KEY'))){
		return true;
	}
}

function chkCuser(){
	if(Cookie::get('authId') && Cookie::get('utype') == 'unit'){
		return true;
	}
}

function chkPuser(){
	if(Cookie::get('authId') && Cookie::get('utype') == 'person'){
		return true;
	}
}

function chkSuser(){
	if(Cookie::get('authId') && Cookie::get('utype') == 'school'){
		return true;
	}
}*/

/*
 * 临时用户登录验证，读取原来程序cookie
 */
function chkUser(){
    if($_COOKIE['FSQ_uid']){
        return true;
    }
}

function chkCuser(){
    if(chkUser){
        if($_COOKIE['FSQ_utype'] == 'unit'){
            return true;
        }
    }
}

function chkPuser(){
    if($_COOKIE['FSQ_utype'] == 'person'){
        return true;
    }
}

function chkSuser(){
    if($_COOKIE['FSQ_utype'] == 'school'){
        return true;
    }
}

//产生随机数
function createRand(){
    $str=gettimeofday(1).rand();
    return str_replace(".","",$str);
}


//获取子元素
function getChild(&$arr, $pid){
    $child = array();
    foreach($arr as $k => $v){
        if($v['pid'] == $pid){
            $child[] = $v;
            unset($arr[$k]);
        }
    }
    return $child;
}

//字符裁剪 $str, $start=0, $length, $charset="utf-8", $suffix=true
function cutstr($str,$start=0, $length, $charset="utf-8", $suffix=true){
    //echo utf8_strlen($str);
    if(utf8_strlen($str) > $length){
        /*var_dump($str);
        var_dump($start);
        var_dump($length);
        var_dump(utf8_strlen($str));*/
        $str = msubstr($str, $start, $length, $charset, false);
        if($suffix){
            $str.="...";
        }
        //exit('x');
        //return $str;
    }
    return $str;
}

// 计算中文字符串长度
function utf8_strlen($string = null) {
    preg_match_all("/./us", $string, $match);
    return count($match[0]);
    //$str = ‘Hello,中国！’;echo ($zhStr);
    //$zhStr = ‘您好，中国！’;
    // 输出：6
    //echo utf8_strlen($str); // 输出：9
}

function filter_script($str){
    $str = preg_replace( "@<script(.*?)</script>@is", "", $str );
    $str = preg_replace( "@<iframe(.*?)</iframe>@is", "", $str );
    $str = preg_replace( "@<style(.*?)</style>@is", "", $str );
    return $str;
}


function do_post($url, $data) {
    $ch = curl_init ();
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
    curl_setopt ( $ch, CURLOPT_POST, TRUE );
    curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
    curl_setopt ( $ch, CURLOPT_URL, $url );
    $ret = curl_exec ( $ch );

    curl_close ( $ch );
    return $ret;
}



function isMobile2()
{
    if (preg_match("/(ipad)/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
        return false;
    }
    $arr = explode('.', $_SERVER['HTTP_HOST']);
    if($arr[0] == "m") return true;
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    }
    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset($_SERVER['HTTP_VIA'])) {
        // 找不到为flase,否则为true
        return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
    }
    // 脑残法，判断手机发送的客户端标志,兼容性有待提高
    if (isset($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array(
            'nokia',
            'sony',
            'ericsson',
            'mot',
            'samsung',
            'htc',
            'sgh',
            'lg',
            'sharp',
            'sie-',
            'philips',
            'panasonic',
            'alcatel',
            'lenovo',
            'iphone',
            'ipod',
            'blackberry',
            'meizu',
            'android',
            'netfront',
            'symbian',
            'ucweb',
            'windowsce',
            'palm',
            'operamini',
            'operamobi',
            'openwave',
            'nexusone',
            'cldc',
            'midp',
            'wap',
            'mobile'
        );
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    // 协议法，因为有可能不准确，放到最后判断
    if (isset($_SERVER['HTTP_ACCEPT'])) {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;
}



/**
 * 字符串小助手
 *
 * @version        $Id: string.helper.php 5 14:24 2010年7月5日Z tianya $
 * @package        DedeCMS.Helpers
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
//拼音的缓冲数组
$pinyins = Array();

/**
 *  中文截取2，单字节截取模式
 *  如果是request的内容，必须使用这个函数
 *
 * @access    public
 * @param     string  $str  需要截取的字符串
 * @param     int  $slen  截取的长度
 * @param     int  $startdd  开始标记处
 * @return    string
 */
if ( ! function_exists('cn_substrR'))
{
    function cn_substrR($str, $slen, $startdd=0)
    {
        $str = cn_substr(stripslashes($str), $slen, $startdd);
        return addslashes($str);
    }
}

/**
 *  中文截取2，单字节截取模式
 *
 * @access    public
 * @param     string  $str  需要截取的字符串
 * @param     int  $slen  截取的长度
 * @param     int  $startdd  开始标记处
 * @return    string
 */
if ( ! function_exists('cn_substr'))
{
    function cn_substr($str, $slen, $startdd=0)
    {
        global $cfg_soft_lang;
        if($cfg_soft_lang=='utf-8')
        {
            return cn_substr_utf8($str, $slen, $startdd);
        }
        $restr = '';
        $c = '';
        $str_len = strlen($str);
        if($str_len < $startdd+1)
        {
            return '';
        }
        if($str_len < $startdd + $slen || $slen==0)
        {
            $slen = $str_len - $startdd;
        }
        $enddd = $startdd + $slen - 1;
        for($i=0;$i<$str_len;$i++)
        {
            if($startdd==0)
            {
                $restr .= $c;
            }
            else if($i > $startdd)
            {
                $restr .= $c;
            }

            if(ord($str[$i])>0x80)
            {
                if($str_len>$i+1)
                {
                    $c = $str[$i].$str[$i+1];
                }
                $i++;
            }
            else
            {
                $c = $str[$i];
            }

            if($i >= $enddd)
            {
                if(strlen($restr)+strlen($c)>$slen)
                {
                    break;
                }
                else
                {
                    $restr .= $c;
                    break;
                }
            }
        }
        return $restr;
    }
}

/**
 *  utf-8中文截取，单字节截取模式
 *
 * @access    public
 * @param     string  $str  需要截取的字符串
 * @param     int  $slen  截取的长度
 * @param     int  $startdd  开始标记处
 * @return    string
 */
if ( ! function_exists('cn_substr_utf8'))
{
    function cn_substr_utf8($str, $length, $start=0)
    {
        if(strlen($str) < $start+1)
        {
            return '';
        }
        preg_match_all("/./su", $str, $ar);
        $str = '';
        $tstr = '';

        //为了兼容mysql4.1以下版本,与数据库varchar一致,这里使用按字节截取
        for($i=0; isset($ar[0][$i]); $i++)
        {
            if(strlen($tstr) < $start)
            {
                $tstr .= $ar[0][$i];
            }
            else
            {
                if(strlen($str) < $length + strlen($ar[0][$i]) )
                {
                    $str .= $ar[0][$i];
                }
                else
                {
                    break;
                }
            }
        }
        return $str;
    }
}

/**
 *  HTML转换为文本
 *
 * @param    string  $str 需要转换的字符串
 * @param    string  $r   如果$r=0直接返回内容,否则需要使用反斜线引用字符串
 * @return   string
 */
if ( ! function_exists('Html2Text'))
{
    function Html2Text($str,$r=0)
    {
        if(!function_exists('SpHtml2Text'))
        {
            require_once(DEDEINC."/inc/inc_fun_funString.php");
        }
        if($r==0)
        {
            return SpHtml2Text($str);
        }
        else
        {
            $str = SpHtml2Text(stripslashes($str));
            return addslashes($str);
        }
    }
}


/**
 *  文本转HTML
 *
 * @param    string  $txt 需要转换的文本内容
 * @return   string
 */
if ( ! function_exists('Text2Html'))
{
    function Text2Html($txt)
    {
        $txt = str_replace("  ", "　", $txt);
        $txt = str_replace("<", "&lt;", $txt);
        $txt = str_replace(">", "&gt;", $txt);
        $txt = preg_replace("/[\r\n]{1,}/isU", "<br/>\r\n", $txt);
        return $txt;
    }
}

/**
 *  获取半角字符
 *
 * @param     string  $fnum  数字字符串
 * @return    string
 */
if ( ! function_exists('GetAlabNum'))
{
    function GetAlabNum($fnum)
    {
        $nums = array("０","１","２","３","４","５","６","７","８","９");
        //$fnums = "0123456789";
        $fnums = array("0","1","2","3","4","5","6","7","8","9");
        $fnum = str_replace($nums, $fnums, $fnum);
        $fnum = preg_replace("/[^0-9\.-]/", '', $fnum);
        if($fnum=='')
        {
            $fnum=0;
        }
        return $fnum;
    }
}

/**
 *  获取拼音以gbk编码为准
 *
 * @access    public
 * @param     string  $str     字符串信息
 * @param     int     $ishead  是否取头字母
 * @param     int     $isclose 是否关闭字符串资源
 * @return    string
 */
if ( ! function_exists('GetPinyin'))
{
    function GetPinyin($str, $ishead=0, $isclose=1)
    {
        global $cfg_soft_lang;
        if(!function_exists('SpGetPinyin'))
        {
            //全局函数仅是inc_fun_funAdmin.php文件中函数的一个映射
            require_once(DEDEINC."/inc/inc_fun_funAdmin.php");
        }
        if($cfg_soft_lang=='utf-8')
        {
            return SpGetPinyin(utf82gb($str), $ishead, $isclose);
        }
        else
        {
            return SpGetPinyin($str, $ishead, $isclose);
        }
    }
}
/**
 *  将实体html代码转换成标准html代码（兼容php4）
 *
 * @access    public
 * @param     string  $str     字符串信息
 * @param     long    $options  替换的字符集
 * @return    string
 */

if ( ! function_exists('htmlspecialchars_decode'))
{
    function htmlspecialchars_decode($str, $options=ENT_COMPAT) {
        $trans = get_html_translation_table(HTML_SPECIALCHARS, $options);

        $decode = ARRAY();
        foreach ($trans AS $char=>$entity) {
            $decode[$entity] = $char;
        }

        $str = strtr($str, $decode);

        return $str;
    }
}

if ( ! function_exists('ubb'))
{
    function ubb($Text) {
        $Text=trim($Text);
        //$Text=htmlspecialchars($Text);
        //$Text=ereg_replace("\n","<br>",$Text);
        $Text=preg_replace("/\\t/is","  ",$Text);
        $Text=preg_replace("/\[hr\]/is","<hr>",$Text);
        $Text=preg_replace("/\[separator\]/is","<br/>",$Text);
        $Text=preg_replace("/\[h1\](.+?)\[\/h1\]/is","<h1>\\1</h1>",$Text);
        $Text=preg_replace("/\[h2\](.+?)\[\/h2\]/is","<h2>\\1</h2>",$Text);
        $Text=preg_replace("/\[h3\](.+?)\[\/h3\]/is","<h3>\\1</h3>",$Text);
        $Text=preg_replace("/\[h4\](.+?)\[\/h4\]/is","<h4>\\1</h4>",$Text);
        $Text=preg_replace("/\[h5\](.+?)\[\/h5\]/is","<h5>\\1</h5>",$Text);
        $Text=preg_replace("/\[h6\](.+?)\[\/h6\]/is","<h6>\\1</h6>",$Text);
        $Text=preg_replace("/\[center\](.+?)\[\/center\]/is","<center>\\1</center>",$Text);
        //$Text=preg_replace("/\[url=([^\[]*)\](.+?)\[\/url\]/is","<a href=\\1 target='_blank'>\\2</a>",$Text);
        $Text=preg_replace("/\[url\](.+?)\[\/url\]/is","<a href=\"\\1\" target='_blank'>\\1</a>",$Text);
        $Text=preg_replace("/\[url=(http:\/\/.+?)\](.+?)\[\/url\]/is","<a href='\\1' target='_blank'>\\2</a>",$Text);
        $Text=preg_replace("/\[url=(.+?)\](.+?)\[\/url\]/is","<a href=\\1>\\2</a>",$Text);
        $Text=preg_replace("/\[img\](.+?)\[\/img\]/is","<img src=\\1>",$Text);
        $Text=preg_replace("/\[img\s(.+?)\](.+?)\[\/img\]/is","<img \\1 src=\\2>",$Text);
        $Text=preg_replace("/\[color=(.+?)\](.+?)\[\/color\]/is","<font color=\\1>\\2</font>",$Text);
        $Text=preg_replace("/\[colorTxt\](.+?)\[\/colorTxt\]/eis","color_txt('\\1')",$Text);
        $Text=preg_replace("/\[style=(.+?)\](.+?)\[\/style\]/is","<div class='\\1'>\\2</div>",$Text);
        $Text=preg_replace("/\[size=(.+?)\](.+?)\[\/size\]/is","<font size=\\1>\\2</font>",$Text);
        $Text=preg_replace("/\[sup\](.+?)\[\/sup\]/is","<sup>\\1</sup>",$Text);
        $Text=preg_replace("/\[sub\](.+?)\[\/sub\]/is","<sub>\\1</sub>",$Text);
        $Text=preg_replace("/\[pre\](.+?)\[\/pre\]/is","<pre>\\1</pre>",$Text);
        $Text=preg_replace("/\[emot\](.+?)\[\/emot\]/eis","emot('\\1')",$Text);
        $Text=preg_replace("/\[email\](.+?)\[\/email\]/is","<a href='mailto:\\1'>\\1</a>",$Text);
        $Text=preg_replace("/\[i\](.+?)\[\/i\]/is","<i>\\1</i>",$Text);
        $Text=preg_replace("/\[u\](.+?)\[\/u\]/is","<u>\\1</u>",$Text);
        $Text=preg_replace("/\[b\](.+?)\[\/b\]/is","<b>\\1</b>",$Text);
        $Text=preg_replace("/\[quote\](.+?)\[\/quote\]/is","<blockquote>引用:<div style='border:1px solid silver;background:#EFFFDF;color:#393939;padding:5px' >\\1</div></blockquote>", $Text);
        $Text=preg_replace("/\[sig\](.+?)\[\/sig\]/is","<div style='text-align: left; color: darkgreen; margin-left: 5%'><br><br>--------------------------<br>\\1<br>--------------------------</div>", $Text);
        return $Text;
    }
}


function SpHtml2Text($str)
{
    $str = preg_replace("/<sty(.*)\\/style>|<scr(.*)\\/script>|<!--(.*)-->/isU","",$str);
    $alltext = "";
    $start = 1;
    for($i=0;$i<strlen($str);$i++)
    {
        if($start==0 && $str[$i]==">")
        {
            $start = 1;
        }
        else if($start==1)
        {
            if($str[$i]=="<")
            {
                $start = 0;
                $alltext .= " ";
            }
            else if(ord($str[$i])>31)
            {
                $alltext .= $str[$i];
            }
        }
    }
    $alltext = str_replace("　"," ",$alltext);
    $alltext = preg_replace("/&([^;&]*)(;|&)/","",$alltext);
    $alltext = preg_replace("/[ ]+/s"," ",$alltext);
    return $alltext;
}




//浏览器判断
function getBrowser(){
    $agent=$_SERVER["HTTP_USER_AGENT"];
    if(strpos($agent,'MSIE')!==false || strpos($agent,'rv:11.0')) //ie11判断
        return "ie";
    else if(strpos($agent,'Firefox')!==false)
        return "firefox";
    else if(strpos($agent,'Chrome')!==false)
        return "chrome";
    else if(strpos($agent,'Opera')!==false)
        return 'opera';
    else if((strpos($agent,'Chrome')==false)&&strpos($agent,'Safari')!==false)
        return 'safari';
    else
        return 'unknown';
}

//浏览器版本
function getBrowserVer(){
    if (empty($_SERVER['HTTP_USER_AGENT'])){    //当浏览器没有发送访问者的信息的时候
        return 'unknow';
    }
    $agent= $_SERVER['HTTP_USER_AGENT'];
    if (preg_match('/MSIE\s(\d+)\..*/i', $agent, $regs))
        return $regs[1];
    elseif (preg_match('/FireFox\/(\d+)\..*/i', $agent, $regs))
        return $regs[1];
    elseif (preg_match('/Opera[\s|\/](\d+)\..*/i', $agent, $regs))
        return $regs[1];
    elseif (preg_match('/Chrome\/(\d+)\..*/i', $agent, $regs))
        return $regs[1];
    elseif ((strpos($agent,'Chrome')==false)&&preg_match('/Safari\/(\d+)\..*$/i', $agent, $regs))
        return $regs[1];
    else
        return 'unknow';
}



/**
 *  文档自动分页
 *
 * @access    public
 * @param     string  $mybody  内容
 * @param     string  $spsize  分页大小
 * @param     string  $sptag  分页标记
 * @return    string
 */
function SpLongBody($mybody, $spsize, $sptag)
{
    if(strlen($mybody) < $spsize)
    {
        return $mybody;
    }
    $mybody = stripslashes($mybody);
    $bds = explode('<', $mybody);
    $npageBody = '';
    $istable = 0;
    $mybody = '';
    foreach($bds as $i=>$k)
    {
        if($i==0)
        {
            $npageBody .= $bds[$i]; continue;
        }
        $bds[$i] = "<".$bds[$i];
        if(strlen($bds[$i])>6)
        {
            $tname = substr($bds[$i],1,5);
            if(strtolower($tname)=='table')
            {
                $istable++;
            }
            else if(strtolower($tname)=='/tabl')
            {
                $istable--;
            }
            if($istable>0)
            {
                $npageBody .= $bds[$i]; continue;
            }
            else
            {
                $npageBody .= $bds[$i];
            }
        }
        else
        {
            $npageBody .= $bds[$i];
        }
        if(strlen($npageBody)>$spsize)
        {
            $mybody .= $npageBody.$sptag;
            $npageBody = '';
        }
    }
    if($npageBody!='')
    {
        $mybody .= $npageBody;
    }
    return addslashes($mybody);
}

function ShowLongBody($mybody, $spsize)
{
    $bodylen = strlen($mybody);
    $reckonPageNum = $bodylen / $spsize; //预估能分多少而，如果第一次explode页数小于此页，则进行下一个explode
    $reckonPageNum  = 2;
    if($bodylen < $spsize)
    {
        return array($mybody);
    }

    $arr = splitBody($mybody,'',$reckonPageNum);

    /*	$spliter = '</p>';
     $arr = explode($spliter,$mybody);

     if(count($arr) < $reckonPageNum){
        $spliter = '<br />';
        $arr = explode($spliter,$mybody);
        if(count($arr) < $reckonPageNum){
        $spliter = '<br>';
        $arr = explode($spliter,$mybody);
        }
        }
    */
    $temp = '';
    $c = count($arr);
    for($i = 0; $i < $c; $i+=2){
        if($i != $c-1)
            $temp .= $arr[$i].$arr[$i+1];
        //var_dump($temp);
        //var_dump($arr[)
        if(strlen($temp) >= $spsize){
            $pageContent[] = $temp;
            $temp = '';

        }
    }
    if(!empty($temp)){ //末尾不足分页大小的部分
        $pageContent[] = $temp;
    }
    //print_r($pageContent);exit;
    return $pageContent;
}

function splitBody($str,$spliter,$reckonPageNum){
    $r = preg_split('/(<\/p>|<br>|<br \/>|<\/div>)/',$str,-1,PREG_SPLIT_DELIM_CAPTURE);
    return $r;

    print_r($r);exit('---------');
    //先查找分隔符数量，然后选取最接近预估数的作为分隔符 ,如果每个分隔块与分块大小之差大于50%,则需两次分割
    $spliters = array('</p>', '<br>','<br />', '</div>');
    $spliter_nums = array();
    foreach($spliters as $v){
        //echo $v;
        $spliter_nums[] = substr_count($str,$v);
    }
    var_dump($spliter_nums);exit;

    $spliter = '</p>';
    $pnum = substr_count($str,$spliter);

    $spliter = '<br />';
    $bnum = substr_count($str,$spliter);

    $spliter = '<br>';
    $b2num = substr_count($str,$spliter);

    $spliter = '</div>';
    $dnum = substr_count($str,$spliter);

    max(array($pnum,$b2num,$bnum,$dnum));

}


/**
 *  文档自动分页
 *
 * @access    public
 * @param     string  $mybody  内容
 * @param     string  $spsize  分页大小
 * @param     string  $sptag  分页标记
 * @return    string
 */
function ShowLongBodyxxxxx($mybody, $spsize, $sptag)
{
    if(strlen($mybody) < $spsize)
    {
        return $mybody;
    }
    $mybody = stripslashes($mybody);
    $bds = explode('</', $mybody);
    $npageBody = '';
    $istable = 0;
    $mybody = '';
    $pageContent = array();
    foreach($bds as $i=>$k)
    {
        if($i==0)
        {
            $npageBody .= $bds[$i]; continue;
        }
        $bds[$i] = "</".$bds[$i];
        if(strlen($bds[$i])>6)
        {
            $tname = substr($bds[$i],1,5);
            if(strtolower($tname)=='table')
            {
                $istable++;
            }
            else if(strtolower($tname)=='/tabl')
            {
                $istable--;
            }
            if($istable>0)
            {
                $npageBody .= $bds[$i]; continue;
            }
            else
            {
                $npageBody .= $bds[$i];
            }
        }
        else
        {
            $npageBody .= $bds[$i];
        }
        if(strlen($npageBody)>$spsize)
        {
            $pageContent[] = $npageBody.$sptag;
            $npageBody = '';
        }
    }
    if($npageBody!='')
    {
        $mybody .= $npageBody;
    }

    print_r($pageContent);
    return $pageContent;
    return addslashes($mybody);
}

/**
 * 从数组中取指定字段
 * @param array $data 数据
 * @param array $field  字段数组
 * @return multitype:array
 */
function field($data,$field){
    $ret = array();
    foreach ($field as $v){
        $ret[$v] = $data[$v];
    }
    return $ret;
}

function member_thumb(&$r){
    $defaultImg = $r['sex'] ? C('IMG_SEX_1') : C('IMG_SEX_0');
    $r['thumb'] = empty($r['thumb']) ? $defaultImg :URL_IMG.'/'.$r['thumb'];
}

function thumb_privew($thumb){
    return empty($thumb) ? C('DEFAULT_AVATAR') : URL_IMG.'/'.$thumb;
}

//阿拉伯数字转中文数字
function ToChinaseNum($num)
{
    $char = array("零","一","二","三","四","五","六","七","八","九");
    $dw = array("","十","百","千","万","亿","兆");
    $retval = "";
    $proZero = false;
    for($i = 0;$i < strlen($num);$i++)
    {
        if($i > 0)    $temp = (int)(($num % pow (10,$i+1)) / pow (10,$i));
        else $temp = (int)($num % pow (10,1));

        if($proZero == true && $temp == 0) continue;

        if($temp == 0) $proZero = true;
        else $proZero = false;

        if($proZero)
        {
            if($retval == "") continue;
            $retval = $char[$temp].$retval;
        }
        else $retval = $char[$temp].$dw[$i].$retval;
    }
    if($retval == "一十") $retval = "十";
    return $retval;
}

//从时间中取出日期
function datetimeToDate($datetime){
    return explode(' ',$datetime)[0];
}

function getCateTitle($id){
    // return "bbbb";
    return  M('Category')->find($id)['title'];
}


//获取上传文件信息
function getuploadinfo($name){
    $upload = new Think\Upload();
    $upload->rootPath  =     'uploads/';
    $upload->savePath  =     '';
    $upload->saveName = array('uniqid','');
    $upload->exts     = array('jpg', 'gif', 'png', 'jpeg');
    $upload->autoSub  = true;
    $upload->subName  = array('date','Ymd');
    $uploadinfo = $upload -> upload();
    if(!$uploadinfo){
        $datainfo = array('errorno'=>0,'errormsg'=>$upload -> getError());
    }else{
        foreach($uploadinfo as $k => $v){
            $v['path'] = $upload->rootPath.$v['savepath'].$v['savename'];
            $arr[] = $v;
        }
        $datainfo = array('errorno'=>1,$name=>$arr);
    }
    return $datainfo;
}



if (!function_exists('getallheaders')){
    function getallheaders($raw=false) {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$key] = $value;
            }
        }
        if($raw){
            $str = "";
            foreach ($headers as $k => $v){
                $str .= "$k: $v\r\n";
            }
            return $str;
        }
        return $headers;
    }
}



function pinyinToCN($pinyin){

    $r = M('surname')->where(['pinyin' => $pinyin])->find();//getByPinyi($pinyin);

    if(empty($r)) return "曹";
    return $r['surname'];
}


