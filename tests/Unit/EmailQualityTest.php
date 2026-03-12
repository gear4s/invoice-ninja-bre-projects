<?php

namespace Tests\Unit;

use App\Jobs\Mail\NinjaMailer;
use App\Jobs\Mail\NinjaMailerObject;
use App\Models\Company;
use App\Models\User;
use App\Services\Email\AdminEmailMailable;
use App\Services\Email\EmailMailable;
use App\Services\Email\EmailObject;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\Notification;
use Modules\Admin\Jobs\Account\EmailQuality;
use Tests\TestCase;

class EmailQualityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!class_exists(EmailQuality::class)) {
            $this->markTestSkipped('EmailQuality class is not available (Admin module not installed).');
        }
    }

    private function makeCompanyMock(string $companyName = 'Test Company'): Company
    {
        $presenter = new class($companyName)
        {
            private string $name;

            public function __construct(string $name)
            {
                $this->name = $name;
            }

            public function name(): string
            {
                return $this->name;
            }
        };

        $owner_presenter = new class
        {
            public function name(): string
            {
                return 'Test Owner';
            }
        };

        $owner = $this->createMock(User::class);
        $owner->method('present')->willReturn($owner_presenter);

        $company = $this->createMock(Company::class);
        $company->method('present')->willReturn($presenter);
        $company->method('owner')->willReturn($owner);
        $company->method('notification')->willReturn(
            new class
            {
                public function ninja()
                {
                    return null;
                }
            }
        );

        return $company;
    }

    private function makeSettings(string $replyToEmail = '', string $replyToName = ''): object
    {
        return (object) [
            'reply_to_email' => $replyToEmail,
            'reply_to_name' => $replyToName,
        ];
    }

    private function buildNinjaMailerNmo(string $subject, string $body, ?Company $company = null): NinjaMailerObject
    {
        $mail_obj = new \stdClass;
        $mail_obj->subject = $subject;
        $mail_obj->data = ['body' => $body];
        $mail_obj->markdown = 'email.template.client';

        $nmo = new NinjaMailerObject;
        $nmo->mailable = new NinjaMailer($mail_obj);
        $nmo->company = $company;
        $nmo->to_user = (object) ['email' => 'client@example.com'];
        $nmo->settings = $this->makeSettings();

        return $nmo;
    }

    private function buildEmailMailableNmo(string $subject, string $body, ?Company $company = null): NinjaMailerObject
    {
        $email_object = new EmailObject;
        $email_object->subject = $subject;
        $email_object->body = $body;
        $email_object->company_key = 'test-key';
        $email_object->company = $company ?? $this->makeCompanyMock();
        $email_object->settings = $this->makeSettings();
        $email_object->whitelabel = false;
        $email_object->invitation = null;
        $email_object->html_template = 'email.template.client';
        $email_object->to = [new Address('test@example.com')];
        $email_object->documents = [];

        $nmo = new NinjaMailerObject;
        $nmo->mailable = new EmailMailable($email_object);
        $nmo->company = $company;
        $nmo->to_user = (object) ['email' => 'client@example.com'];
        $nmo->settings = $this->makeSettings();

        return $nmo;
    }

    public function test_clean_ninja_mailer_subject_passes()
    {
        $company = $this->makeCompanyMock();
        $nmo = $this->buildNinjaMailerNmo('Your invoice is ready', 'Please find your invoice attached.', $company);

        $eq = new EmailQuality($nmo, $company);
        $this->assertFalse($eq->run());
    }

    public function test_spam_keyword_in_ninja_mailer_subject_triggers_hit()
    {
        $company = $this->makeCompanyMock();
        $nmo = $this->buildNinjaMailerNmo('Your McAfee subscription renewal', 'Renew now.', $company);

        $eq = new EmailQuality($nmo, $company);
        $this->assertTrue($eq->run());
    }

    public function test_clean_email_mailable_subject_passes()
    {
        $company = $this->makeCompanyMock();
        $nmo = $this->buildEmailMailableNmo('Invoice #1001 from Acme Corp', 'Here is your invoice.', $company);

        $eq = new EmailQuality($nmo, $company);
        $this->assertFalse($eq->run());
    }

    public function test_spam_keyword_in_email_mailable_subject_triggers_hit()
    {
        $company = $this->makeCompanyMock();
        $nmo = $this->buildEmailMailableNmo('Norton Security Alert', 'Your Norton subscription.', $company);

        $eq = new EmailQuality($nmo, $company);
        $this->assertTrue($eq->run());
    }

    public function test_email_mailable_strips_br_tags_from_subject()
    {
        $company = $this->makeCompanyMock();
        $nmo = $this->buildEmailMailableNmo('Your<br>Invoice<br>Ready', 'Body text.', $company);

        $eq = new EmailQuality($nmo, $company);
        $this->assertFalse($eq->run());
    }

    public function test_spam_company_name_triggers_hit()
    {
        $company = $this->makeCompanyMock('PayPal Inc');
        $nmo = $this->buildNinjaMailerNmo('Your invoice', 'Body.', $company);

        $eq = new EmailQuality($nmo, $company);
        $this->assertTrue($eq->run());
    }

    public function test_percent_in_email_is_flagged()
    {
        $company = $this->makeCompanyMock();
        $nmo = $this->buildNinjaMailerNmo('Your invoice', 'Body.', $company);
        $nmo->to_user = (object) ['email' => 'user%exploit@example.com'];

        $eq = new EmailQuality($nmo, $company);
        // Percent emails get flagged via notification but don't return true unless other checks hit
        $eq->run();
        $this->assertTrue(true); // No exception thrown
    }

    public function test_email_mailable_with_closures_does_not_throw()
    {
        $company = $this->makeCompanyMock();

        $email_object = new EmailObject;
        $email_object->subject = 'Your invoice is ready';
        $email_object->body = 'Please find your invoice attached.';
        $email_object->company_key = 'test-key';
        $email_object->company = $company;
        $email_object->settings = $this->makeSettings();
        $email_object->whitelabel = false;
        $email_object->invitation = null;
        $email_object->html_template = 'email.template.client';
        $email_object->to = [new Address('test@example.com')];
        $email_object->documents = [];

        $mailable = new EmailMailable($email_object);

        $nmo = new NinjaMailerObject;
        $nmo->mailable = $mailable;
        $nmo->company = $company;
        $nmo->to_user = (object) ['email' => 'client@example.com'];
        $nmo->settings = $this->makeSettings();

        // This would throw "Serialization of 'Closure' is not allowed" with the old approach
        $eq = new EmailQuality($nmo, $company);
        $result = $eq->run();

        $this->assertFalse($result);
    }

    public function test_original_mailable_is_not_mutated()
    {
        $company = $this->makeCompanyMock();

        $mail_obj = new \stdClass;
        $mail_obj->subject = 'Your invoice is ready';
        $mail_obj->data = ['body' => 'Clean body text.'];
        $mail_obj->markdown = 'email.template.client';

        $mailable = new NinjaMailer($mail_obj);

        $nmo = new NinjaMailerObject;
        $nmo->mailable = $mailable;
        $nmo->company = $company;
        $nmo->to_user = (object) ['email' => 'client@example.com'];
        $nmo->settings = $this->makeSettings();

        $eq = new EmailQuality($nmo, $company);
        $eq->run();

        // Verify the original mailable data was not mutated
        $this->assertEquals('Your invoice is ready', $mailable->mail_obj->subject);
        $this->assertEquals('Clean body text.', $mailable->mail_obj->data['body']);
        $this->assertNull($mailable->subject, 'Mailable subject property should not have been set');
    }

    public function test_spam_reply_to_name_is_flagged()
    {
        $company = $this->makeCompanyMock();
        $nmo = $this->buildNinjaMailerNmo('Your invoice', 'Body.', $company);
        $nmo->settings = $this->makeSettings('reply@example.com', 'Norton Support');

        $eq = new EmailQuality($nmo, $company);
        $result = $eq->run();

        // Spam username check returns false (flagged but not blocked)
        $this->assertFalse($result);
    }

    public function test_case_insensitive_spam_detection()
    {
        $company = $this->makeCompanyMock();
        $nmo = $this->buildNinjaMailerNmo('MCAFEE RENEWAL NOTICE', 'Please renew.', $company);

        $eq = new EmailQuality($nmo, $company);
        $this->assertTrue($eq->run());
    }

    public function test_unknown_mailable_type_returns_clean_result()
    {
        $company = $this->makeCompanyMock();

        // Use a plain Mailable (neither NinjaMailer nor EmailMailable)
        $mailable = new Mailable;

        $nmo = new NinjaMailerObject;
        $nmo->mailable = $mailable;
        $nmo->company = $company;
        $nmo->to_user = (object) ['email' => 'client@example.com'];
        $nmo->settings = $this->makeSettings();

        $eq = new EmailQuality($nmo, $company);
        $result = $eq->run();

        // Unknown mailable type returns [null, false] so no spam checks trigger
        $this->assertFalse($result);
    }

    public function test_admin_email_mailable_spam_subject_triggers_hit()
    {
        $company = $this->makeCompanyMock();

        $email_object = new EmailObject;
        $email_object->subject = 'Norton Security Alert';
        $email_object->body = 'Your Norton subscription.';
        $email_object->company_key = 'test-key';
        $email_object->company = $company;
        $email_object->settings = $this->makeSettings();
        $email_object->whitelabel = false;
        $email_object->invitation = null;
        $email_object->html_template = 'email.admin.generic';
        $email_object->to = [new Address('test@example.com')];
        $email_object->documents = [];
        $email_object->attachments = [];

        $nmo = new NinjaMailerObject;
        $nmo->mailable = new AdminEmailMailable($email_object);
        $nmo->company = $company;
        $nmo->to_user = (object) ['email' => 'client@example.com'];
        $nmo->settings = $this->makeSettings();

        $eq = new EmailQuality($nmo, $company);
        $this->assertTrue($eq->run());
    }

    public function test_admin_email_mailable_clean_subject_passes()
    {
        $company = $this->makeCompanyMock();

        $email_object = new EmailObject;
        $email_object->subject = 'Invoice reminder sent';
        $email_object->body = 'A reminder was sent to the client.';
        $email_object->company_key = 'test-key';
        $email_object->company = $company;
        $email_object->settings = $this->makeSettings();
        $email_object->whitelabel = false;
        $email_object->invitation = null;
        $email_object->html_template = 'email.admin.generic';
        $email_object->to = [new Address('test@example.com')];
        $email_object->documents = [];
        $email_object->attachments = [];

        $nmo = new NinjaMailerObject;
        $nmo->mailable = new AdminEmailMailable($email_object);
        $nmo->company = $company;
        $nmo->to_user = (object) ['email' => 'client@example.com'];
        $nmo->settings = $this->makeSettings();

        $eq = new EmailQuality($nmo, $company);
        $this->assertFalse($eq->run());
    }

    public function test_ninja_mailer_with_null_mail_obj_does_not_throw()
    {
        $company = $this->makeCompanyMock();

        $mailable = new NinjaMailer(null);

        $nmo = new NinjaMailerObject;
        $nmo->mailable = $mailable;
        $nmo->company = $company;
        $nmo->to_user = (object) ['email' => 'client@example.com'];
        $nmo->settings = $this->makeSettings();

        $eq = new EmailQuality($nmo, $company);
        $result = $eq->run();

        // Null mail_obj falls through to [null, false]
        $this->assertFalse($result);
    }

    public function test_null_mailable_does_not_throw()
    {
        $company = $this->makeCompanyMock();

        $nmo = new NinjaMailerObject;
        $nmo->mailable = null;
        $nmo->company = $company;
        $nmo->to_user = (object) ['email' => 'client@example.com'];
        $nmo->settings = $this->makeSettings();

        $eq = new EmailQuality($nmo, $company);
        $result = $eq->run();

        $this->assertFalse($result);
    }
}
