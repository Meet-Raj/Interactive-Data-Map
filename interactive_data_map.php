<?php

function enqueue_amcharts()
{
    wp_enqueue_script('amcharts-core', 'https://www.amcharts.com/lib/4/core.js', array(), '4.10.19', true);
    wp_enqueue_script('amcharts-maps', 'https://www.amcharts.com/lib/4/maps.js', array('amcharts-core'), '4.10.19', true);
    wp_enqueue_script('amcharts-geodata', 'https://www.amcharts.com/lib/4/geodata/worldLow.js', array('amcharts-core', 'amcharts-maps'), '4.10.19', true);
    wp_enqueue_script('amcharts-theme', 'https://www.amcharts.com/lib/4/themes/animated.js', array('amcharts-core'), '4.10.19', true);
    wp_enqueue_script('amcharts-countries', 'https://cdn.amcharts.com/lib/5/geodata/data/countries2.js', array('amcharts-core'), '4.10.19', true);
    wp_enqueue_script('jquery');

}
add_action('wp_enqueue_scripts', 'enqueue_amcharts');

function interactive_data_map_style()
{
    wp_enqueue_style('interactivedatamap', plugin_dir_url(__FILE__) . 'css/interactivedatamap.css', array(), '1.0');
}
add_action('mepr_account_nav', 'interactive_data_map_style');

function create_idma_post_type()
{
    $labels = array(
        'name' => __('Interactive Data Map'),
        'singular_name' => __('Interactive Data Map Article'),
        'add_new' => __('Add New Article'),
        'add_new_item' => __('Add New Article'),
        'edit_item' => __('Edit Article'),
        'new_item' => __('New Article'),
        'view_item' => __('View Article'),
        'search_items' => __('Search Article'),
        'not_found' => __('No articles found'),
        'not_found_in_trash' => __('No articles found in Trash'),
        'parent_item_colon' => '',
        'menu_name' => __('Interactive Data Map'),
    );

    register_post_type(
        'interactive_data_map',
        array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'query_var' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'taxonomies' => array('country', 'type'),
            'menu_icon' => 'dashicons-location',
        )
    );
}
add_action('init', 'create_idma_post_type');

function register_country_taxonomy()
{
    $labels = array(
        'name' => _x('Country', 'taxonomy general name'),
        'singular_name' => _x('Country', 'taxonomy singular name'),
        'search_items' => __('Search Country'),
        'all_items' => __('All Country'),
        'parent_item' => __('Parent Country'),
        'parent_item_colon' => __('Parent Country:'),
        'edit_item' => __('Edit Country'),
        'update_item' => __('Update Country'),
        'add_new_item' => __('Add New Country'),
        'new_item_name' => __('New Country Name'),
        'menu_name' => __('Country'),
    );

    register_taxonomy(
        'country',
        'interactive_data_map',
        array(
            'labels' => $labels,
            'hierarchical' => true,
            'show_in_rest' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
        )
    );
}
add_action('init', 'register_country_taxonomy');

function create_custom_taxonomy()
{
    $labels = array(
        'name' => _x('Types', 'taxonomy general name'),
        'singular_name' => _x('Type', 'taxonomy singular name'),
        'search_items' => __('Search Types'),
        'all_items' => __('All Types'),
        'parent_item' => __('Parent Type'),
        'parent_item_colon' => __('Parent Type:'),
        'edit_item' => __('Edit Type'),
        'update_item' => __('Update Type'),
        'add_new_item' => __('Add New Type'),
        'new_item_name' => __('New Type Name'),
        'menu_name' => __('Types'),
    );

    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'custom-type'),
        'rest_base' => 'custom-types',
    );

    register_taxonomy('type', 'interactive_data_map', $args);
}
add_action('init', 'create_custom_taxonomy', 0);

function main_menu($user)
{
    $rdmenu_active = (isset($_GET['action']) && $_GET['action'] == '#') ? 'mepr-nav-tab' : '';
    $user_id = $user->ID;
    $has_specific_memberships = has_specific_memberships($user_id);

    if ($has_specific_memberships) {
        ?>
        <span class="mepr-nav-item knowledge-base  <?php echo $rdmenu_active; ?>">
            <a href="<?php echo esc_url(home_url('/account/?action=#')); ?> ">R&D Data</a>
        </span>
        <?php
    }
}
add_action('mepr_account_nav', 'main_menu', 100);

function interactive_data_map_tab($user)
{
    $map_active = (isset($_GET['action']) && $_GET['action'] == 'interactive-data-map') ? 'mepr-active-nav-tab' : '';
    $user_id = $user->ID;
    $has_specific_memberships = has_specific_memberships($user_id);
    if ($has_specific_memberships) {
        ?>
        <span class="mepr-nav-item interactive-data-map  <?php echo $map_active; ?>">
            <a href="<?php echo esc_url(home_url('/account/?action=interactive-data-map')); ?>">Interactive Data Map</a>
        </span>
        <?php
    }
}
add_action('mepr_account_nav', 'interactive_data_map_tab', 120);

function has_specific_memberships($user_id)
{
    global $wpdb;
    $membership_ids_to_check = array(17, 18);
    $memberships = $wpdb->get_col($wpdb->prepare("
        SELECT memberships
        FROM {$wpdb->prefix}mepr_members
        WHERE user_id = %d AND memberships IN (" . implode(',', $membership_ids_to_check) . ")
    ", $user_id));
    return !empty($memberships);
}

function interactive_data_map_content($action)
{
    if ($action == 'interactive-data-map') {
        ?>
        <div class="filter-container">
            <select id="country-dropdown">
                <option value="">Select Country</option>
                <?php
                $countries = get_terms(array('taxonomy' => 'country', 'hide_empty' => false));
                foreach ($countries as $country) {
                    echo '<option value="' . esc_attr($country->slug) . '">' . esc_html($country->name) . '</option>';
                }
                ?>
            </select>

            <div class="type-taxonomy">
                <?php
                $types = get_terms(array('taxonomy' => 'type', 'hide_empty' => false));
                if (!empty($types) && !is_wp_error($types)) {
                    echo '<ul>';
                    foreach ($types as $type) {
                        echo '<li><a href="#" class="type-link" data-type="' . esc_attr($type->slug) . '">' . esc_html($type->name) . '</a></li>';
                    }
                    echo '</ul>';
                } else {
                    echo 'No terms found.';
                }
                ?>
            </div>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
                crossorigin="anonymous" referrerpolicy="no-referrer" />

            <script>
                        jQuery(document).ready(function ($) {
                    var map = am4core.create("map", am4maps.MapChart);
                    map.geodata = am4geodata_worldLow;
                    map.projection = new am4maps.projections.Miller();
                    var polygonSeries = map.series.push(new am4maps.MapPolygonSeries());
                    polygonSeries.exclude = ["AQ"];
                    polygonSeries.useGeodata = true;
                    var polygonTemplate = polygonSeries.mapPolygons.template;
                    polygonTemplate.fill = am4core.color("#808080");
                    polygonTemplate.stroke = am4core.color("#ffffff");
                    polygonTemplate.tooltipHTML = '<b>{name}</b>';
                    var polygonSeries = map.series.push(new am4maps.MapPolygonSeries());
                    polygonSeries.calculateVisualCenter = true;
                    polygonTemplate.tooltipPosition = "fixed";
                    polygonSeries.tooltip.label.interactionsEnabled = true;
                    polygonSeries.tooltip.keepTargetHover = true;
                    var hs = polygonTemplate.states.create("hover");
                    hs.properties.fill = am4core.color("#367B25");

                var map_data =
                {
                    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    nonce: '<?php echo wp_create_nonce('update_map_nonce'); ?>',
                    countryData: <?php echo json_encode(get_country_data()); ?>,
                };


                var selectedCountry = '';
            


                    var polygonSeries = '';
                    function updateMap(country, type) {
                        selectedCountry = country;
                        $.ajax({
                            url: map_data.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'update_map',
                                nonce: map_data.nonce,
                                country: country,
                                type: type,
                            },
                            success: function (response) {
                                try {
                                    var data = JSON.parse(response);
                                    if (Array.isArray(data)) {
                                        polygonSeries.data = data.map(function (item) {
                                            $countryCode = item.code;
                                            am4core.useTheme(am4themes_animated);
                                            var map = am4core.create("map", am4maps.MapChart);
                                            map.geodata = am4geodata_worldLow;
                                            map.projection = new am4maps.projections.Miller();
                                            var polygonSeries = map.series.push(new am4maps.MapPolygonSeries());
                                            polygonSeries.exclude = ["AQ"];
                                            polygonSeries.useGeodata = true;
                                            var polygonTemplate = polygonSeries.mapPolygons.template;
                                            polygonTemplate.tooltipText = "{name}";
                                            polygonTemplate.fill = am4core.color("#808080");
                                            var hs = polygonTemplate.states.create("hover");
                                            hs.properties.fill = am4core.color("#1cad75");
                                            var as = polygonTemplate.states.create("active");
                                            as.properties.fill = am4core.color("#367B25");
                                            var postLink = '<?php echo esc_url(home_url('/account/?action=post-map&post_id=')); ?>' + item.id;
                                            polygonSeries.calculateVisualCenter = true;
                                            polygonTemplate.tooltipPosition = "fixed";
                                            polygonSeries.tooltip.label.interactionsEnabled = true;
                                            polygonSeries.tooltip.keepTargetHover = true;
                                            polygonTemplate.tooltipHTML = '<b>{name}</b>';
                                            map.events.on("ready", function (ev) {
                                                var countryCode = item.code;
                                                var country = polygonSeries.getPolygonById(countryCode);
                                                if (country) {
                                                    var zoomLevel = 5;
                                                    map.zoomToMapObject(country, zoomLevel);
                                                    setTimeout(function () {
                                                        country.isActive = true;
                                                        var tooltipContent = '<h5 class="country-name">{name} <span><button type="button" class="remove-tooltip" onClick="window.location.reload();"><i class="far fa-circle-xmark"></i></button></span></h5><a href="' + postLink + '" data-post-id="' + item.id + '">' + item.title + '</a>';
                                                        country.tooltipHTML = tooltipContent;
                                                        country.showTooltip();
                                                    }, 1000);
                                                }
                                            });
                                        });
                                    }
                                    else {
                                        console.error('Invalid data format:', data);
                                    }
                                }
                                catch (error) {
                                    console.error('Error parsing JSON:', error);
                                }
                            },
                        });
                    }

                    $('#country-dropdown').on('change', function () {
                        var selectedCountry = $(this).val();
                        updateMap(selectedCountry);
                        if (polygonSeries.mapPolygons) {
                            polygonSeries.mapPolygons.each(function (mapPolygon) {
                                if (mapPolygon.dataItem.dataContext.country === selectedCountry) {
                                    mapPolygon.isHover = true;
                                } else {
                                    mapPolygon.isHover = false;
                                }
                            });
                        }

                    });

                $(".type-link").on("click", function (e) {
                    e.preventDefault();
                    var type = $(this).data("type");
                    $.ajax({
                        url: map_data.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'type_update',
                            nonce: '<?php echo wp_create_nonce('type_update_nonce'); ?>',
                            type: type,
                        },
                        success: function (response) {
                            try {
                                var data = JSON.parse(response);
                                if (Array.isArray(data)) {
                                    // Initialize the map outside the loop to avoid recreating it multiple times
                                    var map = am4core.create("map", am4maps.MapChart);
                                    map.geodata = am4geodata_worldLow;
                                    map.projection = new am4maps.projections.Miller();
                                    var polygonSeries = map.series.push(new am4maps.MapPolygonSeries());
                                    polygonSeries.useGeodata = true;
                                    var polygonTemplate = polygonSeries.mapPolygons.template;
                                    polygonTemplate.tooltipText = "{name} test";
                                    polygonTemplate.fill = am4core.color("gray");
                                    var hs = polygonTemplate.states.create("hover");
                                    hs.properties.fill = am4core.color("#367B25");
                                    polygonSeries.exclude = ["AQ"];
                                    var polygonData = [];

                                    polygonSeries.events.on("inited", function () {
                                        data.forEach(function (item) {
                                            var countryCodeArray = item.code;
                                            countryCodeArray.forEach(function (countryCode) {
                                                var countryPolygon = polygonSeries.getPolygonById(countryCode);
                                                if (countryPolygon) {
                                                    var countryName = countryPolygon.dataItem.dataContext.name;
                                                    countryPolygon.isActive = true;
                                                    countryPolygon.tooltipText = "{name}";
                                                    var postLink = '<?php echo esc_url(home_url('/account/?action=post-map&post_id=')); ?>' + item.id;
                                                    var tooltipContent = '<h5 class="country-name">{name} <span><button type="button" class="remove-tooltip" onClick="window.location.reload();"><i class="far fa-circle-xmark"></i></button></span></h5><a href="' + postLink + '" data-post-id="' + item.id + '">' + item.title + '</a>';
                                                    countryPolygon.tooltipHTML = tooltipContent;
                                                    countryPolygon.tooltipPosition = "fixed";
                                                    countryPolygon.showTooltip();
                                                    polygonData.push({
                                                        "id": countryCode,
                                                        "name": countryName,
                                                        "value": 100,
                                                        "fill": am4core.color("green")
                                                    });
                                                }
                                            });
                                        });

                                        polygonSeries.data = polygonData;
                                        polygonTemplate.propertyFields.fill = "fill";
                                    });
                                } else {
                                    console.error('Invalid data format:', data);
                                }
                            } catch (error) {
                                console.error('Error parsing JSON:', error);
                            }
                        },
                    });
                });




                });

            </script>

        </div>
        <div class="map-container">
            <div id="map" style="height: 700px;"></div>
        </div>
        <?php
    }
}



add_action('mepr_account_nav_content', 'interactive_data_map_content');

function get_country_post_titles() {
    check_ajax_referer('get_country_post_titles_nonce', 'nonce');

    wp_send_json($countryData);
}

add_action('wp_ajax_get_country_post_titles', 'get_country_post_titles');
add_action('wp_ajax_nopriv_get_country_post_titles', 'get_country_post_titles');


function get_country_data()
{
    $countries = get_terms(array('taxonomy' => 'country', 'hide_empty' => false));
    $country_data = array();

    foreach ($countries as $country) {
        $country_data[] = array(
            'slug' => esc_attr($country->slug),
            'name' => esc_html($country->name),
            'articles' => get_country_articles($country->slug),
        );
    }
    return $country_data;
}

function get_country_articles($country_slug)
{
    $args = array(
        'post_type' => 'interactive_data_map',
        'tax_query' => array(
            'relation' => 'AND',
            array(
                'taxonomy' => 'country',
                'field' => 'slug',
                'terms' => $country_slug,
            ),
        ),
    );
    $query = new WP_Query($args);
    $articles = array();
    while ($query->have_posts()) {
        $query->the_post();
        $articles[] = array(
            'id' => get_the_ID(),
            'title' => get_the_title(),
        );
    }

    wp_reset_postdata();

    return $articles;
}

function update_map_data()
{
    check_ajax_referer('update_map_nonce', 'nonce');
    $country = sanitize_text_field($_POST['country']);
    $country_codes = array(
        'afghanistan' => 'AF',
        'albania' => 'AL',
        'algeria' => 'DZ',
        'andorra' => 'AD',
        'angola' => 'AO',
        'antigua' => 'AG',
        'argentina' => 'AR',
        'armenia' => 'AM',
        'australia' => 'AU',
        'austria' => 'AT',
        'azerbaijan' => 'AZ',
        'bahamas' => 'BS',
        'bahrain' => 'BH',
        'bangladesh' => 'BD',
        'barbados' => 'BB',
        'belarus' => 'BY',
        'belgium' => 'BE',
        'belize' => 'BZ',
        'benin' => 'BJ',
        'bhutan' => 'BT',
        'bolivia' => 'BO',
        'bosnia_and_herzegovina' => 'BA',
        'botswana' => 'BW',
        'brazil' => 'BR',
        'brunei' => 'BN',
        'bulgaria' => 'BG',
        'burkina_faso' => 'BF',
        'burundi' => 'BI',
        'cambodia' => 'KH',
        'cameroon' => 'CM',
        'canada' => 'CA',
        'cape_verde' => 'CV',
        'central_african_republic' => 'CF',
        'chad' => 'TD',
        'chile' => 'CL',
        'china' => 'CN',
        'colombia' => 'CO',
        'comoros' => 'KM',
        'congo_congo-brazzaville' => 'CG',
        'congo_congo-kinshasa' => 'CD',
        'costa_rica' => 'CR',
        'croatia' => 'HR',
        'cuba' => 'CU',
        'cyprus' => 'CY',
        'czechia_czech_republic' => 'CZ',
        'denmark' => 'DK',
        'djibouti' => 'DJ',
        'dominica' => 'DM',
        'dominican_republic' => 'DO',
        'ecuador' => 'EC',
        'egypt' => 'EG',
        'el_salvador' => 'SV',
        'equatorial_guinea' => 'GQ',
        'eritrea' => 'ER',
        'estonia' => 'EE',
        'ethiopia' => 'ET',
        'fiji' => 'FJ',
        'finland' => 'FI',
        'france' => 'FR',
        'gabon' => 'GA',
        'gambia' => 'GM',
        'georgia' => 'GE',
        'germany' => 'DE',
        'ghana' => 'GH',
        'greece' => 'GR',
        'grenada' => 'GD',
        'guatemala' => 'GT',
        'guinea' => 'GN',
        'guinea-bissau' => 'GW',
        'guyana' => 'GY',
        'haiti' => 'HT',
        'honduras' => 'HN',
        'hungary' => 'HU',
        'iceland' => 'IS',
        'india' => 'IN',
        'indonesia' => 'ID',
        'iran' => 'IR',
        'iraq' => 'IQ',
        'ireland' => 'IE',
        'israel' => 'IL',
        'italy' => 'IT',
        'ivory_coast' => 'CI',
        'jamaica' => 'JM',
        'japan' => 'JP',
        'jordan' => 'JO',
        'kazakhstan' => 'KZ',
        'kenya' => 'KE',
        'kiribati' => 'KI',
        'korea_north' => 'KP',
        'korea_south' => 'KR',
        'kuwait' => 'KW',
        'kyrgyzstan' => 'KG',
        'laos' => 'LA',
        'latvia' => 'LV',
        'lebanon' => 'LB',
        'lesotho' => 'LS',
        'liberia' => 'LR',
        'libya' => 'LY',
        'liechtenstein' => 'LI',
        'lithuania' => 'LT',
        'luxembourg' => 'LU',
        'macedonia' => 'MK',
        'madagascar' => 'MG',
        'malawi' => 'MW',
        'malaysia' => 'MY',
        'maldives' => 'MV',
        'mali' => 'ML',
        'malta' => 'MT',
        'marshall_islands' => 'MH',
        'mauritania' => 'MR',
        'mauritius' => 'MU',
        'mexico' => 'MX',
        'micronesia' => 'FM',
        'moldova' => 'MD',
        'monaco' => 'MC',
        'mongolia' => 'MN',
        'montenegro' => 'ME',
        'morocco' => 'MA',
        'mozambique' => 'MZ',
        'myanmar_formerly_burma' => 'MM',
        'namibia' => 'NA',
        'nauru' => 'NR',
        'nepal' => 'NP',
        'netherlands' => 'NL',
        'new_zealand' => 'NZ',
        'nicaragua' => 'NI',
        'niger' => 'NE',
        'nigeria' => 'NG',
        'norway' => 'NO',
        'oman' => 'OM',
        'pakistan' => 'PK',
        'palau' => 'PW',
        'palestine_state' => 'PS',
        'panama' => 'PA',
        'papua_new_guinea' => 'PG',
        'paraguay' => 'PY',
        'peru' => 'PE',
        'philippines' => 'PH',
        'poland' => 'PL',
        'portugal' => 'PT',
        'qatar' => 'QA',
        'romania' => 'RO',
        'russia' => 'RU',
        'rwanda' => 'RW',
        'saint_kitts_and_nevis' => 'KN',
        'saint_lucia' => 'LC',
        'saint_vincent_and_the_grenadines' => 'VC',
        'samoa' => 'WS',
        'san_marino' => 'SM',
        'sao_tome_and_principe' => 'ST',
        'saudi_arabia' => 'SA',
        'senegal' => 'SN',
        'serbia' => 'RS',
        'seychelles' => 'SC',
        'sierra_leone' => 'SL',
        'singapore' => 'SG',
        'slovakia' => 'SK',
        'slovenia' => 'SI',
        'solomon_islands' => 'SB',
        'somalia' => 'SO',
        'south_africa' => 'ZA',
        'spain' => 'ES',
        'sri_lanka' => 'LK',
        'sudan' => 'SD',
        'suriname' => 'SR',
        'swaziland' => 'SZ',
        'sweden' => 'SE',
        'switzerland' => 'CH',
        'syria' => 'SY',
        'taiwan' => 'TW',
        'tajikistan' => 'TJ',
        'tanzania' => 'TZ',
        'thailand' => 'TH',
        'timor-leste' => 'TL',
        'puerto_rico' => 'PR',
        'togo' => 'TG',
        'tonga' => 'TO',
        'trinidad_and_tobago' => 'TT',
        'tunisia' => 'TN',
        'turkey' => 'TR',
        'turkmenistan' => 'TM',
        'tuvalu' => 'TV',
        'uganda' => 'UG',
        'ukraine' => 'UA',
        'united_arab_emirates' => 'AE',
        'united_kingdom' => 'GB',
        'usa' => 'US',
        'uruguay' => 'UY',
        'uzbekistan' => 'UZ',
        'vanuatu' => 'VU',
        'vatican_city' => 'VA',
        'venezuela' => 'VE',
        'vietnam' => 'VN',
        'yemen' => 'YE',
        'zambia' => 'ZM',
        'zimbabwe' => 'ZW',

    );

    $code = isset($country_codes[strtolower($country)]) ? $country_codes[strtolower($country)] : '';

    $args = array(
        'post_type' => 'interactive_data_map',
        'tax_query' => array(
            'relation' => 'or',
            array(
                'taxonomy' => 'country',
                'field' => 'slug',
                'terms' => $country,
            ),
        ),
    );

    $country_term = get_term_by('slug', $country, 'country');
    $data = array();
    if ($country_term) {
        $query = new WP_Query($args);

        while ($query->have_posts()) {
            $query->the_post();
            $article_title = get_the_title();
            $post_url = get_permalink();
            $data[] = array(
                'id' => get_the_ID(),
                'title' => $article_title,
                'country' => $country,
                'code' => $code,
                'url' => $post_url,
            );
        }
        wp_reset_postdata();
    }
    wp_die(json_encode($data));
}

add_action('wp_ajax_update_map', 'update_map_data');
add_action('wp_ajax_nopriv_update_map', 'update_map_data');


function type_update_data()
{
    check_ajax_referer('type_update_nonce', 'nonce');
    $type = sanitize_text_field($_POST['type']);
    $country_codes = array(
        'afghanistan' => 'AF',
        'albania' => 'AL',
        // ... (other country codes)
    );

    $code = isset($country_codes[strtolower($type)]) ? $country_codes[strtolower($type)] : '';

    $args = array(
        'post_type' => 'interactive_data_map',
        'tax_query' => array(
            'relation' => 'or',
            array(
                'taxonomy' => 'type',
                'field' => 'slug',
                'terms' => $type,
            ),
        ),
    );

    $type_term = get_term_by('slug', $type, 'type');
    $data = array();
    $post_data = array();
    if ($type_term) {
        $query = new WP_Query($args);

        $countrys = array(); // Move this line outside the loop

        while ($query->have_posts()) {
            $query->the_post();
            $article_title = get_the_title();
            $post_url = get_permalink();
            $post_id = get_the_ID();
            $post_data[] = array(
                'article_title' => $article_title,
                'post_url' => $post_url,
                'post_id' => $post_id,
            );

            $taxonomy = 'country'; // Replace 'your_custom_taxonomy' with the name of your custom taxonomy

            $terms = get_the_terms($post_id, $taxonomy);

            if ($terms && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $countrys[] = $term->slug;
                }
            }
        }

        $uniqueArray = array_unique($countrys);
        $uniqueArray = array_values($uniqueArray);

        $country_codes = array(
            'afghanistan' => 'AF',
            'albania' => 'AL',
            'algeria' => 'DZ',
            'andorra' => 'AD',
            'angola' => 'AO',
            'antigua' => 'AG',
            'argentina' => 'AR',
            'armenia' => 'AM',
            'australia' => 'AU',
            'austria' => 'AT',
            'azerbaijan' => 'AZ',
            'bahamas' => 'BS',
            'bahrain' => 'BH',
            'bangladesh' => 'BD',
            'barbados' => 'BB',
            'belarus' => 'BY',
            'belgium' => 'BE',
            'belize' => 'BZ',
            'benin' => 'BJ',
            'bhutan' => 'BT',
            'bolivia' => 'BO',
            'bosnia_and_herzegovina' => 'BA',
            'botswana' => 'BW',
            'brazil' => 'BR',
            'brunei' => 'BN',
            'bulgaria' => 'BG',
            'burkina_faso' => 'BF',
            'burundi' => 'BI',
            'cambodia' => 'KH',
            'cameroon' => 'CM',
            'canada' => 'CA',
            'cape_verde' => 'CV',
            'central_african_republic' => 'CF',
            'chad' => 'TD',
            'chile' => 'CL',
            'china' => 'CN',
            'colombia' => 'CO',
            'comoros' => 'KM',
            'congo_congo-brazzaville' => 'CG',
            'congo_congo-kinshasa' => 'CD',
            'costa_rica' => 'CR',
            'croatia' => 'HR',
            'cuba' => 'CU',
            'cyprus' => 'CY',
            'czechia_czech_republic' => 'CZ',
            'denmark' => 'DK',
            'djibouti' => 'DJ',
            'dominica' => 'DM',
            'dominican_republic' => 'DO',
            'ecuador' => 'EC',
            'egypt' => 'EG',
            'el_salvador' => 'SV',
            'equatorial_guinea' => 'GQ',
            'eritrea' => 'ER',
            'estonia' => 'EE',
            'ethiopia' => 'ET',
            'fiji' => 'FJ',
            'finland' => 'FI',
            'france' => 'FR',
            'gabon' => 'GA',
            'gambia' => 'GM',
            'georgia' => 'GE',
            'germany' => 'DE',
            'ghana' => 'GH',
            'greece' => 'GR',
            'grenada' => 'GD',
            'guatemala' => 'GT',
            'guinea' => 'GN',
            'guinea-bissau' => 'GW',
            'guyana' => 'GY',
            'haiti' => 'HT',
            'honduras' => 'HN',
            'hungary' => 'HU',
            'iceland' => 'IS',
            'india' => 'IN',
            'indonesia' => 'ID',
            'iran' => 'IR',
            'iraq' => 'IQ',
            'ireland' => 'IE',
            'israel' => 'IL',
            'italy' => 'IT',
            'ivory_coast' => 'CI',
            'jamaica' => 'JM',
            'japan' => 'JP',
            'jordan' => 'JO',
            'kazakhstan' => 'KZ',
            'kenya' => 'KE',
            'kiribati' => 'KI',
            'korea_north' => 'KP',
            'korea_south' => 'KR',
            'kuwait' => 'KW',
            'kyrgyzstan' => 'KG',
            'laos' => 'LA',
            'latvia' => 'LV',
            'lebanon' => 'LB',
            'lesotho' => 'LS',
            'liberia' => 'LR',
            'libya' => 'LY',
            'liechtenstein' => 'LI',
            'lithuania' => 'LT',
            'luxembourg' => 'LU',
            'macedonia' => 'MK',
            'madagascar' => 'MG',
            'malawi' => 'MW',
            'malaysia' => 'MY',
            'maldives' => 'MV',
            'mali' => 'ML',
            'malta' => 'MT',
            'marshall_islands' => 'MH',
            'mauritania' => 'MR',
            'mauritius' => 'MU',
            'mexico' => 'MX',
            'micronesia' => 'FM',
            'moldova' => 'MD',
            'monaco' => 'MC',
            'mongolia' => 'MN',
            'montenegro' => 'ME',
            'morocco' => 'MA',
            'mozambique' => 'MZ',
            'myanmar_formerly_burma' => 'MM',
            'namibia' => 'NA',
            'nauru' => 'NR',
            'nepal' => 'NP',
            'netherlands' => 'NL',
            'new_zealand' => 'NZ',
            'nicaragua' => 'NI',
            'niger' => 'NE',
            'nigeria' => 'NG',
            'norway' => 'NO',
            'oman' => 'OM',
            'pakistan' => 'PK',
            'palau' => 'PW',
            'palestine_state' => 'PS',
            'panama' => 'PA',
            'papua_new_guinea' => 'PG',
            'paraguay' => 'PY',
            'peru' => 'PE',
            'philippines' => 'PH',
            'poland' => 'PL',
            'portugal' => 'PT',
            'qatar' => 'QA',
            'romania' => 'RO',
            'russia' => 'RU',
            'rwanda' => 'RW',
            'saint_kitts_and_nevis' => 'KN',
            'saint_lucia' => 'LC',
            'saint_vincent_and_the_grenadines' => 'VC',
            'samoa' => 'WS',
            'san_marino' => 'SM',
            'sao_tome_and_principe' => 'ST',
            'saudi_arabia' => 'SA',
            'senegal' => 'SN',
            'serbia' => 'RS',
            'seychelles' => 'SC',
            'sierra_leone' => 'SL',
            'singapore' => 'SG',
            'slovakia' => 'SK',
            'slovenia' => 'SI',
            'solomon_islands' => 'SB',
            'somalia' => 'SO',
            'south_africa' => 'ZA',
            'spain' => 'ES',
            'sri_lanka' => 'LK',
            'sudan' => 'SD',
            'suriname' => 'SR',
            'swaziland' => 'SZ',
            'sweden' => 'SE',
            'switzerland' => 'CH',
            'syria' => 'SY',
            'taiwan' => 'TW',
            'tajikistan' => 'TJ',
            'tanzania' => 'TZ',
            'thailand' => 'TH',
            'timor-leste' => 'TL',
            'puerto_rico' => 'PR',
            'togo' => 'TG',
            'tonga' => 'TO',
            'trinidad_and_tobago' => 'TT',
            'tunisia' => 'TN',
            'turkey' => 'TR',
            'turkmenistan' => 'TM',
            'tuvalu' => 'TV',
            'uganda' => 'UG',
            'ukraine' => 'UA',
            'united_arab_emirates' => 'AE',
            'united_kingdom' => 'GB',
            'usa' => 'US',
            'uruguay' => 'UY',
            'uzbekistan' => 'UZ',
            'vanuatu' => 'VU',
            'vatican_city' => 'VA',
            'venezuela' => 'VE',
            'vietnam' => 'VN',
            'yemen' => 'YE',
            'zambia' => 'ZM',
            'zimbabwe' => 'ZW',
        );

        $mappedValues = array();

        foreach ($uniqueArray as $term) {
            if (isset($country_codes[$term])) {
                $mappedValues[] = $country_codes[$term];
            }
        }

        // print_r($mappedValues);

        $data[] = array(
            'code' => $mappedValues,
            'post_data' => $post_data,
        );

        wp_reset_postdata();
    }
    // wp_die();
    wp_die(json_encode($data));

}

add_action('wp_ajax_type_update', 'type_update_data');
add_action('wp_ajax_nopriv_type_update', 'type_update_data');





function singe_post_map($user)
{
    $singlemap = (isset($_GET['action']) && $_GET['action'] == 'post-map') ? 'mepr-active-nav-tab' : '';
    ?>
    <span class="mepr-nav-item map-base  <?php echo $singlemap; ?>">
        <a href="<?php echo esc_url(home_url('/account/?action=post-map')); ?> " style="display: none;"></a>
    </span>
    <?php
}
add_action('mepr_account_nav', 'singe_post_map');

function map_single_post($action)
{
    if ($action == 'post-map') {
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

        if ($post_id > 0) {
            $post = get_post($post_id);

            if ($post instanceof WP_Post) {
                echo '<a href="' . esc_url(home_url('/account/?action=interactive-data-map')) . '" class="back-button btn-green mb-4"><i class="fas fa-arrow-left-long"></i> Back to Interactive Data Map</a>';
                echo '<div class="knowledge-post-content">';
                echo '<h2 class="post-title">' . esc_html($post->post_title) . '</h2>';
                echo '<ul class="post-info">';
                echo '<li><i class="fas fa-clock"></i> <b>Created On:</b> ' . esc_html(get_the_time('F j, Y', $post_id)) . '</li>';

                $categories = get_the_category($post_id);
                if (!empty($categories)) {
                    echo '<li><i class="fas fa-folder"></i> <b>Category:</b> ' . esc_html($categories[0]->name) . '</li>';
                }
                echo '</ul>';

                if (has_post_thumbnail($post_id)) {
                    echo '<div class="featured-image">';
                    echo get_the_post_thumbnail($post_id);
                    echo '</div>';
                }

                $post_content = $post->post_content;
                echo '<div class="post-content">' . apply_filters('get_the_content', $post_content) . '</div>';
                $blocks = parse_blocks($post_content);
                foreach ($blocks as $block) {
                    // Check if the block is a YouTube block
                    if ($block['blockName'] === 'core/embed-youtube') {
                        $youtube_url = $block['attrs']['url'];

                        $embed_code = wp_oembed_get($youtube_url);
                        if ($embed_code) {
                            echo '<div class="embedded-media">' . $embed_code . '</div>';
                            echo '<p><b>YouTube URL:</b> ' . esc_url($youtube_url) . '</p>';
                        }
                    }
                }

                echo '</div>';
            } else {
                echo '<p>No post found</p>';
            }
        }
    }
}
add_action('mepr_account_nav_content', 'map_single_post');