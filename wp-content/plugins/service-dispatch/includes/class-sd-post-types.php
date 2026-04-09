<?php
if (!defined('ABSPATH')) exit;

class SD_Post_Types {

    const STAGES = [
        'new-request'       => 'New Request',
        'approved-priced'   => 'Approved / Priced',
        'posted-to-vendors' => 'Posted to Vendors',
        'claimed'           => 'Claimed',
        'scheduled'         => 'Scheduled / In Progress',
        'completed-review'  => 'Completed – Pending Review',
        'ready-to-invoice'  => 'Ready to Invoice',
        'closed-paid'       => 'Closed / Paid',
        'issue-escalation'  => 'Issue / Escalation',
    ];

    const SERVICE_TYPES = [
        'general-maintenance'   => 'General Maintenance',
        'housekeeping'          => 'Housekeeping / Janitorial',
        'floor-care'            => 'Floor Care',
        'carpet-care'           => 'Carpet Care',
        'window-cleaning'       => 'Window Cleaning',
        'power-washing'         => 'Power Washing',
        'graffiti-removal'      => 'Graffiti Removal',
        'lighting-electrical'   => 'Lighting / Electrical',
        'plumbing'              => 'Plumbing',
        'hvac'                  => 'HVAC',
        'painting'              => 'Painting',
        'other'                 => 'Other',
    ];

    const URGENCY_LEVELS = [
        'routine'  => 'Routine (3–5 business days)',
        'priority' => 'Priority (1–2 business days)',
        'urgent'   => 'Urgent (same/next day)',
    ];

    public static function init() {
        add_action('init', [__CLASS__, 'register']);
        add_action('init', [__CLASS__, 'register_taxonomy']);
    }

    public static function register() {
        $labels = [
            'name'               => 'Jobs',
            'singular_name'      => 'Job',
            'menu_name'          => 'Service Dispatch',
            'add_new'            => 'Add New Job',
            'add_new_item'       => 'Add New Job',
            'edit_item'          => 'Edit Job',
            'new_item'           => 'New Job',
            'view_item'          => 'View Job',
            'search_items'       => 'Search Jobs',
            'not_found'          => 'No jobs found',
            'not_found_in_trash' => 'No jobs found in trash',
        ];

        register_post_type('sd_job', [
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'capability_type'    => 'post',
            'hierarchical'       => false,
            'supports'           => ['title', 'editor', 'custom-fields'],
            'has_archive'        => false,
            'rewrite'            => false,
            'show_in_rest'       => true,
        ]);
    }

    public static function register_taxonomy() {
        register_taxonomy('sd_service_type', 'sd_job', [
            'labels' => [
                'name'          => 'Service Types',
                'singular_name' => 'Service Type',
            ],
            'public'       => false,
            'show_ui'      => true,
            'hierarchical' => true,
            'show_in_rest' => true,
        ]);

        register_taxonomy('sd_job_tag', 'sd_job', [
            'labels' => [
                'name'          => 'Job Tags',
                'singular_name' => 'Job Tag',
            ],
            'public'       => false,
            'show_ui'      => true,
            'hierarchical' => false,
            'show_in_rest' => true,
        ]);
    }

    public static function get_stage_label($stage) {
        return self::STAGES[$stage] ?? $stage;
    }

    public static function get_stage_color($stage) {
        $colors = [
            'new-request'       => '#3b82f6',
            'approved-priced'   => '#8b5cf6',
            'posted-to-vendors' => '#f59e0b',
            'claimed'           => '#f97316',
            'scheduled'         => '#06b6d4',
            'completed-review'  => '#10b981',
            'ready-to-invoice'  => '#6366f1',
            'closed-paid'       => '#22c55e',
            'issue-escalation'  => '#ef4444',
        ];
        return $colors[$stage] ?? '#6b7280';
    }

    public static function get_stage_icon($stage) {
        $icons = [
            'new-request'       => 'dashicons-plus-alt',
            'approved-priced'   => 'dashicons-yes-alt',
            'posted-to-vendors' => 'dashicons-megaphone',
            'claimed'           => 'dashicons-flag',
            'scheduled'         => 'dashicons-calendar-alt',
            'completed-review'  => 'dashicons-clipboard',
            'ready-to-invoice'  => 'dashicons-money-alt',
            'closed-paid'       => 'dashicons-shield-alt',
            'issue-escalation'  => 'dashicons-warning',
        ];
        return $icons[$stage] ?? 'dashicons-marker';
    }
}
