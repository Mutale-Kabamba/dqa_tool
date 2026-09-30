<?php

namespace Tests\Feature;

use App\Models\Audit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditPdfTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Audit $audit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::where('email', 'admin@dqa.local')->first();
        $this->audit = Audit::where('audit_code', 'AUD-001')->first();
    }

    public function test_can_download_audit_pdf_dossier(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.audits.pdf', ['audit' => $this->audit]));

        $response->assertSuccessful();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('DQA_Dossier_AUD-001.pdf', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_can_stream_audit_pdf_dossier(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('admin.audits.pdf.stream', ['audit' => $this->audit]));

        $response->assertSuccessful();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }
}
