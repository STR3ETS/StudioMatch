<?php

namespace App\Http\Controllers\Api\App;

use App\Actions\ChangeBookingStatus;
use App\Actions\HandleTicket;
use App\Actions\ReviewRoom;
use App\Enums\BookingStatus;
use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Post;
use App\Models\Room;
use App\Models\User;
use App\Support\AdminDesk;
use App\Support\AppPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * De beheerder in de app: overzicht, tickets (ook afhandelen), de goedkeuringswachtrij (beoordelen),
 * boekingen (status wijzigen), gebruikers, omzet en de blog. Dezelfde gegevens als de website via
 * AdminDesk, dezelfde regels via de acties (HandleTicket, ReviewRoom, ChangeBookingStatus). Alleen
 * blogartikelen schrijven en de CSV-exports blijven op de website.
 */
class AdminController extends Controller
{
    public function overview(): JsonResponse
    {
        $counts = AdminDesk::overview();

        return response()->json([
            'open_disputes' => $counts['openDisputes'],
            'open_damages' => $counts['openDamages'],
            'in_review' => $counts['inReviewCount'],
            'live_rooms' => $counts['liveCount'],
            'studios' => $counts['studioCount'],
            'artists' => $counts['artistCount'],
            'hosts' => $counts['hostCount'],
        ]);
    }

    // ===== Tickets =====

    public function tickets(): JsonResponse
    {
        return response()->json([
            'disputes' => AdminDesk::disputes()->map(fn (Booking $booking) => AppPayload::adminTicket($booking, 'dispute'))->values(),
            'damages' => AdminDesk::damages()->map(fn (Booking $booking) => AppPayload::adminTicket($booking, 'damage'))->values(),
        ]);
    }

    public function resolveTicket(Request $request, Booking $booking, HandleTicket $handle): JsonResponse
    {
        if ($booking->status !== BookingStatus::Disputed) {
            return $this->ticketResult(HandleTicket::ALREADY_RESOLVED, 409);
        }

        $validated = $request->validate(HandleTicket::RESOLVE_RULES);

        return $this->ticketResult($handle->resolveDispute($booking, (int) $validated['refund_percent'], $validated['resolution_note']));
    }

    public function respondToTicket(Request $request, Booking $booking, HandleTicket $handle): JsonResponse
    {
        abort_if($booking->damage_reported_at === null, 404);

        if ($booking->damage_resolved_at !== null) {
            return $this->ticketResult(HandleTicket::ALREADY_RESOLVED, 409);
        }

        $validated = $request->validate(HandleTicket::RESPOND_RULES);

        return $this->ticketResult($handle->respondToDamage($booking, $validated['damage_response']));
    }

    /** De melding van de website en de bijgewerkte tickets. */
    private function ticketResult(string $outcome, int $status = 200): JsonResponse
    {
        return response()->json([
            'outcome' => $outcome,
            'message' => __('admin.tickets.'.$outcome),
            'tickets' => $this->tickets()->getData(true),
        ], $status);
    }

    // ===== Goedkeuringswachtrij =====

    public function queue(): JsonResponse
    {
        return response()->json([
            'rooms' => AdminDesk::queue()->map(fn (Room $room) => AppPayload::adminRoom($room))->values(),
        ]);
    }

    public function room(Room $room, ReviewRoom $review): JsonResponse
    {
        $room->load(['studio.user.hostProfile', 'photos', 'hours']);

        return response()->json(AppPayload::adminRoomDetail($room, $review->stripeBlocked($room)));
    }

    public function approveRoom(Room $room, ReviewRoom $review): JsonResponse
    {
        $outcome = $review->approve($room);

        return response()->json([
            'outcome' => $outcome,
            'message' => match ($outcome) {
                ReviewRoom::APPROVED => __('admin.queue.approved', ['room' => $room->title]),
                ReviewRoom::STRIPE_REQUIRED => __('admin.queue.stripe_required', ['host' => $room->studio->user->name]),
                default => __('admin.queue.not_in_review'),
            },
        ], $outcome === ReviewRoom::APPROVED ? 200 : 409);
    }

    public function rejectRoom(Request $request, Room $room, ReviewRoom $review): JsonResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $outcome = $review->reject($room, $validated['rejection_reason']);

        return response()->json([
            'outcome' => $outcome,
            'message' => $outcome === ReviewRoom::REJECTED
                ? __('admin.queue.rejected', ['room' => $room->title])
                : __('admin.queue.not_in_review'),
        ], $outcome === ReviewRoom::REJECTED ? 200 : 409);
    }

    // ===== Boekingen =====

    public function bookings(Request $request): JsonResponse
    {
        $status = $request->validate([
            'status' => ['nullable', Rule::enum(BookingStatus::class)],
        ])['status'] ?? null;

        return response()->json([
            'bookings' => AdminDesk::bookings($status)->map(fn (Booking $booking) => AppPayload::adminBooking($booking))->values(),
            'statuses' => collect(BookingStatus::cases())->map(fn (BookingStatus $case) => [
                'value' => $case->value,
                'label' => __('booking.status.'.$case->value),
            ])->values(),
        ]);
    }

    public function changeBookingStatus(Request $request, Booking $booking, ChangeBookingStatus $change): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(BookingStatus::class)],
        ]);

        $change($booking, BookingStatus::from($validated['status']));

        return response()->json([
            'message' => __('admin.bookings.updated'),
            'booking' => AppPayload::adminBooking($booking->fresh(['room.studio', 'user'])),
        ]);
    }

    // ===== Gebruikers, omzet, blog =====

    public function users(Request $request): JsonResponse
    {
        $role = $request->validate([
            'rol' => ['nullable', Rule::in([UserRole::Artiest->value, UserRole::Verhuurder->value])],
        ])['rol'] ?? null;

        return response()->json([
            'users' => AdminDesk::users($role)->map(fn (User $user) => AppPayload::adminUser($user))->values(),
        ]);
    }

    public function revenue(): JsonResponse
    {
        $revenue = AdminDesk::revenue();

        return response()->json([
            'total_count' => $revenue['totalCount'],
            'total_rent_cents' => $revenue['totalRent'],
            'total_fees_cents' => $revenue['totalFees'],
            'studios' => $revenue['studios']->map(fn (array $row) => [
                'id' => $row['studio']->id,
                'name' => $row['studio']->name,
                'city' => $row['studio']->city,
                'host' => $row['studio']->user->name,
                'count' => $row['count'],
                'rent_cents' => $row['rent'],
                'fees_cents' => $row['fees'],
            ])->values(),
        ]);
    }

    public function posts(Request $request): JsonResponse
    {
        $status = $request->validate([
            'status' => ['nullable', Rule::enum(PostStatus::class)],
        ])['status'] ?? null;

        return response()->json([
            'posts' => AdminDesk::posts($status)->map(fn (Post $post) => AppPayload::adminPost($post))->values(),
            'new_path' => route('admin.posts.create', [], false),
        ]);
    }

    public function deletePost(Post $post): JsonResponse
    {
        $title = $post->title;
        $post->delete();

        return response()->json(['message' => __('admin.blog.deleted', ['title' => $title])]);
    }
}
