<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Listing;
use App\Models\WhatsappOutbox;
use App\Services\WhatsAppNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WhatsappOutboxController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->canUseWhatsappOutbox(), 403);

        return view('admin.whatsapp.index', [
            'messages' => WhatsappOutbox::query()->latest()->paginate(Listing::PER_PAGE),
        ]);
    }

    public function open(WhatsappOutbox $outbox, WhatsAppNotifier $whatsapp): RedirectResponse
    {
        abort_unless(auth()->user()?->canUseWhatsappOutbox(), 403);
        $outbox->update(['status' => 'opened']);

        return redirect()->away($whatsapp->waMeUrl($outbox));
    }
}
