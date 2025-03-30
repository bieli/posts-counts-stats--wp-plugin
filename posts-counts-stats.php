<?php
/*
Plugin Name: Posts Counts Stats
Description: Display yearly and monthly posts counts in WordPress admin settings in a hierarchical format.
Version: 1.1
Author: Marcin Bielak - https://github.com/bieli/posts-counts-stats--wp-plugin
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Post_Count_Stats {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
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

    public function render_settings_page() {
        global $wpdb;

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
            echo '<h2 style="width: 12%;">Year: <span style="float: right;">' . esc_html($year) . '</span></h2>';
            $yearly_total = array_sum($months);
            echo '<p style="margin-left: 2%; width: 10%;">Total Posts: <span style="float: right;">' . esc_html($yearly_total) . '</span></p>';
            echo '<ul style="margin-left: 2%; width: 10%;">';
            foreach ($months as $month => $post_count) {
                $month_name = date('F', mktime(0, 0, 0, $month, 1));
                if ($post_count == 0) {
                    $c_color = '#afafaf';
                    $post_count_link = esc_html($post_count);
                } else {
                    if ($post_count > 4) {
                        $c_color = 'green';
                    } else {
                        $c_color = 'red';
                    }
                    // Generate link for filtering posts in the admin post list
                    $filter_url = admin_url('edit.php?post_type=post') . '&m=' . sprintf('%04d%02d', $year, $month);
                    $post_count_link = '<a href="' . esc_url($filter_url) . '">' . esc_html($post_count) . '</a>';
                }

                $all_posts += $post_count;

                echo '<li>' . esc_html($month_name) . ': <span style="float: right; color: ' . $c_color . '">' . $post_count_link . '</span></li>';
            }
            echo '</ul>';
        }

        echo '</div>';
        echo '<h2 style="width: 12%;">All posts count: <span style="float: right;">' . $all_posts . '</span></h2>';
    }
}

new Post_Count_Stats();
?>
