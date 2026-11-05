<?php

namespace Tests\Feature;

use App\Enums\HostelStudentRequestStatus;
use App\Models\HostelRequestLink;
use App\Models\HostelStudentRequest;
use App\Models\User;
use App\Notifications\NewHostelStudentRequest;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HostelStudentRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_managers_generate_one_link_that_never_changes(): void
    {
        $manager = User::factory()->role('hostel_manager')->create();
        $admin = User::factory()->role('admin')->create();

        $this->actingAs($manager)->get(route('hostel.student-requests.index'))
            ->assertOk()
            ->assertSee('Generate link');

        $this->post(route('hostel.student-requests.link'))->assertRedirect(route('hostel.student-requests.index'));
        $link = HostelRequestLink::query()->sole();

        $this->actingAs($admin)->post(route('hostel.student-requests.link'))->assertRedirect();
        $this->assertSame(1, HostelRequestLink::query()->count());
        $this->assertSame($link->token, HostelRequestLink::current()->token);

        $this->get(route('hostel.student-requests.index'))
            ->assertOk()
            ->assertSee($link->url())
            ->assertSee('https://wa.me/?text=', false)
            ->assertSee('Copy link');
    }

    public function test_only_hostel_managers_and_admins_can_open_student_requests(): void
    {
        foreach (['hostel_manager', 'admin', 'super_admin', 'developer'] as $role) {
            $this->actingAs(User::factory()->role($role)->create())
                ->get(route('hostel.student-requests.index'))
                ->assertOk();
        }

        foreach (['slt_employee', 'coordinator', 'canteen', 'management'] as $role) {
            $user = User::factory()->role($role)->create();
            $this->actingAs($user)->get(route('hostel.student-requests.index'))->assertForbidden();
            $this->actingAs($user)->post(route('hostel.student-requests.link'))->assertForbidden();
        }
    }

    public function test_student_submits_form_without_login_and_managers_get_bell_notification(): void
    {
        Notification::fake();
        $manager = User::factory()->role('hostel_manager')->create();
        $employee = User::factory()->role('slt_employee')->create();
        $link = $this->link();

        $this->get(route('hostel.student-form', $link->token))
            ->assertOk()
            ->assertSee('Request your hostel room.')
            ->assertSee('name="student_id"', false)
            ->assertSee('name="emergency_contact_phone"', false);

        $this->post(route('hostel.student-form.submit', $link->token), $this->payload())
            ->assertRedirect(route('hostel.student-form.done', $link->token));

        $studentRequest = HostelStudentRequest::query()->sole();
        $this->assertSame('Nimali Perera', $studentRequest->student_name);
        $this->assertSame(HostelStudentRequestStatus::PENDING, $studentRequest->status);
        $this->assertStringStartsWith('HSR-'.now()->format('Y').'-', $studentRequest->reference);

        Notification::assertSentTo($manager, NewHostelStudentRequest::class);
        Notification::assertNotSentTo($employee, NewHostelStudentRequest::class);

        $this->get(route('hostel.student-form.done', $link->token))->assertOk()->assertSee($studentRequest->reference);
        $this->get(route('hostel.student-form.done', $link->token))->assertRedirect(route('hostel.student-form', $link->token));
    }

    public function test_bell_notification_links_to_the_request(): void
    {
        $manager = User::factory()->role('hostel_manager')->create();
        $link = $this->link();

        $this->post(route('hostel.student-form.submit', $link->token), $this->payload())->assertRedirect();
        $reference = HostelStudentRequest::query()->sole()->reference;

        $this->actingAs($manager)->getJson(route('notifications.feed'))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Student hostel request']);
        $this->get(route('hostel.student-requests.index', ['search' => $reference]))
            ->assertOk()
            ->assertSee($reference)
            ->assertSee('Nimali Perera');
    }

    public function test_invalid_link_and_bad_input_are_rejected(): void
    {
        $link = $this->link();

        $this->get(route('hostel.student-form', str_repeat('x', 40)))
            ->assertNotFound()
            ->assertSee('This link is not valid')
            ->assertSee('011 2954 069');
        $this->get(route('hostel.student-form', substr($link->token, 0, 30)))->assertNotFound();
        $this->get('/hostel-request')->assertNotFound()->assertSee('This link is not valid');
        $this->post(route('hostel.student-form.submit', str_repeat('x', 40)), $this->payload())->assertNotFound();

        $this->from(route('hostel.student-form', $link->token))
            ->post(route('hostel.student-form.submit', $link->token), $this->payload([
                'student_name' => '',
                'phone' => 'abc',
                'check_out_date' => now()->addDays(3)->toDateString(),
                'check_out_time' => '09:00',
            ]))
            ->assertSessionHasErrors(['student_name', 'phone', 'check_out_time']);

        $this->post(route('hostel.student-form.submit', $link->token), $this->payload(['website' => 'spam']))
            ->assertSessionHasErrors('website');

        $this->assertSame(0, HostelStudentRequest::query()->count());
    }

    public function test_link_with_extra_characters_pasted_after_it_still_opens_the_form(): void
    {
        $link = $this->link();

        foreach (['0565556567', '.', ')', '%20Thanks'] as $extra) {
            $this->get('/hostel-request/'.$link->token.$extra)
                ->assertRedirect(route('hostel.student-form', $link->token));
        }

        $this->get('/hostel-request/'.$link->token.'?ref=whatsapp')->assertOk()->assertSee('Request your hostel room.');
    }

    public function test_manager_decisions_send_sms_and_follow_allowed_steps(): void
    {
        config(['sms.driver' => 'http', 'sms.http.url' => 'https://sms.example.test/send', 'sms.http.token' => 'secret']);
        Http::fake(['sms.example.test/*' => Http::response(['ok' => true])]);
        $manager = User::factory()->role('hostel_manager')->create();
        $link = $this->link();
        $this->post(route('hostel.student-form.submit', $link->token), $this->payload())->assertRedirect();
        $studentRequest = HostelStudentRequest::query()->sole();

        $this->actingAs($manager)
            ->from(route('hostel.student-requests.index'))
            ->post(route('hostel.student-requests.decision', $studentRequest), ['status' => 'rejected'])
            ->assertSessionHasErrors('decision_note');

        $this->post(route('hostel.student-requests.decision', $studentRequest), [
            'status' => 'approved',
            'decision_note' => 'Room 12 is ready.',
        ])->assertSessionHas('success');

        $studentRequest->refresh();
        $this->assertSame(HostelStudentRequestStatus::APPROVED, $studentRequest->status);
        $this->assertSame($manager->id, $studentRequest->decided_by_user_id);
        $this->assertSame('sent', $studentRequest->sms_status);
        Http::assertSent(fn ($request) => $request['to'] === '94771234567'
            && str_contains($request['message'], $studentRequest->reference)
            && str_contains($request['message'], 'APPROVED')
            && str_contains($request['message'], 'Room 12 is ready.')
            && $request->hasHeader('Authorization', 'Bearer secret'));

        $this->post(route('hostel.student-requests.decision', $studentRequest), ['status' => 'rejected', 'decision_note' => 'Too late'])
            ->assertSessionHasErrors('status');

        $this->post(route('hostel.student-requests.decision', $studentRequest), ['status' => 'cancelled', 'decision_note' => 'Hostel closed for repairs.'])
            ->assertSessionHas('success');
        $this->assertSame(HostelStudentRequestStatus::CANCELLED, $studentRequest->fresh()->status);
        Http::assertSentCount(2);
    }

    public function test_failed_sms_is_recorded_and_reported(): void
    {
        config(['sms.driver' => 'http', 'sms.http.url' => 'https://sms.example.test/send']);
        Http::fake(['sms.example.test/*' => Http::response('down', 500)]);
        $manager = User::factory()->role('admin')->create();
        $this->post(route('hostel.student-form.submit', $this->link()->token), $this->payload())->assertRedirect();
        $studentRequest = HostelStudentRequest::query()->sole();

        $this->actingAs($manager)
            ->post(route('hostel.student-requests.decision', $studentRequest), ['status' => 'approved'])
            ->assertSessionHas('warning');

        $this->assertSame('failed', $studentRequest->fresh()->sms_status);
        $this->assertSame(HostelStudentRequestStatus::APPROVED, $studentRequest->fresh()->status);
    }

    public function test_list_paginates_newest_first_and_filters_by_status(): void
    {
        $manager = User::factory()->role('hostel_manager')->create();
        foreach (range(1, 12) as $i) {
            HostelStudentRequest::query()->create(array_merge($this->payload(), [
                'student_name' => sprintf('Student %02d', $i),
                'status' => $i === 1 ? HostelStudentRequestStatus::APPROVED : HostelStudentRequestStatus::PENDING,
            ]));
            $this->travel(1)->minutes();
        }

        $this->actingAs($manager)->get(route('hostel.student-requests.index'))
            ->assertOk()
            ->assertSee('Showing 1–10 of 12')
            ->assertSeeInOrder(['Student 12', 'Student 11'])
            ->assertSee('id="hsr-results"', false)
            ->assertSee('page=2', false);

        $this->get(route('hostel.student-requests.index', ['page' => 2]), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Showing 11–12 of 12')
            ->assertSee('Student 01');

        $this->get(route('hostel.student-requests.index', ['status' => 'approved']))
            ->assertOk()
            ->assertSee('Showing 1–1 of 1')
            ->assertDontSee('Student requests pages')
            ->assertSee('Student 01')
            ->assertDontSee('Student 02');
    }

    public function test_sidebar_shows_student_requests_for_managers_only(): void
    {
        $this->actingAs(User::factory()->role('hostel_manager')->create())
            ->get(route('dashboard'))
            ->assertSee('hide-menu">Student Requests', false);

        $this->actingAs(User::factory()->role('slt_employee')->create())
            ->get(route('dashboard'))
            ->assertDontSee('hide-menu">Student Requests', false);
    }

    private function link(): HostelRequestLink
    {
        return HostelRequestLink::firstOrGenerate(User::factory()->role('hostel_manager')->create());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'student_name' => 'Nimali Perera',
            'student_id' => 'NB/IT/2026/045',
            'check_in_date' => now()->addDays(3)->toDateString(),
            'check_in_time' => '11:00',
            'check_out_date' => now()->addDays(5)->toDateString(),
            'check_out_time' => '10:00',
            'home_town' => 'Kurunegala',
            'phone' => '077 123 4567',
            'emergency_contact_name' => 'Sunil Perera',
            'emergency_contact_phone' => '071 765 4321',
        ], $overrides);
    }
}
