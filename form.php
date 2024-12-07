<?php
// Shortcode to display the registration form
add_shortcode('event_registration_form', 'display_event_registration_form');
function display_event_registration_form() {
    global $wpdb;
    $na_events_table = $wpdb->prefix . 'na_events';

    // Fetch all events including the price
    $events = $wpdb->get_results("SELECT id, name, remaining_capacity, date, price,time FROM $na_events_table");

    ob_start(); // Start output buffering
    ?>
    <style>
        .capacity-completed {
			font-family: "iranyekan", Sans-serif;
            color: red !important;
            font-weight: bold;
        }
        .disabled-radio {
			font-family: "iranyekan", Sans-serif;
            opacity: 0.5;
            pointer-events: none;
        }

        .events-container {
			font-family: "iranyekan", Sans-serif;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            width: 46%;
        }

        .event-box {
			font-family: "iranyekan", Sans-serif;
            flex: 1 1 calc(50% - 20px); /* Take half of the container width, minus the gap */
            box-sizing: border-box;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color: #f9f9f9;
            transition: box-shadow 0.3s ease;
        }

        .event-box:hover {
			font-family: "iranyekan", Sans-serif;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .event-box input[type="radio"] {
			font-family: "iranyekan", Sans-serif;
            margin-right: 10px;
        }

        .event-box label {
			font-family: "iranyekan", Sans-serif;
            display: flex;
            flex-direction: column;
        }

        .event-box span {
			font-family: "iranyekan", Sans-serif;
            margin-top: 5px;
            color: #666;
        }

        .main-event-box-container{
			font-family: "iranyekan", Sans-serif;
            display: flex;
            gap: 20px;
            flex-direction: row;
            flex-wrap: wrap;
        }

        .regular{
			font-family: "iranyekan", Sans-serif;
            line-height: 22px;
        }

        .event-title-h5{
			font-family: "iranyekan", Sans-serif;
            margin: 0 0 8px 0;
        }
		.date-inboxes{
			font-family: "iranyekan", Sans-serif;
			display:inline;
			margin-right:4px!important;
		}
		label{
			font-family: "iranyekan", Sans-serif;
			 margin-bottom: 8px;
		}
    </style>
    <form method="post">
        <h3>روز مورد نظر برای شرکت در جشنواره را انتخاب فرمایید.</h3>
        <div class="main-event-box-container">
        <?php
        foreach ($events as $event) {
            $is_disabled = $event->remaining_capacity <= 0;
            $disabled_class = $is_disabled ? 'disabled-radio capacity-completed' : '';
            $capacity_text = $is_disabled ? '0 تکمیل ' : " {$event->remaining_capacity} نفر";
            $price_text = $event->price !== null ? number_format($event->price) . 'تومان ' : 'بدون قیمت ';
            $date = $event->date !== null ? $event->date : 'بدون تاریخ';
//             $time = $event->time !== null ? $event->time : 'بدون زمان';
            ?>
        
            <div class="events-container">
                <div class="event-box">
                    <input type="radio" name="event_id" value="<?php echo esc_attr($event->id); ?>" <?php echo $is_disabled ? 'disabled' : ''; ?> class="<?php echo esc_attr($disabled_class); ?>" required>
                    <label>
                        <h5 class="event-title-h5">
                            <?php echo esc_html($event->name); ?>
                        </h5>
                        <span class="regular <?php echo $is_disabled ? $disabled_class : ''; ?>">
                            ظرفیت باقی‌مانده: <?php echo esc_html($capacity_text); ?> <br> قیمت: <?php echo esc_html($price_text); ?> <br> تاریخ: <p class="date-inboxes"><?php echo esc_html($date); ?> </p>
                        </span>
                    </label>
                </div>
            </div>

            <?php
        }
        ?>
        </div>
        <h3>لطفا فرم ثبت نام را تکمیل فرمایید</h3>
        <!-- Form fields for participant information -->
        <label for="first_name">نام</label>
        <input type="text" name="first_name" required><br>

        <label for="last_name">نام خانوادگی</label>
        <input type="text" name="last_name" required><br>

        <label for="email">ایمیل</label>
        <input type="text" name="email" required><br>

        <label for="national_code">کدملی</label>
        <input type="text" name="national_code" required><br>

        <label for="birth_date">تاریخ تولد</label>
        <input class="event-participent-birthdate" type="text" name="birth_date"><br>

        <label for="companion_name">نام همراه</label>
        <input type="text" name="companion_name"><br>

        <label for="phone_number">موبایل</label>
        <input type="text" name="phone_number"><br>

        <label for="address">آدرس</label>
        <textarea name="address"></textarea><br>

        <label for="bio">بیوگرافی</label>
        <textarea name="bio"></textarea><br>

        <label for="parents_bio">بیوگرافی والدین</label>
        <textarea name="parents_bio"></textarea><br>

        <label for="disease_background">سابقه بیماری</label>
        <textarea name="disease_background" required></textarea><br>

        <label for="note">توضحات تکمیلی</label>
        <textarea name="note"></textarea><br>

        <input type="submit" name="submit_event_registration" value="ثبت نام">
    </form>
    <?php

    // Handle form submission
    if (isset($_POST['submit_event_registration'])) {

        // Sanitize the form data
        $event_id = intval($_POST['event_id']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $email = sanitize_text_field($_POST['email']);
        $national_code = sanitize_text_field($_POST['national_code']);
        $birth_date = sanitize_text_field($_POST['birth_date']);
        $companion_name = sanitize_text_field($_POST['companion_name']);
        $phone_number = sanitize_text_field($_POST['phone_number']);
        $address = sanitize_textarea_field($_POST['address']);
        $bio = sanitize_textarea_field($_POST['bio']);
        $parents_bio = sanitize_textarea_field($_POST['parents_bio']);
        $disease_background = sanitize_textarea_field($_POST['disease_background']);
        $note = sanitize_textarea_field($_POST['note']);

        $event_participants_table = $wpdb->prefix . 'event_participants';
        $na_events_table = $wpdb->prefix . 'na_events';

        $event = $wpdb->get_results("SELECT id, name, remaining_capacity, price FROM $na_events_table WHERE id = $event_id");
        $price = $event[0]->price;

        $tracking_code = generate_tracking_code();

        // Insert participant data
        $result = $wpdb->insert($event_participants_table, [
            'event_id' => $event_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'national_code' => $national_code,
            'birth_date' => $birth_date,
            'companion_name' => $companion_name,
            'phone_number' => $phone_number,
            'address' => $address,
            'bio' => $bio,
            'parents_bio' => $parents_bio,
            'tracking_code' => $tracking_code,
            'disease_background' => $disease_background,
            'note' => $note,
            'price' => $price,
        ]);

        if ($result === false) {
            // Output the SQL query and error for debugging
            echo '<p>Database insert failed. SQL: ' . $wpdb->last_query . '</p>';
            echo '<p>Error: ' . $wpdb->last_error . '</p>';
            return ob_get_clean();
        }

        echo '<script type="text/javascript">
            alert("پیش ثبت نام شما برای شماره همراه ' . $phone_number . '.با موفقیت ایجاد شد. جهت تایید نهایی ثبت نام مرحله پرداخت را تکمیل فرمایید");
            window.location.href = "' . home_url('/event-invoice?tracking_code='.$tracking_code.'&price='.$price) . '";
        </script>';
    }

    return ob_get_clean(); // Return the form HTML
}
