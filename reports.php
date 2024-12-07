<?php
// Add admin menu and sub-menu
add_action('admin_menu', 'na_add_admin_menu');
function na_add_admin_menu() {
    add_menu_page(
        'رویداد',        // Page title
        'رویداد',        // Menu title
        'manage_options', // Capability
        'na_event',       // Menu slug
        '',               // Function (optional, if not used, no top-level page will be created)
        'dashicons-tickets-alt', // Icon URL
        6                 // Position
    );

    add_submenu_page(
        'na_event',           // Parent slug
        'گزارش ثبت نام ها',    // Page title
        'گزارش ثبت نام ها',    // Menu title
        'manage_options',     // Capability
        'na_event_report',    // Menu slug
        'na_display_participants_list' // Function
    );
}

// Display the participants list
function na_display_participants_list() {
    global $wpdb;
    $event_participants_table = $wpdb->prefix . 'event_participants';
    $na_events_table = $wpdb->prefix . 'na_events';

    // Fetch events for filtering
    $events = $wpdb->get_results("SELECT id, name, date FROM $na_events_table");

    // Handle search and filter inputs
    $search_query = isset($_POST['search_query']) ? sanitize_text_field($_POST['search_query']) : '';
    $filter_event = isset($_POST['filter_event']) ? sanitize_text_field($_POST['filter_event']) : '';
    $filter_date = isset($_POST['filter_date']) ? sanitize_text_field($_POST['filter_date']) : '';

    // Build the query with filters and search
    $query = "SELECT p.*, e.name as event_name, e.date as event_date
              FROM $event_participants_table p
              JOIN $na_events_table e ON p.event_id = e.id
              WHERE 1=1";

    if ($search_query) {
        $query .= $wpdb->prepare(" AND (p.first_name LIKE %s OR p.last_name LIKE %s OR p.email LIKE %s OR p.phone_number LIKE %s)", 
            '%' . $search_query . '%', '%' . $search_query . '%', '%' . $search_query . '%', '%' . $search_query . '%');
    }

    if ($filter_event) {
        $query .= $wpdb->prepare(" AND e.id = %d", $filter_event);
    }

    if ($filter_date) {
        $query .= $wpdb->prepare(" AND e.date = %s", $filter_date);
    }

    $participants = $wpdb->get_results($query);

    // Output the table
    echo '<div class="wrap">';
    echo '<h1>گزارش ثبت نام ها</h1>';

    // Search and filter form
    echo '<form method="post">';
    echo '<input type="text" name="search_query" placeholder="جستجو..." value="' . esc_attr($search_query) . '">';
    echo '<select name="filter_event">';
    echo '<option value="">انتخاب رویداد</option>';
    foreach ($events as $event) {
        $selected = ($filter_event == $event->id) ? 'selected' : '';
        echo '<option value="' . esc_attr($event->id) . '" ' . $selected . '>' . esc_html($event->name) . '</option>';
    }
    echo '</select>';
    echo '<input type="date" name="filter_date" value="' . esc_attr($filter_date) . '">';
    echo '<input type="submit" class="button button-primary" value="فیلتر">';
    echo '</form>';

    // Display table
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr>';
    echo '<th>نام</th>';
    echo '<th>نام خانوادگی</th>';
    echo '<th>ایمیل</th>';
    echo '<th>کد ملی</th>';
    echo '<th>تاریخ تولد</th>';
    echo '<th>نام همراه</th>';
    echo '<th>شماره موبایل</th>';
    echo '<th>آدرس</th>';
    echo '<th>بیوگرافی</th>';
    echo '<th>بیوگرافی والدین</th>';
    echo '<th>کد پیگیری</th>';
    echo '<th>سابقه بیماری</th>';
    echo '<th>توضحات تکمیلی</th>';
    echo '<th>قیمت</th>';
    echo '<th>نام رویداد</th>';
    echo '<th>تاریخ رویداد</th>';
    echo '<th>تاریخ ثبت‌نام</th>';
    echo '<th>کد مرجع</th>';
    echo '<th>وضعیت پرداخت</th>';
    echo '</tr></thead><tbody>';

    foreach ($participants as $participant) {
        echo '<tr>';
        echo '<td>' . esc_html($participant->first_name) . '</td>';
        echo '<td>' . esc_html($participant->last_name) . '</td>';
        echo '<td>' . esc_html($participant->email) . '</td>';
        echo '<td>' . esc_html($participant->national_code) . '</td>';
        echo '<td>' . esc_html($participant->birth_date) . '</td>';
        echo '<td>' . esc_html($participant->companion_name) . '</td>';
        echo '<td>' . esc_html($participant->phone_number) . '</td>';
        echo '<td>' . esc_html($participant->address) . '</td>';
        echo '<td>' . esc_html($participant->bio) . '</td>';
        echo '<td>' . esc_html($participant->parents_bio) . '</td>';
        echo '<td>' . esc_html($participant->tracking_code) . '</td>';
        echo '<td>' . esc_html($participant->disease_background) . '</td>';
        echo '<td>' . esc_html($participant->note) . '</td>';
        echo '<td>' . esc_html($participant->price) . '</td>';
        echo '<td>' . esc_html($participant->event_name) . '</td>';
        echo '<td>' . esc_html($participant->event_date) . '</td>';
        echo '<td>' . esc_html($participant->time_stamp) . '</td>';
        echo '<td>' . esc_html($participant->authority) . '</td>';
        echo '<td>' . esc_html($participant->payment_status) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '<form method="post">';
    echo '<input type="hidden" name="search_query" value="' . esc_attr($search_query) . '">';
    echo '<input type="hidden" name="filter_event" value="' . esc_attr($filter_event) . '">';
    echo '<input type="hidden" name="filter_date" value="' . esc_attr($filter_date) . '">';
    echo '<input type="submit" name="download_csv" class="button button-primary" value="دانلود CSV">';
    echo '</form>';
    echo '</div>';

    // Handle CSV download
    if (isset($_POST['download_csv'])) {
        na_download_csv($participants);
    }
}

// Download CSV function
function na_download_csv($participants) {
    $filename = 'participants_list_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename=' . $filename);

    $output = fopen('php://output', 'w');

    // Column headers
    fputcsv($output, array(
        'نام', 'نام خانوادگی', 'ایمیل', 'کد ملی', 'تاریخ تولد', 
        'نام همراه', 'شماره موبایل', 'آدرس', 'بیوگرافی', 
        'بیوگرافی والدین', 'کد پیگیری', 'سابقه بیماری', 
        'توضحات تکمیلی', 'قیمت', 'نام رویداد', 'تاریخ رویداد',
        'تاریخ ثبت‌نام', 'کد مرجع', 'وضعیت پرداخت'
    ));

    // Rows
    foreach ($participants as $participant) {
        fputcsv($output, array(
            $participant->first_name,
            $participant->last_name,
            $participant->email,
            $participant->national_code,
            $participant->birth_date,
            $participant->companion_name,
            $participant->phone_number,
            $participant->address,
            $participant->bio,
            $participant->parents_bio,
            $participant->tracking_code,
            $participant->disease_background,
            $participant->note,
            $participant->price,
            $participant->event_name,
            $participant->event_date,
            $participant->time_stamp,
            $participant->authority,
            $participant->payment_status
        ));
    }

    fclose($output);
    exit;
}
?>
