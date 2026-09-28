<?php

namespace Tests\Feature;

use App\Console\Commands\FonnteTestCommand;
use App\Services\FonnteService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FonnteServiceTest extends TestCase
{
    private const TOKEN = 'TEST-TOKEN-abc123';

    private const GROUP = '120363000000000000@g.us';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.fonnte.enabled' => true,
            'services.fonnte.token' => self::TOKEN,
            'services.fonnte.group_id' => self::GROUP,
        ]);
    }

    public function test_send_to_group_posts_token_target_and_message(): void
    {
        Http::fake([FonnteService::ENDPOINT => Http::response(['status' => true])]);

        $this->assertTrue(app(FonnteService::class)->sendToGroup('Halo grup'));

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $r) => $r->url() === 'https://api.fonnte.com/send'
            && $r->method() === 'POST'
            && $r->hasHeader('Authorization', self::TOKEN)
            && $r['target'] === self::GROUP
            && $r['message'] === 'Halo grup'
            && ! isset($r['countryCode']));
    }

    public function test_send_to_phone_uses_country_code_62(): void
    {
        Http::fake([FonnteService::ENDPOINT => Http::response(['status' => true])]);

        $this->assertTrue(app(FonnteService::class)->sendToPhone('081234567890', 'Halo'));

        Http::assertSent(fn (Request $r) => $r['target'] === '081234567890'
            && $r['countryCode'] === '62'
            && $r['message'] === 'Halo');
    }

    public function test_disabled_sends_nothing(): void
    {
        Http::fake();
        config(['services.fonnte.enabled' => false]);

        $this->assertFalse(app(FonnteService::class)->sendToGroup('x'));
        $this->assertFalse(app(FonnteService::class)->sendToPhone('0812', 'x'));

        Http::assertNothingSent();
    }

    public function test_missing_token_or_target_sends_nothing(): void
    {
        Http::fake();
        $fonnte = app(FonnteService::class);

        $this->assertFalse($fonnte->sendToPhone(null, 'x'));
        $this->assertFalse($fonnte->sendToPhone('  ', 'x'));

        config(['services.fonnte.group_id' => '']);
        $this->assertFalse($fonnte->sendToGroup('x'));

        config(['services.fonnte.group_id' => self::GROUP, 'services.fonnte.token' => '']);
        $this->assertFalse($fonnte->sendToGroup('x'));

        Http::assertNothingSent();
    }

    public function test_fonnte_errors_return_false_and_never_throw(): void
    {
        $fonnte = app(FonnteService::class);

        Http::fake([FonnteService::ENDPOINT => Http::response('Server Error', 500)]);
        $this->assertFalse($fonnte->sendToGroup('x'));

        // Fonnte reports most failures as HTTP 200 + status:false.
        Http::fake([FonnteService::ENDPOINT => Http::response(['status' => false, 'reason' => 'invalid token'])]);
        $this->assertFalse($fonnte->sendToGroup('x'));

        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out after 8001 milliseconds'));
        $this->assertFalse($fonnte->sendToGroup('x'));
    }

    public function test_logs_never_contain_the_token(): void
    {
        $logged = [];
        Log::listen(function ($event) use (&$logged) {
            $logged[] = $event->message.' '.json_encode($event->context);
        });

        // Worst case: an error message that echoes the token back.
        Http::fake(fn () => throw new ConnectionException('failed with Authorization '.self::TOKEN));
        app(FonnteService::class)->sendToGroup('x');

        Http::fake([FonnteService::ENDPOINT => Http::response(['status' => false, 'reason' => 'token '.self::TOKEN.' invalid'])]);
        app(FonnteService::class)->sendToPhone('081234567890', 'x');

        $this->assertCount(2, $logged);
        foreach ($logged as $line) {
            $this->assertStringNotContainsString(self::TOKEN, $line);
            $this->assertStringNotContainsString('081234567890', $line);
        }
    }

    public function test_artisan_fonnte_test_sends_test_message_to_group(): void
    {
        Http::fake([FonnteService::ENDPOINT => Http::response(['status' => true])]);

        $this->artisan('fonnte:test')
            ->doesntExpectOutputToContain(self::TOKEN)
            ->doesntExpectOutputToContain(self::GROUP)
            ->expectsOutputToContain('Terkirim')
            ->assertSuccessful();

        Http::assertSent(fn (Request $r) => $r['target'] === self::GROUP
            && $r['message'] === FonnteTestCommand::MESSAGE
            && $r['message'] === 'Tes notifikasi sistem magang');
    }

    public function test_artisan_fonnte_test_fails_without_config(): void
    {
        Http::fake();
        config(['services.fonnte.enabled' => false]);

        $this->artisan('fonnte:test')
            ->expectsOutputToContain('Konfigurasi belum lengkap')
            ->assertFailed();

        Http::assertNothingSent();
    }
}
