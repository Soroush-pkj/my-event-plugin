<?php



function generate_tracking_code() {
    // Generate a unique tracking code using a prefix, timestamp, and a random string
    $prefix = 'NFI-';
    $timestamp = time();
    $timestamp = substr($timestamp, 6);
    $random_string = wp_generate_password(3, false);
    return $prefix . $timestamp . '-' . $random_string;
}



add_shortcode('event_invoice', 'display_event_invoice');
function  display_event_invoice(){

    ob_start();
    global $wpdb;
    $tracking_code = $_GET['tracking_code'];
    $price = $_GET['price'];
    // show tracking_code and price and button for payment

    // curl to get payment link
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.zarinpal.com/pg/v4/payment/request.json',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS =>'{
  "merchant_id": "5fbe8c53-9384-4c49-a605-06fceb63da16",
  "currency": "IRT",
  "amount": ' . $price . ',
  "callback_url": "https://naturefriends.ir/payment-callback",
  "description": "خرید ایونت",
  "metadata": {"mobile": "09121234567","email": "$tracking_code"}
}',
        CURLOPT_HTTPHEADER => array(
            'accept: application/json',
            'content-type: application/json'
        ),
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    $response = json_decode($response);
    $payment_link = 'https://www.zarinpal.com/pg/StartPay/' . $response->data->authority;

    $event_participants_table = $wpdb->prefix . 'event_participants';
    $result = $wpdb->get_results("SELECT * FROM $event_participants_table WHERE tracking_code = '$tracking_code'");
    if (empty($result)) {
        return 'کد پیگیری نامعتبر است';
    }
    // update authority in database
    $wpdb->update($event_participants_table, ['authority' => $response->data->authority], ['tracking_code' => $tracking_code]);

    $na_events_table = $wpdb->prefix . 'na_events';
    $event_id = $result[0]->event_id;
    $event = $wpdb->get_results("SELECT * FROM $na_events_table WHERE id = '$event_id'");

    ?>
    <h1>روییداد  <?php echo $event[0]->name; ?>  </h1>
    <h2>مبلغ قابل پرداخت <?php echo number_format($price); ?> تومان </h2>
    <h3>جهت تایید نهایی ثبت نام لطفا بر روی دکمه پرداخت کلیک کنید</h3>
    <a class="payment-button" href="<?php echo $payment_link; ?>">پرداخت</a>
    <?php
    return ob_get_clean();





}

add_shortcode('invoice_callback', 'display_invoice_callback');
function display_invoice_callback () {
    ob_start();
    global $wpdb;
    $authority = $_GET['Authority'];
    $status = $_GET['Status'];

    $event_participants_table = $wpdb->prefix . 'event_participants';
    $na_events_table = $wpdb->prefix . 'na_events';

    $result = $wpdb->get_results("SELECT * FROM $event_participants_table WHERE authority = '$authority'");
    if (empty($result)) {
        return 'کد پیگیری نامعتبر است';
    }

    if ( $result[0]->payment_status == 'success' ) {
        return 'پرداخت شما با موفقیت انجام شد .همچنین رسید پرداخت به ایمیل شما ارسال شد در صورت عدم مشاهده لطفا پوشه اسپم را چک کنید ';
    }

    if ( $result[0]->payment_status == 'fail' ) {
        return 'تراکنش شما ناموفق بوده لطفا دوباره ثبت نام کنید';
    }
    $price = $result[0]->price;

    $data = array("merchant_id" => "5fbe8c53-9384-4c49-a605-06fceb63da16", "authority" => $authority, "amount" => $price);
    $jsonData = json_encode($data);
    $ch = curl_init('https://api.zarinpal.com/pg/v4/payment/verify.json');
    curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v4');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ));

    $response = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($response,true);
    if($response['data']['code']  == 100) {
        $wpdb->update($event_participants_table, ['payment_status' => 'success'], ['authority' => $authority]);

        $update_result = $wpdb->query($wpdb->prepare(
            "UPDATE $na_events_table SET successful_registered = successful_registered + 1, remaining_capacity = remaining_capacity - 1 WHERE id = %d",
            $result[0]->event_id
        ));

        // Send the email
        $first_name = $result[0]->first_name;
        $last_name = $result[0]->last_name;
        $email = $result[0]->email;
        $phone_number = $result[0]->phone_number;
        $tracking_code = $result[0]->tracking_code;

        $subject = 'ثبت نام موفق';
        $message = "$first_name $last_name عزیز\n\nثبت نام شما با شماره موبایل $phone_number با موفقیت انجام شد.\n\nکد پیگیری شما: $tracking_code";

        wp_mail($email, $subject, $message);

        echo $message;
        echo '<br>';
        echo 'پرداخت شما با موفقیت انجام شد .همچنین رسید پرداخت به ایمیل شما ارسال شد در صورت عدم مشاهده لطفا پوشه اسپم را چک کنید';
    } else {
        $wpdb->update($event_participants_table, ['payment_status' => 'fail'], ['authority' => $authority]);
        echo 'پرداخت ناموفق بود لطفا دوباره ثبت نام کنید';
    }
}




