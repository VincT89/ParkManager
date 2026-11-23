<?php

namespace Tests\Feature;

use App\Models\Parking;
use App\Models\Reservation;
use App\Services\WhatsAppReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Support\ManagementFixtures;
use Tests\TestCase;

class WhatsAppReviewInvitationsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $legacyFixtures = false;

    private array $fixtures;

    private const MESSAGE_API = 'https://api.twilio.com/2010-04-01/Accounts/*/Messages.json';

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->fixtures = ManagementFixtures::create();
    }

    private function settings(string $park = 'B'): array
    {
        $digit = $park === 'A' ? 'a' : 'b';

        return [
            'account_sid' => 'AC'.str_repeat($digit, 32),
            'api_key_sid' => 'SK'.str_repeat($digit, 32),
            'api_key_secret' => Str::random(32),
            'messaging_service_sid' => 'MG'.str_repeat($digit, 32),
            'content_sid' => 'HX'.str_repeat($digit, 32),
            'sender' => $park === 'A' ? '+12025550105' : '+12025550106',
            'review_url' => 'https://g.page/r/review-'.$digit.'-demo/review',
        ];
    }

    private function enable(string $park = 'B'): Parking
    {
        $parking = $this->fixtures['parks'][$park]['parking'];
        $parking->whatsapp_review_settings = $this->settings($park);
        $parking->whatsapp_reviews_enabled_at = now()->timestamp;
        $parking->save();

        return $parking;
    }

    private function recordExit(Reservation $reservation, bool $value = true): void
    {
        $this->postJson(route('reservations.toggle-movement', $reservation), [
            '_parking_context' => $reservation->parking_id,
            'type' => 'exited',
            'value' => $value,
        ])->assertOk()->assertJson(['success' => true]);
    }

    private function dueReservation(string $park = 'B'): Reservation
    {
        $parking = $this->enable($park);
        $reservation = $this->fixtures['parks'][$park]['reservation'];
        $phone = $park === 'A' ? '+12025550101' : '+12025550102';
        $reservation->customer_phone = $phone;
        $reservation->whatsapp_review_consent = WhatsAppReviewService::consent($phone, 'operator');
        $reservation->has_exited = true;
        $reservation->exited_at = $parking->whatsapp_reviews_enabled_at;
        $reservation->save();

        return $reservation;
    }

    private function runSender(): void
    {
        $this->artisan('whatsapp:send-review-invitations')->assertSuccessful();
    }

    public function test_admin_can_save_and_disable_settings_for_own_parking_without_exposing_the_secret(): void
    {
        $parking = $this->fixtures['parks']['B']['parking'];
        $settings = $this->settings();
        $secret = $settings['api_key_secret'];
        unset($settings['api_key_secret']);
        $edit = route('parkings.whatsapp-reviews.edit', $parking);
        $update = route('parkings.whatsapp-reviews.update', $parking);
        $context = ['_parking_context' => $parking->id];

        $this->actingAs($this->fixtures['partner'])->get($edit)->assertOk()->assertSee('WhatsApp e recensioni');
        $this->from($edit)->put($update, $context + ['enabled' => '1'])->assertSessionHasErrors('enabled');
        $this->assertNull($parking->fresh()->whatsapp_reviews_enabled_at);

        $this->from($edit)->put($update, $context + array_replace($settings, ['review_url' => 'https://g.page.attacker.example/r/demo/review']) + [
            'enabled' => '1', 'credentials' => ['api_key_secret' => $secret],
        ])->assertSessionHasErrors('review_url')->assertSessionMissing('_old_input.credentials');

        $this->put($update, $context + $settings + [
            'enabled' => '1', 'credentials' => ['api_key_secret' => $secret],
        ])->assertRedirect($edit)->assertSessionHasNoErrors();
        $saved = $parking->fresh();
        $this->assertTrue($saved->whatsappReviewsEnabled());
        $this->assertSame($secret, $saved->whatsapp_review_settings['api_key_secret']);
        $this->assertStringNotContainsString($secret, $saved->getRawOriginal('whatsapp_review_settings'));
        $this->assertArrayNotHasKey('whatsapp_review_settings', $saved->toArray());
        $this->get($edit)->assertOk()->assertSee('Chiave salvata')->assertDontSee($secret);

        $this->from($edit)->put($update, $context + ['enabled' => '1', 'api_key_sid' => 'SK'.str_repeat('c', 32)])
            ->assertSessionHasErrors('enabled');
        $this->assertSame($settings['api_key_sid'], $parking->fresh()->whatsapp_review_settings['api_key_sid']);

        $this->put($update, $context + ['enabled' => '0'])->assertRedirect($edit);
        $this->assertNull($parking->fresh()->whatsapp_reviews_enabled_at);
        $this->assertSame($secret, $parking->fresh()->whatsapp_review_settings['api_key_secret']);
    }

    public function test_settings_and_consent_writes_respect_parking_and_staff_permissions(): void
    {
        $other = $this->fixtures['parks']['A'];
        $parking = $this->fixtures['parks']['B']['parking'];
        $context = ['_parking_context' => $parking->id];

        $this->actingAs($this->fixtures['partner']);
        $this->get(route('parkings.whatsapp-reviews.edit', $other['parking']))->assertNotFound();
        $this->put(route('parkings.whatsapp-reviews.update', $other['parking']), $context + ['enabled' => '0'])->assertNotFound();
        $this->put(route('reservations.whatsapp-review-consent', $other['reservation']), $context + ['consent' => '1'])->assertNotFound();
        $this->put(route('parkings.whatsapp-reviews.update', $parking), ['_parking_context' => $other['parking']->id, 'enabled' => '0'])->assertConflict();

        $this->actingAs($this->fixtures['staff']);
        $this->get(route('parkings.whatsapp-reviews.edit', $parking))->assertForbidden();
        $this->put(route('parkings.whatsapp-reviews.update', $parking), $context + ['enabled' => '0'])->assertForbidden();
    }

    public function test_send_is_due_24_elapsed_hours_after_the_actual_exit_and_never_repeated(): void
    {
        $exit = Carbon::parse('2027-03-27 10:00:00', 'Europe/Rome');
        $this->travelTo($exit->copy()->subHour());
        $parking = $this->enable();
        $settings = $parking->whatsapp_review_settings;
        $reservation = $this->fixtures['parks']['B']['reservation'];
        $reservation->update(['customer_phone' => '001 202 555 0102', 'ends_at' => $exit->copy()->subMinutes(30)]);
        $this->actingAs($this->fixtures['staff']);
        $this->put(route('reservations.whatsapp-review-consent', $reservation), [
            '_parking_context' => $parking->id, 'consent' => '1',
        ])->assertRedirect(route('reservations.show', $reservation));

        $messageSid = 'SM'.str_repeat('1', 32);
        Http::fake([self::MESSAGE_API => Http::response(['sid' => $messageSid, 'status' => 'queued'], 201)]);
        $this->travelTo($exit);
        $this->recordExit($reservation);
        $this->travelTo($exit->copy()->addMinutes(5));
        $this->recordExit($reservation);
        $this->assertSame($exit->timestamp, $reservation->fresh()->exited_at);

        $due = Carbon::createFromTimestamp($exit->timestamp + 86400, 'Europe/Rome');
        $this->assertSame('11:00', $due->format('H:i'));
        $this->travelTo($due->copy()->subSecond());
        $this->runSender();
        Http::assertNothingSent();

        $this->travelTo($due);
        $this->runSender();
        $this->runSender();
        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/'.$settings['account_sid'].'/Messages.json'
            && $request['From'] === 'whatsapp:'.$settings['sender']
            && $request['To'] === 'whatsapp:+12025550102'
            && $request['MessagingServiceSid'] === $settings['messaging_service_sid']
            && $request['ContentSid'] === $settings['content_sid']
            && json_decode($request['ContentVariables'], true) === [1 => $parking->name, 2 => $settings['review_url']]
            && ! isset($request['Body']));
        $this->assertSame('accepted', $reservation->fresh()->whatsapp_review_status);
        $this->assertSame($messageSid, $reservation->fresh()->whatsapp_review_message_sid);

        $this->recordExit($reservation, false);
        $this->recordExit($reservation);
        $this->travel(25)->hours();
        $this->runSender();
        Http::assertSentCount(1);
    }

    public function test_worker_only_sends_for_eligible_reservations_and_uses_their_own_parking_settings(): void
    {
        $this->freezeTime();
        $reservationA = $this->dueReservation('A');
        $reservationB = $this->dueReservation('B');
        $fixtures = $this->fixtures['parks']['B'];
        foreach ([
            'legacy-exit' => ['exited_at' => null],
            'prior-exit' => ['exited_at' => now()->timestamp - 1],
            'no-consent' => ['whatsapp_review_consent' => null],
            'changed-phone' => ['customer_phone' => '+12025550109'],
            'cancelled' => ['status' => 'cancelled'],
            'no-show' => ['status' => 'no_show'],
            'unconfirmed' => ['status' => 'pending'],
            'undone-exit' => ['has_exited' => false],
            'claimed' => ['whatsapp_review_status' => 'sending'],
        ] as $code => $overrides) {
            $copy = $reservationB->replicate();
            $copy->forceFill(['external_id' => $code, ...$overrides])->save();
        }
        Http::fake([self::MESSAGE_API => Http::response(['sid' => 'SM'.str_repeat('2', 32), 'status' => 'queued'], 201)]);
        $this->travel(24)->hours();
        $this->runSender();
        Http::assertSentCount(2);
        foreach ([$reservationA, $reservationB] as $reservation) {
            $settings = $reservation->parking->whatsapp_review_settings;
            Http::assertSent(fn (Request $request) => $request['To'] === 'whatsapp:'.$reservation->customer_phone
                && $request['From'] === 'whatsapp:'.$settings['sender']
                && json_decode($request['ContentVariables'], true)[2] === $settings['review_url']);
        }
        $this->assertSame(2, Reservation::where('whatsapp_review_status', 'accepted')->count());
    }

    public function test_revocation_and_pausing_prevent_requests_while_undoing_exit_restarts_the_delay(): void
    {
        $this->freezeTime();
        $reservation = $this->dueReservation();
        $parking = $reservation->parking;
        $this->actingAs($this->fixtures['partner']);
        $context = ['_parking_context' => $parking->id];
        Http::fake([self::MESSAGE_API => Http::response(['sid' => 'SM'.str_repeat('3', 32), 'status' => 'queued'], 201)]);

        $this->put(route('reservations.whatsapp-review-consent', $reservation), $context + ['consent' => '0'])->assertRedirect();
        $this->travel(24)->hours();
        $this->runSender();
        Http::assertNothingSent();

        $this->put(route('reservations.whatsapp-review-consent', $reservation), $context + ['consent' => '1'])->assertRedirect();
        $this->recordExit($reservation, false);
        $this->assertNull($reservation->fresh()->exited_at);
        $this->runSender();
        Http::assertNothingSent();

        $this->recordExit($reservation);
        $this->travel(23)->hours();
        $this->runSender();
        Http::assertNothingSent();
        $this->put(route('parkings.whatsapp-reviews.update', $parking), $context + ['enabled' => '0'])->assertRedirect();
        $this->travel(1)->hour();
        $this->runSender();
        Http::assertNothingSent();

        $this->put(route('parkings.whatsapp-reviews.update', $parking), $context + ['enabled' => '1'])->assertRedirect();
        $this->runSender();
        Http::assertNothingSent();
    }

    public function test_timeout_is_recorded_without_duplicate_attempts_and_rejections_are_visible(): void
    {
        $this->freezeTime();
        $uncertain = $this->dueReservation('A');
        $failed = $this->dueReservation('B');
        $account = $uncertain->parking->whatsapp_review_settings['account_sid'];
        Http::fake([
            'https://api.twilio.com/2010-04-01/Accounts/'.$account.'/Messages.json' => Http::failedConnection(),
            self::MESSAGE_API => Http::response(['code' => 21610], 400),
        ]);

        $this->travel(24)->hours();
        $this->runSender();
        $this->runSender();
        $this->assertSame('uncertain', $uncertain->fresh()->whatsapp_review_status);
        $this->assertSame('failed', $failed->fresh()->whatsapp_review_status);
        Http::assertSentCount(2);
        $this->actingAs($this->fixtures['partner'])->get(route('reservations.show', $failed))
            ->assertOk()->assertSee('Invio fallito.');
    }

    public function test_public_booking_records_optional_consent_only_with_an_explicit_number_prefix(): void
    {
        $parking = $this->enable();
        $form = route('public.booking.form', ['parking' => $parking->public_booking_key]);
        $this->get($form)->assertOk()->assertSee('whatsapp-review-opt-in');
        $this->get(route('public.booking.form'))->assertOk()->assertDontSee('whatsapp-review-opt-in');

        $data = [
            'parking' => $parking->public_booking_key,
            'product_code' => 'auto_open',
            'arrival_date' => now()->addDays(7)->toDateString(), 'arrival_time' => '10:00',
            'departure_date' => now()->addDays(8)->toDateString(), 'departure_time' => '10:00',
            'spots' => 1, 'passengers_count' => 1,
            'customer_name' => 'Prenotazione di prova', 'customer_email' => 'consent@example.test',
            'customer_phone' => '2025550106', 'license_plate' => 'TEST-WA',
        ];
        $this->from($form)->post(route('public.booking.store'), $data + ['whatsapp_review_opt_in' => '1'])
            ->assertRedirect($form)->assertSessionHasErrors('whatsapp_review_opt_in');
        $this->post(route('public.booking.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull(Reservation::where('customer_email', $data['customer_email'])->sole()->whatsapp_review_consent);

        $this->post(route('public.booking.store'), array_replace($data, [
            'customer_phone' => '+1 (202) 555-0106', 'customer_email' => 'yes-consent@example.test', 'whatsapp_review_opt_in' => '1',
        ]))->assertRedirect()->assertSessionHasNoErrors();
        $saved = Reservation::where('customer_email', 'yes-consent@example.test')->sole();
        $this->assertSame('website', $saved->whatsapp_review_consent['source']);
        $this->assertSame('+12025550106', $saved->whatsapp_review_consent['number']);
        $this->assertNull($saved->exited_at);
        $this->assertArrayNotHasKey('whatsapp_review_consent', $saved->toArray());
        Http::assertNothingSent();
    }
}
