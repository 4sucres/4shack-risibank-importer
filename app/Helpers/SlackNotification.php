<?php

namespace App\Helpers;

/**
 * https://gist.github.com/alexstone/9319715
 */

class SlackNotification
{
    // (string) $message - message to be passed to Slack
    // (string) $room - room in which to write the message, too
    // (string) $icon - You can set up custom emoji icons to use with each message
    public static function send($message, $room = "engineering", $icon = ":longbox:")
    {
        if (!$slack_webhook_url = env('SLACK_WEBHOOK_URL')) return null;

        $room = ($room) ? $room : "engineering";
        $data = "payload=" . json_encode(array(
            "channel"       =>  "#{$room}",
            "text"          =>  $message,
            "icon_emoji"    =>  $icon
        ));

        // You can get your webhook endpoint from your Slack settings
        $ch = curl_init($slack_webhook_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        // Laravel-specific log writing method
        // Log::info("Sent to Slack: " . $message, array('context' => 'Notifications'));
        return $result;
    }
}
