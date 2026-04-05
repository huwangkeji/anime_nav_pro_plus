<?php
/**
 * =============================================
 * admin.php - 后台管理中心 v6.0
 *
 * 【功能特性】
 *  1. 登录成功/失败 → 弹窗提示（3秒自动消失）
 *  2. 忘记密码弹窗 → 显示重置方式
 *  3. 所有保存/添加/修改操作 → Toast成功提示（3秒）
 *  4. 每个表单独立 action name，互不覆盖字段
 *
 * 【Action 清单】
 *   login       → 登录验证
 *   add_link    → 添加导航链接
 *   add_cat     → 添加新分类+图标
 *   update_icon → 更新分类图标
 *   save_site   → 保存站点基本设置（仅4字段）
 *   save_footer → 保存底部页脚设置（仅5字段）
 *   save_code   → 保存自定义代码（仅2字段）
 *
 * 依赖: config.php (统一配置加载)
 * =============================================
 */

/* ====== 加载统一配置模块 ====== */
require_once dirname(__FILE__).'/config.php';

session_start();
$ADMIN_PASSWORD = 'admin123';  // ← 可修改后台密码

/**
 * ============================================================
 *   消息队列机制：
 *   PHP端把需要显示的消息写入 $_SESSION['admin_msgs'] 数组
 *   页面底部 JS 读取并逐条弹出 Toast
 * ============================================================
 */
function push_msg($text, $type='success'){
    /* $type: success / error / info */
    if(!isset($_SESSION['admin_msgs'])) $_SESSION['admin_msgs']=array();
    $_SESSION['admin_msgs'][] = array('text'=>$text, 'type'=>$type);
}

/* ============================================================
 *   登录处理（带弹窗反馈）
 * ============================================================ */
$login_result = null;  // null=未提交, ok=成功, fail=失败
if(isset($_POST['login'])){
    if($_POST['pwd'] === $ADMIN_PASSWORD){
        $_SESSION['admin_ok'] = 1;
        /* 登录成功 → 存消息到session，不跳转，直接渲染管理界面+弹Toast */
        push_msg('✅ 登录成功！欢迎回来');
        $login_result = 'ok';
    }else{
        $login_result = 'fail';
    }
}
if(isset($_GET['logout'])){
    session_destroy();
    header('Location: admin.php');
    exit;
}

/* ============================================================
 *   【Action X】生成静态首页
 *   将 index.php 的输出渲染为 index.html 静态文件
 * ============================================================ */
if(isset($_GET['gen_html']) && isset($_SESSION['admin_ok'])){
    $indexUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
              . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/index.php';
    $html = @file_get_contents($indexUrl);
    if($html !== false && $html !== ''){
        $staticFile = dirname(__FILE__) . '/index.html';
        $result = @file_put_contents($staticFile, $html);
        if($result !== false){
            push_msg('✅ 静态首页 index.html 生成成功！(' . number_format($result/1024, 1) . ' KB)');
        }else{
            push_msg('⚠️ 写入 index.html 失败，请检查目录权限','error');
        }
    }else{
        push_msg('⚠️ 获取 index.php 内容失败，请确保首页可访问','error');
    }
}


/* ====== 未登录且非登录成功时显示登录框 ====== */
if(!isset($_SESSION['admin_ok']) && $login_result !== 'ok'):
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>后台登录</title>
<link rel="stylesheet" href="style.css">
</head>
<body style="background:#0a0a1a">

<!-- ========== 登录页面 ========== -->
<div class="login">
<form method="post" id="loginForm">
<h2>🔐 后台登录</h2>

<!-- 密码输入 -->
<input name="pwd" type="password" placeholder="请输入管理员密码"
       autocomplete="current-password" autofocus>

<!-- 忘记密码链接 -->
<p style="text-align:center;margin-bottom:18px;">
    <span onclick="openFpwd()"
          style="color:rgba(255,255,255,0.35);font-size:0.82em;cursor:pointer;
                 text-decoration:underline;transition:color 0.25s;"
          onmouseover="this.style.color='#48dbfb'"
          onmouseout="this.style.color='rgba(255,255,255,0.35)'">
        🔑 忘记密码？
    </span>
</p>

<button name="login" type="submit">登 录</button>
</form>
</div><!-- /.login -->


<!-- ==================== 忘记密码弹窗 ==================== -->
<div class="fpwd-mask" id="fpwdMask" onclick="closeFpwd(event)">
<div class="fpwd-box" onclick="event.stopPropagation()">
    <h3>🔑 重置密码</h3>
    <p>如果忘记管理员密码，请按以下步骤重置：</p>
    <ol style="color:#aaa;font-size:0.86em;line-height:2;padding-left:20px;margin-bottom:8px;">
        <li>通过 FTP / 文件管理器 打开服务器上的 <code style="color:#feca57">admin.php</code></li>
        <li>找到第 <code style="color:#feca57">28</code> 行：<br>
            <code style="color:#00d2d3;font-size:0.82em">$ADMIN_PASSWORD = 'admin123';</code></li>
        <li>将 <code style="color:#ff6b6b">admin123</code> 改为你想要的新密码</li>
        <li>保存文件后重新登录即可</li>
    </ol>
    <button class="fpwd-close" onclick="document.getElementById('fpwdMask').classList.remove('open')">
        我知道了，关闭
    </button>
</div>
</div>


<!-- ==================== 登录结果Toast脚本 ==================== -->
<script>
/* ---- 登录结果检测与弹窗 ---- */
(function(){
    var result = "<?= $login_result === 'fail' ? 'fail' : ($login_result==='ok'?'ok':'') ?>";
    if(result === 'fail'){
        /* 密码错误 → 红色弹窗 3秒消失 */
        showToast('❌ 密码错误，请重试', 'error', 3000);
    }else if(result === 'ok'){
        /* 登录成功 → 绿色弹窗（不再跳转，直接渲染管理界面） */
        showToast('✅ 登录成功！欢迎回来', 'success', 2500);
    }
})();

/* ---- Toast 通用函数（全站共用）---- */
function showToast(text, type, duration){
    /* 移除已有toast */
    var old=document.querySelector('.toast');if(old)old.remove();
    /* 创建 */
    var el=document.createElement('div');
    el.className='toast toast-'+(type||'success');
    el.textContent=text;
    document.body.appendChild(el);
    /* 触发动画 */
    requestAnimationFrame(function(){ el.classList.add('show'); });
    /* 自动消失 */
    setTimeout(function(){
        el.classList.remove('show');
        setTimeout(function(){ if(el.parentNode)el.parentNode.removeChild(el); },400);
    }, duration||3000);
}

/* ---- 忘记密码弹窗控制 ---- */
function openFpwd(){ document.getElementById('fpwdMask').classList.add('open'); }
function closeFpwd(e){
    if(e && e.target!==e.currentTarget) return;
    document.getElementById('fpwdMask').classList.remove('open');
}
/* ESC关闭 */
document.addEventListener('keydown',function(e){ if(e.keyCode===27){ closeFpwd(); } });
</script>

</body></html>
<?php
exit;  /* 防止未登录用户看到管理界面 */
endif;


/* ============================================================
 *   【Action 1】添加导航链接
 * ============================================================ */
if(isset($_POST['add_link'])){
    $cat = trim($_POST['cat']);
    $name = trim($_POST['name']);
    $url = trim($_POST['url']);
    if($cat !== '' && $name !== '' && $url !== ''){
        if(!isset($SITE_DATA[$cat])){ $SITE_DATA[$cat] = array(); }
        $SITE_DATA[$cat][] = array('name'=>$name, 'url'=>$url, 'click'=>0);
        save_json(DATA_FILE, $SITE_DATA);
        push_msg('✅ 导航链接「'.htmlspecialchars($name).'」添加成功！');
    }else{ push_msg('⚠️ 名称、网址和分类不能为空','error'); }
}

/* ============================================================
 *   【Action 2】删除导航链接（GET方式，直接跳转）
 * ============================================================ */
if(isset($_GET['dl'])){
    $c = trim($_GET['cat']);
    $id = trim($_GET['id']);
    if($c !== '' && isset($SITE_DATA[$c][$id])){
        unset($SITE_DATA[$c][$id]);
        $SITE_DATA[$c] = array_values($SITE_DATA[$c]);
        save_json(DATA_FILE, $SITE_DATA);
        push_msg('🗑️ 链接已删除');
    }
}

/* ============================================================
 *   【Action 2b】编辑导航链接
 *   支持修改：名称、网址、点击量
 * ============================================================ */
if(isset($_POST['edit_link'])){
    $cat   = isset($_POST['link_cat'])   ? trim($_POST['link_cat'])   : '';
    $idx   = isset($_POST['link_idx'])   ? intval($_POST['link_idx']) : -1;
    $name  = isset($_POST['link_name'])  ? trim($_POST['link_name'])  : '';
    $url   = isset($_POST['link_url'])   ? trim($_POST['link_url'])   : '';
    $click = isset($_POST['link_click']) ? intval($_POST['link_click']) : 0;

    if($cat !== '' && $idx >= 0 && isset($SITE_DATA[$cat][$idx])){
        if($name === '' || $url === ''){
            push_msg('⚠️ 链接名称和网址不能为空','error');
        }else{
            $SITE_DATA[$cat][$idx]['name']  = $name;
            $SITE_DATA[$cat][$idx]['url']   = $url;
            $SITE_DATA[$cat][$idx]['click'] = max(0, $click);
            save_json(DATA_FILE, $SITE_DATA);
            push_msg('✅ 链接「'.htmlspecialchars($name).'」修改成功！');
        }
    }else{
        push_msg('⚠️ 链接不存在或分类错误','error');
    }
}

/* ============================================================
 *   【Action 3】添加新分类（带图标 + 排序ID）
 *   ID只能为数字，用于前端排序
 * ============================================================ */
if(isset($_POST['add_cat'])){
    $new_cat = trim($_POST['new_cat']);
    $new_icon = isset($_POST['cat_icon'])? trim($_POST['cat_icon']) : '';
    $new_id   = isset($_POST['cat_id'])? trim($_POST['cat_id']) : '';

    /* ID校验：只允许正整数 */
    if($new_id !== '' && !ctype_digit($new_id)){
        push_msg('⚠️ 排序ID只能是正整数数字！','error');
    }elseif($new_cat !== '' && !isset($SITE_DATA[$new_cat])){
        $SITE_DATA[$new_cat] = array();
        save_json(DATA_FILE, $SITE_DATA);

        /* 保存图标 */
        if($new_icon !== ''){
            $CAT_ICONS[$new_cat] = $new_icon;
            save_json(CAT_ICON_FILE, $CAT_ICONS);
        }

        /* 保存排序ID（默认取当前最大ID+1，或用户指定） */
        $max_id = !empty($CAT_SORT) ? max($CAT_SORT) : -1;
        $CAT_SORT[$new_cat] = ($new_id !== '') ? intval($new_id) : ($max_id + 1);
        save_json(CAT_SORT_FILE, $CAT_SORT);

        push_msg('📂 分类「'.htmlspecialchars($new_cat).'」创建成功！');
    }elseif(isset($SITE_DATA[$new_cat])){
        push_msg('⚠️ 该分类已存在','error');
    }
}

/* ============================================================
 *   【Action 4】更新分类属性（图标 + ID + 名称）
 *   通过编辑弹窗提交，一次更新三项
 * ============================================================ */
if(isset($_POST['update_icon'])){
    $cn   = trim($_POST['icon_cat']);     // 原分类名
    $ci   = isset($_POST['icon_val']) ? trim($_POST['icon_val']) : '';
    $cid  = isset($_POST['edit_id'])  ? trim($_POST['edit_id'])  : '';
    $cnew = isset($_POST['edit_name'])? trim($_POST['edit_name']): '';

    if($cn === '' || !isset($SITE_DATA[$cn])){
        push_msg('⚠️ 分类不存在','error');
    }else{
        /* 更新图标 */
        if($ci !== ''){
            $CAT_ICONS[$cn] = $ci;
            save_json(CAT_ICON_FILE, $CAT_ICONS);
        }

        /* 更新排序ID */
        if($cid !== ''){
            if(ctype_digit($cid)){
                $CAT_SORT[$cn] = intval($cid);
                save_json(CAT_SORT_FILE, $CAT_SORT);
            }
        }

        /* 更新分类名称（需要同时更新 data.json / cat_icons / cat_sort 的key） */
        if($cnew !== '' && $cnew !== $cn){
            if(!isset($SITE_DATA[$cnew])){
                /* 重命名所有数据源中的key */
                $SITE_DATA[$cnew] = $SITE_DATA[$cn]; unset($SITE_DATA[$cn]);
                save_json(DATA_FILE, $SITE_DATA);

                if(isset($CAT_ICONS[$cn])){ $CAT_ICONS[$cnew] = $CAT_ICONS[$cn]; unset($CAT_ICONS[$cn]); save_json(CAT_ICON_FILE, $CAT_ICONS); }
                if(isset($CAT_SORT[$cn])){  $CAT_SORT[$cnew]  = $CAT_SORT[$cn];  unset($CAT_SORT[$cn]);  save_json(CAT_SORT_FILE, $CAT_SORT); }

                push_msg('✏️ 分类已更新：'.htmlspecialchars($cn).' → '.htmlspecialchars($cnew));
            }else{
                push_msg('⚠️ 目标名称「'.htmlspecialchars($cnew).'」已存在，名称未修改','error');
            }
        }else{
            push_msg('✅ 分类「'.htmlspecialchars($cn).'」已更新');
        }
    }
}

/* ============================================================
 *   【Action 5】删除分类（同时清理 data/icons/sort）
 * ============================================================ */
if(isset($_GET['dc'])){
    $dc = trim($_GET['dc']);
    if($dc !== '' && $dc !== 'featured'){
        unset($SITE_DATA[$dc]);
        unset($CAT_ICONS[$dc]);
        unset($CAT_SORT[$dc]);           /* 同步删除排序记录 */
        save_json(DATA_FILE, $SITE_DATA);
        save_json(CAT_ICON_FILE, $CAT_ICONS);
        save_json(CAT_SORT_FILE, $CAT_SORT);
        push_msg('🗑️ 分类「'.htmlspecialchars($dc).'」已删除');
    }
}

/* ============================================================
 *   【Action 6】保存站点基本设置
 * ============================================================ */
if(isset($_POST['save_site'])){
    $SITE_CONFIG['title']     = isset($_POST['title'])     ? trim($_POST['title'])     : '';
    $SITE_CONFIG['subtitle']  = isset($_POST['subtitle'])  ? trim($_POST['subtitle'])  : '';
    $SITE_CONFIG['keywords']  = isset($_POST['keywords'])  ? trim($_POST['keywords'])  : '';
    $SITE_CONFIG['background']= isset($_POST['background'])? trim($_POST['background']): '';
    save_json(CONFIG_FILE, $SITE_CONFIG);
    $SITE_CONFIG = load_json(CONFIG_FILE);
    push_msg('💾 站点基本设置保存成功！');
}

/* ============================================================
 *   【Action 7】保存底部页脚设置
 * ============================================================ */
if(isset($_POST['save_footer'])){
    $SITE_CONFIG['footer_line1'] = isset($_POST['footer_line1']) ? trim($_POST['footer_line1']) : '';
    $SITE_CONFIG['icp']          = isset($_POST['icp'])          ? trim($_POST['icp'])          : '';
    $SITE_CONFIG['icp_url']      = isset($_POST['icp_url'])      ? trim($_POST['icp_url'])      : '';
    $SITE_CONFIG['footer_line3'] = isset($_POST['footer_line3']) ? trim($_POST['footer_line3']) : '';
    $SITE_CONFIG['footer_line4'] = isset($_POST['footer_line4']) ? trim($_POST['footer_line4']) : '';
    save_json(CONFIG_FILE, $SITE_CONFIG);
    $SITE_CONFIG = load_json(CONFIG_FILE);
    push_msg('💾 底部页脚设置保存成功！');
}

/* ============================================================
 *   【Action 8】保存自定义代码
 * ============================================================ */
if(isset($_POST['save_code'])){
    $SITE_CONFIG['custom_html'] = isset($_POST['custom_html']) ? $_POST['custom_html'] : '';
    $SITE_CONFIG['custom_css']  = isset($_POST['custom_css'])  ? $_POST['custom_css']  : '';
    save_json(CONFIG_FILE, $SITE_CONFIG);
    $SITE_CONFIG = load_json(CONFIG_FILE);
    push_msg('💾 自定义代码保存成功！');
}

/* ============================================================
 *   【Action 9】生成静态首页 index.html
 *   直接渲染与 index.php 完全相同的 HTML 并写入文件
 *   减轻服务器动态渲染压力
 * ============================================================ */
if(isset($_GET['gen_html'])){
    // 重新加载最新数据
    $SITE_DATA   = load_json(DATA_FILE);
    $SITE_CONFIG = load_json(CONFIG_FILE);
    $CAT_ICONS   = load_json(CAT_ICON_FILE);
    $CAT_SORT    = load_json(CAT_SORT_FILE);

    // === 完全复制 index.php 的渲染逻辑生成静态 HTML ===
    $CAT_COLORS = array(
        'featured'   => array('icon'=>'🔥','c1'=>'#ff6b6b','c2'=>'#feca57','c3'=>'#ff9ff3','glow'=>'rgba(255,107,107,0.2)'),
        'AI工具'     => array('icon'=>'🤖','c1'=>'#48dbfb','c2'=>'#0abde3','c3'=>'#10ac84','glow'=>'rgba(72,219,251,0.2)'),
        '开发工具'   => array('icon'=>'💻','c1'=>'#a29bfe','c2'=>'#6c5ce7','c3'=>'#fd79a8','glow'=>'rgba(162,155,254,0.2)'),
        '实用工具'   => array('icon'=>'🛠️','c1'=>'#00d2d3','c2'=>'#01a3a4','c3'=>'#48dbfb','glow'=>'rgba(0,210,211,0.2)'),
        '娱乐专区'   => array('icon'=>'🎮','c1'=>'#ff9ff3','c2'=>'#f368e0','c3'=>'#ff6b6b','glow'=>'rgba(255,159,243,0.2)'),
        '学习资源'   => array('icon'=>'📚','c1'=>'#feca57','c2'=>'#ff9f43','c3'=>'#ee5a24','glow'=>'rgba(254,202,87,0.2)'),
        '设计素材'   => array('icon'=>'🎨','c1'=>'#ff6b81','c2'=>'#ee5a52','c3'=>'#f8b500','glow'=>'rgba(255,107,129,0.2)'),
        '影视动漫'   => array('icon'=>'🎬','c1'=>'#e056fd','c2'=>'#be2edd','c3'=>'#686de0','glow'=>'rgba(224,86,253,0.2)'),
    );
    $DEF_ICON='📌'; $DEF_C1='#5f27cd'; $DEF_C2='#341f97'; $DEF_C3='#222f3e'; $DEF_GLOW='rgba(95,39,205,0.15)';

    function get_cat_style_s($cat_name){
        global $CAT_COLORS, $CAT_ICONS, $DEF_ICON, $DEF_C1, $DEF_C2, $DEF_C3, $DEF_GLOW;
        if(isset($CAT_ICONS[$cat_name]))       { $icon = $CAT_ICONS[$cat_name]; }
        elseif(isset($CAT_COLORS[$cat_name]))  { $icon = $CAT_COLORS[$cat_name]['icon']; }
        else                                   { $icon = $DEF_ICON; }
        if(isset($CAT_COLORS[$cat_name])){ $r = $CAT_COLORS[$cat_name]; }
        else{ $r = array('icon'=>$icon,'c1'=>$DEF_C1,'c2'=>$DEF_C2,'c3'=>$DEF_C3,'glow'=>$DEF_GLOW); }
        $r['icon'] = $icon;
        return $r;
    }

    $static_html = '<!DOCTYPE html>'."\n";
    $static_html .= '<html lang="zh-CN">'."\n";
    $static_html .= '<head>'."\n";
    $static_html .= '<meta charset="UTF-8">'."\n";
    $static_html .= '<meta name="viewport" content="width=device-width,initial-scale=1.0">'."\n";
    $static_html .= '<title>'.htmlspecialchars(cfg('title','导航')).'</title>'."\n";
    $static_html .= '<meta name="description" content="'.htmlspecialchars(cfg('title','')).' - 精选网址导航">'."\n";
    $static_html .= '<meta name="keywords" content="'.htmlspecialchars(cfg('keywords','')).'">'."\n";
    $static_html .= '<link rel="stylesheet" href="style.css">'."\n";
    $bg = trim(cfg('background',''));
    if($bg !== '') $static_html .= '<style>.bg-canvas{background:url(\''.htmlspecialchars($bg).'\') center/cover fixed no-repeat!important;}</style>'."\n";
    $css = trim(cfg('custom_css',''));
    if($css !== '') $static_html .= '<style type="text/css">'.$css.'</style>'."\n";
    $static_html .= '</head>'."\n";
    $static_html .= '<body>'."\n";
    $static_html .= '<div class="bg-canvas"></div><div class="overlay">'."\n";
    $static_html .= '<div class="site-header"><h1 class="site-title">'.htmlspecialchars(cfg('title','个人在线网址合集')).'</h1>';
    $static_html .= '<p class="site-subtitle">'.htmlspecialchars(cfg('subtitle','✦ 精选网址导航 · 一站直达 ✦')).'</p></div>'."\n";
    $static_html .= '<div class="bing-search-wrap"><form class="bing-search-form" onsubmit="return doSearch(event)"><div class="bing-input-wrap"><svg class="bing-icon" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg><input type="text" id="q" autocomplete="off" placeholder="搜索互联网..." autofocus></div><input type="submit" value="" style="display:none"><div class="engine-switch"><button type="button" class="eng-btn active" data-e="bing" onclick="swEng(this)">Bing</button><span class="eng-divider"></span><button type="button" class="eng-btn" data-e="google" onclick="swEng(this)">Google</button></div></form></div>'."\n";
    $static_html .= '<div class="quick-tips"><span>按 Enter 直接搜索</span><span class="qt-dot">·</span><span>Bing / Google 双引擎</span></div>'."\n";

    // Featured
    if(!empty($SITE_DATA['featured'])){
        $cs = $CAT_COLORS['featured'];
        $static_html .= '<div class="sec-head"><i class="sec-icon">'.$cs['icon'].'</i><span class="sec-title" style="--a:'.$cs['c1'].';--b:'.$cs['c2'].'">推荐位 / Featured</span><div class="sec-line" style="--a:'.$cs['c1'].'"></div></div>'."\n";
        $static_html .= '<div class="g-feat">';
        foreach($SITE_DATA['featured'] as $k=>$v){
            $static_html .= '<div class="card feat" style="--c1:'.$cs['c1'].';--c2:'.$cs['c2'].';--c3:'.$cs['c3'].';--gl:'.$cs['glow'].';--hv:'.$cs['c2'].'"><span class="badge-hot">HOT</span><a href="click.php?cat=featured&id='.$k.'" target="_blank">'.htmlspecialchars($v['name']).'</a><small>'.intval($v['click']).' 次</small></div>';
        }
        $static_html .= '</div>'."\n";
    }

    // 普通分类
    $sorted_cats = array_keys($SITE_DATA);
    usort($sorted_cats, function($a,$b){ global $CAT_SORT; $va=isset($CAT_SORT[$a])?intval($CAT_SORT[$a]):999; $vb=isset($CAT_SORT[$b])?intval($CAT_SORT[$b]):999; return $va-$vb; });
    foreach($sorted_cats as $cat){
        if($cat==='featured') continue;
        $items = $SITE_DATA[$cat];
        $s = get_cat_style_s($cat);
        $slug = preg_replace('/[^\w\x{4e00}-\x{9fff}]/u','',$cat);
        $static_html .= '<div class="sec-head"><i class="sec-icon">'.$s['icon'].'</i><span class="sec-title" style="--a:'.$s['c1'].';--b:'.$s['c2'].'">'.htmlspecialchars($cat).'</span><div class="sec-line" style="--a:'.$s['c1'].'"></div></div>'."\n";
        if(!empty($items)){
            $static_html .= '<div class="g-nav" id="s-'.$slug.'">';
            foreach($items as $k=>$v){
                $linkIcon = isset($v['icon']) ? $v['icon'] : '';
                $static_html .= '<div class="card" style="--c1:'.$s['c1'].';--c2:'.$s['c2'].';--c3:'.$s['c3'].';--gl:'.$s['glow'].';--hv:'.$s['c2'].'">';
                if($linkIcon) $static_html .= '<span style="margin-right:3px;font-size:0.85em;">'.htmlspecialchars($linkIcon).'</span>';
                $static_html .= '<a href="click.php?cat='.urlencode($cat).'&id='.$k.'" target="_blank">'.htmlspecialchars($v['name']).'</a><small>'.intval($v['click']).' 次</small></div>';
            }
            $static_html .= '</div>'."\n";
        }else{
            $static_html .= '<div class="g-nav"><div class="empty-cat-hint">暂无内容，敬请期待 🎉</div></div>'."\n";
        }
    }

    // Footer
    $static_html .= '<footer class="site-footer">';
    $f1=cfg('footer_line1','');
    if($f1!=='') $static_html .= '<p class="fr">'.$f1.'</p>';
    else $static_html .= '<p class="fr">&copy; '.date('Y').' '.htmlspecialchars(cfg('title','')).'</p>';
    $icp=cfg('icp',''); $icpu=cfg('icp_url','');
    if($icp!==''){
        if(stripos($icp,'<a')!==false) $static_html .= '<p class="fr">'.$icp.'</p>';
        elseif($icpu!=='') $static_html .= '<p class="fr"><a href="'.htmlspecialchars($icpu).'" target="_blank" rel="nofollow" class="fl">'.htmlspecialchars($icp).'</a></p>';
        else $static_html .= '<p class="fr">'.htmlspecialchars($icp).'</p>';
    }
    $f3=cfg('footer_line3','');
    if($f3!=='') $static_html .= '<p class="fr fr-sm">'.$f3.'</p>';
    else $static_html .= '<p class="fr fr-sm">Powered with ♥</p>';
    $f4=cfg('footer_line4','');
    if($f4!=='') $static_html .= '<p class="fr fr-sm">'.$f4.'</p>';
    $static_html .= '</footer>'."\n";

    $custom_html = trim(cfg('custom_html',''));
    if($custom_html!=='') $static_html .= $custom_html."\n";
    $static_html .= '</div><!-- /.overlay -->'."\n";
    $static_html .= '<script src="app.js"></script>'."\n";
    $static_html .= '<script>var _eng="bing";function swEng(btn){_eng=btn.getAttribute("data-e");var bs=document.querySelectorAll(".eng-btn");for(var i=0;i<bs.length;i++)bs[i].className="eng-btn";btn.className="eng-btn active";}function doSearch(e){var q=document.getElementById("q").value.trim();if(!q)return!1;var base=_eng==="bing"?"https://www.bing.com/search?q=":"https://www.google.com/search?q=";window.open(base+encodeURIComponent(q),"_blank");return!1;}</script>'."\n";
    $static_html .= '</body></html>'."\n";

    $static_file = dirname(__FILE__).'/index.html';
    $written = file_put_contents($static_file, $static_html);

    if($written !== false && $written > 0){
        push_msg("📄 静态首页已生成！文件大小：".number_format($written)." 字节");
    }else{
        push_msg('⚠️ 静态首页生成失败，请检查目录写入权限','error');
    }
}

/**
    $SITE_CONFIG['custom_html'] = isset($_POST['custom_html']) ? $_POST['custom_html'] : '';
    $SITE_CONFIG['custom_css']  = isset($_POST['custom_css'])  ? $_POST['custom_css']  : '';
    save_json(CONFIG_FILE, $SITE_CONFIG);
    $SITE_CONFIG = load_json(CONFIG_FILE);
    push_msg('💾 自定义代码保存成功！');
}

/**
 * 读取并清空消息队列（JS会读取这些数据弹Toast）
 * 用完即删，避免重复弹出
 */
$msgs_to_show = array();
if(!empty($_SESSION['admin_msgs'])){
    $msgs_to_show = $_SESSION['admin_msgs'];
    unset($_SESSION['admin_msgs']);
}
?>


<!-- ==================== 后台管理界面 HTML ==================== -->
<link rel="stylesheet" href="style.css">

<div style="max-width:920px;margin:0 auto;padding:20px;">

<!-- 页头（右侧按钮组 + 二次确认） -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:8px;">
    <h1 style="background:linear-gradient(90deg,#ff6b6b,#48dbfb);
               -webkit-background-clip:text;-webkit-text-fill-color:transparent;margin:0;">
        ⚡ 后台管理中心
    </h1>
    <!-- 右侧按钮组 -->
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <!-- 生成静态首页按钮 -->
        <button onclick="if(confirm('确定生成静态首页？\n将把首页渲染为 index.html，可减轻服务器压力。')){location.href='?gen_html=1';}"
                class="logout-btn" title="将首页渲染为 index.html 静态文件，减轻服务器压力"
                style="background:linear-gradient(135deg,#6c5ce7,#a29bfe);">
            📄 生成静态首页
        </button>
        <!-- 退出登录按钮 -->
        <button onclick="if(confirm('确定要退出登录吗？')){location.href='?logout=1';}"
                class="logout-btn" title="退出后台管理">
            🚪 退出登录
        </button>
    </div>
</div>


<!--
==============================================================
 模块一：站点基本设置
 action=save_site → 只更新 title/subtitle/keywords/background
==============================================================
-->
<h2 class="ah" style="color:#48dbfb;">📡 站点基本设置</h2>
<div class="ac">
<form method="post">

    <label>网站标题</label>
    <input type="text" name="title" value="<?= e(cfg('title','')) ?>" class="ai">

    <label>副标题（标题下方文字，支持HTML）</label>
    <input type="text" name="subtitle" value="<?= e(cfg('subtitle','')) ?>"
           placeholder='例：✦ 精选网址导航 · 一站直达 ✦' class="ai">

    <label>SEO关键词（逗号分隔）</label>
    <input type="text" name="keywords" value="<?= e(cfg('keywords','')) ?>" class="ai">

    <label>背景图片地址（留空使用默认渐变背景）</label>
    <input type="text" name="background" value="<?= e(cfg('background','')) ?>"
           placeholder="https://example.com/bg.jpg 或留空" class="ai">

    <button type="submit" name="save_site" class="bs b-blue">
        💾 保存站点设置
    </button>

</form>
</div>


<!--
==============================================================
 模块二：底部页脚设置（每行支持HTML代码）
 action=save_footer → 只更新5个字段
==============================================================
-->
<h2 class="ah" style="color:#ff9ff3;">🎨 底部页脚设置（支持HTML代码）</h2>
<div class="ac">
<form method="post">
<p style="color:#feca57;font-size:0.82em;margin-bottom:14px;padding:8px 10px;
   background:rgba(254,202,87,0.08);border-radius:6px;border-left:3px solid #feca57;">
💡 提示：以下每个框都支持填写 <b>HTML代码</b>。<br>
例如：<code>&lt;a href="https://xxx"&gt;我的链接&lt;/a&gt;</code>
或 <code>&lt;span style="color:red"&gt;红色文字&lt;/span&gt;</code><br>
留空则使用默认值。ICP备案号支持填写完整 &lt;a&gt; 标签。
</p>

    <!-- 第1行：版权信息 -->
    <label>第1行 - 版权信息 <span style="font-size:0.75em;color:#888;">（支持HTML，留空自动 ©年 标题）</span></label>
    <textarea name="footer_line1" rows="2" class="ai-html"
              placeholder='例：&copy; 2026 我的网站 · All Rights Reserved'><?= e(cfg('footer_line1','')) ?></textarea>

    <!-- ICP备案号（支持完整<a>标签）-->
    <label>ICP备案号 <span style="font-size:0.75em;color:#888;">（支持HTML，如 &lt;a href=...&gt;...&lt;/a&gt;）</span></label>
    <textarea name="icp" rows="2" class="ai-html"
              placeholder='例：京ICP备xxxxxxxx号 或 &lt;a href="https://..."&gt;京ICP备x号&lt;/a&gt;'><?= e(cfg('icp','')) ?></textarea>

    <label>备案链接地址（如果上面ICP已写了完整a标签，此项留空即可）</label>
    <input type="text" name="icp_url" value="<?= e(cfg('icp_url','')) ?>"
           placeholder="https://beian.miit.gov.cn" class="ai">

    <!-- 第3行：Powered 信息 -->
    <label>第3行 - Powered 信息 <span style="font-size:0.75em;color:#888;">（支持HTML，留空默认）</span></label>
    <textarea name="footer_line3" rows="2" class="ai-html"
              placeholder='例：Powered with ❤️ by MySite'><?= e(cfg('footer_line3','')) ?></textarea>

    <!-- 第4行：额外自定义行 -->
    <label>第4行 - 额外自定义行 <span style="font-size:0.75em;color:#888;">（支持HTML/友情链接等，可留空）</span></label>
    <textarea name="footer_line4" rows="2" class="ai-html"
              placeholder='例：&lt;a href="/about"&gt;关于我们&lt;/a&gt; | &lt;a href="/contact"&gt;联系我们&lt;/a&gt;'><?= e(cfg('footer_line4','')) ?></textarea>

    <button type="submit" name="save_footer" class="bs b-pink">
        💾 保存底部设置
    </button>

</form>
</div>


<!--
==============================================================
 模块三：导航分类管理（含图标素材库 + ID排序）
==============================================================
-->
<h2 class="ah" style="color:#feca57;">📂 导航分类管理</h2>
<div class="ac">

    <!-- 新增分类表单（名称 + 排序ID + 图标） -->
    <h3 style="color:#bbb;font-size:0.9em;margin-bottom:10px;">➕ 添加新分类</h3>
    <form method="post" style="display:flex;gap:8px;margin-bottom:14px;align-items:flex-end;">
        <div style="flex:1;">
            <label style="font-size:0.78em;color:#888;display:block;margin-bottom:4px;">分类名称</label>
            <input type="text" name="new_cat" placeholder="输入分类名称，如：游戏资源"
                   required class="ai">
        </div>
        <div style="min-width:90px;">
            <label style="font-size:0.78em;color:#888;display:block;margin-bottom:4px;">排序 ID</label>
            <input type="number" name="cat_id" min="0" max="999"
                   placeholder="数字"
                   class="ai" style="font-family:'Consolas',monospace;text-align:center;"
                   pattern="[0-9]*" inputmode="numeric">
        </div>
        <div style="min-width:150px;position:relative">
            <label style="font-size:0.78em;color:#888;display:block;margin-bottom:4px;">图标</label>
            <input type="text" name="cat_icon" id="nciInput"
                   value="📁" readonly onclick="toggleLib()"
                   class="ai" style="cursor:pointer;padding:10px 12px;" placeholder="点击选图标">
        </div>
        <button type="submit" name="add_cat" class="bc" style="align-self:flex-end;">
            + 新增
        </button>
    </form>

    <!-- 图标素材库（48个精选emoji图标） -->
    <h3 style="color:#bbb;font-size:0.85em;margin:12px 0 8px;">🎨 图标素材库（点击选用）</h3>
    <div class="ilib"><?php
        /* ===== 图标库：全部使用标准单字emoji，无组合文字，杜绝溢出 ===== */
        $LIB = array(
            '🔥','🤖','💻','🛠️','🎮','📚','🎨','🎬','🌐','☁️',
            '🔒','💰','🛒','🍔','✈️','🏋️','📱','🎵','📷','📰',
            '💼','🔧','🚀','⭐','💎','🎯','📁','🌈','⚡','💊',
            '🏠','🌍','📝','🔬','🏥','⚽','🐱','🌸','❄️','☀️',
            '🎁','📌','🔑','🎪','🍕','🚗','💡','✅'
        );
        foreach($LIB as $ico):
            echo '<span class="li-item" data-icon="'.e($ico).'" title="'.e($ico).'"
                      onclick="pickIcon(this)">'.$ico.'</span>';
        endforeach;
    ?></div>
    <p style="color:#666;font-size:0.76em;margin-top:6px;">
        提示：也可直接输入 emoji / 图片URL / FontAwesome类名
    </p>

    <!-- 当前所有分类（按排序ID从小到大排列） -->
    <h3 style="color:#bbb;font-size:0.85em;margin:16px 0 8px;">📋 当前所有分类（按 ID 排序）</h3>
    <table class="ct">
        <tr><th width="36">图标</th><th width="50">ID</th><th>分类名</th><th width="55">链接数</th><th width="90">操作</th></tr>
        <?php
            /* ====== 按 CAT_SORT 的ID值从小到大排序 ====== */
            $sorted_cats = array_keys($SITE_DATA);
            usort($sorted_cats, function($a, $b) use ($CAT_SORT){
                $va = isset($CAT_SORT[$a]) ? intval($CAT_SORT[$a]) : 999;
                $vb = isset($CAT_SORT[$b]) ? intval($CAT_SORT[$b]) : 999;
                return $va - $vb;   /* 升序：小ID在前 */
            });

            foreach($sorted_cats as $cname):
                $items = $SITE_DATA[$cname];
                $curIcon = isset($CAT_ICONS[$cname]) ? $CAT_ICONS[$cname] : '📌';
                $curId   = isset($CAT_SORT[$cname])  ? intval($CAT_SORT[$cname]) : '-';
        ?>
        <tr>
            <td style="text-align:center;font-size:1.3em;"><?= e($curIcon) ?></td>
            <td style="text-align:center;color:#feca57;font-family:'Consolas',monospace;font-weight:600;">
                <?= $curId ?>
            </td>
            <td style="color:#fff;font-size:0.92em;">
                <?= e($cname) ?>
                <?php if($cname === 'featured'):
                    echo '<span style="font-size:0.72em;color:#feca57;"> (系统保留)</span>';
                endif; ?>
            </td>
            <td style="text-align:center;color:#48dbfb;font-size:0.86em;">
                <?= is_array($items) ? count($items) : 0 ?>
            </td>
            <td style="text-align:center">
                <?php if($cname !== 'featured'): ?>
                <!-- 编辑按钮（打开完整编辑弹窗：图标+ID+名称） -->
                <!-- 使用 data-* 属性传参 + data URI 安全编码，避免 onclick 中特殊字符破坏JS -->
                <span class="ieb" 
                      data-ecat="<?= htmlspecialchars($cname, ENT_QUOTES, 'UTF-8') ?>"
                      data-eicon="<?= htmlspecialchars($curIcon, ENT_QUOTES, 'UTF-8') ?>"
                      data-eid="<?= intval($curId) ?>"
                      onclick="openCatEdit(this.getAttribute('data-ecat'), this.getAttribute('data-eicon'), this.getAttribute('data-eid'))"
                      title="编辑分类（图标/ID/名称）">✏️</span>
                <!-- 删除按钮（带确认） -->
                <a href="<?= '?'.htmlentities('dc='.urlencode($cname)) ?>"
                   class="dcl" onclick="return confirm('确定删除此分类及旗下所有链接？')">删</a>
                <?php else: echo '-'; endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

</div><!-- /.ac -->


<!--
==============================================================
 模块四：添加导航链接
==============================================================
-->
<h2 class="ah" style="color:#00d2d3;">➕ 添加导航链接</h2>
<div class="ac">
<form method="post">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">
        <div style="flex:1;min-width:130px;">
            <label>网站名称</label>
            <input type="text" name="name" required class="ai" placeholder="名称">
        </div>
        <div style="flex:2;min-width:200px;">
            <label>网址</label>
            <input type="text" name="url" required class="ai" placeholder="https://">
        </div>
        <div style="min-width:120px;">
            <label>所属分类</label>
            <select name="cat" class="as">
                <?php foreach($SITE_DATA as $c => $v): ?>
                <option><?= e($c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" name="add_link" class="ba">添加</button>
    </div>
</form>
</div>


<!--
==============================================================
 模五：链接列表 + 删除
==============================================================
-->
<h2 class="ah" style="color:#ff6b6b;">🗑️ 链接管理</h2>
<div class="ac">
<?php foreach($SITE_DATA as $cname => $items): ?>
    <h3 style="color:#a29bfe;margin-top:12px;font-size:0.93em;">
        <?= e($cname) ?>
        (<?= is_array($items)?count($items):0 ?>)
    </h3>
    <table class="lt">
        <tr><th>名称</th><th>网址</th><th width="50">点击</th><th width="72"></th></tr>
        <?php if(!empty($items)):
            foreach($items as $k => $v):
        ?>
        <tr>
            <td><?= e($v['name']) ?></td>
            <td style="color:#777;font-size:0.8em;word-break:break-all;"><?= e($v['url']) ?></td>
            <td style="text-align:center;color:#feca57;"><?= intval($v['click']) ?></td>
            <td style="text-align:center;">
                <!-- 编辑按钮 -->
                <span class="ieb" style="cursor:pointer;margin-right:4px;"
                      data-lcat="<?= htmlspecialchars($cname, ENT_QUOTES, 'UTF-8') ?>"
                      data-lidx="<?= $k ?>"
                      data-lname="<?= htmlspecialchars($v['name'], ENT_QUOTES, 'UTF-8') ?>"
                      data-lurl="<?= htmlspecialchars($v['url'], ENT_QUOTES, 'UTF-8') ?>"
                      data-lclick="<?= intval($v['click']) ?>"
                      onclick="openLinkEdit(this)"
                      title="编辑链接">✏️</span>
                <!-- 删除按钮 -->
                <a href="<?= '?'.htmlentities('dl=1&cat='.urlencode($cname).'&id='.$k) ?>"
                   class="dll" onclick="return confirm('确定删除此链接？')">✕</a>
            </td>
        </tr>
        <?php   endforeach;
          else:
              echo '<tr><td colspan="4" class="empty-row">暂无链接</td></tr>';
          endif; ?>
    </table>
<?php endforeach; ?>
</div>


<!--
==============================================================
 模六：自定义代码（高级功能）
==============================================================
-->
<h2 class="ah" style="color:#a29bfe;">💻 自定义代码（高级）</h2>
<div class="ac">
<p style="color:#999;font-size:0.82em;margin-bottom:12px;">
    填入的 CSS 会注入 &lt;head&gt;，HTML 会插入页面底部。可用于统计代码、广告、自定义样式等。
</p>
<form method="post">

    <label>自定义 CSS（注入到 &lt;head&gt; 中）</label>
    <textarea name="custom_css" rows="5" class="at"
              placeholder="/* 例：.site-title{color:red;} */"><?= e(cfg('custom_css','')) ?></textarea>

    <label>自定义 HTML（插入 &lt;/body&gt; 前）</label>
    <textarea name="custom_html" rows="6" class="at"
              placeholder="<!-- 统计/广告代码等 -->&#10;"><!-- 例：<script>console.log("hi");</script> --><?= e(cfg('custom_html','')) ?></textarea>

    <button type="submit" name="save_code" class="bs b-purple">
        💾 保存自定义代码
    </button>

</form>
</div>

<!-- 底部版权 -->
<p style="margin-top:30px;text-align:center;color:rgba(255,255,255,0.18);font-size:0.76em;">
    Anime Nav Pro Plus · Admin v6.0 · Toast UI
</p>

</div><!-- /容器结束 -->


<!--
================================================================
 分类编辑弹窗（点击 ✏️ 触发）
 可编辑：图标 + 排序ID + 分类名称
================================================================
-->
<div id="iem" style="display:none">
    <div class="im-box" style="width:460px;">
        <h3>✏️ 编辑分类 - <span id="iemCatName"></span></h3>

        <!-- 分类名称输入 -->
        <label style="color:#bbb;font-size:0.82em;display:block;margin-bottom:5px;">分类名称</label>
        <input type="text" id="iemNameInput" class="im-inp"
               placeholder="新名称（留空则不修改）">

        <!-- 排序ID输入 -->
        <label style="color:#bbb;font-size:0.82em;display:block;margin-top:12px;margin-bottom:5px;">排序 ID（数字越小越靠前）</label>
        <input type="number" id="iemIdInput" class="im-inp"
               min="0" max="999" placeholder="数字"
               style="font-family:'Consolas',monospace;text-align:center;">

        <!-- 图标选择 -->
        <label style="color:#bbb;font-size:0.82em;display:block;margin-top:12px;margin-bottom:5px;">选择图标</label>
        <div class="im-grid" id="iemGrid" style="max-height:180px;"></div>
        <input type="text" id="iemInput" class="im-inp"
               style="margin-top:8px;"
               placeholder="或手动输入 emoji / 图片URL">

        <div class="im-btns">
            <button type="button" class="ib-c" onclick="closeIconEdit()">取消</button>
            <button type="button" class="ib-o" onclick="confirmIconEdit()">确定保存</button>
        </div>
    </div>
</div>

<!-- 编辑隐藏表单（提交图标+ID+名称） -->
<form method="post" id="iconForm" style="display:none">
    <input type="hidden" name="update_icon" value="1">
    <input type="hidden" name="icon_cat"  id="hfCat"  value="">
    <input type="hidden" name="icon_val"   id="hfVal"  value="">
    <input type="hidden" name="edit_id"    id="hfId"   value="">
    <input type="hidden" name="edit_name"  id="hfName" value="">
</form>

<!-- =============================================
  链接编辑弹窗
============================================= -->
<div id="linkEditModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:10000;align-items:center;justify-content:center;">
    <div style="background:#1a1a2e;border-radius:16px;padding:28px;width:440px;max-width:95vw;box-shadow:0 20px 60px rgba(0,0,0,0.5);">
        <h3 style="color:#fff;margin:0 0 20px 0;font-size:1.1em;">✏️ 编辑链接</h3>

        <!-- 链接名称 -->
        <div style="margin-bottom:14px;">
            <label style="color:#aaa;font-size:0.82em;display:block;margin-bottom:5px;">链接名称</label>
            <input type="text" id="linkNameInput" class="ai" style="width:100%;box-sizing:border-box;" placeholder="网站名称">
        </div>

        <!-- 链接网址 -->
        <div style="margin-bottom:14px;">
            <label style="color:#aaa;font-size:0.82em;display:block;margin-bottom:5px;">网址 URL</label>
            <input type="text" id="linkUrlInput" class="ai" style="width:100%;box-sizing:border-box;" placeholder="https://">
        </div>

        <!-- 点击量 -->
        <div style="margin-bottom:20px;">
            <label style="color:#aaa;font-size:0.82em;display:block;margin-bottom:5px;">点击量（手动修正）</label>
            <input type="number" id="linkClickInput" class="ai" style="width:100%;box-sizing:border-box;" min="0" placeholder="0">
        </div>

        <!-- 按钮组 -->
        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <button type="button" class="ib-c" onclick="closeLinkEdit()">取消</button>
            <button type="button" class="ib-o" onclick="confirmLinkEdit()">保存修改</button>
        </div>
    </div>
</div>

<!-- 链接编辑隐藏表单 -->
<form method="post" id="linkEditForm" style="display:none">
    <input type="hidden" name="edit_link" value="1">
    <input type="hidden" name="link_cat"    id="lfCat"    value="">
    <input type="hidden" name="link_idx"    id="lfIdx"    value="">
    <input type="hidden" name="link_name"   id="lfName"   value="">
    <input type="hidden" name="link_url"    id="lfUrl"    value="">
    <input type="hidden" name="link_click"  id="lfClick"  value="">
</form>



<style>
/* ========== 后台专用样式（内联，不污染全局CSS）========== */

/* 卡片容器 */
.ac{background:rgba(255,255,255,0.04);border-radius:12px;padding:22px;
    border:1px solid rgba(255,255,255,0.07);margin-bottom:22px;}

/* 标题 */
.ah{font-size:1.08em;margin:22px 0 4px;}

/*
 * 输入框 - 不透明深色底 + 白色文字
 * 关键修复：确保在暗色背景下文字清晰可见
 */
.ai{width:100%;padding:10px 14px;background:rgba(15,17,35,0.95)!important;
    border:1px solid rgba(255,255,255,0.16)!important;border-radius:8px;
    color:#fff!important;margin-bottom:13px;outline:none;font-size:0.91em;
    transition:border-color 0.25s;}
.ai:focus{border-color:#48dbfb!important;box-shadow:0 0 14px rgba(72,219,251,0.12)!important;}

/*
 * select 下拉框
 * 关键：不透明背景 + 白色选项文字（解决之前透明看不到的问题）
 */
.as{width:100%;padding:10px 14px;background:rgba(15,17,35,0.97)!important;
    border:1px solid rgba(255,255,255,0.16)!important;border-radius:8px;
    color:#fff!important;font-size:0.91em;cursor:pointer;
    appearance:none;-webkit-appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%23888'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:right 12px center;}
.as option{background:#141630;color:#fff;padding:4px 8px;}

/* textarea（代码编辑用 - 绿色等宽字体） */
.at{width:100%;padding:11px 14px;background:rgba(0,0,0,0.35)!important;
    border:1px solid rgba(255,255,255,0.12)!important;border-radius:8px;
    color:#0f8!important;font-family:Consolas,'Courier New',monospace;font-size:0.86em;
    resize:vertical;margin-bottom:13px;outline:none;}

/*
 * textarea（HTML 输入用 - 底部页脚设置）
 * 关键：白色明亮文字 + 深色底 + 黄色聚焦边框
 * 解决之前 ICP 备案号输入框看不到的问题
 */
.ai-html{width:100%;padding:10px 14px;background:rgba(12,15,30,0.95)!important;
    border:1px solid rgba(255,255,255,0.16)!important;border-radius:8px;
    color:#e0e0e0!important;font-size:0.92em;line-height:1.6;   /* 字体从0.88加大到0.92 */
    resize:vertical;margin-bottom:13px;outline:none;min-height:52px;}
.ai-html:focus{border-color:#feca57!important;box-shadow:0 0 12px rgba(254,202,87,0.10)!important;}

/* ===== 按钮通用样式 ===== */
.bs{width:100%;padding:11px;border:none;border-radius:8px;color:#fff;
    font-weight:600;cursor:pointer;font-size:0.93em;transition:all 0.25s;}
.bs:hover{transform:scale(1.01);filter:brightness(1.08);}
.b-blue{background:linear-gradient(135deg,#667eea,#764ba2)}
.b-pink{background:linear-gradient(135deg,#f368e0,#e056fd)}
.b-purple{background:linear-gradient(135deg,#a29bfe,#6c5ce7)}

/* 小按钮：新增分类（黄色） */
.bc{padding:9px 20px;background:linear-gradient(135deg,#feca57,#ff9f43);
    border:none;border-radius:8px;color:#333;cursor:pointer;font-weight:600;
    font-size:0.88em;white-space:nowrap;transition:all 0.25s;}
.bc:hover{transform:scale(1.03);box-shadow:0 4px 14px rgba(254,202,87,0.3)}

/* 小按钮：添加链接（青色） */
.ba{padding:11px 20px;background:linear-gradient(135deg,#00d2d3,#01a3a4);
    border:none;border-radius:8px;color:#fff;cursor:pointer;font-weight:600;
    white-space:nowrap;transition:all 0.25s;}
.ba:hover{transform:scale(1.03)}

/* ===== 表格 ===== */
.ct,.lt{width:100%;border-collapse:collapse;}
.ct th,.ct td,.lt th,.lt td{
    padding:7px 5px;border-bottom:1px solid rgba(255,255,255,0.04);}
.ct th,.lt th{color:#888;font-size:0.8em;text-align:left;font-weight:normal;}
.empty-row{color:#555;text-align:center;padding:14px;font-size:0.84em;}

/* ===== 图标素材库（防溢出：固定尺寸+溢出隐藏） ===== */
.ilib{display:flex;flex-wrap:wrap;gap:6px;padding:14px;
    background:rgba(0,0,0,0.18);border-radius:10px;
    border:1px solid rgba(255,255,255,0.05);}
.li-item{
    display:inline-flex;align-items:center;justify-content:center;
    width:38px;height:38px;min-width:38px;max-width:38px;   /* 固定宽高 */
    font-size:1.15em;line-height:1;
    background:rgba(255,255,255,0.05);
    border-radius:7px;cursor:pointer;transition:all 0.18s;
    border:1px solid transparent;
    overflow:hidden;            /* 关键：裁剪掉任何溢出文字/内容 */
    text-align:center;vertical-align:middle;
    -webkit-text-size-adjust:none;}
.li-item:hover{background:rgba(72,219,251,0.15);
    border-color:rgba(72,219,251,0.28);transform:scale(1.15);}
.li-item.active{background:rgba(72,219,251,0.2);border-color:#48dbfb;}

/* 操作链接 */
.ieb{cursor:pointer;font-size:0.92em;padding:2px 5px;border-radius:4px;
    display:inline-block;transition:background 0.2s;}
.ieb:hover{background:rgba(255,255,255,0.08);}
.dcl{color:#ff6b6b;text-decoration:none;font-size:0.83em;margin-left:6px;}
.dcl:hover{text-decoration:underline;}
.dll{color:#ff6b6b;text-decoration:none;font-size:0.9em;}
.dll:hover{opacity:0.7;}

/* ===== 图标编辑弹窗 ===== */
#iem{position:fixed;top:0;left:0;width:100%;height:100%;
    background:rgba(0,0,0,0.75);z-index:99999;display:flex;
    justify-content:center;align-items:center;}
.im-box{background:#161832;border-radius:16px;padding:24px;width:400px;max-width:90%;
    border:1px solid rgba(255,255,255,0.09);
    box-shadow:0 30px 70px rgba(0,0,0,0.55);}
.im-box h3{color:#feca57;margin-bottom:12px;font-size:0.98em;}
.im-grid{display:flex;flex-wrap:wrap;gap:5px;max-height:240px;overflow-y:auto;
    margin-bottom:12px;padding:10px;background:rgba(0,0,0,0.2);border-radius:8px;}
.im-grid span{
    width:34px;height:34px;min-width:34px;max-width:34px;   /* 固定尺寸 */
    display:flex;align-items:center;justify-content:center;
    font-size:1.1em;line-height:1;
    background:rgba(255,255,255,0.06);border-radius:6px;
    cursor:pointer;transition:all 0.15s;border:1px solid transparent;
    overflow:hidden;            /* 防溢出 */
    text-align:center;vertical-align:middle;}
.im-grid span:hover{background:rgba(72,219,251,0.15);border-color:rgba(72,219,251,0.28);transform:scale(1.08);}
.im-grid span.on{background:rgba(72,219,251,0.2);border-color:#48dbfb;}
.im-inp{width:100%;padding:9px;background:rgba(0,0,0,0.28);
    border:1px solid rgba(255,255,255,0.1);border-radius:8px;
    color:#fff;margin-bottom:12px;font-size:0.9em;}
.im-btns{text-align:right;display:flex;gap:8px;justify-content:flex-end;}
.ib-c{padding:6px 16px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);
    border-radius:6px;cursor:pointer;color:#aaa;font-size:0.86em;}
.ib-o{padding:6px 18px;background:linear-gradient(135deg,#00d2d3,#01a3a4);
    border:none;border-radius:6px;cursor:pointer;color:#fff;font-size:0.86em;}

/* ===== 右侧退出登录按钮 ===== */
.logout-btn{
    padding:9px 22px;
    background:linear-gradient(135deg,#ff6b6b,#ee3333);
    border:1px solid rgba(255,107,107,0.3);
    border-radius:10px;color:#fff;cursor:pointer;
    font-size:0.88em;font-weight:600;
    white-space:nowrap;transition:all 0.3s;
    box-shadow:0 3px 12px rgba(255,107,107,0.2);
}
.logout-btn:hover{
    transform:scale(1.05);
    box-shadow:0 5px 20px rgba(255,107,107,0.35);
    background:linear-gradient(135deg,#ff4444,#cc2222);
}
</style>


<!-- ====================================================================
     全局 JavaScript
     功能：Toast弹窗 + 删除确认 + 图标选择 + 操作成功提示
==================================================================== -->
<script>

/**
 * ========================================
 *  页面加载时：读取PHP消息队列并弹出Toast
 *  使用上方已定义的 showToast() 函数
 * ========================================
 * ========================================
 *  页面加载时：读取PHP消息队列并弹出Toast
 *  数据由服务端 $_SESSION['admin_msgs'] 注入
 * ========================================
 */
(function(){
    /* 服务端注入的消息数组（JSON格式） */
    var msgs = <?= json_encode($msgs_to_show, JSON_UNESCAPED_UNICODE) ?>;

    if(msgs && msgs.length > 0){
        /* 逐条弹出，每条间隔600ms */
        msgs.forEach(function(m, i){
            setTimeout(function(){
                showToast(m.text, m.type, 3000);
            }, i * 650);
        });
    }
})();


/**
 * ========================================
 *  删除确认（分类 + 链接）
 * ========================================
 */
(function(){
    var links=document.querySelectorAll('.dcl');
    for(var i=0;i<links.length;i++){
        links[i].onclick=function(){return confirm('确定删除此分类及旗下所有链接？');};
    }
    var dls=document.querySelectorAll('.dll');
    for(var j=0;j<dls.length;j++){
        dls[j].onclick=function(){return confirm('确定删除此链接？');};
    }
})();


/**
 * ========================================
 *  图标素材库 - 新增分类时选用
 * ======================================== */
/**
 * 素材库图标点击 → 更新新增分类的图标输入框
 * 优先用 data-icon 属性，失败则回退到 textContent
 */
function pickIcon(el){
    /* 取图标值：优先 data-icon，回退 textContent */
    var icon = el.getAttribute('data-icon') || el.textContent || '';
    if(!icon) return;  /* 防止空值 */

    /* ★ 更新新增分类的图标输入框 */
    var nci = document.getElementById('nciInput');
    if(nci) nci.value = icon;

    /* 高亮当前选中项 */
    var all=document.querySelectorAll('.li-item');
    for(var i=0;i<all.length;i++) all[i].className='li-item';
    el.className='li-item active';

    /* 同步到弹窗编辑输入框（如果弹窗已打开） */
    var mi=document.getElementById('iemInput');
    if(mi) mi.value=icon;
}

/* 素材库切换显隐（新增分类的图标框） */
function toggleLib(){
    var lib=document.querySelector('.ilib');
    if(lib) lib.style.display=(lib.style.display==='none')?'':'none';
}


/**
 * ========================================
 *  分类编辑弹窗（编辑：图标 + ID + 名称）
 *  从 openIconEdit 升级为 openCatEdit
 * ========================================
 */
var _editTarget='';
function openCatEdit(catName, curIcon, curId){
    _editTarget=catName;
    document.getElementById('iemCatName').textContent=catName;

    /* 填充三个字段（确保类型正确） */
    document.getElementById('iemInput').value=curIcon || '📌';       /* 图标 */
    document.getElementById('iemIdInput').value=(curId && curId!=='-') ? curId : '';  /* 排序ID */
    document.getElementById('iemNameInput').value=catName;           /* 分类名称 */

    /* 渲染图标网格（与素材库完全一致，48个） */
    var icons=['🔥','🤖','💻','🛠️','🎮','📚','🎨','🎬','🌐','☁️',
               '🔒','💰','🛒','🍔','✈️','🏋️','📱','🎵','📷','📰',
               '💼','🔧','🚀','⭐','💎','🎯','📁','🌈','⚡','💊',
               '🏠','🌍','📝','🔬','🏥','⚽','🐱','🌸','❄️','☀️',
               '🎁','📌','🔑','🎪','🍕','🚗','💡','✅'];
    var html='';
    for(var i=0;i<icons.length;i++){
        var cls=(icons[i]===curIcon)?' on':'';
        html+='<span'+cls+' onclick="modalPick(\''+icons[i]+'\')">'+icons[i]+'</span>';
    }
    document.getElementById('iemGrid').innerHTML=html;
    document.getElementById('iem').style.display='flex';
}

/* 向后兼容：旧的 openIconEdit 调用自动转发到 openCatEdit */
function openIconEdit(name,icon){ openCatEdit(name, icon, 0); }

/* 图标网格点击选中 */
function modalPick(ico){
    document.getElementById('iemInput').value=ico;
    var spans=document.querySelectorAll('#iemGrid span');
    for(var k=0;k<spans.length;k++){
        spans[k].className=(spans[k].textContent.trim()===ico)?' on':'';
    }
}
function closeIconEdit(){document.getElementById('iem').style.display='none';}

/* 提交编辑（图标 + ID + 名称 三项一起提交） */
function confirmIconEdit(){
    if(!_editTarget)return;
    document.getElementById('hfCat').value = _editTarget;           /* 原分类名 */
    document.getElementById('hfVal').value = document.getElementById('iemInput').value;     /* 新图标 */
    document.getElementById('hfId').value  = document.getElementById('iemIdInput').value;   /* 新ID */
    document.getElementById('hfName').value= document.getElementById('iemNameInput').value; /* 新名称 */
    closeIconEdit();
    document.getElementById('iconForm').submit();
}

/* =============================================
   链接编辑功能
============================================= */

/* 打开链接编辑弹窗 */
var _editLinkCat = '';
var _editLinkIdx = '';

function openLinkEdit(el){
    _editLinkCat = el.getAttribute('data-lcat') || '';
    _editLinkIdx = el.getAttribute('data-lidx') || '';

    document.getElementById('linkNameInput').value  = el.getAttribute('data-lname') || '';
    document.getElementById('linkUrlInput').value   = el.getAttribute('data-lurl') || '';
    document.getElementById('linkClickInput').value = el.getAttribute('data-lclick') || '0';

    document.getElementById('linkEditModal').style.display = 'flex';
}

/* 关闭链接编辑弹窗 */
function closeLinkEdit(){
    document.getElementById('linkEditModal').style.display = 'none';
}

/* 确认保存链接编辑 */
function confirmLinkEdit(){
    if(!_editLinkCat || _editLinkIdx === '') return;

    document.getElementById('lfCat').value   = _editLinkCat;
    document.getElementById('lfIdx').value   = _editLinkIdx;
    document.getElementById('lfName').value  = document.getElementById('linkNameInput').value;
    document.getElementById('lfUrl').value   = document.getElementById('linkUrlInput').value;
    document.getElementById('lfClick').value = document.getElementById('linkClickInput').value || '0';

    closeLinkEdit();
    document.getElementById('linkEditForm').submit();
}

/* 点击弹窗外部关闭（支持两个弹窗） */
document.addEventListener('click', function(e){
    var iem=document.getElementById('iem');
    if(iem && iem.style.display!=='none' && e.target===iem) closeIconEdit();

    var lem=document.getElementById('linkEditModal');
    if(lem && lem.style.display!=='none' && e.target===lem) closeLinkEdit();
});

/* ESC键关闭弹窗 */
document.addEventListener('keydown',function(e){
    if(e.keyCode===27){
        closeIconEdit();
        closeLinkEdit();
    }
});

</script>
