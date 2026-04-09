<?php
/**
 * Entaro Child Theme — Service Dispatch Automation
 * Overrides registration, roles, labels, footer, and dashboards
 */

// ──────────────────────────────────────────────
// 1. ENQUEUE CHILD STYLES
// ──────────────────────────────────────────────
function entaro_child_enqueue_styles() {
    wp_enqueue_style('entaro-child-style', get_stylesheet_uri());
    wp_enqueue_style('sd-child-custom', get_stylesheet_directory_uri() . '/sd-custom.css', [], '1.0.0');
}
add_action('wp_enqueue_scripts', 'entaro_child_enqueue_styles', 100);

// ──────────────────────────────────────────────
// 2. OVERRIDE REGISTRATION ROLES
//    Change "Candidate" → "Client" and "Employer" → "Vendor / Service Provider"
// ──────────────────────────────────────────────

// Remove parent theme's registration fields (deferred to ensure parent is loaded)
function sd_remove_parent_registration_hooks() {
    remove_action('woocommerce_register_form', 'entaro_registration_form_fields');
    remove_action('woocommerce_created_customer', 'entaro_wc_save_registration_form_fields');
}
add_action('after_setup_theme', 'sd_remove_parent_registration_hooks', 20);

// Add our custom registration role dropdown
function sd_registration_form_fields() {
    $roles = [
        'sd_client' => 'Client / Service Requester',
        'sd_vendor' => 'Vendor / Service Provider',
    ];
    $selected = !empty($_POST['role']) ? sanitize_text_field($_POST['role']) : 'sd_client';
    ?>
    <p class="form-group form-row form-row-wide">
        <label for="sd_role"><?php esc_html_e('I want to register as', 'entaro-child'); ?></label>
        <select name="role" id="sd_role" class="input-text form-control">
            <?php foreach ($roles as $key => $label): ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($selected, $key); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p class="form-group form-row form-row-wide">
        <label for="sd_phone"><?php esc_html_e('Phone Number', 'entaro-child'); ?> <span class="required">*</span></label>
        <input type="tel" class="input-text form-control" name="sd_phone" id="sd_phone" value="<?php echo isset($_POST['sd_phone']) ? esc_attr($_POST['sd_phone']) : ''; ?>" required />
    </p>
    <p class="form-group form-row form-row-wide">
        <label for="sd_company"><?php esc_html_e('Company Name', 'entaro-child'); ?></label>
        <input type="text" class="input-text form-control" name="sd_company" id="sd_company" value="<?php echo isset($_POST['sd_company']) ? esc_attr($_POST['sd_company']) : ''; ?>" />
    </p>
    <?php
}
add_action('woocommerce_register_form', 'sd_registration_form_fields');

// Validate phone number
function sd_registration_validation($errors, $username, $email) {
    if (empty($_POST['sd_phone'])) {
        $errors->add('sd_phone_error', '<strong>Error</strong>: Phone number is required.');
    }
    return $errors;
}
add_filter('woocommerce_registration_errors', 'sd_registration_validation', 10, 3);

// Save role and meta on registration
function sd_save_registration_fields($customer_id) {
    $allowed_roles = ['sd_client', 'sd_vendor'];
    if (isset($_POST['role']) && in_array($_POST['role'], $allowed_roles)) {
        $user = new WP_User($customer_id);
        $user->set_role($_POST['role']);
    }
    if (isset($_POST['sd_phone'])) {
        $phone = sanitize_text_field($_POST['sd_phone']);
        update_user_meta($customer_id, 'sd_vendor_phone', $phone);
        update_user_meta($customer_id, 'sd_client_phone', $phone);
    }
    if (isset($_POST['sd_company'])) {
        update_user_meta($customer_id, 'sd_client_company', sanitize_text_field($_POST['sd_company']));
    }
}
add_action('woocommerce_created_customer', 'sd_save_registration_fields');

// ──────────────────────────────────────────────
// 3. OVERRIDE ROLE DISPLAY NAMES
// ──────────────────────────────────────────────
function sd_rename_roles() {
    global $wp_roles;
    if (isset($wp_roles->roles['employer'])) {
        $wp_roles->roles['employer']['name'] = 'Vendor / Service Provider';
        $wp_roles->role_names['employer'] = 'Vendor / Service Provider';
    }
    if (isset($wp_roles->roles['subscriber'])) {
        $wp_roles->roles['subscriber']['name'] = 'Client';
        $wp_roles->role_names['subscriber'] = 'Client';
    }
    if (isset($wp_roles->roles['candidate'])) {
        $wp_roles->roles['candidate']['name'] = 'Client';
        $wp_roles->role_names['candidate'] = 'Client';
    }
}
add_action('init', 'sd_rename_roles', 999);

// ──────────────────────────────────────────────
// 4. OVERRIDE MENU LABELS & ACCOUNT MENU BEHAVIOR
// ──────────────────────────────────────────────
function sd_override_nav_menu_labels($menus) {
    $menus['top-menu'] = 'Vendor Menu Account';
    $menus['candidate-menu'] = 'Client Menu Account';
    return $menus;
}
add_filter('registered_nav_menus', 'sd_override_nav_menu_labels');

// Map sd_client role to candidate-menu so clients see the right account menu
function sd_fix_account_menu_location($args) {
    if (!is_user_logged_in() || empty($args['theme_location'])) return $args;
    if ($args['theme_location'] !== 'top-menu') return $args;

    $user = wp_get_current_user();
    if (in_array('sd_client', $user->roles)) {
        $args['theme_location'] = 'candidate-menu';
    }
    return $args;
}
add_filter('wp_nav_menu_args', 'sd_fix_account_menu_location');

// ──────────────────────────────────────────────
// 5. OVERRIDE HEADER "POST A JOB" BUTTON
//    Parent calls entaro_submit_job_resume() directly in templates,
//    so we replace its output via inline JS + a PHP filter for the URL.
// ──────────────────────────────────────────────
function sd_override_header_buttons() {
    $service_url = esc_url(home_url('/service-request/'));
    $vendor_url  = esc_url(home_url('/vendor-dashboard/'));
    $user = wp_get_current_user();
    $is_vendor = is_user_logged_in() && in_array('sd_vendor', $user->roles);
    $target_url = $is_vendor ? $vendor_url : $service_url;
    $btn_text = $is_vendor ? 'Vendor Dashboard' : 'Request Service';
    $btn_icon = $is_vendor ? 'fa-dashboard' : 'fa-plus-circle';
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('.submit-job a.btn-second, a.btn-second[href*="submit-job"], a.btn-second[href*="post-a-job"]').forEach(function(el){
            el.href = '<?php echo $target_url; ?>';
            el.innerHTML = '<i class="fa <?php echo $btn_icon; ?>" aria-hidden="true"></i> <?php echo $btn_text; ?>';
        });
        document.querySelectorAll('.submit-job a').forEach(function(el){
            if(el.textContent.match(/post a (job|resume)/i)){
                el.href = '<?php echo $target_url; ?>';
                el.innerHTML = '<i class="fa <?php echo $btn_icon; ?>" aria-hidden="true"></i> <?php echo $btn_text; ?>';
            }
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'sd_override_header_buttons', 5);

// Also replace "Employer" / "Candidate" text throughout the page nav
function sd_override_all_labels() {
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        var replacements = [
            ['Employers', 'Vendors'],
            ['Employer', 'Vendor'],
            ['Candidates', 'Clients'],
            ['Candidate', 'Client'],
            ['Post a job', 'Request Service'],
            ['POST A JOB', 'REQUEST SERVICE'],
            ['Post a resume', 'Request Service'],
            ['For Employers', 'For Vendors'],
            ['For Candidates', 'For Clients'],
            ['Job Dashboard', 'Vendor Dashboard'],
            ['Candidate Dashboard', 'Client Dashboard'],
        ];
        function replaceText(node){
            if(node.nodeType === 3){
                var t = node.textContent;
                replacements.forEach(function(r){ t = t.split(r[0]).join(r[1]); });
                if(t !== node.textContent) node.textContent = t;
            } else {
                node.childNodes.forEach(replaceText);
            }
        }
        ['nav','header','.main-menu','.topmenu-menu','.dropdown-menu','.woocommerce-MyAccount-navigation'].forEach(function(sel){
            document.querySelectorAll(sel).forEach(replaceText);
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'sd_override_all_labels', 6);

// Footer is overridden via footer.php in the child theme

// ──────────────────────────────────────────────
// 7. ADD WOOCOMMERCE MY ACCOUNT ENDPOINTS FOR DASHBOARDS
// ──────────────────────────────────────────────
function sd_wc_account_menu_items($items) {
    $user = wp_get_current_user();

    $new_items = [];
    foreach ($items as $key => $label) {
        if ($key === 'dashboard') {
            $new_items[$key] = $label;
            if (in_array('sd_vendor', $user->roles) || in_array('administrator', $user->roles)) {
                $new_items['vendor-portal'] = 'Vendor Dashboard';
            }
            if (in_array('sd_client', $user->roles) || in_array('subscriber', $user->roles) || in_array('administrator', $user->roles)) {
                $new_items['service-requests'] = 'Service Requests';
            }
        } else {
            $new_items[$key] = $label;
        }
    }
    return $new_items;
}
add_filter('woocommerce_account_menu_items', 'sd_wc_account_menu_items');

function sd_wc_add_endpoints() {
    add_rewrite_endpoint('vendor-portal', EP_ROOT | EP_PAGES);
    add_rewrite_endpoint('service-requests', EP_ROOT | EP_PAGES);
}
add_action('init', 'sd_wc_add_endpoints');

function sd_wc_vendor_portal_content() {
    $user = wp_get_current_user();
    if (!in_array('sd_vendor', $user->roles) && !in_array('administrator', $user->roles)) {
        echo '<p>Access denied. This section is for vendors only.</p>';
        return;
    }
    echo do_shortcode('[sd_vendor_dashboard]');
}
add_action('woocommerce_account_vendor-portal_endpoint', 'sd_wc_vendor_portal_content');

function sd_wc_service_requests_content() {
    $user = wp_get_current_user();
    echo do_shortcode('[sd_client_dashboard]');
}
add_action('woocommerce_account_service-requests_endpoint', 'sd_wc_service_requests_content');

// Endpoint titles
function sd_wc_endpoint_titles($title, $endpoint = '') {
    global $wp_query;
    if (isset($wp_query->query_vars['vendor-portal'])) return 'Vendor Dashboard';
    if (isset($wp_query->query_vars['service-requests'])) return 'Service Requests';
    return $title;
}
add_filter('the_title', 'sd_wc_endpoint_titles', 10, 2);

// ──────────────────────────────────────────────
// 8. AUTO-ACTIVATE SERVICE DISPATCH PLUGIN
// ──────────────────────────────────────────────
function sd_auto_activate_plugin() {
    $plugin = 'service-dispatch/service-dispatch.php';
    if (!is_plugin_active($plugin) && file_exists(WP_PLUGIN_DIR . '/' . $plugin)) {
        activate_plugin($plugin);
    }
}
add_action('admin_init', 'sd_auto_activate_plugin');

// ──────────────────────────────────────────────
// 9. CREATE REQUIRED PAGES ON THEME ACTIVATION
// ──────────────────────────────────────────────
function sd_create_required_pages() {
    $pages = [
        'vendor-dashboard' => [
            'title'   => 'Vendor Dashboard',
            'content' => '[sd_vendor_dashboard]',
        ],
        'client-dashboard' => [
            'title'   => 'Client Dashboard',
            'content' => '[sd_client_dashboard]',
        ],
        'service-request' => [
            'title'   => 'Submit Service Request',
            'content' => '<!-- Service Request Form will be embedded here -->
<p>Loading service request form...</p>',
        ],
        'vendor-registration' => [
            'title'   => 'Vendor Registration',
            'content' => '<!-- Vendor Registration Form will be embedded here -->
<p>Loading vendor registration form...</p>',
        ],
    ];

    foreach ($pages as $slug => $page_data) {
        $existing = get_page_by_path($slug);
        if (!$existing) {
            wp_insert_post([
                'post_title'   => $page_data['title'],
                'post_content' => $page_data['content'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_name'    => $slug,
            ]);
        }
    }
}
add_action('after_switch_theme', 'sd_create_required_pages');
add_action('admin_init', 'sd_create_required_pages_once');

function sd_create_required_pages_once() {
    if (get_option('sd_pages_created')) return;
    sd_create_required_pages();
    update_option('sd_pages_created', true);
}

// ──────────────────────────────────────────────
// 10. CREATE FLUENT FORMS PROGRAMMATICALLY
// ──────────────────────────────────────────────
function sd_create_fluent_forms() {
    $current_version = 3;
    if ((int) get_option('sd_forms_version', 0) >= $current_version) return;
    if (!function_exists('wpFluent')) return;

    global $wpdb;
    $table = $wpdb->prefix . 'fluentform_forms';
    if (!$wpdb->get_var("SHOW TABLES LIKE '$table'")) return;

    sd_create_service_request_form();
    sd_create_vendor_registration_form();
    sd_create_client_onboarding_form();

    update_option('sd_forms_version', $current_version);
}
add_action('admin_init', 'sd_create_fluent_forms', 20);

function sd_create_service_request_form() {
    global $wpdb;
    $table = $wpdb->prefix . 'fluentform_forms';

    $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE title = %s", 'Commercial Service Request'));

    $form_fields = json_encode([
        'fields' => [
            // Section 1: Contact Info
            sd_ff_section_break('Contact Information'),
            sd_ff_text('names', 'Full Name', true, 'input_text', 'Your full name'),
            sd_ff_text('sd_company', 'Company Name', true, 'input_text', 'Your company name'),
            sd_ff_text('phone', 'Phone Number', true, 'phone', '+1 (555) 000-0000'),
            sd_ff_text('email', 'Email Address', true, 'email', 'your@email.com'),

            // Section 2: Location Details
            sd_ff_section_break('Location Details'),
            sd_ff_text('sd_address', 'Service Address', true, 'input_text', '123 Main St'),
            sd_ff_text('sd_city', 'City', true, 'input_text', 'Houston'),
            sd_ff_text('sd_state', 'State', true, 'input_text', 'TX'),
            sd_ff_text('sd_zip', 'Zip Code', true, 'input_text', '77001'),
            sd_ff_text('sd_site_name', 'Site Name / Store Name', false, 'input_text', 'Westheimer Retail Store'),

            // Section 3: Service Request Details
            sd_ff_section_break('Service Request Details'),
            sd_ff_select('sd_service_type', 'Service Type', true, [
                'General Maintenance', 'Housekeeping / Janitorial', 'Floor Care',
                'Carpet Care', 'Window Cleaning', 'Power Washing',
                'Graffiti Removal', 'Lighting / Electrical', 'Plumbing',
                'HVAC', 'Painting', 'Other (Describe Below)',
            ]),
            sd_ff_textarea('sd_description', 'Describe the Issue / Request', true, 'Please describe what you need done, what areas are affected, and any important details.'),
            sd_ff_select('sd_urgency', 'Urgency Level', true, [
                'Routine (3–5 business days)',
                'Priority (1–2 business days)',
                'Urgent (same/next day if available)',
            ]),
            sd_ff_text('sd_preferred_date', 'Preferred Service Date', false, 'input_date', ''),
            sd_ff_select('sd_time_window', 'Preferred Time Window', false, [
                'Morning (8am–12pm)', 'Afternoon (12pm–4pm)', 'Evening (4pm–8pm)',
            ]),

            // Section 4: Access + On-Site Contact
            sd_ff_section_break('Access + On-Site Contact'),
            sd_ff_text('sd_onsite_name', 'On-Site Contact Name', true, 'input_text', ''),
            sd_ff_text('sd_onsite_phone', 'On-Site Contact Phone', true, 'phone', '+1 (555) 000-0000'),
            sd_ff_textarea('sd_access_instructions', 'Access Instructions', false, 'Lockbox, key holder, check-in desk, loading dock info...'),

            // Section 5: Photos & Documents
            sd_ff_section_break('Photos & Documents'),
            sd_ff_image_upload('sd_photos', 'Photos of the Issue / Area', false, 'Upload clear photos of the issue or work area. This helps us quote and dispatch faster.', 10),
            sd_ff_file_upload('sd_documents', 'Supporting Documents', false, 'Upload any related documents (floor plans, POs, scope sheets, etc.)'),

            // Section 6: Consent
            sd_ff_section_break('Pricing Disclosure + Consent'),
            sd_ff_checkbox('sd_pricing_consent', 'I understand typical pricing ranges (if shown) are estimates only. Final pricing is confirmed after review and scheduling.', true),
            sd_ff_checkbox('sd_sms_consent', 'I agree to receive SMS/email updates regarding my service request. Reply STOP to opt out.', true),
        ],
        'submitButton' => [
            'uniqElKey' => 'el_submit',
            'element'   => 'button',
            'attributes' => [
                'type'  => 'submit',
                'class' => '',
            ],
            'settings' => [
                'align'          => 'left',
                'button_style'   => 'default',
                'container_class' => '',
                'button_size'    => 'md',
                'btn_text'       => 'Submit Service Request',
                'button_ui'      => ['type' => 'default', 'text' => 'Submit Service Request', 'img_url' => ''],
            ],
        ],
    ]);

    if ($existing) {
        $form_id = (int) $existing;
        $wpdb->update($table, [
            'status'              => 'published',
            'appearance_settings' => null,
            'form_fields'         => $form_fields,
            'has_payment'         => 0,
            'type'                => 'form',
            'conditions'          => null,
            'updated_at'          => current_time('mysql'),
        ], ['id' => $form_id]);
    } else {
        $wpdb->insert($table, [
            'title'               => 'Commercial Service Request',
            'status'              => 'published',
            'appearance_settings' => null,
            'form_fields'         => $form_fields,
            'has_payment'         => 0,
            'type'                => 'form',
            'conditions'          => null,
            'created_at'          => current_time('mysql'),
            'updated_at'          => current_time('mysql'),
        ]);
        $form_id = $wpdb->insert_id;
    }

    // Save form settings meta
    $meta_table = $wpdb->prefix . 'fluentform_form_meta';
    $form_settings_value = json_encode([
        'confirmation' => [
            'redirectTo'   => 'samePage',
            'messageToShow' => '<h3>Request Received</h3><p>Thanks — we received your request. Our team will review details and follow up to confirm scheduling and final pricing.</p>',
            'samePageFormBehavior' => 'hide_form',
        ],
        'layout' => ['labelPlacement' => 'top', 'helpMessagePlacement' => 'with_label', 'asteriskPlacement' => 'asterisk-right'],
    ]);
    $meta_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$meta_table} WHERE form_id = %d AND meta_key = %s",
        $form_id,
        'formSettings'
    ));
    if ($meta_exists) {
        $wpdb->update($meta_table, ['value' => $form_settings_value], ['id' => $meta_exists]);
    } else {
        $wpdb->insert($meta_table, [
            'form_id'  => $form_id,
            'meta_key' => 'formSettings',
            'value'    => $form_settings_value,
        ]);
    }

    // Update the service-request page with the form shortcode
    $page = get_page_by_path('service-request');
    if ($page) {
        wp_update_post([
            'ID'           => $page->ID,
            'post_content' => '[fluentform id="' . $form_id . '"]',
        ]);
    }

    update_option('sd_service_request_form_id', $form_id);
}

function sd_create_vendor_registration_form() {
    global $wpdb;
    $table = $wpdb->prefix . 'fluentform_forms';

    $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE title = %s", 'Vendor Registration'));
    if ($existing) return;

    $form_fields = json_encode([
        'fields' => [
            sd_ff_section_break('Vendor Information'),
            sd_ff_text('names', 'Full Name', true, 'input_text', 'Your full name'),
            sd_ff_text('sd_company', 'Company / Business Name', true, 'input_text', 'Your business name'),
            sd_ff_text('phone', 'Phone Number', true, 'phone', '+1 (555) 000-0000'),
            sd_ff_text('email', 'Email Address', true, 'email', 'your@email.com'),

            sd_ff_section_break('Service Capabilities'),
            sd_ff_multi_select('sd_services', 'Service Types You Provide', true, [
                'General Maintenance', 'Housekeeping / Janitorial', 'Floor Care',
                'Carpet Care', 'Window Cleaning', 'Power Washing',
                'Graffiti Removal', 'Lighting / Electrical', 'Plumbing',
                'HVAC', 'Painting',
            ]),
            sd_ff_text('sd_coverage_area', 'Coverage Area / Cities', true, 'input_text', 'Houston, Katy, Sugar Land...'),
            sd_ff_textarea('sd_experience', 'Experience / Qualifications', false, 'Briefly describe your experience and any relevant certifications.'),

            sd_ff_section_break('Business Details'),
            sd_ff_text('sd_license', 'License / Insurance Number', false, 'input_text', 'Optional'),
            sd_ff_select('sd_availability', 'General Availability', true, [
                'Weekdays Only', 'Weekends Only', 'Any Day', 'Emergency / On-Call Available',
            ]),

            sd_ff_section_break('Agreement'),
            sd_ff_checkbox('sd_terms', 'I agree to the vendor terms and service dispatch policies.', true),
            sd_ff_checkbox('sd_sms', 'I agree to receive SMS job notifications. Reply STOP to opt out.', true),
        ],
        'submitButton' => [
            'uniqElKey' => 'el_submit',
            'element'   => 'button',
            'attributes' => ['type' => 'submit', 'class' => ''],
            'settings'   => [
                'align' => 'left', 'button_style' => 'default', 'container_class' => '',
                'button_size' => 'md', 'btn_text' => 'Register as Vendor',
                'button_ui' => ['type' => 'default', 'text' => 'Register as Vendor', 'img_url' => ''],
            ],
        ],
    ]);

    $wpdb->insert($table, [
        'title'       => 'Vendor Registration',
        'status'      => 'published',
        'form_fields' => $form_fields,
        'has_payment'  => 0,
        'type'        => 'form',
        'created_at'  => current_time('mysql'),
        'updated_at'  => current_time('mysql'),
    ]);

    $form_id = $wpdb->insert_id;

    $meta_table = $wpdb->prefix . 'fluentform_form_meta';
    $wpdb->insert($meta_table, [
        'form_id'  => $form_id,
        'meta_key' => 'formSettings',
        'value'    => json_encode([
            'confirmation' => [
                'redirectTo'   => 'samePage',
                'messageToShow' => '<h3>Registration Received</h3><p>Thank you for registering as a vendor. We will review your application and activate your account shortly. You will receive an SMS when approved.</p>',
                'samePageFormBehavior' => 'hide_form',
            ],
        ]),
    ]);

    $page = get_page_by_path('vendor-registration');
    if ($page) {
        wp_update_post([
            'ID'           => $page->ID,
            'post_content' => '[fluentform id="' . $form_id . '"]',
        ]);
    }

    update_option('sd_vendor_form_id', $form_id);
}

function sd_create_client_onboarding_form() {
    global $wpdb;
    $table = $wpdb->prefix . 'fluentform_forms';

    $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE title = %s", 'Client Onboarding'));
    if ($existing) return;

    $form_fields = json_encode([
        'fields' => [
            sd_ff_section_break('Company / Client Info'),
            sd_ff_text('sd_company_name', 'Company Name', true, 'input_text', ''),
            sd_ff_text('names', 'Main Contact Name', true, 'input_text', ''),
            sd_ff_text('phone', 'Main Contact Phone', true, 'phone', ''),
            sd_ff_text('email', 'Main Contact Email', true, 'email', ''),
            sd_ff_text('sd_billing_email', 'Accounts Payable Email', false, 'email', 'If different from above'),
            sd_ff_multi_select('sd_approver', 'Who can approve work?', true, [
                'Main Contact', 'On-site Manager', 'Both',
            ]),

            sd_ff_section_break('Property / Site Info'),
            sd_ff_text('sd_site_address', 'Site Address', true, 'input_text', ''),
            sd_ff_textarea('sd_access', 'Access Instructions', false, 'Gate codes, key holders, check-in process'),

            sd_ff_section_break('Acknowledgment'),
            sd_ff_checkbox('sd_ack_pricing', 'I understand final pricing is confirmed after review', true),
            sd_ff_checkbox('sd_ack_scheduling', 'I understand services are coordinated based on availability', true),
            sd_ff_checkbox('sd_ack_payment', 'I agree to remit payment per invoice terms', true),
        ],
        'submitButton' => [
            'uniqElKey' => 'el_submit',
            'element'   => 'button',
            'attributes' => ['type' => 'submit', 'class' => ''],
            'settings'   => [
                'align' => 'left', 'button_style' => 'default', 'container_class' => '',
                'button_size' => 'md', 'btn_text' => 'Complete Onboarding',
                'button_ui' => ['type' => 'default', 'text' => 'Complete Onboarding', 'img_url' => ''],
            ],
        ],
    ]);

    $wpdb->insert($table, [
        'title'       => 'Client Onboarding',
        'status'      => 'published',
        'form_fields' => $form_fields,
        'has_payment'  => 0,
        'type'        => 'form',
        'created_at'  => current_time('mysql'),
        'updated_at'  => current_time('mysql'),
    ]);

    $form_id = $wpdb->insert_id;

    $meta_table = $wpdb->prefix . 'fluentform_form_meta';
    $wpdb->insert($meta_table, [
        'form_id'  => $form_id,
        'meta_key' => 'formSettings',
        'value'    => json_encode([
            'confirmation' => [
                'redirectTo'   => 'samePage',
                'messageToShow' => '<h3>Onboarding Complete</h3><p>Thank you! Your account is now set up. You can submit service requests anytime.</p>',
                'samePageFormBehavior' => 'hide_form',
            ],
        ]),
    ]);

    update_option('sd_client_onboarding_form_id', $form_id);
}

// ──────────────────────────────────────────────
// FLUENT FORMS FIELD HELPERS
// ──────────────────────────────────────────────
function sd_ff_text($name, $label, $required, $type = 'input_text', $placeholder = '') {
    $element = 'input_text';
    $input_type = 'text';

    if ($type === 'email') { $element = 'input_email'; $input_type = 'email'; }
    elseif ($type === 'phone') { $element = 'phone'; $input_type = 'tel'; }
    elseif ($type === 'input_date') { $element = 'input_date'; $input_type = 'text'; }

    if ($type === 'input_date') {
        return [
            'element'    => 'input_date',
            'uniqElKey'  => 'el_' . substr(md5($name . $label), 0, 8),
            'attributes' => [
                'type'        => 'text',
                'name'        => $name,
                'value'       => '',
                'id'          => '',
                'class'       => '',
                'placeholder' => $placeholder,
            ],
            'settings' => [
                'container_class'   => '',
                'label'             => $label,
                'label_placement'   => '',
                'admin_field_label' => $name,
                'help_message'      => '',
                'validation_rules'  => $required ? ['required' => ['value' => true, 'message' => 'This field is required']] : [],
                'conditional_logics' => [],
                'date_format'       => 'm/d/Y',
                'date_config'       => '{}',
            ],
            'editor_options' => [
                'title' => $label,
            ],
        ];
    }

    return [
        'element'    => $element,
        'uniqElKey'  => 'el_' . substr(md5($name . $label), 0, 8),
        'attributes' => [
            'type'        => $input_type,
            'name'        => $name,
            'value'       => '',
            'id'          => '',
            'class'       => '',
            'placeholder' => $placeholder,
        ],
        'settings' => [
            'container_class'   => '',
            'label'             => $label,
            'label_placement'   => '',
            'admin_field_label' => $name,
            'help_message'      => '',
            'validation_rules'  => $required ? ['required' => ['value' => true, 'message' => 'This field is required']] : [],
            'conditional_logics' => [],
        ],
        'editor_options' => [
            'title' => $label,
        ],
    ];
}

function sd_ff_textarea($name, $label, $required, $placeholder = '') {
    return [
        'element'    => 'input_textarea',
        'uniqElKey'  => 'el_' . substr(md5($name . $label), 0, 8),
        'attributes' => [
            'name'        => $name,
            'value'       => '',
            'id'          => '',
            'class'       => '',
            'placeholder' => $placeholder,
            'rows'        => 4,
        ],
        'settings' => [
            'container_class'   => '',
            'label'             => $label,
            'admin_field_label' => $name,
            'validation_rules'  => $required ? ['required' => ['value' => true, 'message' => 'This field is required']] : [],
        ],
    ];
}

function sd_ff_select($name, $label, $required, $options) {
    $ff_options = [];
    foreach ($options as $opt) {
        $ff_options[] = ['label' => $opt, 'value' => $opt, 'calc_value' => ''];
    }
    return [
        'element'    => 'select',
        'uniqElKey'  => 'el_' . substr(md5($name . $label), 0, 8),
        'attributes' => [
            'name'  => $name,
            'value' => '',
            'id'    => '',
            'class' => '',
        ],
        'settings' => [
            'container_class'   => '',
            'label'             => $label,
            'admin_field_label' => $name,
            'placeholder'       => '— Select —',
            'validation_rules'  => $required ? ['required' => ['value' => true, 'message' => 'This field is required']] : [],
            'advanced_options'  => $ff_options,
            'calc_value_status' => false,
        ],
    ];
}

function sd_ff_multi_select($name, $label, $required, $options) {
    $ff_options = [];
    foreach ($options as $opt) {
        $ff_options[] = ['label' => $opt, 'value' => $opt, 'calc_value' => ''];
    }
    return [
        'element'    => 'input_checkbox',
        'uniqElKey'  => 'el_' . substr(md5($name . $label), 0, 8),
        'attributes' => [
            'name'  => $name,
            'value' => [],
            'id'    => '',
            'class' => '',
            'type'  => 'checkbox',
        ],
        'settings' => [
            'container_class'   => '',
            'label'             => $label,
            'admin_field_label' => $name,
            'validation_rules'  => $required ? ['required' => ['value' => true, 'message' => 'This field is required']] : [],
            'advanced_options'  => $ff_options,
        ],
    ];
}

function sd_ff_checkbox($name, $label, $required) {
    return [
        'element'    => 'terms_and_condition',
        'uniqElKey'  => 'el_' . substr(md5($name . $label), 0, 8),
        'attributes' => [
            'name'  => $name,
            'value' => false,
            'class' => '',
            'type'  => 'checkbox',
        ],
        'settings' => [
            'container_class'   => '',
            'label'             => '',
            'admin_field_label' => $name,
            'validation_rules'  => $required ? ['required' => ['value' => true, 'message' => 'You must agree to continue']] : [],
            'tnc_html'          => '<p>' . $label . '</p>',
            'has_checkbox'      => true,
        ],
    ];
}

function sd_ff_image_upload($name, $label, $required, $help = '', $max_count = 10) {
    return [
        'element'    => 'input_image',
        'uniqElKey'  => 'el_' . substr(md5($name . $label), 0, 8),
        'attributes' => [
            'type'   => 'file',
            'name'   => $name,
            'value'  => '',
            'id'     => '',
            'class'  => '',
            'accept' => 'image/*',
        ],
        'settings' => [
            'container_class'      => '',
            'label'                => $label,
            'admin_field_label'    => $name,
            'label_placement'      => '',
            'btn_text'             => 'Choose Photos',
            'upload_file_location' => 'default',
            'file_location_type'   => 'follow_global_settings',
            'help_message'         => $help,
            'upload_bttn_ui'       => '',
            'validation_rules' => [
                'required'            => ['value' => $required, 'message' => 'This field is required'],
                'max_file_size'       => ['value' => 5242880, '_valueFrom' => 'MB', 'message' => 'Maximum file size is 5MB'],
                'max_file_count'      => ['value' => $max_count, 'message' => 'Maximum ' . $max_count . ' files allowed'],
                'allowed_image_types' => ['value' => ['jpg', 'jpeg', 'png', 'gif'], 'message' => 'Only jpg, jpeg, png, gif files are allowed'],
            ],
            'conditional_logics' => [],
        ],
        'editor_options' => [
            'title'      => $label,
            'icon_class' => 'ff-edit-images',
            'template'   => 'inputFile',
        ],
    ];
}

function sd_ff_file_upload($name, $label, $required, $help = '') {
    return [
        'element'    => 'input_file',
        'uniqElKey'  => 'el_' . substr(md5($name . $label), 0, 8),
        'attributes' => [
            'type'  => 'file',
            'name'  => $name,
            'value' => '',
            'id'    => '',
            'class' => '',
        ],
        'settings' => [
            'container_class'      => '',
            'label'                => $label,
            'admin_field_label'    => $name,
            'label_placement'      => '',
            'btn_text'             => 'Choose File',
            'upload_file_location' => 'default',
            'file_location_type'   => 'follow_global_settings',
            'help_message'         => $help,
            'upload_bttn_ui'       => '',
            'validation_rules' => [
                'required'           => ['value' => $required, 'message' => 'This field is required'],
                'max_file_size'      => ['value' => 10485760, '_valueFrom' => 'MB', 'message' => 'Maximum file size is 10MB'],
                'max_file_count'     => ['value' => 5, 'message' => 'Maximum 5 files allowed'],
                'allowed_file_types' => ['value' => ['jpg','jpeg','png','gif','pdf','doc','docx'], 'message' => 'File type not allowed'],
            ],
            'conditional_logics' => [],
        ],
        'editor_options' => [
            'title'      => $label,
            'icon_class' => 'ff-edit-files',
            'template'   => 'inputFile',
        ],
    ];
}

function sd_ff_section_break($title) {
    return [
        'element'   => 'section_break',
        'uniqElKey' => 'el_' . substr(md5($title), 0, 8),
        'attributes' => [
            'class' => '',
            'id'    => '',
        ],
        'settings'  => [
            'label'           => $title,
            'description'     => '',
            'align'           => 'left',
            'container_class' => '',
        ],
    ];
}

// ──────────────────────────────────────────────
// 11. HANDLE SERVICE REQUEST FORM SUBMISSION
//     Creates a job (sd_job post) when form is submitted
// ──────────────────────────────────────────────
function sd_handle_service_request_submission($insertId, $formData, $form) {
    $service_form_id = get_option('sd_service_request_form_id');
    if (!$service_form_id || $form->id != $service_form_id) return;

    $service_type_map = [
        'General Maintenance'        => 'general-maintenance',
        'Housekeeping / Janitorial'  => 'housekeeping',
        'Floor Care'                 => 'floor-care',
        'Carpet Care'                => 'carpet-care',
        'Window Cleaning'            => 'window-cleaning',
        'Power Washing'              => 'power-washing',
        'Graffiti Removal'           => 'graffiti-removal',
        'Lighting / Electrical'      => 'lighting-electrical',
        'Plumbing'                   => 'plumbing',
        'HVAC'                       => 'hvac',
        'Painting'                   => 'painting',
        'Other (Describe Below)'     => 'other',
    ];

    $urgency_map = [
        'Routine (3–5 business days)'        => 'routine',
        'Priority (1–2 business days)'       => 'priority',
        'Urgent (same/next day if available)' => 'urgent',
    ];

    $time_map = [
        'Morning (8am–12pm)'   => 'morning',
        'Afternoon (12pm–4pm)' => 'afternoon',
        'Evening (4pm–8pm)'    => 'evening',
    ];

    $client_name = $formData['names'] ?? '';
    $company     = $formData['sd_company'] ?? '';
    $service_raw = $formData['sd_service_type'] ?? '';
    $service_type = $service_type_map[$service_raw] ?? 'other';

    $job_title = $client_name . ' — ' . ($service_raw ?: 'Service Request');

    $job_id = wp_insert_post([
        'post_title'  => $job_title,
        'post_type'   => 'sd_job',
        'post_status' => 'publish',
        'post_content' => $formData['sd_description'] ?? '',
    ]);

    if (!$job_id || is_wp_error($job_id)) return;

    $meta_fields = [
        '_sd_client_name'        => $client_name,
        '_sd_company_name'       => $company,
        '_sd_client_phone'       => $formData['phone'] ?? '',
        '_sd_client_email'       => $formData['email'] ?? '',
        '_sd_service_address'    => $formData['sd_address'] ?? '',
        '_sd_city'               => $formData['sd_city'] ?? '',
        '_sd_state'              => $formData['sd_state'] ?? '',
        '_sd_zip'                => $formData['sd_zip'] ?? '',
        '_sd_site_name'          => $formData['sd_site_name'] ?? '',
        '_sd_service_type'       => $service_type,
        '_sd_urgency'            => $urgency_map[$formData['sd_urgency'] ?? ''] ?? 'routine',
        '_sd_preferred_date'     => $formData['sd_preferred_date'] ?? '',
        '_sd_time_window'        => $time_map[$formData['sd_time_window'] ?? ''] ?? '',
        '_sd_onsite_contact'     => $formData['sd_onsite_name'] ?? '',
        '_sd_onsite_phone'       => $formData['sd_onsite_phone'] ?? '',
        '_sd_access_instructions' => $formData['sd_access_instructions'] ?? '',
        '_sd_stage'              => 'new-request',
    ];

    foreach ($meta_fields as $key => $value) {
        update_post_meta($job_id, $key, sanitize_text_field($value));
    }

    // Store uploaded photos
    if (!empty($formData['sd_photos'])) {
        $photos = $formData['sd_photos'];
        if (is_string($photos)) $photos = json_decode($photos, true) ?: [$photos];
        if (!is_array($photos)) $photos = [$photos];
        update_post_meta($job_id, '_sd_photos', array_map('esc_url_raw', $photos));
    }

    // Store uploaded documents
    if (!empty($formData['sd_documents'])) {
        $docs = $formData['sd_documents'];
        if (is_string($docs)) $docs = json_decode($docs, true) ?: [$docs];
        if (!is_array($docs)) $docs = [$docs];
        update_post_meta($job_id, '_sd_documents', array_map('esc_url_raw', $docs));
    }

    // Store Fluent Forms entry reference for admin lookup
    update_post_meta($job_id, '_sd_ff_entry_id', $insertId);
    update_post_meta($job_id, '_sd_ff_form_id', $form->id);

    // Link to logged-in user
    if (is_user_logged_in()) {
        update_post_meta($job_id, '_sd_submitted_by', get_current_user_id());
    }

    // Add timeline event
    if (class_exists('SD_Meta_Boxes')) {
        SD_Meta_Boxes::add_timeline_event($job_id, 'Job created from service request form', '#3b82f6');
    }
}
add_action('fluentform/submission_inserted', 'sd_handle_service_request_submission', 10, 3);
add_action('fluentform_submission_inserted', 'sd_handle_service_request_submission', 10, 3);

// ──────────────────────────────────────────────
// 12. OVERRIDE WC MY ACCOUNT DASHBOARD CONTENT
// ──────────────────────────────────────────────
function sd_wc_dashboard_override() {
    $user = wp_get_current_user();
    ?>
    <div class="sd-wc-dashboard">
        <div class="sd-wc-welcome">
            <h2>Welcome back, <?php echo esc_html($user->display_name); ?>!</h2>
            <p>Quick access to your service dispatch tools:</p>
        </div>
        <div class="sd-wc-cards">
            <?php if (in_array('sd_vendor', $user->roles) || in_array('administrator', $user->roles)): ?>
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('vendor-portal')); ?>" class="sd-wc-card sd-wc-card-vendor">
                    <span class="dashicons dashicons-hammer"></span>
                    <h3>Vendor Dashboard</h3>
                    <p>View available jobs, manage active work, track earnings</p>
                </a>
            <?php endif; ?>
            <?php if (in_array('sd_client', $user->roles) || in_array('subscriber', $user->roles) || in_array('administrator', $user->roles)): ?>
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('service-requests')); ?>" class="sd-wc-card sd-wc-card-client">
                    <span class="dashicons dashicons-clipboard"></span>
                    <h3>Service Requests</h3>
                    <p>Track your service requests and view invoices</p>
                </a>
                <a href="<?php echo esc_url(home_url('/service-request/')); ?>" class="sd-wc-card sd-wc-card-new">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <h3>New Service Request</h3>
                    <p>Submit a new commercial service request</p>
                </a>
            <?php endif; ?>
            <?php if (in_array('administrator', $user->roles)): ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=sd-pipeline')); ?>" class="sd-wc-card sd-wc-card-admin">
                    <span class="dashicons dashicons-networking"></span>
                    <h3>Admin Pipeline</h3>
                    <p>Manage all jobs in the kanban pipeline board</p>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
function sd_override_wc_dashboard() {
    remove_action('woocommerce_account_dashboard', 'woocommerce_account_dashboard');
    add_action('woocommerce_account_dashboard', 'sd_wc_dashboard_override');
}
add_action('init', 'sd_override_wc_dashboard');

// ──────────────────────────────────────────────
// 13. FLUSH REWRITE RULES ON FIRST LOAD
// ──────────────────────────────────────────────
function sd_flush_rewrites_once() {
    if (get_option('sd_rewrites_flushed')) return;
    flush_rewrite_rules();
    update_option('sd_rewrites_flushed', true);
}
add_action('init', 'sd_flush_rewrites_once', 9999);

// ──────────────────────────────────────────────
// 14. LOGIN GATE — Require login for protected pages
// ──────────────────────────────────────────────
function sd_protect_pages_content($content) {
    if (is_admin() || wp_doing_ajax()) return $content;
    if (is_user_logged_in()) return $content;

    $protected_slugs = ['service-request', 'vendor-dashboard', 'client-dashboard'];
    global $post;
    if (!$post || !in_array($post->post_name, $protected_slugs)) return $content;

    $login_url = get_permalink(get_option('woocommerce_myaccount_page_id'));
    if (!$login_url) $login_url = wp_login_url();

    ob_start();
    ?>
    <div class="sd-login-required">
        <div class="sd-login-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <h2>Login Required</h2>
        <p>Please log in or create an account to access this page. Register as a <strong>Client</strong> to request services, or as a <strong>Vendor</strong> to provide services.</p>
        <div class="sd-login-buttons">
            <a href="<?php echo esc_url($login_url); ?>" class="sd-btn sd-btn-primary">Login / Register</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_filter('the_content', 'sd_protect_pages_content', 1);

// Make service-request page full-width (no right sidebar).
function sd_set_page_layouts() {
    $pages = ['service-request', 'vendor-dashboard', 'client-dashboard', 'vendor-registration'];
    foreach ($pages as $slug) {
        $page = get_page_by_path($slug);
        if (!$page) continue;
        if (get_post_meta($page->ID, 'apus_page_layout', true) !== 'main') {
            update_post_meta($page->ID, 'apus_page_layout', 'main');
        }
        if (get_post_meta($page->ID, 'apus_page_fullwidth', true) !== 'yes') {
            update_post_meta($page->ID, 'apus_page_fullwidth', 'yes');
        }
    }
}
add_action('admin_init', 'sd_set_page_layouts');
