<?php
/*
Plugin Name: Posts Counts Stats
Description: Display yearly and monthly posts counts in WordPress admin settings in a hierarchical format + dashboard widget.
Version: 1.3.0
Author: Marcin Bielak - https://github.com/bieli/posts-counts-stats--wp-plugin
*/

if (!defined('ABSPATH')) {
    exit;
}

class Post_Count_Stats {
    public function __construct() {
	    add_action('admin_menu', [$this, 'add_settings_page']);
	    add_action('wp_dashboard_setup', [$this, 'add_dashboard_widget']);
    }

    public function add_settings_page() {
        add_options_page(
            'Posts Counts Stats',
            'Posts Counts Stats',
            'manage_options',
            'posts-counts-stats',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page($year_only = false) {
	    global $wpdb;

	    if ($year_only == true) {
	      $wd = '100';
	    } else {
		$wd = '12';
            }

        echo '<div class="wrap">';
        echo '<h1>Posts Count Stats</h1>';

        $all_posts = 0;

        // Get post counts grouped by year and month
        $stats = $wpdb->get_results("
            SELECT 
                YEAR(post_date) AS year,
                MONTH(post_date) AS month,
                COUNT(ID) AS post_count
            FROM $wpdb->posts
            WHERE post_status = 'publish' AND post_type = 'post'
            GROUP BY YEAR(post_date), MONTH(post_date)
            ORDER BY YEAR(post_date) ASC, MONTH(post_date) ASC
        ");

        // Organize data by year and month
        $organized_stats = [];
        foreach ($stats as $stat) {
            $year = $stat->year;
            $month = $stat->month;
            $post_count = $stat->post_count;

            if (!isset($organized_stats[$year])) {
                $organized_stats[$year] = array_fill(1, 12, 0); // Initialize all 12 months to zero
            }

            $organized_stats[$year][$month] = $post_count;
        }

        // Display stats
        foreach ($organized_stats as $year => $months) {
            echo '<h2 style="width: ' . $wd . '%;">Year: <span style="float: right;">' . esc_html($year) . '</span></h2>';
            $yearly_total = array_sum($months);
            echo '<h4 style="margin-left: 0%; width: ' . $wd . '%;">Total Posts: <span style="float: right;">' . esc_html($yearly_total) . '</span></h4>';
	    if ($year_only == false) {
	    echo '<ul style="margin-left: 2%; width: 10%;">';
            foreach ($months as $month => $post_count) {
                $month_name = date('F', mktime(0, 0, 0, $month, 1));
                if ($post_count == 0) {
                    $c_color = '#afafaf';
                    $shape = ''; //'■ ';
                    $s_color = 'red';
                    $post_count_link = esc_html($post_count);
                } else {
                    if ($post_count > 4) {
                        $s_color = 'green';
                        $shape = ''; //'▴';
                    } else {
                        $s_color = 'red';
                        $shape = ''; //'▾';
                    }
                    $c_color = '#000000';
                    // Generate link for filtering posts in the admin post list
                    $filter_url = admin_url('edit.php?post_type=post') . '&m=' . sprintf('%04d%02d', $year, $month);
                    $post_count_link = '<a href="' . esc_url($filter_url) . '">' . esc_html($post_count) . '</a>';
                }


                echo '<li>' . esc_html($month_name) . ': 
<span style="float: right; color: ' . $s_color . '">' . $shape . '</span> <span style="margin-right: 1%;float: right; color: ' . $c_color . '">' . $post_count_link . '</span> </li>';
	      }
	    }
	$all_posts += array_sum($organized_stats[$year]);
	    
            echo '</ul><hr />';
        }

        echo '</div>';
	echo '<h2 style="width: ' . $wd . '%;">All posts count: <span style="float: right;">' . $all_posts . '</span></h2>';

        // Simple bar graph display
        echo '<div style="margin-top:5%;">';
        echo '<h3>Yearly Posts Counts Chart</h3>';
        echo '<div style="display:flex; align-items: flex-end; height:200px; border:1px solid #ccc; padding:10px;">';

        foreach ($organized_stats as $year => $months) {
	    $post_count = array_sum($organized_stats[$year]);

            echo '<div style="flex:1; margin:0 5px; text-align:center;">';
            echo $post_count . '<div style="border: 1px #afafaf solid; background-color: lightblue; height:' . esc_attr($post_count) . 'px; width:100%;"></div>';
            echo '<small>' . esc_attr($year) . '</small>';
            echo '</div>';
        }

        echo '</div>';
        echo '</div>';
    }

    public function render_widget() {
	$this->render_settings_page($year_only = true);
    }

    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'posts_counts_stats_widget',
            'Yearly Post Counts Summary <a href="' . esc_url(admin_url('options-general.php?page=posts-counts-stats')) . '" class="button" style="float:right;">Go to details</a>',
            [$this, 'render_widget']
        );
    }
}

new Post_Count_Stats();
?>
