<?php
/**
 * =============================================
 * index.php - 前端首页
 * 彩虹炫彩玻璃UI导航站
 * 依赖: config.php (统一配置加载)
 * =============================================
 */

// 加载统一配置模块
require_once dirname(__FILE__).'/config.php';

/* ================================================================
 *   分类颜色预设表（每类独立的彩虹配色方案）
 *   新增分类不在此表中时自动使用默认紫色系
 *   图标优先从 cat_icons.json 读取（后台可自定义）
 * ================================================================ */
$CAT_COLORS = array(
    'featured' => array(
        'icon' => '🔥',   'c1' => '#ff6b6b', 'c2' => '#feca57',
        'c3' => '#ff9ff3', 'glow' => 'rgba(255,107,107,0.2)'
    ),
    'AI工具' => array(
        'icon' => '🤖',   'c1' => '#48dbfb', 'c2' => '#0abde3',
        'c3' => '#10ac84', 'glow' => 'rgba(72,219,251,0.2)'
    ),
    '开发工具' => array(
        'icon' => '💻',   'c1' => '#a29bfe', 'c2' => '#6c5ce7',
        'c3' => '#fd79a8', 'glow' => 'rgba(162,155,254,0.2)'
    ),
    '实用工具' => array(
        'icon' => '🛠️',  'c1' => '#00d2d3', 'c2' => '#01a3a4',
        'c3' => '#48dbfb', 'glow' => 'rgba(0,210,211,0.2)'
    ),
    '娱乐专区' => array(
        'icon' => '🎮',   'c1' => '#ff9ff3', 'c2' => '#f368e0',
        'c3' => '#ff6b6b', 'glow' => 'rgba(255,159,243,0.2)'
    ),
    '学习资源' => array(
        'icon' => '📚',   'c1' => '#feca57', 'c2' => '#ff9f43',
        'c3' => '#ee5a24', 'glow' => 'rgba(254,202,87,0.2)'
    ),
    '设计素材' => array(
        'icon' => '🎨',   'c1' => '#ff6b81', 'c2' => '#ee5a52',
        'c3' => '#f8b500', 'glow' => 'rgba(255,107,129,0.2)'
    ),
    '影视动漫' => array(
        'icon' => '🎬',   'c1' => '#e056fd', 'c2' => '#be2edd',
        'c3' => '#686de0', 'glow' => 'rgba(224,86,253,0.2)'
    ),
);

// 默认颜色（未预设的分类使用）
$DEF_ICON = '📌';
$DEF_C1   = '#5f27cd';
$DEF_C2   = '#341f97';
$DEF_C3   = '#222f3e';
$DEF_GLOW = 'rgba(95,39,205,0.15)';

/**
 * 获取分类的完整样式配置（合并图标+颜色）
 * @param string $cat_name 分类名
 * @return array icon/c1/c2/c3/glow
 */
function get_cat_style($cat_name){
    global $CAT_COLORS, $CAT_ICONS, $DEF_ICON, $DEF_C1, $DEF_C2, $DEF_C3, $DEF_GLOW;

    // 1) 取图标：优先 cat_icons.json > 颜色表 > 默认
    if(isset($CAT_ICONS[$cat_name]))       { $icon = $CAT_ICONS[$cat_name]; }
    elseif(isset($CAT_COLORS[$cat_name]))  { $icon = $CAT_COLORS[$cat_name]['icon']; }
    else                                   { $icon = $DEF_ICON; }

    // 2) 取颜色
    if(isset($CAT_COLORS[$cat_name])){
        $r = $CAT_COLORS[$cat_name];
    }else{
        $r = array('icon'=>$icon,'c1'=>$DEF_C1,'c2'=>$DEF_C2,'c3'=>$DEF_C3,'glow'=>$DEF_GLOW);
    }

    // 3) 用最终确定的图标覆盖
    $r['icon'] = $icon;
    return $r;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">

<!-- ====== SEO Meta（全部后台可配置）====== -->
<title><?= e(cfg('title','导航')) ?></title>
<meta name="description" content="<?= e(cfg('title','')) ?> - 精选网址导航">
<meta name="keywords" content="<?= e(cfg('keywords','')) ?>">

<!-- 样式 -->
<link rel="stylesheet" href="style.css">

<?php /* 自定义背景图片 */ ?>
<?php if(!empty(trim(cfg('background','')))): ?>
<style>.bg-canvas{background:url('<?= e(cfg('background')) ?>') center/cover fixed no-repeat!important;}</style>
<?php endif; ?>

<?php /* 后台填写的自定义CSS注入到head */ ?>
<?php if(!empty(trim(cfg('custom_css','')))): ?>
<style type="text/css"><?= trim($SITE_CONFIG['custom_css']) ?></style>
<?php endif; ?>

</head>
<body>

<div class="bg-canvas"></div>     <!-- 动态渐变背景 -->
<div class="overlay">            <!-- 内容覆盖层 -->

    <!-- ========== 页面标题区（标题+副标题均后台可改）========== -->
    <div class="site-header">
        <h1 class="site-title"><?= e(cfg('title','个人在线网址合集')) ?></h1>
        <p class="site-subtitle"><?= e(cfg('subtitle','✦ 精选网址导航 · 一站直达 ✦')) ?></p>
    </div>

    <!-- ========== Bing风格搜索框（Bing / Google 双引擎）========== -->
    <div class="bing-search-wrap">
        <form class="bing-search-form" onsubmit="return doSearch(event)">
            <div class="bing-input-wrap">
                <svg class="bing-icon" viewBox="0 0 24 24" fill="none"
                     stroke="#888" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35"/>
                </svg>
                <input type="text" id="q" autocomplete="off"
                       placeholder="搜索互联网..." autofocus>
            </div>
            <input type="submit" value="" style="display:none">
            <div class="engine-switch">
                <button type="button" class="eng-btn active" data-e="bing"
                        onclick="swEng(this)">Bing</button>
                <span class="eng-divider"></span>
                <button type="button" class="eng-btn" data-e="google"
                        onclick="swEng(this)">Google</button>
            </div>
        </form>
    </div>

    <!-- 搜索提示文字 -->
    <div class="quick-tips">
        <span>按 Enter 直接搜索</span>
        <span class="qt-dot">·</span>
        <span>Bing / Google 双引擎</span>
    </div>


    <!-- ==================== 推荐位 Featured（金色HOT角标）==================== -->
    <?php if(!empty($SITE_DATA['featured'])):
        $cs = $CAT_COLORS['featured'];  // 推荐位专用样式
    ?>
    <div class="sec-head">
        <i class="sec-icon"><?= $cs['icon'] ?></i>
        <span class="sec-title"
              style="--a:<?= $cs['c1'] ?>;--b:<?= $cs['c2'] ?>">
            推荐位 / Featured
        </span>
        <div class="sec-line" style="--a:<?= $cs['c1'] ?>"></div>
    </div>
    <div class="g-feat">
        <?php foreach($SITE_DATA['featured'] as $k => $v): ?>
        <div class="card feat"
             style="--c1:<?= $cs['c1'] ?>;--c2:<?= $cs['c2'] ?>;
                    --c3:<?= $cs['c3'] ?>;--gl:<?= $cs['glow'] ?>;--hv:<?= $cs['c2'] ?>">
            <span class="badge-hot">HOT</span>
            <a href="click.php?cat=featured&id=<?= $k ?>"
               target="_blank"><?= e($v['name']) ?></a>
            <small><?= intval($v['click']) ?> 次</small>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>


    <!-- ==================== 普通分类循环展示 ====================
         - featured 已在上面单独处理，这里跳过
         - 空分类也会显示（带提示文案）
         - 按 cat_sort.json 的ID从小到大排序
         - 每个分类从 cat_colors 取色 + cat_icons.json 取图标
    ================================================================== -->
    <?php
        /* ====== 按 CAT_SORT 的ID值从小到大排序（与后台一致）====== */
        $sorted_cats = array_keys($SITE_DATA);
        usort($sorted_cats, function($a, $b) use ($CAT_SORT){
            $va = isset($CAT_SORT[$a]) ? intval($CAT_SORT[$a]) : 999;
            $vb = isset($CAT_SORT[$b]) ? intval($CAT_SORT[$b]) : 999;
            return $va - $vb;
        });

        foreach($sorted_cats as $cat):
        if($cat === 'featured') continue;  // 跳过推荐位

        $items = $SITE_DATA[$cat];
        $s = get_cat_style($cat);
        $slug = preg_replace('/[^\w\x{4e00}-\x{9fff}]/u', '', $cat);
    ?>
    <div class="sec-head">
        <i class="sec-icon"><?= $s['icon'] ?></i>
        <span class="sec-title"
              style="--a:<?= $s['c1'] ?>;--b:<?= $s['c2'] ?>">
            <?= e($cat) ?>
        </span>
        <div class="sec-line" style="--a:<?= $s['c1'] ?>"></div>
    </div>

    <?php if(!empty($items)): ?>
    <div class="g-nav" id="s-<?= $slug ?>">
        <?php foreach($items as $k => $v): ?>
        <div class="card"
             style="--c1:<?= $s['c1'] ?>;--c2:<?= $s['c2'] ?>;
                    --c3:<?= $s['c3'] ?>;--gl:<?= $s['glow'] ?>;--hv:<?= $s['c2'] ?>">
            <a href="click.php?cat=<?= urlencode($cat) ?>&id=<?= $k ?>"
               target="_blank"><?= e($v['name']) ?></a>
            <small><?= intval($v['click']) ?> 次</small>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else:
        /* 空分类占位提示 */
    ?>
    <div class="g-nav">
        <div class="empty-cat-hint">暂无内容，敬请期待 🎉</div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>


    <!-- ========== 底部页脚（4行均支持HTML代码）========== -->
    <footer class="site-footer">

        <!-- 第1行：版权信息（支持HTML） -->
        <?php $f1 = cfg('footer_line1', '');
              if($f1 !== ''): ?>
        <p class="fr"><?= $f1 ?></p>
        <?php else: ?>
        <p class="fr">&copy; <?= date('Y') ?> <?= e(cfg('title','')) ?></p>
        <?php endif; ?>

        <!-- 第2行：ICP备案号（支持HTML / 纯文本自动加链接） -->
        <?php $icp  = cfg('icp','');
              $icpu = cfg('icp_url','');
              if($icp !== ''): ?>
        <?php   /* 如果ICP内容中已经包含 <a 标签则直接原样输出 */ ?>
        <?php   if(stripos($icp, '<a') !== false): ?>
        <p class="fr"><?= $icp ?></p>
        <?php   elseif($icpu !== ''): ?>
        <p class="fr"><a href="<?= e($icpu) ?>" target="_blank"
                         rel="nofollow" class="fl"><?= e($icp) ?></a></p>
        <?php   else: ?>
        <p class="fr"><?= e($icp) ?></p>
        <?php   endif; ?>
        <?php endif; ?>

        <!-- 第3行：Powered 信息（支持HTML） -->
        <?php $f3 = cfg('footer_line3', ''); ?>
        <?php if($f3 !== ''): ?>
        <p class="fr fr-sm"><?= $f3 ?></p>
        <?php else: ?>
        <p class="fr fr-sm">Powered with &hearts;</p>
        <?php endif; ?>

        <!-- 第4行：额外自定义行（支持HTML） -->
        <?php $f4 = cfg('footer_line4', ''); ?>
        <?php if($f4 !== ''): ?>
        <p class="fr fr-sm"><?= $f4 ?></p>
        <?php endif; ?>

    </footer>


    <!-- ========== 自定义HTML代码（插入</body>前）========== -->
    <?php if(!empty(trim(cfg('custom_html','')))):
          echo trim($SITE_CONFIG['custom_html']);
      endif; ?>

</div><!-- /.overlay -->

<script src="app.js"></script>
<script>
/** 搜索引擎切换 */
var _eng = 'bing';
function swEng(btn){
    _eng = btn.getAttribute('data-e');
    var bs = document.querySelectorAll('.eng-btn');
    for(var i=0;i<bs.length;i++) bs[i].className = 'eng-btn';
    btn.className = 'eng-btn active';
}

/** 执行搜索 */
function doSearch(ev){
    ev.preventDefault();
    var q = document.getElementById('q').value.trim();
    if(!q) return;
    window.open(
        _eng === 'bing'
            ? 'https://www.bing.com/search?q=' + encodeURIComponent(q)
            : 'https://www.google.com/search?q=' + encodeURIComponent(q),
        '_blank'
    );
}
</script>

</body></html>
