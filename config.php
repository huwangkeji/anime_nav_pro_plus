<?php
/**
 * =============================================
 * config.php - 统一配置/数据加载模块
 * 所有 PHP 文件通过 require_once 引入此文件
 * 避免重复代码，确保数据一致性
 * =============================================
 */

/* ----- 文件路径常量 ----- */
define('DATA_FILE',    dirname(__FILE__).'/data.json');       // 导航链接数据
define('CONFIG_FILE',  dirname(__FILE__).'/config.json');     // 站点全局配置
define('CAT_ICON_FILE',dirname(__FILE__).'/cat_icons.json'); // 分类图标映射
define('CAT_SORT_FILE',dirname(__FILE__).'/cat_sort.json'); // 分类ID排序

/**
 * 安全读取 JSON 文件
 * @param string $file 文件路径
 * @param array $default 解析失败时的默认值
 * @return array 解析后的数组
 */
function load_json($file, $default=array()){
    if(!file_exists($file)) return $default;
    $raw = file_get_contents($file);
    if($raw===false) return $default;
    $arr = json_decode($raw, true);
    return is_array($arr) ? $arr : $default;
}

/**
 * 安全写入 JSON 文件（带格式化）
 * @param string $file 文件路径
 * @param mixed $data 要写入的数据
 * @return bool 是否成功
 */
function save_json($file, $data){
    return (bool)file_put_contents(
        $file,
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
    );
}

// ====== 加载四大数据源 ======
$SITE_DATA    = load_json(DATA_FILE);     // 导航分类+链接
$SITE_CONFIG  = load_json(CONFIG_FILE);   // 站点设置
$CAT_ICONS    = load_json(CAT_ICON_FILE); // 分类图标
$CAT_SORT     = load_json(CAT_SORT_FILE); // 分类排序ID

/**
 * 获取站点配置项（带默认值）
 * @param string $key    配置键名
 * @param string $def    默认值
 * @return string 配置值
 */
function cfg($key, $def=''){
    global $SITE_CONFIG;
    return isset($SITE_CONFIG[$key]) ? trim($SITE_CONFIG[$key]) : $def;
}

/**
 * HTML安全输出
 * @param string $str 原始字符串
 * @return string 转义后字符串
 */
function e($str){
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
