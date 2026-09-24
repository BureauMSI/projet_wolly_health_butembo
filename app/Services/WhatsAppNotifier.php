<?php

namespace App\Services;

use App\Models\WhatsappOutbox;
use App\Models\WhatsappTemplate;
use App\Support\PlanConfig;

class WhatsAppNotifier
{
    public function __construct(
        private PlanConfig $plan,
        private SyncOutbox $outbox,
    ) {}

    public function enqueue(string $toPhone, string $templateKey, string $locale, array $payload): WhatsappOutbox
    {
        $template = WhatsappTemplate::query()
            ->where('key', $templateKey)
            ->where('locale', $locale)
            ->first();

        $body = (string) ($template?->body ?? '');
        foreach ($payload as $key => $value) {
            $body = str_replace('{{'.$key.'}}', (string) $value, $body);
        }

        $row = WhatsappOutbox::query()->create([
            'origin_device_id' => $this->plan->originDeviceId(),
            'to_phone' => $toPhone,
            'template_key' => $templateKey,
            'locale' => $locale,
            'payload_json' => $payload,
            'body_rendered' => $body,
            'driver' => 'wa_me',
            'status' => 'pending',
        ]);

        $this->outbox->enqueue(
            'whatsapp_outbox',
            $row->uuid,
            'create',
            $row->toArray(),
            $this->plan->originDeviceId(),
        );

        return $row;
    }

    public function waMeUrl(WhatsappOutbox $row): string
    {
        $digits = preg_replace('/\D+/', '', $row->to_phone) ?? '';
        if (str_starts_with($digits, '0')) {
            $digits = '243'.substr($digits, 1);
        }

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($row->body_rendered);
    }
}
