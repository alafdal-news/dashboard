<?php

namespace App\Services;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    protected Messaging $messaging;
    protected string $defaultTopic = 'all';

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    /**
     * Send push notification for a new article
     *
     * @param int $articleId
     * @param string $title
     * @param string|null $body
     * @return bool
     */
    public function sendArticleNotification(int $articleId, string $title, ?string $body = null): bool
    {
        // Decode HTML entities and clean the title
        $cleanTitle = html_entity_decode(stripslashes($title), ENT_QUOTES, 'UTF-8');
        
        // Use title as body if no body provided
        $notificationBody = $body ? html_entity_decode(stripslashes($body), ENT_QUOTES, 'UTF-8') : $cleanTitle;
        
        // Truncate body if too long (FCM has limits)
        $notificationBody = mb_strlen($notificationBody) > 200 
            ? mb_substr($notificationBody, 0, 197) . '...' 
            : $notificationBody;

        $message = CloudMessage::fromArray([
            'topic' => $this->defaultTopic,
            'notification' => [
                'title' => 'Alafdal News',
                'body' => $notificationBody,
                'sound' => 'default',
            ],
            'data' => [
                'related_id' => (string) $articleId,
                'notification_type' => 'article',
                'vibrate' => '1',
                'date' => now()->format('Y-m-d H:i:s'),
                'sound' => '1',
                'badge' => '0',
            ],
            // Android-specific configuration
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'default_vibrate_timings' => true,
                    'channel_id' => 'alafdal_news_channel',
                ],
            ],
            // iOS-specific configuration (APNs)
            'apns' => [
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => 'Alafdal News',
                            'body' => $notificationBody,
                        ],
                        'sound' => 'default',
                        'badge' => 1,
                    ],
                ],
            ],
        ]);

        try {
            $this->messaging->send($message);
            
            Log::info('Firebase notification sent successfully', [
                'article_id' => $articleId,
                'topic' => $this->defaultTopic,
            ]);
            
            return true;
        } catch (\Kreait\Firebase\Exception\MessagingException $e) {
            Log::error('Firebase messaging error', [
                'article_id' => $articleId,
                'error' => $e->getMessage(),
            ]);
            return false;
        } catch (\Kreait\Firebase\Exception\FirebaseException $e) {
            Log::error('Firebase general error', [
                'article_id' => $articleId,
                'error' => $e->getMessage(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Unexpected error sending Firebase notification', [
                'article_id' => $articleId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send notification to a specific topic
     *
     * @param string $topic
     * @param string $title
     * @param string $body
     * @param array $data
     * @return bool
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        $message = CloudMessage::withTarget('topic', $topic)
            ->withNotification(Notification::create($title, $body))
            ->withData($data);

        try {
            $this->messaging->send($message);
            return true;
        } catch (\Throwable $e) {
            Log::error('Firebase topic notification error', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Set the default topic for notifications
     *
     * @param string $topic
     * @return self
     */
    public function setDefaultTopic(string $topic): self
    {
        $this->defaultTopic = $topic;
        return $this;
    }
}
