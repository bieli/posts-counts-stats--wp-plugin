<?php
/*
Plugin Name: Posts Counts Stats
Description: Display yearly and monthly posts counts in WordPress admin settings in a hierarchical matrix format + dashboard widget.
Version: 1.4.0
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

    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'posts_counts_stats_dashboard_widget',
            'Posts Counts Stats (Yearly Summary)',
            [$this, 'render_dashboard_widget']
        );
    }

    public function render_dashboard_widget() {
        $this->render_settings_page(true);
        
        echo '<p style="margin-top: 15px;"><a href="' . admin_url('options-general.php?page=posts-counts-stats') . '" class="button button-secondary">View Monthly Details & Graphs</a></p>';
    }

    public function render_settings_page($year_only = false) {
        global $wpdb;

        echo '<div class="wrap">';
        if (!$year_only) {
            echo '<h1>Posts Count Stats</h1>';
        }

        $all_posts = 0;

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

        if (empty($stats)) {
            echo '<p>No published posts found.</p></div>';
            return;
        }

        $organized_stats = [];
        $yearly_totals = [];
        $monthly_totals = array_fill(1, 12, 0);
        $years_list = [];

        foreach ($stats as $stat) {
            $year = $stat->year;
            $month = $stat->month;
            $post_count = $stat->post_count;

            if (!in_array($year, $years_list)) {
                $years_list[] = $year;
            }

            if (!isset($organized_stats[$year])) {
                $organized_stats[$year] = array_fill(1, 12, 0);
                $yearly_totals[$year] = 0;
            }

            $organized_stats[$year][$month] = $post_count;
            $yearly_totals[$year] += $post_count;
            $monthly_totals[$month] += $post_count;
            $all_posts += $post_count;
        }

        sort($years_list);

        echo '<h2 style="margin-top:20px;">Yearly Bar Graph</h2>';
        echo '<div style="background:#fff; border:1px solid #ccd0d4; padding:20px; margin-bottom:20px; max-width:600px;">';
        $max_yearly = max($yearly_totals) > 0 ? max($yearly_totals) : 1;
        
        foreach ($years_list as $yr) {
            $total = isset($yearly_totals[$yr]) ? $yearly_totals[$yr] : 0;
            $percentage = round(($total / $max_yearly) * 100);
            echo '<div style="margin-bottom: 10px; display: flex; align-items: center;">';
            echo '<span style="width: 50px; font-weight: bold;">' . esc_html($yr) . '</span>';
            echo '<div style="background: #f0f0f1; flex-grow: 1; height: 20px; margin: 0 10px; border-radius: 3px; overflow: hidden;">';
            echo '<div style="background: #2271b1; width: ' . esc_attr($percentage) . '%; height: 100%;"></div>';
            echo '</div>';
            echo '<span style="width: 40px; text-align: right;">' . esc_html($total) . '</span>';
            echo '</div>';
        }
        echo '</div>';

        if ($year_only) {
            $reverse_years = array_reverse($years_list);
            echo '<table class="wp-list-table widefat fixed striped posts" style="max-width: 600px;">';
            echo '<thead><tr><th>Time Period</th><th>Post Count</th></tr></thead>';
            echo '<tbody>';
            foreach ($reverse_years as $year) {
                $year_url = admin_url('edit.php?m=' . $year);
                echo '<tr>';
                echo '<td><strong>Year ' . esc_html($year) . '</strong></td>';
                echo '<td><a href="' . esc_url($year_url) . '">' . esc_html($yearly_totals[$year]) . '</a></td>';
                echo '</tr>';
            }
            echo '<tr style="font-weight: bold; background: #d3d3d3;">';
            echo '<td><strong>Grand Total</strong></td>';
            echo '<td><strong>' . esc_html($all_posts) . '</strong></td>';
            echo '</tr>';
            echo '</tbody></table>';
        } else {
            echo '<h2>Detailed Statistics Matrix</h2>';
            echo '<div style="overflow-x: auto; max-width: 100%; margin-bottom: 20px;">';
            echo '<table class="wp-list-table widefat fixed striped posts" style="width: auto; min-width: 600px; border-collapse: collapse;">';
            
            echo '<thead><tr style="background: #f6f7f7;">';
            echo '<th style="border: 1px solid #c3c4c7; padding: 10px; font-weight: bold;">Month</th>';
            foreach ($years_list as $year) {
                echo '<th style="border: 1px solid #c3c4c7; padding: 10px; text-align: center; font-weight: bold;">' . esc_html($year) . '</th>';
            }
            echo '<th style="border: 1px solid #c3c4c7; padding: 10px; text-align: center; font-weight: bold; background: #edeeee;">Total</th>';
            echo '</tr></thead>';
            
            echo '<tbody>';
            
            for ($m = 1; $m <= 12; $m++) {
                $month_name = date_i18n('F', mktime(0, 0, 0, $m, 1));
                $month_padded = str_pad($m, 2, '0', STR_PAD_LEFT);
                
                echo '<tr>';
                echo '<td style="border: 1px solid #c3c4c7; padding: 8px; font-weight: 500;">' . esc_html($month_name) . '</td>';
                
                foreach ($years_list as $year) {
                    $count = isset($organized_stats[$year][$m]) ? $organized_stats[$year][$m] : 0;
                    echo '<td style="border: 1px solid #c3c4c7; padding: 8px; text-align: center;">';
                    if ($count > 0) {
                        $month_url = admin_url('edit.php?m=' . $year . $month_padded);
                        echo '<a href="' . esc_url($month_url) . '">' . esc_html($count) . '</a>';
                    } else {
                        echo '<span style="color: #a7aaad;">0</span>';
                    }
                    echo '</td>';
                }
                
                echo '<td style="border: 1px solid #c3c4c7; padding: 8px; text-align: center; font-weight: bold; background: #f6f7f7;">' . esc_html($monthly_totals[$m]) . '</td>';
                echo '</tr>';
            }
            
            echo '<tr style="background: #edeeee; font-weight: bold; border-top: 2px solid #c3c4c7;">';
            echo '<td style="border: 1px solid #c3c4c7; padding: 10px;">Total</td>';
            foreach ($years_list as $year) {
                $year_url = admin_url('edit.php?m=' . $year);
                echo '<td style="border: 1px solid #c3c4c7; padding: 10px; text-align: center;">';
                echo '<a href="' . esc_url($year_url) . '">' . esc_html($yearly_totals[$year]) . '</a>';
                echo '</td>';
            }
            echo '<td style="border: 1px solid #c3c4c7; padding: 10px; text-align: center; background: #d3d3d3; font-weight: bold; border: 2px solid #999;">' . esc_html($all_posts) . '</td>';
            echo '</tr>';
            
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
        }
        
        echo '</div>';
    }
}

new Post_Count_Stats();
